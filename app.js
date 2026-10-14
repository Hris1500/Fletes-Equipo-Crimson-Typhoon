
const menu_boton = document.getElementById("menu-boton");
const menu_seleccion = document.getElementById("menu-seleccion");
const main = document.querySelector("main");
const botones_seleccion = document.querySelectorAll(".boton-seleccion");

const API_URL = "./api.php";

// Menú lateral
if (menu_boton && menu_seleccion && main) {
    menu_boton.addEventListener("click", () => {
        menu_seleccion.classList.toggle("menu-toggle");
        main.classList.toggle("menu-toggle");
    });
}

// Vista seleccionada
botones_seleccion.forEach((boton) => {
    boton.addEventListener("click", () => {
        botones_seleccion.forEach((elemento) => {
            elemento.classList.remove("selected");
        });

        boton.classList.add("selected");
    });
});

// Comunicación con api.php
async function solicitarAPI(recurso, opciones = {}) {
    const url = new URL(API_URL, window.location.href);

    url.searchParams.set("recurso", recurso);

    if (opciones.id !== undefined) {
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

    const respuesta = await fetch(url, configuracion);
    const datos = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(datos.error ?? "Error en la solicitud");
    }

    return datos;
}



function contexto() {
    return {
        vista: "dashboard",
        clientes: [],

        titulo_vista() {
            if (!this.vista) {
                return "";
            }

            return this.vista.charAt(0).toUpperCase()
                + this.vista.slice(1);
        },


    };
}
