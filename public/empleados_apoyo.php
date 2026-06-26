<?php
require_once "includes/session.php";
require_once "includes/security_headers.php";
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "db.php";

require_auth();

/* =========================================
   FILTRO DE TIEMPO
========================================= */
$filtro = $_GET["filtro"] ?? "mes";

switch ($filtro) {
    case "3dias":
        $condicionFecha = "created_at >= NOW() - INTERVAL '3 days'";
        break;
    case "semana":
        $condicionFecha = "created_at >= NOW() - INTERVAL '7 days'";
        break;
    case "mes":
        $condicionFecha = "created_at >= NOW() - INTERVAL '1 month'";
        break;
    default:
        $condicionFecha = "1=1";
        $filtro = "todo";
        break;
}

/* =========================================
   BÚSQUEDA
========================================= */
$buscar = trim($_GET["buscar"] ?? "");
$condicionBusqueda = "";
$paramsBusqueda = [];

if ($buscar !== "") {
    $condicionBusqueda = "
        AND (
            apoyo            ILIKE :buscar OR
            nombre           ILIKE :buscar OR
            apellido_paterno ILIKE :buscar OR
            apellido_materno ILIKE :buscar OR
            correo           ILIKE :buscar OR
            celular_1        ILIKE :buscar OR
            celular_2        ILIKE :buscar OR
            municipio        ILIKE :buscar OR
            colonia          ILIKE :buscar
        )
    ";
    $paramsBusqueda[":buscar"] = "%$buscar%";
}

/* =========================================
   MENSAJE
========================================= */
$mensaje = "";
if (isset($_GET["ok"])) {
    $mensaje = "El registro fue actualizado correctamente.";
} elseif (isset($_GET["eliminado"])) {
    $mensaje = "El registro fue eliminado correctamente.";
} elseif (isset($_GET["apoyo_eliminado"])) {
    $mensaje = "El apoyo fue eliminado correctamente.";
}

/* =========================================
   CONSULTAR APOYOS ACTIVOS
========================================= */
$sqlApoyos = "
    SELECT id, nombre, descripcion, activo, created_at
    FROM apoyos
    WHERE activo = TRUE
    ORDER BY created_at DESC
";
$apoyosActivos = $pdo->query($sqlApoyos)->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   CONSULTAR REGISTROS DE APOYOS
========================================= */
$sqlRegistrosApoyos = "
    SELECT id, apoyo, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           observaciones, estatus, created_at, ine_path
    FROM registros_apoyos
    WHERE $condicionFecha
      $condicionBusqueda
    ORDER BY created_at DESC
";

$stmtRegistros = $pdo->prepare($sqlRegistrosApoyos);
$stmtRegistros->execute($paramsBusqueda);
$registrosApoyos = $stmtRegistros->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   DATOS PARA GRÁFICAS
========================================= */
$conteoApoyos     = [];
$conteoMunicipios = [];
$conteoEstatus    = [];

foreach ($registrosApoyos as $r) {
    $apoyo     = trim($r["apoyo"]     ?? "");
    $municipio = trim($r["municipio"] ?? "");
    $estatus   = trim($r["estatus"]   ?? "");

    if ($apoyo     !== "") $conteoApoyos[$apoyo]         = ($conteoApoyos[$apoyo]         ?? 0) + 1;
    if ($municipio !== "") $conteoMunicipios[$municipio] = ($conteoMunicipios[$municipio] ?? 0) + 1;
    if ($estatus   !== "") $conteoEstatus[$estatus]      = ($conteoEstatus[$estatus]      ?? 0) + 1;
}

arsort($conteoApoyos);
arsort($conteoMunicipios);

$labelsApoyos     = array_keys($conteoApoyos);
$valuesApoyos     = array_values($conteoApoyos);
$labelsMunicipios = array_keys($conteoMunicipios);
$valuesMunicipios = array_values($conteoMunicipios);
$labelsEstatus    = array_keys($conteoEstatus);
$valuesEstatus    = array_values($conteoEstatus);

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
];
$coloresJson = json_encode($colores);

/* Totales para tarjetas KPI */
$totalRegistros  = count($registrosApoyos);
$totalEntregados = $conteoEstatus["entregado"] ?? 0;
$totalPendientes = $conteoEstatus["pendiente"] ?? 0;
$totalApoyosTipo = count($apoyosActivos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Apoyos | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    /* ---- KPI cards ---- */
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
    .kpi-card__num {
      font-size: 32px;
      font-weight: 800;
      color: #7A1737;
      line-height: 1;
    }
    .kpi-card__label {
      font-size: 12px;
      color: #6b7280;
      margin-top: 6px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    /* ---- badges ---- */
    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: capitalize;
    }
    .badge--entregado  { background:#dcfce7; color:#166534; }
    .badge--pendiente  { background:#fef9c3; color:#92400e; }
    .badge--cancelado  { background:#fee2e2; color:#991b1b; }
    .badge--proceso    { background:#dbeafe; color:#1e40af; }

    /* ---- filtros ---- */
    .filtros-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
    .filtros-bar a {
      padding:7px 16px; border-radius:8px; font-size:13px;
      background:#f3f4f6; color:#374151; text-decoration:none;
      font-weight:600; border:1px solid #e5e7eb; transition: background .15s;
    }
    .filtros-bar a.active,
    .filtros-bar a:hover { background:#7A1737; color:#fff; border-color:#7A1737; }

    /* ---- gráficas ---- */
    .graficas-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    .grafica-box {
      background:#fff; border:1px solid #e5e7eb;
      border-radius:16px; padding:18px;
    }
    .grafica-box h3 { margin:0 0 14px; color:#7A1737; font-size:14px; }

    /* ---- alert ---- */
    .alert-success {
      background:#dcfce7; color:#166534; border:1px solid #86efac;
      border-radius:10px; padding:12px 18px; margin-bottom:18px; font-weight:600;
    }

    /* ---- apoyo chip ---- */
    .apoyo-chip {
      display:inline-block; background:#fdf2f5; color:#7A1737;
      border:1px solid #f0c2cd; border-radius:8px;
      font-size:12px; font-weight:700; padding:2px 10px; margin-top:6px;
    }

    /* ---- tabla de apoyos dados de alta ---- */
    .apoyos-table {
      width:100%; border-collapse:collapse; margin-top:14px; font-size:14px;
    }
    .apoyos-table th {
      text-align:left; padding:10px 14px;
      background:#fdf2f5; color:#7A1737; font-weight:700;
      border-bottom:2px solid #f0c2cd;
    }
    .apoyos-table td {
      padding:10px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle;
    }
    .apoyos-table tr:last-child td { border-bottom:none; }
    .apoyos-table tr:hover td { background:#fafafa; }

    @media (max-width:600px) {
      .apoyos-table thead { display:none; }
      .apoyos-table tr { display:block; margin-bottom:12px;
        border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
      .apoyos-table td { display:block; padding:8px 14px; }
    }
  </style>
</head>
<body>

<!-- ====== TOPBAR ====== -->
<header class="topbar">
  <div class="wrap topbar__inner">
    <div class="brand">
      <div class="brand__logo">
        <img src="assets/img/logo.png" alt="Samuel Te Escucha">
      </div>
      <div>
        <h2>Samuel Te Escucha</h2>
        <span class="brand__sub">Panel de apoyos</span>
      </div>
    </div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="empleados_queja.php">Quejas</a>
      <a href="empleados_apoyo.php">Apoyos</a>
      <a href="empleados_cita.php">Citas</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<!-- ====== MAIN ====== -->
<main class="wrap dashboard">

  <div class="dashboard-top">
    <div>
      <h1>Gestión de apoyos</h1>
      <p style="margin:8px 0 0;color:#6b7280;">Consulta apoyos dados de alta y solicitudes registradas.</p>
    </div>
    <a href="dashboard.php" class="btn btn--light">Volver al panel</a>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
  <?php endif; ?>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo $totalApoyosTipo; ?></div>
      <div class="kpi-card__label">Tipos de apoyo activos</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo $totalRegistros; ?></div>
      <div class="kpi-card__label">Solicitudes en período</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num" style="color:#166534;"><?php echo $totalEntregados; ?></div>
      <div class="kpi-card__label">Entregados</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num" style="color:#92400e;"><?php echo $totalPendientes; ?></div>
      <div class="kpi-card__label">Pendientes</div>
    </div>
  </div>

  <!-- Filtros de tiempo -->
  <div class="filtros-bar">
    <a href="?filtro=3dias<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === '3dias'  ? 'active' : ''; ?>">Últimos 3 días</a>
    <a href="?filtro=semana<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'semana' ? 'active' : ''; ?>">Última semana</a>
    <a href="?filtro=mes<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'mes'    ? 'active' : ''; ?>">Último mes</a>
    <a href="?filtro=todo<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'todo'   ? 'active' : ''; ?>">Todo</a>
  </div>

  <!-- Buscador -->
  <form method="GET" style="margin-bottom:24px; display:flex; gap:10px; flex-wrap:wrap;">
    <input
      type="text"
      name="buscar"
      placeholder="Buscar por apoyo, nombre, correo, celular, municipio..."
      value="<?php echo htmlspecialchars($buscar); ?>"
      style="flex:1; min-width:240px; max-width:460px; padding:10px 14px;
             border:1px solid #ccc; border-radius:10px; font-size:14px;"
    >
    <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
    <button type="submit" class="btn">Buscar</button>
    <a href="empleados_apoyo.php?filtro=<?php echo urlencode($filtro); ?>" class="btn btn--light">Limpiar</a>
  </form>

  <!-- ==============================
       APOYOS DADOS DE ALTA
  ============================== -->
  <section class="dashboard-card">
    <div class="calendar-head">
      <h2>Apoyos dados de alta
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
          (<?php echo count($apoyosActivos); ?>)
        </span>
      </h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="alta_apoyo.php" class="btn">+ Dar de alta apoyo</a>
        <a href="exportar_apoyos_excel.php" class="btn btn--light">Exportar a Excel</a>
      </div>
    </div>

    <?php if (empty($apoyosActivos)): ?>
      <div class="list-item" style="margin-top:14px;">
        <strong>Sin apoyos</strong>
        <div>No hay apoyos registrados.</div>
      </div>
    <?php else: ?>
      <table class="apoyos-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre del apoyo</th>
            <th>Descripción</th>
            <th>Registrado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($apoyosActivos as $i => $apoyo): ?>
            <tr>
              <td style="color:#9ca3af;font-size:12px;"><?php echo $i + 1; ?></td>
              <td><strong><?php echo htmlspecialchars($apoyo["nombre"] ?? ""); ?></strong></td>
              <td style="color:#6b7280;">
                <?php echo htmlspecialchars($apoyo["descripcion"] ?? "—"); ?>
              </td>
              <td style="color:#9ca3af;font-size:12px;white-space:nowrap;">
                <?php echo htmlspecialchars($apoyo["created_at"] ?? ""); ?>
              </td>
              <td>
                <form method="POST" action="eliminar_apoyo.php" style="margin:0;"
                      onsubmit="return confirm('¿Seguro que deseas eliminar este apoyo?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)($apoyo["id"] ?? 0); ?>">
                  <button type="submit" class="btn btn--light" style="font-size:12px;padding:5px 12px;">
                    🗑 Eliminar
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <!-- ==============================
       GRÁFICAS
  ============================== -->
  <?php if (!empty($registrosApoyos)): ?>
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Estadísticas del período</h2>
    <div class="graficas-grid">

      <?php if (!empty($labelsApoyos)): ?>
      <div class="grafica-box">
        <h3>📦 Apoyos más solicitados</h3>
        <canvas id="graficaApoyos" height="220"></canvas>
      </div>
      <?php endif; ?>

      <?php if (!empty($labelsMunicipios)): ?>
      <div class="grafica-box">
        <h3>📍 Por municipio</h3>
        <canvas id="graficaMunicipios" height="220"></canvas>
      </div>
      <?php endif; ?>

      <?php if (!empty($labelsEstatus)): ?>
      <div class="grafica-box">
        <h3>✅ Por estatus</h3>
        <canvas id="graficaEstatus" height="220"></canvas>
      </div>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

  <!-- ==============================
       REGISTROS DE APOYOS
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <div class="calendar-head">
      <h2>Solicitudes de apoyos
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
          (<?php echo count($registrosApoyos); ?>)
        </span>
      </h2>
      <a href="exportar_apoyos_excel.php" class="btn btn--light">Exportar a Excel</a>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($registrosApoyos)): ?>
        <div class="list-item">
          <strong>Sin registros</strong>
          <div>Aún no hay personas registradas en apoyos para este período.</div>
        </div>
      <?php else: ?>
        <?php foreach ($registrosApoyos as $r): ?>
          <?php
            $estatusR   = $r["estatus"] ?? "";
            $badgeClass = match(strtolower($estatusR)) {
                "entregado"  => "badge--entregado",
                "pendiente"  => "badge--pendiente",
                "cancelado"  => "badge--cancelado",
                "en proceso" => "badge--proceso",
                default      => "badge--pendiente",
            };
          ?>
          <div class="list-item">

            <!-- Folio + badge -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;">
              <span style="font-size:13px;font-weight:800;color:#7A1737;">
                Folio: APOYO-<?php echo str_pad((string)($r["id"] ?? 0), 6, "0", STR_PAD_LEFT); ?>
              </span>
              <span class="badge <?php echo $badgeClass; ?>">
                <?php echo htmlspecialchars($estatusR); ?>
              </span>
            </div>

            <!-- Nombre -->
            <strong style="font-size:15px;">
              <?php echo htmlspecialchars(
                trim(($r["nombre"] ?? "") . " " . ($r["apellido_paterno"] ?? "") . " " . ($r["apellido_materno"] ?? ""))
              ); ?>
            </strong>

            <!-- Tipo de apoyo -->
            <div>
              <span class="apoyo-chip"><?php echo htmlspecialchars($r["apoyo"] ?? ""); ?></span>
            </div>

            <?php if (!empty($r["celular_1"])): ?>
            <div style="margin-top:6px;color:#6b7280;font-size:13px;">
              📞 Celular 1: <?php echo htmlspecialchars($r["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["celular_2"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 2: <?php echo htmlspecialchars($r["celular_2"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["correo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              ✉️ Correo: <?php echo htmlspecialchars($r["correo"]); ?>
            </div>
            <?php endif; ?>


            <?php
              $dir = trim(
                ($r["calle"] ?? "") . " #" . ($r["no_exterior"] ?? "") .
                (($r["no_interior"] ?? "") !== "" ? " Int. " . $r["no_interior"] : "") .
                ", Col. " . ($r["colonia"] ?? "") .
                ", " . ($r["municipio"] ?? "") .
                ", CP " . ($r["codigo_postal"] ?? "")
              );
            ?>
            <?php if (!empty(trim($r["calle"] ?? ""))): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📍 Dirección: <?php echo htmlspecialchars($dir); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["observaciones"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📝 Observaciones: <?php echo htmlspecialchars($r["observaciones"]); ?>
            </div>
            <?php endif; ?>

           <?php if (!empty($r["ine_path"])): ?>
  <?php $ineUrl = "/" . ltrim($r["ine_path"], "/"); ?>
  <div style="margin-top:10px;">
    <a href="<?php echo htmlspecialchars($ineUrl); ?>" target="_blank" class="btn btn--light">🪪 Ver INE</a>
  </div>
<?php endif; ?>

            <div style="margin-top:8px;font-size:12px;color:#9ca3af;">
              Registrado: <?php echo htmlspecialchars($r["created_at"] ?? ""); ?>
            </div>

            <div style="margin-top:14px;">
              <form method="POST" action="eliminar_registro_apoyo.php" style="margin:0;"
                    onsubmit="return confirm('¿Seguro que deseas eliminar este registro?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)($r["id"] ?? 0); ?>">
                <button type="submit" class="btn btn--light">🗑 Eliminar registro</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</main>

<!-- ====== FOOTER ====== -->
<footer class="footer">
  <div class="wrap footer__inner">
    <div>© 2026 Samuel Te Escucha</div>
    <div>Panel interno</div>
  </div>
</footer>

<!-- ====== SCRIPTS ====== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const colores = <?php echo $coloresJson; ?>;

const labelsApoyos = <?php echo json_encode($labelsApoyos, JSON_UNESCAPED_UNICODE); ?>;
const valuesApoyos = <?php echo json_encode($valuesApoyos); ?>;

const labelsMunicipios = <?php echo json_encode($labelsMunicipios, JSON_UNESCAPED_UNICODE); ?>;
const valuesMunicipios = <?php echo json_encode($valuesMunicipios); ?>;

const labelsEstatus = <?php echo json_encode($labelsEstatus, JSON_UNESCAPED_UNICODE); ?>;
const valuesEstatus = <?php echo json_encode($valuesEstatus); ?>;

const chartOpts = (legendPos = 'bottom') => ({
  plugins: { legend: { position: legendPos, labels: { font: { size: 12 }, padding: 14 } } },
  responsive: true
});

if (document.getElementById('graficaApoyos') && labelsApoyos.length) {
  new Chart(document.getElementById('graficaApoyos'), {
    type: 'bar',
    data: {
      labels: labelsApoyos,
      datasets: [{
        label: 'Solicitudes',
        data: valuesApoyos,
        backgroundColor: colores.slice(0, labelsApoyos.length),
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

if (document.getElementById('graficaMunicipios') && labelsMunicipios.length) {
  new Chart(document.getElementById('graficaMunicipios'), {
    type: 'pie',
    data: {
      labels: labelsMunicipios,
      datasets: [{ data: valuesMunicipios, backgroundColor: colores.slice(0, labelsMunicipios.length), borderWidth: 1 }]
    },
    options: chartOpts()
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
</script>

</body>
</html>