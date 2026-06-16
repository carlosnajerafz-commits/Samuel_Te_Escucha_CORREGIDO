<?php
$mostrarModal = false;
$modalTitulo  = "";
$modalMensaje = "";
$modalTipo    = "";

if (isset($_GET["ok"])) {
    $mostrarModal = true;
    $modalTitulo  = "Tu solicitud de cita fue registrada con éxito";
    $id           = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
    $folio        = str_pad($id, 6, "0", STR_PAD_LEFT);
    $modalMensaje = "¡Registro exitoso!\n\nTu número de folio es: #" . $folio . "\n\nTu solicitud ha sido recibida. Nos pondremos en contacto contigo.";
    $modalTipo    = "success";
}

if (isset($_GET["error"])) {
    $mostrarModal = true;
    $modalTitulo  = "Error";
    $error        = $_GET["error"];
    $modalMensaje = match($error) {
        "martes"         => "Solo se pueden solicitar citas para los martes.",
        "ocupada"        => "Ya existe una cita en esa fecha y horario.",
        "bloqueado"      => "La fecha u hora seleccionada no está disponible. Por favor elige otro horario.",
        "celular"        => "Los celulares deben tener 10 dígitos.",
        "archivo_grande" => "La imagen supera los 5 MB permitidos.",
        default          => "Error al generar la cita.",
    };
    $modalTipo = "error";
}

// Verificar modo mantenimiento
if (file_exists(__DIR__ . "/maintenance.flag")) {
    include "maintenance.php";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar Cita | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/cita.css?v=3">
  <style>
    @media (max-width: 768px) {
      #calendarGrid {
        display: grid !important;
        grid-template-columns: repeat(7, 1fr) !important;
        gap: 6px !important;
      }
      #calendarGrid .calendar-day-ui,
      #calendarGrid button,
      #calendarGrid div {
        width: auto !important; min-width: 0 !important; max-width: none !important;
        height: 54px !important; min-height: 54px !important; padding: 6px !important;
      }
    }
  </style>
</head>
<body>

<?php if ($mostrarModal): ?>
  <div class="status-modal-overlay"></div>
  <div class="status-modal <?php echo htmlspecialchars($modalTipo); ?>">
    <h3><?php echo htmlspecialchars($modalTitulo); ?></h3>
    <p><?php echo htmlspecialchars($modalMensaje); ?></p>
    <button class="btn" onclick="window.location.href='cita.php'">Aceptar</button>
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
      <a href="queja.php">Gestión</a>
      <a href="apoyos.php">Apoyos</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell">
  <div class="page-header">
    <h1>Martes ciudadano</h1>
    <p>Selecciona un martes disponible y envía tu solicitud para revisión.</p>
  </div>

  <section class="form-card">
    <form method="POST" action="guardar_cita.php" id="citaForm" enctype="multipart/form-data">
      <div class="appointment-layout">

        <!-- Calendario -->
        <div class="appointment-calendar-card">
          <div class="calendar-toolbar">
            <button type="button" class="calendar-nav-btn" id="prevMonthBtn">‹</button>
            <h3 id="calendarTitle">Mes Año</h3>
            <button type="button" class="calendar-nav-btn" id="nextMonthBtn">›</button>
          </div>
          <div class="calendar-weekdays">
            <div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div><div>Dom</div>
          </div>
          <div class="calendar-grid-ui" id="calendarGrid"></div>
          <div class="calendar-legend">
            <span><span class="legend-dot legend-dot--enabled"></span> Martes disponible</span>
            <span><span class="legend-dot legend-dot--disabled"></span> No disponible</span>
            <span><span class="legend-dot legend-dot--selected"></span> Seleccionado</span>
          </div>
          <div class="calendar-selected-info" id="calendarSelectedInfo">
            Aún no has seleccionado una fecha.
          </div>
        </div>

        <!-- Formulario -->
        <div class="appointment-form-card">
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
              <input type="text" name="celular_1" maxlength="10" pattern="[0-9]{10}" required>
            </div>

            <div class="form-group">
              <label>Celular 2</label>
              <input type="text" name="celular_2" maxlength="10" pattern="[0-9]{10}" required>
            </div>

            <div class="form-group">
              <label>Correo electrónico</label>
              <input type="email" name="correo" required>
            </div>

            <div class="form-group">
              <label>Calle</label>
              <input type="text" name="calle" required>
            </div>

            <div class="form-group">
              <label>No. exterior</label>
              <input type="text" name="no_exterior" required>
            </div>

            <div class="form-group">
              <label>No. interior</label>
              <input type="text" name="no_interior">
            </div>

            <div class="form-group">
              <label>Código postal</label>
              <input
                type="text"
                id="codigo_postal"
                name="codigo_postal"
                maxlength="5"
                pattern="[0-9]{5}"
                inputmode="numeric"
                placeholder="Ej: 55740"
                required
                oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,5);"
              >
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
              <label>Fecha seleccionada</label>
              <input type="text" id="fechaVisible" placeholder="Selecciona un martes en el calendario" readonly>
              <input type="hidden" name="fecha" id="fechaInput" required>
            </div>

            <div class="form-group form-group--full">
              <label>Horario disponible</label>
              <div class="slots-grid" id="slotsGrid">
                <button type="button" class="slot-btn" data-slot="09:00">09:00 - 09:20</button>
                <button type="button" class="slot-btn" data-slot="09:20">09:20 - 09:40</button>
                <button type="button" class="slot-btn" data-slot="09:40">09:40 - 10:00</button>
                <button type="button" class="slot-btn" data-slot="10:00">10:00 - 10:20</button>
                <button type="button" class="slot-btn" data-slot="10:20">10:20 - 10:40</button>
                <button type="button" class="slot-btn" data-slot="10:40">10:40 - 11:00</button>
                <button type="button" class="slot-btn" data-slot="11:00">11:00 - 11:20</button>
                <button type="button" class="slot-btn" data-slot="11:20">11:20 - 11:40</button>
                <button type="button" class="slot-btn" data-slot="11:40">11:40 - 12:00</button>
                <button type="button" class="slot-btn" data-slot="12:00">12:00 - 12:20</button>
                <button type="button" class="slot-btn" data-slot="12:20">12:20 - 12:40</button>
                <button type="button" class="slot-btn" data-slot="12:40">12:40 - 13:00</button>
                <button type="button" class="slot-btn" data-slot="13:00">13:00 - 13:20</button>
                <button type="button" class="slot-btn" data-slot="13:20">13:20 - 13:40</button>
                <button type="button" class="slot-btn" data-slot="13:40">13:40 - 14:00</button>
                <button type="button" class="slot-btn" data-slot="14:00">14:00 - 14:20</button>
                <button type="button" class="slot-btn" data-slot="14:20">14:20 - 14:40</button>
                <button type="button" class="slot-btn" data-slot="14:40">14:40 - 15:00</button>
              </div>
              <input type="hidden" name="hora" id="horaInput" required>
            </div>

            <div class="form-group form-group--full">
              <label>Motivo de la cita</label>
              <textarea name="motivo" maxlength="500" placeholder="Explique brevemente el asunto de la cita..."></textarea>
            </div>

          </div>

          <div class="form-actions">
            <a href="index.php" class="btn btn--light">Volver</a>
            <button type="submit" class="btn">Enviar solicitud</button>
          </div>
        </div>



      </div>
    </form>
  </section>
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
/* ---- Código postal → colonias ---- */
document.addEventListener("DOMContentLoaded", function () {
  const cpInput   = document.getElementById("codigo_postal");
  const colSelect = document.getElementById("colonia");
  const munInput  = document.getElementById("municipio");

  cpInput.addEventListener("input", function () {
    const cp = this.value.trim();
    colSelect.innerHTML = '<option value="">Seleccione una colonia</option>';
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
          colSelect.innerHTML = '<option value="">Código postal no encontrado</option>';
        }
      })
      .catch(() => {
        colSelect.innerHTML = '<option value="">Error al buscar CP</option>';
      });
  });
});

/* ---- Horas ocupadas ---- */
async function obtenerHorasOcupadas(fecha) {
  try {
    const res = await fetch(`horas_ocupadas.php?fecha=${fecha}`);
    return await res.json();
  } catch (e) { return []; }
}

async function actualizarHoras(fecha) {
  const horasOcupadas = await obtenerHorasOcupadas(fecha);
  document.querySelectorAll(".slot-btn").forEach(btn => {
    const ocupada     = horasOcupadas.includes(btn.dataset.slot);
    btn.disabled      = ocupada;
    btn.style.opacity = ocupada ? "0.5" : "1";
    btn.style.cursor  = ocupada ? "not-allowed" : "pointer";
    btn.title         = ocupada ? "Hora ocupada" : "";
  });
}

document.getElementById("fechaInput").addEventListener("change", function () {
  actualizarHoras(this.value);
});
</script>

<script src="assets/js/cita-calendar.js"></script>

</body>
</html>
