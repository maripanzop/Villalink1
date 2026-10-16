
<?php
require_once __DIR__ . '/conexion.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Si ya inició sesión como administrador, entrar al panel */
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin_eventos.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Correo o contraseña incorrectos.';
    } else {
        $stmt = $conexion->prepare(
            'SELECT id, nombre, correo, password_hash
             FROM administradores
             WHERE correo = ?
             LIMIT 1'
        );
        $stmt->execute([$correo]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];

            header('Location: admin_eventos.php');
            exit;
        }

        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso administrador | VillaLink</title>
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 20px;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #071b49, #147bc1);
}

.login {
    background: #fff;
    width: 100%;
    max-width: 420px;
    padding: 35px;
    border-radius: 20px;
    box-shadow: 0 18px 50px #00143a55;
}

.logo {
    text-align: center;
    font-size: 32px;
    font-weight: bold;
    color: #126bc4;
    margin-bottom: 8px;
}

h2 {
    text-align: center;
    color: #173b70;
    margin-bottom: 8px;
}

.subtitulo {
    text-align: center;
    color: #718096;
    margin-bottom: 28px;
}

label {
    display: block;
    margin: 16px 0 7px;
    font-weight: bold;
    color: #30445e;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd7e6;
    border-radius: 9px;
    font-size: 15px;
}

input:focus {
    outline: 2px solid #90c8ff;
    border-color: #126bc4;
}

button {
    width: 100%;
    margin-top: 24px;
    padding: 14px;
    border: none;
    border-radius: 9px;
    background: #126bc4;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #0b529b;
}

.error {
    color: #9e2435;
    background: #ffebee;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
}

.volver {
    display: block;
    text-align: center;
    margin-top: 22px;
    color: #126bc4;
    text-decoration: none;
}
</style>
</head>
<body>
<div class="login">
    <div class="logo">VillaLink</div>
    <h2>Panel administrativo</h2>
    <p class="subtitulo">Ingresa con tu cuenta de administrador</p>

    <?php if ($error !== ''): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <label for="correo">Correo electrónico</label>
        <input
            id="correo"
            type="email"
            name="correo"
            autocomplete="username"
            maxlength="190"
            required
        >

        <label for="password">Contraseña</label>
        <input
            id="password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <button type="submit">Iniciar sesión</button>
    </form>

    <a class="volver" href="index.php">Volver al inicio</a>
</div>
</body>
</html>
