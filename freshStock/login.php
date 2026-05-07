<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, redirigir al panel
if (!empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require 'conexion.php';
$error = '';
$mensaje_exito = '';

// Mensajes desde logout o index
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'session_terminated') {
        $mensaje_exito = 'Sesión cerrada correctamente. ¡Hasta pronto!';
    } elseif ($_GET['msg'] === 'forced_termination') {
        $mensaje_exito = 'Sesión terminada por protocolo de seguridad.';
    }
}
if (isset($_GET['error']) && $_GET['error'] === 'security_breach') {
    $error = 'Acceso denegado: Se detectó una acción no autorizada.';
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $usuario_input  = trim($_POST['usuario'] ?? '');
        $password_input = $_POST['password'] ?? '';
        $ip_cliente     = $_SERVER['REMOTE_ADDR'];

        if ($usuario_input === '' || $password_input === '') {
            $error = 'Por favor completa todos los campos.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $usuario_input)) {
            // Validación de formato: alfanuméricos y guion bajo, 3-20 caracteres
            $error = 'Usuario inválido. Solo se permiten letras, números y guión bajo (3-20 caracteres).';
        } else {
            // Limpiar intentos antiguos (> 15 min)
            $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL 15 MINUTE)");

            // Verificar bloqueo por USUARIO + IP
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM login_attempts 
                 WHERE usuario = :usuario AND ip_address = :ip 
                 AND attempt_time > (NOW() - INTERVAL 15 MINUTE)"
            );
            $stmt->execute([':usuario' => $usuario_input, ':ip' => $ip_cliente]);
            $intentos = $stmt->fetchColumn();

            if ($intentos >= 5) {  // 5 intentos fallidos en 15 min
                $error = 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente (15 min).';
            } else {
                // Buscar usuario
                $stmt = $pdo->prepare("SELECT id, usuario, password FROM usuarios WHERE usuario = :usuario LIMIT 1");
                $stmt->execute([':usuario' => $usuario_input]);
                $row = $stmt->fetch();

                if ($row && password_verify($password_input, $row['password'])) {
                    // --- ÉXITO ---
                    session_regenerate_id(true);
                    $_SESSION['usuario_id']  = $row['id'];
                    $_SESSION['usuario']     = $row['usuario'];
                    $_SESSION['user_agent']  = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $_SESSION['ip']          = $ip_cliente;
                    $_SESSION['last_regen']  = time();
                    // Regenerar token CSRF tras login
                    $_SESSION['csrf_token']  = bin2hex(random_bytes(32));

                    // Registrar sesión en BD
                    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$row['id'], session_id(), $ip_cliente, $_SESSION['user_agent']]);

                    // Limpiar intentos fallidos de ese usuario+IP
                    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE usuario = :usuario AND ip_address = :ip");
                    $stmt->execute([':usuario' => $usuario_input, ':ip' => $ip_cliente]);

                    header('Location: index.php');
                    exit;
                } else {
                    // Fallo: registrar intento
                    $stmt = $pdo->prepare("INSERT INTO login_attempts (usuario, ip_address, attempt_time) VALUES (:usuario, :ip, NOW())");
                    $stmt->execute([':usuario' => $usuario_input, ':ip' => $ip_cliente]);

                    $restantes = 4 - $intentos; // porque el fallo ya se sumó
                    if ($restantes > 0) {
                        $error = "Usuario o contraseña incorrectos. Te quedan $restantes intento(s).";
                    } else {
                        $error = "Usuario o contraseña incorrectos. Cuenta bloqueada temporalmente.";
                    }
                }
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
    <title>FreshStock · Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
      body { display: block; }
      .flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
      .flash-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
      .flash { margin-bottom: 20px; padding: 12px; border-radius: 8px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
<div class="login-page">
  <div class="login-bg"></div>
  <div class="login-card">
    <div class="login-brand">
      <div class="login-logo"><i class="bi bi-box-seam"></i></div>
      <div class="login-title">FreshStock</div>
      <div class="login-sub">Sistema de Control de Inventario</div>
    </div>

    <?php if ($error): ?>
    <div class="flash flash-error">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <?php if ($mensaje_exito): ?>
    <div class="flash flash-success">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <form class="login-form" method="POST" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <div class="form-group">
        <label for="usuario"><i class="bi bi-person-circle"></i> Usuario</label>
        <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required autofocus>
      </div>
      <div class="form-group">
        <label for="password"><i class="bi bi-lock"></i> Contraseña</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:8px; width: 100%;">
        Ingresar al sistema <i class="bi bi-arrow-right"></i>
      </button>
    </form>

    <p style="text-align:center;font-size:.75rem;color:var(--txt-muted);margin-top:24px">
      FreshStock v2.0 · Gestión de Perecibles
    </p>
  </div>
</div>
</body>
</html>