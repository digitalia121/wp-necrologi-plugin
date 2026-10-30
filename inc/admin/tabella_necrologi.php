<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Tabella dei necrologi nel backend, con la classe standard di WordPress:
 * ricerca, viste, ordinamento, paginazione e azioni al passaggio del mouse.
 */
class PortaleFunebreNecrologi_TabellaNecrologi extends WP_List_Table {

    const PER_PAGINA = 20;

    /** @var string URL della pagina "Necrologi" del plugin. */
    private $url_pagina;

    /** @var object[] Tutti i necrologi (dati completi del portale). */
    private $tutti;

    function __construct(array $necrologi, $url_pagina) {
        parent::__construct([
            'singular' => 'necrologio',
            'plural'   => 'necrologi',
            'ajax'     => false,
        ]);
        $this->tutti      = $necrologi;
        $this->url_pagina = $url_pagina;
    }

    function get_columns() {
        return [
            'foto'     => '<span class="screen-reader-text">Foto</span>',
            'nome'     => 'Defunto',
            'funerale' => 'Funerale',
            'rosario'  => 'Rosario',
            'feretro'  => 'Chiusura feretro',
            'cordogli' => 'Cordogli',
            'seo'      => 'SEO',
            'data'     => 'Pubblicato',
        ];
    }

    protected function get_sortable_columns() {
        return [
            'nome'     => ['nome', false],
            'funerale' => ['funerale', true],
            'cordogli' => ['cordogli', true],
            'data'     => ['data', true],
        ];
    }

    protected function get_default_primary_column_name() {
        return 'nome';
    }

    /** Viste: tutti, con cordogli, esclusi dai motori di ricerca. */
    protected function get_views() {

        $vista   = $this->Vista();
        $conteggi = ['tutti' => count($this->tutti), 'cordogli' => 0, 'esclusi' => 0];

        foreach ($this->tutti as $necro) {
            if (self::NumeroCordogli($necro)) { $conteggi['cordogli']++; }
            if (!PortaleFunebreNecrologi_SeoNecrologio::IsIndicizzabile($necro->slug)) { $conteggi['esclusi']++; }
        }

        $etichette = ['tutti' => 'Tutti', 'cordogli' => 'Con cordogli', 'esclusi' => 'Esclusi dai motori di ricerca'];
        $viste     = [];

        foreach ($etichette as $chiave => $etichetta) {
            if ($chiave !== 'tutti' && !$conteggi[$chiave]) { continue; }
            $url = $chiave === 'tutti' ? $this->url_pagina : add_query_arg('vista', $chiave, $this->url_pagina);
            $viste[$chiave] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url($url),
                $vista === $chiave ? ' class="current" aria-current="page"' : '',
                esc_html($etichetta),
                $conteggi[$chiave]
            );
        }

        return $viste;
    }

    function prepare_items() {

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns(), $this->get_default_primary_column_name()];

        $elementi = $this->tutti;
        $vista    = $this->Vista();
        $ricerca  = isset($_REQUEST['s']) ? trim(sanitize_text_field(wp_unslash($_REQUEST['s']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo lettura.

        if ($vista === 'cordogli') {
            $elementi = array_filter($elementi, static function ($necro) { return self::NumeroCordogli($necro) > 0; });
        } elseif ($vista === 'esclusi') {
            $elementi = array_filter($elementi, static function ($necro) { return !PortaleFunebreNecrologi_SeoNecrologio::IsIndicizzabile($necro->slug); });
        }

        if ($ricerca !== '') {
            $cerca    = remove_accents(strtolower($ricerca));
            $elementi = array_filter($elementi, static function ($necro) use ($cerca) {
                return strpos(remove_accents(strtolower($necro->nome_defunto)), $cerca) !== false;
            });
        }

        $elementi = $this->Ordina(array_values($elementi));

        $totale = count($elementi);
        $pagina = $this->get_pagenum();

        $this->set_pagination_args([
            'total_items' => $totale,
            'per_page'    => self::PER_PAGINA,
            'total_pages' => (int) ceil($totale / self::PER_PAGINA),
        ]);

        $this->items = array_slice($elementi, ($pagina - 1) * self::PER_PAGINA, self::PER_PAGINA);
    }

    function no_items() {
        echo esc_html(isset($_REQUEST['s']) && $_REQUEST['s'] !== '' ? 'Nessun necrologio corrisponde alla ricerca.' : 'Non ci sono necrologi pubblicati al momento.'); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    /* ------------------------------------------------------------------
     * Colonne
     * ---------------------------------------------------------------- */

    protected function column_foto($necro) {
        if (empty($necro->thumbnail)) { return '<span class="pfn-nt-foto pfn-nt-foto-vuota dashicons dashicons-format-image" aria-hidden="true"></span>'; }
        return sprintf(
            '<a href="%s" tabindex="-1" aria-hidden="true"><img class="pfn-nt-foto" src="%s" alt="" loading="lazy"/></a>',
            esc_url($this->UrlDettaglio($necro)),
            esc_url(PortaleFunebreNecrologi_API::GetImgUrl($necro->thumbnail))
        );
    }

    protected function column_nome($necro) {

        $obj     = new PortaleFunebreNecrologi_Necrologio($necro);
        $morte   = $obj->DataMorte();
        $eta     = $obj->Eta();
        $dettagli = [];

        if ($morte)        { $dettagli[] = 'Morto/a il ' . PortaleFunebreNecrologi_Render::DataBreve($morte); }
        if ($eta !== null) { $dettagli[] = $eta . ' anni'; }

        $azioni = [
            'apri' => sprintf('<a href="%s">Apri</a>', esc_url($this->UrlDettaglio($necro))),
            'sito' => sprintf('<a href="%s" target="_blank" rel="noopener">Vedi sul sito</a>', esc_url($obj->Url())),
        ];

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>%s%s',
            esc_url($this->UrlDettaglio($necro)),
            esc_html($obj->Nome()),
            $dettagli ? '<div class="pfn-nt-sotto">' . esc_html(implode(' · ', $dettagli)) . '</div>' : '',
            $this->row_actions($azioni)
        );
    }

    protected function column_funerale($necro) { return self::Cerimonia($necro, 'funerale'); }

    protected function column_rosario($necro) { return self::Cerimonia($necro, 'rosario'); }

    protected function column_feretro($necro) { return self::Cerimonia($necro, 'chiusura_feretro'); }

    protected function column_cordogli($necro) {
        $numero = self::NumeroCordogli($necro);
        if (!$numero) { return '<span class="pfn-nt-vuoto" aria-label="Nessun cordoglio">&mdash;</span>'; }
        return sprintf('<a class="pfn-nt-badge" href="%s#pfn-cordogli">%d</a>', esc_url($this->UrlDettaglio($necro)), $numero);
    }

    protected function column_seo($necro) {

        $indicizzabile  = PortaleFunebreNecrologi_SeoNecrologio::IsIndicizzabile($necro->slug);
        $personalizzato = PortaleFunebreNecrologi_SeoNecrologio::IsPersonalizzato($necro->slug);

        $html = $indicizzabile
            ? '<span class="pfn-nt-seo is-ok"><span class="dashicons dashicons-visibility" aria-hidden="true"></span>Indicizzato</span>'
            : '<span class="pfn-nt-seo is-off"><span class="dashicons dashicons-hidden" aria-hidden="true"></span>Escluso</span>';

        if ($personalizzato) { $html .= '<div class="pfn-nt-sotto">Personalizzato</div>'; }

        return $html;
    }

    protected function column_data($necro) {
        $data = !empty($necro->pubblicato_il) ? strtotime($necro->pubblicato_il) : false;
        if (!$data) { return '<span class="pfn-nt-vuoto">&mdash;</span>'; }
        return sprintf('Pubblicato<br><span title="%s">%s</span>', esc_attr(date_i18n('j F Y, H:i', $data)), esc_html(date_i18n('d/m/Y', $data)));
    }

    protected function column_default($necro, $colonna) {
        return '';
    }

    /* ------------------------------------------------------------------
     * Utilita'
     * ---------------------------------------------------------------- */

    function UrlDettaglio($necro) {
        return add_query_arg('necrologio', rawurlencode($necro->slug), $this->url_pagina);
    }

    static function NumeroCordogli($necro) {
        $numero = 0;
        foreach (['email', 'pdf', 'whatsapp'] as $tipo) {
            if (isset($necro->cordogli->$tipo) && is_array($necro->cordogli->$tipo)) { $numero += count($necro->cordogli->$tipo); }
        }
        return $numero;
    }

    /** Luogo e data della cerimonia su due righe; trattino se non e' prevista. */
    static function Cerimonia($necro, $tipo) {

        $cerimonia = (new PortaleFunebreNecrologi_Necrologio($necro))->Cerimonia($tipo);
        $giorno    = PortaleFunebreNecrologi_Necrologio::Data($cerimonia['data']);

        if (!$cerimonia['luogo'] && !$giorno) { return '<span class="pfn-nt-vuoto" aria-label="Non prevista">&mdash;</span>'; }

        $quando = $giorno ? PortaleFunebreNecrologi_Render::DataBreve($giorno) : '';
        if ($cerimonia['ora_da']) { $quando .= ($quando ? ', ore ' : 'Ore ') . $cerimonia['ora_da']; }

        return ($cerimonia['luogo'] ? esc_html($cerimonia['luogo']) : '')
            . ($quando ? '<div class="pfn-nt-sotto">' . esc_html($quando) . '</div>' : '');
    }

    private function Vista() {
        $vista = isset($_REQUEST['vista']) ? sanitize_key(wp_unslash($_REQUEST['vista'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return in_array($vista, ['cordogli', 'esclusi'], true) ? $vista : 'tutti';
    }

    private function Ordina(array $elementi) {

        $orderby = isset($_REQUEST['orderby']) ? sanitize_key(wp_unslash($_REQUEST['orderby'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order   = (isset($_REQUEST['order']) && strtolower(sanitize_key(wp_unslash($_REQUEST['order']))) === 'asc') ? 1 : -1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        // Senza ordinamento scelto: l'ordine del portale (i piu' recenti per primi).
        if (!in_array($orderby, ['nome', 'funerale', 'cordogli', 'data'], true)) { return $elementi; }

        $valore = static function ($necro) use ($orderby) {
            switch ($orderby) {
                case 'nome':     return remove_accents(strtolower($necro->nome_defunto));
                case 'funerale': return isset($necro->funerale->data) ? (string) $necro->funerale->data . ' ' . (isset($necro->funerale->ora_da) ? $necro->funerale->ora_da : '') : '';
                case 'cordogli': return self::NumeroCordogli($necro);
                case 'data':     return isset($necro->pubblicato_il) ? (string) $necro->pubblicato_il : '';
            }
            return '';
        };

        usort($elementi, static function ($a, $b) use ($valore, $order) {
            return $order * ($valore($a) <=> $valore($b));
        });

        return $elementi;
    }

}
