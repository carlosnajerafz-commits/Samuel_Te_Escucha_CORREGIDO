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
            municipio ILIKE :buscar OR
            colonia ILIKE :buscar OR
            seccion_electoral ILIKE :buscar
        )
    ";
    $paramsBusqueda[":buscar"] = "%$buscar%";
}

/* =========================================
   MENSAJE
========================================= */
$mensaje = "";
if (isset($_GET["ok"]) || isset($_GET["cita_actualizada"])) {
    $mensaje = "El registro fue actualizado correctamente.";
} elseif (isset($_GET["eliminado"])) {
    $mensaje = "El registro fue eliminado correctamente.";
}

/* =========================================
   CONSULTAR SOLICITUDES DE CITA
========================================= */
$sqlSolicitudes = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           fecha, hora, motivo, ine_path, estatus, created_at
    FROM citas
    WHERE estatus = 'solicitada'
      AND $condicionFecha
      $condicionBusqueda
    ORDER BY fecha ASC, hora ASC
";
$stmtSolicitudes = $pdo->prepare($sqlSolicitudes);
$stmtSolicitudes->execute($paramsBusqueda);
$solicitudesCita = $stmtSolicitudes->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   CONSULTAR CITAS ACEPTADAS
========================================= */
$sqlCitasAceptadas = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           fecha, hora, motivo, ine_path, estatus, created_at
    FROM citas
    WHERE estatus = 'aceptada'
      AND $condicionFecha
      $condicionBusqueda
    ORDER BY fecha ASC, hora ASC
";
$stmtCitasAceptadas = $pdo->prepare($sqlCitasAceptadas);
$stmtCitasAceptadas->execute($paramsBusqueda);
$citasAceptadas = $stmtCitasAceptadas->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   CONSULTAR HISTORIAL DE CITAS
========================================= */
$sqlHistorialCitas = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           fecha, hora, motivo, ine_path, estatus, created_at
    FROM citas
    WHERE estatus IN ('rechazada', 'realizada', 'cancelada', 'vencida')
      AND $condicionFecha
      $condicionBusqueda
    ORDER BY fecha DESC, hora DESC
";
$stmtHistorialCitas = $pdo->prepare($sqlHistorialCitas);
$stmtHistorialCitas->execute($paramsBusqueda);
$historialCitas = $stmtHistorialCitas->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   DATOS PARA GRÁFICAS
========================================= */
$conteoEstatus    = [];
$conteoMunicipios = [];
$conteoHoras      = [];

$todasLasCitas = array_merge($solicitudesCita, $citasAceptadas, $historialCitas);

foreach ($todasLasCitas as $cita) {
    $estatus   = trim($cita["estatus"]   ?? "");
    $municipio = trim($cita["municipio"] ?? "");
    $hora      = substr((string)($cita["hora"] ?? ""), 0, 5);

    if ($estatus   !== "") $conteoEstatus[$estatus]      = ($conteoEstatus[$estatus]      ?? 0) + 1;
    if ($municipio !== "") $conteoMunicipios[$municipio] = ($conteoMunicipios[$municipio] ?? 0) + 1;
    if ($hora      !== "") $conteoHoras[$hora]           = ($conteoHoras[$hora]           ?? 0) + 1;
}

$labelsEstatus    = array_keys($conteoEstatus);
$valuesEstatus    = array_values($conteoEstatus);
$labelsMunicipios = array_keys($conteoMunicipios);
$valuesMunicipios = array_values($conteoMunicipios);
$labelsHoras      = array_keys($conteoHoras);
$valuesHoras      = array_values($conteoHoras);

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
  <title>Panel de Citas | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .graficas-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px;
      margin-top: 24px;
    }
    .grafica-box {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }
    .grafica-box h3 { margin: 0 0 14px; font-size: 15px; color: #7A1737; }
    .badge {
      display: inline-block;
      padding: 2px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: capitalize;
    }
    .badge--solicitada { background:#fef9c3; color:#92400e; }
    .badge--aceptada   { background:#dcfce7; color:#166534; }
    .badge--rechazada  { background:#fee2e2; color:#991b1b; }
    .badge--realizada  { background:#dbeafe; color:#1e40af; }
    .badge--cancelada  { background:#f3f4f6; color:#374151; }
    .badge--vencida    { background:#ede9fe; color:#5b21b6; }
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
        <span class="brand__sub">Panel de citas</span>
      </div>
    </div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="empleados_cita.php">Citas</a>
      <a href="empleados_queja.php">Quejas</a>
      <a href="empleados_apoyo.php">Apoyos</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<!-- ====== CONTENIDO PRINCIPAL ====== -->
<main class="wrap dashboard">

  <?php if ($mensaje !== ""): ?>
    <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
  <?php endif; ?>

  <!-- Filtros de tiempo -->
  <div class="filtros-bar">
    <a href="?filtro=3dias<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === '3dias'  ? 'active' : ''; ?>">Últimos 3 días</a>
    <a href="?filtro=semana<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'semana' ? 'active' : ''; ?>">Esta semana</a>
    <a href="?filtro=mes<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'mes'    ? 'active' : ''; ?>">Este mes</a>
    <a href="?filtro=todo<?php echo $buscar !== "" ? "&buscar=".urlencode($buscar) : ""; ?>"
       class="<?php echo $filtro === 'todo'   ? 'active' : ''; ?>">Todo</a>
  </div>

  <!-- Buscador -->
  <form method="GET" style="margin-bottom:24px; display:flex; gap:10px; flex-wrap:wrap;">
    <input
      type="text"
      name="buscar"
      placeholder="Buscar por nombre, correo, celular, municipio..."
      value="<?php echo htmlspecialchars($buscar); ?>"
      style="flex:1; min-width:240px; max-width:460px; padding:10px 14px;
             border:1px solid #ccc; border-radius:10px; font-size:14px;"
    >
    <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
    <button type="submit" class="btn">Buscar</button>
    <a href="empleados_cita.php?filtro=<?php echo urlencode($filtro); ?>" class="btn btn--light">Limpiar</a>
  </form>

  <!-- ==============================
       GRÁFICAS
  ============================== -->
  <?php if (!empty($todasLasCitas)): ?>
  <section class="dashboard-card">
    <h2>Resumen estadístico</h2>
    <div class="graficas-grid">

      <div class="grafica-box">
        <h3>Citas por estatus</h3>
        <canvas id="graficaEstatus" height="220"></canvas>
      </div>

      <?php if (!empty($labelsMunicipios)): ?>
      <div class="grafica-box">
        <h3>Citas por municipio</h3>
        <canvas id="graficaMunicipios" height="220"></canvas>
      </div>
      <?php endif; ?>

      <?php if (!empty($labelsHoras)): ?>
      <div class="grafica-box">
        <h3>Citas por hora</h3>
        <canvas id="graficaHoras" height="220"></canvas>
      </div>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

  <!-- ==============================
       SOLICITUDES PENDIENTES
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Solicitudes de cita
      <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
        (<?php echo count($solicitudesCita); ?>)
      </span>
    </h2>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($solicitudesCita)): ?>
        <div class="list-item">
          <strong>Sin solicitudes</strong>
          <div>No hay solicitudes pendientes.</div>
        </div>
      <?php else: ?>
        <?php foreach ($solicitudesCita as $cita): ?>
          <div class="list-item">
            <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: CITA-<?php echo str_pad((string)($cita["id"] ?? 0), 6, "0", STR_PAD_LEFT); ?>
              <span class="badge badge--solicitada" style="margin-left:8px;">Solicitada</span>
            </div>

            <strong>
              <?php echo htmlspecialchars(
                trim(($cita["nombre"] ?? "") . " " . ($cita["apellido_paterno"] ?? "") . " " . ($cita["apellido_materno"] ?? ""))
              ); ?>
            </strong>

            <div style="margin-top:6px;color:#6b7280;font-size:13px;">
              📅 Fecha: <?php echo htmlspecialchars($cita["fecha"] ?? ""); ?>
              &nbsp;·&nbsp;
              🕐 Hora: <?php echo htmlspecialchars(substr((string)($cita["hora"] ?? ""), 0, 5)); ?>
            </div>

            <?php if (!empty($cita["celular_1"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 1: <?php echo htmlspecialchars($cita["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["celular_2"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 2: <?php echo htmlspecialchars($cita["celular_2"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["correo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              ✉️ Correo: <?php echo htmlspecialchars($cita["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["municipio"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📍 Municipio: <?php echo htmlspecialchars($cita["municipio"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["motivo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📝 Motivo: <?php echo htmlspecialchars($cita["motivo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["ine_path"])): ?>
              <?php $ineUrl = "/" . ltrim($cita["ine_path"], "/"); ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($ineUrl); ?>" target="_blank" class="btn btn--light">🪪 Ver INE</a>
              </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="aceptada">
                <input type="hidden" name="origen"  value="citas">
                <button type="submit" class="btn">✔ Aceptar</button>
              </form>

              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="rechazada">
                <input type="hidden" name="origen"  value="citas">
                <button type="submit" class="btn btn--light"
                        onclick="return confirm('¿Rechazar esta cita?');">✘ Rechazar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ==============================
       CITAS ACEPTADAS
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Citas aceptadas
      <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
        (<?php echo count($citasAceptadas); ?>)
      </span>
    </h2>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($citasAceptadas)): ?>
        <div class="list-item">
          <strong>Sin citas aceptadas</strong>
          <div>No hay citas aceptadas en el período seleccionado.</div>
        </div>
      <?php else: ?>
        <?php foreach ($citasAceptadas as $cita): ?>
          <div class="list-item">
            <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: CITA-<?php echo str_pad((string)($cita["id"] ?? 0), 6, "0", STR_PAD_LEFT); ?>
              <span class="badge badge--aceptada" style="margin-left:8px;">Aceptada</span>
            </div>

            <strong>
              <?php echo htmlspecialchars(
                trim(($cita["nombre"] ?? "") . " " . ($cita["apellido_paterno"] ?? "") . " " . ($cita["apellido_materno"] ?? ""))
              ); ?>
            </strong>

            <div style="margin-top:6px;color:#6b7280;font-size:13px;">
              📅 Fecha: <?php echo htmlspecialchars($cita["fecha"] ?? ""); ?>
              &nbsp;·&nbsp;
              🕐 Hora: <?php echo htmlspecialchars(substr((string)($cita["hora"] ?? ""), 0, 5)); ?>
            </div>

            <?php if (!empty($cita["celular_1"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 1: <?php echo htmlspecialchars($cita["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["celular_2"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 2: <?php echo htmlspecialchars($cita["celular_2"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["correo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              ✉️ Correo: <?php echo htmlspecialchars($cita["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["municipio"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📍 Municipio: <?php echo htmlspecialchars($cita["municipio"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["motivo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📝 Motivo: <?php echo htmlspecialchars($cita["motivo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["ine_path"])): ?>
              <?php $ineUrl = "/" . ltrim($cita["ine_path"], "/"); ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($ineUrl); ?>" target="_blank" class="btn btn--light">🪪 Ver INE</a>
              </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="realizada">
                <input type="hidden" name="origen"  value="citas">
                <button type="submit" class="btn">✔ Marcar realizada</button>
              </form>

              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="cancelada">
                <input type="hidden" name="origen"  value="citas">
                <button type="submit" class="btn btn--light"
                        onclick="return confirm('¿Cancelar esta cita?');">✘ Cancelar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ==============================
       HISTORIAL DE CITAS
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Historial de citas
      <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
        (<?php echo count($historialCitas); ?>)
      </span>
    </h2>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($historialCitas)): ?>
        <div class="list-item">
          <strong>Sin historial</strong>
          <div>No hay citas en historial para el período seleccionado.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialCitas as $cita): ?>
          <?php
            $estatusCita = $cita["estatus"] ?? "";
            $badgeClass  = "badge--" . $estatusCita;
          ?>
          <div class="list-item">
            <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: CITA-<?php echo str_pad((string)($cita["id"] ?? 0), 6, "0", STR_PAD_LEFT); ?>
              <span class="badge <?php echo htmlspecialchars($badgeClass); ?>" style="margin-left:8px;">
                <?php echo htmlspecialchars($estatusCita); ?>
              </span>
            </div>

            <strong>
              <?php echo htmlspecialchars(
                trim(($cita["nombre"] ?? "") . " " . ($cita["apellido_paterno"] ?? "") . " " . ($cita["apellido_materno"] ?? ""))
              ); ?>
            </strong>

            <div style="margin-top:6px;color:#6b7280;font-size:13px;">
              📅 Fecha: <?php echo htmlspecialchars($cita["fecha"] ?? ""); ?>
              &nbsp;·&nbsp;
              🕐 Hora: <?php echo htmlspecialchars(substr((string)($cita["hora"] ?? ""), 0, 5)); ?>
            </div>

            <?php if (!empty($cita["celular_1"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Celular 1: <?php echo htmlspecialchars($cita["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["correo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              ✉️ Correo: <?php echo htmlspecialchars($cita["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["municipio"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📍 Municipio: <?php echo htmlspecialchars($cita["municipio"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["motivo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📝 Motivo: <?php echo htmlspecialchars($cita["motivo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["ine_path"])): ?>
              <?php $ineUrl = "/" . ltrim($cita["ine_path"], "/"); ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($ineUrl); ?>" target="_blank" class="btn btn--light">🪪 Ver INE</a>
              </div>
            <?php endif; ?>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_cita.php" style="margin:0;"
                    onsubmit="return confirm('¿Eliminar esta cita del historial?');">
                <input type="hidden" name="id"    value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="origen" value="citas">
                <button type="submit" class="btn btn--light">🗑 Eliminar cita</button>
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

const labelsEstatus = <?php echo json_encode($labelsEstatus, JSON_UNESCAPED_UNICODE); ?>;
const valuesEstatus = <?php echo json_encode($valuesEstatus); ?>;

if (document.getElementById('graficaEstatus') && labelsEstatus.length) {
  new Chart(document.getElementById('graficaEstatus'), {
    type: 'pie',
    data: {
      labels: labelsEstatus,
      datasets: [{ data: valuesEstatus, backgroundColor: colores.slice(0, labelsEstatus.length), borderWidth: 1 }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } }, responsive: true }
  });
}

const labelsMunicipios = <?php echo json_encode($labelsMunicipios, JSON_UNESCAPED_UNICODE); ?>;
const valuesMunicipios = <?php echo json_encode($valuesMunicipios); ?>;

if (document.getElementById('graficaMunicipios') && labelsMunicipios.length) {
  new Chart(document.getElementById('graficaMunicipios'), {
    type: 'pie',
    data: {
      labels: labelsMunicipios,
      datasets: [{ data: valuesMunicipios, backgroundColor: colores.slice(0, labelsMunicipios.length), borderWidth: 1 }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } }, responsive: true }
  });
}

const labelsHoras = <?php echo json_encode($labelsHoras, JSON_UNESCAPED_UNICODE); ?>;
const valuesHoras  = <?php echo json_encode($valuesHoras); ?>;

if (document.getElementById('graficaHoras') && labelsHoras.length) {
  new Chart(document.getElementById('graficaHoras'), {
    type: 'bar',
    data: {
      labels: labelsHoras,
      datasets: [{ label: 'Citas', data: valuesHoras, backgroundColor: 'rgba(122,23,55,0.75)', borderRadius: 6 }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { ticks: { font: { size: 12 } } } },
      responsive: true
    }
  });
}
</script>


