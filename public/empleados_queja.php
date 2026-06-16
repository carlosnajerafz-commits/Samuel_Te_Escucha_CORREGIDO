<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

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
            nombre ILIKE :buscar OR
            apellido_paterno ILIKE :buscar OR
            apellido_materno ILIKE :buscar OR
            correo ILIKE :buscar OR
            celular_1 ILIKE :buscar OR
            celular_2 ILIKE :buscar OR
            tipo ILIKE :buscar OR
            municipio ILIKE :buscar OR
            colonia ILIKE :buscar
        )
    ";
    $paramsBusqueda[":buscar"] = "%$buscar%";
}

/* =========================================
   MENSAJE
========================================= */
$mensaje = "";
if (isset($_GET["ok"])) {
    $mensaje = "La queja fue actualizada correctamente.";
} elseif (isset($_GET["eliminada"])) {
    $mensaje = "La queja fue eliminada correctamente.";
}

/* =========================================
   CONSULTAR QUEJAS PENDIENTES
========================================= */
$sqlQuejasPendientes = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           tipo, descripcion, evidencia_path, created_at, estatus
    FROM quejas
    WHERE estatus = 'pendiente'
      AND $condicionFecha
      $condicionBusqueda
    ORDER BY created_at DESC
";

$stmtQuejasPendientes = $pdo->prepare($sqlQuejasPendientes);
$stmtQuejasPendientes->execute($paramsBusqueda);
$quejasPendientes = $stmtQuejasPendientes->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   CONSULTAR HISTORIAL DE QUEJAS
========================================= */
$sqlHistorialQuejas = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           tipo, descripcion, evidencia_path, created_at, estatus
    FROM quejas
    WHERE estatus IN ('atendida', 'cerrada', 'completada')
      AND $condicionFecha
      $condicionBusqueda
    ORDER BY created_at DESC
";

$stmtHistorialQuejas = $pdo->prepare($sqlHistorialQuejas);
$stmtHistorialQuejas->execute($paramsBusqueda);
$historialQuejas = $stmtHistorialQuejas->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   DATOS PARA GRÁFICAS
========================================= */
$conteoTipos      = [];
$conteoMunicipios = [];
$conteoEstatus    = [
    "pendiente"  => 0,
    "atendida"   => 0,
    "cerrada"    => 0,
    "completada" => 0,
];

$todasLasQuejas = array_merge($quejasPendientes, $historialQuejas);

foreach ($todasLasQuejas as $q) {
    $tipo      = trim($q["tipo"]      ?? "");
    $municipio = trim($q["municipio"] ?? "");
    $estatus   = trim($q["estatus"]   ?? "");

    if ($tipo !== "") {
        $conteoTipos[$tipo] = ($conteoTipos[$tipo] ?? 0) + 1;
    }
    if ($municipio !== "") {
        $conteoMunicipios[$municipio] = ($conteoMunicipios[$municipio] ?? 0) + 1;
    }
    if (isset($conteoEstatus[$estatus])) {
        $conteoEstatus[$estatus]++;
    }
}

$labelsTipos      = array_keys($conteoTipos);
$valuesTipos      = array_values($conteoTipos);
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
];
$coloresJson = json_encode($colores);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Quejas | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: capitalize;
    }
    .badge--pendiente  { background:#fef9c3; color:#92400e; }
    .badge--atendida   { background:#dbeafe; color:#1e40af; }
    .badge--cerrada    { background:#f3f4f6; color:#374151; }
    .badge--completada { background:#dcfce7; color:#166534; }
    .alert-success {
      background:#dcfce7; color:#166534;
      border:1px solid #86efac;
      border-radius:10px; padding:12px 18px;
      margin-bottom:18px; font-weight:600;
    }
    .filtros-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
    .filtros-bar a {
      padding:7px 16px; border-radius:8px; font-size:13px;
      background:#f3f4f6; color:#374151; text-decoration:none;
      font-weight:600; border:1px solid #e5e7eb;
    }
    .filtros-bar a.active { background:#7A1737; color:#fff; border-color:#7A1737; }
    .graficas-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
      gap:20px; margin-top:20px;
    }
    .grafica-box {
      background:#fff; border:1px solid #e5e7eb;
      border-radius:16px; padding:18px;
    }
    .grafica-box h3 { margin-top:0; color:#7A1737; font-size:15px; }
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
        <span class="brand__sub">Panel de quejas</span>
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

<!-- ====== CONTENIDO PRINCIPAL ====== -->
<main class="wrap dashboard">

  <div class="dashboard-top">
    <div>
      <h1>Gestión de quejas</h1>
      <p style="margin:8px 0 0;color:#6b7280;">Consulta, administra y da seguimiento a las quejas ciudadanas.</p>
    </div>
    <a href="dashboard.php" class="btn btn--light">Volver al panel</a>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
  <?php endif; ?>

  <!-- Filtros de tiempo -->
  <div class="filtros-bar">
    <a href="?filtro=3dias<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === '3dias' ? 'active' : ''; ?>">Últimos 3 días</a>
    <a href="?filtro=semana<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'semana' ? 'active' : ''; ?>">Última semana</a>
    <a href="?filtro=mes<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'mes' ? 'active' : ''; ?>">Último mes</a>
    <a href="?filtro=todo<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'todo' ? 'active' : ''; ?>">Todo</a>
  </div>

  <!-- Buscador -->
  <form method="GET" style="margin-bottom:24px; display:flex; gap:10px; flex-wrap:wrap;">
    <input
      type="text"
      name="buscar"
      placeholder="Buscar por nombre, correo, celular, tipo, municipio..."
      value="<?php echo htmlspecialchars($buscar); ?>"
      style="flex:1; min-width:240px; max-width:460px; padding:10px 14px;
             border:1px solid #ccc; border-radius:10px; font-size:14px;"
    >
    <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
    <button type="submit" class="btn">Buscar</button>
    <a href="empleados_queja.php?filtro=<?php echo urlencode($filtro); ?>" class="btn btn--light">Limpiar</a>
  </form>

  <!-- ==============================
       GRÁFICAS
  ============================== -->
  <?php if (!empty($todasLasQuejas)): ?>
  <section class="dashboard-card">
    <h2>Estadísticas de quejas</h2>
    <div class="graficas-grid">

      <?php if (!empty($labelsTipos)): ?>
      <div class="grafica-box">
        <h3>Por tipo</h3>
        <canvas id="graficaTipos" height="220"></canvas>
      </div>
      <?php endif; ?>

      <?php if (!empty($labelsMunicipios)): ?>
      <div class="grafica-box">
        <h3>Por municipio</h3>
        <canvas id="graficaMunicipios" height="220"></canvas>
      </div>
      <?php endif; ?>

      <div class="grafica-box">
        <h3>Cumplimiento</h3>
        <canvas id="graficaEstatus" height="220"></canvas>
      </div>

    </div>
  </section>
  <?php endif; ?>

  <!-- ==============================
       QUEJAS PENDIENTES
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Quejas pendientes
      <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
        (<?php echo count($quejasPendientes); ?>)
      </span>
    </h2>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($quejasPendientes)): ?>
        <div class="list-item">
          <strong>No hay quejas pendientes</strong>
          <div>Cuando se registren aparecerán aquí.</div>
        </div>
      <?php else: ?>
        <?php foreach ($quejasPendientes as $queja): ?>
          <div class="list-item">
            <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: QUEJA-<?php echo str_pad((string)($queja["id"] ?? 0), 6, "0", STR_PAD_LEFT); ?>
              <span class="badge badge--pendiente" style="margin-left:8px;">Pendiente</span>
            </div>

            <strong>
              <?php echo htmlspecialchars(
                trim(($queja["nombre"] ?? "") . " " . ($queja["apellido_paterno"] ?? "") . " " . ($queja["apellido_materno"] ?? ""))
              ); ?>
            </strong>

            <div style="margin-top:6px;font-size:13px;">
              <strong style="color:#7A1737;">Tipo:</strong>
              <?php echo htmlspecialchars($queja["tipo"] ?? ""); ?>
            </div>

            <?php if (!empty($queja["celular_1"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 1: <?php echo htmlspecialchars($queja["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["celular_2"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 2: <?php echo htmlspecialchars($queja["celular_2"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["correo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              ✉️ Correo: <?php echo htmlspecialchars($queja["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["seccion_electoral"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              🗳 Sección electoral: <?php echo htmlspecialchars($queja["seccion_electoral"]); ?>
            </div>
            <?php endif; ?>

            <?php
              $direccion = trim(
                ($queja["calle"] ?? "") . " #" . ($queja["no_exterior"] ?? "") .
                (($queja["no_interior"] ?? "") !== "" ? " Int. " . $queja["no_interior"] : "") .
                ", Col. " . ($queja["colonia"] ?? "") .
                ", " . ($queja["municipio"] ?? "") .
                ", CP " . ($queja["codigo_postal"] ?? "")
              );
            ?>
            <?php if ($direccion !== " #, Col. , , CP "): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📍 Dirección: <?php echo htmlspecialchars($direccion); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["descripcion"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📝 Descripción: <?php echo htmlspecialchars($queja["descripcion"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["evidencia_path"])): ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($queja["evidencia_path"]); ?>" target="_blank" class="btn btn--light">Ver evidencia</a>
              </div>
            <?php endif; ?>

            <div style="margin-top:8px;font-size:12px;color:#9ca3af;">
              Registrada: <?php echo htmlspecialchars($queja["created_at"] ?? ""); ?>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
              <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)($queja["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="atendida">
                <button type="submit" class="btn">Marcar atendida</button>
              </form>

              <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)($queja["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="completada">
                <button type="submit" class="btn">Completar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ==============================
       HISTORIAL DE QUEJAS
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <div class="calendar-head">
      <h2>Historial de quejas
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
          (<?php echo count($historialQuejas); ?>)
        </span>
      </h2>
      <a href="exportar_quejas_excel.php" class="btn btn--light btn-exportar">Exportar a Excel</a>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($historialQuejas)): ?>
        <div class="list-item">
          <strong>Sin historial de quejas</strong>
          <div>Aún no hay quejas atendidas, cerradas o completadas.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialQuejas as $queja): ?>
          <?php $badgeClass = "badge--" . ($queja["estatus"] ?? ""); ?>
          <div class="list-item">
            <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: QUEJA-<?php echo str_pad((string)($queja["id"] ?? 0), 6, "0", STR_PAD_LEFT); ?>
              <span class="badge <?php echo htmlspecialchars($badgeClass); ?>" style="margin-left:8px;">
                <?php echo htmlspecialchars($queja["estatus"] ?? ""); ?>
              </span>
            </div>

            <strong>
              <?php echo htmlspecialchars(
                trim(($queja["nombre"] ?? "") . " " . ($queja["apellido_paterno"] ?? "") . " " . ($queja["apellido_materno"] ?? ""))
              ); ?>
            </strong>

            <div style="margin-top:6px;font-size:13px;">
              <strong style="color:#7A1737;">Tipo:</strong>
              <?php echo htmlspecialchars($queja["tipo"] ?? ""); ?>
            </div>

            <?php if (!empty($queja["celular_1"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 1: <?php echo htmlspecialchars($queja["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["celular_2"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 2: <?php echo htmlspecialchars($queja["celular_2"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["correo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              ✉️ Correo: <?php echo htmlspecialchars($queja["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["seccion_electoral"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              🗳 Sección electoral: <?php echo htmlspecialchars($queja["seccion_electoral"]); ?>
            </div>
            <?php endif; ?>

            <?php
              $direccion = trim(
                ($queja["calle"] ?? "") . " #" . ($queja["no_exterior"] ?? "") .
                (($queja["no_interior"] ?? "") !== "" ? " Int. " . $queja["no_interior"] : "") .
                ", Col. " . ($queja["colonia"] ?? "") .
                ", " . ($queja["municipio"] ?? "") .
                ", CP " . ($queja["codigo_postal"] ?? "")
              );
            ?>
            <?php if ($direccion !== " #, Col. , , CP "): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📍 Dirección: <?php echo htmlspecialchars($direccion); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["descripcion"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📝 Descripción: <?php echo htmlspecialchars($queja["descripcion"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["evidencia_path"])): ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($queja["evidencia_path"]); ?>" target="_blank" class="btn btn--light">Ver evidencia</a>
              </div>
            <?php endif; ?>

            <div style="margin-top:8px;font-size:12px;color:#9ca3af;">
              Registrada: <?php echo htmlspecialchars($queja["created_at"] ?? ""); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_queja.php" style="margin:0;"
                    onsubmit="return confirm('¿Seguro que deseas eliminar esta queja?');">
                <input type="hidden" name="id" value="<?php echo (int)($queja["id"] ?? 0); ?>">
                <button type="submit" class="btn btn--light">🗑 Eliminar queja</button>
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

const labelsTipos = <?php echo json_encode($labelsTipos, JSON_UNESCAPED_UNICODE); ?>;
const valuesTipos = <?php echo json_encode($valuesTipos); ?>;

const labelsMunicipios = <?php echo json_encode($labelsMunicipios, JSON_UNESCAPED_UNICODE); ?>;
const valuesMunicipios = <?php echo json_encode($valuesMunicipios); ?>;

const labelsEstatus = <?php echo json_encode($labelsEstatus, JSON_UNESCAPED_UNICODE); ?>;
const valuesEstatus = <?php echo json_encode($valuesEstatus); ?>;

if (document.getElementById('graficaTipos') && labelsTipos.length) {
  new Chart(document.getElementById('graficaTipos'), {
    type: 'pie',
    data: {
      labels: labelsTipos,
      datasets: [{ data: valuesTipos, backgroundColor: colores.slice(0, labelsTipos.length), borderWidth: 1 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, responsive: true }
  });
}

if (document.getElementById('graficaMunicipios') && labelsMunicipios.length) {
  new Chart(document.getElementById('graficaMunicipios'), {
    type: 'pie',
    data: {
      labels: labelsMunicipios,
      datasets: [{ data: valuesMunicipios, backgroundColor: colores.slice(0, labelsMunicipios.length), borderWidth: 1 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, responsive: true }
  });
}

if (document.getElementById('graficaEstatus')) {
  new Chart(document.getElementById('graficaEstatus'), {
    type: 'doughnut',
    data: {
      labels: labelsEstatus,
      datasets: [{ data: valuesEstatus, backgroundColor: colores.slice(0, labelsEstatus.length), borderWidth: 1 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, responsive: true }
  });
}
</script>

</body>
</html>