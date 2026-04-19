// assets/js/parksys.js — ParkSys Pro Frontend Utilities

const ParkSys = {

    BASE: document.querySelector('meta[name="base-url"]')?.content ?? '',
    CSRF: document.querySelector('meta[name="csrf-token"]')?.content ?? '',

    /**
     * Secure Fetch Wrapper
     * Automatically adds CSRF token to headers for non-GET requests.
     */
    async secureFetch(url, options = {}) {
        if (!options.method) options.method = 'GET';
        
        if (options.method.toUpperCase() !== 'GET') {
            if (!options.headers) options.headers = {};
            options.headers['X-CSRF-TOKEN'] = ParkSys.CSRF;
        }

        const fullUrl = url.startsWith('http') ? url : ParkSys.BASE + url;
        return fetch(fullUrl, options);
    },

    // ── ENTRY ────────────────────────────────────────────────
    processEntry(slotId, plateNumber, vehicleType, callback) {
        const data = new FormData();
        data.append('slot_id', slotId);
        data.append('plate_number', plateNumber.toUpperCase().trim());
        data.append('vehicle_type', vehicleType);

        ParkSys.secureFetch('/api/process_entry.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                ParkSys.toast(res.message, res.success ? 'success' : 'danger');
                if (callback) callback(res);
            })
            .catch(() => ParkSys.toast('Network error during entry. Please retry.', 'danger'));
    },

    // ── EXIT ─────────────────────────────────────────────────
    processExit(identifier, callback, paymentMethod = 'cash') {
        const data = new FormData();
        data.append('identifier', identifier);
        data.append('payment_method', paymentMethod);

        ParkSys.secureFetch('/api/process_exit.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                ParkSys.toast(res.message, res.success ? 'success' : 'danger');
                if (callback) callback(res);
            })
            .catch(() => ParkSys.toast('Network error during exit. Please retry.', 'danger'));
    },

    // ── REFRESH SLOTS (live AJAX update) ─────────────────────
    refreshSlots() {
        ParkSys.secureFetch('/api/get_slots.php')
            .then(r => r.json())
            .then(({ slots }) => {
                if (!slots) return;
                slots.forEach(slot => {
                    const el = document.querySelector(`[data-slot-id="${slot.id}"]`);
                    if (!el) return;

                    // Update classes and data attributes
                    el.className = `slot slot-${slot.status}`;
                    el.dataset.status = slot.status;
                    el.dataset.plate = slot.plate_number ?? '';
                    el.dataset.entry = slot.entry_time ?? '';
                    el.dataset.ref = slot.reference_id ?? '';

                    // Update vehicle icon
                    const icons = { motorcycle: '🏍', van: '🚐', truck: '🚐', car: '🚗' };
                    const vIcon = el.querySelector('.slot-vehicle');
                    if (vIcon) vIcon.textContent = slot.vehicle_type ? (icons[slot.vehicle_type] ?? '🚗') : '';

                    // Update duration label
                    const durEl = el.querySelector('.slot-dur');
                    if (durEl) durEl.textContent = slot.duration_label ?? '';
                });
            })
            .catch(err => console.warn('[ParkSys] refreshSlots error:', err));
    },

    // ── TOAST NOTIFICATIONS ──────────────────────────────────
    toast(message, type = 'success') {
        const wrap = document.getElementById('toast-container') ?? ParkSys._mkToastContainer();
        const t = document.createElement('div');
        t.className = `ps-toast ps-toast-${type}`;
        t.textContent = message;
        wrap.appendChild(t);

        // Animate in
        requestAnimationFrame(() => t.classList.add('show'));
        setTimeout(() => {
            t.classList.remove('show');
            setTimeout(() => t.remove(), 300);
        }, 4000);
    },

    _mkToastContainer() {
        const c = document.createElement('div');
        c.id = 'toast-container';
        c.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        // Inject toast styles
        const style = document.createElement('style');
        style.textContent = `
      .ps-toast{background:#1a2236;border:1px solid #2a3550;color:#e8edf7;padding:12px 16px;border-radius:10px;
        font-family:'DM Sans',sans-serif;font-size:13px;max-width:320px;opacity:0;transform:translateX(20px);
        transition:.3s cubic-bezier(.16,1,.3,1);}
      .ps-toast.show{opacity:1;transform:translateX(0);}
      .ps-toast.ps-toast-success{border-color:rgba(34,197,94,.4);}
      .ps-toast.ps-toast-danger{border-color:rgba(239,68,68,.4);color:#f87d84;}
    `;
        document.head.appendChild(style);
        document.body.appendChild(c);
        return c;
    },

    // ── FORMAT HELPERS ────────────────────────────────────────
    peso(amount) {
        return '₱' + parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    },

    formatDuration(mins) {
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        const parts = [];
        if (h) parts.push(`${h} hr${h > 1 ? 's' : ''}`);
        if (m) parts.push(`${m} min`);
        return parts.length ? parts.join(' ') : '< 1 min';
    },
};

// ── Global keyboard shortcut: Ctrl+E = focus entry ──────────
document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 'e') {
        const inp = document.getElementById('m-plate') ?? document.querySelector('.entry-plate-input');
        if (inp) { e.preventDefault(); inp.focus(); }
    }
});