<?php
require_once "includes/session.php";
require_once "db.php";
require_once "includes/security_headers.php";
require_once "includes/csrf.php";
require_once "includes/rate_limit.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validate('login.php');

    // Rate limiting: máximo 5 intentos de login por minuto por IP
    if (!rate_limit_check($pdo, 'login', 5, 60)) {
        $error = "Demasiados intentos. Espera un momento antes de reintentar.";
    } else {
        $usuario = trim($_POST["usuario"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($usuario === "" || $password === "") {
            $error = "Debes capturar usuario y contraseña.";
        } else {
            $sql = "SELECT id, usuario, password_hash, nombre_completo
                    FROM empleados
                    WHERE LOWER(TRIM(usuario)) = LOWER(TRIM(:usuario))
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([":usuario" => $usuario]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empleado || !password_verify($password, $empleado["password_hash"])) {
                $error = "Usuario o contraseña incorrectos.";
            } else {
                session_regenerate_id(true);

                $_SESSION["empleado_id"] = $empleado["id"];
                $_SESSION["empleado_usuario"] = $empleado["usuario"];
                $_SESSION["empleado_nombre"] = $empleado["nombre_completo"];

                header("Location: dashboard.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Personal | Samuel Te Escucha</title>
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
        <span class="brand__sub">Acceso del personal</span>
      </div>
    </div>
  </div>
</header>

<main class="login-wrap">
  <section class="login-card">
    <h1>Iniciar sesión</h1>
    <p>Acceso exclusivo para personal autorizado.</p>

    <?php if ($error !== ""): ?>
      <div style="background:#fdecea;color:#8a1c1c;padding:12px 14px;border-radius:12px;margin-bottom:18px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label>Usuario</label>
        <input type="text" name="usuario" required>
      </div>

      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" required>
      </div>

      <div class="form-actions" style="justify-content:space-between;">
        <a href="index.php" class="btn btn--light">Volver</a>
        <button type="submit" class="btn">Entrar</button>
      </div>
    </form>
  </section>
</main>

</body>
</html>
