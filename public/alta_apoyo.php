<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    if ($nombre !== "") {
        $sql = "INSERT INTO apoyos (nombre, descripcion, activo)
                VALUES (:nombre, :descripcion, TRUE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":nombre" => $nombre,
            ":descripcion" => $descripcion
        ]);
        $mensaje = "Apoyo dado de alta correctamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alta de Apoyos | Samuel Te Escucha</title>
  <link rel="stylesheet" href="assets/css/styles.css">
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
        <span class="brand__sub">Panel de personal</span>
      </div>
    </div>

    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="alta_apoyo.php">Alta de apoyos</a>
      <a href="logout.php">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="wrap page-shell">
  <div class="page-header">
    <h1>Dar de alta apoyo</h1>
    <p>Registra nuevos apoyos para que la ciudadanía pueda inscribirse.</p>
  </div>

  <?php if ($mensaje !== ""): ?>
    <div style="background:#e8f5e9;color:#1b5e20;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-weight:700;">
      <?php echo htmlspecialchars($mensaje); ?>
    </div>
  <?php endif; ?>

  <section class="form-card">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group form-group--full">
          <label>Nombre del apoyo</label>
          <input type="text" name="nombre" required>
        </div>

        <div class="form-group form-group--full">
          <label>Descripción</label>
          <textarea name="descripcion"></textarea>
        </div>
      </div>

      <div class="form-actions">
        <a href="dashboard.php" class="btn btn--light">Volver</a>
        <button type="submit" class="btn">Guardar apoyo</button>
      </div>
    </form>
  </section>
</main>

</body>
</html>