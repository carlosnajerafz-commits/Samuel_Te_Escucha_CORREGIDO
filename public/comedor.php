<?php
require_once "db.php";

// Verificar modo mantenimiento
if (file_exists(__DIR__ . "/maintenance.flag")) {
    include "maintenance.php";
    exit;
}

// Obtener próximos eventos activos
$eventos = $pdo->query("
    SELECT id, fecha, hora_inicio, hora_fin, lugar, descripcion, cupo_maximo
    FROM eventos_comedor
    WHERE activo = TRUE AND fecha >= CURRENT_DATE
    ORDER BY fecha ASC, hora_inicio ASC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Contar registros por evento para saber cupo disponible
$cuposOcupados = [];
if (!empty($eventos)) {
    $ids = implode(",", array_map(fn($e) => (int)$e["id"], $eventos));
    $rows = $pdo->query("
        SELECT evento_id, COUNT(*) AS total, SUM(numero_personas) AS personas
        FROM registros_comedor
        WHERE evento_id IN ($ids)
          AND estatus != 'cancelado'
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
        "celular"    => "El número de celular debe tener exactamente 10 dígitos.",
        "evento"     => "El evento seleccionado no existe o ya no está disponible.",
        "cupo"       => "Lo sentimos, el evento seleccionado ya alcanzó su cupo máximo.",
        "personas"   => "El número de personas debe ser entre 1 y 20.",
        default      => "No fue posible registrarte. Verifica los datos e inténtalo de nuevo.",
    };
    $modalTipo = "error";
}

// Pre-seleccionar evento si viene por GET
$eventoSeleccionado = isset($_GET["evento"]) ? (int)$_GET["evento"] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comedor Solidario | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .eventos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }
    .evento-card {
      background: #fff;
      border: 2px solid #e5e7eb;
      border-radius: 14px;
      padding: 20px;
      cursor: pointer;
      transition: border-color .2s, box-shadow .2s;
      position: relative;
    }
    .evento-card:hover { border-color: #7A1737; box-shadow: 0 4px 16px rgba(122,23,55,.12); }
    .evento-card.selected { border-color: #7A1737; background: #fdf2f5; }
    .evento-card.sin-cupo { opacity: .55; cursor: not-allowed; }
    .evento-card__fecha {
      font-size: 18px; font-weight: 800; color: #7A1737; margin-bottom: 6px;
    }
    .evento-card__hora {
      font-size: 14px; color: #374151; margin-bottom: 4px; font-weight: 600;
    }
    .evento-card__lugar {
      font-size: 13px; color: #6b7280; margin-bottom: 6px;
    }
    .evento-card__desc {
      font-size: 13px; color: #6b7280; margin-bottom: 10px;
    }
    .evento-card__cupo {
      display: inline-block;
      font-size: 12px; font-weight: 700;
      padding: 3px 10px; border-radius: 20px;
    }
    .evento-card__cupo.disponible { background: #dcfce7; color: #166534; }
    .evento-card__cupo.lleno      { background: #fee2e2; color: #991b1b; }
    .evento-card__cupo.sin-limite { background: #dbeafe; color: #1e40af; }
    .evento-card__check {
      position: absolute; top: 14px; right: 14px;
      width: 22px; height: 22px; border-radius: 50%;
      background: #7A1737; color: #fff;
      display: none; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700;
    }
    .evento-card.selected .evento-card__check { display: flex; }
    .sin-eventos {
      background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
      padding: 32px 24px; text-align: center; color: #6b7280;
      margin-bottom: 32px;
    }
    .sin-eventos h3 { color: #374151; margin-bottom: 8px; }
  </style>
</head>
<body>

<?php if ($mostrarModal): ?>
  <div class="status-modal-overlay"></div>
  <div class="status-modal <?php echo htmlspecialchars($modalTipo); ?>">
    <h3><?php echo htmlspecialchars($modalTitulo); ?></h3>
    <p><?php echo nl2br(htmlspecialchars($modalMensaje)); ?></p>
    <button class="btn" onclick="window.location.href='comedor.php'">Aceptar</button>
  </div>
<?php endif; ?>

<header class="topbar">
  <div class="wrap topbar__inner">
    <div class="brand">
      <div class="brand__logo">
        <img src="assets/img/legislatura3.png" alt="Samuel Te Escucha">
      </div>
      <div>
        <h2>Samuel Te Escucha</h2>
      </div>
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
      <a href="index.php" class="btn btn--light" style="margin-top:16px;display:inline-block;">Volver al inicio</a>
    </div>
  <?php else: ?>

    <section class="form-card">
      <form method="POST" action="guardar_comedor.php" id="comedorForm">

        <h2 style="margin-bottom:16px;font-size:18px;color:#7A1737;">1. Selecciona la fecha del evento</h2>

        <div class="eventos-grid" id="eventosGrid">
          <?php foreach ($eventos as $ev): ?>
            <?php
              $personasOcupadas = $cuposOcupados[$ev["id"]] ?? 0;
              $cupo = (int)$ev["cupo_maximo"];
              $sinCupo = $cupo > 0 && $personasOcupadas >= $cupo;
              $disponibles = $cupo > 0 ? max(0, $cupo - $personasOcupadas) : null;
              $fechaFormato = date("d/m/Y", strtotime($ev["fecha"]));
              $diasSemana = ["Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"];
              $diaSemana = $diasSemana[date("w", strtotime($ev["fecha"]))];
              $horaI = substr($ev["hora_inicio"], 0, 5);
              $horaF = substr($ev["hora_fin"], 0, 5);
              $preSelected = $eventoSeleccionado == $ev["id"] && !$sinCupo;
            ?>
            <div class="evento-card <?php echo $sinCupo ? 'sin-cupo' : ''; ?> <?php echo $preSelected ? 'selected' : ''; ?>"
                 data-id="<?php echo (int)$ev["id"]; ?>"
                 data-sinCupo="<?php echo $sinCupo ? '1' : '0'; ?>"
                 onclick="seleccionarEvento(this)">
              <div class="evento-card__check">✓</div>
              <div class="evento-card__fecha"><?php echo htmlspecialchars("$diaSemana $fechaFormato"); ?></div>
              <div class="evento-card__hora">🕐 <?php echo htmlspecialchars("$horaI – $horaF"); ?></div>
              <?php if (!empty($ev["lugar"])): ?>
                <div class="evento-card__lugar">📍 <?php echo htmlspecialchars($ev["lugar"]); ?></div>
              <?php endif; ?>
              <?php if (!empty($ev["descripcion"])): ?>
                <div class="evento-card__desc"><?php echo htmlspecialchars($ev["descripcion"]); ?></div>
              <?php endif; ?>
              <?php if ($sinCupo): ?>
                <span class="evento-card__cupo lleno">Cupo lleno</span>
              <?php elseif ($disponibles !== null): ?>
                <span class="evento-card__cupo disponible"><?php echo $disponibles; ?> lugar(es) disponible(s)</span>
              <?php else: ?>
                <span class="evento-card__cupo sin-limite">Abierto</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <input type="hidden" name="evento_id" id="eventoIdInput" value="<?php echo $eventoSeleccionado ?: ''; ?>" required>

        <div id="formularioRegistro" style="<?php echo $eventoSeleccionado ? '' : 'display:none;'; ?>">
          <h2 style="margin-bottom:16px;font-size:18px;color:#7A1737;">2. Datos de registro</h2>
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
              <label>Celular</label>
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

            <div class="form-group">
              <label>Número de personas que asistirán</label>
              <input type="number" name="numero_personas" min="1" max="20" value="1" required>
            </div>

            <div class="form-group form-group--full">
              <label>Observaciones (alergias, necesidades especiales, etc.)</label>
              <textarea name="observaciones" maxlength="500" placeholder="Opcional..."></textarea>
            </div>

          </div>

          <div class="form-actions">
            <a href="index.php" class="btn btn--light">Volver</a>
            <button type="submit" class="btn">Registrarme</button>
          </div>
        </div>

      </form>
    </section>

  <?php endif; ?>
</main>

<footer class="footer">
  <div class="wrap footer__inner">
    <div class="footer-left">
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
  document.getElementById("formularioRegistro").style.display = "block";
  document.getElementById("formularioRegistro").scrollIntoView({ behavior: "smooth", block: "start" });
}
</script>

</body>
</html>
