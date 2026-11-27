(function() {
    'use strict';

    const shared = window.OCA.FlzBqPlanning?.Shared?.ui;
    if (!shared?.AdminAccessOverlay) return;

    const request = async (path, options = {}) => {
        const headers = options.headers || {};
        if (options.method && options.method !== 'GET') headers.requesttoken = OC.requestToken;
        const response = await fetch(OC.generateUrl('/apps/flzbqplanung' + path), { credentials: 'same-origin', ...options, headers });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Anfrage fehlgeschlagen.');
        return payload;
    };

    new shared.AdminAccessOverlay({
        overlayId: 'flz-bq-admin-access-overlay',
        triggerId: 'bq-admin-grant-warning',
        closeId: 'flz-bq-admin-access-overlay-close',
        formId: 'flz-bq-full-access-form',
        historyId: 'flz-bq-full-access-history',
        statusId: 'flz-bq-full-access-status',
        api: {
            getState: () => request('/api/admin/full-access'),
            activate: (targetUid, durationMinutes) => request('/api/admin/full-access', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ targetUid, durationMinutes }),
            }),
            revoke: (targetUid) => request('/api/admin/full-access/' + encodeURIComponent(targetUid), { method: 'DELETE' }),
        },
    });
}());
