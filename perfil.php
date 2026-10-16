<?php
session_start();

if (!isset($_SESSION["egresado_id"])) {
    header("Location: index.php");
    exit;
}

require_once "conexion.php";

$id = (int) $_SESSION["egresado_id"];
$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $carrera = trim($_POST["carrera_tecnica"] ?? "");

    if ($nombre === "" || $apellidos === "" || $correo === "" || $carrera === "") {
        $mensaje = "Completa todos los campos.";
        $tipoMensaje = "error";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Escribe un correo electrónico válido.";
        $tipoMensaje = "error";
    } else {
        $consultaCorreo = $conexion->prepare(
            "SELECT id FROM egresados WHERE correo = ? AND id != ?"
        );
        $consultaCorreo->execute([$correo, $id]);

        if ($consultaCorreo->fetch()) {
            $mensaje = "Ese correo ya está registrado en otra cuenta.";
            $tipoMensaje = "error";
        } else {
            $actualizar = $conexion->prepare(
                "UPDATE egresados
                 SET nombre = ?, apellidos = ?, correo = ?, carrera_tecnica = ?
                 WHERE id = ?"
            );
            $actualizar->execute([$nombre, $apellidos, $correo, $carrera, $id]);

            $mensaje = "¡Tu perfil se actualizó correctamente!";
            $tipoMensaje = "exito";
        }
    }
}

$consulta = $conexion->prepare(
    "SELECT nombre, apellidos, correo, carrera_tecnica, matricula
     FROM egresados
     WHERE id = ?"
);
$consulta->execute([$id]);
$egresado = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$egresado) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

function e($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$nombreCompleto = trim($egresado["nombre"] . " " . $egresado["apellidos"]);
$inicial = function_exists("mb_substr")
    ? mb_strtoupper(mb_substr($egresado["nombre"], 0, 1, "UTF-8"), "UTF-8")
    : strtoupper(substr($egresado["nombre"], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VillaLink | Mi perfil</title>
    
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

        * { box-sizing: border-box; }

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

        a { text-decoration: none; color: inherit; }
        button, input { font: inherit; }

        .layout { min-height: 100vh; }

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

        .usuario strong { display: block; font-size: 12px; }
        .usuario small { display: block; margin-top: 5px; font-size: 11px; opacity: .9; }

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

        .menu a:hover, .menu a.activo { background: rgba(255,255,255,.20); }
        .menu .salir { margin-top: 20px; background: rgba(45,64,103,.20); }

        .contenido {
            margin-left: 228px;
            min-width: 0;
            padding: 26px 28px 40px;
        }

        .titulo-principal {
            margin: 0 0 8px;
            color: var(--azul);
            font-size: 29px;
        }

        .descripcion-principal {
            margin: 0 0 26px;
            color: var(--gris);
            font-size: 14px;
            line-height: 1.6;
        }

        .panel {
            padding: 25px;
            background: var(--blanco);
            border: 1px solid var(--borde);
            border-radius: 14px;
        }

        .perfil-encabezado {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 22px;
            margin-bottom: 22px;
            border-bottom: 1px solid var(--borde);
        }

        .perfil-avatar {
            flex-shrink: 0;
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            border-radius: 14px;
            background: var(--azul-claro);
            color: var(--azul-oscuro);
            font-size: 27px;
            font-weight: 800;
        }

        .perfil-encabezado h2 {
            margin: 0 0 6px;
            color: var(--azul);
            font-size: 19px;
            overflow-wrap: anywhere;
        }

        .perfil-encabezado p {
            margin: 0;
            color: var(--gris);
            font-size: 13px;
        }

        .encabezado-seccion {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 17px;
        }

        .encabezado-seccion h2 {
            margin: 0;
            color: var(--azul);
            font-size: 16px;
        }

        .etiqueta {
            display: inline-block;
            padding: 5px 8px;
            background: #e3f1fc;
            color: #526fa7;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
        }

        .formulario {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .campo label {
            color: #52627a;
            font-size: 12px;
            font-weight: 700;
        }

        .campo input {
            width: 100%;
            min-width: 0;
            padding: 12px 13px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            background: #fff;
            color: var(--texto);
            outline: none;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s;
        }

        .campo input:focus {
            border-color: var(--azul);
            box-shadow: 0 0 0 3px rgba(113,139,189,.14);
        }

        .campo input:disabled {
            background: #f5f9fd;
            color: #65738a;
            cursor: not-allowed;
        }

        .nota {
            color: var(--gris);
            font-size: 11px;
            line-height: 1.4;
        }

        .acciones {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        .boton-principal, .boton-secundario {
            display: inline-block;
            padding: 11px 15px;
            border: 0;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        .boton-principal {
            background: var(--azul);
            color: white;
        }

        .boton-principal:hover {
            background: var(--azul-oscuro);
            transform: translateY(-1px);
        }

        .boton-secundario {
            background: #edf3fa;
            color: #526fa7;
        }

        .boton-secundario:hover { background: #dce9f6; }
        .oculto { display: none !important; }

        .mensaje {
            padding: 13px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .mensaje.exito { background: #e6f7ec; color: #236b40; border: 1px solid #c8ecd4; }
        .mensaje.error { background: #fff0f0; color: #9b2c2c; border: 1px solid #f4d0d0; }

        .ayuda {
            margin-top: 20px;
            padding: 15px;
            border-left: 4px solid #9bc8e8;
            border-radius: 7px;
            background: #f5f9fd;
            color: var(--gris);
            font-size: 12px;
            line-height: 1.7;
        }

        footer {
            padding: 25px 0 0;
            color: var(--gris);
            text-align: center;
            font-size: 11px;
        }

        @media (max-width: 760px) {
            .sidebar {
                position: relative;
                width: 100%;
                padding: 14px;
            }

            .logo { margin: 0 0 14px 5px; }
            .usuario { margin-bottom: 14px; }

            .menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .menu a { padding: 11px 9px; }
            .menu .salir { margin-top: 0; }

            .contenido {
                margin-left: 0;
                padding: 22px 14px;
            }

            .titulo-principal { font-size: 23px; }
            .formulario { grid-template-columns: 1fr; }
            .panel { padding: 18px; }
        }

        @media (max-width: 420px) {
            .perfil-encabezado { align-items: flex-start; }
            .perfil-avatar { width: 52px; height: 52px; }
        }
    </style>
</head>
<body>
<div class="layout">
    
<?php require_once __DIR__ . "/barra_lateral.php"; ?>


    <main class="contenido">
        <h1 class="titulo-principal">Mi perfil</h1>
        <p class="descripcion-principal">
            Consulta y actualiza la información de tu cuenta de VillaLink.
        </p>

        <section class="panel">
            <?php if ($mensaje !== ""): ?>
                <div class="mensaje <?= e($tipoMensaje) ?>" role="status">
                    <?= e($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="perfil-encabezado">
                <div class="perfil-avatar"><?= e($inicial) ?></div>
                <div>
                    <h2><?= e($nombreCompleto) ?></h2>
                    <p>Información personal de tu cuenta</p>
                </div>
            </div>

            <div class="encabezado-seccion">
                <h2>Datos personales</h2>
                <span class="etiqueta" id="estadoEdicion">Solo lectura</span>
            </div>

            <form method="POST" id="formPerfil">
                <div class="formulario">
                    <div class="campo">
                        <label for="nombre">Nombre(s)</label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?= e($egresado["nombre"]) ?>"
                               maxlength="100" autocomplete="given-name" required disabled>
                    </div>

                    <div class="campo">
                        <label for="apellidos">Apellidos</label>
                        <input type="text" id="apellidos" name="apellidos"
                               value="<?= e($egresado["apellidos"]) ?>"
                               maxlength="150" autocomplete="family-name" required disabled>
                    </div>

                    <div class="campo">
                        <label for="correo">Correo electrónico</label>
                        <input type="email" id="correo" name="correo"
                               value="<?= e($egresado["correo"]) ?>"
                               maxlength="254" autocomplete="email" required disabled>
                    </div>

                    <div class="campo">
                        <label for="carrera_tecnica">Carrera técnica</label>
                        <input type="text" id="carrera_tecnica" name="carrera_tecnica"
                               value="<?= e($egresado["carrera_tecnica"]) ?>"
                               maxlength="150" required disabled>
                    </div>

                    <div class="campo">
                        <label for="matricula">Matrícula</label>
                        <input type="text" id="matricula"
                               value="<?= e($egresado["matricula"]) ?>" disabled>
                        <span class="nota">La matrícula es un dato de identificación y no se modifica aquí.</span>
                    </div>
                </div>

                <div class="acciones">
                    <button type="button" class="boton-principal" id="btnEditar">
                        ✎ Editar perfil
                    </button>
                    <button type="submit" class="boton-principal oculto" id="btnGuardar">
                        ✓ Guardar cambios
                    </button>
                    <button type="button" class="boton-secundario oculto" id="btnCancelar">
                        Cancelar
                    </button>
                </div>
            </form>

            <div class="ayuda">
                <strong>Importante:</strong> los cambios se guardan en tu cuenta personal.
                La matrícula permanece sin cambios para conservar la identificación de tu registro.
            </div>
        </section>

        <footer>VillaLink · Plataforma de Seguimiento de Graduados</footer>
    </main>
</div>

<script>
    const formulario = document.getElementById("formPerfil");
    const campos = formulario.querySelectorAll(
        'input[name="nombre"], input[name="apellidos"], input[name="correo"], input[name="carrera_tecnica"]'
    );
    const btnEditar = document.getElementById("btnEditar");
    const btnGuardar = document.getElementById("btnGuardar");
    const btnCancelar = document.getElementById("btnCancelar");
    const estadoEdicion = document.getElementById("estadoEdicion");
    const valoresOriginales = {};

    campos.forEach(campo => {
        valoresOriginales[campo.name] = campo.value;
    });

    function cambiarModoEdicion(editar) {
        campos.forEach(campo => campo.disabled = !editar);
        btnEditar.classList.toggle("oculto", editar);
        btnGuardar.classList.toggle("oculto", !editar);
        btnCancelar.classList.toggle("oculto", !editar);
        estadoEdicion.textContent = editar ? "Editando" : "Solo lectura";
    }

    btnEditar.addEventListener("click", () => {
        cambiarModoEdicion(true);
        document.getElementById("nombre").focus();
    });

    btnCancelar.addEventListener("click", () => {
        campos.forEach(campo => campo.value = valoresOriginales[campo.name]);
        cambiarModoEdicion(false);
    });
</script>
</body>
</html>

