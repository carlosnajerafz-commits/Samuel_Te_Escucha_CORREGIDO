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
$quejasPendientes = $stmtQuejasPendientes->fetchAll(PDO::FETCH_ASSOC);
if (!$quejasPendientes) {
    $quejasPendientes = [];
}

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
$historialQuejas = $stmtHistorialQuejas->fetchAll(PDO::FETCH_ASSOC);
if (!$historialQuejas) {
    $historialQuejas = [];
}

/* =========================================
   DATOS PARA GRÁFICAS
========================================= */
$conteoTipos = [];
$conteoMunicipios = [];
$conteoEstatus = [
    "pendiente" => 0,
    "atendida" => 0,
    "cerrada" => 0,
    "completada" => 0
];

$todasLasQuejas = array_merge($quejasPendientes, $historialQuejas);

foreach ($todasLasQuejas as $q) {
    $tipo = trim($q["tipo"] ?? "");
    $municipio = trim($q["municipio"] ?? "");
    $estatus = trim($q["estatus"] ?? "");

    if ($tipo !== "") {
        if (!isset($conteoTipos[$tipo])) {
            $conteoTipos[$tipo] = 0;
        }
        $conteoTipos[$tipo]++;
    }

    if ($municipio !== "") {
        if (!isset($conteoMunicipios[$municipio])) {
            $conteoMunicipios[$municipio] = 0;
        }
        $conteoMunicipios[$municipio]++;
    }

    if (isset($conteoEstatus[$estatus])) {
        $conteoEstatus[$estatus]++;
    }
}

$labelsTipos = array_keys($conteoTipos);
$valuesTipos = array_values($conteoTipos);

$labelsMunicipios = array_keys($conteoMunicipios);
$valuesMunicipios = array_values($conteoMunicipios);

$labelsEstatus = array_keys($conteoEstatus);
$valuesEstatus = array_values($conteoEstatus);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Quejas | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
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
<form method="GET" style="margin:15px 0 20px; display:flex; gap:10px; flex-wrap:wrap;">
  <input 
    type="text" 
    name="buscar" 
    placeholder="Buscar por nombre, correo, celular, tipo, municipio..."
    value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>"
    style="width:100%; max-width:420px; padding:10px 14px; border:1px solid #ccc; border-radius:10px;"
  >

  <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">

  <button type="submit" class="btn">Buscar</button>

  <a href="empleados_queja.php?filtro=<?php echo urlencode($filtro); ?>" class="btn btn--light">
    Limpiar
  </a>
</form>
<main class="wrap dashboard">
  <div class="dashboard-top">
    <div>
      <h1>Gestión de quejas</h1>
      <p style="margin:8px 0 0;color:#6b7280;">Consulta, administra y da seguimiento a las quejas ciudadanas.</p>
    </div>
    <a href="dashboard.php" class="btn btn--light">Volver al panel</a>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div style="background:#e8f5e9;color:#1b5e20;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-weight:700;">
      <?php echo htmlspecialchars($mensaje); ?>
    </div>
  <?php endif; ?>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:18px 0 24px;">
    <a href="empleados_queja.php?filtro=3dias" class="btn <?php echo $filtro === '3dias' ? '' : 'btn--light'; ?>">Últimos 3 días</a>
    <a href="empleados_queja.php?filtro=semana" class="btn <?php echo $filtro === 'semana' ? '' : 'btn--light'; ?>">Última semana</a>
    <a href="empleados_queja.php?filtro=mes" class="btn <?php echo $filtro === 'mes' ? '' : 'btn--light'; ?>">Último mes</a>
    <a href="empleados_queja.php?filtro=todo" class="btn <?php echo $filtro === 'todo' ? '' : 'btn--light'; ?>">Todo</a>
  </div>

  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Estadísticas de quejas</h2>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px;">
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
        <h3 style="margin-top:0;color:#7A1737;">Por tipo</h3>
        <canvas id="graficaTipos"></canvas>
      </div>

      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
        <h3 style="margin-top:0;color:#7A1737;">Por municipio</h3>
        <canvas id="graficaMunicipios"></canvas>
      </div>

      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
        <h3 style="margin-top:0;color:#7A1737;">Cumplimiento</h3>
        <canvas id="graficaEstatus"></canvas>
      </div>
    </div>
  </section>

  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Quejas pendientes</h2>
    <div class="list" style="margin-top:18px;">
      <?php if (empty($quejasPendientes)): ?>
        <div class="list-item">
          <strong>No hay quejas pendientes</strong>
          <div>Cuando se registren aparecerán aquí.</div>
        </div>
      <?php else: ?>
        <?php foreach ($quejasPendientes as $queja): ?>
          <div class="list-item">
            <strong>
              <?php
                echo htmlspecialchars(
                  ($queja["nombre"] ?? "") . " " .
                  ($queja["apellido_paterno"] ?? "") . " " .
                  ($queja["apellido_materno"] ?? "")
                );
              ?>
            </strong>

            <div style="margin-top:6px;">
              <strong style="display:inline;color:#7A1737;">Tipo:</strong>
              <?php echo htmlspecialchars($queja["tipo"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 1: <?php echo htmlspecialchars($queja["celular_1"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 2: <?php echo htmlspecialchars($queja["celular_2"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($queja["correo"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Sección electoral: <?php echo htmlspecialchars($queja["seccion_electoral"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Dirección:
              <?php
                echo htmlspecialchars(
                  ($queja["calle"] ?? "") . " #" . ($queja["no_exterior"] ?? "") .
                  (($queja["no_interior"] ?? "") !== "" ? " Int. " . $queja["no_interior"] : "") .
                  ", Col. " . ($queja["colonia"] ?? "") .
                  ", " . ($queja["municipio"] ?? "") .
                  ", CP " . ($queja["codigo_postal"] ?? "")
                );
              ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Descripción: <?php echo htmlspecialchars($queja["descripcion"] ?? ""); ?>
            </div>

            <?php if (!empty($queja["evidencia_path"])): ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($queja["evidencia_path"]); ?>" target="_blank" class="btn btn--light">Ver evidencia</a>
              </div>
            <?php endif; ?>

            <div style="margin-top:8px;font-size:13px;color:#9ca3af;">
              Registrada: <?php echo htmlspecialchars($queja["created_at"] ?? ""); ?>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
              <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)($queja["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="atendida">
                <button type="submit" class="btn">Marcar atendida</button>
              </form>

             

              <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)($queja["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="completada">
                <button type="submit" class="btn">Completar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="dashboard-card" style="margin-top:22px;">
    <div class="calendar-head">
  <h2>Historial de quejas</h2>

  <a href="exportar_quejas_excel.php" class="btn btn--light btn-exportar">
    Exportar a Excel
  </a>
</div>
    <div class="list" style="margin-top:18px;">
      <?php if (empty($historialQuejas)): ?>
        <section class="dashboard-card" style="margin-top:22px;">
    
        <div class="list-item">
          <strong>Sin historial de quejas</strong>
          <div>Aún no hay quejas atendidas, cerradas o completadas.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialQuejas as $queja): ?>
          <div class="list-item">
            <strong>
              <?php
                echo htmlspecialchars(
                  ($queja["nombre"] ?? "") . " " .
                  ($queja["apellido_paterno"] ?? "") . " " .
                  ($queja["apellido_materno"] ?? "")
                );
              ?>
            </strong>

            <div style="margin-top:6px;">
              <strong style="display:inline;color:#7A1737;">Tipo:</strong>
              <?php echo htmlspecialchars($queja["tipo"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 1: <?php echo htmlspecialchars($queja["celular_1"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 2: <?php echo htmlspecialchars($queja["celular_2"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($queja["correo"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Sección electoral: <?php echo htmlspecialchars($queja["seccion_electoral"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Dirección:
              <?php
                echo htmlspecialchars(
                  ($queja["calle"] ?? "") . " #" . ($queja["no_exterior"] ?? "") .
                  (($queja["no_interior"] ?? "") !== "" ? " Int. " . $queja["no_interior"] : "") .
                  ", Col. " . ($queja["colonia"] ?? "") .
                  ", " . ($queja["municipio"] ?? "") .
                  ", CP " . ($queja["codigo_postal"] ?? "")
                );
              ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Descripción: <?php echo htmlspecialchars($queja["descripcion"] ?? ""); ?>
            </div>

            <?php if (!empty($queja["evidencia_path"])): ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($queja["evidencia_path"]); ?>" target="_blank" class="btn btn--light">Ver evidencia</a>
              </div>
            <?php endif; ?>

            <div style="margin-top:8px;">
              <strong style="display:inline;color:#7A1737;">Estatus:</strong>
              <?php echo htmlspecialchars($queja["estatus"] ?? ""); ?>
            </div>

            <div style="margin-top:8px;font-size:13px;color:#9ca3af;">
              Registrada: <?php echo htmlspecialchars($queja["created_at"] ?? ""); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_queja.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta queja?');">
                <input type="hidden" name="id" value="<?php echo (int)($queja["id"] ?? 0); ?>">
                <button type="submit" class="btn btn--light">Eliminar queja</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer class="footer">
  <div class="wrap footer__inner">
    <div>© 2026 Samuel Te Escucha</div>
    <div>Panel interno</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labelsTipos = <?php echo json_encode($labelsTipos, JSON_UNESCAPED_UNICODE); ?>;
const valuesTipos = <?php echo json_encode($valuesTipos); ?>;

const labelsMunicipios = <?php echo json_encode($labelsMunicipios, JSON_UNESCAPED_UNICODE); ?>;
const valuesMunicipios = <?php echo json_encode($valuesMunicipios); ?>;

const labelsEstatus = <?php echo json_encode($labelsEstatus, JSON_UNESCAPED_UNICODE); ?>;
const valuesEstatus = <?php echo json_encode($valuesEstatus); ?>;

new Chart(document.getElementById('graficaTipos'), {
  type: 'pie',
  data: {
    labels: labelsTipos,
    datasets: [{
      data: valuesTipos
    }]
  }
});

new Chart(document.getElementById('graficaMunicipios'), {
  type: 'pie',
  data: {
    labels: labelsMunicipios,
    datasets: [{
      data: valuesMunicipios
    }]
  }
});

new Chart(document.getElementById('graficaEstatus'), {
  type: 'pie',
  data: {
    labels: labelsEstatus,
    datasets: [{
      data: valuesEstatus
    }]
  }
});
</script>

</body>
</html>