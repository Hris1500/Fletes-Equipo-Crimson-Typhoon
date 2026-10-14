<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestión de Fletes</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
	<link rel="stylesheet" href="style.css">
    <script src="app.js" defer></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body>

	<div class="app" id="app" x-data="contexto">

		<header>
			<div class="lado-izquierdo">

					<button id="menu-boton"><img src="./imagenes/tres_rayas.svg" alt=""></button>
					<h1>Fletes</h1>
			</div>
			<template x-if="vista != ''">
				<h1 class="titulo-vista" x-text="titulo_vista()"></h1>
			</template>
			<div>

			</div>
		</header>

		<div id="menu-seleccion" class="contenedor-seleccion menu-toggle">

				<nav>
					<ul>

						<li>
							<button class="boton-seleccion selected" @click="vista='dashboard'">
								<img src="./imagenes/home.svg" alt="Dashboard">
								Dashboard
							</button>
						</li>

						<li>
							<button class="boton-seleccion " @click="vista='choferes'">
								<img src="./imagenes/Choferes.svg" alt="">
								Choferes
							</button>
						</li>

						<li>
							<button class="boton-seleccion " @click="vista='clientes'">
								<img src="./imagenes/Clientes.svg" alt="">
								Clientes
							</button>
						</li>

						<li>
							<button class="boton-seleccion " @click="vista='unidades'">
								<img src="./imagenes/unidades.svg" alt="">
								Unidades
							</button>
						</li>


						<li>
							<button class="boton-seleccion " @click="vista='fletes'">
								<img src="./imagenes/fletes.svg" alt="">
								Fletes
							</button>
						</li>

						<li>
							<button class="boton-seleccion " @click="vista='fletes por pagar'">
								<img src="./imagenes/flete-pendiente.svg" alt="">
								Fletes por pagar
							</button>
						</li>

						<li>
							<button class="boton-seleccion " @click="vista='reportes'">
								<img src="./imagenes/reportes.svg" alt="">
								Reportes
							</button>
						</li>

						<li>
							<button class="boton-seleccion " @click="vista='historial'">
								<img src="./imagenes/historial.svg" alt="">
								Historial
							</button>
						</li>

						<li>
							<button class="boton-seleccion" @click="vista='creditos'">
								<img src="./imagenes/creditos.svg" alt="">
								Creditos
							</button>
						</li>

					</ul>

				</nav>
	
		</div>

		<main id="main" class="menu-toggle">

			<div class="cuerpo-templates" id="principal">
				<template x-if="vista === 'dashboard'">
						<?php include("./componentes/dashboard.html") ?>
				</template>

				<template x-if="vista === 'choferes'">
						<?php include("./componentes/choferes.html") ?>
				</template>

				<template x-if="vista === 'fletes por pagar'">
						<?php include("./componentes/fletes-por-pagar.html") ?>
				</template>

				<template x-if="vista === 'fletes'">
						<?php include("./componentes/fletes.html") ?>
				</template>

				<template x-if="vista === 'clientes'">
						<?php include("./componentes/clientes.html") ?>
				</template>

				<template x-if="vista === 'reportes'">
					<?php include("./componentes/reportes.html") ?>
				</template>

				<template x-if="vista === 'historial'">
						<?php include("./componentes/historial.html") ?>
				</template>
				
				<template x-if="vista === 'creditos'">
						<?php include("./componentes/creditos.html") ?>
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