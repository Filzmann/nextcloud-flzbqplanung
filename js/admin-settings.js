const translate = typeof t === 'function' ? t : (_app, text, params) => Object.entries(params || {}).reduce((s, [k, v]) => s.split('{' + k + '}').join(String(v)), text)
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('flz-bq-permission-settings')
    const status = document.getElementById('flz-bq-permission-settings-status')
    if (!form) return

    form.addEventListener('submit', async event => {
        event.preventDefault()
        const data = new FormData(form)
        const payload = {}
        for (const field of ['planningRoles', 'teachingRoles', 'publishingRoles', 'pfkPoolRoles']) {
            payload[field] = data.getAll(`${field}[]`).map(value => String(value))
        }
        if (status) status.textContent = translate('flzbqplanung', 'Berechtigungen werden gespeichert …')
        try {
            const response = await fetch(OC.generateUrl('/apps/flzbqplanung/api/role-settings'), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    requesttoken: OC.requestToken,
                },
                body: JSON.stringify(payload),
            })
            const result = await response.json()
            if (!response.ok) throw new Error(result.error || translate('flzbqplanung', 'Die BQ-Berechtigungen konnten nicht gespeichert werden.'))
            if (status) status.textContent = translate('flzbqplanung', 'Die BQ-Berechtigungen wurden gespeichert.')
        } catch (error) {
            if (status) status.textContent = error instanceof Error ? error.message : translate('flzbqplanung', 'Die BQ-Berechtigungen konnten nicht gespeichert werden.')
        }
    })
})
