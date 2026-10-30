<?php

if (!defined('ABSPATH')) { exit; }

if (!PortaleFunebreNecrologi::IsConfigurato()) {  echo 'lista'; return; }

$impostazioni = PortaleFunebreNecrologi::GetImpostazioni();

$tipo_vis     = (isset($impostazioni['tipo_visualizzazione'])) ? $impostazioni['tipo_visualizzazione'] :'grid';
$profilo_box  = (isset($impostazioni['profilo_box']))   ? $impostazioni['profilo_box'] : 'verticale';
$slug_singolo = (isset($impostazioni['slug_singolo']))  ? $impostazioni['slug_singolo'] : 'necrologio';

$list_classes = [];
array_push($list_classes, $tipo_vis);
array_push($list_classes, ($profilo_box == 'verticale') ? 'necro-box-verticale' : 'necro-box-orrizzontale');

// Con il rendering lato server i box (e i loro link) sono gia' nell'HTML; se il portale non risponde li carica il JavaScript.
$necrologi = PortaleFunebreNecrologi_Render::IsAttivo() ? PortaleFunebreNecrologi_Render::Necrologi() : null;

?>

<?php if ($necrologi === null) { ?>
<div class="necrologi-loader"><div class="dg_spinner"></div></div>
<?php } ?>

<?php ?>
<div
  class="lista-necrologi <?php echo esc_attr(implode(' ', $list_classes)); ?>"
  data-pfn-list="1"
  <?php if ($necrologi !== null) { ?>data-pfn-rendered="1"<?php } ?>
  data-slug-singolo="<?php echo esc_attr($slug_singolo); ?>"
  data-tipo-vis="<?php echo esc_attr($tipo_vis); ?>"><?php
  if ($necrologi !== null) {
    foreach (PortaleFunebreNecrologi_Render::Ordina($necrologi) as $necro) {
      echo PortaleFunebreNecrologi_Render::Loop($necro); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup costruito con esc_html/esc_url/esc_attr.
    }
  }
?></div>
