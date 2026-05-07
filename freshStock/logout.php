<?php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar CSRF solo si hay sesión iniciada
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    empty($_SESSION['csrf_token']) || 
    empty($_POST['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // Registro de posible ataque
    error_log("POSIBLE ATAQUE CSRF o sesión inválida desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
    header('Location: index.php?error=security_breach');
    exit;
}

try {
    // Destruir sesión de forma segura
    destroyUserSession($pdo);

    // Cabeceras anti-caché
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    header('Location: login.php?msg=session_terminated');
    exit;

} catch (Exception $e) {
    error_log("Error en Logout: " . $e->getMessage());
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    header('Location: login.php?msg=forced_termination');
    exit;
}