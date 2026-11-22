import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)))
const template = await readFile(path.join(root, 'templates/index.php'), 'utf8')
const adminTemplate = await readFile(path.join(root, 'templates/admin.php'), 'utf8')
const adminSettingsScript = await readFile(path.join(root, 'js/admin-settings.js'), 'utf8')
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
assert.match(template, /Nach oben/)
assert.match(template, /Nach unten/)
for (const roleField of ['planningRoles[]', 'teachingRoles[]', 'publishingRoles[]', 'pfkPoolRoles[]']) {
    assert.equal(adminTemplate.includes(roleField), true)
    assert.equal(template.includes(roleField), false)
}
assert.match(adminTemplate, /Keine der Rollen erhält automatisch eine andere Berechtigung/)
assert.match(adminTemplate, /flz_organization/)
assert.doesNotMatch(adminTemplate, /LocalBase-Organigramm/)
assert.match(adminSettingsScript, /permission_roles|planningRoles|pfkPoolRoles/)
assert.match(template, /data-pfk-search/)
assert.match(template, /data-pfk-options/)
assert.match(template, /Dozentinnenstatus/)
assert.match(template, /Aktiv bedeutet/)
assert.match(template, /\/api\/lecturers\/<\?php p\(\(string\)\$lecturer\['id'\]\); \?>\/status/)
assert.match(template, /id="bq-calendar-sources"/)
assert.match(template, /data-curriculum-template-form/)
assert.match(template, /name="expectedVersion"/)
assert.match(template, /name="moduleKey\[\]"/)
assert.match(template, /name="moduleTitle\[\]"/)
assert.match(template, /name="moduleMinutes\[\]"/)
assert.match(template, /Änderungen an der Vorlage gelten nur für neue Durchläufe/)
assert.match(template, /Noch nicht terminiert/)
assert.match(template, /\$capabilities\['planning'\]/)
assert.match(template, /\$capabilities\['teaching'\]/)
assert.match(template, /\$capabilities\['publishing'\]/)
assert.match(template, /\$capabilities\['pfkPool'\]/)
assert.match(template, /\$capabilities\['admin'\]/)
assert.match(template, /\$hasRunAccess/)
assert.match(template, /if \(\$hasRunAccess\)/)
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
assert.match(css, /\.app-horizontal-scroll-proxy\s*\{[^}]*position:\s*sticky[^}]*bottom:\s*0[^}]*overflow-x:\s*auto/s)
assert.match(css, /\.app-horizontal-scroll-proxy\[hidden\]/)
assert.match(script, /className = 'app-horizontal-scroll-proxy'/)
assert.match(script, /ResizeObserver/)
assert.match(script, /proxy\.addEventListener\('scroll'/)
assert.match(script, /target\.addEventListener\('scroll'/)
assert.match(script, /app\.querySelector\?\.\('\.bq-tab-panel:not\(\[hidden\]\) \.bq-table-wrap'\)/)
assert.doesNotMatch(css, /body\s*\{/)
assert.doesNotMatch(script, /window\./)

const listeners = {}
const tabListeners = new Map()
const globalListeners = new Map()
const observed = []
globalThis.addEventListener = (type, callback) => globalListeners.set(type, callback)
globalThis.removeEventListener = (type, callback) => {
    if (globalListeners.get(type) === callback) globalListeners.delete(type)
}
globalThis.ResizeObserver = class {
    constructor(callback) {
        this.callback = callback
        this.disconnected = false
        observed.push(this)
    }
    observe(target) { this.targets = [...(this.targets || []), target] }
    disconnect() { this.disconnected = true }
}
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
const tableWrap = {
    scrollWidth: 640,
    clientWidth: 320,
    scrollLeft: 12,
    listeners: new Map(),
    addEventListener(type, callback) { this.listeners.set(type, callback) },
    removeEventListener(type, callback) {
        if (this.listeners.get(type) === callback) this.listeners.delete(type)
    },
}
const pfkInput = {
    value: '',
    dataset: { pfkOptions: 'bq-pfk-candidates', pfkStatus: 'bq-pfk-search-status' },
    listeners: new Map(),
    addEventListener(type, callback) { this.listeners.set(type, callback) },
}
const pfkOptions = {
    children: [],
    replaceChildren(...children) { this.children = children },
}
const pfkStatus = { textContent: '' }
const curriculumListeners = new Map()
const createCurriculumRow = name => {
    const controls = {
        up: { disabled: false },
        down: { disabled: false },
        remove: { disabled: false },
    }
    const input = { focus() { this.focused = true } }
    const row = {
        name,
        controls,
        input,
        querySelector(selector) {
            if (selector === '[data-curriculum-move="up"]') return controls.up
            if (selector === '[data-curriculum-move="down"]') return controls.down
            if (selector === '[data-curriculum-remove]') return controls.remove
            if (selector === 'input') return input
            return null
        },
        remove() {
            const index = curriculumModuleRows.rows.indexOf(this)
            if (index >= 0) curriculumModuleRows.rows.splice(index, 1)
        },
        get previousElementSibling() {
            const index = curriculumModuleRows.rows.indexOf(this)
            return index > 0 ? curriculumModuleRows.rows[index - 1] : null
        },
        get nextElementSibling() {
            const index = curriculumModuleRows.rows.indexOf(this)
            return index >= 0 && index < curriculumModuleRows.rows.length - 1 ? curriculumModuleRows.rows[index + 1] : null
        },
    }
    return row
}
const curriculumModuleRows = {
    rows: [],
    querySelectorAll(selector) { return selector === '[data-curriculum-module-row]' ? this.rows : [] },
    append(row) { this.rows.push(row) },
    insertBefore(row, reference) {
        const oldIndex = this.rows.indexOf(row)
        if (oldIndex >= 0) this.rows.splice(oldIndex, 1)
        this.rows.splice(this.rows.indexOf(reference), 0, row)
    },
}
curriculumModuleRows.rows.push(createCurriculumRow('one'), createCurriculumRow('two'))
const addedCurriculumRow = createCurriculumRow('added')
const curriculumModuleTemplate = { content: { firstElementChild: { cloneNode: () => addedCurriculumRow } } }
const addCurriculumModule = { addEventListener(type, callback) { curriculumListeners.set(type, callback) } }
const app = {
    dataset: {},
    children: [],
    append(child) { this.children.push(child) },
    addEventListener(type, callback) {
        listeners[type] = callback
    },
    querySelectorAll(selector) {
        if (selector === '[role="tab"][data-tab-target]') return tabs
        if (selector === '[role="tabpanel"]') return panels
        if (selector === '[data-pfk-search]') return [pfkInput]
        return []
    },
    querySelector(selector) {
        if (selector === '[data-add-curriculum-module]') return addCurriculumModule
        return selector === '.bq-tab-panel:not([hidden]) .bq-table-wrap' ? tableWrap : null
    },
}
const feedback = { textContent: '', dataset: {} }
const proposalResult = { textContent: '' }
const yearProposalStatus = { textContent: '' }
const yearProposalRows = {
    children: [],
    replaceChildren(...children) { this.children = children },
}
const calendarSources = {
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
        if (id === 'flzbqplanung-app') return app
        if (id === 'bq-feedback') return feedback
        if (id === 'bq-proposal-result') return proposalResult
        if (id === 'bq-year-proposal-rows') return yearProposalRows
        if (id === 'bq-year-proposal-status') return yearProposalStatus
        if (id === 'bq-calendar-sources') return calendarSources
        if (id === 'bq-curriculum-template-modules') return curriculumModuleRows
        if (id === 'bq-curriculum-module-template') return curriculumModuleTemplate
        if (id === 'bq-pfk-candidates') return pfkOptions
        if (id === 'bq-pfk-search-status') return pfkStatus
        return null
    },
    createElement(tagName) {
        return {
            tagName,
            children: [],
            textContent: '',
            style: {},
            dataset: {},
            listeners: new Map(),
            attributes: new Map(),
            append(...children) { this.children.push(...children) },
            addEventListener(type, callback) { this.listeners.set(type, callback) },
            removeEventListener(type, callback) {
                if (this.listeners.get(type) === callback) this.listeners.delete(type)
            },
            setAttribute(name, value) { this.attributes.set(name, value) },
            remove() { this.removed = true },
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
    getAll(name) {
        const value = this.form.fields[name]
        if (value === undefined) return []
        return Array.isArray(value) ? value : [value]
    }
}
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return { ok: true, json: async () => ({ data: { id: 1 } }) }
}
await import('../js/main.js')
assert.equal(app.dataset.planningCore, 'ready')
assert.equal(curriculumModuleRows.rows[0].controls.up.disabled, true)
assert.equal(curriculumModuleRows.rows[1].controls.down.disabled, true)
await curriculumListeners.get('click')()
assert.equal(curriculumModuleRows.rows.length, 3)
assert.equal(addedCurriculumRow.input.focused, true)
const curriculumButton = (row, kind) => ({
    dataset: { curriculumMove: kind === 'remove' ? undefined : kind },
    closest(selector) { return selector.includes('curriculum-move') ? this : row },
    matches(selector) { return kind === 'remove' && selector === '[data-curriculum-remove]' },
})
listeners.click({ target: curriculumButton(curriculumModuleRows.rows[0], 'down') })
assert.equal(curriculumModuleRows.rows[1].name, 'one')
listeners.click({ target: curriculumButton(curriculumModuleRows.rows[1], 'up') })
assert.equal(curriculumModuleRows.rows[0].name, 'one')
listeners.click({ target: curriculumButton(addedCurriculumRow, 'remove') })
assert.equal(curriculumModuleRows.rows.includes(addedCurriculumRow), false)
const firstProxy = app.children[0]
assert.equal(firstProxy.hidden, false)
assert.equal(firstProxy.children[0].style.width, '640px')
firstProxy.scrollLeft = 48
firstProxy.listeners.get('scroll')()
assert.equal(tableWrap.scrollLeft, 48)
tableWrap.scrollLeft = 96
tableWrap.listeners.get('scroll')()
assert.equal(firstProxy.scrollLeft, 96)
globalListeners.get('resize')()

tabListeners.get('bq-tab-lecturers:click')({ currentTarget: tabs[1] })
assert.equal(firstProxy.removed, true)
assert.equal(observed[0].disconnected, true)
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
assert.equal(requests[0].url, '/nextcloud/apps/flzbqplanung/api/runs')
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
proposalForm.fields.proposalMonth = 'invalid'
await listeners.submit({ target: proposalForm, preventDefault() {} })
assert.equal(proposalResult.textContent, 'Bitte einen gültigen Planungsmonat auswählen.')
proposalForm.fields.proposalMonth = '2026-09'
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return {
        ok: true,
        json: async () => ({
            data: {
                proposal: { startsOn: '2026-09-04', endsOn: '2026-09-14' },
                calendar: {
                    complete: true,
                    message: 'Kalender geprüft.',
                    sources: [
                        { label: 'Schulferien', status: 'current', complete: true },
                        { label: 'Gesetzliche Feiertage', status: 'current', complete: true },
                        { label: 'BQ-eigene Sperrtage', status: 'current', complete: true },
                    ],
                },
            },
        }),
    }
}
await listeners.submit({ target: proposalForm, preventDefault() {} })
assert.equal(requests[1].url, '/nextcloud/apps/flzbqplanung/api/proposals?year=2026&month=9')
assert.match(proposalResult.textContent, /Kalender vollständig geprüft/)
assert.match(proposalResult.textContent, /2026-09-04 bis 2026-09-14/)
assert.equal(calendarSources.children.length, 3)
assert.equal(calendarSources.children[0].textContent, 'Schulferien: vollständig')
assert.equal(calendarSources.children[2].textContent, 'BQ-eigene Sperrtage: vollständig')

globalThis.fetch = async () => ({
    ok: true,
    json: async () => ({ data: { proposal: null, calendar: {
        complete: false,
        message: 'Kalenderquelle nicht vollständig.',
        sources: [
            { label: 'Schulferien', status: 'stale', complete: false },
            { label: 'Gesetzliche Feiertage', status: 'incompatible', complete: false },
            { label: 'BQ-eigene Sperrtage', status: 'unavailable', complete: false },
        ],
    } } }),
})
await listeners.submit({ target: proposalForm, preventDefault() {} })
assert.equal(proposalResult.textContent, 'Kalenderquelle nicht vollständig.')
assert.equal(calendarSources.children[0].textContent, 'Schulferien: veraltet, nicht vollständig')
assert.equal(calendarSources.children[1].textContent, 'Gesetzliche Feiertage: inkompatibel, nicht verwendbar')
assert.equal(calendarSources.children[2].textContent, 'BQ-eigene Sperrtage: nicht verfügbar')
globalThis.fetch = async () => { throw new Error('Kalender offline') }
await listeners.submit({ target: proposalForm, preventDefault() {} })
assert.equal(proposalResult.textContent, 'Kalender offline')

const yearForm = {
    fields: { proposalYear: '2026' },
    matches: selector => selector === 'form[data-year-proposal-form]',
}
yearForm.fields.proposalYear = '1999'
await listeners.submit({ target: yearForm, preventDefault() {} })
assert.equal(yearProposalStatus.textContent, 'Bitte ein gültiges Planungsjahr zwischen 2000 und 2200 eingeben.')
yearForm.fields.proposalYear = '2026'
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
assert.equal(requests[2].url, '/nextcloud/apps/flzbqplanung/api/proposals/year?year=2026')
assert.equal(yearProposalRows.children.length, 2)
assert.equal(yearProposalRows.children[0].children[0].textContent, 'Januar')
assert.equal(yearProposalRows.children[0].children[1].textContent, '2026-01-02 bis 2026-01-12')
assert.match(yearProposalRows.children[0].children[2].textContent, /keine verworfenen Starttermine/i)
assert.equal(yearProposalRows.children[1].children[1].textContent, 'Kein automatischer Vorschlag')
assert.equal(yearProposalRows.children[1].children[2].textContent, 'Kein konfliktfreier Termin.')
globalThis.fetch = async () => { throw new Error('Jahresvorschau offline') }
await listeners.submit({ target: yearForm, preventDefault() {} })
assert.equal(yearProposalStatus.textContent, 'Jahresvorschau offline')

globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: 'Ungültige Planung' }) })
await listeners.submit({ target: form, preventDefault() {} })
assert.equal(feedback.textContent, 'Ungültige Planung')
assert.equal(feedback.dataset.state, 'error')

const moveForm = {
    dataset: { endpoint: '/api/runs/1/modules/reorder', method: 'POST' },
    fields: { version: '4', moduleId: '3', direction: 'up' },
    matches: selector => selector === 'form[data-endpoint]',
}
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return { ok: true, json: async () => ({ data: [] }) }
}
await listeners.submit({ target: moveForm, preventDefault() {} })
const moveRequest = requests.at(-1)
assert.equal(moveRequest.url, '/nextcloud/apps/flzbqplanung/api/runs/1/modules/reorder')
assert.deepEqual(JSON.parse(moveRequest.options.body), { version: 4, moduleId: 3, direction: 'up' })

const bridgeDaysForm = {
    dataset: { endpoint: '/api/settings/planning', method: 'POST' },
    fields: { bridgeDays: '2026-12-24, ,2026-12-31', workdayCount: '7' },
    matches: selector => selector === 'form[data-endpoint]',
}
await listeners.submit({ target: bridgeDaysForm, preventDefault() {} })
assert.deepEqual(JSON.parse(requests.at(-1).options.body), {
    bridgeDays: ['2026-12-24', '2026-12-31'],
    workdayCount: 7,
})

pfkInput.value = ' beta '
globalThis.fetch = async url => {
    requests.push({ url })
    return {
        ok: true,
        json: async () => ({ data: [
            { uid: 'pfk-a', displayName: 'PFK Alpha' },
            { uid: 'pfk-b', displayName: 'pfk-b' },
        ] }),
    }
}
await pfkInput.listeners.get('input')()
assert.equal(requests.at(-1).url, '/nextcloud/apps/flzbqplanung/api/internal-pfk-members?search=beta')
assert.equal(pfkOptions.children.length, 2)
assert.equal(pfkOptions.children[0].value, 'pfk-a')
assert.equal(pfkOptions.children[0].label, 'PFK Alpha (pfk-a)')
assert.equal(pfkOptions.children[1].label, 'pfk-b')
assert.match(pfkStatus.textContent, /2 passende/)

globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: [] }) })
await pfkInput.listeners.get('focus')()
assert.equal(pfkOptions.children.length, 0)
assert.match(pfkStatus.textContent, /Keine passenden/)

globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: 'PFK-Gruppe fehlt' }) })
await pfkInput.listeners.get('input')()
assert.equal(pfkOptions.children.length, 0)
assert.equal(pfkStatus.textContent, 'PFK-Gruppe fehlt')

const curriculumForm = {
    dataset: { endpoint: '/api/curriculum-template', method: 'PUT' },
    fields: {
        title: 'Basisqualifizierung',
        expectedVersion: '2',
        'moduleKey[]': ['grundlagen', 'recht'],
        'moduleTitle[]': ['Grundlagen', 'Recht'],
        'moduleMinutes[]': ['120', '90'],
    },
    matches: selector => selector === 'form[data-endpoint]' || selector === 'form[data-curriculum-template-form]',
}
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return { ok: true, json: async () => ({ data: { version: 3 } }) }
}
await listeners.submit({ target: curriculumForm, preventDefault() {} })
assert.equal(requests.at(-1).url, '/nextcloud/apps/flzbqplanung/api/curriculum-template')
assert.deepEqual(JSON.parse(requests.at(-1).options.body), {
    title: 'Basisqualifizierung',
    expectedVersion: 2,
    modules: [
        { moduleKey: 'grundlagen', title: 'Grundlagen', minutes: 120 },
        { moduleKey: 'recht', title: 'Recht', minutes: 90 },
    ],
})

await import('./admin-access-smoke.mjs')
await import('./admin-settings-smoke.mjs')

console.log('Filzmann BQ-Planer JavaScript/UI contracts passed')
