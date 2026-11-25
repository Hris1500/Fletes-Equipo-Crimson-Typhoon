
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de Fletes</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <link rel="stylesheet" href="style.css">

    <script defer src="./app.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body>

<div class="app" id="app" x-data="contexto" x-init="inicializar()"
     @cambiar-vista.window="cambiar_vista($event.detail)">

    <header>
        <div class="lado-izquierdo">
            <button id="menu-boton" type="button" @click="alternar_menu()">
                <img src="./imagenes/tres_rayas.svg" alt="Abrir menú">
            </button>

            <h1>Fletes</h1>
        </div>

        <h1 class="titulo-vista" x-text="titulo_vista()"></h1>
    </header>

    <div id="menu-seleccion" class="contenedor-seleccion menu-toggle"
         :class="{ 'menu-toggle': menu_abierto }">

        <nav>
            <ul>
                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('dashboard')">
                        <img src="./imagenes/home.svg" alt="">
                        Dashboard
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('choferes')">
                        <img src="./imagenes/Choferes.svg" alt="">
                        Choferes
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('clientes')">
                        <img src="./imagenes/Clientes.svg" alt="">
                        Clientes
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('unidades')">
                        <img src="./imagenes/unidades.svg" alt="">
                        Unidades
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('fletes')">
                        <img src="./imagenes/fletes.svg" alt="">
                        Fletes
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('fletes_por_pagar')">
                        <img src="./imagenes/flete-pendiente.svg" alt="">
                        Fletes por pagar
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('reportes')">
                        <img src="./imagenes/reportes.svg" alt="">
                        Reportes
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('historial')">
                        <img src="./imagenes/historial.svg" alt="">
                        Historial
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion"
                            @click="cambiar_vista('creditos')">
                        <img src="./imagenes/creditos.svg" alt="">
                        Créditos
                    </button>
                </li>
            </ul>
        </nav>
    </div>

    <main id="main" :class="{ 'menu-toggle': menu_abierto }">

        <div class="cuerpo-templates" id="principal">

            <template x-if="vista === 'dashboard'">
                <div>
                    <?php include "./componentes/dashboard.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'choferes'">
                <div>
                    <?php include "./componentes/choferes.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'clientes'">
                <div>
                    <?php include "./componentes/clientes.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'unidades'">
                <div>
                    <?php include "./componentes/unidades.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'fletes'">
                <div>
                    <?php include "./componentes/fletes.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'fletes_por_pagar'">
                <div>
                    <?php include "./componentes/fletes-por-pagar.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'reportes'">
                <div>
                    <?php include "./componentes/reportes.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'historial'">
                <div>
                    <?php include "./componentes/historial.html"; ?>
                </div>
            </template>

            <template x-if="vista === 'creditos'">
                <div>
                    <?php include "./componentes/creditos.html"; ?>
                </div>
            </template>

            <hr>

            <footer>
                Todos los derechos reservados por el equipo Crimson Typhoon
            </footer>

        </div>
    </main>

</div>

</body>
</html>
