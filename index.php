
<?php
session_start();
require_once "conexion.php";

if (isset($_SESSION["egresado_id"])) {
    header("Location: plataforma.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $nombre === "" ||
        $apellidos === "" ||
        $correo === "" ||
        $password === ""
    ) {
        $error = "Completa todos los campos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Ingresa un correo electrónico válido.";
    } else {
        $sql = "SELECT id, nombre, apellidos, password_hash
                FROM egresados
                WHERE correo = ?
                LIMIT 1";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $usuario &&
            mb_strtolower(trim($usuario["nombre"]), "UTF-8") ===
                mb_strtolower($nombre, "UTF-8") &&
            mb_strtolower(trim($usuario["apellidos"]), "UTF-8") ===
                mb_strtolower($apellidos, "UTF-8") &&
            password_verify($password, $usuario["password_hash"])
        ) {
            session_regenerate_id(true);

            $_SESSION["egresado_id"] = $usuario["id"];
            $_SESSION["egresado_nombre"] = $usuario["nombre"];

            header("Location: plataforma.php");
            exit;
        } else {
            $error = "Los datos ingresados son incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | VillaLink</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #dceff9, #c8e6f4);
            color: #33435b;
        }

        .contenedor {
            width: 100%;
            max-width: 400px;
            padding: 35px 30px;
            background: white;
            border: 1px solid #a9d1ff;
            border-radius: 19px;
            box-shadow: 0 15px 40px rgba(76,126,165,.10);
        }

        header {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo {
            display: grid;
            place-items: center;
            width: 45px;
            height: 45px;
            margin: 0 auto 10px;
            border-radius: 13px;
            background: #6689c8;
            color: white;
            font-size: 25px;
            font-weight: bold;
        }

        h1 {
            margin: 0;
            color: #6689c8;
            letter-spacing: 2px;
            font-size: 30px;
        }

        header p {
            color: #6689c8;
            font-size: 13px;
        }

        .campo {
            display: block;
            margin-bottom: 17px;
            color: #64738a;
            font-size: 13px;
        }

        input {
            display: block;
            width: 100%;
            height: 47px;
            margin-top: 8px;
            padding: 0 13px;
            border: 1px solid #a9d1ff;
            border-radius: 9px;
            background: #fbfcfe;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #6689c8;
        }

        button {
            width: 100%;
            height: 49px;
            margin-top: 6px;
            border: 0;
            border-radius: 9px;
            background: #8eb9df;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #6689c8;
        }

        .error {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 8px;
            background: #fff0f2;
            color: #963c4b;
            font-size: 13px;
        }

        .enlace {
            margin-top: 23px;
            text-align: center;
            font-size: 13px;
        }

        a {
            color: #466ba9;
            font-weight: bold;
            text-decoration: none;
        }

        

.fondo-burbujas {
    position: fixed;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: -1;
    background: linear-gradient(135deg, #e2f1fc, #9bc8e8, #718bbd);
}


.burbuja {
    position: absolute;
    bottom: -100px;
    border: 2px solid rgba(255, 255, 255, .65);
    background: rgba(255, 255, 255, .2);
    border-radius: 50%;
    animation: subir 12s linear infinite;
}

.burbuja:nth-child(1) {
    left: 10%;
    width: 40px;
    height: 40px;
}

.burbuja:nth-child(2) {
    left: 30%;
    width: 65px;
    height: 65px;
    animation-delay: 3s;
}

.burbuja:nth-child(3) {
    left: 50%;
    width: 25px;
    height: 25px;
    animation-delay: 5s;
}

.burbuja:nth-child(4) {
    left: 70%;
    width: 80px;
    height: 80px;
    animation-delay: 2s;
}

.burbuja:nth-child(5) {
    left: 90%;
    width: 50px;
    height: 50px;
    animation-delay: 4s;
}

@keyframes subir {
    from {
        transform: translateY(0);
        opacity: 0;
    }
    20% {
        opacity: .8;
    }
    to {
        transform: translateY(-120vh);
        opacity: 0;
    }

}

.login-container {
    position: relative;
    z-index: 1;
}

.mensaje-exito {
    padding: 12px 15px;
    margin-bottom: 18px;
    border: 1px solid #a8d8b0;
    border-radius: 10px;
    background: #e8f8eb;
    color: #246b36;
    font-size: 14px;
    line-height: 1.5;
}


    </style>
</head>
<body>

<div class="fondo-burbujas" aria-hidden="true">
    <span class="burbuja"></span>
    <span class="burbuja"></span>
    <span class="burbuja"></span>
    <span class="burbuja"></span>
    <span class="burbuja"></span>
</div>

<main class="contenedor">
    <header>
        <div class="logo">V</div>
        <h1>VILLALINK</h1>
        <p>Bienvenido de nuevo a nuestra comunidad villa  de los niños</p>
    </header>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    
<?php if (isset($_GET["registro"]) && $_GET["registro"] === "exitoso"): ?>
    <div class="mensaje-exito">
        ¡Tu cuenta se creó correctamente!<br>
        Ahora inicia sesión con tus datos.
    </div>
<?php endif; ?>

    <form method="POST" action="index.php">
           
<div class="campo">
    <label for="nombre">Nombre(s)</label>
    <input
        type="text"
        id="nombre"
        name="nombre"
        placeholder="Ingresa tu nombre"
        autocomplete="given-name"
        required
    >
</div>

<div class="campo">
    <label for="apellidos">Apellidos</label>
    <input
        type="text"
        id="apellidos"
        name="apellidos"
        placeholder="Ingresa tus apellidos"
        autocomplete="family-name"
        required
    >
</div>

<div class="campo">
    <label for="correo">Correo electrónico</label>
    <input
        type="email"
        id="correo"
        name="correo"
        placeholder="ejemplo@correo.com"
        autocomplete="email"
        required
    >
</div>

<div class="campo">
    <label for="password">Contraseña</label>
    <input
        type="password"
        id="password"
        name="password"
        placeholder="Ingresa tu contraseña"
        autocomplete="current-password"
        required
    >
</div>


        <button type="submit">Iniciar sesión</button>
    </form>

    <p class="enlace">
        ¿Aún no tienes cuenta?
        <a href="registro.php">Regístrate aquí</a>
    </p>
</main>

</body>
</html>
