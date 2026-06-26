<?php
/**
 * Componente Tarjeta de Queja — Samuel Te Escucha
 *
 * Muestra los detalles de una solicitud de queja y proporciona los formularios
 * para cambiar su estado (atender, cerrar, etc.) o descargar su evidencia.
 *
 * @var array $queja Datos de la queja
 * @var string $origen Contexto desde donde se renderiza ('dashboard' o 'quejas')
 */
$folio = "QUEJA-" . str_pad((string)$queja["id"], 6, "0", STR_PAD_LEFT);
$estatus = strtolower($queja["estatus"] ?? "pendiente");
$badgeClass = "badge--" . $estatus;
?>
<div class="list-item">
  <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;display:flex;justify-content:space-between;align-items:center;">
    <span>Folio: <?php echo $folio; ?></span>
    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(ucfirst($queja["estatus"])); ?></span>
  </div>

  <strong><?php echo htmlspecialchars(nombreCompleto($queja)); ?></strong>

  <div style="margin-top:6px;font-size:13px;">
    <strong style="color:#7A1737;">Tipo:</strong>
    <?php echo htmlspecialchars($queja["tipo"]); ?>
  </div>

  <?php if (!empty($queja["celular_1"])): ?>
    <div style="color:#6b7280;font-size:13px;margin-top:4px;">
      📞 Cel 1: <?php echo htmlspecialchars($queja["celular_1"]); ?>
      <?php if (!empty($queja["celular_2"])): ?>
        &nbsp;·&nbsp; Cel 2: <?php echo htmlspecialchars($queja["celular_2"]); ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($queja["correo"])): ?>
    <div style="color:#6b7280;font-size:13px;margin-top:2px;">
      ✉️ <?php echo htmlspecialchars($queja["correo"]); ?>
    </div>
  <?php endif; ?>

  <?php
    $dir = trim(($queja["calle"] ?? "") . " #" . ($queja["no_exterior"] ?? ""));
    if (!empty($queja["no_interior"])) $dir .= " Int. " . $queja["no_interior"];
    if (!empty($queja["colonia"]))     $dir .= ", Col. " . $queja["colonia"];
    if (!empty($queja["municipio"]))   $dir .= ", " . $queja["municipio"];
    if (!empty($queja["codigo_postal"])) $dir .= ", CP " . $queja["codigo_postal"];
  ?>
  <?php if (!empty($queja["calle"])): ?>
    <div style="color:#6b7280;font-size:13px;margin-top:2px;">
      🏠 <?php echo htmlspecialchars($dir); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($queja["descripcion"])): ?>
    <div style="color:#6b7280;font-size:13px;margin-top:4px;">
      📝 <?php echo htmlspecialchars($queja["descripcion"]); ?>
    </div>
  <?php endif; ?>

  <div style="font-size:11px;color:#9ca3af;margin-top:6px;">
    Registrado: <?php echo htmlspecialchars($queja["created_at"]); ?>
  </div>

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
    <?php if (!empty($queja["evidencia_path"])): ?>
      <a href="<?php echo htmlspecialchars($queja["evidencia_path"]); ?>" target="_blank" class="btn btn--light" style="font-size:12px;padding:6px 14px;">🖼 Ver evidencia</a>
    <?php endif; ?>

    <?php if ($queja["estatus"] === 'pendiente'): ?>
      <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
        <input type="hidden" name="estatus" value="atendida">
        <button type="submit" class="btn" style="font-size:12px;padding:6px 14px;">Atender</button>
      </form>
      <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
        <input type="hidden" name="estatus" value="cerrada">
        <button type="submit" class="btn btn--light" style="font-size:12px;padding:6px 14px;" onclick="return confirm('¿Cerrar esta queja?');">Cerrar</button>
      </form>
    <?php elseif ($queja["estatus"] === 'atendida'): ?>
      <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
        <input type="hidden" name="estatus" value="completada">
        <button type="submit" class="btn" style="font-size:12px;padding:6px 14px;background:#166534;">✔ Completar</button>
      </form>
      <form method="POST" action="actualizar_estatus_queja.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
        <input type="hidden" name="estatus" value="cerrada">
        <button type="submit" class="btn btn--light" style="font-size:12px;padding:6px 14px;" onclick="return confirm('¿Cerrar esta queja?');">Cerrar</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (isset($permitirEliminar) && $permitirEliminar): ?>
    <div style="margin-top:10px;">
      <form method="POST" action="eliminar_queja.php" style="margin:0;" onsubmit="return confirm('¿Eliminar definitivamente esta queja? Esta acción no se puede deshacer.');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$queja["id"]; ?>">
        <button type="submit" class="btn btn--light" style="font-size:12px;padding:4px 10px;border:1px solid #dc2626;color:#dc2626;">🗑 Eliminar</button>
      </form>
    </div>
  <?php endif; ?>
</div>
