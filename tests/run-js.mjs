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
assert.match(css, /overflow-y:\s*auto/)
assert.match(css, /min-height:\s*0/)
assert.doesNotMatch(css, /body\s*\{/)
assert.doesNotMatch(script, /window\./)

const app = { dataset: {} }
globalThis.document = {
    addEventListener(type, callback) {
        assert.equal(type, 'DOMContentLoaded')
        callback()
    },
    getElementById(id) {
        assert.equal(id, 'adbqplanung-app')
        return app
    },
}
await import('../js/main.js')
assert.equal(app.dataset.planningCore, 'ready')

console.log('AD BQ-Planer JavaScript/UI contracts passed')
