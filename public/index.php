<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Samuel Te Escucha: plataforma ciudadana para solicitar citas, apoyos y enviar quejas.">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="https://samuelteescucha.com/">
  <title>Samuel Te Escucha</title>

  <link rel="stylesheet" href="assets/css/styles.css?v=3001">
  <link rel="stylesheet" href="assets/css/index.css?v=3001">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="assets/img/logo.png">

  <meta property="og:title" content="Samuel Te Escucha">
  <meta property="og:description" content="Plataforma ciudadana para solicitar citas, apoyos y enviar quejas.">
  <meta property="og:image" content="https://samuelteescucha.com/assets/img/logo.png">
  <meta property="og:url" content="https://samuelteescucha.com/">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Samuel Te Escucha">
  <meta name="twitter:description" content="Plataforma ciudadana para solicitar citas, apoyos y enviar quejas.">
  <meta name="twitter:image" content="https://samuelteescucha.com/assets/img/logo.png">

  <style>
    /* ====== MODAL AVISO DE PRIVACIDAD ====== */
    #privacyOverlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.65);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
      padding: 20px;
    }
    #privacyModal {
      background: #fff;
      border-radius: 20px;
      width: min(680px, 100%);
      max-height: 88vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 25px 60px rgba(0,0,0,0.25);
      overflow: hidden;
    }
    #privacyModal .pm-header {
      background: rgb(122,23,55);
      color: #fff;
      padding: 22px 28px;
      display: flex;
      align-items: center;
      gap: 14px;
      flex-shrink: 0;
    }
    #privacyModal .pm-header img {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      object-fit: contain;
      background: #fff;
      padding: 4px;
    }
    #privacyModal .pm-header h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 800;
      line-height: 1.2;
    }
    #privacyModal .pm-header p {
      margin: 4px 0 0;
      font-size: 13px;
      opacity: .85;
    }
    #privacyModal .pm-body {
      padding: 24px 28px;
      overflow-y: auto;
      flex: 1;
      font-size: 13.5px;
      line-height: 1.7;
      color: #374151;
    }
    #privacyModal .pm-body h3 {
      font-size: 14px;
      color: rgb(122,23,55);
      margin: 20px 0 6px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .03em;
    }
    #privacyModal .pm-body h3:first-child { margin-top: 0; }
    #privacyModal .pm-body ul {
      margin: 6px 0 0 18px;
      padding: 0;
    }
    #privacyModal .pm-body ul li { margin-bottom: 4px; }
    #privacyModal .pm-footer {
      padding: 18px 28px;
      border-top: 1px solid #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-shrink: 0;
      flex-wrap: wrap;
      background: #fafafa;
    }
    #privacyModal .pm-footer p {
      margin: 0;
      font-size: 12px;
      color: #6b7280;
      flex: 1;
    }
    #privacyModal .pm-accept {
      background: rgb(122,23,55);
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 12px 28px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      transition: opacity .15s;
    }
    #privacyModal .pm-accept:hover { opacity: .88; }

    /* scrollbar sutil */
    #privacyModal .pm-body::-webkit-scrollbar { width: 5px; }
    #privacyModal .pm-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
  </style>
</head>

<!-- ====== MODAL AVISO DE PRIVACIDAD ====== -->
<div id="privacyOverlay">
  <div id="privacyModal">

    <div class="pm-header">
      <img src="assets/img/logo.png" alt="Logo">
      <div>
        <h2>Aviso de Privacidad</h2>
        <p>Samuel Te Escucha · samuelteescucha.com</p>
      </div>
    </div>

    <div class="pm-body">

      <h3>I. Identidad y domicilio del responsable</h3>
      <p>El sitio web SamuelTeEscucha.com, operado por la Oficina de Atención Ciudadana del Diputado Samuel Hernández Cruz, con domicilio en el Distrito XXXIII del Estado de México, es responsable del tratamiento de sus datos personales de conformidad con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.</p>

      <h3>II. Datos personales recabados</h3>
      <ul>
        <li>Datos de identificación: nombre completo, edad, sexo, fotografía o copia de identificación oficial cuando resulte indispensable.</li>
        <li>Datos de contacto: número telefónico, correo electrónico, domicilio, municipio y colonia de residencia.</li>
        <li>Datos relacionados con la gestión ciudadana: solicitudes de apoyo, peticiones, quejas, reportes y documentación de soporte.</li>
      </ul>
      <p>La Plataforma no recaba datos personales sensibles salvo que sean indispensables para gestionar apoyos específicos solicitados por el ciudadano y con su consentimiento expreso.</p>

      <h3>III. Finalidades del tratamiento</h3>
      <p><strong>Primarias:</strong></p>
      <ul>
        <li>Registrar solicitudes de audiencia ciudadana y programar citas.</li>
        <li>Integrar expedientes de atención ciudadana.</li>
        <li>Tramitar solicitudes de apoyo social, comunitario o institucional.</li>
        <li>Atender reportes, quejas y peticiones de gestión.</li>
        <li>Canalizar asuntos ante dependencias competentes.</li>
        <li>Llevar controles estadísticos y administrativos.</li>
        <li>Cumplir obligaciones legales.</li>
      </ul>
      <p><strong>Secundarias:</strong> informar sobre programas, actividades y jornadas de atención ciudadana. Puede oponerse enviando un correo al Responsable sin que ello afecte la atención de su solicitud.</p>

      <h3>IV. Transferencia de datos</h3>
      <p>Sus datos podrán transferirse, sin consentimiento adicional, a dependencias de la Administración Pública Federal, Estatal o Municipal, organismos autónomos o autoridades judiciales cuando sea necesario para atender su solicitud. El Responsable no comercializa ni vende datos personales a terceros.</p>

      <h3>V. Seguridad</h3>
      <p>Se implementan medidas de seguridad administrativas, técnicas y físicas para proteger sus datos contra daño, pérdida, alteración, destrucción o acceso no autorizado.</p>

      <h3>VI. Derechos ARCO</h3>
      <p>Usted puede acceder, rectificar, cancelar u oponerse al tratamiento de sus datos enviando una solicitud al número: <strong>55 2172 4723</strong>, incluyendo nombre completo, medio de respuesta, documentos de identidad y descripción del derecho a ejercer.</p>

      <h3>VII. Revocación del consentimiento</h3>
      <p>Puede revocar en cualquier momento el consentimiento otorgado mediante solicitud al número indicado. Ciertas obligaciones legales pueden impedir la eliminación inmediata de determinada información.</p>

      <h3>VIII. Cambios al aviso</h3>
      <p>Cualquier modificación estará disponible en <strong>samuelteescucha.com</strong>. Contacto: <strong>55 2172 4723</strong>. Última actualización: junio de 2026.</p>

    </div>

    <div class="pm-footer">
      <p>Al hacer clic en "Aceptar y continuar" confirmas que has leído y entendido este aviso de privacidad.</p>
      <button class="pm-accept" onclick="aceptarPrivacidad()">Aceptar y continuar</button>
    </div>

  </div>
</div>
<!-- ====== FIN MODAL ====== -->

<body class="index-page">

<header class="topbar">
  <div class="wrap topbar__inner">
    <div class="brand">
      <div class="brand__logo brand-logo-carousel" id="brandLogoCarousel">
        <img src="assets/img/SM_LOGO.png" alt="Samuel Te Escucha" class="brand-logo-slide">
        <img src="assets/img/logoguinda.jpeg" alt="Samuel Te Escucha" class="brand-logo-slide">
      </div>
      <div>
        <h2>Samuel Te Escucha</h2>
      </div>
    </div>

   <div class="hero__actions index-actions">
  <a href="https://maps.app.goo.gl/pS8VFPf7iK7U2AzM6" target="_blank">
    <img src="assets/img/locali.png" alt="Ubícanos" class="index-img-btn">
  </a>
  <a href="https://www.facebook.com/share/17BRGoeBCM/?mibextid=wwXIfr" target="_blank">
    <img src="assets/img/facebook logo.jpg.jpeg" alt="Facebook" class="index-img-btn">
  </a>
  <a href="https://www.instagram.com/samueledomex?igsh=dXR2cGpmYm1rejEy" target="_blank">
    <img src="assets/img/instagram logo.jpg.jpeg" alt="Instagram" class="index-img-btn">
  </a>
  <a href="https://wa.me/525521724723" target="_blank">
    <img src="assets/img/whats.png" alt="WhatsApp" class="index-img-btn">
  </a>

  
</div>
  </div>
</header>

<!-- CARRUSEL -->
<section class="hero hero--carousel">
  <div class="carousel" id="heroCarousel">

    <div class="carousel__slide active" style="background-image:url('assets/img/ISamuel1.jpg');">
      <div class="carousel__overlay"></div>
    </div>
    <div class="carousel__slide" style="background-image:url('assets/img/juntos.png');">
      <div class="carousel__overlay"></div>
    </div>
    <div class="carousel__slide" style="background-image:url('assets/img/ISamuel3.jpg');">
      <div class="carousel__overlay"></div>
    </div>
    <div class="carousel__slide" style="background-image:url('assets/img/ISamuel5.jpg');">
      <div class="carousel__overlay"></div>
    </div>
    <div class="carousel__slide" style="background-image:url('assets/img/ISamuel7.jpg');">
      <div class="carousel__overlay"></div>
    </div>
    <div class="carousel__slide" style="background-image:url('assets/img/ISamuel10.jpg');">
      <div class="carousel__overlay"></div>
    </div>
    <div class="carousel__slide" style="background-image:url('assets/img/ISamuel11.png');">
      <div class="carousel__overlay"></div>
    </div>
    <div class="carousel__slide" style="background-image:url('assets/img/CentralJove1.jpeg');">
      <div class="carousel__overlay"></div>
    </div>

    <div class="hero__content wrap">
      <h1>Samuel Te Escucha</h1>
      <p>Tú diputado te escucha, te apoya y te resuelve</p>
      <div class="carousel__dots" id="carouselDots"></div>
    </div>

  </div>
</section>

<!-- TARJETAS -->
<main class="wrap section">
  <div class="cards-grid">

    <article class="feature-card">
      <h3>Agendar cita</h3>
      <p>Solicita una cita con el diputado. Las citas solo se generan los martes.</p>
      <a href="cita.php" class="btn">Agendar cita</a>
    </article>

    <article class="feature-card">
      <h3>Solicitud de Gestión</h3>
      <p>Reporta problemas de servicios públicos, seguridad, agua, alumbrado y más.</p>
      <a href="queja.php" class="btn">Ir al formulario</a>
    </article>

    <article class="feature-card">
      <h3>Pre-registro para Apoyos</h3>
      <p>Consulta los apoyos disponibles y registra tu solicitud.</p>
      <a href="apoyos.php" class="btn">Ver apoyos</a>
    </article>

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
    <img src="assets/img/legislatura.png" alt="Legislatura" class="index-img-btn">
  </div>
</footer>

<script src="assets/js/app.js"></script>

<script>
/* ---- Carrusel de logo ---- */
document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelectorAll(".brand-logo-carousel .brand-logo-slide");
  let index = 0;
  if (!slides.length) return;
  setInterval(function () {
    slides[index].classList.remove("active");
    index = (index + 1) % slides.length;
    slides[index].classList.add("active");
  }, 2500);
});

/* ---- Aviso de privacidad ---- */
function aceptarPrivacidad() {
  document.getElementById("privacyOverlay").style.display = "none";
  try { sessionStorage.setItem("privacyAccepted", "1"); } catch(e) {}
}

// Si ya aceptó en esta sesión, no mostrar de nuevo
(function () {
  try {
    if (sessionStorage.getItem("privacyAccepted") === "1") {
      document.getElementById("privacyOverlay").style.display = "none";
    }
  } catch(e) {}
})();
</script>

</body>
</html>
