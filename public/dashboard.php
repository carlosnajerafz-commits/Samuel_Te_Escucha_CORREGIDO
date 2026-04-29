<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

/* =========================================
   MENSAJES DEL DASHBOARD
========================================= */
$mensajeDashboard = "";

if (isset($_GET["cita_eliminada"])) {
    $mensajeDashboard = "La cita fue eliminada correctamente.";
} elseif (isset($_GET["queja_eliminada"])) {
    $mensajeDashboard = "La queja fue eliminada correctamente.";
} elseif (isset($_GET["queja_actualizada"])) {
    $mensajeDashboard = "La queja fue actualizada correctamente.";
} elseif (isset($_GET["cita_actualizada"])) {
    $mensajeDashboard = "La solicitud o cita fue actualizada correctamente.";
} elseif (isset($_GET["apoyo_eliminado"])) {
    $mensajeDashboard = "El apoyo fue eliminado correctamente.";
} elseif (isset($_GET["registro_apoyo_eliminado"])) {
    $mensajeDashboard = "El registro de apoyo fue eliminado correctamente.";
}
/* =========================================
   ACTUALIZAR CITAS VENCIDAS AUTOMÁTICAMENTE
   SOLO PARA CITAS ACEPTADAS
========================================= */
$sqlActualizarVencidas = "
    UPDATE citas
    SET estatus = 'vencida'
    WHERE estatus = 'aceptada'
      AND (
        fecha < CURRENT_DATE
        OR (fecha = CURRENT_DATE AND hora < CURRENT_TIME::time)
      )
";
$pdo->exec($sqlActualizarVencidas);

/* =========================================
   CONSULTAR SOLICITUDES DE CITA
========================================= */
$sqlSolicitudes = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, fecha, hora, motivo, estatus
    FROM citas
    WHERE estatus = 'solicitada'
    ORDER BY fecha ASC, hora ASC
";

$stmtSolicitudes = $pdo->query($sqlSolicitudes);
$solicitudesCita = $stmtSolicitudes->fetchAll(PDO::FETCH_ASSOC);

if (!$solicitudesCita) {
    $solicitudesCita = [];
}

/* =========================================
   CONSULTAR CITAS ACEPTADAS ACTIVAS
========================================= */
$sqlCitasAceptadas = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, fecha, hora, motivo, estatus
    FROM citas
    WHERE estatus = 'aceptada'
    ORDER BY fecha ASC, hora ASC
";
$stmtAceptadas = $pdo->query($sqlCitasAceptadas);
$citasAceptadas = $stmtAceptadas->fetchAll();

/* =========================================
   CONSULTAR HISTORIAL DE CITAS
========================================= */
$sqlHistorial = "
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, fecha, hora, motivo, estatus
    FROM citas
    WHERE estatus IN ('rechazada', 'realizada', 'cancelada', 'vencida')
    ORDER BY fecha DESC, hora DESC
";
$stmtHistorial = $pdo->query($sqlHistorial);
$historialCitas = $stmtHistorial->fetchAll();

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
    ORDER BY created_at DESC
";
$stmtQuejasPendientes = $pdo->query($sqlQuejasPendientes);
$quejasPendientes = $stmtQuejasPendientes->fetchAll();

/* =========================================
   CONSULTAR HISTORIAL DE QUEJAS
========================================= */
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
    ORDER BY created_at DESC
";

$stmtHistorialQuejas = $pdo->query($sqlHistorialQuejas);
$historialQuejas = $stmtHistorialQuejas->fetchAll(PDO::FETCH_ASSOC);

if (!$historialQuejas) {
    $historialQuejas = [];
}

/* =========================================
   AGRUPAR CITAS ACEPTADAS POR FECHA
========================================= */
$calendar = [];
foreach ($citasAceptadas as $cita) {
    $fechaKey = $cita["fecha"];
    if (!isset($calendar[$fechaKey])) {
        $calendar[$fechaKey] = [];
    }
    $calendar[$fechaKey][] = $cita;
}

$sqlRegistrosApoyos = "
    SELECT id, apoyo, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           ine_path, observaciones, estatus, created_at
    FROM registros_apoyos
    ORDER BY created_at DESC
";
$stmtRegistrosApoyos = $pdo->query($sqlRegistrosApoyos);
$registrosApoyos = $stmtRegistrosApoyos->fetchAll();

$sqlApoyos = "
    SELECT id, nombre, descripcion, activo, created_at
    FROM apoyos
    ORDER BY created_at DESC
";
$stmtApoyos = $pdo->query($sqlApoyos);
$apoyosActivos = $stmtApoyos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Personal | Samuel Te Escucha</title>
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
        <span class="brand__sub">Panel de personal</span>
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

<main class="wrap dashboard">
  <div class="dashboard-top">
    <div>
      <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION["empleado_nombre"]); ?></h1>
      <p style="margin:8px 0 0;color:#6b7280;">Administración de solicitudes, citas y quejas</p>
    </div>
    <a href="logout.php" class="btn btn--light">Cerrar sesión</a>
  </div>

  <?php if ($mensajeDashboard !== ""): ?>
    <div style="background:#e8f5e9;color:#1b5e20;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-weight:700;">
      <?php echo htmlspecialchars($mensajeDashboard); ?>
    </div>
  <?php endif; ?>

  <!-- SOLICITUDES DE CITA -->
  <section class="dashboard-card" style="margin-bottom:22px;">
    <div class="calendar-head">
      <h2>Solicitudes de cita</h2>
      <span style="color:#6b7280;font-weight:700;">Pendientes de validación</span>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (count($solicitudesCita) === 0): ?>
        <div class="list-item">
          <strong>Sin solicitudes pendientes</strong>
          <div>No hay solicitudes por revisar.</div>
        </div>
      <?php else: ?>
        <?php foreach ($solicitudesCita as $item): ?>
          <div class="list-item">
           <strong>
  <?php echo htmlspecialchars($item["nombre"] . " " . $item["apellido_paterno"] . " " . $item["apellido_materno"]); ?>
</strong>

            <div style="margin-top:6px;color:#6b7280;">
              Fecha: <?php echo htmlspecialchars($item["fecha"]); ?> · Hora: <?php echo htmlspecialchars(substr($item["hora"], 0, 5)); ?>
            </div>

            <div style="margin-top:4px;color:#6b7280;">
  Celular 1: <?php echo htmlspecialchars($item["celular_1"]); ?>
</div>
<div style="font-weight:600;color:#6b7280;margin-top:4px;">
  Celular 2: <?php echo htmlspecialchars($item["celular_2"]); ?>
</div>

<div style="margin-top:4px;color:#6b7280;">
  Celular 2: <?php echo htmlspecialchars($item["celular_2"]); ?>
</div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($item["correo"]); ?>
            </div>

            <?php if (!empty($item["motivo"])): ?>
              <div style="margin-top:4px;color:#6b7280;">
                Motivo: <?php echo htmlspecialchars($item["motivo"]); ?>
              </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
                <input type="hidden" name="estatus" value="aceptada">
                <button type="submit" class="btn">Aceptar</button>
              </form>

              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
                <input type="hidden" name="estatus" value="rechazada">
                <button type="submit" class="btn btn--light">Rechazar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <div class="dashboard-grid">
    <!-- CITAS ACEPTADAS -->
    <section class="dashboard-card">
      <div class="calendar-head">
        <h2>Citas aceptadas</h2>
        <span style="color:#6b7280;font-weight:700;">Activas</span>
      </div>

      <div class="list">
        <?php if (count($calendar) === 0): ?>
          <div class="list-item">
            <strong>Sin citas aceptadas</strong>
            <div>No hay citas activas en este momento.</div>
          </div>
        <?php else: ?>
          <?php foreach ($calendar as $fecha => $items): ?>
            <div class="list-item">
              <strong><?php echo htmlspecialchars($fecha); ?></strong>

              <?php foreach ($items as $item): ?>
                <div class="calendar-event" style="margin-top:10px;">
                  <div>
                    <?php echo htmlspecialchars(substr($item["hora"], 0, 5)); ?> —
                   <?php
                    echo htmlspecialchars(
                    ($item["nombre"] ?? "") . " " .
                      ($item["apellido_paterno"] ?? "") . " " .
                      ($item["apellido_materno"] ?? "")
                  );
                  ?>
                  </div>

                  <div style="font-weight:600;color:#6b7280;margin-top:6px;">
  Celular 1: <?php echo htmlspecialchars($item["celular_1"] ?? ""); ?>
</div>

<div style="font-weight:600;color:#6b7280;margin-top:4px;">
  Celular 2: <?php echo htmlspecialchars($item["celular_2"] ?? ""); ?>
</div>

                  <div style="font-weight:600;color:#6b7280;margin-top:4px;">
                    Correo: <?php echo htmlspecialchars($item["correo"]); ?>
                  </div>

                  <?php if (!empty($item["motivo"])): ?>
                    <div style="font-weight:600;color:#6b7280;margin-top:4px;">
                      Motivo: <?php echo htmlspecialchars($item["motivo"]); ?>
                    </div>
                  <?php endif; ?>

                  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
                    <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                      <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
                      <input type="hidden" name="estatus" value="realizada">
                      <button type="submit" class="btn">Marcar realizada</button>
                    </form>

                    <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                      <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
                      <input type="hidden" name="estatus" value="cancelada">
                      <button type="submit" class="btn btn--light">Cancelar</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- QUEJAS PENDIENTES -->
    <aside class="dashboard-card">
      <h2>Quejas pendientes</h2>
      <div class="list" style="margin-top:18px;">
        <?php if (count($quejasPendientes) === 0): ?>
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
                    $queja["nombre"] . " " .
                    $queja["apellido_paterno"] . " " .
                    $queja["apellido_materno"]
                  );
                ?>
              </strong>

              <div style="margin-top:6px;">
                <strong style="display:inline;color:#7A1737;">Tipo:</strong>
                <?php echo htmlspecialchars($queja["tipo"]); ?>
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

              <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
                <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                  <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
                  <input type="hidden" name="estatus" value="atendida">
                  <button type="submit" class="btn">Marcar atendida</button>
                </form>

                <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                  <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
                  <input type="hidden" name="estatus" value="cerrada">
                  <button type="submit" class="btn btn--light">Cerrar</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>
  </div>

  <!-- HISTORIAL DE CITAS -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Historial de citas</h2>
    <div class="list" style="margin-top:18px;">
      <?php if (count($historialCitas) === 0): ?>
        <div class="list-item">
          <strong>Sin historial</strong>
          <div>Aún no hay citas rechazadas, realizadas, canceladas o vencidas.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialCitas as $cita): ?>
          <div class="list-item">
            <strong><?php echo htmlspecialchars($cita["nombre"]); ?></strong>

            <div style="margin-top:4px;">
              <?php echo htmlspecialchars($cita["fecha"]); ?> · <?php echo htmlspecialchars(substr($cita["hora"], 0, 5)); ?>
            </div>

           <div style="margin-top:4px;color:#6b7280;">
  Celular 1: <?php echo htmlspecialchars($cita["celular_1"]); ?>
</div>

<div style="margin-top:4px;color:#6b7280;">
  Celular 2: <?php echo htmlspecialchars($cita["celular_2"]); ?>
</div>

            <div style="margin-top:4px;color:#6b7280;">
              Correo: <?php echo htmlspecialchars($cita["correo"]); ?>
            </div>

            <?php if (!empty($cita["motivo"])): ?>
              <div style="margin-top:4px;color:#6b7280;">
                Motivo: <?php echo htmlspecialchars($cita["motivo"]); ?>
              </div>
            <?php endif; ?>

            <div style="margin-top:8px;">
              <strong style="display:inline;color:#7A1737;">Estatus:</strong>
              <?php echo htmlspecialchars($cita["estatus"]); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_cita.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta cita?');">
                <input type="hidden" name="id" value="<?php echo (int)$cita["id"]; ?>">
                <button type="submit" class="btn btn--light">Eliminar cita</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- HISTORIAL DE QUEJAS -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Historial de quejas</h2>
    <div class="list" style="margin-top:18px;">
      <?php if (empty($historialQuejas)): ?>
        <div class="list-item">
          <strong>Sin historial de quejas</strong>
          <div>Aún no hay quejas atendidas o cerradas.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialQuejas as $queja): ?>
          <div class="list-item">
            <strong>
              <?php
                echo htmlspecialchars(
                  $queja["nombre"] . " " .
                  $queja["apellido_paterno"] . " " .
                  $queja["apellido_materno"]
                );
              ?>
            </strong>

            <div style="margin-top:6px;">
              <strong style="display:inline;color:#7A1737;">Tipo:</strong>
              <?php echo htmlspecialchars($queja["tipo"]); ?>
            </div>

           <div style="margin-top:4px;color:#6b7280;">
  Celular 1: <?php echo htmlspecialchars($queja["celular_1"]); ?>
</div>

<div style="margin-top:4px;color:#6b7280;">
  Celular 2: <?php echo htmlspecialchars($queja["celular_2"]); ?>
</div>

<div style="margin-top:4px;color:#6b7280;">
  Correo: <?php echo htmlspecialchars($queja["correo"]); ?>
</div>

<div style="margin-top:4px;color:#6b7280;">
  Sección electoral: <?php echo htmlspecialchars($queja["seccion_electoral"]); ?>
</div>

<div style="margin-top:4px;color:#6b7280;">
  Dirección:
  <?php
    echo htmlspecialchars(
      $queja["calle"] . " #" . $queja["no_exterior"] .
      ($queja["no_interior"] !== "" ? " Int. " . $queja["no_interior"] : "") .
      ", Col. " . $queja["colonia"] .
      ", " . $queja["municipio"] .
      ", CP " . $queja["codigo_postal"]
    );
  ?>
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

            <div style="margin-top:8px;">
              <strong style="display:inline;color:#7A1737;">Estatus:</strong>
              <?php echo htmlspecialchars($queja["estatus"]); ?>
            </div>

            <div style="margin-top:8px;font-size:13px;color:#9ca3af;">
              Registrada: <?php echo htmlspecialchars($queja["created_at"]); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_queja.php" onsubmit="return confirm('¿Seguro que deseas eliminar esta queja?');">
                <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
                <button type="submit" class="btn btn--light">Eliminar queja</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

<section class="dashboard-card" style="margin-top:22px;">
  <div class="calendar-head">
    <h2>Apoyos dados de alta</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a href="alta_apoyo.php" class="btn">Dar de alta apoyo</a>
    </div>
  </div>

  <div class="list" style="margin-top:18px;">
    <?php if (count($apoyosActivos) === 0): ?>
      <div class="list-item">
        <strong>Sin apoyos</strong>
        <div>No hay apoyos registrados.</div>
      </div>
    <?php else: ?>
      <?php foreach ($apoyosActivos as $apoyo): ?>
        <div class="list-item">
          <strong><?php echo htmlspecialchars($apoyo["nombre"]); ?></strong>

          <?php if (!empty($apoyo["descripcion"])): ?>
            <div style="margin-top:6px;color:#6b7280;">
              <?php echo htmlspecialchars($apoyo["descripcion"]); ?>
            </div>
          <?php endif; ?>

          <div style="margin-top:8px;font-size:13px;color:#9ca3af;">
            Registrado: <?php echo htmlspecialchars($apoyo["created_at"]); ?>
          </div>

          <div style="margin-top:12px;">
            <form method="POST" action="eliminar_apoyo.php" onsubmit="return confirm('¿Seguro que deseas eliminar este apoyo?');">
              <input type="hidden" name="id" value="<?php echo (int)$apoyo["id"]; ?>">
              <button type="submit" class="btn btn--light">Eliminar apoyo</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="dashboard-card" style="margin-top:22px;">
  <div class="calendar-head">
    <h2>Registros a apoyos</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a href="exportar_apoyos_excel.php" class="btn btn--light">Exportar a Excel</a>
    </div>
  </div>

  <div class="list" style="margin-top:18px;">
    <?php if (count($registrosApoyos) === 0): ?>
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
                $registro["nombre"] . " " .
                $registro["apellido_paterno"] . " " .
                $registro["apellido_materno"]
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
              Observaciones: <?php echo htmlspecialchars($registro["observaciones"]); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($registro["ine_path"])): ?>
  <?php
    $ineUrl = $registro["ine_path"];

    if (strpos($ineUrl, "uploads/") !== 0) {
        $ineUrl = "uploads/" . $ineUrl;
    }

    $ineUrl = "/" . ltrim($ineUrl, "/");
  ?>

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