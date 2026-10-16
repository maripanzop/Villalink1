
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/conexion.php";

$idEgresado = (int)($_SESSION["egresado_id"] ?? 0);

$consultaBarra = $conexion->prepare(
    "SELECT nombre, apellidos
     FROM egresados
     WHERE id = ?
     LIMIT 1"
);

$consultaBarra->execute([$idEgresado]);
$datosBarra = $consultaBarra->fetch(PDO::FETCH_ASSOC);

$nombreCompleto = $datosBarra
    ? trim($datosBarra["nombre"] . " " . $datosBarra["apellidos"])
    : "Egresado VillaLink";

$paginaActual = basename($_SERVER["PHP_SELF"]);

if (!function_exists("vlEscapar")) {
    function vlEscapar($texto) {
        return htmlspecialchars(
            (string)$texto,
            ENT_QUOTES,
            "UTF-8"
        );
    }
}
?>

<aside class="sidebar">

    <div class="logo">VillaLink</div>

    <div class="usuario">

        <div class="avatar">
            <img
                src="logo-removebg-preview.png"
                alt="Logo de VillaLink"
            >
        </div>

        <div class="datos-usuario">
            <strong><?= vlEscapar($nombreCompleto) ?></strong>
            <small>Comunidad de egresados</small>
        </div>

    </div>

    <nav class="menu">

        <a href="plataforma.php"
           class="<?= $paginaActual === 'plataforma.php' ? 'activo' : '' ?>">
            🏠 Inicio
        </a>

        <a href="explorar.php"
           class="<?= $paginaActual === 'explorar.php' ? 'activo' : '' ?>">
            🔎 Explorar
        </a>

        <a href="empleos.php"
           class="<?= $paginaActual === 'empleos.php' ? 'activo' : '' ?>">
            💼 Empleos
        </a>

        <a href="eventos.php"
           class="<?= $paginaActual === 'eventos.php' ? 'activo' : '' ?>">
            🎉 Eventos
        </a>

        <a href="plataforma.php#encuestas">
            📋 Encuestas
        </a>

        <a href="plataforma.php#carreras">
            🎓 Universidades
        </a>

        <a href="perfil.php"
           class="<?= $paginaActual === 'perfil.php' ? 'activo' : '' ?>">
            👤 Mi perfil
        </a>


        <a href="cerrar_sesion.php" class="salir">
            ↪ Cerrar sesión
        </a>

    </nav>

</aside>
