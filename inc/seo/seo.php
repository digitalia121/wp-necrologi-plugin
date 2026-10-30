<?php

if (!defined('ABSPATH')) { exit; }

/**
 * SEO della pagina del singolo necrologio.
 *
 * - Collega l'URL /{slug_singolo}/{necrologio}/ alla pagina WordPress che
 *   contiene lo shortcode, usando la query standard di WordPress.
 * - Legge il necrologio prima del <head>, cosi' titolo, description,
 *   canonical e Open Graph sono quelli del defunto.
 * - Risponde 404 per i necrologi inesistenti e 503 (temporaneo) se il
 *   portale non e' raggiungibile, cosi' un disservizio non fa uscire le
 *   pagine da Google.
 * - Con Yoast i meta passano dai suoi filtri; senza plugin SEO li stampa il
 *   plugin stesso.
 */
class PortaleFunebreNecrologi_Seo {

    const TITOLO_PREDEFINITO = '{nome} - Necrologio | {sito}';

    /** Segnaposto utilizzabili nei modelli di titolo e description. */
    const SEGNAPOSTO = [
        '{nome}'           => 'Nome del defunto',
        '{sito}'           => 'Nome del sito',
        '{agenzia}'        => 'Agenzia funebre',
        '{data_morte}'     => 'Data del decesso',
        '{data_nascita}'   => 'Data di nascita',
        '{eta}'            => 'Età',
        '{luogo_funerale}' => 'Luogo del funerale',
        '{data_funerale}'  => 'Data del funerale',
        '{testo}'          => 'Testo del necrologio',
    ];

    const LUNGHEZZA_DESCRIZIONE = 155;

    /** @var PortaleFunebreNecrologi_Necrologio|null */
    private static $necrologio = null;

    /** '' (non e' un necrologio), 'ok', 'assente', 'errore'. */
    private static $stato = '';

    static function Init() {

        add_filter('request', [__CLASS__, 'FiltraRichiesta']);
        add_action('wp', [__CLASS__, 'Risolvi'], 1);
        add_filter('redirect_canonical', [__CLASS__, 'RedirectCanonical']);
        add_action('template_redirect', [__CLASS__, 'NormalizzaUrl'], 0);
        add_filter('template_include', [__CLASS__, 'Template'], 20);
        add_filter('pre_get_shortlink', [__CLASS__, 'Shortlink']);
        add_action('template_redirect', [__CLASS__, 'PulisciHead']);

        // Le integrazioni dipendono da quali plugin sono attivi: si decide dopo il loro caricamento.
        add_action('plugins_loaded', [__CLASS__, 'InitIntegrazioni'], 20);
    }

    static function InitIntegrazioni() {

        if (PortaleFunebreNecrologi_Seo_Yoast::IsAttivo()) {
            PortaleFunebreNecrologi_Seo_Yoast::Init();
        } else {
            add_filter('pre_get_document_title', [__CLASS__, 'TitoloDocumento'], 20);
            add_filter('wp_title', [__CLASS__, 'TitoloLegacy'], 20, 3);
            add_filter('get_canonical_url', [__CLASS__, 'CanonicalCore'], 10, 2);
            add_filter('wp_robots', [__CLASS__, 'RobotsCore']);
            add_action('wp_head', [__CLASS__, 'StampaMeta'], 1);
        }

        PortaleFunebreNecrologi_Sitemap::Init();
    }

    /** Regola di rewrite del singolo necrologio (usata anche al salvataggio delle impostazioni). */
    static function AggiungiRegola($slug_singolo) {
        add_rewrite_rule('^' . preg_quote($slug_singolo, '#') . '/([^/]+)/?$', 'index.php?necro_slug=$matches[1]', 'top');
    }

    /* ------------------------------------------------------------------
     * Richiesta
     * ---------------------------------------------------------------- */

    /** Fa caricare a WordPress la pagina che ospita lo shortcode del necrologio. */
    static function FiltraRichiesta($vars) {

        if (empty($vars['necro_slug'])) { return $vars; }

        $pagina = self::PaginaContenitore();

        if ($pagina) { $vars['page_id'] = $pagina->ID; }

        return $vars;
    }

    /** Legge il necrologio richiesto e imposta il codice di risposta. */
    static function Risolvi() {

        $slug = sanitize_title((string) get_query_var('necro_slug'));

        if ($slug === '') { return; }

        if (!self::PaginaContenitore()) {
            self::Imposta404();
            return;
        }

        $dati = PortaleFunebreNecrologi_Repository::GetSingolo($slug);

        if (is_wp_error($dati)) {
            // Portale non raggiungibile e nessuna copia di riserva: errore temporaneo.
            // La pagina viene comunque mostrata e il JavaScript riprova a caricare i dati.
            self::$stato = 'errore';
            status_header(503);
            header('Retry-After: 600');
            nocache_headers();
            return;
        }

        if ($dati === false) {
            self::$stato = 'assente';
            self::Imposta404();
            return;
        }

        self::$stato      = 'ok';
        self::$necrologio = new PortaleFunebreNecrologi_Necrologio($dati);
    }

    /** Il necrologio ha un solo indirizzo: la variante con/senza barra finale viene reindirizzata. */
    static function NormalizzaUrl() {

        if (self::$stato !== 'ok' || !isset($_SERVER['REQUEST_URI'])) { return; }

        $richiesta = (string) wp_parse_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH);
        $canonico  = (string) wp_parse_url(self::$necrologio->Url(), PHP_URL_PATH);

        if ($richiesta === $canonico || untrailingslashit($richiesta) !== untrailingslashit($canonico)) { return; }

        $query = isset($_SERVER['QUERY_STRING']) ? sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING'])) : '';

        wp_safe_redirect(self::$necrologio->Url() . ($query !== '' ? '?' . $query : ''), 301, 'Portale Funebre Necrologi');
        exit;
    }

    /** WordPress non deve reindirizzare il necrologio verso la pagina che lo ospita. */
    static function RedirectCanonical($redirect_url) {
        return get_query_var('necro_slug') ? false : $redirect_url;
    }

    /** Stesso template usato storicamente dal plugin (quello del singolo, altrimenti quello della pagina). */
    static function Template($template) {

        if (!in_array(self::$stato, ['ok', 'errore'], true) || is_404()) { return $template; }

        $singolo = get_single_template();
        if (!$singolo) { $singolo = get_page_template(); }

        return $singolo ? $singolo : $template;
    }

    /** Lo shortlink (?p=ID) punterebbe alla pagina contenitore: sul necrologio non viene stampato. */
    static function Shortlink($shortlink) {
        return self::IsNecrologio() ? '' : $shortlink;
    }

    /** I link oEmbed della pagina contenitore non descrivono il necrologio. */
    static function PulisciHead() {
        if (!self::IsNecrologio()) { return; }
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
    }

    private static function Imposta404() {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }

    /* ------------------------------------------------------------------
     * Stato corrente
     * ---------------------------------------------------------------- */

    /** La richiesta corrente e' un necrologio esistente. */
    static function IsNecrologio() {
        return self::$stato === 'ok' && self::$necrologio !== null;
    }

    /** @return PortaleFunebreNecrologi_Necrologio|null */
    static function Corrente() {
        return self::$necrologio;
    }

    /** Il portale non ha risposto per il necrologio richiesto. */
    static function IsErrore() {
        return self::$stato === 'errore';
    }

    /** La pagina WordPress che ospita lo shortcode del singolo necrologio. */
    static function PaginaContenitore() {
        static $pagina = false;
        if ($pagina === false) {
            $trovata = get_page_by_path(PortaleFunebreNecrologi_Necrologio::SlugPagina(), OBJECT, 'page');
            $pagina  = ($trovata instanceof WP_Post) ? $trovata : null;
        }
        return $pagina;
    }

    /**
     * La pagina contenitore aperta senza un necrologio: e' vuota, quindi non
     * va indicizzata ne' messa in sitemap. Se pero' contiene anche la lista o
     * lo slider (pagina usata per entrambe le cose) resta indicizzabile.
     */
    static function IsContenitoreVuoto($post_id = null) {

        $pagina = self::PaginaContenitore();

        if (!$pagina) { return false; }

        if ($post_id === null) {
            if (get_query_var('necro_slug') || !is_page($pagina->ID)) { return false; }
        } elseif ((int) $post_id !== (int) $pagina->ID) {
            return false;
        }

        return !has_shortcode($pagina->post_content, 'gestione_necrologi_lista')
            && !has_shortcode($pagina->post_content, 'gestione_necrologi_slider');
    }

    /* ------------------------------------------------------------------
     * Valori dei meta
     * ---------------------------------------------------------------- */

    /** Impostazioni SEO del necrologio corrente (vuote se non personalizzate). */
    static function Personalizzazioni() {
        return self::IsNecrologio() ? PortaleFunebreNecrologi_SeoNecrologio::Get(self::$necrologio->Slug()) : PortaleFunebreNecrologi_SeoNecrologio::CAMPI;
    }

    /** Il necrologio corrente puo' comparire nei risultati di ricerca. */
    static function IsIndicizzabile() {
        return self::IsNecrologio() && self::Personalizzazioni()['noindex'] !== '1';
    }

    /**
     * Modello del titolo da usare: quello del necrologio, altrimenti quello
     * generale della tab SEO, altrimenti il predefinito.
     */
    static function ModelloTitolo($personalizzato = '') {
        if (trim($personalizzato) !== '') { return $personalizzato; }
        $generale = self::Impostazione('seo_titolo');
        return trim($generale) !== '' ? $generale : self::TITOLO_PREDEFINITO;
    }

    static function Titolo() {
        return self::IsNecrologio() ? self::TitoloPer(self::$necrologio, self::Personalizzazioni()['titolo']) : '';
    }

    static function Descrizione() {
        return self::IsNecrologio() ? self::DescrizionePer(self::$necrologio, self::Personalizzazioni()['descrizione']) : '';
    }

    /**
     * Titolo di un necrologio: usato dalla pagina pubblica e dall'anteprima
     * nel backend, cosi' i due coincidono sempre.
     */
    static function TitoloPer(PortaleFunebreNecrologi_Necrologio $necro, $personalizzato = '') {

        $titolo = self::PulisciSeparatori(self::Sostituisci(self::ModelloTitolo($personalizzato), $necro));

        return (string) apply_filters('portale_funebre_necrologi_seo_titolo', $titolo !== '' ? $titolo : $necro->Nome(), $necro);
    }

    /** Description di un necrologio (pagina pubblica e anteprima nel backend). */
    static function DescrizionePer(PortaleFunebreNecrologi_Necrologio $necro, $personalizzato = '') {

        $modello = $personalizzato;
        if (trim($modello) === '') { $modello = self::Impostazione('seo_descrizione'); }

        if (trim($modello) !== '') {
            $descrizione = self::PulisciSeparatori(self::Sostituisci($modello, $necro));
        } else {
            $descrizione = $necro->TestoSemplice();
        }

        if ($descrizione === '') {
            $funerale    = $necro->Cerimonia('funerale');
            $giorno      = PortaleFunebreNecrologi_Necrologio::Data($funerale['data']);
            $descrizione = 'Necrologio di ' . $necro->Nome() . '.';
            if ($giorno && $funerale['luogo']) {
                $descrizione .= ' Il funerale si terrà il ' . self::DataEstesa($giorno) . ' presso ' . $funerale['luogo'] . '.';
            }
        }

        return (string) apply_filters('portale_funebre_necrologi_seo_descrizione', self::Accorcia($descrizione, self::LUNGHEZZA_DESCRIZIONE), $necro);
    }

    static function Canonical() {
        if (!self::IsNecrologio()) { return ''; }
        $personalizzato = self::Personalizzazioni()['canonical'];
        return $personalizzato !== '' ? $personalizzato : self::$necrologio->Url();
    }

    /** Titolo per Facebook, WhatsApp e X: quello social del necrologio, altrimenti il titolo SEO. */
    static function TitoloSocial() {
        if (!self::IsNecrologio()) { return ''; }
        $modello = self::Personalizzazioni()['social_titolo'];
        return $modello !== '' ? self::PulisciSeparatori(self::Sostituisci($modello, self::$necrologio)) : self::Titolo();
    }

    static function DescrizioneSocial() {
        if (!self::IsNecrologio()) { return ''; }
        $modello = self::Personalizzazioni()['social_descrizione'];
        return $modello !== '' ? self::Accorcia(self::PulisciSeparatori(self::Sostituisci($modello, self::$necrologio)), 200) : self::Descrizione();
    }

    static function Immagine() {
        return self::IsNecrologio() ? self::$necrologio->Immagine() : '';
    }

    /**
     * Immagine per Open Graph e X: la stessa copia ridotta usata dal vecchio
     * sharer, letta una sola volta per richiesta.
     *
     * @return array{url:string,width:int,height:int}|null
     */
    static function ImmagineSocial() {
        static $immagine = false;
        if ($immagine === false) {
            $immagine = null;
            if (self::IsNecrologio()) {
                $scelta   = self::ImmagineDaMedia(self::Personalizzazioni()['social_immagine']);
                $immagine = $scelta ? $scelta : self::$necrologio->ImmagineSocial();
            }
        }
        return $immagine;
    }

    /**
     * Immagine della libreria media scelta per i social.
     *
     * @return array{url:string,width:int,height:int}|null
     */
    static function ImmagineDaMedia($id) {
        $src = $id ? wp_get_attachment_image_src((int) $id, 'large') : false;
        return $src ? ['url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2]] : null;
    }

    /** Nome del sito come appare nei risultati e nell'Open Graph. */
    static function NomeSito() {
        return html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
    }

    /* ------------------------------------------------------------------
     * Meta senza plugin SEO
     * ---------------------------------------------------------------- */

    static function TitoloDocumento($titolo) {
        return self::IsNecrologio() ? self::Titolo() : $titolo;
    }

    /**
     * Temi classici con wp_title(): il tema aggiunge da se' il nome del sito,
     * quindi qui si restituisce solo la parte della pagina, come fa WordPress.
     */
    static function TitoloLegacy($titolo, $sep = '|', $posizione = '') {
        if (!self::IsNecrologio()) { return $titolo; }
        $pagina = self::$necrologio->Nome() . ' - Necrologio';
        return ($posizione === 'right') ? $pagina . " $sep " : " $sep " . $pagina;
    }

    static function CanonicalCore($url, $post = null) {
        return self::IsNecrologio() ? self::Canonical() : $url;
    }

    static function RobotsCore(array $robots) {
        if (self::IsContenitoreVuoto() || (self::IsNecrologio() && !self::IsIndicizzabile())) {
            $robots['noindex'] = true;
            unset($robots['index'], $robots['max-image-preview']);
            if (empty($robots['nofollow'])) { $robots['follow'] = true; }
        }
        return $robots;
    }

    static function StampaMeta() {

        if (!self::IsNecrologio()) { return; }

        $titolo      = self::Titolo();
        $descrizione = self::Descrizione();
        $url         = self::Canonical();
        $immagine    = self::ImmagineSocial();

        echo "\n<!-- Portale Funebre Necrologi: SEO -->\n";
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($descrizione));
        printf('<meta property="og:locale" content="%s" />' . "\n", esc_attr(get_locale()));
        echo '<meta property="og:type" content="article" />' . "\n";
        printf('<meta property="og:title" content="%s" />' . "\n", esc_attr(self::TitoloSocial()));
        printf('<meta property="og:description" content="%s" />' . "\n", esc_attr(self::DescrizioneSocial()));
        printf('<meta property="og:url" content="%s" />' . "\n", esc_url($url));
        printf('<meta property="og:site_name" content="%s" />' . "\n", esc_attr(self::NomeSito()));
        if ($immagine) {
            printf('<meta property="og:image" content="%s" />' . "\n", esc_url($immagine['url']));
            if (is_ssl()) { printf('<meta property="og:image:secure_url" content="%s" />' . "\n", esc_url($immagine['url'])); }
            printf('<meta property="og:image:width" content="%d" />' . "\n", (int) $immagine['width']);
            printf('<meta property="og:image:height" content="%d" />' . "\n", (int) $immagine['height']);
            printf('<meta property="og:image:alt" content="%s" />' . "\n", esc_attr(PortaleFunebreNecrologi_Render::AltFoto(self::$necrologio)));
        }
        echo '<meta name="twitter:card" content="summary" />' . "\n";
        printf('<meta name="twitter:title" content="%s" />' . "\n", esc_attr(self::TitoloSocial()));
        printf('<meta name="twitter:description" content="%s" />' . "\n", esc_attr(self::DescrizioneSocial()));
        if ($immagine) {
            printf('<meta name="twitter:image" content="%s" />' . "\n", esc_url($immagine['url']));
        }

        if (PortaleFunebreNecrologi_Schema::IsAttivo()) {
            $grafo = PortaleFunebreNecrologi_Schema::GrafoCompleto(self::$necrologio);
            echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@graph' => $grafo], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }

        echo "<!-- / Portale Funebre Necrologi: SEO -->\n";
    }

    /* ------------------------------------------------------------------
     * Utilita'
     * ---------------------------------------------------------------- */

    /** Sostituisce i segnaposto di un modello con i dati del necrologio. */
    static function Sostituisci($modello, PortaleFunebreNecrologi_Necrologio $necro) {

        $funerale = $necro->Cerimonia('funerale');
        $morte    = $necro->DataMorte();
        $nascita  = $necro->DataNascita();
        $giorno   = PortaleFunebreNecrologi_Necrologio::Data($funerale['data']);
        $eta      = $necro->Eta();

        $valori = [
            '{nome}'           => $necro->Nome(),
            '{sito}'           => self::NomeSito(),
            '{agenzia}'        => $necro->Agenzia(),
            '{data_morte}'     => $morte ? self::DataEstesa($morte) : '',
            '{data_nascita}'   => $nascita ? self::DataEstesa($nascita) : '',
            '{eta}'            => $eta !== null ? $eta . ' anni' : '',
            '{luogo_funerale}' => $funerale['luogo'],
            '{data_funerale}'  => $giorno ? self::DataEstesa($giorno) : '',
            '{testo}'          => $necro->TestoSemplice(),
        ];

        return strtr($modello, $valori);
    }

    /** Data in forma estesa nella lingua del sito (es. 14 maggio 2025). */
    static function DataEstesa(DateTimeInterface $data) {
        return wp_date('j F Y', $data->getTimestamp(), new DateTimeZone('UTC'));
    }

    /** Toglie i separatori rimasti orfani quando un segnaposto e' vuoto. */
    private static function PulisciSeparatori($testo) {
        $testo = preg_replace('/\s+/u', ' ', (string) $testo);
        $testo = preg_replace('/\s*([\-\|–—·,])(?:\s*[\-\|–—·,])+/u', ' $1', $testo);
        $testo = preg_replace('/\s+([,])/u', '$1', $testo);
        return (string) preg_replace('/^[\s\-\|–—·,]+|[\s\-\|–—·,]+$/u', '', $testo);
    }

    /**
     * Accorcia un testo alla parola intera piu' vicina. Usa solo le funzioni
     * mb_* che WordPress garantisce anche senza l'estensione mbstring.
     */
    private static function Accorcia($testo, $max) {
        $testo = trim((string) $testo);
        if (mb_strlen($testo) <= $max) { return $testo; }
        $taglio = mb_substr($testo, 0, $max);
        $spazio = strrpos($taglio, ' ');
        if ($spazio !== false && mb_strlen(substr($taglio, 0, $spazio)) > $max * 0.6) { $taglio = substr($taglio, 0, $spazio); }
        return preg_replace('/[\s,.;:\-–]+$/u', '', $taglio) . '…';
    }

    private static function Impostazione($chiave) {
        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        return ($impostazioni && isset($impostazioni[$chiave])) ? (string) $impostazioni[$chiave] : '';
    }

}
