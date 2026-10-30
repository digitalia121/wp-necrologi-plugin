<?php

if (!defined('ABSPATH')) { exit; }

/**
 * "Aggiorna dal portale" dalla pagina del necrologio nel backend: rinnova
 * subito la copia locale del necrologio e svuota la cache di pagina del suo
 * URL, senza aspettare la scadenza della cache.
 */
class PortaleFunebreNecrologi_CacheNecrologio {

    const AZIONE = 'portale_funebre_necrologi_aggiorna_cache';

    /** Link (con nonce) per aggiornare la cache di un necrologio. */
    static function UrlAggiorna($slug) {
        return wp_nonce_url(
            add_query_arg(['action' => self::AZIONE, 'necrologio' => rawurlencode($slug)], admin_url('admin-post.php')),
            self::AZIONE . '_' . $slug
        );
    }

    static function Gestisci() {

        $slug = isset($_GET['necrologio']) ? sanitize_title(wp_unslash($_GET['necrologio'])) : '';

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Non hai i permessi per questa operazione.', 'portale-funebre-necrologi'), 403);
        }

        check_admin_referer(self::AZIONE . '_' . $slug);

        $esito = PortaleFunebreNecrologi_Repository::AggiornaSingolo($slug) ? 'aggiornata' : 'errore';

        if ($esito === 'aggiornata') {
            PortaleFunebreNecrologi_Monitor::SvuotaCachePagine([PortaleFunebreNecrologi_Necrologio::UrlDaSlug($slug)]);
        }

        wp_safe_redirect(add_query_arg(
            ['necrologio' => $slug, 'cache' => $esito],
            \PortaleFunebreNecrologi\get_plugin_page_url('necrologi') ?: admin_url('admin.php?page=portale-funebre-necrologi-necrologi')
        ) . '#pfn-cache');
        exit;
    }

}
