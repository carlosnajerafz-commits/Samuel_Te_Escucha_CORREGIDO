<?php
require_once "includes/session.php";
require_once "includes/security_headers.php";
require_once "includes/auth.php";
require_once "db.php";

require_auth();

try {
    $pdo->exec("ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS categoria_vulnerable VARCHAR(150) DEFAULT ''");
    $pdo->exec("ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS menu TEXT DEFAULT ''");
} catch (PDOException $e) { /* ignorar */ }

/* =========================================
   FILTRO DE TIEMPO
========================================= */
$filtro = $_GET["filtro"] ?? "mes";

switch ($filtro) {
    case "3dias":
        $condicionFecha = "r.created_at >= NOW() - INTERVAL '3 days'";
        break;
    case "semana":
        $condicionFecha = "r.created_at >= NOW() - INTERVAL '7 days'";
        break;
    case "mes":
        $condicionFecha = "r.created_at >= NOW() - INTERVAL '1 month'";
        break;
    default:
        $condicionFecha = "1=1";
        $filtro = "todo";
        break;
}

/* =========================================
   REGISTROS DEL PERÍODO
========================================= */
try {
    $sql = "
        SELECT r.numero_personas, r.categoria_vulnerable, r.municipio, r.estatus,
               e.fecha, e.lugar
        FROM registros_comedor r
        LEFT JOIN eventos_comedor e ON e.id = r.evento_id
        WHERE $condicionFecha
    ";
    $registros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $sql = "
        SELECT r.numero_personas, r.municipio, r.estatus,
               e.fecha, e.lugar
        FROM registros_comedor r
        LEFT JOIN eventos_comedor e ON e.id = r.evento_id
        WHERE $condicionFecha
    ";
    $registros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($registros as &$row) $row["categoria_vulnerable"] = "";
    unset($row);
}

/* =========================================
   KPIs Y DATOS PARA GRÁFICAS
========================================= */
$conteoCategoria = [];
$conteoEstatus    = [];
$conteoMunicipio  = [];
$conteoEvento     = [];

$totalPersonas    = 0;
$totalAsistio     = 0;
$totalPendiente   = 0;

foreach ($registros as $r) {
    $categoria = trim($r["categoria_vulnerable"] ?? "");
    $estatus   = trim($r["estatus"] ?? "");
    $municipio = trim($r["municipio"] ?? "");
    $personas  = (int)($r["numero_personas"] ?? 0);

    if ($categoria !== "") $conteoCategoria[$categoria] = ($conteoCategoria[$categoria] ?? 0) + 1;
    if ($estatus   !== "") $conteoEstatus[$estatus]     = ($conteoEstatus[$estatus]     ?? 0) + 1;
    if ($municipio !== "") $conteoMunicipio[$municipio] = ($conteoMunicipio[$municipio] ?? 0) + 1;

    if (!empty($r["fecha"])) {
        $etiquetaEvento = date("d/m/Y", strtotime($r["fecha"]));
        $conteoEvento[$etiquetaEvento] = ($conteoEvento[$etiquetaEvento] ?? 0) + $personas;
    }

    $totalPersonas += $personas;
    if ($estatus === "asistio")   $totalAsistio   += $personas;
    if ($estatus === "pendiente") $totalPendiente += 1;
}

arsort($conteoCategoria);
arsort($conteoMunicipio);
ksort($conteoEvento);

$labelsCategoria = array_keys($conteoCategoria);
$valuesCategoria = array_values($conteoCategoria);
$labelsEstatus    = array_keys($conteoEstatus);
$valuesEstatus    = array_values($conteoEstatus);
$labelsMunicipio  = array_keys($conteoMunicipio);
$valuesMunicipio  = array_values($conteoMunicipio);
$labelsEvento     = array_keys($conteoEvento);
$valuesEvento     = array_values($conteoEvento);

$colores = [
    'rgba(122,23,55,0.85)',
    'rgba(220,53,69,0.85)',
    'rgba(255,159,64,0.85)',
    'rgba(54,162,235,0.85)',
    'rgba(75,192,192,0.85)',
    'rgba(153,102,255,0.85)',
    'rgba(255,205,86,0.85)',
    'rgba(231,76,60,0.85)',
    'rgba(46,204,113,0.85)',
    'rgba(52,152,219,0.85)',
    'rgba(241,196,15,0.85)',
    'rgba(149,165,166,0.85)',
    'rgba(26,188,156,0.85)',
];
$coloresJson = json_encode($colores);

$totalRegistros   = count($registros);
$totalEventosProximos = (int)$pdo->query("SELECT COUNT(*) FROM eventos_comedor WHERE activo = TRUE AND fecha >= CURRENT_DATE")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comedor Solidario — Estadísticas | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 16px;
      margin: 20px 0 24px;
    }
    .kpi-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 18px 20px;
      text-align: center;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .kpi-card__num { font-size: 32px; font-weight: 800; color: #7A1737; line-height: 1; }
    .kpi-card__label {
      font-size: 12px; color: #6b7280; margin-top: 6px; font-weight: 600;
      text-transform: uppercase; letter-spacing: .04em;
    }
    .filtros-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
    .filtros-bar a {
      padding:7px 16px; border-radius:8px; font-size:13px;
      background:#f3f4f6; color:#374151; text-decoration:none;
      font-weight:600; border:1px solid #e5e7eb; transition: background .15s;
    }
    .filtros-bar a.active,
    .filtros-bar a:hover { background:#7A1737; color:#fff; border-color:#7A1737; }
    .graficas-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    .grafica-box { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:18px; }
    .grafica-box h3 { margin:0 0 14px; color:#7A1737; font-size:14px; }
  </style>
</head>
<body>

<header class="topbar">
  <div class="wrap topbar__inner">
    <div class="brand">
      <div class="brand__logo">
        <img src="assets/img/logo.png" alt="Samuel Te Escucha">
      </div>
      <div>
        <h2>Samuel Te Escucha</h2>
        <span class="brand__sub">Panel de personal</span>
      </div>
    </div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="empleados_comedor.php">Registros</a>
      <a href="admin_comedor.php">Comedor</a>
      <a href="comedor_estadisticas.php" style="color:#7A1737;font-weight:700;">Estadísticas</a>
      <a href="exportar_comedor_excel.php">Exportar</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell">
  <div class="page-header">
    <h1>Comedor Solidario — Estadísticas</h1>
    <p>Datos y gráficas de las inscripciones al Comedor Solidario.</p>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo $totalEventosProximos; ?></div>
      <div class="kpi-card__label">Eventos próximos</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo $totalRegistros; ?></div>
      <div class="kpi-card__label">Registros en período</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo $totalPersonas; ?></div>
      <div class="kpi-card__label">Personas registradas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num" style="color:#1e40af;"><?php echo $totalAsistio; ?></div>
      <div class="kpi-card__label">Asistieron</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num" style="color:#92400e;"><?php echo $totalPendiente; ?></div>
      <div class="kpi-card__label">Pendientes</div>
    </div>
  </div>

  <!-- Filtros de tiempo -->
  <div class="filtros-bar">
    <a href="?filtro=3dias"  class="<?php echo $filtro === '3dias'  ? 'active' : ''; ?>">Últimos 3 días</a>
    <a href="?filtro=semana" class="<?php echo $filtro === 'semana' ? 'active' : ''; ?>">Última semana</a>
    <a href="?filtro=mes"    class="<?php echo $filtro === 'mes'    ? 'active' : ''; ?>">Último mes</a>
    <a href="?filtro=todo"   class="<?php echo $filtro === 'todo'   ? 'active' : ''; ?>">Todo</a>
  </div>

  <?php if (empty($registros)): ?>
    <div class="list-item">
      <strong>Sin datos</strong>
      <div>No hay registros del Comedor Solidario para el período seleccionado.</div>
    </div>
  <?php else: ?>
  <section class="dashboard-card">
    <h2>Gráficas del período</h2>
    <div class="graficas-grid">

      <?php if (!empty($labelsCategoria)): ?>
      <div class="grafica-box">
        <h3>🏷️ Por grupo vulnerable</h3>
        <canvas id="graficaCategoria" height="220"></canvas>
      </div>
      <?php endif; ?>

      <?php if (!empty($labelsEstatus)): ?>
      <div class="grafica-box">
        <h3>✅ Por estatus</h3>
        <canvas id="graficaEstatus" height="220"></canvas>
      </div>
      <?php endif; ?>

      <?php if (!empty($labelsEvento)): ?>
      <div class="grafica-box">
        <h3>📅 Personas por fecha de evento</h3>
        <canvas id="graficaEvento" height="220"></canvas>
      </div>
      <?php endif; ?>

      <?php if (!empty($labelsMunicipio)): ?>
      <div class="grafica-box">
        <h3>📍 Por municipio</h3>
        <canvas id="graficaMunicipio" height="220"></canvas>
      </div>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

</main>

<footer class="footer">
  <div class="wrap footer__inner">
    <div>© 2026 Samuel Te Escucha</div>
    <div>Panel interno</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const colores = <?php echo $coloresJson; ?>;

const labelsCategoria = <?php echo json_encode($labelsCategoria, JSON_UNESCAPED_UNICODE); ?>;
const valuesCategoria = <?php echo json_encode($valuesCategoria); ?>;

const labelsEstatus = <?php echo json_encode($labelsEstatus, JSON_UNESCAPED_UNICODE); ?>;
const valuesEstatus = <?php echo json_encode($valuesEstatus); ?>;

const labelsEvento = <?php echo json_encode($labelsEvento, JSON_UNESCAPED_UNICODE); ?>;
const valuesEvento = <?php echo json_encode($valuesEvento); ?>;

const labelsMunicipio = <?php echo json_encode($labelsMunicipio, JSON_UNESCAPED_UNICODE); ?>;
const valuesMunicipio = <?php echo json_encode($valuesMunicipio); ?>;

const chartOpts = (legendPos = 'bottom') => ({
  plugins: { legend: { position: legendPos, labels: { font: { size: 12 }, padding: 14 } } },
  responsive: true
});

if (document.getElementById('graficaCategoria') && labelsCategoria.length) {
  new Chart(document.getElementById('graficaCategoria'), {
    type: 'bar',
    data: {
      labels: labelsCategoria,
      datasets: [{
        label: 'Registros',
        data: valuesCategoria,
        backgroundColor: colores.slice(0, labelsCategoria.length),
        borderRadius: 6,
        borderSkipped: false
      }]
    },
    options: {
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } },
      responsive: true
    }
  });
}

if (document.getElementById('graficaEstatus') && labelsEstatus.length) {
  new Chart(document.getElementById('graficaEstatus'), {
    type: 'doughnut',
    data: {
      labels: labelsEstatus,
      datasets: [{ data: valuesEstatus, backgroundColor: colores.slice(0, labelsEstatus.length), borderWidth: 1 }]
    },
    options: chartOpts()
  });
}

if (document.getElementById('graficaEvento') && labelsEvento.length) {
  new Chart(document.getElementById('graficaEvento'), {
    type: 'bar',
    data: {
      labels: labelsEvento,
      datasets: [{
        label: 'Personas registradas',
        data: valuesEvento,
        backgroundColor: 'rgba(122,23,55,0.85)',
        borderRadius: 6,
        borderSkipped: false
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
      responsive: true
    }
  });
}

if (document.getElementById('graficaMunicipio') && labelsMunicipio.length) {
  new Chart(document.getElementById('graficaMunicipio'), {
    type: 'pie',
    data: {
      labels: labelsMunicipio,
      datasets: [{ data: valuesMunicipio, backgroundColor: colores.slice(0, labelsMunicipio.length), borderWidth: 1 }]
    },
    options: chartOpts()
  });
}
</script>

</body>
</html>
