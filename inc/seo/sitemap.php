<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Sitemap dei necrologi.
 *
 * - Con Yoast: necrologi-sitemap.xml, aggiunta all'indice sitemap_index.xml.
 * - Senza Yoast: provider delle sitemap native di WordPress (wp-sitemap.xml).
 *
 * In entrambi i casi la pagina contenitore vuota viene esclusa.
 */
class PortaleFunebreNecrologi_Sitemap {

    const NOME = 'necrologi';

    /** Limite di URL per singola sitemap previsto dal protocollo. */
    const MAX_URL = 50000;

    static function Init() {

        add_action('save_post', [__CLASS__, 'SvuotaPagine']);

        if (PortaleFunebreNecrologi_Seo_Yoast::IsAttivo()) {
            add_action('init', [__CLASS__, 'RegistraYoast'], 20);
            add_filter('wpseo_sitemap_index', [__CLASS__, 'IndiceYoast']);
            add_filter('wpseo_exclude_from_sitemap_by_post_ids', [__CLASS__, 'EscludiContenitore']);
        } else {
            add_action('wp_sitemaps_init', [__CLASS__, 'RegistraCore']);
            add_filter('wp_sitemaps_posts_query_args', [__CLASS__, 'EscludiContenitoreCore'], 10, 2);
        }
    }

    /**
     * Voci della sitemap.
     *
     * @return array<array{loc:string,mod:int,img:string}>|null null se il portale non risponde.
     */
    static function Voci() {

        $necrologi = PortaleFunebreNecrologi_Render::Necrologi();

        if ($necrologi === null) { return null; }

        $registro = PortaleFunebreNecrologi_Monitor::Registro();
        $voci     = [];

        foreach (array_slice($necrologi, 0, self::MAX_URL) as $necro) {
            $slug   = $necro->Slug();
            $voci[] = [
                'loc' => $necro->Url(),
                'mod' => isset($registro[$slug]['m']) ? (int) $registro[$slug]['m'] : 0,
                'img' => $necro->Immagine(),
            ];
        }

        return $voci;
    }

    /** Data dell'ultima modifica nota tra tutti i necrologi (0 se sconosciuta). */
    static function UltimaModifica() {
        $date = array_column(PortaleFunebreNecrologi_Monitor::Registro(), 'm');
        return $date ? (int) max($date) : 0;
    }

    /* ------------------------------------------------------------------
     * Yoast
     * ---------------------------------------------------------------- */

    static function RegistraYoast() {
        if (isset($GLOBALS['wpseo_sitemaps']) && is_object($GLOBALS['wpseo_sitemaps']) && method_exists($GLOBALS['wpseo_sitemaps'], 'register_sitemap')) {
            $GLOBALS['wpseo_sitemaps']->register_sitemap(self::NOME, [__CLASS__, 'GeneraYoast']);
        }
    }

    static function GeneraYoast() {

        $sitemaps = $GLOBALS['wpseo_sitemaps'];
        $voci     = self::Voci();

        if ($voci === null) {
            $sitemaps->set_bad_sitemap(true);
            return;
        }

        $link = [];
        foreach ($voci as $voce) {
            $link[] = [
                'loc'    => $voce['loc'],
                'mod'    => $voce['mod'] ? gmdate('c', $voce['mod']) : '',
                'images' => $voce['img'] ? [['src' => $voce['img']]] : [],
            ];
        }

        $sitemaps->set_sitemap($sitemaps->renderer->get_sitemap($link, self::NOME, 1));
    }

    static function IndiceYoast($xml) {

        $ultima = self::UltimaModifica();

        $xml .= "\t<sitemap>\n";
        $xml .= "\t\t<loc>" . esc_url(home_url('/' . self::NOME . '-sitemap.xml')) . "</loc>\n";
        if ($ultima) { $xml .= "\t\t<lastmod>" . esc_html(gmdate('c', $ultima)) . "</lastmod>\n"; }
        $xml .= "\t</sitemap>\n";

        return $xml;
    }

    static function EscludiContenitore($esclusi) {
        $pagina = PortaleFunebreNecrologi_Seo::PaginaContenitore();
        if ($pagina && PortaleFunebreNecrologi_Seo::IsContenitoreVuoto($pagina->ID)) {
            $esclusi   = (array) $esclusi;
            $esclusi[] = $pagina->ID;
        }
        return $esclusi;
    }

    /* ------------------------------------------------------------------
     * Sitemap native di WordPress
     * ---------------------------------------------------------------- */

    static function RegistraCore($wp_sitemaps) {
        require_once __DIR__ . '/sitemap-provider.php';
        $wp_sitemaps->registry->add_provider(self::NOME, new PortaleFunebreNecrologi_Sitemap_Provider());
    }

    static function EscludiContenitoreCore($args, $post_type) {
        if ($post_type === 'page') {
            $args['post__not_in'] = self::EscludiContenitore(isset($args['post__not_in']) ? $args['post__not_in'] : []);
        }
        return $args;
    }

    /* ------------------------------------------------------------------
     * Pagine del sito che mostrano i necrologi
     * ---------------------------------------------------------------- */

    /**
     * Pagina con la lista completa dei necrologi: quella scelta nelle
     * impostazioni oppure, in automatico, la prima che contiene lo shortcode.
     *
     * @return int ID della pagina, 0 se non c'e'.
     */
    static function PaginaLista() {

        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        $scelta       = ($impostazioni && isset($impostazioni['seo_pagina_lista'])) ? absint($impostazioni['seo_pagina_lista']) : 0;

        if ($scelta && get_post_status($scelta) === 'publish') { return $scelta; }

        foreach (self::PagineConShortcode() as $id => $shortcode) {
            if ($shortcode === 'gestione_necrologi_lista') { return (int) $id; }
        }

        return 0;
    }

    /**
     * Contenuti pubblicati che contengono la lista o lo slider dei necrologi.
     *
     * @return array<int,string> ID => shortcode trovato.
     */
    static function PagineConShortcode() {

        $in_cache = get_transient('pfn_pagine_shortcode');

        if (is_array($in_cache)) { return $in_cache; }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Risultato messo in cache qui sotto.
        $righe = $wpdb->get_results(
            "SELECT ID, post_content FROM {$wpdb->posts}
             WHERE post_status = 'publish' AND post_type IN ('page', 'post')
             AND (post_content LIKE '%[gestione\\_necrologi\\_lista%' OR post_content LIKE '%[gestione\\_necrologi\\_slider%')
             ORDER BY post_type = 'page' DESC, ID ASC LIMIT 50"
        );

        $pagine = [];
        foreach ((array) $righe as $riga) {
            $pagine[(int) $riga->ID] = has_shortcode($riga->post_content, 'gestione_necrologi_lista') ? 'gestione_necrologi_lista' : 'gestione_necrologi_slider';
        }

        set_transient('pfn_pagine_shortcode', $pagine, 12 * HOUR_IN_SECONDS);

        return $pagine;
    }

    /** Da chiamare quando cambiano i contenuti o le impostazioni. */
    static function SvuotaPagine() {
        delete_transient('pfn_pagine_shortcode');
    }

}
