
<?php
require_once __DIR__ . '/conexion.php';

$mensaje = '';

$consulta = $conexion->query(
    "SELECT COUNT(*) FROM administradores"
);
$existeAdmin = (int) $consulta->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existeAdmin) {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Escribe un nombre y un correo válidos.';
    } elseif (strlen($password) < 12) {
        $mensaje = 'La contraseña debe tener al menos 12 caracteres.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO administradores
                (nombre, correo, password_hash)
                VALUES (?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        try {
            $stmt->execute([$nombre, $correo, $hash]);
            $mensaje = 'Administrador creado. Elimina crear_admin.php por seguridad.';
            $existeAdmin = true;
        } catch (PDOException $e) {
            $mensaje = 'No se pudo crear la cuenta. Comprueba que el correo no esté registrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear administrador | VillaLink</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0; min-height: 100vh; display: grid;
    place-items: center; padding: 20px;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #071b49, #1479c9);
}
.card {
    background: white; width: 100%; max-width: 420px;
    padding: 30px; border-radius: 18px;
    box-shadow: 0 15px 45px #00143a55;
}
h1 { color: #123d79; text-align: center; }
p { color: #52647b; line-height: 1.5; }
label { display: block; margin-top: 15px; font-weight: bold; }
input {
    width: 100%; padding: 12px; margin-top: 7px;
    border: 1px solid #ccd7e5; border-radius: 9px;
}
button {
    width: 100%; padding: 13px; margin-top: 22px;
    border: 0; border-radius: 9px; color: white;
    background: #126bc4; font-weight: bold; cursor: pointer;
}
.mensaje { padding: 12px; background: #edf5ff; border-radius: 8px; }
</style>
</head>
<body>
<div class="card">
    <h1>VillaLink</h1>
    <p>Registro inicial del administrador</p>

    <?php if ($mensaje !== ''): ?>
        <p class="mensaje">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if (!$existeAdmin): ?>
    <form method="post">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" maxlength="100" required>

        <label for="correo">Correo electrónico</label>
        <input id="correo" type="email" name="correo"
               maxlength="190" required>

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password"
               minlength="12" autocomplete="new-password" required>

        <button type="submit">Crear administrador</button>
    </form>
    <?php else: ?>
        <p>El registro inicial está cerrado. Si necesitas recuperar el acceso, utiliza un procedimiento administrativo seguro.</p>
    <?php endif; ?>
</div>
</body>
</html>
