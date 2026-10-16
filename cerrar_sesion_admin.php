
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Eliminar únicamente los datos de la sesión administrativa */
unset($_SESSION['admin_id'], $_SESSION['admin_nombre']);

header('Location: admin_login.php');
exit;
