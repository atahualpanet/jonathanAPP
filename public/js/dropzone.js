/**
 * Caricamento documento con drag & drop.
 * Ogni [data-dropzone] contiene un <input type="file"> e gli elementi [data-dz-*] (vedi partial documento-form).
 * Il file viene controllato qui (tipo e peso) per dare subito un messaggio chiaro; il server ricontrolla comunque.
 */
(function () {
    const TIPI_OK = ['image/jpeg', 'image/png', 'application/pdf'];
    const ESTENSIONI_OK = /\.(jpe?g|png|pdf)$/i;
    const MAX_BYTE = 5 * 1024 * 1024;

    const peso = b => b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';

    function inizializza(zona) {
        const input = zona.querySelector('input[type="file"]');
        const q = nome => zona.querySelector('[data-dz-' + nome + ']');
        const errore = zona.parentElement.querySelector('[data-dz-errore]');
        const nomeEl = q('nome'), pesoEl = q('peso'), anteprima = q('anteprima'), pdf = q('pdf'), rimuovi = q('rimuovi');
        let urlTemp = null;

        function mostraErrore(msg) {
            zona.classList.toggle('is-invalid', !!msg);
            if (errore) { errore.textContent = msg || ''; errore.hidden = !msg; }
        }

        function svuota() {
            input.value = '';
            if (urlTemp) { URL.revokeObjectURL(urlTemp); urlTemp = null; }
            zona.classList.remove('has-file');
            anteprima.hidden = true; anteprima.removeAttribute('src');
            pdf.hidden = true;
        }

        function accetta(file) {
            const tipoOk = TIPI_OK.includes(file.type) || (!file.type && ESTENSIONI_OK.test(file.name));
            if (!tipoOk) { svuota(); mostraErrore('Formato non valido: usa una foto JPG o PNG oppure un PDF.'); return; }
            if (file.size > MAX_BYTE) { svuota(); mostraErrore('File troppo pesante (' + peso(file.size) + '): massimo 5 MB.'); return; }

            mostraErrore('');
            nomeEl.textContent = file.name;
            pesoEl.textContent = peso(file.size);
            if (urlTemp) URL.revokeObjectURL(urlTemp);

            if (file.type === 'application/pdf' || /\.pdf$/i.test(file.name)) {
                anteprima.hidden = true; pdf.hidden = false;
            } else {
                urlTemp = URL.createObjectURL(file);
                anteprima.src = urlTemp; anteprima.hidden = false; pdf.hidden = true;
            }
            zona.classList.add('has-file');
        }

        // Click / tastiera sulla zona = apre la selezione file (su telefono offre anche la fotocamera)
        zona.addEventListener('click', e => {
            if (e.target.closest('[data-dz-rimuovi]')) return;
            if (!zona.classList.contains('has-file')) input.click();
        });
        zona.addEventListener('keydown', e => {
            if ((e.key === 'Enter' || e.key === ' ') && e.target === zona) { e.preventDefault(); input.click(); }
        });

        input.addEventListener('change', () => { input.files.length ? accetta(input.files[0]) : svuota(); });

        rimuovi.addEventListener('click', e => { e.stopPropagation(); svuota(); mostraErrore(''); zona.focus(); });

        // Drag & drop
        ['dragenter', 'dragover'].forEach(ev => zona.addEventListener(ev, e => {
            e.preventDefault();
            zona.classList.add('is-dragover');
        }));
        ['dragleave', 'dragend'].forEach(ev => zona.addEventListener(ev, e => {
            if (e.relatedTarget && zona.contains(e.relatedTarget)) return;
            zona.classList.remove('is-dragover');
        }));
        zona.addEventListener('drop', e => {
            e.preventDefault();
            zona.classList.remove('is-dragover');
            const file = e.dataTransfer && e.dataTransfer.files[0];
            if (!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            accetta(file);
        });
    }

    document.querySelectorAll('[data-dropzone]').forEach(inizializza);

    // Se si lascia cadere un file FUORI dalla zona il browser aprirebbe il file al posto della pagina: lo evitiamo
    ['dragover', 'drop'].forEach(ev => window.addEventListener(ev, e => {
        if (!e.target.closest('[data-dropzone]')) e.preventDefault();
    }));

    // "Modifica": mostra il modulo nascosto (data-mostra="id-del-blocco") e nasconde il pulsante
    document.querySelectorAll('[data-mostra]').forEach(btn => btn.addEventListener('click', () => {
        const blocco = document.getElementById(btn.dataset.mostra);
        if (blocco) { blocco.hidden = false; blocco.querySelector('input,select')?.focus(); }
        btn.hidden = true;
    }));
    document.querySelectorAll('[data-nascondi]').forEach(btn => btn.addEventListener('click', () => {
        const blocco = document.getElementById(btn.dataset.nascondi);
        if (blocco) blocco.hidden = true;
        document.querySelector('[data-mostra="' + btn.dataset.nascondi + '"]')?.removeAttribute('hidden');
    }));

    // Azioni distruttive: form[data-conferma="messaggio"] chiede conferma prima di inviare
    document.querySelectorAll('form[data-conferma]').forEach(form => form.addEventListener('submit', e => {
        if (!window.confirm(form.dataset.conferma)) e.preventDefault();
    }));

    // Evita il doppio invio (un file da 5 MB impiega qualche secondo)
    document.querySelectorAll('form[data-salva-una-volta]').forEach(form => form.addEventListener('submit', () => {
        const b = form.querySelector('button[type="submit"]');
        if (b) { b.disabled = true; b.textContent = 'Salvataggio…'; }
    }));
})();
