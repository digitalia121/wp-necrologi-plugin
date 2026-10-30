<?php

namespace PortaleFunebreNecrologi;

// Evita l'accesso diretto al file
if (!defined('ABSPATH')) { exit; }

function get_plugin_page_url($name) {

    if (PluginBase::$CURRENT_ACTIVE_PLUGIN) {
        $instance = PluginBase::get_instance();
        if ($instance) {
            return admin_url('admin.php?page=' . $instance->get_slug() . '-' . $name);
        }
    }

    return '';

}

function get_plugin_asset_url($name) {

    if (PluginBase::$CURRENT_ACTIVE_PLUGIN) {
        $instance = PluginBase::get_instance();
        if ($instance) {
            return $instance->get_asset_url($name);
        }
    }

    return '';

}

function portale_funebre_necrologi_sanitize_ajax_data($data) {

    static $multilinea = null;

    if ($multilinea === null) {
        $multilinea = (array) apply_filters('portale_funebre_necrologi_campi_multilinea', ['message', 'messaggio']);
    }

    $clean = [];

    foreach ((array) $data as $key => $value) {
        $key = sanitize_key($key);

        if (is_array($value)) {
            $clean[$key] = portale_funebre_necrologi_sanitize_ajax_data($value);
            continue;
        }

        if ('email' === $key) {
            $clean[$key] = sanitize_email($value);
        } elseif (in_array($key, $multilinea, true)) {
            $clean[$key] = sanitize_textarea_field($value);
        } elseif (in_array($key, ['url', 'link'], true)) {
            $clean[$key] = esc_url_raw($value);
        } else {
            $clean[$key] = sanitize_text_field($value);
        }
    }

    return $clean;

}

function portale_funebre_necrologi_ajax() {

    if (!check_ajax_referer('portale_funebre_necrologi_ajax', 'nonce', false)) {
        wp_send_json_error(['message' => 'Nonce non valido.'], 403);
    }

    $request = portale_funebre_necrologi_sanitize_ajax_data(wp_unslash($_POST));
    $azione  = isset($request['dgplugin_action']) ? sanitize_key($request['dgplugin_action']) : '';

    if (isset(PluginBase::$ACTIONS[$azione])) {

        $res = PluginBase::$ACTIONS[$azione]($request);
        wp_send_json_success($res);

    }

    wp_send_json_error(['message' => 'Azione non valida.'], 400);

}
add_action('wp_ajax_portale_funebre_necrologi_ajax', __NAMESPACE__ . '\\portale_funebre_necrologi_ajax');
add_action('wp_ajax_nopriv_portale_funebre_necrologi_ajax', __NAMESPACE__ . '\\portale_funebre_necrologi_ajax');


class PluginBase {

    private $slug;
    private $plugin_name;
    private $PAGINE=[];
    private $dbMan=null;

    private $plug_dir = '';
    private $plug_url = '';
    private $main_plugin_file = '';
    private static $PLUGDATA = [];
    public static  $ACTIONS=[];
    private static $CSS_VARIABLES=[];
    private $plugin_icon = 'dashicons-admin-home';

    private static $INST = null;


    private static $GLOBAL_OBJ_VARS=[];
    public static $CURRENT_ACTIVE_PLUGIN='';

    static function get_instance() {
        return self::$INST;
    }


    function __construct($plugin_name, $main_plugin_file, $icona='dashicons-admin-home', array $legacy_slugs = []) {

        $plugin_dir = dirname($main_plugin_file);

        $this->plug_dir    = $plugin_dir;
        $this->plug_url    = plugin_dir_url($main_plugin_file);
        $this->main_plugin_file = $main_plugin_file;
        $this->plugin_name = $plugin_name;
        $this->plugin_icon = $icona;

        $this->slug  = sanitize_title($plugin_name);
        $this->dbMan = new DbOpzioniPlugin($this->slug, $legacy_slugs);

        $main_class = $this;
        register_activation_hook($main_plugin_file, function () {

            if (!$this->is_installed()) {
                $this->dbMan->create_table_if_not_exists();
                $this->before_install();
            }
            $this->before_activation();

        });

        //register_uninstall_hook(__FILE__, [$this,'after_uninstall']);

        $plug_fold_name = basename($this->plug_dir);

        if ( is_admin() ) {

            add_action('admin_enqueue_scripts', function () {
                wp_enqueue_media();
            });

            add_action('admin_menu', function () {

                $nome_plugin = $this->plugin_name;
                $plug_slug   = $this->slug;

                $callback    = [$this, 'mostra_pagina_plugin'];
                AdminMenuManager::CreaMenuPrincipale($nome_plugin,$plug_slug,$callback,$this->plugin_icon);

                $pagine = $this->create_menu_pages();

                if ($pagine && is_array($pagine)) {

                    foreach ($pagine as $pag) {

                        $pg_slug = sanitize_title($pag);

                        $this->PAGINE[$pg_slug] = $pag;

                        AdminMenuManager::CreaSottoPaginaMenu($nome_plugin, $plug_slug, $pag, $pg_slug, $callback);

                    }

                }

            });

            add_action('admin_enqueue_scripts', function ($hook) use ($plug_fold_name) {
                // Carica solo per le pagine del plugin
                if (strpos($hook, $this->slug) !== false) {
                    $backend_css_path = $this->plug_dir . '/scripts/backend.css';
                    $backend_js_path  = $this->plug_dir . '/scripts/backend.js';
                    $backend_style_handle = $this->slug . '-backend';
                    wp_enqueue_style($backend_style_handle,  $this->plug_url . 'scripts/backend.css', [], file_exists($backend_css_path) ? filemtime($backend_css_path) : false);
                    wp_enqueue_script($this->slug.'-backend', $this->plug_url . 'scripts/backend.js', array('jquery'), file_exists($backend_js_path) ? filemtime($backend_js_path) : false, true);
                    wp_add_inline_style($backend_style_handle, self::get_css_variables());
                }
            });

        }

        add_action('wp_enqueue_scripts', function () use ($plug_fold_name) {
            $frontend_css_path = $this->plug_dir . '/scripts/frontend.css';
            $frontend_js_path  = $this->plug_dir . '/scripts/frontend.js';
            $frontend_style_handle = $this->slug . '-frontend';
            $frontend_script_handle = $this->slug . '-frontend';
            wp_enqueue_style($frontend_style_handle,  $this->plug_url . 'scripts/frontend.css', [], file_exists($frontend_css_path) ? filemtime($frontend_css_path) : false);
            wp_enqueue_script($frontend_script_handle, $this->plug_url . 'scripts/frontend.js', array('jquery'), file_exists($frontend_js_path) ? filemtime($frontend_js_path) : false, true);
            wp_localize_script($frontend_script_handle, 'portaleFunebreNecrologiData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('portale_funebre_necrologi_ajax'),
                'siteUrl' => get_site_url(),
                'vars'    => self::$GLOBAL_OBJ_VARS,
            ]);
            wp_add_inline_style($frontend_style_handle, self::get_css_variables());
        });

    }

    private function hasCall($call_name) {
        if (!isset(self::$WP_CALLS[__FILE__])) { return false; }
        return self::$WP_CALLS[__FILE__] == $call_name;
    }

    protected function is_installed() { return $this->dbMan->isPluginInstalled(); }

    public function get_opzione(string $opzione) {

        $db  = $this->dbMan;
        $res = $db->get_entry($opzione);

        if ($res) { return new DbOpzionePlugin($this->dbMan, $res); }

        return new DbOpzionePlugin($this->dbMan, [
            'nome'      => $opzione,
            'dati'      => '{}',
            'creazione' => '',
            'modifica'  => ''
        ]);

    }

    static function add_js_variables($variabili) { foreach ($variabili as $name => $data) { self::$GLOBAL_OBJ_VARS[$name] = $data; } }

    static function add_css_variables($variabili) { foreach ($variabili as $name => $data) { self::$CSS_VARIABLES[$name] = $data; } }

    private static function get_css_variables() {
        $custom_css = '';
        foreach (self::$CSS_VARIABLES as $k => $val) {
            $custom_css .= '--dg-' . sanitize_key($k) . ': ' . sanitize_text_field($val) . ";\n";
        }
        return $custom_css ? ":root {\n" . $custom_css . "}\n" : '';
    }

    static function add_ajax_call($nome_call, $fun) { self::$ACTIONS[$nome_call] = $fun; }

    static function get_author() { return self::$PLUGDATA['Author']; }
    static function get_author_url() { return self::$PLUGDATA['AuthorURI']; }
    static function get_version() { return self::$PLUGDATA['Version']; }
    static function get_description() { return self::$PLUGDATA['Description']; }
    static function get_plugin_name() { return self::$PLUGDATA['Name']; }

    public function mostra_pagina_plugin () {

        $screen = get_current_screen();
        $screen_id = ($screen) ? $screen->id : '';

        $overview_page  = 'toplevel_page_'.$this->slug.'-overview';
        $page_found = trim(str_replace('_page_','',str_replace($this->slug,'',$screen_id)),'-');

        if ($screen_id == $overview_page || $page_found == 'overview') {

            self::$INST = $this;
            self::$CURRENT_ACTIVE_PLUGIN = basename($this->plug_dir);

            self::$PLUGDATA = \get_plugin_data($this->main_plugin_file, false, true);

            AdminMenuManager::IncludiPaginaPlugin($this->plug_dir.'/plugin_home.php',$this->plugin_name,'Overview');

        } else if (isset($this->PAGINE[$page_found])) {

            self::$INST = $this;
            self::$CURRENT_ACTIVE_PLUGIN = basename($this->plug_dir);

            self::$PLUGDATA = \get_plugin_data($this->main_plugin_file, false, true);

            AdminMenuManager::IncludiPaginaPlugin($this->plug_dir.'/pages/'.$page_found.'.php',$this->plugin_name,$this->PAGINE[$page_found]);
        }

    }

    public function before_install() { }
    public function after_uninstall() { }
    public function before_activation() { }
    public function create_menu_pages() { }

    public function get_slug() { return $this->slug; }

    public function get_asset_url($name) { return $this->plug_url . 'assets/' . ltrim($name, '/'); }

    public function create_shortcode($short_string, array $params=[]) {

        self::$INST = $this;

        self::$CURRENT_ACTIVE_PLUGIN = basename($this->plug_dir);

        $shorts_folder = $this->plug_dir . '/shorts/';

        add_shortcode($short_string, function($atts) use ($short_string, $shorts_folder) {

            $shortcode_file = $shorts_folder . $short_string . '.php';

            if (file_exists($shortcode_file)) {

                if (function_exists($short_string)) {
                    return call_user_func($short_string, shortcode_atts([], $atts));
                } else {
                    ob_start();
                    include_once($shortcode_file);
                    return ob_get_clean();
                }

            } else {
                return '<p>Errore: Il file <strong>' . esc_html($shortcode_file . '.php') . '</strong> non esiste.</p>';
            }

        });

    }

}
