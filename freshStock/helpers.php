<?php
/**
 * helpers.php - Funciones auxiliares globales para FreshStock
 */

/**
 * Destruye la sesión del usuario de forma segura
 * NO intenta hacer header(), debe ser llamada ANTES de enviar headers
 * 
 * @param PDO $pdo Conexión a BD (opcional)
 * @return void
 */
function destroyUserSession($pdo = null): void {
    try {
        $user_id = $_SESSION['usuario_id'] ?? null;
        $current_session_id = session_id();

        // Eliminar sesión de la base de datos
        if ($user_id && $current_session_id && $pdo) {
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = :sid");
            $stmt->execute([':sid' => $current_session_id]);
        }

        // Limpiar variables de sesión
        $_SESSION = [];

        // Destruir cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 86400,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    } catch (Exception $e) {
        error_log("Error al destruir sesión: " . $e->getMessage());
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

/**
 * Determina el estado de un producto
 * 
 * @param int $stock Stock actual
 * @param int $stock_min Stock mínimo
 * @param string $fecha_vencimiento Fecha en formato 'Y-m-d'
 * @return string Estado: 'vencido', 'por_vencer', 'bajo_stock', 'ok'
 */
function estadoProducto(int $stock, int $stock_min, string $fecha_vencimiento): string {
    $hoy = date('Y-m-d');
    $fecha_venc = $fecha_vencimiento;
    
    // 1. Verificar si está vencido
    if ($fecha_venc < $hoy) {
        return 'vencido';
    }
    
    // 2. Verificar si está próximo a vencer (próximos 30 días)
    $fecha_limite = date('Y-m-d', strtotime('+30 days'));
    if ($fecha_venc <= $fecha_limite && $fecha_venc >= $hoy) {
        return 'por_vencer';
    }
    
    // 3. Verificar si está bajo stock
    if ($stock <= $stock_min) {
        return 'bajo_stock';
    }
    
    // 4. Todo está bien
    return 'ok';
}

/**
 * Formatea una fecha de 'Y-m-d' a 'd/m/Y'
 * 
 * @param string $fecha Fecha en formato 'Y-m-d'
 * @return string Fecha formateada 'd/m/Y'
 */
function fmtFecha(string $fecha): string {
    try {
        $date = DateTime::createFromFormat('Y-m-d', $fecha);
        if ($date === false) {
            return $fecha;
        }
        return $date->format('d/m/Y');
    } catch (Exception $e) {
        return $fecha;
    }
}

/**
 * Retorna un badge HTML con el estado del producto
 * 
 * @param string $estado Estado: 'vencido', 'por_vencer', 'bajo_stock', 'ok'
 * @return string HTML del badge
 */
function badgeEstado(string $estado): string {
    $colores = [
        'vencido'     => ['class' => 'badge-danger',    'icono' => '⚠️', 'texto' => 'Vencido'],
        'por_vencer'  => ['class' => 'badge-warn',      'icono' => '⏰', 'texto' => 'Por vencer'],
        'bajo_stock'  => ['class' => 'badge-low',       'icono' => '↓',  'texto' => 'Stock bajo'],
        'ok'          => ['class' => 'badge-ok',        'icono' => '✓',  'texto' => 'Correcto'],
    ];
    
    $config = $colores[$estado] ?? $colores['ok'];
    
    return sprintf(
        '<span class="badge %s">%s %s</span>',
        htmlspecialchars($config['class'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($config['icono'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($config['texto'], ENT_QUOTES, 'UTF-8')
    );
}
