<?php /*
Plugin Name: Portale Funebre Necrologi
Plugin URI: http://www.portalefunebre.com
Description: Gestione dei necrologi sul tuo sito.
Version: 1.1.0
Author: Digitalia Srl
Author URI: https://digitalia.srl
License: GPL2
*/

if (!defined('ABSPATH')) { exit; }

define('PORTALE_FUNEBRE_NECROLOGI_API_INCLUDED', true);

require_once('plugin_core/plugin_load.php');

if (!class_exists('PortaleFunebreNecrologi_API')) {
    require_once plugin_dir_path(__FILE__) . 'inc/funebreapi/PortaleFunebreNecrologi_API.php';
}

require_once plugin_dir_path(__FILE__) . 'inc/form_cordoglio.php';
require_once plugin_dir_path(__FILE__) . 'inc/necrologi_repository.php';
require_once plugin_dir_path(__FILE__) . 'inc/necrologio.php';
require_once plugin_dir_path(__FILE__) . 'inc/render.php';
require_once plugin_dir_path(__FILE__) . 'inc/seo/seo.php';
require_once plugin_dir_path(__FILE__) . 'inc/seo/yoast.php';
require_once plugin_dir_path(__FILE__) . 'inc/seo/schema.php';
require_once plugin_dir_path(__FILE__) . 'inc/seo/sitemap.php';
require_once plugin_dir_path(__FILE__) . 'inc/seo/monitor.php';
require_once plugin_dir_path(__FILE__) . 'inc/seo/necrologio_seo.php';

require_once plugin_dir_path(__FILE__) . 'inc/admin/cache_necrologio.php';

// Azioni della pagina del singolo necrologio nel backend: salvataggio SEO e aggiornamento della cache.
add_action('admin_post_' . PortaleFunebreNecrologi_SeoNecrologio::AZIONE, ['PortaleFunebreNecrologi_SeoNecrologio', 'GestisciSalvataggio']);
add_action('admin_post_' . PortaleFunebreNecrologi_CacheNecrologio::AZIONE, ['PortaleFunebreNecrologi_CacheNecrologio', 'Gestisci']);

register_deactivation_hook(__FILE__, ['PortaleFunebreNecrologi_Monitor', 'Disattiva']);

// Editor del form di cordoglio: solo nella pagina Impostazioni del plugin.
add_action('admin_enqueue_scripts', function ($hook) {

    if (strpos($hook, 'portale-funebre-necrologi-impostazioni') === false) { return; }

    $dir = plugin_dir_path(__FILE__) . 'scripts/';
    $url = plugin_dir_url(__FILE__) . 'scripts/';

    wp_enqueue_style('dashicons');
    wp_enqueue_style('portale-funebre-necrologi-form-editor', $url . 'form-editor.css', [], filemtime($dir . 'form-editor.css'));
    wp_enqueue_script('portale-funebre-necrologi-form-editor', $url . 'form-editor.js', ['jquery', 'jquery-ui-sortable'], filemtime($dir . 'form-editor.js'), true);
});

// Pagina "Necrologi": tabella e pagina del singolo necrologio.
add_action('admin_enqueue_scripts', function ($hook) {

    if (strpos($hook, 'portale-funebre-necrologi-necrologi') === false) { return; }

    $dir = plugin_dir_path(__FILE__) . 'scripts/';
    $url = plugin_dir_url(__FILE__) . 'scripts/';

    wp_enqueue_style('portale-funebre-necrologi-admin-necrologi', $url . 'necrologi-admin.css', [], filemtime($dir . 'necrologi-admin.css'));
    wp_enqueue_script('portale-funebre-necrologi-admin-necrologi', $url . 'necrologi-admin.js', ['jquery'], filemtime($dir . 'necrologi-admin.js'), true);
});

// I campi "testo lungo" del form mantengono gli a capo quando arrivano via ajax.
add_filter('portale_funebre_necrologi_campi_multilinea', function ($chiavi) {
    $form = PortaleFunebreNecrologi_FormCordoglio::GetConfig(PortaleFunebreNecrologi::GetImpostazioni());
    return array_merge($chiavi, PortaleFunebreNecrologi_FormCordoglio::ChiaviMultilinea($form));
});

add_action( 'portale_funebre_necrologi_head_left', function () {
    $link = PortaleFunebreNecrologi_API::GetLoginPath();
    echo '<p class="titolo-portale">Accedi al portale</p><a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer" class="button">Accedi ora</a>';

});

add_action('template_redirect', function () {

    if (isset($_SERVER['REQUEST_URI'])) {

        $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        $url_string  = (trim(wp_parse_url($request_uri, PHP_URL_PATH), '/'));

        $url_routes = explode('/',$url_string);
        // Controlla se la route è "portalefunebre"
        if (isset($url_routes[1]) &&  $url_routes[1] === 'ofadmin') {
            // Reindirizza alla URL desiderata
            wp_safe_redirect('https://www.portalefunebre.com/login');
            exit;
        }

    }

}, 1);


class PortaleFunebreNecrologi extends PortaleFunebreNecrologi\PluginBase {
    static $inInst=null;
    private static $IMPOSTAZIONI=null;
    function __construct() {
        parent::__construct('Portale Funebre Necrologi', __FILE__, 'dashicons-admin-home', ['gestione-necrologi']);

        self::$IMPOSTAZIONI = $this->get_opzione('impostazioni');

        $this->create_shortcode('gestione_necrologi_lista');
        $this->create_shortcode('gestione_necrologi_singolo');
        $this->create_shortcode('gestione_necrologi_slider');

        if (!isset(self::$IMPOSTAZIONI['api_key']) || !self::$IMPOSTAZIONI['api_key']|| !self::$IMPOSTAZIONI['client_id']) { return; }
        
        PortaleFunebreNecrologi_API::Config([
            'END_POINT' => 'https://portalefunebre.com',
            'CLIENT_ID' => self::$IMPOSTAZIONI['client_id'],
            'API_KEY'   => self::$IMPOSTAZIONI['api_key'],
        ]);

        self::$inInst = $this;

        $slug_singolo = (isset(self::$IMPOSTAZIONI['slug_singolo'])) ? self::$IMPOSTAZIONI['slug_singolo'] : 'necrologio';

        add_action('init', function() use ($slug_singolo) {
            add_rewrite_rule(
                '^pf-share/([^/]+)/?$',
                'index.php?pf_share_slug=$matches[1]',
                'top'
            );
            PortaleFunebreNecrologi_Seo::AggiungiRegola($slug_singolo);
        });
        add_filter('query_vars', function($vars) {
            $vars[] = 'pf_share_slug';
            $vars[] = 'necro_slug';
            return $vars;
        });

        if (PortaleFunebreNecrologi::IsConfigurato()) {

            // Pagina del singolo necrologio: routing, codici di risposta, meta SEO e sitemap.
            PortaleFunebreNecrologi_Seo::Init();
            PortaleFunebreNecrologi_Monitor::Init();

            // Vecchio indirizzo di condivisione (/pf-share/{slug}): ora la pagina del
            // necrologio ha i propri meta Open Graph, quindi chi arriva da un link gia'
            // condiviso viene reindirizzato in modo permanente al necrologio (i social
            // seguono il redirect e leggono i meta della pagina vera).
            add_action('template_redirect', function () {

                $slug = sanitize_title((string) get_query_var('pf_share_slug'));
                if (!$slug) return;

                wp_safe_redirect(PortaleFunebreNecrologi_Necrologio::UrlDaSlug($slug), 301, 'Portale Funebre Necrologi');
                exit;

            }, 1);
        }
            

    }

    static function GetImpostazioni() {
        return self::$IMPOSTAZIONI;
    }

    /**
     * Larghezza (in px) della copia usata come og:image. Facebook mostra
     * l'anteprima grande, con l'immagine orizzontale in alto, solo quando
     * l'immagine e' almeno 600x315: restando sotto quella soglia usa la
     * scheda compatta, con la foto (verticale) sulla sinistra del testo.
     */
    const SHARE_IMG_WIDTH = 300;

    /**
     * Crea (una sola volta) una copia ridotta della foto del necrologio dentro
     * la cartella uploads e ne restituisce url e dimensioni reali. Se qualcosa
     * non va, si ripiega sull'immagine originale del portale.
     *
     * @param string $img_url URL dell'immagine originale.
     * @return array{url:string,width:int,height:int}
     */
    static function GetImmagineShare($img_url) {

        $originale = ['url' => $img_url, 'width' => 640, 'height' => 780];

        $file = self::FileImmagineShare($img_url);

        if (!$file) { return $originale; }

        $cartella = $file['cartella'];
        $base_url = $file['base_url'];
        $percorso = $file['percorso'];

        if (!file_exists($percorso)) {

            if (!wp_mkdir_p($cartella)) { return $originale; }

            require_once ABSPATH.'wp-admin/includes/file.php';

            // Puo' avvenire durante la visita di un necrologio: timeout breve.
            $tmp = download_url($img_url, 10);

            if (is_wp_error($tmp)) { return $originale; }

            $editor = wp_get_image_editor($tmp);

            if (is_wp_error($editor)) {
                wp_delete_file($tmp);
                return $originale;
            }

            // Solo la larghezza: l'altezza segue le proporzioni, cosi' il
            // formato verticale resta intatto. Se l'immagine e' gia' piu'
            // piccola resize() fallisce e salviamo la copia com'e'.
            $editor->resize(self::SHARE_IMG_WIDTH, null);

            $salvato = $editor->save($percorso);

            wp_delete_file($tmp);

            if (is_wp_error($salvato) || empty($salvato['path'])) { return $originale; }

            $percorso = $salvato['path'];
        }

        $dimensioni = wp_getimagesize($percorso);

        if (!$dimensioni) { return $originale; }

        return [
            'url'    => $base_url.'/'.basename($percorso),
            'width'  => (int) $dimensioni[0],
            'height' => (int) $dimensioni[1],
        ];
    }

    /**
     * Dove si trova (o si troverebbe) la copia ridotta di una foto, senza crearla.
     *
     * @return array{cartella:string,base_url:string,percorso:string,url:string}|null
     */
    static function FileImmagineShare($img_url) {

        if (!$img_url) { return null; }

        $uploads = wp_upload_dir(null, false);

        if (!empty($uploads['error'])) { return null; }

        $cartella   = trailingslashit($uploads['basedir']).'pf-share-og';
        $base_url   = trailingslashit($uploads['baseurl']).'pf-share-og';
        $estensione = strtolower(pathinfo((string) wp_parse_url($img_url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (!in_array($estensione, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) { $estensione = 'jpg'; }

        // La chiave e' l'URL originale: se la foto cambia, cambia anche il file.
        $nome = md5($img_url).'-'.self::SHARE_IMG_WIDTH.'.'.$estensione;

        return [
            'cartella' => $cartella,
            'base_url' => $base_url,
            'percorso' => $cartella.'/'.$nome,
            'url'      => $base_url.'/'.$nome,
        ];
    }

    static function IsConfigurato() {
        $set = self::GetImpostazioni();
        if (!$set) { return false; }
        if (!isset($set['api_key']) || !isset($set['client_id'])) { return false; } 
        return $set['api_key'] && $set['client_id'];
    }

    function create_menu_pages() {

        if (self::IsConfigurato()) {
            return [
                'Necrologi',
                'Cordogli',
                'Impostazioni'
            ];
        }

        return ['Impostazioni'];

    }

}

// Letture via ajax: servono solo come ripiego quando il markup non e' generato lato server.
PortaleFunebreNecrologi::add_ajax_call('get_necrologio_singolo', function ($var) {
    if (!PortaleFunebreNecrologi::$inInst) { return; }
    $slug = isset($var['slug']) ? sanitize_title($var['slug']) : '';
    $dati = PortaleFunebreNecrologi_Repository::GetSingolo($slug);
    return is_object($dati) ? $dati : null;
});
PortaleFunebreNecrologi::add_ajax_call('get_lista_necrologi', function () {
    if (!PortaleFunebreNecrologi::$inInst) { return; }
    $lista = PortaleFunebreNecrologi_Repository::GetLista();
    return is_wp_error($lista) ? [] : $lista;
});
PortaleFunebreNecrologi::add_ajax_call('get_anteprima_necrologi', function () {
    if (!PortaleFunebreNecrologi::$inInst) { return; }
    // Lo slider ha sempre ricevuto tutti i necrologi: stessa lista (e stessa cache) della lista.
    $lista = PortaleFunebreNecrologi_Repository::GetLista();
    return is_wp_error($lista) ? [] : $lista;
});
PortaleFunebreNecrologi::add_ajax_call('invia_cordoglio_api', function ($var) {
    if (!PortaleFunebreNecrologi::$inInst) { return; }

    $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();

    // Raccolta dei cordogli disattivata nelle impostazioni: nessun invio al portale.
    if (PortaleFunebreNecrologi_FormCordoglio::Modalita($impostazioni) === 'nessuna') { return ['status' => 'error']; }

    $form         = PortaleFunebreNecrologi_FormCordoglio::GetConfig($impostazioni);
    $privacy_url  = isset($impostazioni['link_gdpr']) ? $impostazioni['link_gdpr'] : '';

    $errore = PortaleFunebreNecrologi_FormCordoglio::Valida($form, $var, $privacy_url);
    if ($errore) { return ['status' => 'error', 'validazione' => $errore]; }

    $api = new PortaleFunebreNecrologi_API();
    return $api->InviaCordoglio(PortaleFunebreNecrologi_FormCordoglio::PreparaInvio($form, $var, $privacy_url));
});

new PortaleFunebreNecrologi;


$necro_settings_array = PortaleFunebreNecrologi::GetImpostazioni()->toArray();
unset($necro_settings_array['api_key']);
unset($necro_settings_array['client_id']);
unset($necro_settings_array['form_cordoglio']); // il form viene renderizzato lato server

PortaleFunebreNecrologi\PluginBase::add_js_variables([
    'necro_settings' => $necro_settings_array,
    'necro_img_url'  => PortaleFunebreNecrologi_API::GetImgUrl(''),
    'necrologio_url' => get_site_url().'/'.PortaleFunebreNecrologi::GetImpostazioni()['slug_singolo'],
]);

// URL canonico del necrologio (%slug% viene sostituito) per i link generati in JavaScript.
// Calcolato qui perche' i permalink sono disponibili solo dopo l'avvio di WordPress.
add_action('wp_enqueue_scripts', function () {
    PortaleFunebreNecrologi\PluginBase::add_js_variables([
        'necrologio_url_modello' => PortaleFunebreNecrologi_Necrologio::ModelloUrl(),
    ]);
}, 5);

function portale_funebre_necrologi_num_cols() {
    $num_cols = PortaleFunebreNecrologi::GetImpostazioni()['num_cols'];
    $out = [];
    for ($i=0; $i<$num_cols; $i++) { array_push($out, '1fr'); }
    return implode(' ',$out);
}

PortaleFunebreNecrologi\PluginBase::add_css_variables([
    'necro-colore-box'    => (PortaleFunebreNecrologi::$inInst) ? PortaleFunebreNecrologi::GetImpostazioni()['colore_box'] : '',
    'necro-colore-page'   => (PortaleFunebreNecrologi::$inInst) ? PortaleFunebreNecrologi::GetImpostazioni()['colore_page'] : '#f9eee4',
    'necro-colore-testo'  => (PortaleFunebreNecrologi::$inInst) ? PortaleFunebreNecrologi::GetImpostazioni()['colore_testo'] : '#000',
]);
