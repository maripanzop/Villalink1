
<?php
require_once __DIR__ . '/conexion.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Solo permite entrar a administradores autenticados */
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

/* Protección CSRF */
if (empty($_SESSION['csrf_eventos'])) {
    $_SESSION['csrf_eventos'] = bin2hex(random_bytes(32));
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$mensaje = '';
$error = '';
$editar = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_eventos'], $token)) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo nuevamente.');
    }

    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'eliminar') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new RuntimeException('El evento seleccionado no es válido.');
            }

            $stmt = $conexion->prepare(
                'DELETE FROM eventos WHERE id = ?'
            );
            $stmt->execute([$id]);

            $mensaje = $stmt->rowCount()
                ? 'Evento eliminado correctamente.'
                : 'El evento ya no existe.';
        } elseif ($accion === 'guardar') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $fecha = $_POST['fecha'] ?? '';
            $hora = $_POST['hora'] ?? '';
            $lugar = trim($_POST['lugar'] ?? '');

            if ($titulo === '' || $descripcion === '' || $fecha === ''
                || $hora === '' || $lugar === '') {
                throw new RuntimeException('Completa todos los campos.');
            }

            if (mb_strlen($titulo) > 150 || mb_strlen($lugar) > 200) {
                throw new RuntimeException('El título o el lugar son demasiado largos.');
            }

            $fechaObj = DateTime::createFromFormat('!Y-m-d', $fecha);
            $horaObj = DateTime::createFromFormat('!H:i', $hora);

            if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha
                || !$horaObj || $horaObj->format('H:i') !== $hora) {
                throw new RuntimeException('La fecha o la hora no son válidas.');
            }

            if ($id !== false && $id !== null) {
                if ($id < 1) {
                    throw new RuntimeException('El identificador no es válido.');
                }

                $stmt = $conexion->prepare(
                    'UPDATE eventos
                     SET titulo = ?, descripcion = ?, fecha = ?,
                         hora = ?, lugar = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $titulo, $descripcion, $fecha,
                    $hora, $lugar, $id
                ]);

                $mensaje = 'Evento actualizado correctamente.';
            } else {
                $stmt = $conexion->prepare(
                    'INSERT INTO eventos
                     (titulo, descripcion, fecha, hora, lugar)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $titulo, $descripcion, $fecha,
                    $hora, $lugar
                ]);

                $mensaje = 'Evento publicado correctamente.';
            }
        } else {
            throw new RuntimeException('Acción no reconocida.');
        }
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    } catch (PDOException $ex) {
        $error = 'No se pudo guardar el evento. Revisa la base de datos.';
    }

    /* Evita repetir el envío del formulario al recargar */
    if ($mensaje !== '') {
        header('Location: admin_eventos.php?mensaje=' . urlencode($mensaje));
        exit;
    }
}

if (isset($_GET['editar'])) {
    $idEditar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);

    if ($idEditar && $idEditar > 0) {
        $stmt = $conexion->prepare('SELECT * FROM eventos WHERE id = ?');
        $stmt->execute([$idEditar]);
        $editar = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (isset($_GET['mensaje'])) {
    $mensaje = (string)$_GET['mensaje'];
}

$stmt = $conexion->query(
    'SELECT * FROM eventos ORDER BY fecha DESC, hora DESC'
);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administrar eventos | VillaLink</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f2f6fc;
    color: #20334d;
}
header {
    background: linear-gradient(120deg, #09295d, #147bc1);
    color: white;
    padding: 22px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}
header h1 { margin: 0; font-size: 25px; }
header a { color: white; text-decoration: none; }
main { max-width: 1100px; margin: 30px auto; padding: 0 18px; }
.card {
    background: white;
    border-radius: 15px;
    padding: 24px;
    margin-bottom: 25px;
    box-shadow: 0 6px 22px #193b6510;
}
h2 { color: #123e79; margin-top: 0; }
label { display: block; margin: 14px 0 6px; font-weight: bold; }
input, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd7e6;
    border-radius: 8px;
    font: inherit;
}
textarea { min-height: 110px; resize: vertical; }
button, .btn {
    border: 0;
    border-radius: 8px;
    padding: 11px 16px;
    background: #126bc4;
    color: white;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.cancelar { background: #64748b; }
.eliminar { background: #c0394b; }
.acciones { display: flex; gap: 8px; flex-wrap: wrap; }
.mensaje { background: #e3f8eb; padding: 12px; border-radius: 8px; }
.error { background: #ffeded; padding: 12px; border-radius: 8px; }
.tabla { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td {
    text-align: left;
    padding: 13px 10px;
    border-bottom: 1px solid #e4ebf3;
    vertical-align: top;
}
th { background: #edf4fc; }
@media (max-width: 600px) {
    .card { padding: 16px; }
    header h1 { font-size: 21px; }
}
</style>
</head>
<body>
<header>
    <h1>VillaLink · Administración de eventos</h1>
    <a href="cerrar_sesion_admin.php">Cerrar sesión</a>
</header>

<main>
    <section class="card">
        <h2><?= $editar ? 'Editar evento' : 'Publicar un evento' ?></h2>

        <?php if ($mensaje !== ''): ?>
            <p class="mensaje"><?= e($mensaje) ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['csrf_eventos']) ?>">
            <input type="hidden" name="accion" value="guardar">

            <?php if ($editar): ?>
                <input type="hidden" name="id"
                       value="<?= (int)$editar['id'] ?>">
            <?php endif; ?>

            <label for="titulo">Título del evento</label>
            <input id="titulo" name="titulo" maxlength="150" required
                   value="<?= e($editar['titulo'] ?? '') ?>">

            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion"
                      required><?= e($editar['descripcion'] ?? '') ?></textarea>

            <label for="fecha">Fecha</label>
            <input id="fecha" type="date" name="fecha" required
                   value="<?= e($editar['fecha'] ?? '') ?>">

            <label for="hora">Hora</label>
            <input id="hora" type="time" name="hora" required
                   value="<?= e(isset($editar['hora'])
                       ? substr($editar['hora'], 0, 5) : '') ?>">

            <label for="lugar">Lugar</label>
            <input id="lugar" name="lugar" maxlength="200" required
                   value="<?= e($editar['lugar'] ?? '') ?>">

            <div class="acciones" style="margin-top:20px">
                <button type="submit">
                    <?= $editar ? 'Guardar cambios' : 'Publicar evento' ?>
                </button>
                <?php if ($editar): ?>
                    <a class="btn cancelar" href="admin_eventos.php">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Eventos registrados</h2>

        <?php if (!$eventos): ?>
            <p>Aún no hay eventos publicados.</p>
        <?php else: ?>
        <div class="tabla">
        <table>
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Fecha y hora</th>
                    <th>Lugar</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($eventos as $evento): ?>
                <tr>
                    <td>
                        <strong><?= e($evento['titulo']) ?></strong><br>
                        <?= e($evento['descripcion']) ?>
                    </td>
                    <td>
                        <?= e($evento['fecha']) ?><br>
                        <?= e(substr($evento['hora'], 0, 5)) ?>
                    </td>
                    <td><?= e($evento['lugar']) ?></td>
                    <td>
                        <div class="acciones">
                            <a class="btn"
                               href="admin_eventos.php?editar=<?= (int)$evento['id'] ?>">
                                Editar
                            </a>
                            <form method="post"
                                  onsubmit="return confirm('¿Seguro que deseas eliminar este evento?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= e($_SESSION['csrf_eventos']) ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id"
                                       value="<?= (int)$evento['id'] ?>">
                                <button class="eliminar" type="submit">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
