
<?php
session_start();
require_once "conexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $carrera = trim($_POST["carrera_tecnica"] ?? "");
    $matricula = trim($_POST["matricula"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $nombre === "" || $apellidos === "" ||
        $correo === "" || $carrera === "" ||
        $matricula === "" || $password === ""
    ) {
        $error = "Completa todos los campos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Escribe un correo electrónico válido.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO egresados
                (nombre, apellidos, correo, carrera_tecnica,
                 matricula, password_hash)
                VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                $nombre,
                $apellidos,
                $correo,
                $carrera,
                $matricula,
                $hash
            ]);

            $_SESSION["egresado_id"] = $conexion->lastInsertId();
            $_SESSION["egresado_nombre"] = $nombre;

             header("Location: index.php?registro=exitoso");
    exit;

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $error = "El correo o la matrícula ya están registrados.";
            } else {
                $error = "No se pudo crear la cuenta. Intenta nuevamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | VillaLink</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #dceff9, #c8e6f4);
            color: #33435b;
        }

        .contenedor {
            width: 100%;
            max-width: 500px;
            padding: 32px;
            background: white;
            border: 1px solid #a9d1ff;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(76, 126, 165, .12);
        }

        .encabezado {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            margin: auto auto 10px;
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
            font font-family: matcha ;
        }

        .encabezado p {
            color: #6689c8;
            font-size: 13px;
        }

        .formulario {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 12px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
            font-size: 13px;
            color: #64738a;
        }

        .completo {
            grid-column: 1 / -1;
        }

        input, select {
            width: 100%;
            height: 45px;
            padding: 0 12px;
            border: 1px solid #a9d1ff;
            border-radius: 8px;
            background: #fbfcfe;
            font-size: 14px;
            outline: none;
        }

        input:focus, select:focus {
            border-color: #6689c8;
        }

        button {
            grid-column: 1 / -1;
            height: 48px;
            margin-top: 5px;
            border: none;
            border-radius: 8px;
            background: #8eb9df;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #6689c8;
        }

        .error {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            background: #fff0f2;
            color: #963c4b;
            font-size: 13px;
        }

        .enlace {
            text-align: center;
            margin: 22px 0 0;
            font-size: 13px;
        }

        a {
            color: #466ba9;
            font-weight: bold;
            text-decoration: none;
        }

        @media (max-width: 450px) {
            .contenedor {
                padding: 23px 18px;
            }

            .formulario {
                grid-template-columns: 1fr;
            }

            .completo, button {
                grid-column: 1;
            }
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
        <header class="encabezado">
            <div class="logo">V</div>
            <h1>VILLALINK</h1>
            <p>Únete a nuestra comunidad de graduados</p>
        </header>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="POST" action="registro.php">
            <label class="campo">
                Nombre(s)
                <input type="text" name="nombre" required
                       maxlength="80" autocomplete="given-name">
            </label>

            <label class="campo">
                Apellidos
                <input type="text" name="apellidos" required
                       maxlength="120" autocomplete="family-name">
            </label>

            <label class="campo completo">
                Correo electrónico
                <input type="email" name="correo" required
                       maxlength="190" autocomplete="email">
            </label>

            <label class="campo completo">
                Carrera técnica
                <select name="carrera_tecnica" required>
                    <option value="">Selecciona tu carrera</option>
                    <option value="Informática">Informática</option>
                    <option value="Programación">Manufactura</option>
                    <option value="Contabilidad">Contabilidad</option>
                    <option value="Administración">Gastronomia</option>
                    <option value="Electrónica">Electrónica</option>
                    <option value="Otra">Enfermeria</option>
                </select>
            </label>

            <label class="campo completo">
                Matrícula
                <input type="text" name="matricula" required
                       maxlength="40" autocomplete="off">
            </label>

            <label class="campo completo">
                Contraseña
                <input type="password" name="password" required
                       minlength="8" autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres">
            </label>

            <button type="submit">Crear mi cuenta</button>
        </form>

        <p class="enlace">
            ¿Ya tienes cuenta?
            <a href="index.php">Inicia sesión</a>
        </p>
    </main>
</body>
</html>
