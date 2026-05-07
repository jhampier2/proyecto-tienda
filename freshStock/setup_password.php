<?php
require 'auth.php';
require 'conexion.php';

$mensaje = '';

// Solo puede cambiar su propia contraseña
$usuario_actual = $_SESSION['usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = ['tipo' => 'error', 'texto' => 'Token de seguridad inválido.'];
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $nueva_pass      = trim($_POST['nueva_password'] ?? '');
        $confirmar       = trim($_POST['confirmar'] ?? '');

        if ($current_password === '' || $nueva_pass === '' || $confirmar === '') {
            $mensaje = ['tipo' => 'error', 'texto' => 'Todos los campos son obligatorios.'];
        } elseif ($nueva_pass !== $confirmar) {
            $mensaje = ['tipo' => 'error', 'texto' => 'Las contraseñas no coinciden.'];
        } elseif (strlen($nueva_pass) < 8) {
            $mensaje = ['tipo' => 'error', 'texto' => 'La contraseña debe tener al menos 8 caracteres.'];
        } else {
            // Verificar contraseña actual
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE usuario = :usuario");
            $stmt->execute([':usuario' => $usuario_actual]);
            $hash_actual = $stmt->fetchColumn();

            if (!$hash_actual || !password_verify($current_password, $hash_actual)) {
                $mensaje = ['tipo' => 'error', 'texto' => 'La contraseña actual es incorrecta.'];
            } else {
                // Actualizar contraseña
                $nuevo_hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuarios SET password = :hash WHERE usuario = :usuario")
                    ->execute([':hash' => $nuevo_hash, ':usuario' => $usuario_actual]);
                $mensaje = ['tipo' => 'ok', 'texto' => 'Contraseña actualizada correctamente.'];
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
<title>FreshStock · Cambiar Contraseña</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<aside class="sidebar">
  <!-- ... mismo sidebar que en productos.php, pero señalando la opción activa ... -->
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-box"></i></div>
    <div><div class="brand-name">FreshStock</div><div class="brand-sub">Inventario</div></div>
  </div>
  <span class="nav-section">Menú</span>
  <a href="index.php" class="nav-link"><span class="icon"><i class="bi bi-graph-up"></i></span> Dashboard</a>
  <a href="productos.php" class="nav-link"><span class="icon"><i class="bi bi-box"></i></span> Productos</a>
  <a href="exportar.php" class="nav-link"><span class="icon"><i class="bi bi-download"></i></span> Exportar CSV</a>
  <a href="setup_password.php" class="nav-link active"><span class="icon"><i class="bi bi-gear"></i></span> Contraseña</a>
  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="user-role">Administrador</div>
      </div>
      <form id="logout-form" action="logout.php" method="POST" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="logout-btn" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></button>
      </form>
    </div>
  </div>
</aside>

<main class="main">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-shield-lock"></i> Cambiar Contraseña</h1>
    <p class="page-subtitle">Actualiza tu contraseña de acceso</p>
  </div>

  <?php if ($mensaje): ?>
  <div class="alert alert-<?= $mensaje['tipo'] === 'ok' ? 'success' : 'danger' ?>" style="margin-bottom:20px">
    <?= $mensaje['texto'] ?>
  </div>
  <?php endif; ?>

  <div class="section-card" style="max-width:500px">
    <div class="section-head"><span class="section-title">Formulario de cambio</span></div>
    <form method="POST" style="padding:24px;display:flex;flex-direction:column;gap:16px">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <div class="form-group">
        <label>Usuario</label>
        <input type="text" value="<?= htmlspecialchars($usuario_actual, ENT_QUOTES, 'UTF-8') ?>" disabled 
               style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#aaa;border-radius:8px">
      </div>
      <div class="form-group">
        <label>Contraseña actual</label>
        <input type="password" name="current_password" required
               style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.18);color:#f0f4f8;font-size:.9rem;border-radius:8px">
      </div>
      <div class="form-group">
        <label>Nueva contraseña (mín. 8 caracteres)</label>
        <input type="password" name="nueva_password" required
               style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.18);color:#f0f4f8;font-size:.9rem;border-radius:8px">
      </div>
      <div class="form-group">
        <label>Confirmar nueva contraseña</label>
        <input type="password" name="confirmar" required
               style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.18);color:#f0f4f8;font-size:.9rem;border-radius:8px">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">
        <i class="bi bi-check-circle"></i> Actualizar contraseña
      </button>
    </form>
  </div>

  <div class="alert" style="margin-top:20px;background:rgba(205,92,92,0.1);border-color:#cd5c5c44;color:#cd5c5c">
    <i class="bi bi-exclamation-triangle"></i> <strong>Recomendación de seguridad:</strong> Elimina o restringe el acceso a este archivo (<code>setup_password.php</code>) en producción.
  </div>
</main>
</body>
</html>