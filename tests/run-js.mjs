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
assert.match(template, /data-proposal-form/)
assert.match(template, /data-year-proposal-form/)
assert.match(template, /id="bq-year-proposal-rows"/)
assert.match(template, /Konflikte\/Status/)
assert.match(template, /Modul bearbeiten/)
assert.match(template, /Durchlauf bearbeiten/)
assert.match(template, /Terminüberschneidung/)
assert.match(template, /\/api\/runs\/<\?php p\(\(string\)\$run\['id'\]\); \?>\/modules\/<\?php p\(\(string\)\$module\['id'\]\); \?>/)
assert.match(template, /name="bridgeDays"/)
assert.match(template, /role="tablist"/)
for (const tab of ['runs', 'lecturers', 'settings']) {
    assert.match(template, new RegExp(`id="bq-tab-${tab}"[^>]+role="tab"[^>]+aria-controls="bq-panel-${tab}"`))
    assert.match(template, new RegExp(`id="bq-panel-${tab}"[^>]+role="tabpanel"[^>]+aria-labelledby="bq-tab-${tab}"`))
}
assert.match(css, /overflow-y:\s*auto/)
assert.match(css, /min-height:\s*0/)
assert.match(css, /\.bq-tab\[aria-selected="true"\]/)
assert.match(css, /\.bq-tab:focus-visible/)
assert.doesNotMatch(css, /body\s*\{/)
assert.doesNotMatch(script, /window\./)

const listeners = {}
const tabListeners = new Map()
const panels = ['runs', 'lecturers', 'settings'].map((name, index) => ({
    id: `bq-panel-${name}`,
    hidden: index !== 0,
}))
const tabs = ['runs', 'lecturers', 'settings'].map((name, index) => ({
    id: `bq-tab-${name}`,
    dataset: { tabTarget: `bq-panel-${name}` },
    attributes: {
        'aria-selected': index === 0 ? 'true' : 'false',
        tabindex: index === 0 ? '0' : '-1',
    },
    addEventListener(type, callback) {
        tabListeners.set(`${this.id}:${type}`, callback)
    },
    setAttribute(name, value) {
        this.attributes[name] = value
    },
    focus() {
        this.focused = true
    },
}))
const app = {
    dataset: {},
    addEventListener(type, callback) {
        listeners[type] = callback
    },
    querySelectorAll(selector) {
        if (selector === '[role="tab"][data-tab-target]') return tabs
        if (selector === '[role="tabpanel"]') return panels
        return []
    },
}
const feedback = { textContent: '', dataset: {} }
const proposalResult = { textContent: '' }
const yearProposalRows = {
    children: [],
    replaceChildren(...children) { this.children = children },
}
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
        if (id === 'bq-proposal-result') return proposalResult
        if (id === 'bq-year-proposal-rows') return yearProposalRows
        return null
    },
    createElement(tagName) {
        return {
            tagName,
            children: [],
            textContent: '',
            append(...children) { this.children.push(...children) },
        }
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
    get(name) {
        return this.form.fields[name]
    }
}
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return { ok: true, json: async () => ({ data: { id: 1 } }) }
}
await import('../js/main.js')
assert.equal(app.dataset.planningCore, 'ready')

tabListeners.get('bq-tab-lecturers:click')({ currentTarget: tabs[1] })
assert.equal(tabs[0].attributes['aria-selected'], 'false')
assert.equal(tabs[1].attributes['aria-selected'], 'true')
assert.equal(tabs[1].attributes.tabindex, '0')
assert.equal(panels[0].hidden, true)
assert.equal(panels[1].hidden, false)

tabListeners.get('bq-tab-lecturers:keydown')({
    key: 'ArrowRight',
    preventDefault() {},
})
assert.equal(tabs[2].attributes['aria-selected'], 'true')
assert.equal(panels[2].hidden, false)
assert.equal(tabs[2].focused, true)

tabListeners.get('bq-tab-settings:keydown')({
    key: 'Home',
    preventDefault() {},
})
assert.equal(tabs[0].attributes['aria-selected'], 'true')
assert.equal(panels[0].hidden, false)
assert.equal(tabs[0].focused, true)

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

const proposalForm = {
    fields: { proposalMonth: '2026-09' },
    matches: selector => selector === 'form[data-proposal-form]',
}
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return {
        ok: true,
        json: async () => ({
            data: {
                proposal: { startsOn: '2026-09-04', endsOn: '2026-09-14' },
                calendar: { complete: true, message: 'Kalender geprüft.' },
            },
        }),
    }
}
await listeners.submit({ target: proposalForm, preventDefault() {} })
assert.equal(requests[1].url, '/nextcloud/apps/adbqplanung/api/proposals?year=2026&month=9')
assert.match(proposalResult.textContent, /Kalender vollständig geprüft/)
assert.match(proposalResult.textContent, /2026-09-04 bis 2026-09-14/)

globalThis.fetch = async () => ({
    ok: true,
    json: async () => ({ data: { proposal: null, calendar: { complete: false, message: 'Kalenderquelle nicht vollständig.' } } }),
})
await listeners.submit({ target: proposalForm, preventDefault() {} })
assert.equal(proposalResult.textContent, 'Kalenderquelle nicht vollständig.')

const yearForm = {
    fields: { proposalYear: '2026' },
    matches: selector => selector === 'form[data-year-proposal-form]',
}
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return {
        ok: true,
        json: async () => ({ data: [
            { month: 1, proposal: { startsOn: '2026-01-02', endsOn: '2026-01-12', rejectedCandidates: [] }, calendar: { complete: true, message: 'Kalender geprüft.' } },
            { month: 2, proposal: null, calendar: { complete: true, message: 'Kalender geprüft.' }, error: 'Kein konfliktfreier Termin.' },
        ] }),
    }
}
await listeners.submit({ target: yearForm, preventDefault() {} })
assert.equal(requests[2].url, '/nextcloud/apps/adbqplanung/api/proposals/year?year=2026')
assert.equal(yearProposalRows.children.length, 2)
assert.equal(yearProposalRows.children[0].children[0].textContent, 'Januar')
assert.equal(yearProposalRows.children[0].children[1].textContent, '2026-01-02 bis 2026-01-12')
assert.match(yearProposalRows.children[0].children[2].textContent, /keine verworfenen Starttermine/i)
assert.equal(yearProposalRows.children[1].children[1].textContent, 'Kein automatischer Vorschlag')
assert.equal(yearProposalRows.children[1].children[2].textContent, 'Kein konfliktfreier Termin.')

globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: 'Ungültige Planung' }) })
await listeners.submit({ target: form, preventDefault() {} })
assert.equal(feedback.textContent, 'Ungültige Planung')
assert.equal(feedback.dataset.state, 'error')

console.log('AD BQ-Planer JavaScript/UI contracts passed')
