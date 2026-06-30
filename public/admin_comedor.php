<?php
require_once "includes/session.php";
require_once "db.php";
require_once "includes/security_headers.php";
require_once "includes/csrf.php";
require_once "includes/helpers.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

// Auto-migración: columnas tipo_comedor, grupo_dirigido y menu
try {
    $pdo->exec("ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS tipo_comedor VARCHAR(100) DEFAULT ''");
    $pdo->exec("ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS grupo_dirigido VARCHAR(150) DEFAULT ''");
    $pdo->exec("ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS menu TEXT DEFAULT ''");
} catch (PDOException $e) { /* ignorar */ }

$mensaje = "";
$error   = "";

if (isset($_GET["ok"]))                $mensaje = "Evento creado correctamente.";
elseif (isset($_GET["eliminado"]))     $mensaje = "Evento eliminado correctamente.";
elseif (isset($_GET["desactivado"]))   $mensaje = "Evento desactivado.";
elseif (isset($_GET["activado"]))      $mensaje = "Evento activado.";
elseif (isset($_GET["menu_ok"]))       $mensaje = "Menú actualizado correctamente.";
elseif (isset($_GET["error"])) {
    $error = match($_GET["error"]) {
        "fecha"   => "La fecha es obligatoria.",
        "hora"    => "La hora de inicio debe ser anterior a la hora de fin.",
        "cupo"    => "El cupo máximo debe ser un número positivo o 0.",
        default   => "Error al guardar el evento.",
    };
}

// Próximos eventos (activos e inactivos)
$proximosEventos = $pdo->query("
    SELECT e.id, e.fecha, e.hora_inicio, e.hora_fin, e.lugar, e.descripcion,
           e.cupo_maximo, e.activo,
           COALESCE(e.tipo_comedor, '') AS tipo_comedor,
           COALESCE(e.grupo_dirigido, '') AS grupo_dirigido,
           COALESCE(e.menu, '') AS menu,
           COALESCE(SUM(r.numero_personas), 0) AS personas_registradas,
           COUNT(r.id) AS registros_count
    FROM eventos_comedor e
    LEFT JOIN registros_comedor r
           ON r.evento_id = e.id AND r.estatus != 'cancelado'
    WHERE e.fecha >= CURRENT_DATE
    GROUP BY e.id
    ORDER BY e.fecha ASC, e.hora_inicio ASC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$eventosHistorial = $pdo->query("
    SELECT e.id, e.fecha, e.hora_inicio, e.hora_fin, e.lugar, e.descripcion,
           e.cupo_maximo, e.activo,
           COALESCE(e.tipo_comedor, '') AS tipo_comedor,
           COALESCE(e.grupo_dirigido, '') AS grupo_dirigido,
           COALESCE(e.menu, '') AS menu,
           COALESCE(SUM(r.numero_personas), 0) AS personas_registradas,
           COUNT(r.id) AS registros_count
    FROM eventos_comedor e
    LEFT JOIN registros_comedor r
           ON r.evento_id = e.id AND r.estatus != 'cancelado'
    WHERE e.fecha < CURRENT_DATE
    GROUP BY e.id
    ORDER BY e.fecha DESC, e.hora_inicio DESC
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Comedor Solidario | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .evento-row {
      background:#fff; border:1px solid #e5e7eb; border-radius:12px;
      padding:16px 20px; margin-bottom:12px;
      display:flex; align-items:center; gap:20px; flex-wrap:wrap;
    }
    .evento-row__info { flex:1; min-width:200px; }
    .evento-row__fecha { font-weight:800; color:#7A1737; font-size:16px; margin-bottom:4px; }
    .evento-row__detail { font-size:13px; color:#6b7280; margin-top:2px; }
    .evento-row__actions { display:flex; gap:8px; flex-wrap:wrap; }
    .badge-activo   { background:#dcfce7; color:#166534; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-inactivo { background:#f3f4f6; color:#6b7280; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .alert-success { background:#dcfce7; color:#166534; border:1px solid #86efac; border-radius:10px; padding:12px 18px; margin-bottom:20px; font-weight:600; }
    .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; border-radius:10px; padding:12px 18px; margin-bottom:20px; font-weight:600; }
    .menu-row { display:flex; align-items:center; gap:10px; margin-top:10px; flex-wrap:wrap; width:100%; }
    .menu-row label { font-size:12px; font-weight:700; color:#374151; white-space:nowrap; }
    .menu-row input[type="text"] { flex:1; min-width:220px; border:1px solid #d1d5db; border-radius:8px; padding:5px 10px; font-size:12px; color:#374151; background:#fff; }
    .menu-row input[type="text"]:focus { outline:none; border-color:#7A1737; box-shadow:0 0 0 3px rgba(122,23,55,.08); }
    .btn-menu { background:#7A1737; color:#fff; border:none; border-radius:8px; padding:5px 14px; font-size:12px; font-weight:700; cursor:pointer; }
    .btn-menu:hover { opacity:.85; }
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
      <a href="admin_comedor.php" style="color:#7A1737;font-weight:700;">Comedor</a>
      <a href="comedor_estadisticas.php">Estadísticas</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell">
  <div class="page-header">
    <h1>Comedor Solidario — Gestión de Eventos</h1>
    <p>Da de alta las fechas y horarios de los eventos del comedor solidario.</p>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
  <?php endif; ?>
  <?php if ($error !== ""): ?>
    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- ====== FORMULARIO NUEVO EVENTO ====== -->
  <section class="form-card" style="margin-bottom:32px;">
    <h2 style="margin-bottom:20px;font-size:18px;color:#7A1737;">Agregar nuevo evento</h2>
    <form method="POST" action="guardar_evento_comedor.php">
      <?php echo csrf_field(); ?>
      <div class="form-grid">

        <div class="form-group">
          <label>Tipo de comedor</label>
          <select name="tipo_comedor">
            <option value="">— Seleccionar —</option>
            <option value="Desayuno solidario">Desayuno solidario</option>
            <option value="Comida solidaria">Comida solidaria</option>
            <option value="Cena solidaria">Cena solidaria</option>
            <option value="Comedor comunitario">Comedor comunitario</option>
            <option value="Desayuno y comida">Desayuno y comida</option>
          </select>
        </div>

        <div class="form-group">
          <label>Dirigido a</label>
          <select name="grupo_dirigido">
            <option value="">— Seleccionar grupo —</option>
            <?php foreach (categoriasVulnerables(true) as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Fecha del evento</label>
          <input type="date" name="fecha" required min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
          <label>Hora de inicio</label>
          <input type="time" name="hora_inicio" required value="10:00">
        </div>

        <div class="form-group">
          <label>Hora de fin</label>
          <input type="time" name="hora_fin" required value="14:00">
        </div>

        <div class="form-group">
          <label>Lugar</label>
          <input type="text" name="lugar" placeholder="Ej: Explanada del palacio municipal..." maxlength="255">
        </div>

        <div class="form-group">
          <label>Cupo máximo de personas <small style="color:#9ca3af;">(0 = sin límite)</small></label>
          <input type="number" name="cupo_maximo" min="0" value="0">
        </div>

        <div class="form-group form-group--full">
          <label>Descripción del evento</label>
          <textarea name="descripcion" maxlength="500" placeholder="Información adicional sobre el evento..."></textarea>
        </div>

        <div class="form-group form-group--full">
          <label>Menú — ¿qué se va a servir?</label>
          <textarea name="menu" maxlength="500" placeholder="Ej: Arroz, pollo guisado, frijoles y agua de fruta..."></textarea>
        </div>

      </div>
      <div class="form-actions">
        <a href="dashboard.php" class="btn btn--light">Volver</a>
        <button type="submit" class="btn">Guardar evento</button>
      </div>
    </form>
  </section>

  <!-- ====== PRÓXIMOS EVENTOS ====== -->
  <section class="dashboard-card" style="margin-bottom:22px;">
    <div class="calendar-head">
      <h2>Próximos eventos
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">(<?php echo count($proximosEventos); ?>)</span>
      </h2>
      <a href="empleados_comedor.php" style="font-size:13px;color:#7A1737;font-weight:600;text-decoration:none;">Ver registros →</a>
    </div>

    <div style="margin-top:18px;">
      <?php if (empty($proximosEventos)): ?>
        <div class="list-item"><strong>Sin eventos programados</strong></div>
      <?php else: ?>
        <?php foreach ($proximosEventos as $ev): ?>
          <?php
            $fechaFmt = date("d/m/Y", strtotime($ev["fecha"]));
            $diasSemana = ["Dom","Lun","Mar","Mié","Jue","Vie","Sáb"];
            $dia = $diasSemana[date("w", strtotime($ev["fecha"]))];
            $hI = substr($ev["hora_inicio"], 0, 5);
            $hF = substr($ev["hora_fin"], 0, 5);
            $cupo = (int)$ev["cupo_maximo"];
            $ocupado = (int)$ev["personas_registradas"];
          ?>
          <div class="evento-row">
            <div class="evento-row__info">
              <div class="evento-row__fecha">
                <?php echo htmlspecialchars("$dia $fechaFmt"); ?>
                &nbsp;
                <span class="<?php echo $ev["activo"] ? 'badge-activo' : 'badge-inactivo'; ?>">
                  <?php echo $ev["activo"] ? "Activo" : "Inactivo"; ?>
                </span>
              </div>
              <div class="evento-row__detail">🕐 <?php echo htmlspecialchars("$hI – $hF"); ?></div>
              <?php if (!empty($ev["lugar"])): ?>
                <div class="evento-row__detail">📍 <?php echo htmlspecialchars($ev["lugar"]); ?></div>
              <?php endif; ?>
              <?php if (!empty($ev["tipo_comedor"])): ?>
                <div class="evento-row__detail">🍽️ <?php echo htmlspecialchars($ev["tipo_comedor"]); ?></div>
              <?php endif; ?>
              <?php if (!empty($ev["grupo_dirigido"])): ?>
                <div class="evento-row__detail">🏷️ Dirigido a: <strong><?php echo htmlspecialchars($ev["grupo_dirigido"]); ?></strong></div>
              <?php endif; ?>
              <div class="evento-row__detail">
                👥 <?php echo $ocupado; ?> persona(s) registrada(s)
                <?php if ($cupo > 0): ?>
                  de <?php echo $cupo; ?> disponibles
                <?php endif; ?>
                &nbsp;·&nbsp;
                <?php echo (int)$ev["registros_count"]; ?> solicitud(es)
              </div>
              <?php if (!empty($ev["menu"])): ?>
                <div class="evento-row__detail">🍽️ Menú: <?php echo htmlspecialchars($ev["menu"]); ?></div>
              <?php endif; ?>
              <form method="POST" action="actualizar_menu_comedor.php" class="menu-row">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$ev["id"]; ?>">
                <label>Menú:</label>
                <input type="text" name="menu" maxlength="500" placeholder="¿Qué se va a servir?"
                       value="<?php echo htmlspecialchars($ev["menu"]); ?>">
                <button type="submit" class="btn-menu">Guardar</button>
              </form>
            </div>
            <div class="evento-row__actions">
              <a href="empleados_comedor.php?evento=<?php echo (int)$ev["id"]; ?>"
                 class="btn btn--light" style="font-size:13px;padding:6px 14px;">Ver registros</a>
              <form method="POST" action="eliminar_evento_comedor.php" style="margin:0;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$ev["id"]; ?>">
                <input type="hidden" name="accion" value="<?php echo $ev["activo"] ? 'desactivar' : 'activar'; ?>">
                <button type="submit" class="btn btn--light" style="font-size:13px;padding:6px 14px;">
                  <?php echo $ev["activo"] ? "Desactivar" : "Activar"; ?>
                </button>
              </form>
              <form method="POST" action="eliminar_evento_comedor.php" style="margin:0;"
                    onsubmit="return confirm('¿Eliminar este evento y todos sus registros?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$ev["id"]; ?>">
                <input type="hidden" name="accion" value="eliminar">
                <button type="submit" class="btn btn--light" style="font-size:13px;padding:6px 14px;">🗑 Eliminar</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ====== HISTORIAL DE EVENTOS ====== -->
  <?php if (!empty($eventosHistorial)): ?>
  <section class="dashboard-card">
    <div class="calendar-head">
      <h2>Historial de eventos
        <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">(últimos 30)</span>
      </h2>
    </div>
    <div style="margin-top:18px;">
      <?php foreach ($eventosHistorial as $ev): ?>
        <?php
          $fechaFmt = date("d/m/Y", strtotime($ev["fecha"]));
          $hI = substr($ev["hora_inicio"], 0, 5);
          $hF = substr($ev["hora_fin"], 0, 5);
        ?>
        <div class="evento-row">
          <div class="evento-row__info">
            <div class="evento-row__fecha" style="color:#6b7280;">
              <?php echo htmlspecialchars($fechaFmt); ?>
            </div>
            <div class="evento-row__detail">🕐 <?php echo htmlspecialchars("$hI – $hF"); ?></div>
            <?php if (!empty($ev["lugar"])): ?>
              <div class="evento-row__detail">📍 <?php echo htmlspecialchars($ev["lugar"]); ?></div>
            <?php endif; ?>
            <div class="evento-row__detail">
              👥 <?php echo (int)$ev["personas_registradas"]; ?> persona(s) · <?php echo (int)$ev["registros_count"]; ?> solicitud(es)
            </div>
            <?php if (!empty($ev["menu"])): ?>
              <div class="evento-row__detail">🍽️ Menú: <?php echo htmlspecialchars($ev["menu"]); ?></div>
            <?php endif; ?>
          </div>
          <div class="evento-row__actions">
            <a href="empleados_comedor.php?evento=<?php echo (int)$ev["id"]; ?>"
               class="btn btn--light" style="font-size:13px;padding:6px 14px;">Ver registros</a>
            <form method="POST" action="eliminar_evento_comedor.php" style="margin:0;"
                  onsubmit="return confirm('¿Eliminar este evento?');">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id" value="<?php echo (int)$ev["id"]; ?>">
              <input type="hidden" name="accion" value="eliminar">
              <button type="submit" class="btn btn--light" style="font-size:13px;padding:6px 14px;">🗑 Eliminar</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
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

</body>
</html>
