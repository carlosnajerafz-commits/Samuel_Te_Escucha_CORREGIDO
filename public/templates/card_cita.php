<?php
/**
 * Componente Tarjeta de Cita — Samuel Te Escucha
 *
 * Muestra los detalles de una solicitud de cita y proporciona los formularios
 * para cambiar su estado (aceptar, rechazar, etc.).
 *
 * @var array $item Datos de la cita
 * @var string $origen Contexto desde donde se renderiza ('dashboard' o 'citas')
 */
$folio = "CITA-" . str_pad((string)$item["id"], 6, "0", STR_PAD_LEFT);
$estatus = strtolower($item["estatus"] ?? "solicitada");
$badgeClass = "badge--" . $estatus;
?>
<div class="list-item">
  <div style="margin-bottom:8px;font-size:13px;font-weight:800;color:#7A1737;display:flex;justify-content:space-between;align-items:center;">
    <span>Folio: <?php echo $folio; ?></span>
    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(ucfirst($item["estatus"])); ?></span>
  </div>

  <strong><?php echo htmlspecialchars(nombreCompleto($item)); ?></strong>

  <div style="margin-top:6px;color:#6b7280;font-size:13px;">
    📅 <?php echo htmlspecialchars($item["fecha"]); ?>
    &nbsp;·&nbsp;
    🕐 <?php echo htmlspecialchars(substr($item["hora"], 0, 5)); ?>
  </div>

  <?php if (!empty($item["celular_1"])): ?>
    <div style="margin-top:4px;color:#6b7280;font-size:13px;">
      📞 Cel 1: <?php echo htmlspecialchars($item["celular_1"]); ?>
      <?php if (!empty($item["celular_2"])): ?>
        &nbsp;·&nbsp; Cel 2: <?php echo htmlspecialchars($item["celular_2"]); ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($item["correo"])): ?>
    <div style="margin-top:4px;color:#6b7280;font-size:13px;">
      ✉️ <?php echo htmlspecialchars($item["correo"]); ?>
    </div>
  <?php endif; ?>

  <?php
    $dir = trim(($item["calle"] ?? "") . " #" . ($item["no_exterior"] ?? ""));
    if (!empty($item["no_interior"])) $dir .= " Int. " . $item["no_interior"];
    if (!empty($item["colonia"]))     $dir .= ", Col. " . $item["colonia"];
    if (!empty($item["municipio"]))   $dir .= ", " . $item["municipio"];
    if (!empty($item["codigo_postal"])) $dir .= ", CP " . $item["codigo_postal"];
  ?>
  <?php if (!empty($item["calle"])): ?>
    <div style="margin-top:4px;color:#6b7280;font-size:13px;">
      🏠 <?php echo htmlspecialchars($dir); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($item["seccion_electoral"])): ?>
    <div style="margin-top:4px;color:#6b7280;font-size:13px;">
      🗳️ Sección: <?php echo htmlspecialchars($item["seccion_electoral"]); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($item["motivo"])): ?>
    <div style="margin-top:4px;color:#6b7280;font-size:13px;">
      📝 Motivo: <?php echo htmlspecialchars($item["motivo"]); ?>
    </div>
  <?php endif; ?>

  <?php if ($item["estatus"] === 'solicitada'): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
      <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
        <input type="hidden" name="estatus" value="aceptada">
        <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen ?? ''); ?>">
        <button type="submit" class="btn">✔ Aceptar</button>
      </form>
      <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
        <input type="hidden" name="estatus" value="rechazada">
        <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen ?? ''); ?>">
        <button type="submit" class="btn btn--light" onclick="return confirm('¿Rechazar esta cita?');">✘ Rechazar</button>
      </form>
    </div>
  <?php elseif ($item["estatus"] === 'aceptada'): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
      <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
        <input type="hidden" name="estatus" value="realizada">
        <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen ?? ''); ?>">
        <button type="submit" class="btn" style="background:#166534;">✔ Realizada</button>
      </form>
      <form method="POST" action="actualizar_estatus_cita.php" style="margin:0;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
        <input type="hidden" name="estatus" value="cancelada">
        <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen ?? ''); ?>">
        <button type="submit" class="btn btn--light" onclick="return confirm('¿Cancelar esta cita?');">✘ Cancelar</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if (isset($permitirEliminar) && $permitirEliminar): ?>
    <div style="margin-top:10px;">
      <form method="POST" action="eliminar_cita.php" style="margin:0;" onsubmit="return confirm('¿Eliminar definitivamente este registro? Esta acción no se puede deshacer.');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$item["id"]; ?>">
        <button type="submit" class="btn btn--light" style="font-size:12px;padding:4px 10px;border:1px solid #dc2626;color:#dc2626;">🗑 Eliminar</button>
      </form>
    </div>
  <?php endif; ?>
</div>
