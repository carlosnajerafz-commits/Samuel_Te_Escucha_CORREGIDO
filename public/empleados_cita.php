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
            apoyo ILIKE :buscar OR
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
if (isset($_GET["ok"])) {
    $mensaje = "El registro fue actualizado correctamente.";
} elseif (isset($_GET["eliminado"])) {
    $mensaje = "El registro fue eliminado correctamente.";
} elseif (isset($_GET["apoyo_eliminado"])) {
    $mensaje = "El apoyo fue eliminado correctamente.";
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
   DATOS PARA GRÁFICAS DE CITAS
========================================= */
$conteoEstatus = [];
$conteoMunicipios = [];
$conteoHoras = [];

$todasLasCitas = array_merge($solicitudesCita, $citasAceptadas, $historialCitas);

foreach ($todasLasCitas as $cita) {
    $estatus = trim($cita["estatus"] ?? "");
    $municipio = trim($cita["municipio"] ?? "");
    $hora = substr((string)($cita["hora"] ?? ""), 0, 5);

    if ($estatus !== "") {
        $conteoEstatus[$estatus] = ($conteoEstatus[$estatus] ?? 0) + 1;
    }

    if ($municipio !== "") {
        $conteoMunicipios[$municipio] = ($conteoMunicipios[$municipio] ?? 0) + 1;
    }

    if ($hora !== "") {
        $conteoHoras[$hora] = ($conteoHoras[$hora] ?? 0) + 1;
    }
}

$labelsEstatus = array_keys($conteoEstatus);
$valuesEstatus = array_values($conteoEstatus);

$labelsMunicipios = array_keys($conteoMunicipios);
$valuesMunicipios = array_values($conteoMunicipios);

$labelsHoras = array_keys($conteoHoras);
$valuesHoras = array_values($conteoHoras);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Citas | Samuel Te Escucha</title>
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

<form method="GET" style="margin:15px 0 20px; display:flex; gap:10px; flex-wrap:wrap;">
  <input 
    type="text" 
    name="buscar" 
   placeholder="Buscar por nombre, correo, celular, municipio..."
    value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>"
    style="width:100%; max-width:420px; padding:10px 14px; border:1px solid #ccc; border-radius:10px;"
  >

  <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">

  <button type="submit" class="btn">Buscar</button>

  <a href="empleados_cita.php?filtro=<?php echo urlencode($filtro); ?>" class="btn btn--light">
    Limpiar
  </a>
</form>


  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Solicitudes de cita</h2>
    <div class="list" style="margin-top:18px;">
      <?php if (empty($solicitudesCita)): ?>
        <div class="list-item">
          <strong>Sin solicitudes</strong>
          <div>No hay solicitudes pendientes.</div>
        </div>
      <?php else: ?>
        <?php foreach ($solicitudesCita as $cita): ?>
          <div class="list-item">
            <strong>
              <?php echo htmlspecialchars(($cita["nombre"] ?? "") . " " . ($cita["apellido_paterno"] ?? "") . " " . ($cita["apellido_materno"] ?? "")); ?>
            </strong>

            <div style="margin-top:6px;color:#6b7280;">
              Fecha: <?php echo htmlspecialchars($cita["fecha"] ?? ""); ?> · Hora: <?php echo htmlspecialchars(substr((string)($cita["hora"] ?? ""), 0, 5)); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 1: <?php echo htmlspecialchars($cita["celular_1"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 2: <?php echo htmlspecialchars($cita["celular_2"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($cita["correo"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Dirección:
              <?php
                echo htmlspecialchars(
                  ($cita["calle"] ?? "") . " #" . ($cita["no_exterior"] ?? "") .
                  (($cita["no_interior"] ?? "") !== "" ? " Int. " . $cita["no_interior"] : "") .
                  ", Col. " . ($cita["colonia"] ?? "") .
                  ", " . ($cita["municipio"] ?? "") .
                  ", CP " . ($cita["codigo_postal"] ?? "")
                );
              ?>
            </div>

            <?php if (!empty($cita["motivo"])): ?>
              <div style="margin-top:4px;color:#6b7280;">
                Motivo: <?php echo htmlspecialchars($cita["motivo"]); ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($cita["ine_path"])): ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($cita["ine_path"]); ?>" target="_blank" class="btn btn--light">Ver INE</a>
              </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="aceptada">
                <button type="submit" class="btn">Aceptar</button>
              </form>

              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="rechazada">
                <button type="submit" class="btn btn--light">Rechazar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Citas aceptadas</h2>
    <div class="list" style="margin-top:18px;">
      <?php if (empty($citasAceptadas)): ?>
        <div class="list-item">
          <strong>Sin citas aceptadas</strong>
          <div>No hay citas activas.</div>
        </div>
      <?php else: ?>
        <?php foreach ($citasAceptadas as $cita): ?>
          <div class="list-item">
            <strong>
              <?php echo htmlspecialchars(($cita["nombre"] ?? "") . " " . ($cita["apellido_paterno"] ?? "") . " " . ($cita["apellido_materno"] ?? "")); ?>
            </strong>

            <div style="margin-top:6px;color:#6b7280;">
              Fecha: <?php echo htmlspecialchars($cita["fecha"] ?? ""); ?> · Hora: <?php echo htmlspecialchars(substr((string)($cita["hora"] ?? ""), 0, 5)); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 1: <?php echo htmlspecialchars($cita["celular_1"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($cita["correo"] ?? ""); ?>
            </div>

            <?php if (!empty($cita["motivo"])): ?>
              <div style="margin-top:4px;color:#6b7280;">
                Motivo: <?php echo htmlspecialchars($cita["motivo"]); ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($cita["ine_path"])): ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($cita["ine_path"]); ?>" target="_blank" class="btn btn--light">Ver INE</a>
              </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="realizada">
                <button type="submit" class="btn">Marcar realizada</button>
              </form>

              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <input type="hidden" name="estatus" value="cancelada">
                
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="dashboard-card" style="margin-top:22px;">
    <div class="calendar-head">
      <h2>Historial de citas</h2>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($historialCitas)): ?>
        <div class="list-item">
          <strong>Sin historial</strong>
          <div>No hay citas en historial.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialCitas as $cita): ?>
          <div class="list-item">
            <strong>
              <?php echo htmlspecialchars(($cita["nombre"] ?? "") . " " . ($cita["apellido_paterno"] ?? "") . " " . ($cita["apellido_materno"] ?? "")); ?>
            </strong>

            <div style="margin-top:6px;color:#6b7280;">
              Fecha: <?php echo htmlspecialchars($cita["fecha"] ?? ""); ?> · Hora: <?php echo htmlspecialchars(substr((string)($cita["hora"] ?? ""), 0, 5)); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 1: <?php echo htmlspecialchars($cita["celular_1"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($cita["correo"] ?? ""); ?>
            </div>

            <div style="margin-top:8px;">
              <strong style="display:inline;color:#7A1737;">Estatus:</strong>
              <?php echo htmlspecialchars($cita["estatus"] ?? ""); ?>
            </div>

            <?php if (!empty($cita["ine_path"])): ?>
              <div style="margin-top:8px;">
                <a href="<?php echo htmlspecialchars($cita["ine_path"]); ?>" target="_blank" class="btn btn--light">Ver INE</a>
              </div>
            <?php endif; ?>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_cita.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta cita?');">
                <input type="hidden" name="id" value="<?php echo (int)($cita["id"] ?? 0); ?>">
                <button type="submit" class="btn btn--light">Eliminar cita</button>
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
const labelsEstatus = <?php echo json_encode($labelsEstatus, JSON_UNESCAPED_UNICODE); ?>;
const valuesEstatus = <?php echo json_encode($valuesEstatus); ?>;

const labelsMunicipios = <?php echo json_encode($labelsMunicipios, JSON_UNESCAPED_UNICODE); ?>;
const valuesMunicipios = <?php echo json_encode($valuesMunicipios); ?>;

const labelsHoras = <?php echo json_encode($labelsHoras, JSON_UNESCAPED_UNICODE); ?>;
const valuesHoras = <?php echo json_encode($valuesHoras); ?>;

new Chart(document.getElementById('graficaEstatus'), {
  type: 'pie',
  data: {
    labels: labelsEstatus,
    datasets: [{
      data: valuesEstatus
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

new Chart(document.getElementById('graficaHoras'), {
  type: 'pie',
  data: {
    labels: labelsHoras,
    datasets: [{
      data: valuesHoras
    }]
  }
});
</script>

</body>
</html>