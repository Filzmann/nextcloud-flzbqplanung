import assert from 'node:assert/strict'

globalThis.window = globalThis
globalThis.document = {
    getElementById(id) {
        if (id === 'flz-shared-overlay-styles') return this.head.children[0] ?? null;
        return id === 'smoke-notice' ? this._notice ?? null : null;
    },
    createElement: () => ({ id: '', textContent: '', children: [], append(child) { this.children.push(child) } }),
    querySelectorAll: () => [],
    addEventListener: () => {},
    head: { children: [], appendChild(child) { this.children.push(child) } },
}
globalThis.OC = { generateUrl: (path) => `/index.php${path}`, requestToken: 'smoke-token' }

for (const file of ['api-client.js', 'model.js', 'overlay.js', 'repository.js', 'ui.js']) {
    await import(`../js/shared/${file}`)
}

const shared = globalThis.window.OCA?.FlzBqPlanning?.Shared
assert.ok(shared?.api?.ApiClient, 'ApiClient fehlt im Bundle.')
assert.ok(shared?.models?.Model, 'Model fehlt im Bundle.')
assert.ok(shared?.repositories?.Repository, 'Repository fehlt im Bundle.')
assert.ok(shared?.ui?.Notice, 'Notice fehlt im Bundle.')
assert.ok(shared?.ui?.Overlay && shared?.ui?.AdminAccessOverlay, 'Overlay-Primitives fehlen im Bundle.')
assert.ok(!('LocalBase' in globalThis.window), 'Das Bundle erzeugt weiterhin window.LocalBase.')

const requests = []
globalThis.fetch = async (url, options = {}) => {
    requests.push([url, options])
    return { ok: true, status: 200, text: async () => JSON.stringify({ ok: true }) }
}
const client = new shared.api.ApiClient({ appId: 'flzbqplanung' })
await client.request('/api/state')
assert.strictEqual(requests[0][0], '/index.php/apps/flzbqplanung/api/state')
assert.strictEqual(requests[0][1].headers.requesttoken, 'smoke-token')

class Entry extends shared.models.Model {
    constructor(data) { super(); this.data = data }
    toArray() { return { ...this.data } }
}
assert.strictEqual(JSON.stringify(Entry.get({ id: 1 }).to_array()), JSON.stringify({ id: 1 }))
assert.strictEqual(Entry.get_all([{ id: 1 }, null, { id: 2 }]).length, 2)
assert.strictEqual(Entry.get(null), null)
assert.throws(() => Entry.get({}).save(), /gespeichert/)

const repoCalls = []
const repo = new shared.repositories.Repository({
    request: async (path, options = {}) => { repoCalls.push([path, options]); return { ok: true } },
})
await repo.request('/api/x')
await repo.post('/api/y', { a: 1 })
assert.strictEqual(repo.encode('a b'), 'a%20b')
assert.deepStrictEqual(repoCalls, [
    ['/api/x', {}],
    ['/api/y', { method: 'POST', body: JSON.stringify({ a: 1 }) }],
])

const noticeElement = { className: '', hidden: true, textContent: '' }
globalThis.document._notice = noticeElement
const notice = new shared.ui.Notice('smoke-notice', { baseClass: 'smoke-notice', typeClassPrefix: 'smoke-notice--' })
notice.success('Bereit')
assert.strictEqual(noticeElement.textContent, 'Bereit')
assert.strictEqual(noticeElement.hidden, false)
assert.strictEqual(noticeElement.className, 'smoke-notice smoke-notice--success')
notice.clear()
assert.strictEqual(noticeElement.hidden, true)

shared.ui.injectOverlayStyles()
shared.ui.injectOverlayStyles()
assert.strictEqual(globalThis.document.head.children.length, 1, 'Overlay-Styles werden genau einmal injiziert.')
assert.strictEqual(globalThis.document.head.children[0].id, 'flz-shared-overlay-styles')

console.log('Filzmann BQ-Planer shared bundle runtime smoke passed.')
