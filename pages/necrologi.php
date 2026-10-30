<?php

use function PortaleFunebreNecrologi\get_plugin_page_url;

if (!defined('ABSPATH')) { exit; }

$url_pagina = get_plugin_page_url('necrologi');

// Pagina del singolo necrologio: informazioni complete e impostazioni SEO.
if (!empty($_GET['necrologio'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo lettura.
    require plugin_dir_path(__DIR__) . 'inc/admin/dettaglio_necrologio.php';
    return;
}

require_once plugin_dir_path(__DIR__) . 'inc/admin/tabella_necrologi.php';

$necrologi = PortaleFunebreNecrologi_Repository::GetListaCompleta();

?>

<div class="pfn-nt">

    <?php if (is_wp_error($necrologi)) { ?>

        <div class="notice notice-error inline"><p>Il portale dei necrologi non risponde in questo momento. Riprova tra qualche minuto.</p></div>

    <?php } else {

        $tabella = new PortaleFunebreNecrologi_TabellaNecrologi($necrologi, $url_pagina);
        $tabella->prepare_items();

        ?>

        <?php $tabella->views(); ?>

        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr(isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : ''); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"/>
            <?php if (!empty($_GET['vista'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
                <input type="hidden" name="vista" value="<?php echo esc_attr(sanitize_key(wp_unslash($_GET['vista']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"/>
            <?php } ?>
            <?php $tabella->search_box('Cerca necrologi', 'pfn-cerca'); ?>
            <?php $tabella->display(); ?>
        </form>

    <?php } ?>

</div>
