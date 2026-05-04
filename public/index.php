<!DOCTYPE html>
<html lang="es">
<head>
 <meta name="description" content="Samuel Te Escucha: plataforma ciudadana para solicitar citas, apoyos y enviar quejas.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://samuelteescucha.com/">
  <title>Samuel Te Escucha</title>

  <!-- CSS GENERAL -->
  <link rel="stylesheet" href="assets/css/styles.css">

  <!-- CSS SOLO PARA INICIO -->
  <link rel="stylesheet" href="assets/css/index.css">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>



<script>
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
</script>

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
</header>

<!-- HERO / CARRUSEL -->
<section class="hero hero--carousel">

  <div class="carousel" id="heroCarousel">

    <!-- SLIDES -->
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

    <div class="carousel__slide" style="background-image:url('assets/img/CentralJoven.jpeg');">
      <div class="carousel__overlay"></div>
    </div>


   

    <!-- CONTENIDO -->
    <div class="hero__content wrap">
    
      <h1>Samuel Te Escucha</h1>
      <p>Tú diputado te escucha, te apoya y te resuelve</p>

      

    <!-- DOTS -->
    <div class="carousel__dots" id="carouselDots"></div>

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
      <h3>Solicitud De Gestión</h3>
      <p>Reporta problemas de servicios públicos, seguridad, agua, alumbrado y más.</p>
      <a href="queja.php" class="btn">Ir al formulario</a>
    </article>

   
    <article class="feature-card">
      <h3>Apoyos</h3>
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

    <a href="login.php" onclick="alert('Solo el personal tiene acceso a este apartado')">
      Personal
    </a>

  </div>
</footer>

<!-- JS -->
<script src="assets/js/app.js"></script>

</body>
</html>