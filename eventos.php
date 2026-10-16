
<?php
session_start();

if (!isset($_SESSION["egresado_id"])) {
    header("Location: index.php");
    exit;
}

require_once "conexion.php";


/* Protección contra envíos falsificados */
if (empty($_SESSION["csrf_votacion"])) {
    $_SESSION["csrf_votacion"] = bin2hex(random_bytes(32));
}

$mensajeVoto = "";
$tipoMensaje = "";

/* Procesar un voto */
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["votar_foto"])) {

    $token = $_POST["csrf_token"] ?? "";
    $concursoId = filter_input(
        INPUT_POST, "concurso_id", FILTER_VALIDATE_INT
    );
    $fotoId = filter_input(
        INPUT_POST, "foto_id", FILTER_VALIDATE_INT
    );

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_votacion"], $token)
    ) {
        $mensajeVoto = "No se pudo validar la solicitud. Recarga la página.";
        $tipoMensaje = "error";
    } elseif (!$concursoId || !$fotoId) {
        $mensajeVoto = "La competencia o la fotografía no son válidas.";
        $tipoMensaje = "error";
    } else {
        try {
            $conexion->beginTransaction();

            /* Verificar que la competencia siga abierta
               y que la foto pertenezca a ella. */
            $validar = $conexion->prepare(
                "SELECT c.id
                 FROM concursos_fotografia c
                 INNER JOIN fotos_concurso f
                    ON f.concurso_id = c.id
                 WHERE c.id = ?
                   AND f.id = ?
                   AND NOW() >= c.fecha_inicio
                   AND NOW() < c.fecha_cierre
                 FOR UPDATE"
            );
            $validar->execute([$concursoId, $fotoId]);

            if (!$validar->fetchColumn()) {
                $conexion->rollBack();
                $mensajeVoto =
                    "La competencia está cerrada o la foto no existe.";
                $tipoMensaje = "error";
            } else {
                $insertar = $conexion->prepare(
                    "INSERT INTO votos_concurso
                     (concurso_id, foto_id, egresado_id)
                     VALUES (?, ?, ?)"
                );

                $insertar->execute([
                    $concursoId,
                    $fotoId,
                    (int)$_SESSION["egresado_id"]
                ]);

                $conexion->commit();

                /* Evitar volver a enviar el mismo formulario */
                header("Location: eventos.php?voto=ok");
                exit;
            }
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            if ($e->getCode() === "23000") {
                $mensajeVoto =
                    "Ya votaste en esta competencia. ¡Gracias por participar!";
            } else {
                error_log("Error al registrar voto: " . $e->getMessage());
                $mensajeVoto =
                    "Ocurrió un error al registrar tu voto. Inténtalo más tarde.";
            }
            $tipoMensaje = "error";
        }
    }
}

/* Mensaje después de registrar el voto */
if (($_GET["voto"] ?? "") === "ok") {
    $mensajeVoto = "¡Tu voto se registró correctamente!";
    $tipoMensaje = "exito";
}

/* Obtener competencias visibles */
$consultaConcursos = $conexion->query(
    "SELECT id, titulo, descripcion, fecha_inicio, fecha_cierre
     FROM concursos_fotografia
     WHERE fecha_cierre > NOW()
     ORDER BY fecha_cierre ASC"
);
$concursos = $consultaConcursos->fetchAll(PDO::FETCH_ASSOC);

/* Obtener fotos y el número de votos */
$consultaFotos = $conexion->prepare(
    "SELECT f.id, f.concurso_id, f.titulo, f.ruta_foto,
            COUNT(v.id) AS total_votos
     FROM fotos_concurso f
     LEFT JOIN votos_concurso v ON v.foto_id = f.id
     WHERE f.concurso_id = ?
     GROUP BY f.id, f.concurso_id, f.titulo, f.ruta_foto
     ORDER BY f.id ASC"
);


function escapar($texto) {
    return htmlspecialchars(
        (string)($texto ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Obtener los datos del egresado
$idEgresado = (int)$_SESSION["egresado_id"];

$consultaNombre = $conexion->prepare(
    "SELECT nombre, apellidos
     FROM egresados
     WHERE id = ?"
);
$consultaNombre->execute([$idEgresado]);
$egresado = $consultaNombre->fetch(PDO::FETCH_ASSOC);

$nombreCompleto = $egresado
    ? trim($egresado["nombre"] . " " . $egresado["apellidos"])
    : "Egresado VillaLink";

// Obtener los eventos registrados en la base de datos
$consultaEventos = $conexion->query(
    "SELECT id, titulo, descripcion, fecha, hora, lugar, imagen
     FROM eventos
     WHERE fecha >= CURDATE()
     ORDER BY fecha ASC, hora ASC"
);

$eventos = $consultaEventos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos | VillaLink</title>

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
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    background: var(--fondo);
    color: var(--texto);
}

/* BARRA LATERAL */

/* BARRA LATERAL VILLALINK */

.sidebar {
    width: 264px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding: 20px 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--azul);
    color: var(--blanco);
    overflow-y: auto;
}

.logo {
    width: 100%;
    text-align: center;
    font-size: 25px;
    font-weight: 800;
    margin-bottom: 22px;
    color: var(--blanco);
}

.subtitulo {
    display: none;
}

.avatar {
    width: 66px;
    height: 66px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--blanco);
    border-radius: 12px;
    overflow: hidden;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 5px;
}

.usuario {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 12px 10px;
    margin-bottom: 18px;
    background: transparent;
    border-radius: 10px;
    overflow: hidden;
}

.datos-usuario {
    width: 100%;
    min-width: 0;
    text-align: center;
}

.usuario strong {
    display: block;
    width: 100%;
    color: var(--blanco);
    font-size: 13px;
    line-height: 1.5;
    text-align: center;
    overflow-wrap: anywhere;
}

.usuario small {
    display: none;
}

.menu {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.menu a {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    width: 100%;
    min-height: 43px;
    padding: 12px;
    margin: 0;
    border-radius: 9px;
    color: var(--blanco);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.menu a:hover,
.menu a.activo {
    background: rgba(255, 255, 255, 0.20);
    box-shadow: none;
}

.menu a:last-child {
    margin-top: auto;
    background: var(--azul-oscuro);
}

.contenido {
    margin-left: 264px;
    padding: 30px;
}

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .menu {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .contenido {
        margin-left: 0;
        padding: 22px;
    }
}


/* AJUSTE DEL CONTENIDO */
.contenido {
    margin-left: 264px;
    padding: 30px;
}

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .logo {
        text-align: left;
    }

    .menu {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 5px;
    }

    .contenido {
        margin-left: 0;
        padding: 22px;
    }
}


/* CONTENIDO PRINCIPAL */
.contenido {
    margin-left: 250px;
    padding: 35px;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.encabezado h1 {
    color: var(--azul-oscuro);
    font-size: 30px;
}

.encabezado p {
    color: var(--gris);
    margin-top: 7px;
}

.contador {
    background: var(--blanco);
    padding: 15px 20px;
    border: 1px solid var(--borde);
    border-radius: 12px;
    color: var(--azul-oscuro);
    box-shadow: 0 5px 18px rgba(82, 111, 167, 0.08);
}

/* BANNER */
.introduccion {
    padding: 27px;
    border-radius: 16px;
    margin-bottom: 28px;
    color: var(--texto);
    background: linear-gradient(
        120deg,
        var(--azul-claro),
        var(--blanco)
    );
    border: 1px solid var(--borde);
    box-shadow: 0 8px 22px rgba(82, 111, 167, 0.08);
}

.introduccion h2 {
    color: var(--azul-oscuro);
    margin-bottom: 9px;
}

.introduccion p {
    line-height: 1.7;
    color: var(--texto);
}

/* TARJETAS DE EVENTOS */
.eventos-grid {
    display: grid;
    grid-template-columns: repeat(
        auto-fit,
        minmax(260px, 1fr)
    );
    gap: 22px;
}

.evento {
    background: var(--blanco);
    border-radius: 15px;
    padding: 23px;
    border: 1px solid var(--borde);
    box-shadow: 0 5px 20px rgba(82, 111, 167, 0.07);
    transition: transform 0.2s, box-shadow 0.2s;
}

.evento:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(82, 111, 167, 0.14);
}

.evento-icono {
    width: 50px;
    height: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: var(--azul-claro);
    color: var(--azul-oscuro);
    border-radius: 13px;
    font-size: 25px;
    margin-bottom: 17px;
}

.evento h3 {
    color: var(--azul-oscuro);
    font-size: 20px;
    margin-bottom: 12px;
}

.descripcion {
    color: var(--gris);
    font-size: 14px;
    line-height: 1.7;
    margin-bottom: 18px;
    white-space: pre-line;
}

.dato {
    padding: 11px 0;
    border-top: 1px solid var(--borde);
    color: var(--texto);
    font-size: 14px;
}

.dato strong {
    color: var(--azul-oscuro);
}

/* CUANDO NO HAY EVENTOS */
.vacio {
    background: var(--blanco);
    text-align: center;
    padding: 50px 20px;
    border-radius: 15px;
    border: 1px solid var(--borde);
    grid-column: 1 / -1;
}

.vacio .icono {
    font-size: 45px;
    margin-bottom: 15px;
}

.vacio h3 {
    color: var(--azul-oscuro);
    margin-bottom: 10px;
}

.vacio p {
    color: var(--gris);
    line-height: 1.6;
}

/* PANTALLAS PEQUEÑAS */
@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .menu {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 5px;
    }

    .menu a {
        margin: 0;
    }

    .contenido {
        margin-left: 0;
        padding: 22px;
    }

    .encabezado h1 {
        font-size: 25px;
    }
}

.competencias {
    margin-top: 35px;
}

.titulo-competencias {
    margin-bottom: 22px;
}

.titulo-competencias h2 {
    color: var(--azul-oscuro);
    font-size: 26px;
    margin-bottom: 8px;
}

.titulo-competencias p,
.cierre {
    color: var(--gris);
    line-height: 1.6;
}

.concurso {
    background: var(--blanco);
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid var(--borde);
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(82, 111, 167, 0.07);
}

.concurso h3 {
    color: var(--azul-oscuro);
    margin-bottom: 10px;
}

.cierre {
    font-size: 13px;
    margin: 14px 0;
}

.fotos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 18px;
    margin-top: 20px;
}

.foto-card {
    min-width: 0;
    overflow: hidden;
    padding: 12px;
    border: 1px solid var(--borde);
    border-radius: 12px;
    background: #fff;
}

.foto-card img {
    display: block;
    width: 100%;
    height: 210px;
    object-fit: cover;
    border-radius: 9px;
    margin-bottom: 12px;
}

.foto-card h4 {
    color: var(--texto);
    margin-bottom: 8px;
    overflow-wrap: anywhere;
}

.votos,
.estado-voto {
    display: block;
    margin: 10px 0;
    font-size: 14px;
    color: var(--azul-oscuro);
}

.foto-card button {
    width: 100%;
    padding: 11px;
    border: none;
    border-radius: 8px;
    background: var(--azul-oscuro);
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.foto-card button:hover {
    background: var(--azul);
}

.mensaje-voto {
    padding: 13px 16px;
    border-radius: 9px;
    margin: 15px 0;
    background: var(--blanco);
    border: 1px solid var(--borde);
}

.mensaje-voto.exito {
    color: #246b43;
}

.mensaje-voto.error {
    color: #a12c37;
}

    </style>
</head>

<body>


<?php require_once __DIR__ . "/barra_lateral.php"; ?>


<main class="contenido">

    <header class="encabezado">
        <div>
            <h1>Eventos VillaLink</h1>
            <p>Conecta, participa y mantente cerca de tu comunidad.</p>
        </div>

        <div class="contador">
            📅 Eventos disponibles: <strong><?= count($eventos) ?></strong>
        </div>
    </header>

    <section class="introduccion">
        <h2>¡Sigue formando parte de VillaLink!</h2>
        <p>
            Descubre reuniones, talleres, conferencias y actividades
            para convivir con otros egresados y continuar desarrollándote
            profesionalmente.
        </p>
    </section>

    <section class="eventos-grid">

        <?php if (count($eventos) > 0): ?>

            <?php foreach ($eventos as $evento): ?>
                <article class="evento">

                    <div class="evento-icono">📅</div>

                    <h3><?= escapar($evento["titulo"]) ?></h3>

                    <p class="descripcion">
                        <?= escapar($evento["descripcion"] ?: "Próximamente habrá más información sobre este evento.") ?>
                    </p>

                    <div class="dato">
                        <strong>Fecha:</strong>
                        <?php
                        $fecha = $evento["fecha"]
                            ? date("d/m/Y", strtotime($evento["fecha"]))
                            : "Por confirmar";
                        echo escapar($fecha);
                        ?>
                    </div>

                    <div class="dato">
                        <strong>Hora:</strong>
                        <?= escapar($evento["hora"] ?: "Por confirmar") ?>
                    </div>

                    <div class="dato">
                        <strong>Lugar:</strong>
                        <?= escapar($evento["lugar"] ?: "Por confirmar") ?>
                    </div>

                </article>
            <?php endforeach; ?>

        <?php else: ?>

            <div class="vacio">
                <div class="icono">🗓️</div>
                <h3>Por el momento no hay eventos próximos</h3>
                <p>
                    Cuando se publiquen nuevos eventos, aparecerán aquí
                    para que puedas consultarlos.
                </p>
            </div>

        <?php endif; ?>

    </section>


    
<section class="competencias">
    <div class="titulo-competencias">
        <h2>📸 Competencias de fotografía</h2>
        <p>
            Descubre las fotografías de nuestra comunidad
            y vota por tu favorita.
        </p>
    </div>

    <?php if ($mensajeVoto !== ""): ?>
        <div class="mensaje-voto <?= escapar($tipoMensaje) ?>">
            <?= escapar($mensajeVoto) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($concursos)): ?>
        <div class="vacio">
            <div class="icono">📷</div>
            <h3>Próximamente habrá competencias</h3>
            <p>Cuando se publique una nueva competencia, aparecerá aquí.</p>
        </div>
    <?php else: ?>

        <?php foreach ($concursos as $concurso): ?>
            <?php
            $consultaFotos->execute([(int)$concurso["id"]]);
            $fotos = $consultaFotos->fetchAll(PDO::FETCH_ASSOC);

            $consultaMiVoto = $conexion->prepare(
                "SELECT foto_id
                 FROM votos_concurso
                 WHERE concurso_id = ? AND egresado_id = ?"
            );
            $consultaMiVoto->execute([
                (int)$concurso["id"],
                (int)$_SESSION["egresado_id"]
            ]);
            $miVoto = $consultaMiVoto->fetchColumn();

            $cerrada = strtotime($concurso["fecha_cierre"]) <= time();
            ?>

            <article class="concurso">
                <h3><?= escapar($concurso["titulo"]) ?></h3>

                <p class="descripcion">
                    <?= escapar(
                        $concurso["descripcion"] ??
                        "¡Conoce las fotografías participantes!"
                    ) ?>
                </p>

                <p class="cierre">
                    <strong>Cierre de votaciones:</strong>
                    <?= escapar(
                        date(
                            "d/m/Y H:i",
                            strtotime($concurso["fecha_cierre"])
                        )
                    ) ?>
                </p>

                <div class="fotos-grid">
                    <?php foreach ($fotos as $foto): ?>
                        <div class="foto-card">
                            <img
                                src="<?= escapar($foto["ruta_foto"]) ?>"
                                alt="<?= escapar($foto["titulo"]) ?>"
                                loading="lazy"
                            >

                            <h4><?= escapar($foto["titulo"]) ?></h4>

                            <p class="votos">
                                🗳️ <?= (int)$foto["total_votos"] ?>
                                <?= (int)$foto["total_votos"] === 1
                                    ? "voto"
                                    : "votos" ?>
                            </p>

                            <?php if ($cerrada): ?>
                                <span class="estado-voto">Votación cerrada</span>
                            <?php elseif ($miVoto !== false): ?>
                                <?php if ((int)$miVoto === (int)$foto["id"]): ?>
                                    <span class="estado-voto">✓ Tu voto</span>
                                <?php else: ?>
                                    <span class="estado-voto">Ya participaste</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" action="eventos.php">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= escapar($_SESSION["csrf_votacion"]) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="concurso_id"
                                        value="<?= (int)$concurso["id"] ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="foto_id"
                                        value="<?= (int)$foto["id"] ?>"
                                    >

                                    <button type="submit" name="votar_foto">
                                        Votar por esta foto
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($fotos)): ?>
                        <p>Aún no se han publicado fotografías.</p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

    <?php endif; ?>
</section>

</main>

</body>
</html>
