document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('adbqplanung-app')
    const feedback = document.getElementById('bq-feedback')
    if (!app) return

    app.dataset.planningCore = 'ready'
    app.addEventListener('submit', async event => {
        const form = event.target
        if (!form.matches('form[data-endpoint]')) return
        event.preventDefault()
        const payload = Object.fromEntries(new FormData(form).entries())
        for (const field of ['workdayCount', 'startWeekday', 'defaultCapacity', 'capacity', 'version', 'minutes', 'additionalCapacity']) {
            if (field in payload) payload[field] = Number(payload[field])
        }
        if ('reflectionMonthOffsets' in payload) {
            payload.reflectionMonthOffsets = String(payload.reflectionMonthOffsets)
                .split(',')
                .map(value => Number(value.trim()))
        }

        if (feedback) {
            feedback.textContent = 'Änderung wird gespeichert …'
            feedback.dataset.state = 'pending'
        }
        try {
            const response = await fetch(OC.generateUrl(`/apps/adbqplanung${form.dataset.endpoint}`), {
                method: form.dataset.method,
                headers: {
                    'Content-Type': 'application/json',
                    requesttoken: OC.requestToken,
                },
                body: JSON.stringify(payload),
            })
            const result = await response.json()
            if (!response.ok) throw new Error(result.error || 'Die Änderung konnte nicht gespeichert werden.')
            location.reload()
        } catch (error) {
            if (feedback) {
                feedback.textContent = error instanceof Error ? error.message : 'Die Änderung konnte nicht gespeichert werden.'
                feedback.dataset.state = 'error'
            }
        }
    })
})
