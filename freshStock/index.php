<?php
require 'auth.php';
require 'conexion.php';

$hoy = date('Y-m-d');

// ── KPIs ──────────────────────────────────────────────────────────────────────
$kpis = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN stock <= stock_min THEN 1 ELSE 0 END) AS bajo_stock,
        SUM(CASE WHEN fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END) AS vencidos,
        SUM(CASE WHEN fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS por_vencer,
        ROUND(SUM(stock * precio_compra), 2) AS valor_inventario,
        ROUND(SUM((precio_venta - precio_compra) * stock), 2) AS ganancia_estimada
    FROM productos
")->fetch();

// ── Productos vencidos ────────────────────────────────────────────────────────
$vencidos = $pdo->query("
    SELECT * FROM productos
    WHERE fecha_vencimiento < CURDATE()
    ORDER BY fecha_vencimiento ASC
")->fetchAll();

// ── Productos por vencer (próximos 30 días) ───────────────────────────────────
$porVencer = $pdo->query("
    SELECT *,
           DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
    FROM productos
    WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY fecha_vencimiento ASC
")->fetchAll();

// ── Bajo stock ────────────────────────────────────────────────────────────────
$bajoStock = $pdo->query("
    SELECT * FROM productos
    WHERE stock <= stock_min AND fecha_vencimiento >= CURDATE()
    ORDER BY stock ASC
")->fetchAll();

// ── Top productos por ganancia ────────────────────────────────────────────────
$topGanancia = $pdo->query("
    SELECT *,
           ROUND((precio_venta - precio_compra) * stock, 2) AS ganancia_total,
           ROUND(precio_venta - precio_compra, 2) AS ganancia_unit
    FROM productos
    WHERE fecha_vencimiento >= CURDATE()
    ORDER BY ganancia_total DESC
    LIMIT 5
")->fetchAll();

// ── Datos para gráficos ──────────────────────────────────────────────────────
$grafGanancias = $pdo->query("
    SELECT 
        nombre,
        ROUND((precio_venta - precio_compra) * stock, 2) AS ganancia_total
    FROM productos
    ORDER BY ganancia_total DESC
    LIMIT 8
")->fetchAll();

$grafEstados = [
    'ok' => max(0, (int)$kpis['total'] - (int)$kpis['vencidos'] - (int)$kpis['por_vencer']),
    'vencidos' => (int)$kpis['vencidos'],
    'por_vencer' => (int)$kpis['por_vencer']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FreshStock · Dashboard</title>
<link rel="stylesheet" href="css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- ── SIDEBAR ───────────────────────────────────────────────────────────── -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-box"></i></div>
    <div>
      <div class="brand-name">FreshStock</div>
      <div class="brand-sub">Inventario</div>
    </div>
  </div>

  <span class="nav-section">Menú</span>
  <a href="index.php"     class="nav-link active"><span class="icon"><i class="bi bi-graph-up"></i></span> Dashboard</a>
  <a href="productos.php" class="nav-link"><span class="icon"><i class="bi bi-box"></i></span> Productos</a>
  <a href="exportar.php"  class="nav-link"><span class="icon"><i class="bi bi-download"></i></span> Exportar CSV</a>

  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="user-role" style="cursor:pointer;transition:.2s" title="Clic para configurar contraseña" onclick="window.location.href='setup_password.php'">Administrador</div>
      </div>
      <form id="logout-form" action="logout.php" method="POST" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="logout-btn" title="Cerrar sesión" style="border:none;background:none;cursor:pointer;padding:0;">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </form>
    </div>
  </div>
</aside>

<!-- ── MAIN ──────────────────────────────────────────────────────────────── -->
<main class="main">
  <div class="page-header">
    <h1 class="page-title"><i class="bi bi-graph-up"></i> Dashboard</h1>
    <p class="page-subtitle">Resumen del inventario al <?= date('d/m/Y') ?></p>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card green">
      <div class="kpi-icon"><i class="bi bi-box"></i></div>
      <div class="kpi-value"><?= $kpis['total'] ?></div>
      <div class="kpi-label">Total Productos</div>
    </div>
    <div class="kpi-card blue">
      <div class="kpi-icon"><i class="bi bi-graph-down"></i></div>
      <div class="kpi-value"><?= $kpis['bajo_stock'] ?></div>
      <div class="kpi-label">Bajo Stock</div>
    </div>
    <div class="kpi-card red">
      <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="kpi-value"><?= $kpis['vencidos'] ?></div>
      <div class="kpi-label">Vencidos</div>
    </div>
    <div class="kpi-card amber">
      <div class="kpi-icon"><i class="bi bi-exclamation-octagon"></i></div>
      <div class="kpi-value"><?= $kpis['por_vencer'] ?></div>
      <div class="kpi-label">Por Vencer</div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-icon"><i class="bi bi-wallet2"></i></div>
      <div class="kpi-value">S/ <?= number_format($kpis['valor_inventario'], 2) ?></div>
      <div class="kpi-label">Valor Inventario</div>
    </div>
    <div class="kpi-card amber">
      <div class="kpi-icon"><i class="bi bi-graph-up"></i></div>
      <div class="kpi-value">S/ <?= number_format($kpis['ganancia_estimada'], 2) ?></div>
      <div class="kpi-label">Ganancia Estimada</div>
    </div>
  </div>
    <!-- Productos por vencer -->
    <div class="section-card">
      <div class="section-head">
        <span class="section-title"><i class="bi bi-exclamation-octagon"></i> Por Vencer (30 días)</span>
        <span class="badge badge-warn"><?= count($porVencer) ?></span>
      </div>
      <div class="tbl-wrap">
        <?php if (empty($porVencer)): ?>
          <div class="empty-state"><div class="empty-icon"><i class="bi bi-check-circle" style="font-size: 2.5rem;"></i></div><div class="empty-text">¡Sin productos próximos a vencer!</div></div>
        <?php else: ?>
        <table>
          <thead>
            <tr><th>Producto</th><th>Vencimiento</th><th>Días</th><th>Stock</th></tr>
          </thead>
          <tbody>
            <?php foreach ($porVencer as $p): ?>
            <tr>
              <td class="product-name"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= fmtFecha($p['fecha_vencimiento']) ?></td>
              <td>
                <span class="badge <?= $p['dias_restantes'] <= 7 ? 'badge-danger' : 'badge-warn' ?>">
                  <?= $p['dias_restantes'] ?> d
                </span>
              </td>
              <td><?= $p['stock'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
      <!-- Gráficos Dashboard -->
  <div style="display:grid;grid-template-columns:1.4fr .8fr;gap:20px;margin-bottom:24px">
    <div class="section-card">
      <div class="section-head">
        <span class="section-title"><i class="bi bi-bar-chart"></i> Ganancias por Producto</span>
      </div>
      <div style="padding:22px;height:280px">
        <canvas id="chartGanancias"></canvas>
      </div>
    </div>

    <div class="section-card">
      <div class="section-head">
        <span class="section-title"><i class="bi bi-pie-chart"></i> Estado de Productos</span>
      </div>
      <div style="padding:22px;height:280px">
        <canvas id="chartEstados"></canvas>
      </div>
    </div>
  </div>

    <!-- Top ganancias -->
    <div class="section-card">
      <div class="section-head">
        <span class="section-title"><i class="bi bi-graph-up"></i> Top Ganancias Estimadas</span>
        <a href="exportar.php" class="btn btn-ghost btn-sm">Exportar</a>
      </div>
      <div class="tbl-wrap">
        <?php if (empty($topGanancia)): ?>
          <div class="empty-state"><div class="empty-icon"><i class="bi bi-box" style="font-size: 2.5rem;"></i></div><div class="empty-text">Sin productos registrados.</div></div>
        <?php else: ?>
        <table>
          <thead>
            <tr><th>Producto</th><th>Unit.</th><th>Stock</th><th>Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($topGanancia as $p): ?>
            <tr>
              <td class="product-name"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="price price-gain">+S/ <?= number_format($p['ganancia_unit'], 2) ?></span></td>
              <td><?= $p['stock'] ?></td>
              <td><span class="price price-sell">S/ <?= number_format($p['ganancia_total'], 2) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /grid -->

  <!-- Tabla vencidos -->
  <?php if (!empty($vencidos)): ?>
  <div class="section-card" style="margin-top:20px">
    <div class="section-head">
      <span class="section-title"><i class="bi bi-exclamation-triangle"></i> Productos Vencidos</span>
      <span class="badge badge-danger"><?= count($vencidos) ?> crítico(s)</span>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr><th>Producto</th><th>Stock</th><th>Vencimiento</th><th>Pérdida estimada</th><th>Acción</th></tr>
        </thead>
        <tbody>
          <?php foreach ($vencidos as $p): ?>
          <?php $perdida = ($p['precio_compra'] * $p['stock']); ?>
          <tr>
            <td class="product-name"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $p['stock'] ?></td>
            <td><span class="badge badge-danger"><?= fmtFecha($p['fecha_vencimiento']) ?></span></td>
            <td style="color:var(--danger)">-S/ <?= number_format($perdida, 2) ?></td>
            <td>
              <a href="productos.php?editar=<?= $p['id'] ?>" class="btn btn-warn-ghost btn-sm"><i class="bi bi-pencil"></i> Editar</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</main>
<script>
const gananciasLabels = <?= json_encode(array_column($grafGanancias, 'nombre')) ?>;
const gananciasData = <?= json_encode(array_map('floatval', array_column($grafGanancias, 'ganancia_total'))) ?>;

const estadosLabels = ['OK', 'Vencidos', 'Por vencer'];
const estadosData = [
  <?= (int)$grafEstados['ok'] ?>,
  <?= (int)$grafEstados['vencidos'] ?>,
  <?= (int)$grafEstados['por_vencer'] ?>
];

new Chart(document.getElementById('chartGanancias'), {
  type: 'bar',
  data: {
    labels: gananciasLabels,
    datasets: [{
      data: gananciasData,
      backgroundColor: gananciasData.map(v => v >= 0 ? 'rgba(34,197,94,.75)' : 'rgba(239,68,68,.75)'),
      borderColor: gananciasData.map(v => v >= 0 ? 'rgb(34,197,94)' : 'rgb(239,68,68)'),
      borderWidth: 1,
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { ticks: { callback: value => 'S/ ' + value } }
    }
  }
});

new Chart(document.getElementById('chartEstados'), {
  type: 'doughnut',
  data: {
    labels: estadosLabels,
    datasets: [{
      data: estadosData,
      backgroundColor: [
        'rgba(34,197,94,.85)',
        'rgba(239,68,68,.85)',
        'rgba(245,158,11,.85)'
      ],
      borderWidth: 3
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: {
        position: 'bottom'
      }
    }
  }
});

// Anti-cache
const perfEntries = performance.getEntriesByType("navigation");
if (perfEntries.length > 0 && perfEntries[0].type === "back_forward") {
  window.location.reload(true);
}

window.addEventListener('pageshow', function(event) {
  if (event.persisted) {
    window.location.reload(true);
  }
});
</script>
</body>
</html>
