<?php
require 'auth.php';
require 'conexion.php';

// ── Registrar exportación en sesión ──────────────────────────────────────────
function registrarExport(string $filtro, int $total): void {
    if (!isset($_SESSION['export_log'])) $_SESSION['export_log'] = [];
    array_unshift($_SESSION['export_log'], [
        'filtro' => $filtro,
        'total'  => $total,
        'fecha'  => date('d/m/Y H:i:s'),
    ]);
    $_SESSION['export_log'] = array_slice($_SESSION['export_log'], 0, 8);
}

// ── Helper: construir WHERE ───────────────────────────────────────────────────
function buildWhere(string $filtro, string $desde = '', string $hasta = ''): string {
    $conds = [];

    switch ($filtro) {
        case 'vencidos':   $conds[] = "fecha_vencimiento < CURDATE()"; break;
        case 'por_vencer': $conds[] = "fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"; break;
        case 'bajo_stock': $conds[] = "stock <= stock_min"; break;
        case 'ok':         $conds[] = "fecha_vencimiento >= CURDATE() AND stock > stock_min"; break;
        case 'sin_stock':  $conds[] = "stock = 0"; break;
        case 'rango':
            if ($desde) $conds[] = "fecha_vencimiento >= " . (new PDO(''))->quote($desde);
            if ($hasta) $conds[] = "fecha_vencimiento <= " . (new PDO(''))->quote($hasta);
            break;
    }

    return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
}

// ── Columnas disponibles ──────────────────────────────────────────────────────
$COLS_DISPONIBLES = [
    'id'             => 'ID',
    'nombre'         => 'Nombre',
    'stock'          => 'Stock',
    'stock_min'      => 'Stock Mínimo',
    'precio_compra'  => 'Precio Compra (S/)',
    'precio_venta'   => 'Precio Venta (S/)',
    'ganancia_unit'  => 'Ganancia Unit. (S/)',
    'ganancia_total' => 'Ganancia Total (S/)',
    'fecha_venc'     => 'Fecha Vencimiento',
    'estado'         => 'Estado',
];

// ── AJAX: preview de datos ────────────────────────────────────────────────────
if (isset($_GET['preview'])) {
    header('Content-Type: application/json');
    $filtro = $_GET['filtro'] ?? 'todos';
    $desde  = $_GET['desde'] ?? '';
    $hasta  = $_GET['hasta'] ?? '';
    $limit  = min((int)($_GET['limit'] ?? 10), 50);

    $where = match($filtro) {
        'vencidos'   => "WHERE fecha_vencimiento < CURDATE()",
        'por_vencer' => "WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
        'bajo_stock' => "WHERE stock <= stock_min",
        'ok'         => "WHERE fecha_vencimiento >= CURDATE() AND stock > stock_min",
        'sin_stock'  => "WHERE stock = 0",
        default      => "",
    };

    if ($filtro === 'rango') {
        $conds = [];
        if ($desde) $conds[] = "fecha_vencimiento >= " . $pdo->quote($desde);
        if ($hasta) $conds[] = "fecha_vencimiento <= " . $pdo->quote($hasta);
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }

    $total    = $pdo->query("SELECT COUNT(*) FROM productos $where")->fetchColumn();
    $productos = $pdo->query("SELECT * FROM productos $where ORDER BY fecha_vencimiento ASC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($productos as $p) {
        $ganUnit  = round($p['precio_venta'] - $p['precio_compra'], 2);
        $ganTotal = round($ganUnit * $p['stock'], 2);
        $estadoCod = estadoProducto($p['stock'], $p['stock_min'], $p['fecha_vencimiento']);
        $rows[] = [
            'id'             => $p['id'],
            'nombre'         => $p['nombre'],
            'stock'          => $p['stock'],
            'stock_min'      => $p['stock_min'],
            'precio_compra'  => number_format($p['precio_compra'], 2),
            'precio_venta'   => number_format($p['precio_venta'], 2),
            'ganancia_unit'  => number_format($ganUnit, 2),
            'ganancia_total' => number_format($ganTotal, 2),
            'fecha_venc'     => $p['fecha_vencimiento'],
            'estado'         => $estadoCod,
        ];
    }
    echo json_encode(['total' => $total, 'rows' => $rows]);
    exit;
}

// ── Descarga CSV ──────────────────────────────────────────────────────────────
if (isset($_GET['download'])) {
    $filtro  = $_GET['filtro'] ?? 'todos';
    $desde   = $_GET['desde']  ?? '';
    $hasta   = $_GET['hasta']  ?? '';
    $colsSel = isset($_GET['cols']) ? explode(',', $_GET['cols']) : array_keys($COLS_DISPONIBLES);
    $sep     = $_GET['sep'] ?? ','; // , o ;

    $where = match($filtro) {
        'vencidos'   => "WHERE fecha_vencimiento < CURDATE()",
        'por_vencer' => "WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
        'bajo_stock' => "WHERE stock <= stock_min",
        'ok'         => "WHERE fecha_vencimiento >= CURDATE() AND stock > stock_min",
        'sin_stock'  => "WHERE stock = 0",
        default      => "",
    };

    if ($filtro === 'rango') {
        $conds = [];
        if ($desde) $conds[] = "fecha_vencimiento >= " . $pdo->quote($desde);
        if ($hasta) $conds[] = "fecha_vencimiento <= " . $pdo->quote($hasta);
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }

    $productos = $pdo->query("SELECT * FROM productos $where ORDER BY fecha_vencimiento ASC")->fetchAll();
    registrarExport($filtro, count($productos));

    $filename = "freshstock_" . $filtro . "_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

    // Fila de metadatos
    fputcsv($out, ['# Reporte FreshStock', 'Filtro: ' . $filtro, 'Generado: ' . date('d/m/Y H:i'), 'Usuario: ' . $_SESSION['usuario']], $sep);
    fputcsv($out, [], $sep); // fila vacía

    // Encabezados seleccionados
    $headers = array_map(fn($k) => $COLS_DISPONIBLES[$k] ?? $k, $colsSel);
    fputcsv($out, $headers, $sep);

    foreach ($productos as $p) {
        $ganUnit  = round($p['precio_venta'] - $p['precio_compra'], 2);
        $ganTotal = round($ganUnit * $p['stock'], 2);
        $estadoMap = ['vencido' => 'Vencido', 'por_vencer' => 'Por vencer', 'bajo_stock' => 'Bajo stock', 'ok' => 'OK'];
        $estadoCod = estadoProducto($p['stock'], $p['stock_min'], $p['fecha_vencimiento']);

        $allVals = [
            'id'             => $p['id'],
            'nombre'         => $p['nombre'],
            'stock'          => $p['stock'],
            'stock_min'      => $p['stock_min'],
            'precio_compra'  => number_format($p['precio_compra'], 2),
            'precio_venta'   => number_format($p['precio_venta'], 2),
            'ganancia_unit'  => number_format($ganUnit, 2),
            'ganancia_total' => number_format($ganTotal, 2),
            'fecha_venc'     => $p['fecha_vencimiento'],
            'estado'         => $estadoMap[$estadoCod] ?? 'OK',
        ];

        fputcsv($out, array_map(fn($k) => $allVals[$k] ?? '', $colsSel), $sep);
    }
    fclose($out);
    exit;
}

// ── Resumen general ───────────────────────────────────────────────────────────
$resumen = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END) AS vencidos,
        SUM(CASE WHEN fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS por_vencer,
        SUM(CASE WHEN stock <= stock_min AND fecha_vencimiento >= CURDATE() THEN 1 ELSE 0 END) AS bajo_stock,
        SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) AS sin_stock,
        SUM(CASE WHEN fecha_vencimiento >= CURDATE() AND stock > stock_min THEN 1 ELSE 0 END) AS ok,
        SUM(precio_venta * stock) AS valor_inventario
    FROM productos
")->fetch();

$pct = fn($n) => $resumen['total'] > 0 ? round($n / $resumen['total'] * 100, 1) : 0;
$exportLog = $_SESSION['export_log'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FreshStock · Exportar CSV</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* ── Mejoras adicionales para exportar.php ─────────────────────────────────── */

/* Cards de exportación mejoradas */
.export-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}

.export-card {
  background: var(--card-bg, #1e2028);
  border: 1px solid var(--border, #2a2d38);
  border-radius: 14px;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  cursor: default;
}
.export-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,.25);
}

.export-card-body { padding: 22px; }

.export-card-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
  margin-bottom: 14px;
}
.icon-blue   { background: rgba(99,179,237,.12); color: #63b3ed; }
.icon-red    { background: rgba(252,129,129,.12); color: var(--danger, #fc8181); }
.icon-yellow { background: rgba(246,173, 85,.12); color: var(--warn,  #f6ad55); }
.icon-cyan   { background: rgba(76, 201,240,.12); color: var(--info,  #4cc9f0); }
.icon-green  { background: rgba(72, 199,142,.12); color: var(--success,#48c78e); }
.icon-orange { background: rgba(237,137, 54,.12); color: #ed8936; }

.export-card-title {
  font-family: 'Sora', sans-serif;
  font-weight: 700;
  font-size: .95rem;
  margin-bottom: 4px;
}
.export-card-desc {
  color: var(--txt-muted, #8b8fa8);
  font-size: .8rem;
  margin-bottom: 10px;
  line-height: 1.5;
}
.export-card-count {
  font-size: 1.6rem;
  font-weight: 800;
  font-family: 'Sora', sans-serif;
  margin-bottom: 4px;
}

/* Barra de progreso */
.progress-bar-wrap {
  background: var(--border, #2a2d38);
  border-radius: 99px;
  height: 4px;
  margin-bottom: 14px;
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .8s ease;
}

/* Botones de acción en card */
.card-actions {
  display: flex;
  gap: 8px;
}
.btn-preview {
  flex: 1;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: .8rem;
  font-weight: 600;
  border: 1px solid var(--border, #2a2d38);
  background: transparent;
  color: var(--txt-muted, #8b8fa8);
  cursor: pointer;
  transition: .2s;
  display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-preview:hover { background: var(--border, #2a2d38); color: var(--txt, #e2e6f0); }

.btn-dl {
  flex: 1;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: .8rem;
  font-weight: 600;
  text-decoration: none;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: .2s;
}
.btn-dl-blue   { background: rgba(99,179,237,.15); color: #63b3ed; border: 1px solid rgba(99,179,237,.3); }
.btn-dl-red    { background: rgba(252,129,129,.15); color: var(--danger); border: 1px solid rgba(252,129,129,.3); }
.btn-dl-yellow { background: rgba(246,173, 85,.15); color: var(--warn);   border: 1px solid rgba(246,173, 85,.3); }
.btn-dl-cyan   { background: rgba(76, 201,240,.15); color: var(--info);   border: 1px solid rgba(76, 201,240,.3); }
.btn-dl-green  { background: rgba(72, 199,142,.15); color: var(--success);border: 1px solid rgba(72, 199,142,.3); }
.btn-dl-orange { background: rgba(237,137, 54,.15); color: #ed8936;       border: 1px solid rgba(237,137, 54,.3); }

.btn-dl:hover { filter: brightness(1.15); transform: translateY(-1px); }

/* ── Panel avanzado ─────────────────────────────────────────────────────────── */
.advanced-panel {
  background: var(--card-bg, #1e2028);
  border: 1px solid var(--border, #2a2d38);
  border-radius: 14px;
  margin-bottom: 24px;
  overflow: hidden;
}
.advanced-toggle {
  width: 100%;
  padding: 16px 22px;
  background: none;
  border: none;
  color: var(--txt, #e2e6f0);
  font-size: .9rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: background .2s;
}
.advanced-toggle:hover { background: var(--border, #2a2d38); }
.advanced-toggle .chevron { margin-left: auto; transition: transform .3s; }
.advanced-toggle.open .chevron { transform: rotate(180deg); }

.advanced-body {
  display: none;
  padding: 0 22px 22px;
  border-top: 1px solid var(--border, #2a2d38);
}
.advanced-body.open { display: block; }

.adv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 18px; }
@media(max-width:600px) { .adv-grid { grid-template-columns: 1fr; } }

.adv-label {
  font-size: .78rem;
  font-weight: 700;
  color: var(--txt-muted, #8b8fa8);
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: 10px;
}

.cols-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 8px;
}
.col-check {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: .82rem;
  color: var(--txt, #e2e6f0);
  cursor: pointer;
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid var(--border, #2a2d38);
  transition: .2s;
  user-select: none;
}
.col-check:hover { background: var(--border, #2a2d38); }
.col-check input { accent-color: var(--accent, #6c63ff); width: 14px; height: 14px; }
.col-check.checked { border-color: var(--accent, #6c63ff); background: rgba(108,99,255,.08); }

.sep-select {
  background: var(--bg, #13141a);
  border: 1px solid var(--border, #2a2d38);
  border-radius: 8px;
  color: var(--txt, #e2e6f0);
  padding: 8px 12px;
  font-size: .85rem;
}
.adv-input {
  background: var(--bg, #13141a);
  border: 1px solid var(--border, #2a2d38);
  border-radius: 8px;
  color: var(--txt, #e2e6f0);
  padding: 8px 12px;
  font-size: .85rem;
  width: 100%;
}
.adv-input:focus { outline: none; border-color: var(--accent, #6c63ff); }

.range-fields { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.range-fields .adv-input { flex: 1; min-width: 130px; }

.btn-dl-rango {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 9px;
  background: linear-gradient(135deg, var(--accent, #6c63ff), #a78bfa);
  color: #fff;
  font-weight: 700;
  font-size: .85rem;
  border: none;
  cursor: pointer;
  margin-top: 14px;
  text-decoration: none;
  transition: opacity .2s, transform .2s;
}
.btn-dl-rango:hover { opacity: .9; transform: translateY(-1px); }

/* ── Modal de preview ───────────────────────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; pointer-events: none;
  transition: opacity .25s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }

.modal-box {
  background: var(--card-bg, #1e2028);
  border: 1px solid var(--border, #2a2d38);
  border-radius: 18px;
  width: min(900px, 95vw);
  max-height: 80vh;
  display: flex; flex-direction: column;
  transform: translateY(20px);
  transition: transform .25s;
  overflow: hidden;
}
.modal-overlay.open .modal-box { transform: translateY(0); }

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border, #2a2d38);
  display: flex; align-items: center; gap: 12px;
}
.modal-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 1rem; flex: 1; }
.modal-badge {
  background: rgba(108,99,255,.15);
  color: var(--accent, #6c63ff);
  border-radius: 99px;
  padding: 3px 12px;
  font-size: .78rem;
  font-weight: 700;
}
.modal-close {
  width: 32px; height: 32px;
  border-radius: 8px;
  background: var(--border, #2a2d38);
  border: none;
  color: var(--txt, #e2e6f0);
  cursor: pointer;
  font-size: 1rem;
  display: flex; align-items: center; justify-content: center;
  transition: .15s;
}
.modal-close:hover { background: var(--danger, #fc8181); color: #fff; }

.modal-body { overflow-y: auto; flex: 1; padding: 0; }

/* Tabla de preview */
.preview-table-wrap { overflow-x: auto; }
.preview-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .8rem;
}
.preview-table th {
  padding: 10px 14px;
  text-align: left;
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--txt-muted, #8b8fa8);
  background: var(--bg, #13141a);
  position: sticky; top: 0;
  border-bottom: 1px solid var(--border, #2a2d38);
}
.preview-table td {
  padding: 10px 14px;
  border-bottom: 1px solid var(--border, #2a2d38);
  color: var(--txt, #e2e6f0);
  white-space: nowrap;
}
.preview-table tr:hover td { background: rgba(255,255,255,.02); }

.estado-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 2px 10px; border-radius: 99px;
  font-size: .73rem; font-weight: 700;
}
.estado-ok        { background: rgba(72,199,142,.15); color: #48c78e; }
.estado-vencido   { background: rgba(252,129,129,.15); color: var(--danger,#fc8181); }
.estado-por_vencer{ background: rgba(246,173, 85,.15); color: var(--warn, #f6ad55); }
.estado-bajo_stock{ background: rgba(76, 201,240,.15); color: var(--info, #4cc9f0); }

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border, #2a2d38);
  display: flex; justify-content: space-between; align-items: center; gap: 12px;
  flex-wrap: wrap;
}
.modal-info { font-size: .8rem; color: var(--txt-muted, #8b8fa8); }

.preview-loading {
  display: flex; align-items: center; justify-content: center;
  padding: 60px;
  flex-direction: column;
  gap: 12px;
  color: var(--txt-muted, #8b8fa8);
}
.spinner {
  width: 32px; height: 32px;
  border: 3px solid var(--border, #2a2d38);
  border-top-color: var(--accent, #6c63ff);
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Log de exportaciones ───────────────────────────────────────────────────── */
.log-list { list-style: none; padding: 0; margin: 0; }
.log-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--border, #2a2d38);
  font-size: .82rem;
}
.log-item:last-child { border-bottom: none; }
.log-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  background: rgba(108,99,255,.12);
  color: var(--accent, #6c63ff);
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; flex-shrink: 0;
}
.log-filtro { font-weight: 600; color: var(--txt, #e2e6f0); flex: 1; }
.log-meta   { color: var(--txt-muted, #8b8fa8); font-size: .76rem; text-align: right; }

/* ── Valor total inventario ─────────────────────────────────────────────────── */
.inv-value-card {
  background: rgba(28, 27, 32, 0.97); /* mismo tono oscuro sin degradado */
  border: 1px solid rgb(53, 53, 56);
  border-radius: 14px;
  padding: 20px 24px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 18px;
  flex-wrap: wrap;
}
.inv-value-icon {
  width: 52px; height: 52px;
  border-radius: 14px;
  background: rgba(108,99,255,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem;
  color: var(--accent, #6c63ff);
  flex-shrink: 0;
}
.inv-value-label { font-size: .78rem; color: var(--txt-muted); text-transform: uppercase; letter-spacing: .06em; font-weight: 700; margin-bottom: 4px; }
.inv-value-num   { font-size: 1.8rem; font-family: 'Sora', sans-serif; font-weight: 800; color: var(--accent, #6c63ff); }
.inv-value-sub   { font-size: .8rem; color: var(--txt-muted); margin-top: 2px; }

/* Tooltip */
[data-tip] { position: relative; }
[data-tip]::after {
  content: attr(data-tip);
  position: absolute; bottom: calc(100% + 8px); left: 50%;
  transform: translateX(-50%);
  background: #0d0e13;
  border: 1px solid var(--border);
  color: var(--txt);
  font-size: .74rem;
  padding: 5px 10px;
  border-radius: 7px;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity .15s;
}
[data-tip]:hover::after { opacity: 1; }
</style>
</head>
<body>

<!-- ── Sidebar ──────────────────────────────────────────────────────────────── -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-box"></i></div>
    <div>
      <div class="brand-name">FreshStock</div>
      <div class="brand-sub">Inventario</div>
    </div>
  </div>
  <span class="nav-section">Menú</span>
  <a href="index.php"     class="nav-link"><span class="icon"><i class="bi bi-graph-up"></i></span> Dashboard</a>
  <a href="productos.php" class="nav-link"><span class="icon"><i class="bi bi-box"></i></span> Productos</a>
  <a href="exportar.php"  class="nav-link active"><span class="icon"><i class="bi bi-download"></i></span> Exportar CSV</a>
  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="user-role" style="cursor:pointer" title="Configurar contraseña" onclick="location.href='setup_password.php'">Administrador</div>
      </div>
      <a href="logout.php" class="logout-btn" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
</aside>

<!-- ── Main ─────────────────────────────────────────────────────────────────── -->
<main class="main">

  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-download"></i> Exportar Reportes CSV</h1>
      <p class="page-subtitle">Descarga, filtra y previsualiza datos del inventario antes de exportar</p>
    </div>
  </div>

  <!-- Valor total del inventario -->
  <div class="inv-value-card">
    <div class="inv-value-icon"><i class="bi bi-cash-stack"></i></div>
    <div>
      <div class="inv-value-label">Valor total en inventario (precio venta)</div>
      <div class="inv-value-num">S/ <?= number_format($resumen['valor_inventario'] ?? 0, 2) ?></div>
      <div class="inv-value-sub"><?= $resumen['total'] ?> productos registrados en el sistema</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:24px;flex-wrap:wrap">
      <?php
      $stats = [
        ['Vencidos',   $resumen['vencidos'],   'var(--danger)', 'bi-x-circle'],
        ['Por vencer', $resumen['por_vencer'],  'var(--warn)',   'bi-clock'],
        ['Bajo stock', $resumen['bajo_stock'],  'var(--info)',   'bi-graph-down-arrow'],
        ['OK',         $resumen['ok'],          '#48c78e',       'bi-check-circle'],
      ];
      foreach ($stats as [$lbl, $val, $col, $ico]): ?>
      <div style="text-align:center">
        <div style="font-size:.7rem;color:var(--txt-muted);text-transform:uppercase;letter-spacing:.05em"><?= $lbl ?></div>
        <div style="font-size:1.3rem;font-weight:800;color:<?= $col ?>"><?= $val ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── Cards de exportación ───────────────────────────────────────────────── -->
  <div class="export-grid">

    <?php
    $cards = [
      [
        'filtro' => 'todos',
        'icon'   => 'bi-archive',
        'cls'    => 'icon-blue',
        'title'  => 'Inventario Completo',
        'desc'   => 'Todos los productos registrados.',
        'count'  => $resumen['total'],
        'pct'    => 100,
        'bar'    => '#63b3ed',
        'btn'    => 'btn-dl-blue',
      ],
      [
        'filtro' => 'vencidos',
        'icon'   => 'bi-exclamation-triangle',
        'cls'    => 'icon-red',
        'title'  => 'Productos Vencidos',
        'desc'   => 'Fecha de vencimiento ya superada.',
        'count'  => $resumen['vencidos'],
        'pct'    => $pct($resumen['vencidos']),
        'bar'    => 'var(--danger)',
        'btn'    => 'btn-dl-red',
      ],
      [
        'filtro' => 'por_vencer',
        'icon'   => 'bi-hourglass-split',
        'cls'    => 'icon-yellow',
        'title'  => 'Por Vencer (30 días)',
        'desc'   => 'Próximos a vencer en 30 días.',
        'count'  => $resumen['por_vencer'],
        'pct'    => $pct($resumen['por_vencer']),
        'bar'    => 'var(--warn)',
        'btn'    => 'btn-dl-yellow',
      ],
      [
        'filtro' => 'bajo_stock',
        'icon'   => 'bi-graph-down-arrow',
        'cls'    => 'icon-cyan',
        'title'  => 'Bajo Stock',
        'desc'   => 'Stock igual o menor al mínimo.',
        'count'  => $resumen['bajo_stock'],
        'pct'    => $pct($resumen['bajo_stock']),
        'bar'    => 'var(--info)',
        'btn'    => 'btn-dl-cyan',
      ],
      [
        'filtro' => 'sin_stock',
        'icon'   => 'bi-slash-circle',
        'cls'    => 'icon-orange',
        'title'  => 'Sin Stock',
        'desc'   => 'Productos con stock en cero.',
        'count'  => $resumen['sin_stock'],
        'pct'    => $pct($resumen['sin_stock']),
        'bar'    => '#ed8936',
        'btn'    => 'btn-dl-orange',
      ],
      [
        'filtro' => 'ok',
        'icon'   => 'bi-check2-circle',
        'cls'    => 'icon-green',
        'title'  => 'Estado Óptimo',
        'desc'   => 'Stock suficiente y sin vencer.',
        'count'  => $resumen['ok'],
        'pct'    => $pct($resumen['ok']),
        'bar'    => '#48c78e',
        'btn'    => 'btn-dl-green',
      ],
    ];

    foreach ($cards as $c): ?>
    <div class="export-card">
      <div class="export-card-body">
        <div class="export-card-icon <?= $c['cls'] ?>">
          <i class="bi <?= $c['icon'] ?>"></i>
        </div>
        <div class="export-card-title"><?= $c['title'] ?></div>
        <div class="export-card-desc"><?= $c['desc'] ?></div>
        <div class="export-card-count" style="color:<?= $c['bar'] ?>"><?= $c['count'] ?></div>
        <div class="progress-bar-wrap" data-tip="<?= $c['pct'] ?>% del total">
          <div class="progress-bar-fill" style="width:<?= $c['pct'] ?>%;background:<?= $c['bar'] ?>"></div>
        </div>
        <div class="card-actions">
          <button class="btn-preview" onclick="openPreview('<?= $c['filtro'] ?>', '<?= $c['title'] ?>')">
            <i class="bi bi-eye"></i> Vista previa
          </button>
          <a href="exportar.php?download=1&filtro=<?= $c['filtro'] ?>" class="btn-dl <?= $c['btn'] ?>" id="dl-<?= $c['filtro'] ?>">
            <i class="bi bi-download"></i> Exportar
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

  </div>

  <!-- ── Panel avanzado ─────────────────────────────────────────────────────── -->
  <div class="advanced-panel">
    <button class="advanced-toggle" id="advBtn" onclick="toggleAdvanced()">
      <i class="bi bi-sliders"></i>
      <span>Opciones avanzadas</span>
      <span style="font-size:.78rem;color:var(--txt-muted);font-weight:400">Rango de fechas · Selector de columnas · Separador</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="advanced-body" id="advBody">
      <div class="adv-grid">

        <!-- Columnas -->
        <div>
          <div class="adv-label"><i class="bi bi-table"></i> Columnas a exportar</div>
          <div class="cols-grid" id="colsGrid">
            <?php foreach ($COLS_DISPONIBLES as $key => $label): ?>
            <label class="col-check checked" id="lbl-<?= $key ?>">
              <input type="checkbox" name="col" value="<?= $key ?>" checked onchange="updateColCheck(this)">
              <?= $label ?>
            </label>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:10px;display:flex;gap:8px">
            <button onclick="toggleAllCols(true)"  style="font-size:.75rem;padding:4px 10px;border-radius:6px;background:var(--border);border:none;color:var(--txt);cursor:pointer">Seleccionar todo</button>
            <button onclick="toggleAllCols(false)" style="font-size:.75rem;padding:4px 10px;border-radius:6px;background:var(--border);border:none;color:var(--txt);cursor:pointer">Limpiar</button>
          </div>
        </div>

        <!-- Filtros extra -->
        <div>
          <div class="adv-label"><i class="bi bi-calendar-range"></i> Exportar por rango de vencimiento</div>
          <div class="range-fields">
            <input type="date" class="adv-input" id="rangoDesde" placeholder="Desde">
            <span style="color:var(--txt-muted);font-size:.85rem">→</span>
            <input type="date" class="adv-input" id="rangoHasta" placeholder="Hasta">
          </div>

          <div style="margin-top:18px">
            <div class="adv-label"><i class="bi bi-file-earmark-text"></i> Separador CSV</div>
            <select class="sep-select" id="sepSelect">
              <option value=",">, (coma) — compatible con LibreOffice, Google Sheets</option>
              <option value=";">; (punto y coma) — compatible con Excel ES/LA</option>
              <option value="&#9;">&#9; (tabulación) — TSV genérico</option>
            </select>
          </div>

          <button class="btn-dl-rango" id="btnRango" onclick="exportarRango()">
            <i class="bi bi-download"></i> Exportar rango personalizado
          </button>
          <button class="btn-preview" style="margin-top:8px;width:100%" onclick="openPreview('rango','Rango personalizado')">
            <i class="bi bi-eye"></i> Vista previa del rango
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Historial + Info ────────────────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px" class="two-col-grid">

    <!-- Historial de sesión -->
    <div class="section-card">
      <div class="section-head">
        <span class="section-title"><i class="bi bi-clock-history"></i> Exportaciones recientes</span>
        <?php if ($exportLog): ?>
        <button onclick="this.closest('.section-card').querySelector('.log-list').innerHTML='<li style=\'padding:12px 0;color:var(--txt-muted);font-size:.82rem;text-align:center\'>Sin historial en esta sesión.</li>';<?php $_SESSION['export_log'] = []; ?>" 
                style="font-size:.75rem;padding:4px 10px;border-radius:6px;background:var(--border);border:none;color:var(--txt-muted);cursor:pointer">
          Limpiar
        </button>
        <?php endif; ?>
      </div>
      <div style="padding:8px 20px 16px">
        <?php if ($exportLog): ?>
        <ul class="log-list">
          <?php foreach ($exportLog as $log): ?>
          <li class="log-item">
            <div class="log-icon"><i class="bi bi-file-earmark-arrow-down"></i></div>
            <div class="log-filtro"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $log['filtro']))) ?></div>
            <div class="log-meta">
              <?= $log['total'] ?> filas<br>
              <?= $log['fecha'] ?>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p style="padding:20px 0;text-align:center;color:var(--txt-muted);font-size:.82rem">
          <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
          Sin exportaciones en esta sesión.
        </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Info -->
    <div class="section-card">
      <div class="section-head">
        <span class="section-title"><i class="bi bi-info-circle"></i> Sobre los reportes</span>
      </div>
      <div style="padding:16px 22px;color:var(--txt-muted);font-size:.83rem;line-height:1.9">
        <p><i class="bi bi-check-circle" style="color:#48c78e;margin-right:6px"></i>Codificación <strong style="color:var(--txt)">UTF-8 con BOM</strong> para compatibilidad Excel.</p>
        <p><i class="bi bi-check-circle" style="color:#48c78e;margin-right:6px"></i>Separador configurable: <strong style="color:var(--txt)">, ; o tabulación</strong>.</p>
        <p><i class="bi bi-check-circle" style="color:#48c78e;margin-right:6px"></i>Incluye metadatos en la primera fila del CSV.</p>
        <p><i class="bi bi-check-circle" style="color:#48c78e;margin-right:6px"></i>Columnas <strong style="color:var(--txt)">seleccionables</strong> según lo que necesites.</p>
        <p><i class="bi bi-check-circle" style="color:#48c78e;margin-right:6px"></i>Vista previa antes de descargar.</p>
        <p style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
          Generado por <strong style="color:var(--accent)"><?= htmlspecialchars($_SESSION['usuario']) ?></strong>
          · <?= date('d/m/Y H:i') ?>
        </p>
      </div>
    </div>

  </div>

</main>

<!-- ── Modal de preview ──────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="previewModal">
  <div class="modal-box">
    <div class="modal-header">
      <i class="bi bi-table" style="color:var(--accent)"></i>
      <span class="modal-title" id="modalTitle">Vista previa</span>
      <span class="modal-badge" id="modalBadge">— productos</span>
      <button class="modal-close" onclick="closePreview()"><i class="bi bi-x"></i></button>
    </div>
    <div class="modal-body" id="modalBody">
      <div class="preview-loading">
        <div class="spinner"></div>
        <span>Cargando datos…</span>
      </div>
    </div>
    <div class="modal-footer">
      <span class="modal-info" id="modalInfo"></span>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn-preview" onclick="closePreview()"><i class="bi bi-x"></i> Cerrar</button>
        <a href="#" class="btn-dl btn-dl-blue" id="modalDlBtn"><i class="bi bi-download"></i> Descargar este filtro</a>
      </div>
    </div>
  </div>
</div>

<script>
// ── Panel avanzado ────────────────────────────────────────────────────────────
function toggleAdvanced() {
  const btn  = document.getElementById('advBtn');
  const body = document.getElementById('advBody');
  btn.classList.toggle('open');
  body.classList.toggle('open');
}

// ── Columnas ──────────────────────────────────────────────────────────────────
function updateColCheck(el) {
  el.closest('.col-check').classList.toggle('checked', el.checked);
}
function toggleAllCols(state) {
  document.querySelectorAll('#colsGrid input[type=checkbox]').forEach(cb => {
    cb.checked = state;
    updateColCheck(cb);
  });
}
function getSelectedCols() {
  return [...document.querySelectorAll('#colsGrid input:checked')].map(cb => cb.value).join(',');
}

// ── Exportar rango ────────────────────────────────────────────────────────────
function exportarRango() {
  const desde = document.getElementById('rangoDesde').value;
  const hasta = document.getElementById('rangoHasta').value;
  const sep   = encodeURIComponent(document.getElementById('sepSelect').value);
  const cols  = getSelectedCols();
  if (!desde && !hasta) { alert('Ingresa al menos una fecha de inicio o fin.'); return; }
  window.location.href = `exportar.php?download=1&filtro=rango&desde=${desde}&hasta=${hasta}&sep=${sep}&cols=${cols}`;
}

// Aplicar cols y sep a todos los botones de descarga
document.querySelectorAll('a[id^="dl-"]').forEach(a => {
  a.addEventListener('click', function(e) {
    const cols = getSelectedCols();
    const sep  = encodeURIComponent(document.getElementById('sepSelect').value);
    if (!cols) { e.preventDefault(); alert('Selecciona al menos una columna.'); return; }
    this.href = this.href.split('?')[0] + '?' + new URLSearchParams({
      download: 1,
      filtro:   new URLSearchParams(this.href.split('?')[1]).get('filtro'),
      cols,
      sep,
    });
  });
});

// ── Modal de preview ──────────────────────────────────────────────────────────
let currentFiltro = '';
const ESTADO_LABELS = {
  ok:         ['OK',          'estado-ok'],
  vencido:    ['Vencido',     'estado-vencido'],
  por_vencer: ['Por vencer',  'estado-por_vencer'],
  bajo_stock: ['Bajo stock',  'estado-bajo_stock'],
};

async function openPreview(filtro, titulo) {
  currentFiltro = filtro;
  document.getElementById('previewModal').classList.add('open');
  document.getElementById('modalTitle').textContent = 'Vista previa · ' + titulo;
  document.getElementById('modalBadge').textContent = '…';
  document.getElementById('modalBody').innerHTML = `
    <div class="preview-loading">
      <div class="spinner"></div>
      <span>Cargando datos…</span>
    </div>`;

  const desde = document.getElementById('rangoDesde').value;
  const hasta = document.getElementById('rangoHasta').value;
  let url = `exportar.php?preview=1&filtro=${filtro}&limit=20`;
  if (filtro === 'rango') { url += `&desde=${desde}&hasta=${hasta}`; }

  try {
    const res  = await fetch(url);
    const data = await res.json();

    document.getElementById('modalBadge').textContent = data.total + ' productos';
    document.getElementById('modalInfo').textContent  =
      'Mostrando ' + data.rows.length + ' de ' + data.total + ' productos';

    const dlBtn = document.getElementById('modalDlBtn');
    const cols  = getSelectedCols();
    const sep   = encodeURIComponent(document.getElementById('sepSelect').value);
    dlBtn.href  = `exportar.php?download=1&filtro=${filtro}&cols=${cols}&sep=${sep}` +
                  (filtro === 'rango' ? `&desde=${desde}&hasta=${hasta}` : '');

    if (!data.rows.length) {
      document.getElementById('modalBody').innerHTML =
        '<div class="preview-loading"><i class="bi bi-inbox" style="font-size:2rem"></i><span>No hay productos con este filtro.</span></div>';
      return;
    }

    const cols_visible = ['id','nombre','stock','precio_venta','ganancia_total','fecha_venc','estado'];
    const heads = ['ID','Nombre','Stock','P. Venta','Gan. Total','Vencimiento','Estado'];

    let html = '<div class="preview-table-wrap"><table class="preview-table"><thead><tr>';
    heads.forEach(h => { html += `<th>${h}</th>`; });
    html += '</tr></thead><tbody>';

    data.rows.forEach(r => {
      const [elbl, ecls] = ESTADO_LABELS[r.estado] ?? ['OK','estado-ok'];
      html += '<tr>' +
        `<td>${r.id}</td>` +
        `<td style="font-weight:600;color:var(--txt)">${r.nombre}</td>` +
        `<td>${r.stock}</td>` +
        `<td>S/ ${r.precio_venta}</td>` +
        `<td>S/ ${r.ganancia_total}</td>` +
        `<td>${r.fecha_venc}</td>` +
        `<td><span class="estado-badge ${ecls}">${elbl}</span></td>` +
        '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('modalBody').innerHTML = html;

  } catch(err) {
    document.getElementById('modalBody').innerHTML =
      '<div class="preview-loading" style="color:var(--danger)"><i class="bi bi-exclamation-triangle" style="font-size:2rem"></i><span>Error al cargar los datos.</span></div>';
  }
}

function closePreview() {
  document.getElementById('previewModal').classList.remove('open');
}

// Cerrar con Escape o clic en overlay
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePreview(); });
document.getElementById('previewModal').addEventListener('click', function(e) {
  if (e.target === this) closePreview();
});

// Animar barras al cargar
window.addEventListener('load', () => {
  document.querySelectorAll('.progress-bar-fill').forEach(el => {
    const target = el.style.width;
    el.style.width = '0';
    requestAnimationFrame(() => {
      el.style.transition = 'width 1s ease';
      el.style.width = target;
    });
  });
});
</script>

<style>
@media(max-width:700px) {
  .two-col-grid { grid-template-columns: 1fr !important; }
}
</style>
</body>
</html>