
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de Fletes</title>

    <link rel="stylesheet" href="style.css">

    <script defer src="./app.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body>

<div class="app" id="app" x-data="contexto" @keydown.escape.window="menu_abierto = false">

    <header>
        <div class="lado-izquierdo">
            <button id="menu-boton" type="button" @click="alternar_menu()"
                    aria-controls="menu-seleccion" aria-label="Menú"
                    :aria-expanded="menu_abierto.toString()">
                <img src="./imagenes/tres_rayas.svg" alt="">
            </button>

            <span class="marca">Fletes</span>
        </div>

        <h1 class="titulo-vista" x-text="titulo_vista()"></h1>
    </header>

    <div id="menu-seleccion" class="contenedor-seleccion"
         :class="{ 'menu-toggle': menu_abierto }">

        <nav>
            <ul>
                <li>
                    <button type="button" class="boton-seleccion" title="Dashboard"
                            :class="{ selected: vista === 'dashboard' }"
                            :aria-current="vista === 'dashboard' ? 'page' : false"
                            @click="cambiar_vista('dashboard')">
                        <img src="./imagenes/home.svg" alt="">
                        <span>Dashboard</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Choferes"
                            :class="{ selected: vista === 'choferes' }"
                            :aria-current="vista === 'choferes' ? 'page' : false"
                            @click="cambiar_vista('choferes')">
                        <img src="./imagenes/Choferes.svg" alt="">
                        <span>Choferes</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Clientes"
                            :class="{ selected: vista === 'clientes' }"
                            :aria-current="vista === 'clientes' ? 'page' : false"
                            @click="cambiar_vista('clientes')">
                        <img src="./imagenes/Clientes.svg" alt="">
                        <span>Clientes</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Unidades"
                            :class="{ selected: vista === 'unidades' }"
                            :aria-current="vista === 'unidades' ? 'page' : false"
                            @click="cambiar_vista('unidades')">
                        <img src="./imagenes/unidades.svg" alt="">
                        <span>Unidades</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Fletes"
                            :class="{ selected: vista === 'fletes' }"
                            :aria-current="vista === 'fletes' ? 'page' : false"
                            @click="cambiar_vista('fletes')">
                        <img src="./imagenes/fletes.svg" alt="">
                        <span>Fletes</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Fletes por pagar"
                            :class="{ selected: vista === 'fletes_por_pagar' }"
                            :aria-current="vista === 'fletes_por_pagar' ? 'page' : false"
                            @click="cambiar_vista('fletes_por_pagar')">
                        <img src="./imagenes/flete-pendiente.svg" alt="">
                        <span>Fletes por pagar</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Reportes"
                            :class="{ selected: vista === 'reportes' }"
                            :aria-current="vista === 'reportes' ? 'page' : false"
                            @click="cambiar_vista('reportes')">
                        <img src="./imagenes/reportes.svg" alt="">
                        <span>Reportes</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Historial"
                            :class="{ selected: vista === 'historial' }"
                            :aria-current="vista === 'historial' ? 'page' : false"
                            @click="cambiar_vista('historial')">
                        <img src="./imagenes/historial.svg" alt="">
                        <span>Historial</span>
                    </button>
                </li>

                <li>
                    <button type="button" class="boton-seleccion" title="Créditos"
                            :class="{ selected: vista === 'creditos' }"
                            :aria-current="vista === 'creditos' ? 'page' : false"
                            @click="cambiar_vista('creditos')">
                        <img src="./imagenes/creditos.svg" alt="">
                        <span>Créditos</span>
                    </button>
                </li>
            </ul>
        </nav>
    </div>

    <div class="fondo-menu" :class="{ visible: menu_abierto }"
         @click="menu_abierto = false"></div>

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

            <footer>
                Todos los derechos reservados por el equipo Crimson Typhoon
            </footer>

        </div>
    </main>

</div>

</body>
</html>
