<?php
require 'auth.php';
require 'conexion.php';

$flash   = '';
$flashType = 'ok';
$editData  = null;

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $flash = '<i class="bi bi-trash"></i> Producto eliminado correctamente.';
}

// ── GUARDAR (INSERT o UPDATE) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $nombre         = trim($_POST['nombre']         ?? '');
    $stock          = (int)($_POST['stock']          ?? 0);
    $stock_min      = (int)($_POST['stock_min']      ?? 0);
    $precio_compra  = (float)($_POST['precio_compra']  ?? 0);
    $precio_venta   = (float)($_POST['precio_venta']   ?? 0);
    $fecha_venc     = trim($_POST['fecha_vencimiento'] ?? '');

    // Validaciones
    $errores = [];
    if ($nombre === '')              $errores[] = 'El nombre es obligatorio.';
    if ($stock < 0)                  $errores[] = 'El stock no puede ser negativo.';
    if ($stock_min < 0)              $errores[] = 'El stock mínimo no puede ser negativo.';
    if ($precio_compra <= 0)         $errores[] = 'El precio de compra debe ser mayor a 0.';
    if ($precio_venta <= 0)          $errores[] = 'El precio de venta debe ser mayor a 0.';
    if ($precio_venta < $precio_compra) $errores[] = 'El precio de venta no puede ser menor al de compra.';
    if ($fecha_venc === '')          $errores[] = 'La fecha de vencimiento es obligatoria.';

    if (!empty($errores)) {
        $flash     = '<i class="bi bi-exclamation-triangle"></i> ' . implode(' | ', $errores);
        $flashType = 'error';
        // Re-poblar formulario
        $editData = $_POST;
    } else {
        if ($id) {
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE productos SET
                    nombre = ?, stock = ?, stock_min = ?,
                    precio_compra = ?, precio_venta = ?, fecha_vencimiento = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $stock, $stock_min, $precio_compra, $precio_venta, $fecha_venc, $id]);
            $flash = '<i class="bi bi-check-circle"></i> Producto actualizado correctamente.';
        } else {
            // Insertar
            $stmt = $pdo->prepare("
                INSERT INTO productos (nombre, stock, stock_min, precio_compra, precio_venta, fecha_vencimiento)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $stock, $stock_min, $precio_compra, $precio_venta, $fecha_venc]);
            $flash = '<i class="bi bi-check-circle"></i> Producto registrado correctamente.';
        }
    }
}

// ── CARGAR PARA EDITAR ────────────────────────────────────────────────────────
if (isset($_GET['editar']) && is_numeric($_GET['editar']) && $editData === null) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $editData = $stmt->fetch();
}

// ── LISTAR / FILTRAR ─────────────────────────────────────────────────────────
$filtro = trim($_GET['q'] ?? '');
$estadoFiltro = trim($_GET['estado'] ?? '');
$stockFiltro = trim($_GET['stock_filtro'] ?? '');

$stmt = $pdo->query("SELECT * FROM productos ORDER BY fecha_vencimiento ASC");
$productos = $stmt->fetchAll();

// Filtro por nombre
if ($filtro !== '') {
    $productos = array_filter($productos, function ($p) use ($filtro) {
        return stripos($p['nombre'], $filtro) !== false;
    });
}

// Filtro por estado
if ($estadoFiltro !== '') {
    $productos = array_filter($productos, function ($p) use ($estadoFiltro) {
        $estado = estadoProducto($p['stock'], $p['stock_min'], $p['fecha_vencimiento']);
        return $estado === $estadoFiltro;
    });
}

// Filtro solo por stock
if ($stockFiltro !== '') {
    $productos = array_filter($productos, function ($p) use ($stockFiltro) {
        if ($stockFiltro === 'bajo') {
            return (int)$p['stock'] <= (int)$p['stock_min'];
        }

        if ($stockFiltro === 'normal') {
            return (int)$p['stock'] > (int)$p['stock_min'];
        }

        return true;
    });
}

$productos = array_values($productos);
// ── PAGINACIÓN ─────────────────────────────────────────
$porPagina = 5;
$totalProductos = count($productos);
$totalPaginas = ceil($totalProductos / $porPagina);

$paginaActual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($paginaActual < 1) $paginaActual = 1;
if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;

$inicio = ($paginaActual - 1) * $porPagina;
$productos = array_slice($productos, $inicio, $porPagina);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FreshStock · Productos</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ── SIDEBAR ── -->
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
  <a href="productos.php" class="nav-link active"><span class="icon"><i class="bi bi-box"></i></span> Productos</a>
  <a href="exportar.php"  class="nav-link"><span class="icon"><i class="bi bi-download"></i></span> Exportar CSV</a>
  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="user-role" style="cursor:pointer;transition:.2s" title="Clic para configurar contraseña" onclick="window.location.href='setup_password.php'">Administrador</div>
      </div>
      <a href="logout.php" class="logout-btn" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
</aside>

<!-- ── MAIN ── -->
<main class="main">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-box"></i> Gestión de Productos</h1>
    <p class="page-subtitle">Registra, edita y controla el inventario de perecibles</p>
  </div>

  <?php if ($flash): ?>
  <div class="flash flash-<?= $flashType ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <!-- ── FORMULARIO ADD / EDIT ── -->
  <div class="section-card" style="margin-bottom:24px">
    <div class="section-head">
      <span class="section-title">
        <?= $editData ? '<i class="bi bi-pencil"></i> Editar Producto' : '<i class="bi bi-plus-circle"></i> Nuevo Producto' ?>
      </span>
      <?php if ($editData): ?>
      <a href="productos.php" class="btn btn-ghost btn-sm"><i class="bi bi-x"></i> Cancelar</a>
      <?php endif; ?>
    </div>

    <form method="POST" action="productos.php">
      <?php if ($editData && isset($editData['id'])): ?>
      <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group" style="grid-column: span 2">
          <label for="nombre">Nombre del Producto</label>
          <input type="text" id="nombre" name="nombre" placeholder="Ej. Yogurt Fresa 1L"
                 value="<?= htmlspecialchars($editData['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="100">
        </div>

        <div class="form-group">
          <label for="stock">Stock Actual</label>
          <input type="number" id="stock" name="stock" min="0" placeholder="0"
                 value="<?= htmlspecialchars($editData['stock'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="form-group">
          <label for="stock_min">Stock Mínimo</label>
          <input type="number" id="stock_min" name="stock_min" min="0" placeholder="0"
                 value="<?= htmlspecialchars($editData['stock_min'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="form-group">
          <label for="precio_compra">Precio Compra (S/)</label>
          <input type="number" id="precio_compra" name="precio_compra" step="0.01" min="0.01" placeholder="0.00"
                 value="<?= htmlspecialchars($editData['precio_compra'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="precio_venta">Precio Venta (S/)</label>
          <input type="number" id="precio_venta" name="precio_venta" step="0.01" min="0.01" placeholder="0.00"
                 value="<?= htmlspecialchars($editData['precio_venta'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="fecha_vencimiento">Fecha de Vencimiento</label>
          <input type="date" id="fecha_vencimiento" name="fecha_vencimiento"
                 value="<?= htmlspecialchars($editData['fecha_vencimiento'] ?? '') ?>" required>
        </div>
      </div>

      <!-- Preview ganancia en tiempo real -->
      <div style="padding: 0 24px 16px">
        <div class="stat-chip" id="ganancia-preview" style="font-size:.8rem">
          <i class="bi bi-graph-up"></i> Margen: <span id="margen-val">—</span> &nbsp;|&nbsp; Ganancia por unidad: S/ <span id="gain-unit">—</span>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <?= $editData ? '<i class="bi bi-download"></i> Actualizar Producto' : '<i class="bi bi-plus-circle"></i> Registrar Producto' ?>
        </button>
        <?php if (!$editData): ?>
        <button type="reset" class="btn btn-ghost"><i class="bi bi-eraser"></i> Limpiar</button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- ── FILTRO ── -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:12px">
    <form method="GET" action="productos.php" style="display:flex;gap:8px;flex:1;max-width:620px">
      <input 
        type="text" 
        name="q" 
        placeholder="Buscar producto..." 
        value="<?= htmlspecialchars($filtro) ?>" 
        style="flex:1"
      >

      <select name="estado" style="max-width:180px;min-height:42px" onchange="this.form.submit()">
        <option value="">Todos los estados</option>
        <option value="ok" <?= $estadoFiltro === 'ok' ? 'selected' : '' ?>>Correcto</option>
        <option value="por_vencer" <?= $estadoFiltro === 'por_vencer' ? 'selected' : '' ?>>Por vencer</option>
        <option value="vencido" <?= $estadoFiltro === 'vencido' ? 'selected' : '' ?>>Vencido</option>
      </select>

      <select name="stock_filtro" style="max-width:180px;min-height:42px" onchange="this.form.submit()">
        <option value="">Todo el stock</option>
        <option value="bajo" <?= $stockFiltro === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
        <option value="normal" <?= $stockFiltro === 'normal' ? 'selected' : '' ?>>Stock normal</option>
      </select>

      <button type="submit" class="btn btn-ghost">
        <i class="bi bi-search"></i> Buscar
      </button>

      <?php if ($filtro || $estadoFiltro || $stockFiltro): ?>
        <a href="productos.php" class="btn btn-ghost">
          <i class="bi bi-x"></i>
        </a>
      <?php endif; ?>
    </form>
    <div class="stats-row">
      <span class="stat-chip"><i class="bi bi-box"></i> <?= count($productos) ?> producto(s)</span>
      <a href="exportar.php" class="btn btn-ghost btn-sm"><i class="bi bi-download"></i> Exportar CSV</a>
    </div>
  </div>

  <!-- ── TABLA ── -->
  <div class="section-card">
    <div class="tbl-wrap">
      <?php if (empty($productos)): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="bi bi-box" style="font-size: 2.5rem;"></i></div>
          <div class="empty-text">
            No hay productos registrados
            <?= $filtro ? ' para "' . htmlspecialchars($filtro) . '"' : '' ?>
            <?= $estadoFiltro ? ' con el estado seleccionado' : '' ?>
            <?= $stockFiltro ? ' con el filtro de stock seleccionado' : '' ?>.
          </div>
        </div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Producto</th>
            <th>Stock</th>
            <th>Mín.</th>
            <th>P. Compra</th>
            <th>P. Venta</th>
            <th>Ganancia</th>
            <th>Vencimiento</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productos as $p):
            $estado    = estadoProducto($p['stock'], $p['stock_min'], $p['fecha_vencimiento']);
            $ganUnit   = $p['precio_venta'] - $p['precio_compra'];
            $ganTotal  = $ganUnit * $p['stock'];
            $diasDiff  = (int)(new DateTime())->diff(new DateTime($p['fecha_vencimiento']))->format('%r%a');
          ?>
          <tr>
            <td style="color:var(--txt-muted);font-size:.8rem"><?= $p['id'] ?></td>
            <td class="product-name"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <strong><?= $p['stock'] ?></strong>
              <?php if ($p['stock'] <= $p['stock_min']): ?>
              <span style="color:var(--info);font-size:.75rem"> ↓</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--txt-muted)"><?= $p['stock_min'] ?></td>
            <td><span class="price price-buy">S/ <?= number_format($p['precio_compra'], 2) ?></span></td>
            <td><span class="price price-sell">S/ <?= number_format($p['precio_venta'], 2) ?></span></td>
            <td>
              <span class="price price-gain">+S/ <?= number_format($ganUnit, 2) ?></span>
              <div style="font-size:.72rem;color:var(--txt-muted)">Total: S/ <?= number_format($ganTotal, 2) ?></div>
            </td>
            <td>
              <?= fmtFecha($p['fecha_vencimiento']) ?>
              <?php if ($diasDiff >= 0 && $diasDiff <= 30): ?>
              <div style="font-size:.72rem;color:var(--warn)"><?= $diasDiff ?> días</div>
              <?php elseif ($diasDiff < 0): ?>
              <div style="font-size:.72rem;color:var(--danger)"><?= abs($diasDiff) ?> d. vencido</div>
              <?php endif; ?>
            </td>
            <td><?= badgeEstado($estado) ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <a href="productos.php?editar=<?= $p['id'] ?>" class="btn btn-warn-ghost btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                <a href="productos.php?eliminar=<?= $p['id'] ?>"
                   class="btn btn-danger-ghost btn-sm"
                   title="Eliminar"
                   onclick="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($p['nombre'])) ?>»? Esta acción no se puede deshacer.')">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
<!-- 🔽 PEGAR AQUÍ LA PAGINACIÓN -->
<?php if ($totalPaginas > 1): ?>
<div style="display:flex;justify-content:center;gap:6px;padding:16px">

  <?php if ($paginaActual > 1): ?>
    <a class="btn btn-ghost btn-sm" href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaActual - 1])) ?>">
      ←
    </a>
  <?php endif; ?>

  <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
    <a class="btn btn-sm <?= $i == $paginaActual ? 'btn-primary' : 'btn-ghost' ?>"
       href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
       <?= $i ?>
    </a>
  <?php endfor; ?>

  <?php if ($paginaActual < $totalPaginas): ?>
    <a class="btn btn-ghost btn-sm" href="?<?= http_build_query(array_merge($_GET, ['page' => $paginaActual + 1])) ?>">
      →
    </a>
  <?php endif; ?>

</div>
<?php endif; ?>
<!-- 🔼 FIN -->

</div> <!-- section-card -->
  </div>

</main>

<script>
// Preview de ganancia en tiempo real
const compraI = document.getElementById('precio_compra');
const ventaI  = document.getElementById('precio_venta');
const stockI  = document.getElementById('stock');
const margenV = document.getElementById('margen-val');
const gainU   = document.getElementById('gain-unit');

function actualizarPreview() {
  const c = parseFloat(compraI.value) || 0;
  const v = parseFloat(ventaI.value)  || 0;
  if (c > 0 && v > 0) {
    const diff   = v - c;
    const margen = ((diff / c) * 100).toFixed(1);
    margenV.textContent = margen + '%';
    gainU.textContent   = diff.toFixed(2);
    margenV.style.color = diff >= 0 ? 'var(--accent)' : 'var(--danger)';
  } else {
    margenV.textContent = '—';
    gainU.textContent   = '—';
  }
}
// FRANCOTIRADOR ANTI-CACHE: Detecta si el usuario usó el botón "Atrás"
  
  // 1. Método moderno (Performance Navigation API)
  const perfEntries = performance.getEntriesByType("navigation");
  if (perfEntries.length > 0 && perfEntries[0].type === "back_forward") {
      // Si detecta que volvimos por el historial, fuerza la recarga desde el servidor
      window.location.reload(true);
  }

  // 2. Método de respaldo (Para Safari y móviles que congelan pestañas)
  window.addEventListener('pageshow', function(event) {
      if (event.persisted) {
          window.location.reload(true);
      }
  });
[compraI, ventaI, stockI].forEach(el => el && el.addEventListener('input', actualizarPreview));
actualizarPreview();
</script>
</body>
</html>
