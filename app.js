const API_URL = "./api.php";

async function solicitarAPI(recurso, opciones = {}) {
    const url = new URL(API_URL, window.location.href);

    url.searchParams.set("recurso", recurso);

    if (opciones.id !== undefined && opciones.id !== null) {
        url.searchParams.set("id", opciones.id);
    }

    const configuracion = {
        method: opciones.method ?? "GET",
        headers: {
            Accept: "application/json"
        }
    };

    if (opciones.body !== undefined) {
        configuracion.headers["Content-Type"] = "application/json";
        configuracion.body = JSON.stringify(opciones.body);
    }

    let respuesta;

    try {
        respuesta = await fetch(url, configuracion);
    } catch (error) {
        throw new Error(
            "No se pudo conectar con el servidor. Verifica que PHP esté funcionando."
        );
    }

    const texto = await respuesta.text();
    let datos;

    try {
        datos = texto ? JSON.parse(texto) : null;
    } catch (error) {
        console.error("Respuesta recibida de la API:", texto);

        throw new Error(
            "La API no devolvió JSON válido. Revisa los errores de PHP."
        );
    }

    if (!respuesta.ok) {
        throw new Error(
            datos?.error ?? `Error HTTP ${respuesta.status}`
        );
    }

    return datos;
}


const VISTAS = {
    dashboard: {
        archivo: "./componentes/dashboard.html",
        titulo: "Dashboard"
    },
    clientes: {
        archivo: "./componentes/clientes.html",
        titulo: "Clientes"
    },
    unidades: {
        archivo: "./componentes/unidades.html",
        titulo: "Unidades"
    },
    choferes: {
        archivo: "./componentes/choferes.html",
        titulo: "Choferes"
    },
    fletes: {
        archivo: "./componentes/fletes.html",
        titulo: "Fletes"
    },
    fletes_por_pagar: {
        archivo: "./componentes/fletes_por_pagar.html",
        titulo: "Fletes por pagar"
    },
    reportes: {
        archivo: "./componentes/reportes.html",
        titulo: "Reportes"
    },
    historial: {
        archivo: "./componentes/historial.html",
        titulo: "Historial"
    },
    creditos: {
        archivo: "./componentes/creditos.html",
        titulo: "Créditos"
    }
};


function contexto() {
    return {
        vista: "dashboard",
        menu_abierto: false,

        titulo_vista() {
            const titulos = {
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

            return titulos[this.vista] ?? "Gestión de Fletes";
        },

        inicializar() {
            this.vista = "dashboard";
        },

        cambiar_vista(nombre) {
            this.vista = nombre;
            this.menu_abierto = false;
        },

        alternar_menu() {
            this.menu_abierto = !this.menu_abierto;
        }
    };
}
    

contexto.identificadorCarga = 0;


function escaparHTML(valor) {
    return String(valor ?? "").replace(/[&<>"']/g, caracter => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[caracter]);
}

function mensajeError(error, mensajePredeterminado = "Ocurrió un error.") {
    return error?.message ?? mensajePredeterminado;
}

function numeroONulo(valor) {
    if (valor === "" || valor === null || valor === undefined) {
        return null;
    }

    const numero = Number(valor);

    return Number.isFinite(numero) ? numero : null;
}


function vistaClientes() {
    return {
        clientes: [],
        busqueda: "",
        cargando: false,
        error: "",
        mensaje: "",

        mostrarFormulario: false,
        editando: false,

        formulario: {
            id_cliente: null,
            nombre: "",
            telefono: "",
            correo: "",
            direccion: "",
            activo: 1
        },

        get clientesFiltrados() {
            const texto = this.busqueda.trim().toLowerCase();

            if (!texto) return this.clientes;

            return this.clientes.filter(cliente =>
                [
                    cliente.nombre,
                    cliente.telefono,
                    cliente.correo,
                    cliente.direccion
                ].some(valor =>
                    String(valor ?? "").toLowerCase().includes(texto)
                )
            );
        },

        async init() {
            await this.cargarClientes();
        },

        async cargarClientes() {
            this.cargando = true;
            this.error = "";

            try {
                const datos = await solicitarAPI("clientes");

                this.clientes = Array.isArray(datos)
                    ? datos
                    : datos?.clientes ?? [];

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al cargar clientes:", error);

            } finally {
                this.cargando = false;
            }
        },

        abrirFormulario(cliente = null) {
            this.error = "";
            this.mensaje = "";

            if (cliente) {
                this.editando = true;

                this.formulario = {
                    id_cliente: cliente.id_cliente,
                    nombre: cliente.nombre ?? "",
                    telefono: cliente.telefono ?? "",
                    correo: cliente.correo ?? "",
                    direccion: cliente.direccion ?? "",
                    activo: Number(cliente.activo ?? 1)
                };
            } else {
                this.editando = false;

                this.formulario = {
                    id_cliente: null,
                    nombre: "",
                    telefono: "",
                    correo: "",
                    direccion: "",
                    activo: 1
                };
            }

            this.mostrarFormulario = true;
        },

        cerrarFormulario() {
            this.mostrarFormulario = false;
            this.error = "";
        },

        async guardarCliente() {
            this.error = "";
            this.mensaje = "";

            if (!this.formulario.nombre.trim()) {
                this.error = "El nombre del cliente es obligatorio.";
                return;
            }

            const datos = {
                nombre: this.formulario.nombre.trim(),
                telefono: this.formulario.telefono.trim(),
                correo: this.formulario.correo.trim(),
                direccion: this.formulario.direccion.trim(),
                activo: Number(this.formulario.activo)
            };

            const estabaEditando = this.editando;

            try {
                if (estabaEditando) {
                    await solicitarAPI("clientes", {
                        method: "PUT",
                        id: this.formulario.id_cliente,
                        body: datos
                    });
                } else {
                    await solicitarAPI("clientes", {
                        method: "POST",
                        body: datos
                    });
                }

                this.cerrarFormulario();

                this.mensaje = estabaEditando
                    ? "Cliente actualizado correctamente."
                    : "Cliente registrado correctamente.";

                await this.cargarClientes();

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al guardar cliente:", error);
            }
        },

        async eliminarCliente(cliente) {
            if (!window.confirm(
                `¿Deseas eliminar al cliente "${cliente.nombre}"?`
            )) {
                return;
            }

            this.error = "";

            try {
                await solicitarAPI("clientes", {
                    method: "DELETE",
                    id: cliente.id_cliente
                });

                this.mensaje = "Cliente eliminado correctamente.";
                await this.cargarClientes();

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al eliminar cliente:", error);
            }
        }
    };
}


function vistaChoferes() {
    return {
        choferes: [],
        busqueda: "",
        cargando: false,
        guardando: false,
        error: "",
        errorFormulario: "",
        mensaje: "",

        formularioAbierto: false,

        formulario: {
            id_chofer: null,
            nombre: "",
            telefono: "",
            num_licencia: "",
            activo: true
        },

        get choferesFiltrados() {
            const texto = this.busqueda.trim().toLowerCase();

            if (!texto) return this.choferes;

            return this.choferes.filter(chofer =>
                [
                    chofer.nombre,
                    chofer.telefono,
                    chofer.num_licencia
                ].some(valor =>
                    String(valor ?? "").toLowerCase().includes(texto)
                )
            );
        },

        async init() {
            await this.cargarChoferes();
        },

        async cargarChoferes() {
            this.cargando = true;
            this.error = "";

            try {
                const datos = await solicitarAPI("choferes");

                this.choferes = Array.isArray(datos)
                    ? datos
                    : datos?.choferes ?? [];

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al cargar choferes:", error);

            } finally {
                this.cargando = false;
            }
        },

        abrirFormulario(chofer = null) {
            this.errorFormulario = "";
            this.mensaje = "";

            if (chofer) {
                this.formulario = {
                    id_chofer: chofer.id_chofer,
                    nombre: chofer.nombre ?? "",
                    telefono: chofer.telefono ?? "",
                    num_licencia: chofer.num_licencia ?? "",
                    activo: Number(chofer.activo ?? 1) === 1
                };
            } else {
                this.formulario = {
                    id_chofer: null,
                    nombre: "",
                    telefono: "",
                    num_licencia: "",
                    activo: true
                };
            }

            this.formularioAbierto = true;
        },

        editarChofer(chofer) {
            this.abrirFormulario(chofer);
        },

        cerrarFormulario() {
            this.formularioAbierto = false;
            this.errorFormulario = "";
        },

        async guardarChofer() {
            this.errorFormulario = "";

            const datos = {
                nombre: this.formulario.nombre.trim(),
                telefono: this.formulario.telefono.trim(),
                num_licencia: this.formulario.num_licencia.trim(),
                activo: Number(this.formulario.activo)
            };

            if (!datos.nombre || !datos.num_licencia) {
                this.errorFormulario =
                    "El nombre y el número de licencia son obligatorios.";
                return;
            }

            const estabaEditando = Boolean(this.formulario.id_chofer);
            this.guardando = true;

            try {
                if (estabaEditando) {
                    await solicitarAPI("choferes", {
                        method: "PUT",
                        id: this.formulario.id_chofer,
                        body: datos
                    });
                } else {
                    await solicitarAPI("choferes", {
                        method: "POST",
                        body: datos
                    });
                }

                this.cerrarFormulario();
                this.mensaje = estabaEditando
                    ? "Chofer actualizado correctamente."
                    : "Chofer registrado correctamente.";

                await this.cargarChoferes();

            } catch (error) {
                this.errorFormulario = mensajeError(error);
                console.error("Error al guardar chofer:", error);

            } finally {
                this.guardando = false;
            }
        }
    };
}


function vistaUnidades() {
    return {
        unidades: [],
        busqueda: "",
        cargando: false,
        error: "",
        mensaje: "",

        mostrarFormulario: false,
        editando: false,

        formulario: {
            id_unidad: null,
            placas: "",
            tipo: "",
            capacidad_kg: "",
            activa: 1
        },

        get unidadesFiltradas() {
            const texto = this.busqueda.trim().toLowerCase();

            if (!texto) return this.unidades;

            return this.unidades.filter(unidad =>
                [
                    unidad.placas,
                    unidad.tipo,
                    unidad.capacidad_kg
                ].some(valor =>
                    String(valor ?? "").toLowerCase().includes(texto)
                )
            );
        },

        async init() {
            await this.cargarUnidades();
        },

        async cargarUnidades() {
            this.cargando = true;
            this.error = "";

            try {
                const datos = await solicitarAPI("unidades");

                this.unidades = Array.isArray(datos)
                    ? datos
                    : datos?.unidades ?? [];

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al cargar unidades:", error);

            } finally {
                this.cargando = false;
            }
        },

        abrirFormulario(unidad = null) {
            this.error = "";
            this.mensaje = "";

            if (unidad) {
                this.editando = true;

                this.formulario = {
                    id_unidad: unidad.id_unidad,
                    placas: unidad.placas ?? "",
                    tipo: unidad.tipo ?? "",
                    capacidad_kg: unidad.capacidad_kg ?? "",
                    activa: Number(unidad.activa ?? 1)
                };
            } else {
                this.editando = false;

                this.formulario = {
                    id_unidad: null,
                    placas: "",
                    tipo: "",
                    capacidad_kg: "",
                    activa: 1
                };
            }

            this.mostrarFormulario = true;
        },

        cerrarFormulario() {
            this.mostrarFormulario = false;
            this.error = "";
        },

        async guardarUnidad() {
            this.error = "";
            this.mensaje = "";

            if (!this.formulario.placas.trim()) {
                this.error = "Las placas son obligatorias.";
                return;
            }

            const capacidad =
                numeroONulo(this.formulario.capacidad_kg);

            if (
                this.formulario.capacidad_kg !== "" &&
                (capacidad === null || capacidad < 0)
            ) {
                this.error = "La capacidad debe ser un número válido.";
                return;
            }

            const datos = {
                placas: this.formulario.placas.trim(),
                tipo: this.formulario.tipo.trim(),
                capacidad_kg: capacidad,
                activa: Number(this.formulario.activa)
            };

            const estabaEditando = this.editando;

            try {
                if (estabaEditando) {
                    await solicitarAPI("unidades", {
                        method: "PUT",
                        id: this.formulario.id_unidad,
                        body: datos
                    });
                } else {
                    await solicitarAPI("unidades", {
                        method: "POST",
                        body: datos
                    });
                }

                this.cerrarFormulario();

                this.mensaje = estabaEditando
                    ? "Unidad actualizada correctamente."
                    : "Unidad registrada correctamente.";

                await this.cargarUnidades();

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al guardar unidad:", error);
            }
        },

        async eliminarUnidad(unidad) {
            if (!window.confirm(
                `¿Deseas eliminar la unidad con placas "${unidad.placas}"?`
            )) {
                return;
            }

            this.error = "";

            try {
                await solicitarAPI("unidades", {
                    method: "DELETE",
                    id: unidad.id_unidad
                });

                this.mensaje = "Unidad eliminada correctamente.";
                await this.cargarUnidades();

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al eliminar unidad:", error);
            }
        }
    };
}


function vistaFletes() {
    return {
        fletes: [],
        clientes: [],
        choferes: [],
        unidades: [],

        busqueda: "",
        cargando: false,
        guardando: false,
        error: "",
        errorFormulario: "",
        mensaje: "",

        formularioAbierto: false,

        formulario: {
            id_flete: null,
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
        },

        get fletesFiltrados() {
            const texto = this.busqueda.trim().toLowerCase();

            if (!texto) return this.fletes;

            return this.fletes.filter(flete =>
                [
                    flete.id_flete,
                    flete.cliente,
                    flete.chofer,
                    flete.placas,
                    flete.origen,
                    flete.destino,
                    flete.estatus
                ].some(valor =>
                    String(valor ?? "").toLowerCase().includes(texto)
                )
            );
        },

        async init() {
            await this.cargarDatos();
        },

        async cargarDatos() {
            this.cargando = true;
            this.error = "";

            try {
                const [fletes, clientes, choferes, unidades] =
                    await Promise.all([
                        solicitarAPI("fletes"),
                        solicitarAPI("clientes"),
                        solicitarAPI("choferes"),
                        solicitarAPI("unidades")
                    ]);

                this.fletes = Array.isArray(fletes)
                    ? fletes
                    : fletes?.fletes ?? [];

                this.clientes = Array.isArray(clientes)
                    ? clientes
                    : clientes?.clientes ?? [];

                this.choferes = Array.isArray(choferes)
                    ? choferes
                    : choferes?.choferes ?? [];

                this.unidades = Array.isArray(unidades)
                    ? unidades
                    : unidades?.unidades ?? [];

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al cargar datos de fletes:", error);

            } finally {
                this.cargando = false;
            }
        },

        abrirFormulario(flete = null) {
            this.errorFormulario = "";

            if (flete) {
                this.formulario = {
                    id_flete: flete.id_flete,
                    id_cliente: String(flete.id_cliente ?? ""),
                    id_chofer: String(flete.id_chofer ?? ""),
                    id_unidad: String(flete.id_unidad ?? ""),
                    origen: flete.origen ?? "",
                    destino: flete.destino ?? "",
                    descripcion_carga: flete.descripcion_carga ?? "",
                    fecha_salida: flete.fecha_salida ?? "",
                    fecha_entrega: flete.fecha_entrega ?? "",
                    monto: flete.monto ?? "",
                    estatus: flete.estatus ?? "pendiente"
                };
            } else {
                this.formulario = {
                    id_flete: null,
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
                };
            }

            this.formularioAbierto = true;
        },

        editarFlete(flete) {
            this.abrirFormulario(flete);
        },

        cerrarFormulario() {
            this.formularioAbierto = false;
            this.errorFormulario = "";
        },

        async guardarFlete() {
            this.errorFormulario = "";

            const f = this.formulario;
            const monto = Number(f.monto);

            if (!f.id_cliente || !f.id_chofer || !f.id_unidad) {
                this.errorFormulario =
                    "Selecciona un cliente, un chofer y una unidad.";
                return;
            }

            if (!f.origen.trim() || !f.destino.trim()) {
                this.errorFormulario =
                    "El origen y el destino son obligatorios.";
                return;
            }

            if (!Number.isFinite(monto) || monto <= 0) {
                this.errorFormulario =
                    "El monto debe ser mayor que cero.";
                return;
            }

            if (
                f.fecha_salida &&
                f.fecha_entrega &&
                f.fecha_entrega < f.fecha_salida
            ) {
                this.errorFormulario =
                    "La fecha de entrega no puede ser anterior a la salida.";
                return;
            }

            const datos = {
                id_cliente: Number(f.id_cliente),
                id_chofer: Number(f.id_chofer),
                id_unidad: Number(f.id_unidad),
                origen: f.origen.trim(),
                destino: f.destino.trim(),
                descripcion_carga: f.descripcion_carga.trim(),
                fecha_salida: f.fecha_salida || null,
                fecha_entrega: f.fecha_entrega || null,
                monto,
                estatus: f.estatus
            };

            const estabaEditando = Boolean(f.id_flete);

            this.guardando = true;

            try {
                if (estabaEditando) {
                    await solicitarAPI("fletes", {
                        method: "PUT",
                        id: f.id_flete,
                        body: datos
                    });
                } else {
                    await solicitarAPI("fletes", {
                        method: "POST",
                        body: datos
                    });
                }

                this.cerrarFormulario();

                this.mensaje = estabaEditando
                    ? "Flete actualizado correctamente."
                    : "Flete registrado correctamente.";

                await this.cargarDatos();

            } catch (error) {
                this.errorFormulario = mensajeError(error);
                console.error("Error al guardar flete:", error);

            } finally {
                this.guardando = false;
            }
        },

        formatearMonto(valor) {
            return new Intl.NumberFormat("es-MX", {
                style: "currency",
                currency: "MXN"
            }).format(Number(valor) || 0);
        }
    };
}


function vistaFletesPorPagar() {
    return {
        fletes: [],
        cargando: false,
        error: "",
        mensaje: "",
        busqueda: "",
        procesandoPago: null,

        get fletesFiltrados() {
            const texto = this.busqueda.trim().toLowerCase();

            if (!texto) return this.fletes;

            return this.fletes.filter(flete =>
                [
                    flete.id_flete,
                    flete.cliente,
                    flete.chofer,
                    flete.placas,
                    flete.origen,
                    flete.destino,
                    flete.estatus
                ].some(valor =>
                    String(valor ?? "").toLowerCase().includes(texto)
                )
            );
        },

        get totalPendiente() {
            return this.fletes.reduce(
                (total, flete) => total + Number(flete.monto || 0),
                0
            );
        },

        async init() {
            await this.cargarFletes();
        },

        async cargarFletes() {
            this.cargando = true;
            this.error = "";

            try {
                const datos =
                    await solicitarAPI("fletes_por_pagar");

                const lista = Array.isArray(datos)
                    ? datos
                    : datos?.fletes ?? [];

                this.fletes = lista.filter(
                    flete => Number(flete.pagado) === 0
                );

            } catch (error) {
                this.error = mensajeError(error);
                console.error(
                    "Error al cargar fletes por pagar:",
                    error
                );

            } finally {
                this.cargando = false;
            }
        },

        async registrarPago(flete) {
            if (this.procesandoPago !== null) {
                return;
            }

            const confirmado = window.confirm(
                `¿Confirmas que el flete #${flete.id_flete} ` +
                `por ${this.formatearMonto(flete.monto)} ya fue pagado?`
            );

            if (!confirmado) return;

            this.error = "";
            this.mensaje = "";
            this.procesandoPago = flete.id_flete;

            try {
                await solicitarAPI("fletes_por_pagar", {
                    method: "PUT",
                    id: flete.id_flete,
                    body: {}
                });

                this.mensaje =
                    `Pago del flete #${flete.id_flete} registrado correctamente.`;

                await this.cargarFletes();

            } catch (error) {
                this.error = mensajeError(error);
                console.error("Error al registrar el pago:", error);

            } finally {
                this.procesandoPago = null;
            }
        },

        formatearMonto(valor) {
            return new Intl.NumberFormat("es-MX", {
                style: "currency",
                currency: "MXN"
            }).format(Number(valor) || 0);
        }
    };
}


document.addEventListener("alpine:init", () => {
    Alpine.data("contexto", contexto);
    Alpine.data("vistaClientes", vistaClientes);
    Alpine.data("vistaChoferes", vistaChoferes);
    Alpine.data("vistaUnidades", vistaUnidades);
    Alpine.data("vistaFletes", vistaFletes);
    Alpine.data("vistaFletesPorPagar", vistaFletesPorPagar);
});


document.addEventListener("DOMContentLoaded", () => {
    const botonMenu = document.getElementById("menu-boton");
    const menuSeleccion = document.getElementById("menu-seleccion");

    if (botonMenu && menuSeleccion) {
        botonMenu.addEventListener("click", () => {
            menuSeleccion.classList.toggle("menu-visible");
        });

        menuSeleccion.addEventListener("click", event => {
            const boton = event.target.closest("[data-vista]");

            if (!boton) return;

            const nombreVista = boton.dataset.vista;
            const raiz = document.querySelector('[x-data="contexto"]');

            if (raiz && window.Alpine) {
                const componente = Alpine.$data(raiz);

                if (componente?.cambiar_vista) {
                    componente.cambiar_vista(nombreVista);
                }
            }

            menuSeleccion.classList.remove("menu-visible");
        });
    }
});
