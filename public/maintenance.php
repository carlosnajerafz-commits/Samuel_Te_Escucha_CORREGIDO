<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>En mantenimiento | Samuel Te Escucha</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f3f4f6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
      text-align: center;
    }

    .card {
      background: #fff;
      border-radius: 24px;
      padding: 52px 44px;
      max-width: 520px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.10);
    }

    .logo {
      width: 80px;
      height: 80px;
      border-radius: 16px;
      object-fit: contain;
      margin-bottom: 24px;
    }

    .icon {
      font-size: 56px;
      margin-bottom: 18px;
      display: block;
    }

    h1 {
      color: rgb(122,23,55);
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 14px;
    }

    p {
      color: #6b7280;
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 10px;
    }

    .divider {
      border: none;
      border-top: 1px solid #e5e7eb;
      margin: 28px 0;
    }

    .contact {
      font-size: 14px;
      color: #9ca3af;
    }

    .contact a {
      color: rgb(122,23,55);
      font-weight: 700;
      text-decoration: none;
    }

    /* barra animada */
    .progress-bar {
      width: 100%;
      height: 6px;
      background: #f3f4f6;
      border-radius: 99px;
      margin: 24px 0 0;
      overflow: hidden;
    }
    .progress-bar__fill {
      height: 100%;
      width: 40%;
      background: linear-gradient(90deg, rgb(122,23,55), #e05b8a);
      border-radius: 99px;
      animation: slide 2s ease-in-out infinite alternate;
    }
    @keyframes slide {
      from { transform: translateX(-60%); }
      to   { transform: translateX(200%); }
    }
  </style>
</head>
<body>

  <div class="card">
    <img src="assets/img/logo.png" alt="Samuel Te Escucha" class="logo">

    <span class="icon">🔧</span>

    <h1>Sitio en mantenimiento</h1>

    <p>Estamos realizando mejoras para ofrecerte una mejor experiencia.</p>
    <p>Volveremos en breve.</p>

    <div class="progress-bar">
      <div class="progress-bar__fill"></div>
    </div>

    <hr class="divider">

    <p class="contact">
      ¿Necesitas ayuda? Contáctanos por WhatsApp:<br>
      <a href="https://wa.me/525521724723" target="_blank">55 2172 4723</a>
    </p>
  </div>

</body>
</html>
