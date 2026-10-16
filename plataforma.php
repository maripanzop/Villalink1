
<?php
session_start();

if (!isset($_SESSION["egresado_id"])) {
    header("Location: index.php");
    exit;
}

require_once "conexion.php";

$nombre = htmlspecialchars(
    $_SESSION["egresado_nombre"] ?? "Egresado",
    ENT_QUOTES,
    "UTF-8"
);

// Obtener los datos reales del egresado conectado.
$stmt = $conexion->prepare(
    "SELECT nombre, apellidos, correo, carrera_tecnica, matricula
     FROM egresados
     WHERE id = ?"
);
$stmt->execute([$_SESSION["egresado_id"]]);
$egresado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$egresado) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$nombreCompleto = htmlspecialchars(
    $egresado["nombre"] . " " . $egresado["apellidos"],
    ENT_QUOTES,
    "UTF-8"
);
$carrera = htmlspecialchars(
    $egresado["carrera_tecnica"],
    ENT_QUOTES,
    "UTF-8"
);
$correo = htmlspecialchars(
    $egresado["correo"],
    ENT_QUOTES,
    "UTF-8"
);
$matricula = htmlspecialchars(
    $egresado["matricula"],
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>VillaLink | Inicio</title>

<link rel="stylesheet" href="barra_lateral.css">



<style>
:root {
    --azul: #718bbd;
    --azul-oscuro: #526fa7;
    --azul-claro: #e3f1fc;
    --fondo: #e2f1fc;
    --blanco: #ffffff;
    --texto: #33445e;
    --gris: #748198;
    --borde: #dce9f6;
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 25px;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--fondo);
    color: var(--texto);
}

a {
    text-decoration: none;
    color: inherit;
}

button, input, select {
    font: inherit;
}

.layout {
    min-height: 100vh;
}

.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 228px;
    padding: 20px 12px;
    overflow-y: auto;
    background: var(--azul);
    color: white;
    z-index: 10;
}

.logo {
    margin: 0 0 33px 10px;
    font-size: 25px;
    font-weight: 800;
    letter-spacing: .5px;
}

.usuario {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 10px;
    margin-bottom: 28px;
    background: rgba(255,255,255,.15);
    border-radius: 12px;
    overflow-wrap: anywhere;
}

.avatar {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #e3f1fc;
    color: var(--azul-oscuro);
    font-size: 19px;
    font-weight: bold;
}

.usuario strong {
    display: block;
    font-size: 12px;
}

.usuario small {
    display: block;
    margin-top: 5px;
    font-size: 11px;
    opacity: .9;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 12px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    transition: background .2s;
}

.menu a:hover,
.menu a.activo {
    background: rgba(255,255,255,.20);
}

.menu .salir {
    margin-top: 20px;
    background: rgba(45,64,103,.20);
}


.contenido {
    margin-left: 248px !important;
    min-width: 0;
    padding: 26px 28px 40px;
    box-sizing: border-box;
}


.titulo-principal {
    margin: 0 0 26px;
    color: var(--azul);
    font-size: 29px;
}

.panel {
    padding: 25px;
    background: var(--blanco);
    border: 1px solid var(--borde);
    border-radius: 14px;
}

.bienvenida {
    margin-bottom: 22px;
    border-left: 5px solid #9bc8e8;
}

.bienvenida h2 {
    margin: 0 0 12px;
    color: var(--azul);
    font-size: 19px;
}

.bienvenida p {
    margin: 7px 0;
    font-size: 14px;
    line-height: 1.7;
}

.estadisticas {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 22px;
}

.estadistica {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 19px;
    background: white;
    border-radius: 13px;
    border: 1px solid var(--borde);
}

.icono-estadistica {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    background: var(--azul-claro);
    border-radius: 10px;
    font-size: 20px;
}

.estadistica strong {
    display: block;
    font-size: 24px;
    color: #26364e;
}

.estadistica span {
    display: block;
    margin-top: 4px;
    color: var(--gris);
    font-size: 12px;
}

.contenido-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(280px, 1fr);
    gap: 18px;
    align-items: start;
}

.columna {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-width: 0;
}

.encabezado-seccion {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.encabezado-seccion h2 {
    margin: 0;
    color: var(--azul);
    font-size: 16px;
}

.enlace-azul {
    color: #82b5e0;
    font-size: 12px;
    font-weight: 700;
}

.empleo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 11px;
    margin-bottom: 12px;
    border: 1px solid #e0edf9;
    border-radius: 9px;
}

.empleo:last-child {
    margin-bottom: 0;
}

.empresa-icono {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 43px;
    height: 43px;
    border-radius: 8px;
    background: #9bc8e8;
    color: white;
    font-weight: 800;
}

.empleo-info {
    flex: 1;
    min-width: 0;
}

.empleo-info h3 {
    margin: 0 0 5px;
    font-size: 13px;
    color: #26364e;
}

.empleo-info p {
    margin: 0 0 6px;
    color: var(--gris);
    font-size: 11px;
}

.etiqueta {
    display: inline-block;
    padding: 4px 7px;
    background: #e3f1fc;
    color: #526fa7;
    border-radius: 4px;
    font-size: 10px;
}

.boton-pequeno {
    padding: 8px 10px;
    border: 0;
    border-radius: 6px;
    background: #e3f1fc;
    color: #526fa7;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.boton-pequeno:hover {
    background: #c9e3f8;
}

.panel h2 {
    font-size: 16px;
}

.panel-seguimiento p,
.evento p {
    font-size: 13px;
    line-height: 1.6;
}

.boton-principal {
    display: inline-block;
    padding: 11px 15px;
    border: 0;
    border-radius: 7px;
    background: #718bbd;
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.boton-principal:hover {
    background: var(--azul-oscuro);
}

.evento-fecha {
    color: var(--azul);
    font-size: 12px;
    font-weight: 800;
}

.evento h3 {
    margin: 8px 0 5px;
    font-size: 14px;
}

.mentores {
    color: white;
    background: linear-gradient(115deg, #9bc8e8, #718bbd);
    border: 0;
}

.mentores h2 {
    margin-top: 0;
}

.mentores p {
    font-size: 13px;
    line-height: 1.7;
}

.boton-blanco {
    display: inline-block;
    padding: 10px 14px;
    border-radius: 7px;
    background: white;
    color: #526fa7;
    font-size: 12px;
    font-weight: bold;
}

.perfil-datos {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.dato {
    padding: 13px;
    background: #f5f9fd;
    border-radius: 8px;
    overflow-wrap: anywhere;
}

.dato span {
    display: block;
    margin-bottom: 6px;
    color: var(--gris);
    font-size: 12px;
}

.dato strong {
    font-size: 13px;
}

.seccion-oculta {
    scroll-margin-top: 20px;
}

footer {
    padding: 25px 0 0;
    color: var(--gris);
    text-align: center;
    font-size: 11px;
}

@media (max-width: 1100px) {
    .contenido-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .sidebar {
        position: relative;
        width: 100%;
        padding: 14px;
    }

    .logo {
        margin: 0 0 14px 5px;
    }

    .usuario {
        margin-bottom: 14px;
    }

    .menu {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .menu a {
        padding: 11px 9px;
    }

    .menu .salir {
        margin-top: 0;
    }

    .contenido {
        margin-left: 0;
        padding: 22px 14px;
    }

    .titulo-principal {
        font-size: 23px;
    }

    .estadisticas {
        grid-template-columns: 1fr;
    }

    .perfil-datos {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 420px) {
    .empleo {
        flex-wrap: wrap;
    }

    .empleo .boton-pequeno {
        margin-left: auto;
    }

    .panel {
        padding: 18px;
    }
}
</style>
</head>

<body>
<div class="layout">


<?php require_once __DIR__ . "/barra_lateral.php"; ?>


<main class="contenido">

    <section id="inicio">
        <h1 class="titulo-principal">
            Bienvenida, <?= htmlspecialchars(
                $egresado["nombre"],
                ENT_QUOTES,
                "UTF-8"
            ) ?> a VillaLink
        </h1>

        <div class="panel bienvenida">
            <h2>¡Bienvenida a VILLALINK, Egresada!</h2>
            <p>
                Tu acceso ha sido verificado con éxito. Aunque las aulas
                quedaron atrás, la comunidad de Villa de los Niños te
                acompaña a donde vayas.
            </p>
            <p>
                Explora oportunidades laborales, conecta con tu generación
                y cuéntanos qué estás logrando hoy en el mundo profesional.
            </p>
        </div>
    </section>

    <section class="estadisticas" aria-label="Estadísticas">
        <article class="estadistica">
            <div class="icono-estadistica">👥</div>
            <div>
                <strong>VillaLink</strong>
                <span>Red de egresados</span>
            </div>
        </article>

        <article class="estadistica">
            <div class="icono-estadistica">💼</div>
            <div>
                <strong>Empleo</strong>
                <span>Oportunidades laborales</span>
            </div>
        </article>

        <article class="estadistica">
            <div class="icono-estadistica">🎓</div>
            <div>
                <strong>Comunidad</strong>
                <span>Conexiones profesionales</span>
            </div>
        </article>
    </section>

    <div class="contenido-grid">

        <div class="columna">

            <section class="panel seccion-oculta" id="empleos">
                <div class="encabezado-seccion">
                    <h2>Bolsa de Trabajo Exclusiva</h2>
                    <a class="enlace-azul" href="#explorar">Ver más ↓</a>
                </div>

                <article class="empleo">
                    <div class="empresa-icono">B</div>
                    <div class="empleo-info">
                        <h3>Auxiliar Contable / Financiero</h3>
                        <p>Grupo Bimbo · Ciudad de México</p>
                        <span class="etiqueta">Híbrido</span>
                    </div>
                    <button class="boton-pequeno"
                            type="button"
                            onclick="alert('Esta oferta es una demostración. Próximamente podrás postularte.')">
                        Postularse
                    </button>
                </article>

                <article class="empleo">
                    <div class="empresa-icono">T</div>
                    <div class="empleo-info">
                        <h3>Soporte Técnico Junior</h3>
                        <p>Empresa tecnológica · Estado de México</p>
                        <span class="etiqueta">Presencial</span>
                    </div>
                    <button class="boton-pequeno"
                            type="button"
                            onclick="alert('Esta oferta es una demostración. Próximamente podrás postularte.')">
                        Postularse
                    </button>
                </article>
            </section>

            <section class="panel mentores seccion-oculta" id="explorar">
                <h2>¿Necesitas orientación profesional?</h2>
                <p>
                    Conéctate con egresados de generaciones mayores que hoy
                    trabajan en distintas empresas. Recibe consejos sobre
                    entrevistas, currículum y crecimiento laboral.
                </p>
                <a class="boton-blanco" href="#carreras">
                    Explorar Red de Mentores
                </a>
            </section>

            <section class="panel seccion-oculta" id="encuestas">
                <h2>Encuestas para egresados</h2>
                <p>
                    Tu experiencia es importante para mejorar la formación
                    y conocer las necesidades de nuestra comunidad.
                </p>
                <span class="etiqueta">Módulo en preparación</span>
            </section>

        </div>

        <div class="columna">

            <section class="panel panel-seguimiento">
                <h2>Seguimiento Laboral</h2>
                <p>
                    ¿Cambiaste de empleo, ascendiste o sigues estudiando?
                    Mantén tu información al día para que podamos conocer
                    tu trayectoria.
                </p>
                <a class="boton-principal" href="#mi-perfil">
                    Actualizar mi Situación Actual
                </a>
            </section>

            <section class="panel seccion-oculta" id="eventos">
                <div class="encabezado-seccion">
                    <h2>Próximos Eventos</h2>
                </div>

                <article class="evento">
                    <span class="evento-fecha">PRÓXIMAMENTE</span>
                    <h3>Reencuentro de Generaciones</h3>
                    <p>Comunidad VillaLink · Chalco</p>
                    <span class="etiqueta">Evento de ejemplo</span>
                </article>
            </section>

            <section class="panel seccion-oculta" id="mi-perfil">
                <div class="encabezado-seccion">
                    <h2>Mi perfil</h2>
                    <span class="etiqueta">Mis datos</span>
                </div>

                <div class="perfil-datos">
                    <div class="dato">
                        <span>Nombre completo</span>
                        <strong><?= $nombreCompleto ?></strong>
                    </div>

                    <div class="dato">
                        <span>Carrera técnica</span>
                        <strong><?= $carrera ?></strong>
                    </div>

                    <div class="dato">
                        <span>Matrícula</span>
                        <strong><?= $matricula ?></strong>
                    </div>

                    <div class="dato">
                        <span>Correo electrónico</span>
                        <strong><?= $correo ?></strong>
                    </div>
                </div>
            </section>

            <section class="panel seccion-oculta" id="carreras">
                <h2>Mi carrera y comunidad</h2>
                <p>
                    Tu formación técnica es parte de tu historia profesional.
                </p>
                <p><strong><?= $carrera ?></strong></p>
            </section>

        </div>
    </div>

    <footer>
        VillaLink · Plataforma de Seguimiento de Graduados
    </footer>

</main>
</div>
</body>
</html>
