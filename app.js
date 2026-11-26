document.addEventListener('alpine:init', () => {
    Alpine.data('reportesApp', () => ({
        inicio: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        fin: new Date().toISOString().split('T')[0],
        estado: '',
        fletes: [],

        init() {
            this.cargar();
        },

        async cargar() {
            const url = `api.php?inicio=${this.inicio}&fin=${this.fin}&estado=${this.estado}`;
            const res = await fetch(url);
            this.fletes = await res.json();
        },

        get total() {
            return this.fletes.reduce((suma, f) => suma + Number(f.monto || 0), 0);
        }
    }));
});
