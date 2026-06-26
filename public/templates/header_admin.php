<?php
/**
 * Cabecera administrativa común — Samuel Te Escucha
 *
 * Verifica la autenticación del empleado, establece las cabeceras de seguridad HTTP,
 * y renderiza el encabezado HTML junto con la barra de navegación interna.
 */
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/security_headers.php";

// Forzar autenticación de empleado
require_auth();

$activeTab = $activeTab ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Panel de Control | Samuel Te Escucha'); ?></title>
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
        <img src="assets/img/logo.png" alt="Samuel Te Escucha">
      </div>
      <div>
        <h2>Samuel Te Escucha</h2>
        <span class="brand__sub">Panel de administración</span>
      </div>
    </div>

    <nav class="nav">
      <a href="dashboard.php" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">Panel</a>
      <a href="empleados_queja.php" class="<?php echo $activeTab === 'quejas' ? 'active' : ''; ?>">Quejas</a>
      <a href="empleados_apoyo.php" class="<?php echo $activeTab === 'apoyos' ? 'active' : ''; ?>">Apoyos</a>
      <a href="empleados_cita.php" class="<?php echo $activeTab === 'citas' ? 'active' : ''; ?>">Citas</a>
      <a href="empleados_comedor.php" class="<?php echo $activeTab === 'comedor' ? 'active' : ''; ?>">Comedor</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>
