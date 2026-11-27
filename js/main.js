const translate = typeof t === 'function' ? t : (_app, text, params) => Object.entries(params || {}).reduce((s, [k, v]) => s.split('{' + k + '}').join(String(v)), text)
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('flzbqplanung-app')
    const feedback = document.getElementById('bq-feedback')
    const proposalResult = document.getElementById('bq-proposal-result')
    const yearProposalRows = document.getElementById('bq-year-proposal-rows')
    const yearProposalStatus = document.getElementById('bq-year-proposal-status')
    const calendarSources = document.getElementById('bq-calendar-sources')
    const curriculumModuleRows = document.getElementById('bq-curriculum-template-modules')
    const curriculumModuleTemplate = document.getElementById('bq-curriculum-module-template')
    const addCurriculumModule = app?.querySelector?.('[data-add-curriculum-module]')
    if (!app) return

    const refreshCurriculumControls = () => {
        const rows = Array.from(curriculumModuleRows?.querySelectorAll?.('[data-curriculum-module-row]') || [])
        rows.forEach((row, index) => {
            const up = row.querySelector?.('[data-curriculum-move="up"]')
            const down = row.querySelector?.('[data-curriculum-move="down"]')
            const remove = row.querySelector?.('[data-curriculum-remove]')
            if (up) up.disabled = index === 0
            if (down) down.disabled = index === rows.length - 1
            if (remove) remove.disabled = rows.length === 1
        })
    }
    addCurriculumModule?.addEventListener('click', () => {
        const row = curriculumModuleTemplate?.content?.firstElementChild?.cloneNode(true)
        if (!row || !curriculumModuleRows) return
        curriculumModuleRows.append(row)
        refreshCurriculumControls()
        row.querySelector?.('input')?.focus()
    })
    app.addEventListener('click', event => {
        const button = event.target?.closest?.('[data-curriculum-move], [data-curriculum-remove]')
        if (!button) return
        const row = button.closest('[data-curriculum-module-row]')
        if (!row || !curriculumModuleRows) return
        if (button.matches('[data-curriculum-remove]')) row.remove()
        if (button.dataset.curriculumMove === 'up' && row.previousElementSibling) {
            curriculumModuleRows.insertBefore(row, row.previousElementSibling)
        }
        if (button.dataset.curriculumMove === 'down' && row.nextElementSibling) {
            curriculumModuleRows.insertBefore(row.nextElementSibling, row)
        }
        refreshCurriculumControls()
    })
    refreshCurriculumControls()

    let horizontalScrollCleanup = () => {}
    const bindPersistentHorizontalScroll = () => {
        horizontalScrollCleanup()
        const target = app.querySelector?.('.bq-tab-panel:not([hidden]) .bq-table-wrap')
        if (!target) return
        const proxy = document.createElement('div')
        proxy.className = 'app-horizontal-scroll-proxy'
        proxy.tabIndex = 0
        proxy.setAttribute('role', 'region')
        proxy.setAttribute('aria-label', 'Horizontal durch die sichtbare BQ-Ansicht scrollen')
        const track = document.createElement('div')
        track.className = 'app-horizontal-scroll-proxy__track'
        track.setAttribute('aria-hidden', 'true')
        proxy.append(track)
        app.append(proxy)
        const update = () => {
            const visible = target.scrollWidth > target.clientWidth
            proxy.hidden = !visible
            track.style.width = `${target.scrollWidth}px`
            if (visible) proxy.scrollLeft = target.scrollLeft
        }
        const fromProxy = () => { target.scrollLeft = proxy.scrollLeft }
        const fromTarget = () => { proxy.scrollLeft = target.scrollLeft }
        proxy.addEventListener('scroll', fromProxy)
        target.addEventListener('scroll', fromTarget)
        const observer = typeof ResizeObserver === 'function' ? new ResizeObserver(update) : null
        observer?.observe(app)
        observer?.observe(target)
        globalThis.addEventListener('resize', update)
        update()
        horizontalScrollCleanup = () => {
            observer?.disconnect()
            globalThis.removeEventListener('resize', update)
            proxy.removeEventListener('scroll', fromProxy)
            target.removeEventListener('scroll', fromTarget)
            proxy.remove()
            horizontalScrollCleanup = () => {}
        }
    }

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
        bindPersistentHorizontalScroll()
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
    bindPersistentHorizontalScroll()

    const sourceStatusLabel = source => {
        if (source.complete) return 'vollständig'
        if (source.status === 'stale') return 'veraltet, nicht vollständig'
        if (source.status === 'incompatible') return 'inkompatibel, nicht verwendbar'
        return 'nicht verfügbar'
    }
    const renderCalendarSources = sources => {
        if (!calendarSources) return
        const items = Array.isArray(sources) ? sources.map(source => {
            const item = document.createElement('li')
            item.textContent = `${source.label}: ${sourceStatusLabel(source)}`
            item.dataset.complete = String(Boolean(source.complete))
            return item
        }) : []
        calendarSources.replaceChildren(...items)
    }

    for (const input of app.querySelectorAll('[data-pfk-search]')) {
        const options = document.getElementById(input.dataset.pfkOptions)
        const status = document.getElementById(input.dataset.pfkStatus)
        const search = async () => {
            const query = String(input.value || '').trim()
            if (status) status.textContent = translate('flzbqplanung', 'Mitglieder der Organigrammrolle Pflegefachkräfte werden gesucht …')
            try {
                const url = OC.generateUrl('/apps/flzbqplanung/api/internal-pfk-members')
                const response = await fetch(`${url}?search=${encodeURIComponent(query)}`)
                const result = await response.json()
                if (!response.ok) throw new Error(result.error || translate('flzbqplanung', 'Die PFK-Suche ist nicht verfügbar.'))
                const candidates = Array.isArray(result.data) ? result.data : []
                const entries = candidates.map(candidate => {
                    const option = document.createElement('option')
                    option.value = candidate.uid
                    option.label = candidate.displayName === candidate.uid
                        ? candidate.uid
                        : `${candidate.displayName} (${candidate.uid})`
                    return option
                })
                options?.replaceChildren(...entries)
                if (status) status.textContent = candidates.length === 0
                    ? translate('flzbqplanung', 'Keine passenden Mitglieder der Organigrammrolle Pflegefachkräfte gefunden.')
                    : `${candidates.length} passende PFK-Auswahlmöglichkeiten gefunden.`
            } catch (error) {
                options?.replaceChildren()
                if (status) status.textContent = error instanceof Error ? error.message : translate('flzbqplanung', 'Die PFK-Suche ist nicht verfügbar.')
            }
        }
        input.addEventListener('input', search)
        input.addEventListener('focus', search)
    }

    app.addEventListener('submit', async event => {
        const form = event.target
        if (form.matches('form[data-proposal-form]')) {
            event.preventDefault()
            const value = String(new FormData(form).get('proposalMonth') || '')
            const match = /^(\d{4})-(\d{2})$/.exec(value)
            if (!match) {
                if (proposalResult) proposalResult.textContent = translate('flzbqplanung', 'Bitte einen gültigen Planungsmonat auswählen.')
                return
            }
            if (proposalResult) proposalResult.textContent = translate('flzbqplanung', 'Ferien, Feiertage und Brückentage werden geprüft …')
            renderCalendarSources([])
            try {
                const url = OC.generateUrl('/apps/flzbqplanung/api/proposals')
                const response = await fetch(`${url}?year=${Number(match[1])}&month=${Number(match[2])}`)
                const result = await response.json()
                if (!response.ok) throw new Error(result.error || translate('flzbqplanung', 'Der Terminvorschlag konnte nicht erstellt werden.'))
                const proposal = result.data?.proposal
                const calendar = result.data?.calendar
                renderCalendarSources(calendar?.sources)
                if (!proposal) {
                    if (proposalResult) proposalResult.textContent = calendar?.message || translate('flzbqplanung', 'Die Kalenderquelle ist nicht vollständig verfügbar.')
                    return
                }
                const prefix = calendar?.complete ? translate('flzbqplanung', 'Kalender vollständig geprüft.') : translate('flzbqplanung', 'Kalenderstand eingeschränkt.')
                if (proposalResult) proposalResult.textContent = `${prefix} Vorschlag: ${proposal.startsOn} bis ${proposal.endsOn}. ${calendar?.message || ''}`
            } catch (error) {
                if (proposalResult) proposalResult.textContent = error instanceof Error ? error.message : translate('flzbqplanung', 'Der Terminvorschlag konnte nicht erstellt werden.')
            }
            return
        }
        if (form.matches('form[data-year-proposal-form]')) {
            event.preventDefault()
            const year = Number(new FormData(form).get('proposalYear'))
            if (!Number.isInteger(year) || year < 2000 || year > 2200) {
                if (yearProposalStatus) yearProposalStatus.textContent = translate('flzbqplanung', 'Bitte ein gültiges Planungsjahr zwischen 2000 und 2200 eingeben.')
                return
            }
            if (yearProposalStatus) yearProposalStatus.textContent = translate('flzbqplanung', 'Alle zwölf Monate werden geprüft …')
            try {
                const url = OC.generateUrl('/apps/flzbqplanung/api/proposals/year')
                const response = await fetch(`${url}?year=${year}`)
                const result = await response.json()
                if (!response.ok) throw new Error(result.error || translate('flzbqplanung', 'Die Jahresvorschau konnte nicht erstellt werden.'))
                const monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember']
                const rows = result.data.map(month => {
                    const row = document.createElement('tr')
                    const monthCell = document.createElement('th')
                    monthCell.scope = 'row'
                    monthCell.textContent = monthNames[month.month - 1] || `Monat ${month.month}`
                    const proposalCell = document.createElement('td')
                    proposalCell.textContent = month.proposal
                        ? `${month.proposal.startsOn} bis ${month.proposal.endsOn}`
                        : translate('flzbqplanung', 'Kein automatischer Vorschlag')
                    const statusCell = document.createElement('td')
                    const sourceText = (month.calendar?.sources || [])
                        .map(source => `${source.label}: ${sourceStatusLabel(source)}`)
                        .join('; ')
                    if (!month.proposal) {
                        const reason = month.error || month.calendar?.message || translate('flzbqplanung', 'Kalenderquelle nicht vollständig.')
                        statusCell.textContent = sourceText === '' ? reason : `${reason} ${sourceText}`
                    } else {
                        const rejected = month.proposal.rejectedCandidates || []
                        const labels = [...new Set(rejected.flatMap(candidate => candidate.conflicts || []).map(conflict => conflict.label))]
                        const conflictText = rejected.length === 0
                            ? translate('flzbqplanung', 'Keine verworfenen Starttermine.')
                            : `${rejected.length} verworfene Starttermine${labels.length > 0 ? `: ${labels.join(', ')}` : '.'}`
                        const coverage = month.calendar?.complete ? translate('flzbqplanung', 'Kalender vollständig.') : translate('flzbqplanung', 'Kalenderstand eingeschränkt.')
                        statusCell.textContent = `${coverage} ${conflictText}${sourceText === '' ? '' : ` ${sourceText}`}`
                    }
                    row.append(monthCell, proposalCell, statusCell)
                    return row
                })
                if (yearProposalRows) yearProposalRows.replaceChildren(...rows)
                if (yearProposalStatus) yearProposalStatus.textContent = `Jahresvorschau ${year} mit ${rows.length} Monaten erstellt.`
            } catch (error) {
                if (yearProposalStatus) yearProposalStatus.textContent = error instanceof Error ? error.message : translate('flzbqplanung', 'Die Jahresvorschau konnte nicht erstellt werden.')
            }
            return
        }
        if (!form.matches('form[data-endpoint]')) return
        event.preventDefault()
        const formData = new FormData(form)
        const payload = Object.fromEntries(formData.entries())
        if (form.matches('form[data-curriculum-template-form]')) {
            const keys = formData.getAll('moduleKey[]')
            const titles = formData.getAll('moduleTitle[]')
            const minutes = formData.getAll('moduleMinutes[]')
            payload.expectedVersion = Number(formData.get('expectedVersion'))
            payload.modules = keys.map((moduleKey, index) => ({
                moduleKey: String(moduleKey),
                title: String(titles[index] ?? ''),
                minutes: Number(minutes[index]),
            }))
            delete payload['moduleKey[]']
            delete payload['moduleTitle[]']
            delete payload['moduleMinutes[]']
        }
        for (const field of ['workdayCount', 'startWeekday', 'defaultCapacity', 'capacity', 'version', 'minutes', 'additionalCapacity', 'lecturerId', 'runVersion', 'moduleVersion', 'moduleId']) {
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
            feedback.textContent = translate('flzbqplanung', 'Änderung wird gespeichert …')
            feedback.dataset.state = 'pending'
        }
        try {
            const response = await fetch(OC.generateUrl(`/apps/flzbqplanung${form.dataset.endpoint}`), {
                method: form.dataset.method,
                headers: {
                    'Content-Type': 'application/json',
                    requesttoken: OC.requestToken,
                },
                body: JSON.stringify(payload),
            })
            const result = await response.json()
            if (!response.ok) throw new Error(result.error || translate('flzbqplanung', 'Die Änderung konnte nicht gespeichert werden.'))
            location.reload()
        } catch (error) {
            if (feedback) {
                feedback.textContent = error instanceof Error ? error.message : translate('flzbqplanung', 'Die Änderung konnte nicht gespeichert werden.')
                feedback.dataset.state = 'error'
            }
        }
    })
})
