<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Provider dei necrologi per le sitemap native di WordPress (usato quando
 * Yoast non e' attivo). Caricato solo dentro 'wp_sitemaps_init', quando la
 * classe base esiste.
 */
class PortaleFunebreNecrologi_Sitemap_Provider extends WP_Sitemaps_Provider {

    public function __construct() {
        $this->name        = PortaleFunebreNecrologi_Sitemap::NOME;
        $this->object_type = PortaleFunebreNecrologi_Sitemap::NOME;
    }

    public function get_url_list($page_num, $object_subtype = '') {

        $voci = PortaleFunebreNecrologi_Sitemap::Voci();

        if (!$voci) { return []; }

        $per_pagina = wp_sitemaps_get_max_urls($this->object_type);
        $out        = [];

        foreach (array_slice($voci, ($page_num - 1) * $per_pagina, $per_pagina) as $voce) {
            $url = ['loc' => $voce['loc']];
            if ($voce['mod']) { $url['lastmod'] = gmdate('c', $voce['mod']); }
            $out[] = $url;
        }

        return $out;
    }

    public function get_max_num_pages($object_subtype = '') {
        $voci = PortaleFunebreNecrologi_Sitemap::Voci();
        return $voci ? (int) ceil(count($voci) / wp_sitemaps_get_max_urls($this->object_type)) : 0;
    }

}
