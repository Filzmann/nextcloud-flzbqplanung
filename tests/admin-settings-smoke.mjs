import assert from 'node:assert/strict'

const listeners = new Map()
const status = { textContent: '' }
const form = {
    fields: {
        'planningRoles[]': ['deputy_pdl', 'pdl'],
        'teachingRoles[]': ['deputy_pdl'],
        'publishingRoles[]': ['deputy_pdl'],
        'pfkPoolRoles[]': ['deputy_pdl', 'pfk'],
    },
    addEventListener(type, callback) { listeners.set(type, callback) },
}

globalThis.document = {
    addEventListener(type, callback) {
        assert.equal(type, 'DOMContentLoaded')
        callback()
    },
    getElementById(id) {
        if (id === 'flz-bq-permission-settings') return form
        if (id === 'flz-bq-permission-settings-status') return status
        return null
    },
}
globalThis.FormData = class {
    constructor(target) { this.target = target }
    getAll(name) { return this.target.fields[name] ?? [] }
}
globalThis.OC = {
    requestToken: 'admin-csrf-token',
    generateUrl: value => `/nextcloud${value}`,
}

const requests = []
globalThis.fetch = async (url, options) => {
    requests.push({ url, options })
    return { ok: true, json: async () => ({ data: {} }) }
}

await import('../js/admin-settings.js?admin-settings-smoke')
await listeners.get('submit')({ preventDefault() {} })

assert.equal(requests[0].url, '/nextcloud/apps/flzbqplanung/api/role-settings')
assert.equal(requests[0].options.method, 'PUT')
assert.equal(requests[0].options.headers.requesttoken, 'admin-csrf-token')
assert.deepEqual(JSON.parse(requests[0].options.body), {
    planningRoles: ['deputy_pdl', 'pdl'],
    teachingRoles: ['deputy_pdl'],
    publishingRoles: ['deputy_pdl'],
    pfkPoolRoles: ['deputy_pdl', 'pfk'],
})
assert.equal(status.textContent, 'Die Berechtigungszuordnung wurde gespeichert.')

globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: 'Ungültige Rollenwahl' }) })
await listeners.get('submit')({ preventDefault() {} })
assert.equal(status.textContent, 'Ungültige Rollenwahl')
