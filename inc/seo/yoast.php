<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Integrazione con Yoast SEO: i meta del necrologio passano dai filtri
 * pubblici di Yoast, cosi' non ci sono tag doppi e il resto del sito resta
 * gestito da Yoast come sempre.
 */
class PortaleFunebreNecrologi_Seo_Yoast {

    /** Meta di Yoast che descrivono la pagina contenitore e non il necrologio. */
    const PRESENTER_ESCLUSI = [
        'Yoast\WP\SEO\Presenters\Open_Graph\Article_Published_Time_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Article_Modified_Time_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Article_Author_Presenter',
        'Yoast\WP\SEO\Presenters\Slack\Enhanced_Data_Presenter',
    ];

    static function IsAttivo() {
        return defined('WPSEO_VERSION');
    }

    static function Init() {

        add_filter('wpseo_title', [__CLASS__, 'Titolo'], 20);
        add_filter('wpseo_opengraph_title', [__CLASS__, 'Titolo'], 20);
        add_filter('wpseo_twitter_title', [__CLASS__, 'Titolo'], 20);

        add_filter('wpseo_metadesc', [__CLASS__, 'Descrizione'], 20);
        add_filter('wpseo_opengraph_desc', [__CLASS__, 'Descrizione'], 20);
        add_filter('wpseo_twitter_description', [__CLASS__, 'Descrizione'], 20);

        add_filter('wpseo_canonical', [__CLASS__, 'Canonical'], 20);
        add_filter('wpseo_opengraph_url', [__CLASS__, 'Canonical'], 20);
        add_filter('wpseo_opengraph_type', [__CLASS__, 'TipoOpenGraph'], 20);
        add_filter('wpseo_add_opengraph_images', [__CLASS__, 'Immagini']);

        add_filter('wpseo_robots_array', [__CLASS__, 'Robots'], 20);
        add_filter('wpseo_frontend_presenters', [__CLASS__, 'Presenters'], 20);
        add_filter('wpseo_breadcrumb_links', [__CLASS__, 'Breadcrumb'], 20);
        add_filter('wpseo_schema_graph', [__CLASS__, 'Schema'], 20);
    }

    static function Titolo($valore) {
        return PortaleFunebreNecrologi_Seo::IsNecrologio() ? PortaleFunebreNecrologi_Seo::Titolo() : $valore;
    }

    static function Descrizione($valore) {
        return PortaleFunebreNecrologi_Seo::IsNecrologio() ? PortaleFunebreNecrologi_Seo::Descrizione() : $valore;
    }

    static function Canonical($valore) {
        return PortaleFunebreNecrologi_Seo::IsNecrologio() ? PortaleFunebreNecrologi_Seo::Canonical() : $valore;
    }

    static function TipoOpenGraph($valore) {
        return PortaleFunebreNecrologi_Seo::IsNecrologio() ? 'article' : $valore;
    }

    /** La foto del defunto viene prima di eventuali immagini della pagina contenitore. */
    static function Immagini($contenitore) {
        $immagine = PortaleFunebreNecrologi_Seo::Immagine();
        if ($immagine && is_object($contenitore) && method_exists($contenitore, 'add_image')) {
            $contenitore->add_image(['url' => $immagine, 'alt' => PortaleFunebreNecrologi_Render::AltFoto(PortaleFunebreNecrologi_Seo::Corrente())]);
        }
        return $contenitore;
    }

    /**
     * - La pagina contenitore vuota non va indicizzata.
     * - Il necrologio va indicizzato anche se la pagina contenitore e' stata
     *   impostata come noindex in Yoast (scelta sensata, visto che da sola e'
     *   vuota). Resta escluso solo se il sito scoraggia i motori di ricerca.
     */
    static function Robots($robots) {

        if (!is_array($robots)) { return $robots; }

        if (PortaleFunebreNecrologi_Seo::IsContenitoreVuoto()) {
            $robots['index'] = 'noindex';
            foreach (['imageindex', 'archive', 'snippet', 'max-snippet', 'max-image-preview', 'max-video-preview'] as $chiave) {
                unset($robots[$chiave]);
            }
            return $robots;
        }

        if (PortaleFunebreNecrologi_Seo::IsNecrologio() && get_option('blog_public')) {
            $robots['index']             = 'index';
            $robots['follow']            = 'follow';
            $robots['max-snippet']       = 'max-snippet:-1';
            $robots['max-image-preview'] = 'max-image-preview:large';
            $robots['max-video-preview'] = 'max-video-preview:-1';
        }

        return $robots;
    }

    static function Presenters($presenters) {

        if (!PortaleFunebreNecrologi_Seo::IsNecrologio() || !is_array($presenters)) { return $presenters; }

        return array_values(array_filter($presenters, static function ($presenter) {
            return !in_array(get_class($presenter), self::PRESENTER_ESCLUSI, true);
        }));
    }

    /** Home › Pagina della lista › Nome del defunto. */
    static function Breadcrumb($briciole) {

        if (!PortaleFunebreNecrologi_Seo::IsNecrologio() || !is_array($briciole) || !$briciole) { return $briciole; }

        $out   = [reset($briciole)];
        $lista = PortaleFunebreNecrologi_Sitemap::PaginaLista();

        if ($lista) {
            $out[] = ['url' => get_permalink($lista), 'text' => get_the_title($lista)];
        }

        $out[] = ['url' => PortaleFunebreNecrologi_Seo::Canonical(), 'text' => PortaleFunebreNecrologi_Seo::Corrente()->Nome()];

        return $out;
    }

    /**
     * Il grafo di Yoast descrive la pagina contenitore: si spostano i suoi
     * identificativi sull'URL del necrologio e si aggiungono defunto e funerale.
     */
    static function Schema($grafo) {

        if (!PortaleFunebreNecrologi_Seo::IsNecrologio() || !is_array($grafo)) { return $grafo; }

        $necro    = PortaleFunebreNecrologi_Seo::Corrente();
        $pagina   = PortaleFunebreNecrologi_Seo::PaginaContenitore();
        $vecchio  = $pagina ? get_permalink($pagina) : '';
        $url      = $necro->Url();

        if ($vecchio) { $grafo = self::SostituisciUrl($grafo, $vecchio, $url); }

        if (!PortaleFunebreNecrologi_Schema::IsAttivo()) { return $grafo; }

        foreach ($grafo as $i => $nodo) {
            if (!is_array($nodo) || !isset($nodo['@type']) || !in_array('WebPage', (array) $nodo['@type'], true)) { continue; }
            $grafo[$i]['name']        = PortaleFunebreNecrologi_Seo::Titolo();
            $grafo[$i]['description'] = PortaleFunebreNecrologi_Seo::Descrizione();
            $grafo[$i]['about']       = ['@id' => PortaleFunebreNecrologi_Schema::IdPersona($necro)];
            if ($necro->Immagine()) { $grafo[$i]['thumbnailUrl'] = $necro->Immagine(); }
            unset($grafo[$i]['datePublished'], $grafo[$i]['dateModified'], $grafo[$i]['primaryImageOfPage'], $grafo[$i]['image']);
        }

        return array_merge($grafo, PortaleFunebreNecrologi_Schema::Nodi($necro));
    }

    /** Sostituisce l'URL della pagina contenitore (e i suoi #frammenti) in tutto il grafo. */
    private static function SostituisciUrl($valore, $vecchio, $nuovo) {

        if (is_array($valore)) {
            foreach ($valore as $chiave => $figlio) { $valore[$chiave] = self::SostituisciUrl($figlio, $vecchio, $nuovo); }
            return $valore;
        }

        if (!is_string($valore)) { return $valore; }

        if ($valore === $vecchio) { return $nuovo; }

        if (strpos($valore, $vecchio . '#') === 0) { return $nuovo . substr($valore, strlen($vecchio)); }

        return $valore;
    }

}
