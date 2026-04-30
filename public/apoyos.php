<?php
require_once "db.php";

$stmt = $pdo->query("SELECT id, nombre, descripcion FROM apoyos WHERE activo = TRUE ORDER BY created_at DESC");
$apoyos = $stmt->fetchAll();

$mostrarModal = false;
$modalTitulo = "";
$modalMensaje = "";
$modalTipo = "";

if (isset($_GET["ok"])) {
    $mostrarModal = true;
    $modalTitulo = "Solicitud enviada";

    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

    // opcional: folio bonito
    $folio = str_pad($id, 6, "0", STR_PAD_LEFT);

    $modalMensaje = "¡Registro exitoso!

Tu número de folio es: #" . $folio . "

Tu solicitud ha sido recibida. Nos pondremos en contacto contigo.";

    $modalTipo = "success";
}if (isset($_GET["ok"])) {
    $mostrarModal = true;
    $modalTitulo = "Solicitud enviada";

    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

    // opcional: folio bonito
    $folio = str_pad($id, 6, "0", STR_PAD_LEFT);

    $modalMensaje = "¡Registro exitoso!

Tu número de folio es: #" . $folio . "

Tu solicitud ha sido recibida. Nos pondremos en contacto contigo.";

    $modalTipo = "success";
}

if (isset($_GET["error"])) {
    $mostrarModal = true;
    $modalTitulo = "Error";
    $modalMensaje = $_GET["error"] === "celular"
        ? "Los dos números celulares deben tener exactamente 10 dígitos."
        : "No fue posible registrar tu solicitud. Verifica los datos e inténtalo de nuevo.";
    $modalTipo = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apoyos | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const cpInput = document.getElementById("codigo_postal");
  const colSelect = document.getElementById("colonia");
  const munInput = document.getElementById("municipio");

  if (!cpInput || !colSelect || !munInput) {
    console.error("No se encontraron los campos de CP, colonia o municipio.");
    return;
  }

  cpInput.addEventListener("input", function () {
    const cp = this.value.trim();

    colSelect.innerHTML = "<option value=''>Seleccione una colonia</option>";
    munInput.value = "";

    if (cp.length !== 5) return;

    fetch("buscar_cp.php?cp=" + encodeURIComponent(cp))
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (data.colonias && data.colonias.length > 0) {
          data.colonias.forEach(function (colonia) {
            const option = document.createElement("option");
            option.value = colonia;
            option.textContent = colonia;
            colSelect.appendChild(option);
          });

          munInput.value = data.municipio || "";
        } else {
          colSelect.innerHTML = "<option value=''>Código postal no encontrado</option>";
        }
      })
      .catch(function (error) {
        console.error("Error CP:", error);
      });
  });
});
</script>
<body>

<?php if ($mostrarModal): ?>
  <div class="status-modal-overlay"></div>
  <div class="status-modal <?php echo htmlspecialchars($modalTipo); ?>">
    <h3><?php echo htmlspecialchars($modalTitulo); ?></h3>
    <p><?php echo htmlspecialchars($modalMensaje); ?></p>
    <button class="btn" onclick="window.location.href='apoyos.php'">Aceptar</button>
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
      <a href="cita.php">Citas</a>
     
    </nav>
  </div>
</header>

<main class="wrap page-shell">

  <section class="form-card">
    <form method="POST" action="guardar_apoyo.php" enctype="multipart/form-data">
      <div class="form-grid">

        <!-- APOYO DISPONIBLE -->
        <div class="form-group form-group--full">
          <label class="help-inline">
            Apoyo disponible
            <button type="button" class="help-btn" onclick="document.getElementById('modalApoyos').style.display='flex'">?</button>
          </label>

          <div class="radio-grid">
            <?php foreach ($apoyos as $apoyo): ?>
              <label class="radio-item">
                <input type="radio" name="apoyo" value="<?php echo htmlspecialchars($apoyo["nombre"]); ?>" required>
                <?php echo htmlspecialchars($apoyo["nombre"]); ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- AQUÍ SIGUE TU FORMULARIO NORMAL -->

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
            <button type="button" class="help-btn" onclick="document.getElementById('modalSeccion').style.display='flex'">?</button>
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
  <input 
    type="text" 
    id="codigo_postal" 
    name="codigo_postal" 
    list="lista_cp"
    maxlength="5" 
    pattern="[0-9]{5}" 
    inputmode="numeric"
    placeholder="Ej: 55803"
    required
    oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,5);"
  >

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
    <option value="55850">San Martín de las Pirámides Centro</option>
    <option value="55852">Ixtlahuaca, Santa María Tezompa</option>
    <option value="55853">Cozotlán Norte</option>
    <option value="55854">La Noria, San José Cerro Gordo</option>
    <option value="55855">Chimalpa</option>
    <option value="55856">Santa María Palapa</option>
    <option value="55859">Santiago Tepetitlán</option>
    <option value="55940">Axapusco</option>
    <option value="55950">San Felipe Zacatepec</option>
    <option value="55954">San Pablo Xuchitl</option>
    <option value="55955">San Nicolás Tetepantla</option>
    <option value="55960">San Antonio Ometusco</option>
    <option value="55963">San Miguel Ometusco</option>
    <option value="55965">Jaltepec</option>
    <option value="55966">Atla</option>
    <option value="55970">Nopaltepec</option>
    <option value="55973">Exhacienda La Puerta</option>
    <option value="55975">San Felipe Teotitlán</option>
    <option value="55976">San Miguel Atepoxco</option>
    <option value="55978">Santa Inés Amiltepec</option>
    <option value="55980">San Bartolomé Actopan</option>
    <option value="55983">El Abrojal</option>
    <option value="55984">Colonia Belén</option>
    <option value="55985">Atempan</option>
    <option value="55988">Las Pintas</option>
    <option value="55989">Presa del Rey</option>
    <option value="55990">Ixtlahuaca de Cuauhtémoc</option>
    <option value="55993">Ex Hacienda de Paula</option>
    <option value="55994">Axalpa</option>
    <option value="55995">Mihuacán</option>
    <option value="55996">Álvaro Obregón</option>
    <option value="55998">El Tejocote</option>
    <option value="55740">Tecámac Centro</option>
    <option value="55743">Hacienda del Bosque</option>
    <option value="55748">Ejido de Tecámac</option>
    <option value="55749">5 de Mayo</option>
    <option value="55750">San Juan Pueblo Nuevo</option>
    <option value="55755">Los Reyes Acozac</option>
    <option value="55760">San Martín Azcatepec</option>
    <option value="55763">Los Héroes Tecámac</option>
    <option value="55764">Los Héroes Tecámac Jardines</option>
    <option value="55765">Los Héroes Tecámac Bosques</option>
    <option value="55770">Hacienda Ojo de Agua</option>
  </datalist>
</div>

<div class="form-group">
  <label>Colonia</label>
  <select id="colonia" name="colonia" required>
    <option value="">Seleccione una colonia</option>
  </select>
</div>

<div class="form-group">
  <label>Municipio</label>
  <input type="text" id="municipio" name="municipio" readonly required>
</div>

        



        <div class="form-group form-group--full">
          <label>Observaciones</label>
          <textarea name="observaciones" maxlength="500"></textarea>
        </div>

        <div class="form-group form-group--full">
          <label>Foto de INE</label>
          <input type="file" name="ine_foto" accept="image/*" required>
          <div class="upload-note">Sube una fotografía legible de la identificación.</div>
        </div>
      </div>

      <div class="form-actions">
        <a href="index.php" class="btn btn--light">Volver</a>
        <button type="submit" class="btn">Enviar solicitud</button>
      </div>
    </form>
  </section>
  <div id="modalApoyos" class="status-modal-overlay" style="display:none;">
  <div class="status-modal modal-apoyos">
    <h3>Apoyos disponibles</h3>
    <p>Aquí puedes consultar los apoyos activos y su descripción.</p>

    <div class="apoyos-help-list">
      <?php if (empty($apoyos)): ?>
        <div class="apoyo-help-item">
          <strong>No hay apoyos activos</strong>
        </div>
      <?php else: ?>
        <?php foreach ($apoyos as $apoyo): ?>
          <div class="apoyo-help-item">
            <strong><?php echo htmlspecialchars($apoyo["nombre"]); ?></strong>

            <?php if (!empty($apoyo["descripcion"])): ?>
              <p><?php echo htmlspecialchars($apoyo["descripcion"]); ?></p>
            <?php else: ?>
              <p>Sin descripción disponible.</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <button type="button" class="btn" onclick="document.getElementById('modalApoyos').style.display='none'">Cerrar</button>
  </div>
</div>
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

<script>
document.addEventListener("DOMContentLoaded", function () {
  const codigoPostal = document.getElementById("codigo_postal");
  const coloniaSelect = document.getElementById("colonia");
  const municipioInput = document.getElementById("municipio");

  if (!codigoPostal || !coloniaSelect || !municipioInput) return;

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