<?php
require_once __DIR__ . "/includes/security_headers.php";
require_once __DIR__ . "/includes/session.php";
require_once "db.php";
require_once "includes/helpers.php";

// Verificar modo mantenimiento
if (file_exists(__DIR__ . "/maintenance.flag")) {
    include "maintenance.php";
    exit;
}

// Auto-migración defensiva: columna menu
try {
    $pdo->exec("ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS menu TEXT DEFAULT ''");
} catch (PDOException $e) { /* ignorar */ }

// Obtener próximos eventos activos
$eventos = $pdo->query("
    SELECT id, fecha, hora_inicio, hora_fin, lugar, descripcion, cupo_maximo, COALESCE(menu, '') AS menu
    FROM eventos_comedor
    WHERE activo = TRUE AND fecha >= CURRENT_DATE
    ORDER BY fecha ASC, hora_inicio ASC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Cupo ocupado por evento
$cuposOcupados = [];
if (!empty($eventos)) {
    $ids = implode(",", array_map(fn($e) => (int)$e["id"], $eventos));
    $rows = $pdo->query("
        SELECT evento_id, COALESCE(SUM(numero_personas),0) AS personas
        FROM registros_comedor
        WHERE evento_id IN ($ids) AND estatus != 'cancelado'
        GROUP BY evento_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $cuposOcupados[$r["evento_id"]] = (int)$r["personas"];
    }
}

$mostrarModal = false;
$modalTitulo  = "";
$modalMensaje = "";
$modalTipo    = "";

if (isset($_GET["ok"])) {
    $mostrarModal = true;
    $modalTitulo  = "Registro exitoso";
    $id           = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
    $folio        = str_pad($id, 6, "0", STR_PAD_LEFT);
    $modalMensaje = "¡Tu registro fue enviado con éxito!\n\nFolio: #$folio\n\nNos pondremos en contacto contigo para confirmar tu asistencia.";
    $modalTipo    = "success";
}

if (isset($_GET["error"])) {
    $mostrarModal = true;
    $modalTitulo  = "Error";
    $modalMensaje = match($_GET["error"]) {
        "limite"   => "Ya enviaste 3 registros al comedor en la última hora. Por favor espera un momento antes de intentar de nuevo.",
        "celular"  => "El número de celular debe tener exactamente 10 dígitos.",
        "evento"   => "El evento seleccionado no existe o ya no está disponible.",
        "cupo"     => "Lo sentimos, el evento ya alcanzó su cupo máximo.",
        "personas" => "El número de personas debe ser entre 1 y 20.",
        "categoria"=> "Selecciona el grupo vulnerable al que perteneces.",
        default    => "No fue posible registrarte. Verifica los datos e inténtalo de nuevo.",
    };
    $modalTipo = "error";
}

$eventoSeleccionado = isset($_GET["evento"]) ? (int)$_GET["evento"] : 0;

$diasSemana = ["Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comedor Solidario | Samuel Te Escucha</title>
  <style>
    :root { --guinda: rgb(122,23,55); --bg: #f3f4f6; --border: #e5e7eb; --shadow: 0 14px 38px rgba(17,24,39,.08); }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg); color: #1f2937; display:flex; flex-direction:column; min-height:100vh; }
    a { text-decoration: none; color: inherit; }

    /* TOPBAR */
    .topbar { background:#fff; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; padding:12px 0; }
    .topbar-inner { max-width:1200px; margin:0 auto; padding:0 20px; display:flex; align-items:center; justify-content:space-between; }
    .brand { display:flex; align-items:center; gap:14px; }
    .brand-logo { width:55px; height:55px; border-radius:12px; overflow:hidden; border:1px solid var(--border); }
    .brand-logo img { width:100%; height:100%; object-fit:contain; }
    .brand h2 { margin:0; color:var(--guinda); font-size:22px; font-weight:800; }
    .nav a { padding:10px 15px; border-radius:10px; font-weight:700; color:#4b5563; transition:.2s; }
    .nav a:hover { background:var(--bg); color:var(--guinda); }

    /* SHELL */
    .wrap { max-width:1200px; margin:0 auto; padding:0 20px; }
    .page-shell { padding:40px 0 80px; flex:1; }
    .page-header { margin-bottom:30px; }
    .page-header h1 { margin:0 0 8px; color:var(--guinda); font-size:30px; font-weight:800; }
    .page-header p { margin:0; color:#6b7280; font-size:16px; }

    /* EVENTOS */
    .eventos-titulo { font-size:18px; font-weight:800; color:var(--guinda); margin-bottom:16px; }
    .eventos-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:14px; margin-bottom:32px; }
    .evento-card {
      background:#fff; border:2px solid var(--border); border-radius:14px; padding:18px 20px;
      cursor:pointer; transition:border-color .2s, box-shadow .2s; position:relative;
    }
    .evento-card:hover { border-color:var(--guinda); box-shadow:0 4px 16px rgba(122,23,55,.12); }
    .evento-card.selected { border-color:var(--guinda); background:#fdf2f5; }
    .evento-card.sin-cupo { opacity:.55; cursor:not-allowed; }
    .evento-card__check {
      position:absolute; top:12px; right:12px; width:22px; height:22px; border-radius:50%;
      background:var(--guinda); color:#fff; font-size:13px; font-weight:700;
      display:none; align-items:center; justify-content:center;
    }
    .evento-card.selected .evento-card__check { display:flex; }
    .evento-card__fecha { font-size:17px; font-weight:800; color:var(--guinda); margin-bottom:5px; }
    .evento-card__hora { font-size:14px; color:#374151; font-weight:600; margin-bottom:3px; }
    .evento-card__lugar { font-size:13px; color:#6b7280; margin-bottom:5px; }
    .evento-card__desc { font-size:13px; color:#6b7280; margin-bottom:8px; }
    .chip { display:inline-block; font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; }
    .chip--disponible { background:#dcfce7; color:#166534; }
    .chip--lleno      { background:#fee2e2; color:#991b1b; }
    .chip--abierto    { background:#dbeafe; color:#1e40af; }

    /* FORM */
    .form-card { background:#fff; border:1px solid var(--border); border-radius:24px; padding:35px; box-shadow:var(--shadow); }
    .form-section-title { font-size:18px; font-weight:800; color:var(--guinda); margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border); }
    .form-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
    .form-group { display:flex; flex-direction:column; gap:8px; }
    .form-group.full { grid-column:1/-1; }
    .form-group.half { grid-column:span 2; }
    label { font-weight:700; color:#374151; font-size:14px; }
    input, textarea, select {
      width:100%; min-height:48px; border:1px solid #d1d5db; border-radius:12px;
      padding:12px 16px; font:inherit; background:#fff;
    }
    input:focus, textarea:focus, select:focus { outline:none; border-color:var(--guinda); box-shadow:0 0 0 4px rgba(122,23,55,.08); }
    textarea { min-height:90px; resize:vertical; }
    .form-actions { margin-top:30px; display:flex; justify-content:flex-end; gap:14px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; min-height:48px; padding:0 28px; border-radius:12px; background:var(--guinda); color:#fff; font-weight:700; border:none; cursor:pointer; font:inherit; transition:.2s; }
    .btn:hover { opacity:.88; transform:translateY(-1px); }
    .btn-light { background:#f3f4f6; color:#374151; }

    /* MODAL */
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1000; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
    .modal { background:#fff; border-radius:24px; padding:35px; width:min(480px,90%); text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); }
    .modal h3 { font-size:22px; margin-bottom:10px; }
    .modal p { color:#6b7280; margin-bottom:20px; white-space:pre-line; }

    /* SIN EVENTOS */
    .sin-eventos { background:#fff; border:1px solid var(--border); border-radius:14px; padding:40px 24px; text-align:center; color:#6b7280; }
    .sin-eventos h3 { color:#374151; margin-bottom:8px; font-size:20px; }

    /* FOOTER */
    .footer { background:var(--guinda); padding:0 20px; }
    .footer-inner { max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; min-height:62px; flex-wrap:wrap; gap:10px; color:#fff; font-size:14px; }
    .footer-logo { height:32px; object-fit:contain; filter:brightness(0) invert(1); }
    .footer a { color:rgba(255,255,255,.85); }
    .footer a:hover { color:#fff; }

    @media (max-width:900px) {
      .form-grid { grid-template-columns:1fr 1fr; }
      .form-group.full { grid-column:1/-1; }
      .form-group.half { grid-column:1/-1; }
    }
    @media (max-width:600px) {
      .form-grid { grid-template-columns:1fr; }
      .form-card { padding:20px; }
      .eventos-grid { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

<?php if ($mostrarModal): ?>
  <div class="modal-overlay">
    <div class="modal">
      <h3 style="color:<?php echo $modalTipo === 'success' ? '#15803d' : '#b91c1c'; ?>">
        <?php echo htmlspecialchars($modalTitulo); ?>
      </h3>
      <p><?php echo htmlspecialchars($modalMensaje); ?></p>
      <button class="btn" onclick="window.location.href='comedor.php'">Aceptar</button>
    </div>
  </div>
<?php endif; ?>

<header class="topbar">
  <div class="topbar-inner">
    <div class="brand">
      <div class="brand-logo"><img src="assets/img/legislatura3.png" alt="Samuel Te Escucha"></div>
      <div><h2>Samuel Te Escucha</h2></div>
    </div>
    <nav class="nav">
      <a href="index.php">Inicio</a>
      <a href="cita.php">Citas</a>
      <a href="queja.php">Gestión</a>
      <a href="apoyos.php">Apoyos</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell">
  <div class="page-header">
    <h1>Comedor Solidario</h1>
    <p>Consulta las próximas fechas del comedor y regístrate para asistir.</p>
  </div>

  <?php if (empty($eventos)): ?>
    <div class="sin-eventos">
      <h3>Sin fechas disponibles por el momento</h3>
      <p>Próximamente se publicarán nuevas fechas del Comedor Solidario. ¡Visítanos pronto!</p>
      <a href="index.php" class="btn btn-light" style="margin-top:20px;display:inline-flex;">← Volver al inicio</a>
    </div>
  <?php else: ?>

    <form method="POST" action="guardar_comedor.php" id="comedorForm">

      <!-- PASO 1: ELEGIR EVENTO -->
      <div class="form-card" style="margin-bottom:24px;">
        <p class="form-section-title">1. Selecciona la fecha del evento</p>

        <div class="eventos-grid" id="eventosGrid">
          <?php foreach ($eventos as $ev): ?>
            <?php
              $personasOcupadas = $cuposOcupados[$ev["id"]] ?? 0;
              $cupo = (int)$ev["cupo_maximo"];
              $sinCupo = $cupo > 0 && $personasOcupadas >= $cupo;
              $disponibles = $cupo > 0 ? max(0, $cupo - $personasOcupadas) : null;
              $fechaFmt = date("d/m/Y", strtotime($ev["fecha"]));
              $dia = $diasSemana[date("w", strtotime($ev["fecha"]))];
              $hI = substr($ev["hora_inicio"], 0, 5);
              $hF = substr($ev["hora_fin"], 0, 5);
              $preSelected = $eventoSeleccionado == $ev["id"] && !$sinCupo;
            ?>
            <div class="evento-card <?php echo $sinCupo ? 'sin-cupo' : ''; ?> <?php echo $preSelected ? 'selected' : ''; ?>"
                 data-id="<?php echo (int)$ev["id"]; ?>"
                 data-sin-cupo="<?php echo $sinCupo ? '1' : '0'; ?>"
                 onclick="seleccionarEvento(this)">
              <div class="evento-card__check">✓</div>
              <div class="evento-card__fecha"><?php echo htmlspecialchars("$dia $fechaFmt"); ?></div>
              <div class="evento-card__hora">🕐 <?php echo htmlspecialchars("$hI – $hF"); ?></div>
              <?php if (!empty($ev["lugar"])): ?>
                <div class="evento-card__lugar">📍 <?php echo htmlspecialchars($ev["lugar"]); ?></div>
              <?php endif; ?>
              <?php if (!empty($ev["descripcion"])): ?>
                <div class="evento-card__desc"><?php echo htmlspecialchars($ev["descripcion"]); ?></div>
              <?php endif; ?>
              <?php if (!empty($ev["menu"])): ?>
                <div class="evento-card__desc">🍽️ Menú: <?php echo htmlspecialchars($ev["menu"]); ?></div>
              <?php endif; ?>
              <?php if ($sinCupo): ?>
                <span class="chip chip--lleno">Cupo lleno</span>
              <?php elseif ($disponibles !== null): ?>
                <span class="chip chip--disponible"><?php echo $disponibles; ?> lugar(es)</span>
              <?php else: ?>
                <span class="chip chip--abierto">Abierto</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <input type="hidden" name="evento_id" id="eventoIdInput" value="<?php echo $eventoSeleccionado ?: ''; ?>">
      </div>

      <!-- PASO 2: DATOS PERSONALES -->
      <div class="form-card" id="formularioRegistro" style="<?php echo $eventoSeleccionado ? '' : 'display:none;'; ?>">
        <p class="form-section-title">2. Datos de registro</p>

        <div class="form-grid">

          <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" required>
          </div>
          <div class="form-group">
            <label>Apellido paterno</label>
            <input type="text" name="apellido_paterno" required>
          </div>
          <div class="form-group">
            <label>Apellido materno</label>
            <input type="text" name="apellido_materno" required>
          </div>

          <div class="form-group">
            <label>Celular 1</label>
            <input type="text" name="celular_1" maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                   placeholder="10 dígitos" required>
          </div>
          <div class="form-group">
            <label>Celular 2 (opcional)</label>
            <input type="text" name="celular_2" maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                   placeholder="10 dígitos">
          </div>
          <div class="form-group">
            <label>Correo electrónico (opcional)</label>
            <input type="email" name="correo">
          </div>

          <div class="form-group half">
            <label>Calle</label>
            <input type="text" name="calle" required>
          </div>

          <div class="form-group">
            <label>No. exterior</label>
            <input type="text" name="no_exterior" required>
          </div>
          <div class="form-group">
            <label>No. interior (opcional)</label>
            <input type="text" name="no_interior">
          </div>

          <div class="form-group">
            <label>Código postal</label>
            <input type="text" id="codigo_postal" name="codigo_postal"
                   maxlength="5" pattern="[0-9]{5}" inputmode="numeric"
                   placeholder="Ej: 55740" required
                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,5);">
          </div>
          <div class="form-group">
            <label>Colonia</label>
            <select name="colonia" id="colonia" required>
              <option value="">Seleccione una colonia</option>
            </select>
          </div>
          <div class="form-group">
            <label>Municipio</label>
            <input type="text" name="municipio" id="municipio" readonly required>
          </div>

          <div class="form-group">
            <label>Número de personas que asistirán</label>
            <input type="number" name="numero_personas" min="1" max="20" value="1" required>
          </div>

          <div class="form-group half">
            <label>Grupo vulnerable al que perteneces</label>
            <select name="categoria_vulnerable" required>
              <option value="">Seleccione una opción</option>
              <?php foreach (categoriasVulnerables() as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group half">
            <label>Observaciones (alergias, necesidades especiales, etc.)</label>
            <textarea name="observaciones" maxlength="500" placeholder="Opcional..."></textarea>
          </div>

        </div>

        <div class="form-actions">
          <a href="index.php" class="btn btn-light">Volver</a>
          <button type="submit" class="btn">Registrarme</button>
        </div>
      </div>

    </form>

  <?php endif; ?>
</main>

<footer class="footer">
  <div class="footer-inner">
    <div style="display:flex;align-items:center;gap:10px;">
      <img src="assets/img/SM LOGO-07.png" alt="Logo" class="footer-logo">
      <span>© 2026 Samuel Te Escucha</span>
    </div>
    <div>Oficina virtual</div>
    <a href="login.php" onclick="alert('Solo el personal tiene acceso a este apartado')">Personal</a>
  </div>
</footer>

<script>
function seleccionarEvento(card) {
  if (card.dataset.sinCupo === "1") return;
  document.querySelectorAll(".evento-card").forEach(c => c.classList.remove("selected"));
  card.classList.add("selected");
  document.getElementById("eventoIdInput").value = card.dataset.id;
  const form = document.getElementById("formularioRegistro");
  form.style.display = "";
  form.scrollIntoView({ behavior: "smooth", block: "start" });
}

/* CP → Colonia / Municipio */
document.addEventListener("DOMContentLoaded", function () {
  const cpInput   = document.getElementById("codigo_postal");
  const colSelect = document.getElementById("colonia");
  const munInput  = document.getElementById("municipio");

  cpInput.addEventListener("input", function () {
    const cp = this.value.trim();
    colSelect.innerHTML = "<option value=''>Seleccione una colonia</option>";
    munInput.value = "";
    if (cp.length !== 5) return;
    fetch("buscar_cp.php?cp=" + encodeURIComponent(cp))
      .then(r => r.json())
      .then(data => {
        if (data.colonias && data.colonias.length > 0) {
          data.colonias.forEach(c => {
            const o = document.createElement("option");
            o.value = o.textContent = c;
            colSelect.appendChild(o);
          });
          munInput.value = data.municipio || "";
        } else {
          colSelect.innerHTML = "<option value=''>Código postal no encontrado</option>";
        }
      })
      .catch(() => {
        colSelect.innerHTML = "<option value=''>Error al buscar CP</option>";
      });
  });
});
</script>

</body>
</html>
