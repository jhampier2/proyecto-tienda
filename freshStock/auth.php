<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar helpers ANTES de cualquier lógica
require_once 'helpers.php';

// 1. ¿Hay sesión?
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Verificar fingerprint (IP y User-Agent)
if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '') ||
    $_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    // Posible secuestro, cerrar sesión inmediatamente
    require_once 'conexion.php';
    destroyUserSession($pdo);
    header('Location: login.php?msg=security_breach');
    exit;
}

// 3. Inactividad máxima 30 minutos
$max_inactive = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_inactive)) {
    require_once 'conexion.php';
    destroyUserSession($pdo);
    header('Location: login.php?msg=session_timeout');
    exit;
}
$_SESSION['last_activity'] = time();

// 4. Regenerar ID de sesión cada 5 minutos
if (empty($_SESSION['last_regen']) || time() - $_SESSION['last_regen'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}

// 5. Anti-caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");