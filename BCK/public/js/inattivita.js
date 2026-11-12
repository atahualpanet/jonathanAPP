(function () {
    const badge = document.getElementById('inattivita-badge');
    if (!badge) return;

    const MINUTI = parseInt(badge.dataset.minuti || '30', 10);
    const TOTALE = MINUTI * 60;          // secondi
    const SOGLIA_AVVISO = 5 * 60;        // ultimi 5 minuti: badge rosso

    let ultimaAttivita = Date.now();
    let throttle = null;

    // Qualsiasi attività dell'utente azzera il timer (throttle: max 1 reset ogni 10s)
    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click']
        .forEach(e => window.addEventListener(e, function () {
            if (throttle) return;
            throttle = setTimeout(() => throttle = null, 10000);
            ultimaAttivita = Date.now();
        }, { passive: true }));

    function formatta(sec) {
        return Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0');
    }

    // Render del badge: invisibile di default, visibile solo negli ultimi 5 minuti
    function disegna(rimanenti) {
        const inAvviso = rimanenti <= SOGLIA_AVVISO;
        badge.style.display = inAvviso ? 'inline-flex' : 'none';
        if (!inAvviso) return;   // nascosto: niente da disegnare

        badge.className = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold bg-red-100 text-red-700 border border-red-300 animate-pulse';
        badge.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Disconnensione tra ' + formatta(rimanenti) + '</span>';
    }

    async function logoutAutomatico() {
        try {
            await fetch('/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
        } catch (e) { /* la sessione server scade comunque da sola */ }
        window.location.href = '/login?inattivita=1';
    }

    setInterval(() => {
        const trascorsi = Math.floor((Date.now() - ultimaAttivita) / 1000);
        const rimanenti = Math.max(0, TOTALE - trascorsi);
        disegna(rimanenti);
        if (rimanenti === 0) logoutAutomatico();
    }, 1000);

    disegna(TOTALE);
})();