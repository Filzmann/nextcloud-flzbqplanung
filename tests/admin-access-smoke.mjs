import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const template = readFileSync(new URL('../templates/index.php', import.meta.url), 'utf8')
const style = readFileSync(new URL('../css/style.css', import.meta.url), 'utf8')

const element = () => ({
    children: [],
    dataset: {},
    disabled: false,
    hidden: false,
    textContent: '',
    attributes: {},
    focused: false,
    listeners: {},
    append(child) { this.children.push(child) },
    addEventListener(type, callback) { (this.listeners[type] = this.listeners[type] || []).push(callback) },
    setAttribute(name, value) { this.attributes[name] = String(value) },
    getAttribute(name) { return name in this.attributes ? this.attributes[name] : null },
    focus() { this.focused = true },
    replaceChildren() { this.children = [] },
})
const form = element()
form.elements = { enabled: { checked: true } }
const history = element()
const status = element()
const overlay = element()
overlay.hidden = true
const trigger = element()
const closeButton = element()
const docListeners = {}
const calls = []

globalThis.document = {
    getElementById: id => ({
        'flz-bq-full-access-form': form,
        'flz-bq-full-access-history': history,
        'flz-bq-full-access-status': status,
        'flz-bq-admin-access-overlay': overlay,
        'bq-admin-grant-warning': trigger,
        'flz-bq-admin-access-overlay-close': closeButton,
    }[id] ?? null),
    createElement: () => element(),
    addEventListener: (type, callback) => { (docListeners[type] = docListeners[type] || []).push(callback) },
    querySelectorAll: () => [],
    head: { appendChild() {} },
}
globalThis.window = globalThis
globalThis.OC = {
    requestToken: 'csrf-token',
    generateUrl: value => `/nextcloud${value}`,
}
globalThis.FormData = class {
    get(name) {
        return { enabled: 'on', targetUid: ' admin-target ', durationMinutes: '60' }[name]
    }
}
globalThis.fetch = async (url, options = {}) => {
    calls.push([url, options.method ?? 'GET', options.headers?.requesttoken ?? null, options.body ?? null])
    return { ok: true, json: async () => ({ history: [] }) }
}

await import('../js/shared/overlay.js')
await import('../js/admin-access.js')
const fire = async (el, type, event) => { for (const callback of el.listeners[type] || []) await callback(event) }
await new Promise(resolve => setImmediate(resolve))
assert.deepEqual(calls, [], 'Historie wird vor dem Öffnen des Overlays geladen.')

// Warn-Icon öffnet das Overlay und lädt die Historie.
await fire(trigger, 'click', { target: trigger })
assert.equal(overlay.hidden, false)
assert.equal(trigger.getAttribute('aria-expanded'), 'true')
assert.equal(closeButton.focused, true)

await fire(form, 'submit', { preventDefault() {} })
const button = { dataset: { revokeUid: 'admin-target' }, disabled: false }
await fire(history, 'click', { target: { closest: () => button } })

assert.deepEqual(calls.map(([url, method]) => [url, method]), [
    ['/nextcloud/apps/flzbqplanung/api/admin/full-access', 'GET'],
    ['/nextcloud/apps/flzbqplanung/api/admin/full-access', 'POST'],
    ['/nextcloud/apps/flzbqplanung/api/admin/full-access', 'GET'],
    ['/nextcloud/apps/flzbqplanung/api/admin/full-access/admin-target', 'DELETE'],
    ['/nextcloud/apps/flzbqplanung/api/admin/full-access', 'GET'],
])
assert.equal(calls[1][2], 'csrf-token')
assert.equal(calls[3][2], 'csrf-token')
assert.match(status.textContent, /widerrufen/)

// Escape, Schließen-Button und Backdrop-Klick schließen das Overlay.
for (const callback of docListeners.keydown || []) await callback({ key: 'Escape' })
assert.equal(overlay.hidden, true)
assert.equal(trigger.getAttribute('aria-expanded'), 'false')
assert.equal(trigger.focused, true)
await fire(trigger, 'click', { target: trigger })
await fire(closeButton, 'click', {})
assert.equal(overlay.hidden, true)
await fire(trigger, 'click', { target: trigger })
await fire(overlay, 'click', { target: overlay })
assert.equal(overlay.hidden, true)
assert.deepEqual(calls.slice(-2).map(([url, method]) => [url, method]), [
    ['/nextcloud/apps/flzbqplanung/api/admin/full-access', 'GET'],
    ['/nextcloud/apps/flzbqplanung/api/admin/full-access', 'GET'],
])

for (const contract of ['id="flz-bq-admin-access-overlay"', 'role="dialog"', 'aria-modal="true"', 'id="bq-admin-grant-warning"', 'aria-haspopup="dialog"', 'aria-controls="flz-bq-admin-access-overlay"', 'Datenschutzbeauftragte']) assert.ok(template.includes(contract), `Overlay für fehlenden Admin-Vollzugriff fehlt: ${contract}`)
assert.ok(!template.includes('<details class="bq-admin-grant-warning"'), 'Warn-Icon ist noch als details-Element umgesetzt.')
assert.ok(!template.includes('href="#flz-bq-full-access-heading"'), 'Veralteter Anchor-Verweis ist noch vorhanden.')
const bundleOverlay = readFileSync(new URL('../js/shared/overlay.js', import.meta.url), 'utf8')
for (const contract of ['class="flz-shared-warn-trigger"', 'class="flz-shared-overlay"', "script(Application::APP_ID, 'shared/overlay')"]) assert.ok(template.includes(contract), `Geteilte Overlay-Klassen/Registrierung fehlen in der Vorlage: ${contract}`)
assert.ok(bundleOverlay.includes('.flz-shared-warn-trigger') && bundleOverlay.includes('.flz-shared-overlay'), 'Warn-Icon und Overlay werden nicht mehr durch das geteilte Bundle gestaltet.')
