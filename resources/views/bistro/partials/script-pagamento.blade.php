<script>
function bistroPagamento(cfg) {
    return {
        metodo: cfg.metodo, camera: cfg.camera, esito: null, dividi: false,
        righe: cfg.righe, totale: cfg.totale, q: {},
        init() {
            this.righe.forEach(r => this.q[r.id] = 0);
            if (this.camera) this.verifica();
        },
        get importo() {
            return this.dividi ? this.righe.reduce((s, r) => s + r.prezzo * (parseInt(this.q[r.id]) || 0), 0) : this.totale;
        },
        get pronto() {
            return this.importo > 0 && (this.metodo !== 'camera' || (this.esito && this.esito.valida));
        },
        eur(n) { return Number(n).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        async verifica() {
            const numero = this.camera.trim();
            if (! numero) { this.esito = null; return; }
            try {
                const r = await fetch(cfg.url + '?camera=' + encodeURIComponent(numero), { headers: { 'Accept': 'application/json' } });
                this.esito = await r.json();
            } catch (e) { this.esito = { valida: false }; }
        },
    };
}
</script>
