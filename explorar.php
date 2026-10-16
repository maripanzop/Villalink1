
<?php
require_once __DIR__ . "/conexion.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verificar que el egresado haya iniciado sesión.
$idEgresado = (int) ($_SESSION["egresado_id"] ?? 0);

if ($idEgresado <= 0) {
    header("Location: index.php");
    exit;
}

// Obtener el nombre del egresado que inició sesión.
$consultaUsuario = $conexion->prepare(
    "SELECT nombre, apellidos
     FROM egresados
     WHERE id = ?
     LIMIT 1"
);
$consultaUsuario->execute([$idEgresado]);
$usuarioActual = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuarioActual) {
    header("Location: index.php");
    exit;
}

$nombreCompleto = trim(
    $usuarioActual["nombre"] . " " . $usuarioActual["apellidos"]
);

// Buscar egresados por nombre, apellidos o carrera técnica.
$busqueda = trim($_GET["buscar"] ?? "");

$sql = "SELECT id, nombre, apellidos, carrera_tecnica
        FROM egresados
        WHERE id != ?";

$parametros = [$idEgresado];

if ($busqueda !== "") {
    $sql .= " AND (
        nombre LIKE ?
        OR apellidos LIKE ?
        OR carrera_tecnica LIKE ?
    )";

    $termino = "%" . $busqueda . "%";

    $parametros[] = $termino;
    $parametros[] = $termino;
    $parametros[] = $termino;
}

$sql .= " ORDER BY nombre ASC, apellidos ASC";

$consultaEgresados = $conexion->prepare($sql);
$consultaEgresados->execute($parametros);
$egresados = $consultaEgresados->fetchAll(PDO::FETCH_ASSOC);

function escapar($texto) {
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        "UTF-8"
    );
}

$paginaActual = basename($_SERVER["PHP_SELF"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VillaLink | Explorar</title>

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

        .contenido-explorar {
            margin-left: 228px;
            padding: 28px;
            min-width: 0;
        }

        .titulo-pagina {
            margin: 0 0 8px;
            color: var(--azul);
            font-size: 29px;
        }

        .descripcion {
            margin: 0 0 24px;
            color: var(--gris);
            line-height: 1.7;
            font-size: 14px;
        }

        .portada {
            padding: 28px;
            margin-bottom: 22px;
            border-radius: 15px;
            background: linear-gradient(115deg, #9bc8e8, #718bbd);
            color: white;
        }

        .portada h2 {
            margin: 0 0 10px;
            font-size: 21px;
        }

        .portada p {
            margin: 0;
            line-height: 1.7;
            font-size: 14px;
        }

        .buscador-panel {
            padding: 22px;
            margin-bottom: 22px;
            background: white;
            border: 1px solid var(--borde);
            border-radius: 14px;
        }

        .buscador-panel h2 {
            margin: 0 0 15px;
            color: var(--azul);
            font-size: 17px;
        }

        .form-busqueda {
            display: flex;
            gap: 10px;
        }

        .form-busqueda input {
            flex: 1;
            min-width: 0;
            padding: 12px 14px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            outline: none;
        }

        .form-busqueda input:focus {
            border-color: var(--azul);
        }

        .boton {
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            background: var(--azul);
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .boton:hover {
            background: var(--azul-oscuro);
        }

        .encabezado-lista {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 26px 0 16px;
        }

        .encabezado-lista h2 {
            margin: 0;
            color: var(--azul);
            font-size: 19px;
        }

        .contador {
            color: var(--gris);
            font-size: 13px;
        }

        .lista-egresados {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .tarjeta-egresado {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: white;
            border: 1px solid var(--borde);
            border-radius: 13px;
            transition: transform .2s, box-shadow .2s;
        }

        .tarjeta-egresado:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(82, 111, 167, .12);
        }

        .avatar-egresado {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 52px;
            height: 52px;
            border-radius: 13px;
            background: var(--azul-claro);
            color: var(--azul-oscuro);
            font-size: 21px;
            font-weight: 800;
        }

        .datos-egresado {
            min-width: 0;
        }

        .datos-egresado h3 {
            margin: 0 0 7px;
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .datos-egresado p {
            margin: 0 0 8px;
            color: var(--gris);
            font-size: 13px;
        }

        .etiqueta-carrera {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            background: var(--azul-claro);
            color: var(--azul-oscuro);
            font-size: 11px;
        }

        .sin-resultados {
            padding: 35px 20px;
            text-align: center;
            background: white;
            border: 1px solid var(--borde);
            border-radius: 13px;
            grid-column: 1 / -1;
        }

        .sin-resultados h3 {
            margin-top: 0;
            color: var(--azul);
        }

        .sin-resultados p {
            color: var(--gris);
            font-size: 14px;
        }

        .limpiar {
            display: inline-block;
            margin-top: 12px;
            color: var(--azul-oscuro);
            font-size: 13px;
            font-weight: 700;
        }

        @media (max-width: 1000px) {
            .lista-egresados {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .contenido-explorar {
                margin-left: 0;
                padding: 22px 14px;
            }

            .titulo-pagina {
                font-size: 24px;
            }

            .form-busqueda {
                flex-direction: column;
            }

            .form-busqueda .boton {
                width: 100%;
            }

            .portada {
                padding: 22px;
            }
        }
    </style>
</head>

<body>

<div class="layout">


<?php require_once __DIR__ . "/barra_lateral.php"; ?>


    <main class="contenido-explorar">

        <h1 class="titulo-pagina">Explorar comunidad</h1>

        <p class="descripcion">
            Conecta con otros egresados de Villa de los Niños
            y descubre nuevas oportunidades para tu futuro.
        </p>

        <section class="portada">
            <h2>¡La comunidad VillaLink sigue creciendo! 💙</h2>
            <p>
                Encuentra a otros egresados, conoce las carreras técnicas
                que estudiaron y mantén vivo el vínculo con tu comunidad.
            </p>
        </section>

        <section class="buscador-panel">
            <h2>Buscar egresados</h2>

          
<form class="form-busqueda" method="GET" action="explorar.php">
    <input
        type="search"
        name="buscar"
        placeholder="Escribe un nombre, apellido o carrera..."
        value="<?= escapar($busqueda) ?>"
        aria-label="Buscar egresados"
    >

    <button type="submit" class="boton">
        🔎 Buscar
    </button>
</form>

            </form>
        </section>

        <div class="encabezado-lista">
            <h2>
                <?= $busqueda !== ""
                    ? "Resultados de búsqueda"
                    : "Nuestros egresados" ?>
            </h2>

            <span class="contador">
                <?= count($egresados) ?>
                <?= count($egresados) === 1 ? "egresado" : "egresados" ?>
            </span>
        </div>

        <section class="lista-egresados">

            <?php if (count($egresados) > 0): ?>

                <?php foreach ($egresados as $egresado): ?>

                    <article class="tarjeta-egresado">

                        <div class="avatar-egresado">
                            <?= escapar(mb_strtoupper(
                                mb_substr($egresado["nombre"], 0, 1, "UTF-8"),
                                "UTF-8"
                            )) ?>
                        </div>

                        <div class="datos-egresado">
                            <h3>
                                <?= escapar(
                                    $egresado["nombre"] . " " .
                                    $egresado["apellidos"]
                                ) ?>
                            </h3>

                            <p>Egresado de Villa de los Niños</p>

                            <span class="etiqueta-carrera">
                                🎓 <?= escapar(
                                    $egresado["carrera_tecnica"]
                                    ?: "Carrera no especificada"
                                ) ?>
                            </span>
                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="sin-resultados">
                    <h3>No encontramos egresados</h3>

                    <p>
                        <?php if ($busqueda !== ""): ?>
                            Prueba con otro nombre, apellido o carrera técnica.
                        <?php else: ?>
                            Cuando se registren más egresados,
                            aparecerán aquí.
                        <?php endif; ?>
                    </p>

                    <?php if ($busqueda !== ""): ?>
                        <a href="explorar.php" class="limpiar">
                            Ver todos los egresados
                        </a>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </section>

        <footer>
            VillaLink · Comunidad de egresados de Villa de los Niños
        </footer>

    </main>

</div>

</body>
</html>
