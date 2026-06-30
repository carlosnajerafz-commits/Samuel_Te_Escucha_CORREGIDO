<?php
require_once "includes/session.php";
require_once "includes/security_headers.php";
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "db.php";
require_once "includes/helpers.php";

require_auth();

// Auto-migración: agrega la columna si aún no existe
try {
    $pdo->exec("ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS categoria_vulnerable VARCHAR(150) DEFAULT ''");
} catch (PDOException $e) { /* ignorar si ya existe o sin permisos */ }

$mensaje = "";
if (isset($_GET["ok"]) || isset($_GET["actualizado"])) $mensaje = "Registro actualizado correctamente.";
elseif (isset($_GET["eliminado"]))                      $mensaje = "Registro eliminado correctamente.";

// Filtro por evento
$filtroEvento = (int)($_GET["evento"] ?? 0);

// Filtro de tiempo
$filtro = $_GET["filtro"] ?? "todo";
$condicionFecha = match($filtro) {
    "3dias"  => "r.created_at >= NOW() - INTERVAL '3 days'",
    "semana" => "r.created_at >= NOW() - INTERVAL '7 days'",
    "mes"    => "r.created_at >= NOW() - INTERVAL '1 month'",
    default  => "1=1",
};

// Búsqueda
$buscar = trim($_GET["buscar"] ?? "");
$condicionBusqueda = "";
$paramsBusqueda = [];
if ($buscar !== "") {
    $condicionBusqueda = "
        AND (
            r.nombre           ILIKE :buscar OR
            r.apellido_paterno ILIKE :buscar OR
            r.apellido_materno ILIKE :buscar OR
            r.correo           ILIKE :buscar OR
            r.celular_1        ILIKE :buscar
        )
    ";
    $paramsBusqueda[":buscar"] = "%$buscar%";
}

$condicionEvento = "";
$paramsEvento = [];
if ($filtroEvento > 0) {
    $condicionEvento = "AND r.evento_id = :evento_id";
    $paramsEvento[":evento_id"] = $filtroEvento;
}

$sql = "
    SELECT r.id, r.evento_id, r.nombre, r.apellido_paterno, r.apellido_materno,
           r.celular_1, r.celular_2, r.correo,
           r.calle, r.no_exterior, r.no_interior,
           r.colonia, r.municipio, r.codigo_postal,
           r.numero_personas, r.categoria_vulnerable, r.observaciones, r.estatus, r.created_at,
           e.fecha, e.hora_inicio, e.hora_fin, e.lugar
    FROM registros_comedor r
    LEFT JOIN eventos_comedor e ON e.id = r.evento_id
    WHERE $condicionFecha
      $condicionBusqueda
      $condicionEvento
    ORDER BY e.fecha ASC, r.created_at DESC
";

$params = array_merge($paramsBusqueda, $paramsEvento);
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Fallback si la columna categoria_vulnerable aún no existe en la BD
    $sqlFallback = str_replace("r.categoria_vulnerable, ", "", $sql);
    $stmt = $pdo->prepare($sqlFallback);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($registros as &$row) $row["categoria_vulnerable"] = "";
    unset($row);
}

// Lista de eventos para el filtro
$eventosLista = $pdo->query("
    SELECT id, fecha, hora_inicio, hora_fin, lugar
    FROM eventos_comedor
    ORDER BY fecha DESC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

function nombreCompleto(array $row): string {
    return trim(($row["nombre"] ?? "") . " " . ($row["apellido_paterno"] ?? "") . " " . ($row["apellido_materno"] ?? ""));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comedor Solidario — Registros | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .badge--pendiente   { background:#fef9c3; color:#92400e; }
    .badge--confirmado  { background:#dcfce7; color:#166534; }
    .badge--cancelado   { background:#fee2e2; color:#991b1b; }
    .badge--asistio     { background:#dbeafe; color:#1e40af; }
    .badge {
      display:inline-block; padding:2px 10px; border-radius:20px;
      font-size:11px; font-weight:700; text-transform:capitalize;
    }
    .alert-success {
      background:#dcfce7; color:#166534; border:1px solid #86efac;
      border-radius:10px; padding:12px 18px; margin-bottom:20px; font-weight:600;
    }
    .filter-bar {
      display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;
      background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px;
    }
    .filter-bar select,
    .filter-bar input[type="text"] {
      border:1px solid #d1d5db; border-radius:8px; padding:7px 12px;
      font-size:13px; color:#374151; background:#fff;
    }
    .filter-bar .btn { padding:7px 16px; font-size:13px; }
    .registro-card {
      background:#fff; border:1px solid #e5e7eb; border-radius:12px;
      padding:16px 20px; margin-bottom:12px;
    }
    .registro-card__header {
      display:flex; justify-content:space-between; align-items:flex-start;
      margin-bottom:10px; flex-wrap:wrap; gap:8px;
    }
    .registro-card__folio { font-size:13px; font-weight:800; color:#7A1737; }
    .registro-card__evento {
      font-size:12px; color:#6b7280;
      background:#f3f4f6; border-radius:8px; padding:3px 10px; margin-top:4px;
    }
    .registro-card__info { font-size:13px; color:#6b7280; margin-top:3px; }
    .registro-card__actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
    .categoria-row { display:flex; align-items:center; gap:10px; margin-top:10px; flex-wrap:wrap; }
    .categoria-row label { font-size:12px; font-weight:700; color:#374151; white-space:nowrap; }
    .categoria-select { border:1px solid #d1d5db; border-radius:8px; padding:5px 10px; font-size:12px; color:#374151; background:#fff; min-width:220px; }
    .categoria-select:focus { outline:none; border-color:#7A1737; box-shadow:0 0 0 3px rgba(122,23,55,.08); }
    .btn-cat { background:#7A1737; color:#fff; border:none; border-radius:8px; padding:5px 14px; font-size:12px; font-weight:700; cursor:pointer; }
    .btn-cat:hover { opacity:.85; }
    .badge--categoria { background:#f3e8ff; color:#6b21a8; }
  </style>
</head>
<body class="sticky-footer-page">

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
      <a href="empleados_comedor.php" style="color:#7A1737;font-weight:700;">Comedor</a>
      <a href="admin_comedor.php">Gestionar eventos</a>
      <a href="comedor_estadisticas.php">Estadísticas</a>
      <a href="exportar_comedor_excel.php">Exportar</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell sticky-footer-main">
  <div class="page-header">
    <h1>Comedor Solidario — Registros</h1>
    <p>Gestiona las inscripciones al Comedor Solidario.</p>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div class="alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
  <?php endif; ?>

  <!-- Filtros -->
  <form method="GET" class="filter-bar">
    <select name="evento">
      <option value="0">Todos los eventos</option>
      <?php foreach ($eventosLista as $ev): ?>
        <option value="<?php echo (int)$ev["id"]; ?>"
          <?php echo $filtroEvento == $ev["id"] ? "selected" : ""; ?>>
          <?php
            $f = date("d/m/Y", strtotime($ev["fecha"]));
            $h = substr($ev["hora_inicio"], 0, 5);
            $l = $ev["lugar"] ? " — " . mb_substr($ev["lugar"], 0, 30) : "";
            echo htmlspecialchars("$f $h$l");
          ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="filtro">
      <option value="todo"  <?php echo $filtro === "todo"  ? "selected" : ""; ?>>Todo el tiempo</option>
      <option value="mes"   <?php echo $filtro === "mes"   ? "selected" : ""; ?>>Último mes</option>
      <option value="semana"<?php echo $filtro === "semana"? "selected" : ""; ?>>Última semana</option>
      <option value="3dias" <?php echo $filtro === "3dias" ? "selected" : ""; ?>>Últimos 3 días</option>
    </select>
    <input type="text" name="buscar" placeholder="Buscar por nombre, celular..." value="<?php echo htmlspecialchars($buscar); ?>">
    <button type="submit" class="btn">Filtrar</button>
    <a href="empleados_comedor.php" class="btn btn--light">Limpiar</a>
    <a href="exportar_comedor_excel.php<?php echo $filtroEvento ? "?evento=$filtroEvento" : ""; ?>"
       class="btn btn--light">Exportar CSV</a>
  </form>

  <!-- Conteo -->
  <p style="margin-bottom:14px;font-size:13px;color:#6b7280;font-weight:600;">
    <?php echo count($registros); ?> registro(s) encontrado(s)
  </p>

  <!-- Lista de registros -->
  <?php if (empty($registros)): ?>
    <div class="list-item"><strong>Sin registros</strong><div>No hay inscripciones con los filtros seleccionados.</div></div>
  <?php else: ?>
    <?php foreach ($registros as $r): ?>
      <?php
        $folio = "COM-" . str_pad((string)$r["id"], 6, "0", STR_PAD_LEFT);
        $bc = "badge--" . strtolower($r["estatus"] ?? "pendiente");
        $fechaEvento = $r["fecha"] ? date("d/m/Y", strtotime($r["fecha"])) : "—";
        $horaEvento  = $r["hora_inicio"] ? substr($r["hora_inicio"], 0, 5) : "";
      ?>
      <div class="registro-card">
        <div class="registro-card__header">
          <div>
            <div class="registro-card__folio">
              <?php echo $folio; ?>
              <span class="badge <?php echo $bc; ?>" style="margin-left:8px;"><?php echo htmlspecialchars($r["estatus"]); ?></span>
            </div>
            <div class="registro-card__evento">
              📅 <?php echo htmlspecialchars($fechaEvento); ?>
              <?php if ($horaEvento): ?> 🕐 <?php echo htmlspecialchars($horaEvento); ?><?php endif; ?>
              <?php if (!empty($r["lugar"])): ?> · <?php echo htmlspecialchars(mb_substr($r["lugar"], 0, 40)); ?><?php endif; ?>
            </div>
          </div>
        </div>

        <strong><?php echo htmlspecialchars(nombreCompleto($r)); ?></strong>

        <div class="registro-card__info">👥 <?php echo (int)$r["numero_personas"]; ?> persona(s)</div>

        <?php if (!empty($r["celular_1"])): ?>
          <div class="registro-card__info">📞 <?php echo htmlspecialchars($r["celular_1"]); ?>
            <?php if (!empty($r["celular_2"])): ?> · <?php echo htmlspecialchars($r["celular_2"]); ?><?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($r["correo"])): ?>
          <div class="registro-card__info">✉️ <?php echo htmlspecialchars($r["correo"]); ?></div>
        <?php endif; ?>

        <?php
          $dir = trim(($r["calle"] ?? "") . " #" . ($r["no_exterior"] ?? ""));
          if (!empty($r["no_interior"])) $dir .= " Int. " . $r["no_interior"];
          if (!empty($r["colonia"]))     $dir .= ", Col. " . $r["colonia"];
          if (!empty($r["municipio"]))   $dir .= ", " . $r["municipio"];
          if (!empty($r["codigo_postal"])) $dir .= ", CP " . $r["codigo_postal"];
        ?>
        <?php if (!empty($r["calle"])): ?>
          <div class="registro-card__info">🏠 <?php echo htmlspecialchars($dir); ?></div>
        <?php endif; ?>


        <?php if (!empty($r["observaciones"])): ?>
          <div class="registro-card__info">📝 <?php echo htmlspecialchars($r["observaciones"]); ?></div>
        <?php endif; ?>

        <div class="registro-card__info" style="font-size:12px;color:#9ca3af;">
          Registrado: <?php echo htmlspecialchars($r["created_at"]); ?>
        </div>

        <?php if (!empty($r["categoria_vulnerable"])): ?>
          <div class="registro-card__info">
            <span class="badge badge--categoria">🏷️ <?php echo htmlspecialchars($r["categoria_vulnerable"]); ?></span>
          </div>
        <?php endif; ?>

        <!-- Selector de categoría vulnerable -->
        <?php
          $qActual = http_build_query(array_filter([
            "evento"  => $_GET["evento"]  ?? null,
            "filtro"  => $_GET["filtro"]  ?? null,
            "buscar"  => $_GET["buscar"]  ?? null,
          ]));
        ?>
        <form method="POST" action="actualizar_categoria_comedor.php" class="categoria-row">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id" value="<?php echo (int)$r["id"]; ?>">
          <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($qActual); ?>">
          <label>Categoría:</label>
          <select name="categoria_vulnerable" class="categoria-select">
            <option value="">— Sin categoría —</option>
            <?php
              foreach (categoriasVulnerables() as $cat):
                $sel = ($r["categoria_vulnerable"] ?? "") === $cat ? "selected" : "";
            ?>
              <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $sel; ?>>
                <?php echo htmlspecialchars($cat); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-cat">Guardar</button>
        </form>

        <div class="registro-card__actions">
          <?php if ($r["estatus"] === "pendiente"): ?>
            <form method="POST" action="actualizar_estatus_comedor.php" style="margin:0;">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id" value="<?php echo (int)$r["id"]; ?>">
              <input type="hidden" name="estatus" value="confirmado">
              <button type="submit" class="btn" style="font-size:12px;padding:6px 14px;">✔ Confirmar</button>
            </form>
            <form method="POST" action="actualizar_estatus_comedor.php" style="margin:0;">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id" value="<?php echo (int)$r["id"]; ?>">
              <input type="hidden" name="estatus" value="cancelado">
              <button type="submit" class="btn btn--light" style="font-size:12px;padding:6px 14px;"
                      onclick="return confirm('¿Cancelar este registro?');">✘ Cancelar</button>
            </form>
          <?php elseif ($r["estatus"] === "confirmado"): ?>
            <form method="POST" action="actualizar_estatus_comedor.php" style="margin:0;">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id" value="<?php echo (int)$r["id"]; ?>">
              <input type="hidden" name="estatus" value="asistio">
              <button type="submit" class="btn" style="font-size:12px;padding:6px 14px;">✔ Asistió</button>
            </form>
            <form method="POST" action="actualizar_estatus_comedor.php" style="margin:0;">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id" value="<?php echo (int)$r["id"]; ?>">
              <input type="hidden" name="estatus" value="cancelado">
              <button type="submit" class="btn btn--light" style="font-size:12px;padding:6px 14px;"
                      onclick="return confirm('¿Cancelar este registro?');">✘ Cancelar</button>
            </form>
          <?php endif; ?>
          <form method="POST" action="eliminar_registro_comedor.php" style="margin:0;"
                onsubmit="return confirm('¿Eliminar este registro?');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo (int)$r["id"]; ?>">
            <button type="submit" class="btn btn--light" style="font-size:12px;padding:6px 14px;">🗑 Eliminar</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
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
