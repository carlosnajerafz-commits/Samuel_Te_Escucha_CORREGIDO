<?php
$mostrarModal = false;
$modalTitulo = "";
$modalMensaje = "";
$modalTipo = "";

if (isset($_GET["ok"])) {
    $mostrarModal = true;
    $modalTitulo = "Solicitud enviada";
    $modalMensaje = "Tu solicitud de cita fue registrada con éxito y, en breve, nos pondremos en contacto vía telefónica para confirmarla.";
    $modalTipo = "success";

} elseif (isset($_GET["error"])) {

    $mostrarModal = true;
    $modalTitulo = "Error";

    $error = $_GET["error"];

    if ($error === "martes") {
        $modalMensaje = "Solo se pueden solicitar citas para los martes.";
    } elseif ($error === "ocupada") {
        $modalMensaje = "Ya existe una cita en esa fecha.";
    } elseif ($error === "celular") {
        $modalMensaje = "Los celulares deben tener 10 dígitos.";
    } elseif ($error === "ine") {
        $modalMensaje = "Debes subir una imagen válida de la INE.";
    } else {
        $modalMensaje = "Error al generar la cita.";
    }

    $modalTipo = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar Cita | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
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
        <img src="assets/img/logoguinda.jpeg" alt="Samuel Te Escucha">
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
              <label class="help-inline">
                Sección electoral
                <button type="button" class="help-btn" onclick="document.getElementById('modalSeccion').style.display='flex'" img="">?</button>
              </label>
             <input
  type="text"
  name="seccion_electoral"
  maxlength="4"
  minlength="4"
  pattern="[0-9]{4}"
  inputmode="numeric"
  required
  oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);"
>
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
              <input type="text" id="codigo_postal" name="codigo_postal" list="lista_cp" maxlength="5" pattern="[0-9]{5}" required>
              <datalist id="lista_cp">
                <option value="55803">Acatitla, Colatitla, El Tejocote</option>
                <option value="55807">El Mirador</option>
                <option value="55816">El Portal</option>
                <option value="55825">Ampliación Ejidal Tlajinga</option>
                <option value="55826">Ampliación San Francisco</option>
                <option value="55830">De los Deportes, Del Valle</option>
                <option value="55833">El Potrero</option>
                <option value="55838">Atlatongo, Ejido Purificación</option>
                <option value="55840">Ampliación Ejidal Maquixco</option>
                <option value="55843">Ampliación Cadena Maquixco, El Cayahual</option>
                <option value="55844">Hacienda Cadena</option>

                <option value="55850">San Martín de las Pirámides Centro, Ejido San Martín</option>
                <option value="55852">Ixtlahuaca, Santa María Tezompa, Álvaro Obregón, El Saltito</option>
                <option value="55853">Cozotlán Norte, San Antonio de las Palmas, Tlachinolpa</option>
                <option value="55854">La Noria, San José Cerro Gordo, San Marcos Cerro Gordo</option>
                <option value="55855">Chimalpa, Club Campestre Teotihuacán, Predio Palma y Raya, San Pablo Ixquitlán</option>
                <option value="55856">Santa María Palapa</option>
                <option value="55859">Santiago Tepetitlán</option>

                <option value="55940">Axapusco, Cuauhtémoc, San Antonio, San Bartolo Alto, San Martín</option>
                <option value="55950">Guadalupe Relinas, San Felipe Zacatepec, San Antonio Coayuca</option>
                <option value="55954">San Pablo Xuchitl</option>
                <option value="55955">San Nicolás Tetepantla</option>
                <option value="55960">San Antonio Ometusco</option>
                <option value="55963">San Miguel Ometusco, Santa Ana</option>
                <option value="55965">Jaltepec</option>
                <option value="55966">Atla (Tecuautitlán Atla)</option>

                <option value="55970">Barrios Hidalgo A/B, Morelos A/B, Vicente Guerrero</option>
                <option value="55973">Exhacienda La Puerta</option>
                <option value="55975">San Felipe Teotitlán, Huilotongo, Tlaxixilo, Colonia Roma</option>
                <option value="55976">San Miguel Atepoxco, Tepetzingo</option>
                <option value="55978">Santa Inés Amiltepec, Las Ambrises</option>

                <option value="55980">San Bartolomé Actopan, San Juan Teacalco, Barrios San Miguel, San Antonio, De la Cruz, De Dolores</option>
                <option value="55983">El Abrojal, El Chopo</option>
                <option value="55984">Colonia Belén, Ocotitlán</option>
                <option value="55985">Atempan</option>
                <option value="55988">Las Pintas</option>
                <option value="55989">Presa del Rey</option>
                <option value="55990">Ixtlahuaca de Cuauhtémoc</option>
                <option value="55993">Ex Hacienda de Paula, La Estrella</option>
                <option value="55994">Axalpa</option>
                <option value="55995">Mihuacán</option>
                <option value="55996">Álvaro Obregón, La Presa</option>
                <option value="55998">El Tejocote</option>

                <option value="55740">Tecámac de Felipe Villanueva Centro, El Calvario, Galaxias el Llano</option>
                <option value="55743">Hacienda del Bosque, Real Granada, La Palma, Isidro Fabela</option>
                <option value="55748">Ejido de Tecámac, 1ro de Marzo</option>
                <option value="55749">5 de Mayo, Ampliación 5 de Mayo</option>
                <option value="55750">San Juan Pueblo Nuevo</option>
                <option value="55755">Los Reyes Acozac</option>
                <option value="55760">San Martín Azcatepec, San Pablo Tecalco</option>
                <option value="55763">Los Héroes Tecámac</option>
                <option value="55764">Los Héroes Tecámac Sección Jardines</option>
                <option value="55765">Los Héroes Tecámac Sección Bosques</option>
                <option value="55770">Hacienda Ojo de Agua</option>
                <!-- TECÁMAC MÁS COMPLETO -->
<option value="55767">Los Héroes Tecámac Sección Flores</option>
<option value="55768">Los Héroes Tecámac Sección Bosques II</option>
<option value="55769">Los Héroes Tecámac Sección Bosques III</option>

<option value="55745">Santa María Ajoloapan</option>
<option value="55746">San Pedro Pozohuacán</option>
<option value="55747">San Jerónimo Xonacahuacan</option>

<option value="55730">Ozumbilla</option>
<option value="55733">Santa Cruz Tecámac</option>

<!-- OTUMBA / ZONA -->
<option value="55900">Otumba de Gómez Farías Centro</option>
<option value="55903">San Lorenzo Tlalmimilolpan</option>

<!-- ZONA TEOTIHUACÁN MÁS COMPLETA -->
<option value="55800">Teotihuacán Centro</option>
<option value="55810">San Juan Teotihuacán</option>
              </datalist>
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
              <label>Motivo de la cita (Explique brevemente el asunto de la cita)</label>
              <textarea name="motivo" maxlength="500"></textarea>
            </div>
          </div>

          <div class="form-group form-group--full">
  <label>Foto de INE</label>
  <input type="file" name="ine_foto" accept="image/*" required>
  <div class="upload-note">Sube una fotografía legible de la identificación.</div>
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

<div id="modalSeccion" class="status-modal-overlay" style="display:none;">
  <div class="status-modal">
    <h3>¿Dónde encuentro mi sección electoral?</h3>
    <p>Busca este dato en tu credencial para votar. Aquí se muestra una imagen de ejemplo.</p>
    <img src="assets/img/INE.jpg.jpeg" alt="Ejemplo de sección electoral" class="help-preview">
    <button type="button" class="btn" onclick="document.getElementById('modalSeccion').style.display='none'">Cerrar</button>
  </div>
</div>

<footer class="footer">
  <div class="wrap footer__inner">

    <div class="footer-left">
      <img src="assets/img/SM LOGO-07.png" alt="Logo" class="footer-logo">
      <span>© 2026 Samuel Te Escucha</span>
    </div>

    <div>Oficina virtual</div>

    <a href="login.php" onclick="alert('Solo el personal tiene acceso a este apartado')">
      Personal
    </a>

  </div>
</footer>

<script src="assets/js/cita-calendar.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const codigoPostal = document.getElementById("codigo_postal");
  const coloniaSelect = document.getElementById("colonia");
  const municipioInput = document.getElementById("municipio");

  codigoPostal.addEventListener("change", function () {
    const cp = this.value.trim();

    coloniaSelect.innerHTML = "<option value=''>Seleccione una colonia</option>";
    municipioInput.value = "";

    if (cp.length !== 5) {
      return;
    }

    fetch("buscar_cp.php?cp=" + encodeURIComponent(cp))
      .then(response => response.json())
      .then(data => {
        if (data.colonias && data.colonias.length > 0) {
          data.colonias.forEach(colonia => {
            const option = document.createElement("option");
            option.value = colonia;
            option.textContent = colonia;
            coloniaSelect.appendChild(option);
          });

          municipioInput.value = data.municipio || "";
        }
      })
      .catch(error => {
        console.error("Error al buscar código postal:", error);
      });
  });
});
</script>
</body>
</html>