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
$stmtApoyos = $pdo->query($sqlApoyos);
$apoyosActivos = $stmtApoyos->fetchAll(PDO::FETCH_ASSOC);

if (!$apoyosActivos) {
    $apoyosActivos = [];
}

/* =========================================
   CONSULTAR REGISTROS DE APOYOS
========================================= */
$sqlRegistrosApoyos = "
    SELECT id, apoyo, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           observaciones, estatus, created_at, ine_path
    FROM registros_apoyos
    WHERE $condicionFecha
    ORDER BY created_at DESC
";
$stmtRegistrosApoyos = $pdo->query($sqlRegistrosApoyos);
$registrosApoyos = $stmtRegistrosApoyos->fetchAll(PDO::FETCH_ASSOC);

if (!$registrosApoyos) {
    $registrosApoyos = [];
}

/* =========================================
   DATOS PARA GRÁFICAS
========================================= */
$conteoApoyos = [];
$conteoMunicipios = [];
$conteoEstatus = [];

foreach ($registrosApoyos as $registro) {
    $apoyo = trim($registro["apoyo"] ?? "");
    $municipio = trim($registro["municipio"] ?? "");
    $estatus = trim($registro["estatus"] ?? "");

    if ($apoyo !== "") {
        if (!isset($conteoApoyos[$apoyo])) {
            $conteoApoyos[$apoyo] = 0;
        }
        $conteoApoyos[$apoyo]++;
    }

    if ($municipio !== "") {
        if (!isset($conteoMunicipios[$municipio])) {
            $conteoMunicipios[$municipio] = 0;
        }
        $conteoMunicipios[$municipio]++;
    }

    if ($estatus !== "") {
        if (!isset($conteoEstatus[$estatus])) {
            $conteoEstatus[$estatus] = 0;
        }
        $conteoEstatus[$estatus]++;
    }
}

$labelsApoyos = array_keys($conteoApoyos);
$valuesApoyos = array_values($conteoApoyos);

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
  <title>Panel de Apoyos | Samuel Te Escucha</title>
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

<form method="GET" style="margin:15px 0 20px; display:flex; gap:10px; flex-wrap:wrap;">
  <input 
    type="text" 
    name="buscar" 
    placeholder="Buscar por apoyo, nombre, correo, celular, municipio..."
    value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>"
    style="width:100%; max-width:420px; padding:10px 14px; border:1px solid #ccc; border-radius:10px;"
  >

  <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">

  <button type="submit" class="btn">Buscar</button>

  <a href="empleados_apoyo.php?filtro=<?php echo urlencode($filtro); ?>" class="btn btn--light">
    Limpiar
  </a>
</form>
<main class="wrap dashboard">
  <div class="dashboard-top">
    <div>
      <h1>Gestión de apoyos</h1>
      <p style="margin:8px 0 0;color:#6b7280;">Consulta apoyos dados de alta y solicitudes registradas.</p>
    </div>
    <a href="dashboard.php" class="btn btn--light">Volver al panel</a>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div style="background:#e8f5e9;color:#1b5e20;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-weight:700;">
      <?php echo htmlspecialchars($mensaje); ?>
    </div>
  <?php endif; ?>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:18px 0 24px;">
    <a href="empleados_apoyo.php?filtro=3dias" class="btn <?php echo $filtro === '3dias' ? '' : 'btn--light'; ?>">Últimos 3 días</a>
    <a href="empleados_apoyo.php?filtro=semana" class="btn <?php echo $filtro === 'semana' ? '' : 'btn--light'; ?>">Última semana</a>
    <a href="empleados_apoyo.php?filtro=mes" class="btn <?php echo $filtro === 'mes' ? '' : 'btn--light'; ?>">Último mes</a>
    <a href="empleados_apoyo.php?filtro=todo" class="btn <?php echo $filtro === 'todo' ? '' : 'btn--light'; ?>">Todo</a>
  </div>

  <section class="dashboard-card">
    <div class="calendar-head">
      <h2>Apoyos dados de alta</h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="alta_apoyo.php" class="btn">Dar de alta apoyo</a>
        <a href="exportar_apoyos_excel.php" class="btn btn--light">Exportar a Excel</a>
      </div>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($apoyosActivos)): ?>
        <div class="list-item">
          <strong>Sin apoyos</strong>
          <div>No hay apoyos registrados.</div>
        </div>
      <?php else: ?>
        <?php foreach ($apoyosActivos as $apoyo): ?>
          <div class="list-item">
            <strong><?php echo htmlspecialchars($apoyo["nombre"] ?? ""); ?></strong>

            <?php if (!empty($apoyo["descripcion"])): ?>
              <div style="margin-top:6px;color:#6b7280;">
                <?php echo htmlspecialchars($apoyo["descripcion"] ?? ""); ?>
              </div>
            <?php endif; ?>

            <div style="margin-top:8px;font-size:13px;color:#9ca3af;">
              Registrado: <?php echo htmlspecialchars($apoyo["created_at"] ?? ""); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_apoyo.php" onsubmit="return confirm('¿Seguro que deseas eliminar este apoyo?');">
                <input type="hidden" name="id" value="<?php echo (int)($apoyo["id"] ?? 0); ?>">
                <button type="submit" class="btn btn--light">Eliminar apoyo</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Estadísticas de apoyos</h2>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px;">
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
        <h3 style="margin-top:0;color:#7A1737;">Apoyos más solicitados</h3>
        <canvas id="graficaApoyos"></canvas>
      </div>

      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
        <h3 style="margin-top:0;color:#7A1737;">Por municipio</h3>
        <canvas id="graficaMunicipios"></canvas>
      </div>

      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;">
        <h3 style="margin-top:0;color:#7A1737;">Por estatus</h3>
        <canvas id="graficaEstatus"></canvas>
      </div>
    </div>
  </section>

  <section class="dashboard-card" style="margin-top:22px;">
    <div class="calendar-head">
      <h2>Registros a apoyos</h2>
      <a href="exportar_apoyos_excel.php" class="btn btn--light">Exportar a Excel</a>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($registrosApoyos)): ?>
        <div class="list-item">
          <strong>Sin registros</strong>
          <div>Aún no hay personas registradas en apoyos.</div>
        </div>
      <?php else: ?>
        <?php foreach ($registrosApoyos as $registro): ?>
          <div class="list-item">
            <strong>
              <?php
                echo htmlspecialchars(
                  ($registro["nombre"] ?? "") . " " .
                  ($registro["apellido_paterno"] ?? "") . " " .
                  ($registro["apellido_materno"] ?? "")
                );
              ?>
            </strong>

            <div style="margin-top:6px;">
              <strong style="display:inline;color:#7A1737;">Apoyo:</strong>
              <?php echo htmlspecialchars($registro["apoyo"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 1: <?php echo htmlspecialchars($registro["celular_1"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Celular 2: <?php echo htmlspecialchars($registro["celular_2"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($registro["correo"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Sección electoral: <?php echo htmlspecialchars($registro["seccion_electoral"] ?? ""); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
              Dirección:
              <?php
                echo htmlspecialchars(
                  ($registro["calle"] ?? "") . " #" . ($registro["no_exterior"] ?? "") .
                  (($registro["no_interior"] ?? "") !== "" ? " Int. " . $registro["no_interior"] : "") .
                  ", Col. " . ($registro["colonia"] ?? "") .
                  ", " . ($registro["municipio"] ?? "") .
                  ", CP " . ($registro["codigo_postal"] ?? "")
                );
              ?>
            </div>

            <?php if (!empty($registro["observaciones"])): ?>
              <div style="margin-top:4px;color:#6b7280;">
                Observaciones: <?php echo htmlspecialchars($registro["observaciones"] ?? ""); ?>
              </div>
            <?php endif; ?>

        <?php if (!empty($registro["ine_path"])): ?>
  <?php $ineUrl = "/" . ltrim($registro["ine_path"], "/"); ?>

  <div style="margin-top:8px;">
    <a href="<?php echo htmlspecialchars($ineUrl); ?>" target="_blank" class="btn btn--light">
      Ver INE
    </a>
  </div>
<?php endif; ?>

            <div style="margin-top:8px;">
              <strong style="display:inline;color:#7A1737;">Estatus:</strong>
              <?php echo htmlspecialchars($registro["estatus"] ?? ""); ?>
            </div>

            <div style="margin-top:8px;font-size:13px;color:#9ca3af;">
              Registrado: <?php echo htmlspecialchars($registro["created_at"] ?? ""); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_registro_apoyo.php" onsubmit="return confirm('¿Seguro que deseas eliminar este registro de apoyo?');">
                <input type="hidden" name="id" value="<?php echo (int)($registro["id"] ?? 0); ?>">
                <button type="submit" class="btn btn--light">Eliminar registro</button>
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
const labelsApoyos = <?php echo json_encode($labelsApoyos, JSON_UNESCAPED_UNICODE); ?>;
const valuesApoyos = <?php echo json_encode($valuesApoyos); ?>;

const labelsMunicipios = <?php echo json_encode($labelsMunicipios, JSON_UNESCAPED_UNICODE); ?>;
const valuesMunicipios = <?php echo json_encode($valuesMunicipios); ?>;

const labelsEstatus = <?php echo json_encode($labelsEstatus, JSON_UNESCAPED_UNICODE); ?>;
const valuesEstatus = <?php echo json_encode($valuesEstatus); ?>;

new Chart(document.getElementById('graficaApoyos'), {
  type: 'pie',
  data: {
    labels: labelsApoyos,
    datasets: [{
      data: valuesApoyos
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