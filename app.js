function contexto() {
    const TITULOS = {
        dashboard: "Dashboard",
        clientes: "Clientes",
        unidades: "Unidades",
        choferes: "Choferes",
        fletes: "Fletes",
        fletes_por_pagar: "Fletes por pagar",
        reportes: "Reportes",
        historial: "Historial",
        creditos: "Créditos"
    };

    const ESTATUS = {
        pendiente: "Pendiente",
        en_transito: "En tránsito",
        finalizado: "Finalizado"
    };

    const CAMPOS_BUSQUEDA_FLETE = [
        "id_flete", "cliente", "chofer", "placas",
        "origen", "destino", "estatus"
    ];

    const moneda = new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN"
    });

    const formatearMonto = valor => moneda.format(Number(valor) || 0);
    const etiquetaEstatus = estatus => ESTATUS[estatus] ?? estatus;
    const etiquetaPago = pagado => Number(pagado) === 1 ? "Pagado" : "Por pagar";

    async function solicitarAPI(recurso, { method = "GET", id, body } = {}) {
        const parametros = new URLSearchParams({ recurso });

        if (id != null) parametros.set("id", id);

        let respuesta;

        try {
            respuesta = await fetch(`./api.php?${parametros}`, {
                method,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: body === undefined ? undefined : JSON.stringify(body)
            });
        } catch (error) {
            throw new Error(
                "No se pudo conectar con el servidor. Verifica que PHP esté funcionando."
            );
        }

        const texto = await respuesta.text();
        let datos;

        try {
            datos = JSON.parse(texto);
        } catch (error) {
            console.error("Respuesta recibida de la API:", texto);
            throw new Error(
                "La API no devolvió JSON válido. Revisa los errores de PHP."
            );
        }

        if (!respuesta.ok) {
            throw new Error(datos?.error ?? `Error HTTP ${respuesta.status}`);
        }

        return datos;
    }

    const extender = (base, extra) =>
        Object.defineProperties(base, Object.getOwnPropertyDescriptors(extra));

    function crearLista({ recurso, campos = [], catalogos = [], preparar }) {
        const base = {
            registros: [],
            busqueda: "",
            cargando: false,
            error: "",
            mensaje: "",

            get filtrados() {
                const texto = this.busqueda.trim().toLowerCase();

                if (!texto) return this.registros;

                return this.registros.filter(registro =>
                    campos.some(campo =>
                        String(registro[campo] ?? "").toLowerCase().includes(texto)
                    )
                );
            },

            init() {
                return this.cargar();
            },

            async cargar() {
                this.cargando = true;
                this.error = "";

                try {
                    const [registros, ...listas] = await Promise.all(
                        [recurso, ...catalogos].map(nombre => solicitarAPI(nombre))
                    );

                    this.registros = preparar ? registros.map(preparar) : registros;
                    catalogos.forEach((nombre, i) => this[nombre] = listas[i]);
                } catch (error) {
                    this.error = error.message;
                    console.error(`Error al cargar ${recurso}:`, error);
                } finally {
                    this.cargando = false;
                }
            }
        };

        catalogos.forEach(nombre => base[nombre] = []);

        return base;
    }

    function crearCRUD({ nombre, campoId, vacio, ...lista }) {
        const formularioDe = registro => Object.fromEntries(
            Object.entries({ [campoId]: null, ...vacio }).map(([campo, inicial]) => {
                const valor = registro?.[campo];

                if (valor == null) return [campo, inicial];
                if (typeof inicial === "boolean") return [campo, Number(valor) === 1];
                if (typeof inicial === "string") return [campo, String(valor)];

                return [campo, valor];
            })
        );

        return extender(crearLista(lista), {
            formulario: formularioDe(),
            formularioAbierto: false,
            guardando: false,
            errorFormulario: "",

            abrirFormulario(registro = null) {
                this.formulario = formularioDe(registro);
                this.errorFormulario = "";
                this.mensaje = "";
                this.formularioAbierto = true;

                const form = this.$root.querySelector("form");

                requestAnimationFrame(() => requestAnimationFrame(() => {
                    form.scrollIntoView({ block: "start" });
                    form.querySelector("input, select").focus({ preventScroll: true });
                }));
            },

            cerrarFormulario() {
                this.formularioAbierto = false;
                this.errorFormulario = "";
            },

            async guardar() {
                const id = this.formulario[campoId];

                this.errorFormulario = "";
                this.guardando = true;

                try {
                    await solicitarAPI(lista.recurso, {
                        method: id ? "PUT" : "POST",
                        id,
                        body: this.formulario
                    });

                    this.cerrarFormulario();
                    this.mensaje = `${nombre}: ${id ? "cambios guardados" : "registro creado"} correctamente.`;

                    await this.cargar();
                } catch (error) {
                    this.errorFormulario = error.message;
                    console.error(`Error al guardar ${lista.recurso}:`, error);
                } finally {
                    this.guardando = false;
                }
            }
        });
    }

    function prepararCambio(entrada) {
        const ENTIDADES = {
            clientes: "Cliente",
            choferes: "Chofer",
            unidades: "Unidad",
            fletes: "Flete"
        };

        const CAMPOS = {
            nombre: "Nombre",
            telefono: "Teléfono",
            correo: "Correo",
            direccion: "Dirección",
            num_licencia: "Licencia",
            activo: "Activo",
            activa: "Activa",
            placas: "Placas",
            tipo: "Tipo",
            capacidad_kg: "Capacidad (kg)",
            cliente: "Cliente",
            chofer: "Chofer",
            unidad: "Unidad",
            origen: "Origen",
            destino: "Destino",
            descripcion_carga: "Carga",
            fecha_salida: "Salida",
            fecha_entrega: "Entrega",
            monto: "Monto",
            estatus: "Estatus",
            pagado: "Pago"
        };

        const ACCIONES = {
            creado: "Creado",
            modificado: "Modificado",
            eliminado: "Eliminado"
        };

        const mostrar = (campo, valor) => {
            if (valor === null || valor === "") return "(vacío)";
            if (campo === "estatus") return etiquetaEstatus(valor);
            if (campo === "pagado") return etiquetaPago(valor);
            if (campo === "monto") return formatearMonto(valor);
            if (campo === "activo" || campo === "activa") {
                return Number(valor) === 1 ? "Sí" : "No";
            }

            return valor;
        };

        const esEstado = entrada.accion === "modificado" &&
            ["estatus", "pagado", "activo", "activa"]
                .some(campo => campo in entrada.cambios);

        return {
            fecha: new Date(entrada.fecha).toLocaleString("es-MX"),
            tipo: esEstado ? "estado" : entrada.accion,
            accion: esEstado ? "Cambio de estado" : ACCIONES[entrada.accion],
            registro: `${ENTIDADES[entrada.entidad]} #${entrada.id}: ${entrada.referencia}`,
            detalle: Object.entries(entrada.cambios).map(([campo, [antes, despues]]) => {
                const etiqueta = CAMPOS[campo] ?? campo;

                if (entrada.accion === "creado") {
                    return `${etiqueta}: ${mostrar(campo, despues)}`;
                }

                if (entrada.accion === "eliminado") {
                    return `${etiqueta}: ${mostrar(campo, antes)}`;
                }

                return `${etiqueta}: ${mostrar(campo, antes)} → ${mostrar(campo, despues)}`;
            })
        };
    }

    return {
        vista: "dashboard",
        menu_abierto: false,

        formatearMonto,
        etiquetaEstatus,
        etiquetaPago,

        titulo_vista() {
            return TITULOS[this.vista] ?? "Gestión de Fletes";
        },

        cambiar_vista(nombre) {
            this.vista = nombre;
            window.scrollTo({ top: 0, behavior: "instant" });

            if (window.matchMedia("(max-width: 640px)").matches) {
                this.menu_abierto = false;
            }
        },

        alternar_menu() {
            this.menu_abierto = !this.menu_abierto;
        },

        vistaDashboard() {
            return extender(crearLista({ recurso: "fletes" }), {
                get pendientes() {
                    return this.registros.filter(flete => Number(flete.pagado) === 0);
                },

                get totalPendiente() {
                    return this.pendientes.reduce(
                        (total, flete) => total + Number(flete.monto), 0
                    );
                }
            });
        },

        vistaClientes() {
            return crearCRUD({
                nombre: "Cliente",
                recurso: "clientes",
                campoId: "id_cliente",
                campos: ["nombre", "telefono", "correo", "direccion"],
                vacio: {
                    nombre: "",
                    telefono: "",
                    correo: "",
                    direccion: "",
                    activo: true
                }
            });
        },

        vistaChoferes() {
            return crearCRUD({
                nombre: "Chofer",
                recurso: "choferes",
                campoId: "id_chofer",
                campos: ["nombre", "telefono", "num_licencia"],
                vacio: {
                    nombre: "",
                    telefono: "",
                    num_licencia: "",
                    activo: true
                }
            });
        },

        vistaUnidades() {
            return crearCRUD({
                nombre: "Unidad",
                recurso: "unidades",
                campoId: "id_unidad",
                campos: ["placas", "tipo", "capacidad_kg"],
                vacio: {
                    placas: "",
                    tipo: "",
                    capacidad_kg: "",
                    activa: true
                }
            });
        },

        vistaFletes() {
            return crearCRUD({
                nombre: "Flete",
                recurso: "fletes",
                campoId: "id_flete",
                campos: CAMPOS_BUSQUEDA_FLETE,
                catalogos: ["clientes", "choferes", "unidades"],
                vacio: {
                    id_cliente: "",
                    id_chofer: "",
                    id_unidad: "",
                    origen: "",
                    destino: "",
                    descripcion_carga: "",
                    fecha_salida: "",
                    fecha_entrega: "",
                    monto: "",
                    estatus: "pendiente"
                }
            });
        },

        vistaFletesPorPagar() {
            const lista = crearLista({
                recurso: "fletes_por_pagar",
                campos: CAMPOS_BUSQUEDA_FLETE
            });

            return extender(lista, {
                procesandoPago: null,

                get totalPendiente() {
                    return this.registros.reduce(
                        (total, flete) => total + Number(flete.monto), 0
                    );
                },

                async registrarPago(flete) {
                    if (this.procesandoPago !== null) return;

                    if (!window.confirm(
                        `¿Confirmas que el flete #${flete.id_flete} ` +
                        `por ${formatearMonto(flete.monto)} ya fue pagado?`
                    )) {
                        return;
                    }

                    this.error = "";
                    this.mensaje = "";
                    this.procesandoPago = flete.id_flete;

                    try {
                        await solicitarAPI("fletes_por_pagar", {
                            method: "PUT",
                            id: flete.id_flete
                        });

                        this.mensaje =
                            `Pago del flete #${flete.id_flete} registrado correctamente.`;

                        await this.cargar();
                    } catch (error) {
                        this.error = error.message;
                        console.error("Error al registrar el pago:", error);
                    } finally {
                        this.procesandoPago = null;
                    }
                }
            });
        },

        vistaHistorial() {
            const lista = crearLista({
                recurso: "historial",
                campos: ["fecha", "accion", "registro", "detalle"],
                preparar: prepararCambio
            });

            const busqueda = Object.getOwnPropertyDescriptor(lista, "filtrados").get;

            return extender(lista, {
                tipo: "",

                get filtrados() {
                    return busqueda.call(this).filter(
                        cambio => !this.tipo || cambio.tipo === this.tipo
                    );
                }
            });
        }
    };
}
