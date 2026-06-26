<?php
require_once __DIR__ . "/includes/security_headers.php";
require_once __DIR__ . "/includes/session.php";
require_once "db.php";

$stmt   = $pdo->query("SELECT id, nombre, descripcion FROM apoyos WHERE activo = TRUE ORDER BY created_at DESC");
$apoyos = $stmt->fetchAll();

$mostrarModal = false;
$modalTitulo  = "";
$modalMensaje = "";
$modalTipo    = "";

if (isset($_GET["ok"])) {
    $mostrarModal = true;
    $modalTitulo  = "Solicitud enviada";
    $id           = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
    $folio        = str_pad($id, 6, "0", STR_PAD_LEFT);
    $modalMensaje = "¡Registro exitoso!\n\nTu número de folio es: #" . $folio . "\n\nTu solicitud ha sido recibida. Nos pondremos en contacto contigo.";
    $modalTipo    = "success";
}

if (isset($_GET["error"])) {
    $mostrarModal = true;
    $modalTitulo  = "Error";
    $modalMensaje = match($_GET["error"]) {
        "limite"  => "Ya enviaste 3 solicitudes de apoyo en la última hora. Por favor espera un momento antes de intentar de nuevo.",
        "celular" => "Los dos números celulares deben tener exactamente 10 dígitos.",
        default   => "No fue posible registrar tu solicitud. Verifica los datos e inténtalo de nuevo.",
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
  <title>Apoyos | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
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
        <img src="assets/img/legislatura3.png" alt="Samuel Te Escucha">
      </div>
      <div>
        <h2>Samuel Te Escucha</h2>
      </div>
    </div>
    <nav class="nav">
      <a href="index.php">Inicio</a>
      <a href="queja.php">Gestión</a>
      <a href="cita.php">Citas</a>
      <a href="comedor.php">Comedor</a>
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
            placeholder="Ej: 55803"
            required
            oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,5);"
          >
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

      </div>

      <div class="form-actions">
        <a href="index.php" class="btn btn--light">Volver</a>
        <button type="submit" class="btn">Enviar solicitud</button>
      </div>
    </form>
  </section>

  <!-- Modal apoyos disponibles -->
  <div id="modalApoyos" class="status-modal-overlay" style="display:none;">
    <div class="status-modal modal-apoyos">
      <h3>Apoyos disponibles</h3>
      <p>Aquí puedes consultar los apoyos activos y su descripción.</p>
      <div class="apoyos-help-list">
        <?php if (empty($apoyos)): ?>
          <div class="apoyo-help-item"><strong>No hay apoyos activos</strong></div>
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
document.addEventListener("DOMContentLoaded", function () {
  const cpInput   = document.getElementById("codigo_postal");
  const colSelect = document.getElementById("colonia");
  const munInput  = document.getElementById("municipio");

  if (!cpInput || !colSelect || !munInput) return;

  cpInput.addEventListener("input", function () {
    const cp = this.value.trim();
    colSelect.innerHTML = "<option value=''>Seleccione una colonia</option>";
    munInput.value = "";

    if (cp.length !== 5) return;

    fetch("buscar_cp.php?cp=" + encodeURIComponent(cp))
      .then(res => res.json())
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
