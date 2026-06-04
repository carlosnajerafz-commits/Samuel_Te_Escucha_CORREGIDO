<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

/* =========================================
   BÚSQUEDA POR CP
========================================= */
$buscarCp = trim($_GET["cp"] ?? "");
$coloniasDeCp = [];
$municipioDeCp = "";

if ($buscarCp !== "" && preg_match('/^[0-9]{5}$/', $buscarCp)) {
    $stmtCp = $pdo->prepare("
        SELECT id, colonia, municipio
        FROM codigos_postales
        WHERE codigo_postal = :cp
        ORDER BY colonia ASC
    ");
    $stmtCp->execute([":cp" => $buscarCp]);
    $coloniasDeCp = $stmtCp->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!empty($coloniasDeCp)) {
        $municipioDeCp = $coloniasDeCp[0]["municipio"];
    }
}

/* =========================================
   TODOS LOS CPs (agrupados)
========================================= */
$stmtTodos = $pdo->query("
    SELECT codigo_postal, municipio, COUNT(*) as total
    FROM codigos_postales
    GROUP BY codigo_postal, municipio
    ORDER BY codigo_postal ASC
");
$todosCps = $stmtTodos->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* =========================================
   MENSAJES
========================================= */
$mensaje = "";
$tipoMsg = "success";

if (isset($_GET["ok"])) {
    $mensaje = $_GET["ok"] === "agregado"
        ? "Colonia agregada correctamente."
        : "Colonia eliminada correctamente.";
} elseif (isset($_GET["error"])) {
    $tipoMsg = "error";
    $mensaje = match($_GET["error"]) {
        "campos"    => "Faltan campos obligatorios.",
        "duplicado" => "Esa colonia ya existe en ese código postal.",
        default     => "Ocurrió un error.",
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Códigos Postales | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .cp-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
      margin-top: 16px;
    }
    .cp-table th {
      text-align: left;
      padding: 10px 14px;
      background: #fdf2f5;
      color: #7A1737;
      font-weight: 700;
      border-bottom: 2px solid #f0c2cd;
    }
    .cp-table td {
      padding: 10px 14px;
      border-bottom: 1px solid #f3f4f6;
      vertical-align: middle;
    }
    .cp-table tr:last-child td { border-bottom: none; }
    .cp-table tr:hover td { background: #fafafa; }

    .cp-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 10px;
      margin-top: 16px;
    }
    .cp-chip {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 10px 14px;
      text-align: center;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
      color: #374151;
      text-decoration: none;
      transition: border-color .15s, color .15s;
    }
    .cp-chip:hover, .cp-chip.active {
      border-color: #7A1737;
      color: #7A1737;
      background: #fdf2f5;
    }
    .cp-chip small {
      display: block;
      font-size: 11px;
      font-weight: 400;
      color: #9ca3af;
      margin-top: 3px;
    }

    .alert-success {
      background: #dcfce7; color: #166534; border: 1px solid #86efac;
      border-radius: 10px; padding: 12px 18px; margin-bottom: 18px; font-weight: 600;
    }
    .alert-error {
      background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;
      border-radius: 10px; padding: 12px 18px; margin-bottom: 18px; font-weight: 600;
    }

    .form-inline {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: flex-end;
      margin-top: 20px;
    }
    .form-inline .form-group {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .form-inline label {
      font-size: 12px;
      font-weight: 700;
      color: #6b7280;
      text-transform: uppercase;
    }
    .form-inline input {
      padding: 9px 13px;
      border: 1px solid #d1d5db;
      border-radius: 9px;
      font-size: 14px;
      min-width: 160px;
    }
    .form-inline input:focus {
      outline: none;
      border-color: #7A1737;
    }

    .badge-count {
      display: inline-block;
      background: #7A1737;
      color: #fff;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      padding: 1px 8px;
      margin-left: 6px;
      vertical-align: middle;
    }

    @media (max-width: 600px) {
      .cp-table thead { display: none; }
      .cp-table tr { display: block; margin-bottom: 10px;
        border: 1px solid #e5e7eb; border-radius: 10px; }
      .cp-table td { display: block; padding: 8px 14px; }
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
        <span class="brand__sub">Códigos postales</span>
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
      <h1>Gestión de códigos postales</h1>
      <p style="margin:8px 0 0;color:#6b7280;">Agrega y administra los códigos postales y sus colonias.</p>
    </div>
    <a href="dashboard.php" class="btn btn--light">Volver al panel</a>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div class="alert-<?php echo $tipoMsg; ?>">
      <?php echo htmlspecialchars($mensaje); ?>
    </div>
  <?php endif; ?>

  <!-- ==============================
       AGREGAR NUEVO CP / COLONIA
  ============================== -->
  <section class="dashboard-card">
    <h2>Agregar colonia</h2>
    <p style="color:#6b7280;font-size:14px;margin-top:4px;">
      Si el código postal ya existe, solo se agrega la nueva colonia. Si es nuevo, se crea completo.
    </p>

    <form method="POST" action="guardar_cp.php">
      <input type="hidden" name="accion" value="agregar">
      <div class="form-inline">
        <div class="form-group">
          <label>Código postal *</label>
          <input
            type="text"
            name="codigo_postal"
            maxlength="5"
            pattern="[0-9]{5}"
            placeholder="55740"
            value="<?php echo htmlspecialchars($buscarCp); ?>"
            required
            oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,5); autocompletarMunicipio(this.value);"
          >
        </div>

        <div class="form-group">
          <label>Colonia *</label>
          <input type="text" name="colonia" placeholder="Nombre de la colonia" required style="min-width:220px;">
        </div>

        <div class="form-group">
          <label>Municipio *</label>
          <input type="text" name="municipio" id="municipioInput" placeholder="Tecámac" required>
        </div>

        <div class="form-group" style="justify-content:flex-end;">
          <button type="submit" class="btn">+ Agregar</button>
        </div>
      </div>
    </form>
  </section>

  <!-- ==============================
       BUSCAR CP
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Buscar código postal</h2>

    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
      <input
        type="text"
        name="cp"
        maxlength="5"
        pattern="[0-9]{5}"
        placeholder="Ej: 55740"
        value="<?php echo htmlspecialchars($buscarCp); ?>"
        style="padding:9px 14px;border:1px solid #d1d5db;border-radius:9px;font-size:14px;width:160px;"
        oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,5);"
      >
      <button type="submit" class="btn">Buscar</button>
      <?php if ($buscarCp !== ""): ?>
        <a href="admin_cp.php" class="btn btn--light">Limpiar</a>
      <?php endif; ?>
    </form>

    <?php if ($buscarCp !== "" && empty($coloniasDeCp)): ?>
      <div style="margin-top:16px;color:#6b7280;">No se encontraron colonias para el CP <strong><?php echo htmlspecialchars($buscarCp); ?></strong>.</div>

    <?php elseif (!empty($coloniasDeCp)): ?>
      <div style="margin-top:16px;">
        <strong>CP <?php echo htmlspecialchars($buscarCp); ?></strong>
        — <?php echo htmlspecialchars($municipioDeCp); ?>
        <span class="badge-count"><?php echo count($coloniasDeCp); ?> colonias</span>
      </div>

      <table class="cp-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Colonia</th>
            <th>Municipio</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($coloniasDeCp as $i => $row): ?>
            <tr>
              <td style="color:#9ca3af;font-size:12px;"><?php echo $i + 1; ?></td>
              <td><?php echo htmlspecialchars($row["colonia"]); ?></td>
              <td style="color:#6b7280;"><?php echo htmlspecialchars($row["municipio"]); ?></td>
              <td>
                <form method="POST" action="guardar_cp.php" style="margin:0;"
                      onsubmit="return confirm('¿Eliminar la colonia «<?php echo htmlspecialchars($row["colonia"]); ?>»?');">
                  <input type="hidden" name="accion"     value="eliminar">
                  <input type="hidden" name="id"         value="<?php echo (int)$row["id"]; ?>">
                  <input type="hidden" name="cp_retorno" value="<?php echo htmlspecialchars($buscarCp); ?>">
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
       TODOS LOS CPs
  ============================== -->
  <section class="dashboard-card" style="margin-top:22px;">
    <h2>Todos los códigos postales
      <span style="font-size:14px;font-weight:400;color:#6b7280;margin-left:8px;">
        (<?php echo count($todosCps); ?> CPs registrados)
      </span>
    </h2>

    <?php if (empty($todosCps)): ?>
      <p style="color:#6b7280;margin-top:14px;">No hay códigos postales registrados aún.</p>
    <?php else: ?>
      <div class="cp-grid">
        <?php foreach ($todosCps as $cp): ?>
          <a href="admin_cp.php?cp=<?php echo urlencode($cp["codigo_postal"]); ?>"
             class="cp-chip <?php echo $buscarCp === $cp["codigo_postal"] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cp["codigo_postal"]); ?>
            <small><?php echo htmlspecialchars($cp["municipio"]); ?></small>
            <small><?php echo $cp["total"]; ?> col.</small>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<!-- ====== FOOTER ====== -->
<footer class="footer">
  <div class="wrap footer__inner">
    <div>© 2026 Samuel Te Escucha</div>
    <div>Panel interno</div>
  </div>
</footer>

<script>
// Autocompletar municipio si el CP ya existe en la BD
async function autocompletarMunicipio(cp) {
  if (cp.length !== 5) return;
  try {
    const res = await fetch('buscar_cp.php?cp=' + encodeURIComponent(cp));
    const data = await res.json();
    if (data && data.municipio) {
      document.getElementById('municipioInput').value = data.municipio;
    }
  } catch(e) {}
}
</script>

</body>
</html>
