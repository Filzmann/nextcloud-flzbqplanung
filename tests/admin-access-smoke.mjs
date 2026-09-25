import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const template = readFileSync(new URL('../templates/index.php', import.meta.url), 'utf8')
const style = readFileSync(new URL('../css/style.css', import.meta.url), 'utf8')

const element = () => ({
    children: [],
    dataset: {},
    disabled: false,
    textContent: '',
    listeners: {},
    append(child) { this.children.push(child) },
    addEventListener(type, callback) { this.listeners[type] = callback },
    replaceChildren() { this.children = [] },
})
const form = element()
form.elements = { enabled: { checked: true } }
const history = element()
const status = element()
const calls = []

globalThis.document = {
    getElementById: id => ({
        'adbq-full-access-form': form,
        'adbq-full-access-history': history,
        'adbq-full-access-status': status,
    }[id] ?? null),
    createElement: () => element(),
}
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

await import('../js/admin-access.js')
await new Promise(resolve => setImmediate(resolve))
await form.listeners.submit({ preventDefault() {} })
const button = { dataset: { revokeUid: 'admin-target' }, disabled: false }
await history.listeners.click({ target: { closest: () => button } })

assert.deepEqual(calls.map(([url, method]) => [url, method]), [
    ['/nextcloud/apps/adbqplanung/api/admin/full-access', 'GET'],
    ['/nextcloud/apps/adbqplanung/api/admin/full-access', 'POST'],
    ['/nextcloud/apps/adbqplanung/api/admin/full-access', 'GET'],
    ['/nextcloud/apps/adbqplanung/api/admin/full-access/admin-target', 'DELETE'],
    ['/nextcloud/apps/adbqplanung/api/admin/full-access', 'GET'],
])
assert.equal(calls[1][2], 'csrf-token')
assert.equal(calls[3][2], 'csrf-token')
assert.match(status.textContent, /widerrufen/)
for (const contract of ['bq-admin-grant-warning', '<details', 'Datenschutzbeauftragte', 'target="_blank"']) assert.ok(template.includes(contract), `Titelwarnung für fehlenden Admin-Vollzugriff fehlt: ${contract}`)
assert.ok(style.includes('.bq-admin-grant-warning'), 'Titelwarnung für fehlenden Admin-Vollzugriff ist nicht als kleines Floating-Icon gestaltet.')
