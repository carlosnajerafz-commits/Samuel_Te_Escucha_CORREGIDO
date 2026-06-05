<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

// Después de session_start() y require_once "db.php"
$enMantenimiento = file_exists(__DIR__ . "/maintenance.flag");

$mensajeDashboard = "";
if (isset($_GET["mantenimiento"])) {
    $mensajeDashboard = $_GET["mantenimiento"] === "activado"
        ? "🔧 Modo mantenimiento ACTIVADO. El sitio público muestra la página de mantenimiento."
        : "✅ Modo mantenimiento DESACTIVADO. El sitio está visible al público.";
}
// ... resto de los elseif que ya tienes

/* =========================================
   MENSAJES
========================================= */
$mensajeDashboard = "";
if (isset($_GET["cita_eliminada"]))           $mensajeDashboard = "La cita fue eliminada correctamente.";
elseif (isset($_GET["queja_eliminada"]))      $mensajeDashboard = "La queja fue eliminada correctamente.";
elseif (isset($_GET["queja_actualizada"]))    $mensajeDashboard = "La queja fue actualizada correctamente.";
elseif (isset($_GET["cita_actualizada"]))     $mensajeDashboard = "La solicitud o cita fue actualizada correctamente.";
elseif (isset($_GET["apoyo_eliminado"]))      $mensajeDashboard = "El apoyo fue eliminado correctamente.";
elseif (isset($_GET["registro_apoyo_eliminado"])) $mensajeDashboard = "El registro de apoyo fue eliminado correctamente.";

/* =========================================
   MARCAR CITAS VENCIDAS AUTOMÁTICAMENTE
========================================= */
$pdo->exec("
    UPDATE citas
    SET estatus = 'vencida'
    WHERE estatus = 'aceptada'
      AND (
        fecha < CURRENT_DATE
        OR (fecha = CURRENT_DATE AND hora < CURRENT_TIME::time)
      )
");

/* =========================================
   CONSULTAS
========================================= */
$solicitudesCita = $pdo->query("
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, fecha, hora, motivo, estatus
    FROM citas WHERE estatus = 'solicitada'
    ORDER BY fecha ASC, hora ASC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$citasAceptadas = $pdo->query("
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, fecha, hora, motivo, estatus
    FROM citas WHERE estatus = 'aceptada'
    ORDER BY fecha ASC, hora ASC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$historialCitas = $pdo->query("
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, fecha, hora, motivo, estatus
    FROM citas WHERE estatus IN ('rechazada','realizada','cancelada','vencida')
    ORDER BY fecha DESC, hora DESC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$quejasPendientes = $pdo->query("
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           tipo, descripcion, evidencia_path, created_at, estatus
    FROM quejas WHERE estatus = 'pendiente'
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$historialQuejas = $pdo->query("
    SELECT id, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           tipo, descripcion, evidencia_path, created_at, estatus
    FROM quejas WHERE estatus IN ('atendida','cerrada','completada')
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$apoyosActivos = $pdo->query("
    SELECT id, nombre, descripcion, activo, created_at
    FROM apoyos WHERE activo = TRUE
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$registrosApoyos = $pdo->query("
    SELECT id, apoyo, nombre, apellido_paterno, apellido_materno,
           celular_1, celular_2, correo, seccion_electoral,
           calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
           ine_path, observaciones, estatus, created_at
    FROM registros_apoyos
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   AGRUPAR CITAS ACEPTADAS POR FECHA
========================================= */
$calendar = [];
foreach ($citasAceptadas as $c) {
    $calendar[$c["fecha"]][] = $c;
}

/* =========================================
   KPIs
========================================= */
$kpis = [
    "solicitudes"  => count($solicitudesCita),
    "aceptadas"    => count($citasAceptadas),
    "quejas"       => count($quejasPendientes),
    "apoyos_reg"   => count($registrosApoyos),
];

/* Helper: nombre completo */
function nombreCompleto(array $row): string {
    return trim(($row["nombre"] ?? "") . " " . ($row["apellido_paterno"] ?? "") . " " . ($row["apellido_materno"] ?? ""));
}

/* Helper: dirección */
function direccion(array $row): string {
    $dir  = ($row["calle"] ?? "") . " #" . ($row["no_exterior"] ?? "");
    $dir .= ($row["no_interior"] ?? "") !== "" ? " Int. " . $row["no_interior"] : "";
    $dir .= ", Col. " . ($row["colonia"] ?? "");
    $dir .= ", " . ($row["municipio"] ?? "");
    $dir .= ", CP " . ($row["codigo_postal"] ?? "");
    return $dir;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    /* ---- KPI grid ---- */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 16px;
      margin: 20px 0 28px;
    }
    .kpi-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 18px 20px;
      text-align: center;
      box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }
    .kpi-card__num {
      font-size: 34px;
      font-weight: 800;
      color: #7A1737;
      line-height: 1;
    }
    .kpi-card__num.ok   { color: #166534; }
    .kpi-card__num.warn { color: #92400e; }
    .kpi-card__label {
      font-size: 11px;
      color: #6b7280;
      margin-top: 6px;
      font-weight: 700;
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
    .badge--solicitada  { background:#fef9c3; color:#92400e; }
    .badge--aceptada    { background:#dcfce7; color:#166534; }
    .badge--rechazada   { background:#fee2e2; color:#991b1b; }
    .badge--realizada   { background:#dbeafe; color:#1e40af; }
    .badge--cancelada   { background:#f3f4f6; color:#374151; }
    .badge--vencida     { background:#ede9fe; color:#5b21b6; }
    .badge--pendiente   { background:#fef9c3; color:#92400e; }
    .badge--atendida    { background:#dbeafe; color:#1e40af; }
    .badge--completada  { background:#dcfce7; color:#166534; }
    .badge--cerrada     { background:#f3f4f6; color:#374151; }
    .badge--entregado   { background:#dcfce7; color:#166534; }

    /* ---- section links ---- */
    .section-link {
      font-size: 13px;
      color: #7A1737;
      text-decoration: none;
      font-weight: 600;
    }
    .section-link:hover { text-decoration: underline; }

    /* ---- alert ---- */
    .alert-success {
      background:#dcfce7; color:#166534; border:1px solid #86efac;
      border-radius:10px; padding:12px 18px; margin-bottom:20px; font-weight:600;
    }

    /* ---- apoyo chip ---- */
    .apoyo-chip {
      display: inline-block;
      background: #fdf2f5; color: #7A1737;
      border: 1px solid #f0c2cd; border-radius: 8px;
      font-size: 12px; font-weight: 700;
      padding: 2px 10px; margin-top: 6px;
    }

    /* ---- quick nav ---- */
    .quick-nav {
      display: flex; gap: 12px; flex-wrap: wrap;
      margin-bottom: 28px;
    }
    .quick-nav a {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 18px; border-radius: 10px;
      background: #fff; border: 1px solid #e5e7eb;
      font-weight: 700; font-size: 14px; color: #374151;
      text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,.05);
      transition: border-color .15s, color .15s;
    }
    .quick-nav a:hover { border-color: #7A1737; color: #7A1737; }
    .quick-nav a .ql-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #7A1737; display: inline-block;
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

<!-- ====== MAIN ====== -->
<main class="wrap dashboard">

  <div class="dashboard-top">
    <div>
      <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION["empleado_nombre"] ?? ""); ?></h1>
      <p style="margin:8px 0 0;color:#6b7280;">Resumen general · Administración de solicitudes, citas, quejas y apoyos</p>
    </div>
    <a href="logout.php" class="btn btn--light">Cerrar sesión</a>
  </div>

  <?php if ($mensajeDashboard !== ""): ?>
    <div class="alert-success"><?php echo htmlspecialchars($mensajeDashboard); ?></div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-card__num <?php echo $kpis['solicitudes'] > 0 ? 'warn' : 'ok'; ?>">
        <?php echo $kpis['solicitudes']; ?>
      </div>
      <div class="kpi-card__label">Solicitudes de cita</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo $kpis['aceptadas']; ?></div>
      <div class="kpi-card__label">Citas aceptadas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num <?php echo $kpis['quejas'] > 0 ? 'warn' : 'ok'; ?>">
        <?php echo $kpis['quejas']; ?>
      </div>
      <div class="kpi-card__label">Quejas pendientes</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo count($apoyosActivos); ?></div>
      <div class="kpi-card__label">Tipos de apoyo</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-card__num"><?php echo $kpis['apoyos_reg']; ?></div>
      <div class="kpi-card__label">Solicitudes de apoyo</div>
    </div>
  </div>

  <!-- Botón mantenimiento -->
<div style="margin-bottom:24px;">
  <form method="POST" action="toggle_maintenance.php" style="margin:0;">
    <?php if ($enMantenimiento): ?>
      <button type="submit" class="btn"
              style="background:#166534;display:flex;align-items:center;gap:8px;"
              onclick="return confirm('¿Desactivar modo mantenimiento? El sitio será visible al público.');">
        ✅ Desactivar mantenimiento
      </button>
      <p style="margin-top:8px;font-size:13px;color:#991b1b;font-weight:600;">
        🔴 El sitio público está en mantenimiento ahora mismo.
      </p>
    <?php else: ?>
      <button type="submit" class="btn btn--light"
              style="border:2px solid #92400e;color:#92400e;display:flex;align-items:center;gap:8px;"
              onclick="return confirm('¿Activar modo mantenimiento? El sitio público dejará de ser accesible.');">
        🔧 Activar mantenimiento
      </button>
      <p style="margin-top:8px;font-size:13px;color:#166534;font-weight:600;">
        🟢 El sitio está visible al público.
      </p>
    <?php endif; ?>
  </form>
</div>

  <!-- Acceso rápido -->
  <div class="quick-nav">
    <a href="empleados_cita.php"><span class="ql-dot"></span> Gestionar citas</a>
    <a href="empleados_queja.php"><span class="ql-dot"></span> Gestionar quejas</a>
    <a href="empleados_apoyo.php"><span class="ql-dot"></span> Gestionar apoyos</a>
    <a href="alta_apoyo.php"><span class="ql-dot"></span> + Dar de alta apoyo</a>
    <a href="exportar_apoyos_excel.php"><span class="ql-dot"></span> Exportar apoyos</a>
  </div>

  <!-- ==============================
       SOLICITUDES DE CITA
  ============================== -->
  <section class="dashboard-card" style="margin-bottom:22px;">
    <div class="calendar-head">
      <h2>Solicitudes de cita
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
          (<?php echo count($solicitudesCita); ?>)
        </span>
      </h2>
      <a href="empleados_cita.php" class="section-link">Ver todas →</a>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($solicitudesCita)): ?>
        <div class="list-item">
          <strong>Sin solicitudes pendientes</strong>
          <div>No hay solicitudes por revisar.</div>
        </div>
      <?php else: ?>
        <?php foreach ($solicitudesCita as $item): ?>
          <div class="list-item">
            <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: CITA-<?php echo str_pad((string)$item["id"], 6, "0", STR_PAD_LEFT); ?>
              <span class="badge badge--solicitada" style="margin-left:8px;">Solicitada</span>
            </div>

            <strong><?php echo htmlspecialchars(nombreCompleto($item)); ?></strong>

            <div style="margin-top:6px;color:#6b7280;font-size:13px;">
              📅 <?php echo htmlspecialchars($item["fecha"]); ?>
              &nbsp;·&nbsp;
              🕐 <?php echo htmlspecialchars(substr($item["hora"], 0, 5)); ?>
            </div>

            <?php if (!empty($item["celular_1"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Cel 1: <?php echo htmlspecialchars($item["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($item["celular_2"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📞 Cel 2: <?php echo htmlspecialchars($item["celular_2"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($item["correo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              ✉️ <?php echo htmlspecialchars($item["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($item["motivo"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              📝 <?php echo htmlspecialchars($item["motivo"]); ?>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)$item["id"]; ?>">
                <input type="hidden" name="estatus" value="aceptada">
                <button type="submit" class="btn">✔ Aceptar</button>
              </form>
              <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                <input type="hidden" name="id"     value="<?php echo (int)$item["id"]; ?>">
                <input type="hidden" name="estatus" value="rechazada">
                <button type="submit" class="btn btn--light"
                        onclick="return confirm('¿Rechazar esta cita?');">✘ Rechazar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <div class="dashboard-grid">

    <!-- ==============================
         CITAS ACEPTADAS
    ============================== -->
    <section class="dashboard-card">
      <div class="calendar-head">
        <h2>Citas aceptadas
          <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
            (<?php echo count($citasAceptadas); ?>)
          </span>
        </h2>
        <a href="empleados_cita.php" class="section-link">Ver todas →</a>
      </div>

      <div class="list" style="margin-top:14px;">
        <?php if (empty($calendar)): ?>
          <div class="list-item">
            <strong>Sin citas aceptadas</strong>
            <div>No hay citas activas en este momento.</div>
          </div>
        <?php else: ?>
          <?php foreach ($calendar as $fecha => $items): ?>
            <div class="list-item">
              <strong style="color:#7A1737;">📅 <?php echo htmlspecialchars($fecha); ?></strong>

              <?php foreach ($items as $item): ?>
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f3f4f6;">
                  <div style="font-weight:700;">
                    🕐 <?php echo htmlspecialchars(substr($item["hora"], 0, 5)); ?>
                    &nbsp;—&nbsp;
                    <?php echo htmlspecialchars(nombreCompleto($item)); ?>
                  </div>

                  <?php if (!empty($item["celular_1"])): ?>
                  <div style="color:#6b7280;font-size:13px;margin-top:4px;">
                    📞 Cel 1: <?php echo htmlspecialchars($item["celular_1"]); ?>
                  </div>
                  <?php endif; ?>

                  <?php if (!empty($item["celular_2"])): ?>
                  <div style="color:#6b7280;font-size:13px;margin-top:2px;">
                    📞 Cel 2: <?php echo htmlspecialchars($item["celular_2"]); ?>
                  </div>
                  <?php endif; ?>

                  <?php if (!empty($item["correo"])): ?>
                  <div style="color:#6b7280;font-size:13px;margin-top:2px;">
                    ✉️ <?php echo htmlspecialchars($item["correo"]); ?>
                  </div>
                  <?php endif; ?>

                  <?php if (!empty($item["motivo"])): ?>
                  <div style="color:#6b7280;font-size:13px;margin-top:2px;">
                    📝 <?php echo htmlspecialchars($item["motivo"]); ?>
                  </div>
                  <?php endif; ?>

                  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                    <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                      <input type="hidden" name="id"     value="<?php echo (int)$item["id"]; ?>">
                      <input type="hidden" name="estatus" value="realizada">
                      <button type="submit" class="btn" style="font-size:12px;padding:6px 14px;">✔ Realizada</button>
                    </form>
                    <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
                      <input type="hidden" name="id"     value="<?php echo (int)$item["id"]; ?>">
                      <input type="hidden" name="estatus" value="cancelada">
                      <button type="submit" class="btn btn--light" style="font-size:12px;padding:6px 14px;"
                              onclick="return confirm('¿Cancelar esta cita?');">✘ Cancelar</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- ==============================
         QUEJAS PENDIENTES
    ============================== -->
    <aside class="dashboard-card">
      <div class="calendar-head">
        <h2>Quejas pendientes
          <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
            (<?php echo count($quejasPendientes); ?>)
          </span>
        </h2>
        <a href="empleados_queja.php" class="section-link">Ver todas →</a>
      </div>

      <div class="list" style="margin-top:14px;">
        <?php if (empty($quejasPendientes)): ?>
          <div class="list-item">
            <strong>No hay quejas pendientes</strong>
            <div>Cuando se registren aparecerán aquí.</div>
          </div>
        <?php else: ?>
          <?php foreach ($quejasPendientes as $queja): ?>
            <div class="list-item">
              <div style="margin-bottom:6px;font-size:13px;font-weight:800;color:#7A1737;">
                Folio: QUEJA-<?php echo str_pad((string)$queja["id"], 6, "0", STR_PAD_LEFT); ?>
                <span class="badge badge--pendiente" style="margin-left:6px;">Pendiente</span>
              </div>

              <strong><?php echo htmlspecialchars(nombreCompleto($queja)); ?></strong>

              <div style="margin-top:6px;font-size:13px;">
                <strong style="color:#7A1737;">Tipo:</strong>
                <?php echo htmlspecialchars($queja["tipo"]); ?>
              </div>

              <?php if (!empty($queja["celular_1"])): ?>
              <div style="color:#6b7280;font-size:13px;margin-top:4px;">
                📞 Cel 1: <?php echo htmlspecialchars($queja["celular_1"]); ?>
              </div>
              <?php endif; ?>

              <?php if (!empty($queja["celular_2"])): ?>
              <div style="color:#6b7280;font-size:13px;margin-top:2px;">
                📞 Cel 2: <?php echo htmlspecialchars($queja["celular_2"]); ?>
              </div>
              <?php endif; ?>

              <?php if (!empty($queja["correo"])): ?>
              <div style="color:#6b7280;font-size:13px;margin-top:2px;">
                ✉️ <?php echo htmlspecialchars($queja["correo"]); ?>
              </div>
              <?php endif; ?>

              <?php if (!empty($queja["municipio"])): ?>
              <div style="color:#6b7280;font-size:13px;margin-top:2px;">
                📍 <?php echo htmlspecialchars($queja["municipio"]); ?>
              </div>
              <?php endif; ?>

              <?php if (!empty($queja["descripcion"])): ?>
              <div style="color:#6b7280;font-size:13px;margin-top:2px;">
                📝 <?php echo htmlspecialchars($queja["descripcion"]); ?>
              </div>
              <?php endif; ?>

              <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                  <input type="hidden" name="id"     value="<?php echo (int)$queja["id"]; ?>">
                  <input type="hidden" name="estatus" value="atendida">
                  <button type="submit" class="btn" style="font-size:12px;padding:6px 14px;">Atender</button>
                </form>
                <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
                  <input type="hidden" name="id"     value="<?php echo (int)$queja["id"]; ?>">
                  <input type="hidden" name="estatus" value="cerrada">
                  <button type="submit" class="btn btn--light" style="font-size:12px;padding:6px 14px;"
                          onclick="return confirm('¿Cerrar esta queja?');">Cerrar</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>

  </div><!-- /.dashboard-grid -->

  <!-- ==============================
       HISTORIAL DE CITAS
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <div class="calendar-head">
      <h2>Historial de citas
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
          (<?php echo count($historialCitas); ?>)
        </span>
      </h2>
      <a href="empleados_cita.php" class="section-link">Ver detalle →</a>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($historialCitas)): ?>
        <div class="list-item">
          <strong>Sin historial</strong>
          <div>Aún no hay citas completadas o canceladas.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialCitas as $cita): ?>
          <?php $bc = "badge--" . ($cita["estatus"] ?? ""); ?>
          <div class="list-item">
            <div style="margin-bottom:6px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: CITA-<?php echo str_pad((string)$cita["id"], 6, "0", STR_PAD_LEFT); ?>
              <span class="badge <?php echo htmlspecialchars($bc); ?>" style="margin-left:6px;">
                <?php echo htmlspecialchars($cita["estatus"]); ?>
              </span>
            </div>

            <strong><?php echo htmlspecialchars(nombreCompleto($cita)); ?></strong>

            <div style="color:#6b7280;font-size:13px;margin-top:4px;">
              📅 <?php echo htmlspecialchars($cita["fecha"]); ?>
              &nbsp;·&nbsp;
              🕐 <?php echo htmlspecialchars(substr($cita["hora"], 0, 5)); ?>
            </div>

            <?php if (!empty($cita["celular_1"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              📞 Cel 1: <?php echo htmlspecialchars($cita["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["correo"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              ✉️ <?php echo htmlspecialchars($cita["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($cita["motivo"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              📝 <?php echo htmlspecialchars($cita["motivo"]); ?>
            </div>
            <?php endif; ?>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_cita.php" style="margin:0;"
                    onsubmit="return confirm('¿Eliminar esta cita del historial?');">
                <input type="hidden" name="id" value="<?php echo (int)$cita["id"]; ?>">
                <button type="submit" class="btn btn--light">🗑 Eliminar</button>
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
      <a href="empleados_queja.php" class="section-link">Ver detalle →</a>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($historialQuejas)): ?>
        <div class="list-item">
          <strong>Sin historial de quejas</strong>
          <div>Aún no hay quejas atendidas, cerradas o completadas.</div>
        </div>
      <?php else: ?>
        <?php foreach ($historialQuejas as $queja): ?>
          <?php $bc = "badge--" . ($queja["estatus"] ?? ""); ?>
          <div class="list-item">
            <div style="margin-bottom:6px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: QUEJA-<?php echo str_pad((string)$queja["id"], 6, "0", STR_PAD_LEFT); ?>
              <span class="badge <?php echo htmlspecialchars($bc); ?>" style="margin-left:6px;">
                <?php echo htmlspecialchars($queja["estatus"]); ?>
              </span>
            </div>

            <strong><?php echo htmlspecialchars(nombreCompleto($queja)); ?></strong>

            <div style="margin-top:4px;font-size:13px;">
              <strong style="color:#7A1737;">Tipo:</strong>
              <?php echo htmlspecialchars($queja["tipo"]); ?>
            </div>

            <?php if (!empty($queja["celular_1"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              📞 Cel 1: <?php echo htmlspecialchars($queja["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["correo"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              ✉️ <?php echo htmlspecialchars($queja["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($queja["municipio"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              📍 <?php echo htmlspecialchars($queja["municipio"]); ?>
            </div>
            <?php endif; ?>

            <div style="color:#6b7280;font-size:12px;margin-top:6px;">
              Registrada: <?php echo htmlspecialchars($queja["created_at"]); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_queja.php" style="margin:0;"
                    onsubmit="return confirm('¿Eliminar esta queja del historial?');">
                <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
                <button type="submit" class="btn btn--light">🗑 Eliminar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ==============================
       APOYOS DADOS DE ALTA
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <div class="calendar-head">
      <h2>Apoyos dados de alta
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
          (<?php echo count($apoyosActivos); ?>)
        </span>
      </h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="alta_apoyo.php" class="btn">+ Dar de alta</a>
        <a href="empleados_apoyo.php" class="section-link" style="align-self:center;">Ver detalle →</a>
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
            <strong><?php echo htmlspecialchars($apoyo["nombre"]); ?></strong>

            <?php if (!empty($apoyo["descripcion"])): ?>
            <div style="margin-top:4px;color:#6b7280;font-size:13px;">
              <?php echo htmlspecialchars($apoyo["descripcion"]); ?>
            </div>
            <?php endif; ?>

            <div style="margin-top:6px;font-size:12px;color:#9ca3af;">
              Registrado: <?php echo htmlspecialchars($apoyo["created_at"]); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_apoyo.php" style="margin:0;"
                    onsubmit="return confirm('¿Eliminar este apoyo?');">
                <input type="hidden" name="id" value="<?php echo (int)$apoyo["id"]; ?>">
                <button type="submit" class="btn btn--light">🗑 Eliminar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

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
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <a href="exportar_apoyos_excel.php" class="btn btn--light">Exportar a Excel</a>
        <a href="empleados_apoyo.php" class="section-link">Ver detalle →</a>
      </div>
    </div>

    <div class="list" style="margin-top:18px;">
      <?php if (empty($registrosApoyos)): ?>
        <div class="list-item">
          <strong>Sin registros</strong>
          <div>Aún no hay personas registradas en apoyos.</div>
        </div>
      <?php else: ?>
        <?php foreach ($registrosApoyos as $r): ?>
          <?php
            $estatusR = $r["estatus"] ?? "";
            $bcR = match(strtolower($estatusR)) {
                "entregado"  => "badge--entregado",
                "pendiente"  => "badge--pendiente",
                "cancelado"  => "badge--cancelada",
                "en proceso" => "badge--realizada",
                default      => "badge--pendiente",
            };
          ?>
          <div class="list-item">
            <div style="margin-bottom:6px;font-size:13px;font-weight:800;color:#7A1737;">
              Folio: APOYO-<?php echo str_pad((string)$r["id"], 6, "0", STR_PAD_LEFT); ?>
              <span class="badge <?php echo $bcR; ?>" style="margin-left:6px;">
                <?php echo htmlspecialchars($estatusR); ?>
              </span>
            </div>

            <strong><?php echo htmlspecialchars(nombreCompleto($r)); ?></strong>

            <div>
              <span class="apoyo-chip"><?php echo htmlspecialchars($r["apoyo"] ?? ""); ?></span>
            </div>

            <?php if (!empty($r["celular_1"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:4px;">
              📞 Cel 1: <?php echo htmlspecialchars($r["celular_1"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["celular_2"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              📞 Cel 2: <?php echo htmlspecialchars($r["celular_2"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["correo"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              ✉️ <?php echo htmlspecialchars($r["correo"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["municipio"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              📍 <?php echo htmlspecialchars($r["municipio"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["observaciones"])): ?>
            <div style="color:#6b7280;font-size:13px;margin-top:2px;">
              📝 <?php echo htmlspecialchars($r["observaciones"]); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($r["ine_path"])): ?>
  <?php $ineUrl = "/" . ltrim($r["ine_path"], "/"); ?>
  <div style="margin-top:8px;">
    <a href="<?php echo htmlspecialchars($ineUrl); ?>" target="_blank" class="btn btn--light">🪪 Ver INE</a>
  </div>
<?php endif; ?>

            <div style="color:#9ca3af;font-size:12px;margin-top:6px;">
              Registrado: <?php echo htmlspecialchars($r["created_at"]); ?>
            </div>

            <div style="margin-top:12px;">
              <form method="POST" action="eliminar_registro_apoyo.php" style="margin:0;"
                    onsubmit="return confirm('¿Eliminar este registro de apoyo?');">
                <input type="hidden" name="id" value="<?php echo (int)$r["id"]; ?>">
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

</body>
</html>