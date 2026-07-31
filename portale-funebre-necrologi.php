<?php /*
Plugin Name: Portale Funebre Necrologi
Plugin URI: http://www.portalefunebre.com
Description: Gestione dei necrologi sul tuo sito.
Version: 1.0
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
            add_rewrite_rule('^'.$slug_singolo.'/([^/]+)?', 'index.php?necro_slug=$matches[1]', 'top');
        });
        add_filter('query_vars', function($vars) {
            $vars[] = 'pf_share_slug';
            $vars[] = 'necro_slug';
            return $vars;
        });

        if (PortaleFunebreNecrologi::IsConfigurato()) {
            add_action('template_redirect', function() use ($slug_singolo)  {
                
                $slug = get_query_var('necro_slug');
                
                if (!$slug) { return; }

                $post = get_page_by_path($slug_singolo, OBJECT, 'page'); // oppure 'post' se cerchi tra i post

                if ($post) {
                    
                    setup_postdata($post);
                    
                    // imposta globali per far credere a WP che siamo su quella pagina
                    global $wp_query;

                    $wp_query->post    = $post;
                    $wp_query->posts   = [$post];
                    $wp_query->is_page = true;
                    $wp_query->is_singular = true;
                    $wp_query->is_home = false;
                    $wp_query->is_404  = false;

                    $template = get_single_template();

                    if (!$template) { $template = get_page_template(); }

                    if ($template) {
                        include(get_single_template());
                        exit;
                    }
                    
                } else {
                    // fallback se non trova nulla
                    wp_die('Pagina non trovata', 'Errore 404', ['response' => 404]);
                }
            });
            add_action('template_redirect', function () {

                $slug = get_query_var('pf_share_slug');
                if (!$slug) return;

                remove_all_actions('wpseo_head');

                $api  = new PortaleFunebreNecrologi_API();
                $data = $api->TrovaNecrologioSingolo($slug);

                if (!$data || (empty($data))) {
                    wp_die('Not found');
                }

                $agenzia = (isset($data->agenzia)) ? $data->agenzia : (object)['ragione_sociale' => 'Onoranze Funebri', 'partita_iva'=> ''];

                $thumb = $data->thumbnail;
                $title = esc_attr($data->nome_defunto).' - '.$agenzia->ragione_sociale;
                $desc  = wp_strip_all_tags($data->testo);
                $img   = PortaleFunebreNecrologi_API::GetImgUrl('').$thumb;

                if (!$desc) {
                    $desc = 'E\' mancato/a all\'affetto dei suoi cari '.$data->nome_defunto;
                }
                $url      = home_url('/pf-share/'.$slug);
                $real_url = esc_url(home_url('/'.self::$IMPOSTAZIONI['slug_singolo'].'/'.$slug));

                // Copia ridotta dell'immagine: serve a far usare a Facebook la
                // scheda compatta, con la foto verticale sulla sinistra.
                $og_img = self::GetImmagineShare($img);

                // Il redirect va eseguito solo per i visitatori reali: i crawler dei social
                // devono fermarsi qui per leggere i meta tag Open Graph.
                $redirect = !self::IsSocialCrawler();

                ?>
                <!DOCTYPE html>
                <html>
                    <head>

                        <title><?php echo esc_attr($title); ?></title>

                        <!-- Open Graph / Facebook -->
                        <meta property="og:title" content="<?php echo esc_attr($title); ?>" />
                        <meta property="og:description" content="<?php echo esc_attr($desc); ?>" />
                        <meta property="og:image" content="<?php echo esc_url($og_img['url']); ?>" />
                        <meta property="og:image:secure_url" content="<?php echo esc_url($og_img['url']); ?>" />
                        <meta property="og:image:width" content="<?php echo esc_attr($og_img['width']); ?>" />
                        <meta property="og:image:height" content="<?php echo esc_attr($og_img['height']); ?>" />
                        <meta property="og:image:alt" content="<?php echo esc_attr($title); ?>" />
                        <meta property="og:type"  content="article" />
                        <meta property="og:url" content="<?php echo esc_url($url); ?>" />

                        <!-- X (Twitter) -->
                        <meta property="twitter:card" content="summary" />
                        <meta property="twitter:url" content="<?php echo esc_url($real_url); ?>" />
                        <meta property="twitter:title" content="<?php echo esc_attr($title); ?>" />
                        <meta property="twitter:description" content="<?php echo esc_attr($desc); ?>" />
                        <meta property="twitter:image" content="<?php echo esc_url($og_img['url']); ?>" />

                        <meta name="msapplication-TileImage" content="<?php echo esc_url($img); ?>">
                        <meta name="robots" content="noindex, nofollow">
                    </head>
                    <body>
                        <h1><?php echo esc_attr($title); ?></h1>
                        <p><?php echo esc_attr($desc); ?></p>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>"/>
                        <p><a href="<?php echo esc_url($real_url); ?>">Vai al necrologio</a></p>
                        <?php if ($redirect) : ?>
                        <script>
                        (function () {
                            // I crawler dei social non eseguono JavaScript: leggono i meta tag
                            // qui sopra e si fermano. I browser reali proseguono al necrologio.
                            document.documentElement.style.opacity = '0';
                            window.location.replace(<?php echo wp_json_encode($real_url); ?>);
                        })();
                        </script>
                        <?php endif; ?>
                    </body>
                </html>
                <?php
                exit;
            });
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

        if (!$img_url) { return $originale; }

        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) { return $originale; }

        $cartella  = trailingslashit($uploads['basedir']).'pf-share-og';
        $base_url  = trailingslashit($uploads['baseurl']).'pf-share-og';
        $estensione = strtolower(pathinfo((string) wp_parse_url($img_url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (!in_array($estensione, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) { $estensione = 'jpg'; }

        // La chiave e' l'URL originale: se la foto cambia, cambia anche il file.
        $percorso = $cartella.'/'.md5($img_url).'-'.self::SHARE_IMG_WIDTH.'.'.$estensione;

        if (!file_exists($percorso)) {

            if (!wp_mkdir_p($cartella)) { return $originale; }

            require_once ABSPATH.'wp-admin/includes/file.php';

            $tmp = download_url($img_url);

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
     * Riconosce i crawler dei social network (e dei motori di ricerca) che
     * effettuano lo scraping dei meta tag Open Graph. Per questi user agent
     * la pagina di share non deve reindirizzare, altrimenti le informazioni
     * di condivisione vengono lette dalla pagina di destinazione.
     */
    static function IsSocialCrawler() {

        if (empty($_SERVER['HTTP_USER_AGENT'])) { return true; }

        $ua = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));

        $bots = [
            'facebookexternalhit',
            'facebookcatalog',
            'facebookbot',
            'facebot',
            'meta-externalagent',
            'twitterbot',
            'linkedinbot',
            'whatsapp',
            'telegrambot',
            'discordbot',
            'slackbot',
            'skypeuripreview',
            'pinterest',
            'redditbot',
            'applebot',
            'googlebot',
            'bingbot',
            'embedly',
            'quora link preview',
            'vkshare',
            'w3c_validator',
            'developers.google.com/+/web/snippet',
        ];

        foreach ($bots as $bot) {
            if (strpos($ua, $bot) !== false) { return true; }
        }

        return false;
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

PortaleFunebreNecrologi::add_ajax_call('get_necrologio_singolo', function ($var) {
    if (!PortaleFunebreNecrologi::$inInst) { return; }
    $slug = isset($var['slug']) ? sanitize_title($var['slug']) : '';
    $api = new PortaleFunebreNecrologi_API();
    return $api->TrovaNecrologioSingolo($slug);
});
PortaleFunebreNecrologi::add_ajax_call('get_lista_necrologi', function () {
    if (!PortaleFunebreNecrologi::$inInst) { return; }
    $api = new PortaleFunebreNecrologi_API();
    return $api->TrovaTuttiNecrologi();
});
PortaleFunebreNecrologi::add_ajax_call('get_anteprima_necrologi', function () {
    if (!PortaleFunebreNecrologi::$inInst) { return; }
    $api = new PortaleFunebreNecrologi_API();
    return $api->TrovaTuttiNecrologi(10);
});
PortaleFunebreNecrologi::add_ajax_call('invia_cordoglio_api', function ($var) {
    if (!PortaleFunebreNecrologi::$inInst) { return; }
    $api = new PortaleFunebreNecrologi_API();
    return $api->InviaCordoglio($var);
});

new PortaleFunebreNecrologi;


$necro_settings_array = PortaleFunebreNecrologi::GetImpostazioni()->toArray();
unset($necro_settings_array['api_key']);
unset($necro_settings_array['client_id']);

PortaleFunebreNecrologi\PluginBase::add_js_variables([
    'necro_settings' => $necro_settings_array,
    'necro_img_url'  => PortaleFunebreNecrologi_API::GetImgUrl(''),
    'necrologio_url' => get_site_url().'/'.PortaleFunebreNecrologi::GetImpostazioni()['slug_singolo']
]);

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
