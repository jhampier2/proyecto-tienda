<?php
// conexion.php
require_once 'helpers.php';

// ── SEGURIDAD: Headers de protección global ──────────────────────────────────
// 1. Forzar HTTPS en producción
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'http') {
    // Si viene de un proxy que dice HTTP, redirigir a HTTPS
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
} elseif (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    // En desarrollo/localhost está bien. En producción descomenta:
    // header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    // exit;
}

// 2. Security Headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;");

// ──────────────────────────────────────────────────────────────────────────────
$host = 'localhost';
$db   = 'freshstock_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En producción no muestres detalles
    error_log('Error conexión BD: ' . $e->getMessage());
    die('Error de conexión.');
}