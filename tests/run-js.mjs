import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)))
const template = await readFile(path.join(root, 'templates/index.php'), 'utf8')
const css = await readFile(path.join(root, 'css/style.css'), 'utf8')
const script = await readFile(path.join(root, 'js/main.js'), 'utf8')

assert.match(template, /<main[^>]+aria-labelledby=/)
assert.match(template, /<section[^>]+aria-label=/)
assert.match(template, /data-endpoint=/)
assert.match(template, /max="10"/)
assert.match(template, /keine Warteliste/i)
assert.match(template, /Nachholplätze/)
assert.match(template, /Dozentinnenpool/)
assert.match(template, /Haupt-PFK/)
assert.match(template, /Externe Anfrage erfassen/)
assert.match(template, /targetStatus/)
assert.match(css, /overflow-y:\s*auto/)
assert.match(css, /min-height:\s*0/)
assert.doesNotMatch(css, /body\s*\{/)
assert.doesNotMatch(script, /window\./)

const listeners = {}
const app = {
    dataset: {},
    addEventListener(type, callback) {
        listeners[type] = callback
    },
}
const feedback = { textContent: '', dataset: {} }
let reloads = 0
const requests = []
globalThis.document = {
    addEventListener(type, callback) {
        assert.equal(type, 'DOMContentLoaded')
        callback()
    },
    getElementById(id) {
        if (id === 'adbqplanung-app') return app
        if (id === 'bq-feedback') return feedback
        return null
    },
}
globalThis.OC = {
    requestToken: 'csrf-token',
    generateUrl: value => `/nextcloud${value}`,
}
globalThis.location = { reload: () => { reloads++ } }
globalThis.FormData = class {
    constructor(form) {
        this.form = form
    }
    entries() {
        return Object.entries(this.form.fields)
    }
}
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return { ok: true, json: async () => ({ data: { id: 1 } }) }
}
await import('../js/main.js')
assert.equal(app.dataset.planningCore, 'ready')

const form = {
    dataset: { endpoint: '/api/runs', method: 'POST' },
    fields: { label: 'BQ 09/26', capacity: '10', reflectionMonthOffsets: '1,3,4' },
    matches: selector => selector === 'form[data-endpoint]',
}
await listeners.submit({ target: form, preventDefault() {} })
assert.equal(requests[0].url, '/nextcloud/apps/adbqplanung/api/runs')
assert.equal(requests[0].options.headers.requesttoken, 'csrf-token')
assert.deepEqual(JSON.parse(requests[0].options.body), {
    label: 'BQ 09/26',
    capacity: 10,
    reflectionMonthOffsets: [1, 3, 4],
})
assert.equal(reloads, 1)

globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: 'Ungültige Planung' }) })
await listeners.submit({ target: form, preventDefault() {} })
assert.equal(feedback.textContent, 'Ungültige Planung')
assert.equal(feedback.dataset.state, 'error')

console.log('AD BQ-Planer JavaScript/UI contracts passed')
