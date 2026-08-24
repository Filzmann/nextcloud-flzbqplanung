document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('adbqplanung-app')
    const feedback = document.getElementById('bq-feedback')
    const proposalResult = document.getElementById('bq-proposal-result')
    const yearProposalRows = document.getElementById('bq-year-proposal-rows')
    const yearProposalStatus = document.getElementById('bq-year-proposal-status')
    if (!app) return

    app.dataset.planningCore = 'ready'
    const tabs = Array.from(app.querySelectorAll('[role="tab"][data-tab-target]'))
    const tabPanels = Array.from(app.querySelectorAll('[role="tabpanel"]'))
    const activateTab = (tab, moveFocus = false) => {
        tabs.forEach(candidate => {
            const active = candidate === tab
            candidate.setAttribute('aria-selected', String(active))
            candidate.setAttribute('tabindex', active ? '0' : '-1')
        })
        tabPanels.forEach(panel => {
            panel.hidden = panel.id !== tab.dataset.tabTarget
        })
        if (moveFocus) tab.focus()
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab))
        tab.addEventListener('keydown', event => {
            let targetIndex = null
            if (event.key === 'ArrowRight') targetIndex = (index + 1) % tabs.length
            if (event.key === 'ArrowLeft') targetIndex = (index - 1 + tabs.length) % tabs.length
            if (event.key === 'Home') targetIndex = 0
            if (event.key === 'End') targetIndex = tabs.length - 1
            if (targetIndex === null) return
            event.preventDefault()
            activateTab(tabs[targetIndex], true)
        })
    })

    app.addEventListener('submit', async event => {
        const form = event.target
        if (form.matches('form[data-proposal-form]')) {
            event.preventDefault()
            const value = String(new FormData(form).get('proposalMonth') || '')
            const match = /^(\d{4})-(\d{2})$/.exec(value)
            if (!match) {
                if (proposalResult) proposalResult.textContent = 'Bitte einen gültigen Planungsmonat auswählen.'
                return
            }
            if (proposalResult) proposalResult.textContent = 'Ferien, Feiertage und Brückentage werden geprüft …'
            try {
                const url = OC.generateUrl('/apps/adbqplanung/api/proposals')
                const response = await fetch(`${url}?year=${Number(match[1])}&month=${Number(match[2])}`)
                const result = await response.json()
                if (!response.ok) throw new Error(result.error || 'Der Terminvorschlag konnte nicht erstellt werden.')
                const proposal = result.data?.proposal
                const calendar = result.data?.calendar
                if (!proposal) {
                    if (proposalResult) proposalResult.textContent = calendar?.message || 'Die Kalenderquelle ist nicht vollständig verfügbar.'
                    return
                }
                const prefix = calendar?.complete ? 'Kalender vollständig geprüft.' : 'Kalenderstand eingeschränkt.'
                if (proposalResult) proposalResult.textContent = `${prefix} Vorschlag: ${proposal.startsOn} bis ${proposal.endsOn}. ${calendar?.message || ''}`
            } catch (error) {
                if (proposalResult) proposalResult.textContent = error instanceof Error ? error.message : 'Der Terminvorschlag konnte nicht erstellt werden.'
            }
            return
        }
        if (form.matches('form[data-year-proposal-form]')) {
            event.preventDefault()
            const year = Number(new FormData(form).get('proposalYear'))
            if (!Number.isInteger(year) || year < 2000 || year > 2200) {
                if (yearProposalStatus) yearProposalStatus.textContent = 'Bitte ein gültiges Planungsjahr zwischen 2000 und 2200 eingeben.'
                return
            }
            if (yearProposalStatus) yearProposalStatus.textContent = 'Alle zwölf Monate werden geprüft …'
            try {
                const url = OC.generateUrl('/apps/adbqplanung/api/proposals/year')
                const response = await fetch(`${url}?year=${year}`)
                const result = await response.json()
                if (!response.ok) throw new Error(result.error || 'Die Jahresvorschau konnte nicht erstellt werden.')
                const monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember']
                const rows = result.data.map(month => {
                    const row = document.createElement('tr')
                    const monthCell = document.createElement('th')
                    monthCell.scope = 'row'
                    monthCell.textContent = monthNames[month.month - 1] || `Monat ${month.month}`
                    const proposalCell = document.createElement('td')
                    proposalCell.textContent = month.proposal
                        ? `${month.proposal.startsOn} bis ${month.proposal.endsOn}`
                        : 'Kein automatischer Vorschlag'
                    const statusCell = document.createElement('td')
                    if (!month.proposal) {
                        statusCell.textContent = month.error || month.calendar?.message || 'Kalenderquelle nicht vollständig.'
                    } else {
                        const rejected = month.proposal.rejectedCandidates || []
                        const labels = [...new Set(rejected.flatMap(candidate => candidate.conflicts || []).map(conflict => conflict.label))]
                        const conflictText = rejected.length === 0
                            ? 'Keine verworfenen Starttermine.'
                            : `${rejected.length} verworfene Starttermine${labels.length > 0 ? `: ${labels.join(', ')}` : '.'}`
                        const coverage = month.calendar?.complete ? 'Kalender vollständig.' : 'Kalenderstand eingeschränkt.'
                        statusCell.textContent = `${coverage} ${conflictText}`
                    }
                    row.append(monthCell, proposalCell, statusCell)
                    return row
                })
                if (yearProposalRows) yearProposalRows.replaceChildren(...rows)
                if (yearProposalStatus) yearProposalStatus.textContent = `Jahresvorschau ${year} mit ${rows.length} Monaten erstellt.`
            } catch (error) {
                if (yearProposalStatus) yearProposalStatus.textContent = error instanceof Error ? error.message : 'Die Jahresvorschau konnte nicht erstellt werden.'
            }
            return
        }
        if (!form.matches('form[data-endpoint]')) return
        event.preventDefault()
        const payload = Object.fromEntries(new FormData(form).entries())
        for (const field of ['workdayCount', 'startWeekday', 'defaultCapacity', 'capacity', 'version', 'minutes', 'additionalCapacity', 'lecturerId', 'runVersion', 'moduleVersion']) {
            if (field in payload) payload[field] = Number(payload[field])
        }
        if ('reflectionMonthOffsets' in payload) {
            payload.reflectionMonthOffsets = String(payload.reflectionMonthOffsets)
                .split(',')
                .map(value => Number(value.trim()))
        }
        if ('bridgeDays' in payload) {
            payload.bridgeDays = String(payload.bridgeDays)
                .split(',')
                .map(value => value.trim())
                .filter(Boolean)
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
