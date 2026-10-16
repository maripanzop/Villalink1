
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/conexion.php";

// Verificar que el egresado haya iniciado sesión.
if (empty($_SESSION["egresado_id"])) {
    header("Location: index.php");
    exit;
}

$idEgresado = (int) $_SESSION["egresado_id"];


$consultaNombre = $conexion->prepare(
    "SELECT nombre, apellidos
     FROM egresados
     WHERE id = ?"
);

$consultaNombre->execute([$idEgresado]);
$datosNombre = $consultaNombre->fetch(PDO::FETCH_ASSOC);

$nombreCompleto = $datosNombre
    ? trim($datosNombre["nombre"] . " " . $datosNombre["apellidos"])
    : "Egresado VillaLink";


function escapar($valor) {
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

$errores = [];
$mensaje = "";

$situaciones = [
    "Trabaja",
    "Estudia",
    "Busca empleo",
    "Tiene negocio"
];

$datos = [
    "situacion" => "",
    "empresa" => "",
    "puesto" => "",
    "carrera_actual" => "",
    "descripcion" => ""
];

// Recuperar el seguimiento laboral existente.
$consulta = $conexion->prepare(
    "SELECT situacion, empresa, puesto, carrera_actual, descripcion
     FROM seguimiento_laboral
     WHERE egresado_id = :id"
);
$consulta->execute(["id" => $idEgresado]);

$registro = $consulta->fetch(PDO::FETCH_ASSOC);

if ($registro) {
    $datos = array_merge($datos, $registro);
}

// Token de seguridad del formulario.
if (empty($_SESSION["csrf_seguimiento"])) {
    $_SESSION["csrf_seguimiento"] = bin2hex(random_bytes(32));
}

// Procesar el formulario.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_seguimiento"], $token)
    ) {
        $errores[] = "La sesión del formulario no es válida. Recarga la página e inténtalo de nuevo.";
    }

    $situacion = $_POST["situacion"] ?? "";

    $empresa = trim($_POST["empresa"] ?? "");
    $puesto = trim($_POST["puesto"] ?? "");
    $carreraActual = trim($_POST["carrera_actual"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    if (!in_array($situacion, $situaciones, true)) {
        $errores[] = "Selecciona una situación válida.";
    }

    if (mb_strlen($empresa) > 150) {
        $errores[] = "La empresa no debe superar los 150 caracteres.";
    }

    if (mb_strlen($puesto) > 150) {
        $errores[] = "El puesto no debe superar los 150 caracteres.";
    }

    if (mb_strlen($carreraActual) > 150) {
        $errores[] = "Los estudios actuales no deben superar los 150 caracteres.";
    }

    if (mb_strlen($descripcion) > 5000) {
        $errores[] = "La descripción no debe superar los 5000 caracteres.";
    }

    if (!$errores) {
        try {
            $guardar = $conexion->prepare(
                "INSERT INTO seguimiento_laboral
                    (egresado_id, situacion, empresa, puesto,
                     carrera_actual, descripcion)
                 VALUES
                    (:id, :situacion, :empresa, :puesto,
                     :carrera_actual, :descripcion)
                 ON DUPLICATE KEY UPDATE
                    situacion = VALUES(situacion),
                    empresa = VALUES(empresa),
                    puesto = VALUES(puesto),
                    carrera_actual = VALUES(carrera_actual),
                    descripcion = VALUES(descripcion)"
            );

            $guardar->execute([
                "id" => $idEgresado,
                "situacion" => $situacion,
                "empresa" => $empresa !== "" ? $empresa : null,
                "puesto" => $puesto !== "" ? $puesto : null,
                "carrera_actual" => $carreraActual !== ""
                    ? $carreraActual
                    : null,
                "descripcion" => $descripcion !== ""
                    ? $descripcion
                    : null
            ]);

            // Evitar reenviar el formulario al actualizar la página.
            header("Location: seguimiento.php?guardado=1");
            exit;

        } catch (PDOException $e) {
            $errores[] = "No se pudo guardar la información. Inténtalo nuevamente.";
        }
    }

    // Conservar los datos escritos si hay errores.
    $datos = [
        "situacion" => $situacion,
        "empresa" => $empresa,
        "puesto" => $puesto,
        "carrera_actual" => $carreraActual,
        "descripcion" => $descripcion
    ];
}

if (isset($_GET["guardado"]) && $_GET["guardado"] === "1") {
    $mensaje = "¡Tu seguimiento laboral se guardó correctamente!";

    // Recuperar los datos actualizados.
    $consulta->execute(["id" => $idEgresado]);
    $registro = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        $datos = array_merge($datos, $registro);
    }
}

// Consultar las vacantes activas
$consultaVacantes = $conexion->prepare("
    SELECT id, titulo, empresa, ubicacion, modalidad,
           descripcion, requisitos, sueldo, fecha_limite
    FROM vacantes
    WHERE estado = 'Activa'
      AND (fecha_limite IS NULL OR fecha_limite >= CURDATE())
    ORDER BY creado_en DESC
");

$consultaVacantes->execute();
$vacantes = $consultaVacantes->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seguimiento laboral | VillaLink</title>


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
            --borde: #dce9f6;}


* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--fondo);
    color: #26364e;
    font-family: Arial, sans-serif;
}

a {
    color: inherit;
    text-decoration: none;
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
}

.contenido {
    margin-left: 228px;
    min-width: 0;
    padding: 30px 28px 40px;
}

.titulo-principal {
    margin: 0 0 10px;
    color: var(--azul);
    font-size: 29px;
}

.subtitulo {
    margin: 0 0 25px;
    color: var(--gris);
    font-size: 14px;
    line-height: 1.7;
}

.panel {
    padding: 25px;
    background: var(--blanco);
    border: 1px solid var(--borde);
    border-radius: 14px;
    margin-bottom: 20px;
}

.panel h2 {
    margin: 0 0 10px;
    color: var(--azul);
    font-size: 18px;
}

.descripcion {
    margin: 0 0 23px;
    color: var(--gris);
    font-size: 13px;
    line-height: 1.7;
}

.campo {
    margin-bottom: 20px;
}

.campo label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #26364e;
}

.campo input,
.campo select,
.campo textarea {
    display: block;
    width: 100%;
    padding: 12px 13px;
    border: 1px solid var(--borde);
    border-radius: 8px;
    background: white;
    color: #26364e;
    font: inherit;
    font-size: 13px;
}

.campo textarea {
    min-height: 120px;
    resize: vertical;
}

.campo input:focus,
.campo select:focus,
.campo textarea:focus {
    outline: 2px solid #b8d6f2;
    border-color: #718bbd;
}

.ayuda {
    display: block;
    margin-top: 7px;
    color: var(--gris);
    font-size: 11px;
}

.boton {
    display: inline-block;
    padding: 12px 17px;
    border: 0;
    border-radius: 7px;
    background: #718bbd;
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.boton:hover {
    background: var(--azul-oscuro);
}

.boton-secundario {
    background: var(--azul-claro);
    color: #526fa7;
}

.boton-secundario:hover {
    background: #c9e3f8;
}

.botones {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 25px;
}

.aviso {
    margin-bottom: 20px;
    padding: 14px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.6;
}

.exito {
    background: #e7f7ec;
    color: #21663a;
}

.error {
    background: #fff0f0;
    color: #a52828;
}

footer {
    padding-top: 20px;
    color: var(--gris);
    text-align: center;
    font-size: 11px;
}


/* Oportunidades laborales */
.seccion-vacantes {
    margin-top: 35px;
}

.encabezado-vacantes {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 22px;
}

.encabezado-vacantes h2 {
    color: #173a70;
    font-size: 24px;
    margin: 0 0 8px;
}

.encabezado-vacantes p {
    color: #64748b;
    margin: 0;
    line-height: 1.6;
}

.contador-vacantes {
    background: #e4edff;
    color: #2457a7;
    padding: 9px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.rejilla-vacantes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.tarjeta-vacante {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 5px 18px rgba(23, 58, 112, 0.07);
    transition: transform .2s, box-shadow .2s;
}

.tarjeta-vacante:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 26px rgba(23, 58, 112, 0.12);
}

.icono-vacante {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    background: #e4edff;
    border-radius: 12px;
    font-size: 24px;
    margin-bottom: 15px;
}

.modalidad-vacante {
    display: inline-block;
    background: #e6f7ec;
    color: #176438;
    padding: 5px 9px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 700;
}

.tarjeta-vacante h3 {
    color: #173a70;
    font-size: 19px;
    margin: 14px 0 10px;
}

.empresa-vacante {
    color: #2457a7;
    font-weight: 600;
}

.dato-vacante,
.descripcion-vacante {
    color: #64748b;
    font-size: 13px;
    line-height: 1.7;
}

.descripcion-vacante {
    margin: 15px 0;
}

.pie-vacante {
    display: flex;
    flex-direction: column;
    gap: 9px;
    border-top: 1px solid #edf0f5;
    padding-top: 15px;
    margin-top: 15px;
    color: #475569;
    font-size: 12px;
}

.sin-vacantes {
    text-align: center;
    background: #fff;
    padding: 35px 20px;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
}

.sin-vacantes > span {
    font-size: 35px;
}

.sin-vacantes h3 {
    color: #173a70;
}

.sin-vacantes p {
    color: #64748b;
}

@media (max-width: 760px) {
    .encabezado-vacantes {
        align-items: flex-start;
        flex-direction: column;
    }

    .rejilla-vacantes {
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
        font-size: 24px;
    }

    .panel {
        padding: 19px;
    }
}
</style>
</head>

<body>

<div class="layout">


<?php require_once __DIR__ . "/barra_lateral.php"; ?>

<main class="contenido">

    <h1 class="titulo-principal">Seguimiento laboral</h1>

    <p class="subtitulo">
        Queremos conocer tu trayectoria después de tu formación en
        Villa de los Niños. Mantén actualizada tu situación profesional.
    </p>

    <section class="panel">
        <h2>Mi situación actual</h2>

        <p class="descripcion">
            Selecciona la opción que mejor describa tu situación.
            Puedes modificar tus respuestas cuando lo necesites.
        </p>

        <?php if ($mensaje !== ""): ?>
            <div class="aviso exito" role="status">
                <?= escapar($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($errores): ?>
            <div class="aviso error" role="alert">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= escapar($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="seguimiento.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapar($_SESSION["csrf_seguimiento"]) ?>"
            >

            <div class="campo">
                <label for="situacion">¿Cuál es tu situación actual?</label>

                <select id="situacion" name="situacion" required>
                    <option value="">Selecciona una opción</option>

                    <?php foreach ($situaciones as $opcion): ?>
                        <option
                            value="<?= escapar($opcion) ?>"
                            <?= $datos["situacion"] === $opcion
                                ? "selected"
                                : "" ?>
                        >
                            <?= escapar($opcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="empresa">Empresa o negocio</label>
                <input
                    type="text"
                    id="empresa"
                    name="empresa"
                    maxlength="150"
                    value="<?= escapar($datos["empresa"]) ?>"
                    placeholder="Nombre de la empresa o negocio"
                >
            </div>

            <div class="campo">
                <label for="puesto">Puesto o actividad</label>
                <input
                    type="text"
                    id="puesto"
                    name="puesto"
                    maxlength="150"
                    value="<?= escapar($datos["puesto"]) ?>"
                    placeholder="Ej. Auxiliar administrativo"
                >
            </div>

            <div class="campo">
                <label for="carrera_actual">Estudios actuales</label>
                <input
                    type="text"
                    id="carrera_actual"
                    name="carrera_actual"
                    maxlength="150"
                    value="<?= escapar($datos["carrera_actual"]) ?>"
                    placeholder="Carrera o estudios que realizas"
                >
            </div>

            <div class="campo">
                <label for="descripcion">Descripción de tu trayectoria</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    maxlength="5000"
                    placeholder="Cuéntanos sobre tu situación profesional..."
                ><?= escapar($datos["descripcion"]) ?></textarea>
                <small class="ayuda">
                    Máximo 5000 caracteres.
                </small>
            </div>

            <div class="botones">
                <button type="submit" class="boton">
                    Guardar seguimiento
                </button>

                <a href="plataforma.php"
                   class="boton boton-secundario">
                    Volver al inicio
                </a>
            </div>

        </form>
    </section>

    <footer>
        VillaLink · Plataforma de Seguimiento de Graduados
    </footer>


<section class="seccion-vacantes" id="oportunidades">
    <div class="encabezado-vacantes">
        <div>
            <h2>Oportunidades laborales</h2>
            <p>Descubre empleos que pueden impulsar tu futuro profesional.</p>
        </div>

        <span class="contador-vacantes">
            <?= count($vacantes) ?> vacantes
        </span>
    </div>

    <?php if (empty($vacantes)): ?>
        <div class="sin-vacantes">
            <span>💼</span>
            <h3>Por ahora no hay vacantes disponibles</h3>
            <p>Regresa pronto para consultar nuevas oportunidades.</p>
        </div>
    <?php else: ?>
        <div class="rejilla-vacantes">
            <?php foreach ($vacantes as $vacante): ?>
                <article class="tarjeta-vacante">
                    <div class="icono-vacante">💼</div>

                    <span class="modalidad-vacante">
                        <?= escapar($vacante["modalidad"]) ?>
                    </span>

                    <h3><?= escapar($vacante["titulo"]) ?></h3>

                    <p class="empresa-vacante">
                        🏢 <?= escapar($vacante["empresa"]) ?>
                    </p>

                    <?php if (!empty($vacante["ubicacion"])): ?>
                        <p class="dato-vacante">
                            📍 <?= escapar($vacante["ubicacion"]) ?>
                        </p>
                    <?php endif; ?>

                    <p class="descripcion-vacante">
                        <?= escapar($vacante["descripcion"]) ?>
                    </p>

                    <?php if (!empty($vacante["requisitos"])): ?>
                        <p class="dato-vacante">
                            <strong>Requisitos:</strong>
                            <?= escapar($vacante["requisitos"]) ?>
                        </p>
                    <?php endif; ?>

                    <div class="pie-vacante">
                        <span>
                            💰 <?= escapar($vacante["sueldo"] ?: "No especificado") ?>
                        </span>

                        <?php if (!empty($vacante["fecha_limite"])): ?>
                            <span>
                                📅 Hasta
                                <?= date("d/m/Y", strtotime($vacante["fecha_limite"])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>


</main>
</div>

</body>
</html>
