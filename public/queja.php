<?php
$mostrarModal = false;
$modalTitulo = "";
$modalMensaje = "";
$modalTipo = "";

if (isset($_GET["ok"])) {
    $mostrarModal = true;
    $modalTitulo = "Queja registrada";
    $modalMensaje = "Tu queja fue registrada correctamente.";
    $modalTipo = "success";
}

if (isset($_GET["error"])) {
    $mostrarModal = true;
    $modalTitulo = "Error";
    $modalMensaje = "No fue posible registrar la queja. Verifica los datos e inténtalo de nuevo.";
    $modalTipo = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enviar Queja | Samuel Te Escucha</title>
  <style>
    /* ==========================================================
       ESTILOS PROTEGIDOS (PREFIJO q-) 
       ========================================================== */
    :root {
      --q-guinda: rgb(122, 23, 55);
      --q-guinda-2: rgb(341, 68%, 28%);
      --q-bg: #f3f4f6;
      --q-text: #1f2937;
      --q-border: #e5e7eb;
      --q-shadow: 0 14px 38px rgba(17,24,39,.08);
    }

    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: var(--q-bg); color: var(--q-text); }
    .q-wrap { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

    /* HEADER */
    .q-topbar { background: #fff; border-bottom: 1px solid var(--q-border); position: sticky; top: 0; z-index: 100; padding: 12px 0; }
    .q-topbar-inner { display: flex; align-items: center; justify-content: space-between; }
    .q-brand { display: flex; align-items: center; gap: 14px; text-decoration: none; color: inherit; }
    .q-brand-logo { width: 55px; height: 55px; border-radius: 12px; overflow: hidden; border: 1px solid var(--q-border); }
    .q-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
    .q-brand h2 { margin: 0; color: var(--q-guinda); font-size: 22px; font-weight: 800; }
    .q-brand-sub { display: block; font-size: 12px; color: #6b7280; font-weight: 400; }
    .q-nav a { padding: 10px 15px; border-radius: 10px; font-weight: 700; color: #4b5563; text-decoration: none; transition: 0.2s; }
    .q-nav a:hover { background: #f3f4f6; color: var(--q-guinda); }

    /* LAYOUT PRINCIPAL */
    .q-page-shell { padding: 40px 0 80px; }
    .q-page-header { margin-bottom: 30px; }
    .q-page-header h1 { margin: 0 0 10px; color: var(--q-guinda); font-size: 30px; font-weight: 800; }
    .q-page-header p { margin: 0; color: #6b7280; font-size: 16px; }

    .q-layout { display: grid; grid-template-columns: 1fr 380px; gap: 25px; align-items: stretch; }

    .q-form-card { background: #fff; border: 1px solid var(--q-border); border-radius: 24px; padding: 35px; box-shadow: var(--q-shadow); }
    
    .q-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .q-group { display: flex; flex-direction: column; gap: 8px; }
    .q-full { grid-column: 1 / -1; }

    label { font-weight: 700; color: #374151; font-size: 14px; display: flex; align-items: center; gap: 5px; }
    input, textarea, select { 
        width: 100%; min-height: 48px; border: 1px solid #d1d5db; 
        border-radius: 12px; padding: 12px 16px; font: inherit; background: #fff; box-sizing: border-box;
    }
    input:focus, textarea:focus, select:focus { outline: none; border-color: var(--q-guinda); box-shadow: 0 0 0 4px rgba(122,23,55,0.08); }

    /* RADIOS ALINEADOS A LA IZQUIERDA */
    .q-radio-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 5px; }
    .q-radio-item { 
        display: flex; 
        align-items: center; 
        justify-content: flex-start; /* Alineación a la izquierda */
        gap: 12px; 
        border: 1px solid var(--q-border); 
        background: #f9fafb; 
        border-radius: 12px; 
        padding: 14px; 
        cursor: pointer; 
        font-weight: 600;
        font-size: 13px;
        transition: 0.2s;
    }
    .q-radio-item input { width: 18px; height: 18px; min-height: auto; cursor: pointer; margin: 0; }
    .q-radio-item:hover { border-color: var(--q-guinda); background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }

    /* COLUMNA DE IMÁGENES SIMÉTRICA */
    .q-side-images { display: flex; flex-direction: column; gap: 20px; }
    .q-img-box { 
        background: #fff; border: 1px solid var(--q-border); border-radius: 24px; 
        overflow: hidden; position: relative; box-shadow: var(--q-shadow);
    }
    
    .q-img-main { flex: 2; min-height: 450px; } 
    .q-img-main img { width: 100%; height: 100%; object-fit: cover; object-position: center 15%; display: block; }

    .q-img-sec { flex: 1; display: flex; align-items: center; justify-content: center; background: #fff; padding: 20px; }
    .q-img-sec img { width: 90%; height: 90%; object-fit: contain; }

    .q-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(122,23,55,0.1), rgba(122,23,55,0.4)); pointer-events: none; }

    /* BOTONES */
    .q-actions { margin-top: 30px; display: flex; justify-content: flex-end; gap: 15px; }
    .q-btn { 
        display: inline-flex; align-items: center; justify-content: center; min-height: 48px; 
        padding: 0 30px; border-radius: 12px; background: var(--q-guinda); color: #fff; 
        font-weight: 700; border: none; cursor: pointer; text-decoration: none; transition: 0.2s;
    }
    .q-btn-light { background: #f3f4f6; color: #374151; }
    .q-btn:hover { background: var(--q-guinda-2); transform: translateY(-1px); }

    /* FOOTER */
    .q-footer { background: var(--q-guinda); color: #fff; padding: 35px 0; width: 100%; margin-top: auto; }
    .q-footer-inner { display: flex; justify-content: space-between; align-items: center; font-weight: 600; opacity: 0.9; }

    /* MODALES */
    .q-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
    .q-modal { background: #fff; border-radius: 24px; padding: 35px; width: min(480px, 90%); text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .q-modal h3 { font-size: 24px; margin-bottom: 10px; }
    .q-modal img { width: 100%; border-radius: 15px; margin: 20px 0; border: 1px solid var(--q-border); }

    @media (max-width: 1000px) {
        .q-layout { grid-template-columns: 1fr; }
        .q-grid, .q-radio-grid { grid-template-columns: 1fr; }
        .q-img-main { height: 400px; min-height: auto; }
    }
  </style>
   <!-- CSS GENERAL -->
  <link rel="stylesheet" href="assets/css/styles.css">

  <!-- CSS SOLO PARA INICIO -->
  <link rel="stylesheet" href="assets/css/index.css">
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

</head>
<body>

<?php if ($mostrarModal): ?>
  <div class="q-modal-overlay">
    <div class="q-modal">
      <h3 style="color: <?php echo $modalTipo == 'success' ? '#15803d' : '#b91c1c'; ?>">
        <?php echo htmlspecialchars($modalTitulo); ?>
      </h3>
      <p><?php echo htmlspecialchars($modalMensaje); ?></p>
      <button class="q-btn" onclick="window.location.href='queja.php'">Aceptar</button>
    </div>
  </div>
<?php endif; ?>

<header class="q-topbar">
  <div class="q-wrap q-topbar-inner">
    <a href="index.php" class="q-brand">
        <div class="q-brand-logo"><img src="assets/img/logoguinda.jpeg" alt="Logo"></div>
        <div>
            <h2>Samuel Te Escucha</h2>
            <span class="q-brand-sub">Oficina virtual</span>
        </div>
    </a>
    <nav class="q-nav">
      <a href="index.php">Inicio</a>
      <a href="cita.php">Citas</a>
      <a href="apoyos.php">Apoyos</a>
    </nav>
  </div>
</header>

<main class="q-wrap q-page-shell">
  <div class="q-page-header">
    <h1>Reporte de Problemáticas</h1>
    <p>Completa el formulario para recibir atención por parte de nuestro equipo.</p>
  </div>

  <section class="q-layout">
    <div class="q-form-card">
      <form method="POST" action="guardar_queja.php" enctype="multipart/form-data">
        <div class="q-grid">
          <div class="q-group">
            <label>Nombre</label>
            <input type="text" name="nombre" required>
          </div>
          <div class="q-group">
            <label>Apellido paterno</label>
            <input type="text" name="apellido_paterno" required>
          </div>
          <div class="q-group">
            <label>Apellido materno</label>
            <input type="text" name="apellido_materno" required>
          </div>

          <div class="q-group">
            <label>Teléfono Celular</label>
            <input type="text" name="celular_1" maxlength="10" pattern="[0-9]{10}" required>
          </div>
          <div class="q-group">
            <label>Correo electrónico</label>
            <input type="email" name="correo" required>
          </div>
          <div class="q-group">
            <label>Sección electoral <button type="button" style="border:none; background:var(--q-guinda); color:#fff; border-radius:50%; width:22px; height:22px; cursor:pointer; font-size:12px;" onclick="document.getElementById('modalSeccion').style.display='flex'">?</button></label>
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

          <div class="q-group">
            <label>Calle</label>
            <input type="text" name="calle" required>
          </div>
          <div class="q-group">
            <label>No. exterior</label>
            <input type="text" name="no_exterior" required>
          </div>
   <div class="q-group">
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

<div class="q-group">
  <label>Colonia</label>
  <select id="colonia" name="colonia" required>
    <option value="">Seleccione una colonia</option>
  </select>
</div>

<div class="q-group">
  <label>Municipio</label>
  <input type="text" id="municipio" name="municipio" readonly required>
</div>
         

          <div class="q-group q-full">
            <label>Tipo de reporte</label>
            <div class="q-radio-grid">
              <label class="q-radio-item"><input type="radio" name="tipo" value="Seguridad" required> Seguridad pública</label>
              <label class="q-radio-item"><input type="radio" name="tipo" value="Alumbrado"> Alumbrado público</label>
              <label class="q-radio-item"><input type="radio" name="tipo" value="Agua"> Abasto de agua</label>
              <label class="q-radio-item"><input type="radio" name="tipo" value="Basura"> Recolección de basura</label>
              <label class="q-radio-item"><input type="radio" name="tipo" value="Calles"> Estado de calles</label>
              <label class="q-radio-item"><input type="radio" name="tipo" value="Áreas verdes"> Áreas verdes</label>
              <label class="q-radio-item"><input type="radio" name="tipo" value="Áreas verdes"> Otro</label>
            </div>
          </div>

          <div class="q-group q-full">
            <label>Descripción detallada</label>
            <textarea name="descripcion" rows="5" required placeholder="Describe brevemente la situación..."></textarea>
          </div>

          <div class="q-group q-full">
            <label>Adjuntar Evidencia (Foto)</label>
            <input type="file" name="evidencia" accept="image/*" required>
          </div>
        </div>

        <div class="q-actions">
          <a href="index.php" class="q-btn q-btn-light">Volver al inicio</a>
          <button type="submit" class="q-btn">Enviar Reporte</button>
        </div>
      </form>
    </div>

    <aside class="q-side-images">
      <div class="q-img-box q-img-main">
        <div class="q-overlay"></div>
        <img src="assets/img/samuel vertical.jpg.jpeg" alt="Samuel">
      </div>
      
    </aside>
  </section>
</main>

<footer class="footer">
  <div class="wrap footer__inner">

    <div class="footer-left">
      <img src="assets/img/SM LOGO-07.png" alt="Logo" class="footer-logo">
      <span>© 2026 Samuel Te Escucha</span>
    </div>

    <div>Oficina virtual

    </div>

    <a href="login.php" onclick="alert('Solo el personal tiene acceso a este apartado')">
      Personal
    </a>

  </div>
</footer>

<div id="modalSeccion" class="q-modal-overlay" style="display:none;">
  <div class="q-modal">
    <h3>¿Cómo encontrar tu sección?</h3>
    <p>Ubica el apartado "Sección" en la parte frontal inferior de tu credencial para votar.</p>
    <img src="assets/img/INE.jpg.jpeg" alt="Ejemplo INE">
    <button class="q-btn" onclick="document.getElementById('modalSeccion').style.display='none'">Entendido</button>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const cpInput = document.getElementById("codigo_postal");
  const colSelect = document.getElementById("colonia");
  const munInput = document.getElementById("municipio");

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
            let o = document.createElement("option");
            o.value = o.textContent = c;
            colSelect.appendChild(o);
          });
          munInput.value = data.municipio || "";
        }
      })
      .catch(e => console.error("Error CP:", e));
  });
});ript>

</body>
</html>