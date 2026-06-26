<?php
/**
 * Cabecera pública común — Samuel Te Escucha
 *
 * Muestra el inicio del documento HTML, las cabeceras head y la barra de navegación pública.
 * Verifica también el estado de mantenimiento del sitio.
 */

// Verificar si el sitio está en modo mantenimiento
if (file_exists(dirname(__DIR__) . "/maintenance.flag")) {
    include dirname(__DIR__) . "/maintenance.php";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Samuel Te Escucha'); ?></title>
  <link rel="stylesheet" href="assets/css/styles.css?v=3002">
  <?php if (isset($customCss) && is_array($customCss)): ?>
    <?php foreach ($customCss as $css): ?>
      <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; ?>
  <?php endif; ?>
  <link rel="icon" href="assets/img/logo.png">
</head>
<body>

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
      <a href="apoyos.php">Apoyos</a>
      <a href="comedor.php">Comedor</a>
    </nav>
  </div>
</header>
