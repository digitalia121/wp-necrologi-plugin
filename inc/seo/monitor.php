<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Controllo periodico dei necrologi (WP-Cron, ogni 15 minuti).
 *
 * Confronta la lista del portale con quella vista l'ultima volta e, per i
 * necrologi nuovi, modificati o rimossi:
 * - aggiorna le date di ultima modifica usate dalla sitemap;
 * - svuota la cache del plugin e quella di pagina dei principali plugin di
 *   cache, cosi' le correzioni (es. un orario) compaiono subito;
 * - avvisa Bing e gli altri motori aderenti tramite IndexNow.
 */
class PortaleFunebreNecrologi_Monitor {

    const EVENTO     = 'portale_funebre_necrologi_controllo';
    const INTERVALLO = 'portale_funebre_necrologi_15_minuti';

    const OPZIONE_REGISTRO = 'portale_funebre_necrologi_registro';
    const OPZIONE_STATO    = 'portale_funebre_necrologi_monitor_stato';
    const OPZIONE_CHIAVE   = 'portale_funebre_necrologi_indexnow_chiave';

    const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
    const INDEXNOW_MAX_URL  = 10000;

    static function Init() {
        add_filter('cron_schedules', [__CLASS__, 'Intervalli']);
        add_action(self::EVENTO, [__CLASS__, 'Controlla']);
        add_action('init', [__CLASS__, 'Pianifica']);
        add_action('parse_request', [__CLASS__, 'ServiChiave'], 0);
    }

    static function Intervalli($intervalli) {
        $intervalli[self::INTERVALLO] = ['interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Ogni 15 minuti (Portale Funebre Necrologi)'];
        return $intervalli;
    }

    static function Pianifica() {
        if (!wp_next_scheduled(self::EVENTO)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, self::INTERVALLO, self::EVENTO);
        }
    }

    /** Alla disattivazione del plugin. */
    static function Disattiva() {
        wp_clear_scheduled_hook(self::EVENTO);
    }

    /**
     * Necrologi visti finora: slug => [h => impronta dei dati, c => prima
     * volta visto, m => ultima modifica nota]. 0 = data sconosciuta (gia'
     * presenti al primo controllo).
     *
     * @return array<string,array{h:string,c:int,m:int}>
     */
    static function Registro() {
        $registro = get_option(self::OPZIONE_REGISTRO, []);
        return is_array($registro) ? $registro : [];
    }

    /** @return array{controllo?:int,indexnow?:int,indexnow_esito?:string,indexnow_url?:int} */
    static function Stato() {
        $stato = get_option(self::OPZIONE_STATO, []);
        return is_array($stato) ? $stato : [];
    }

    static function Controlla() {

        if (!PortaleFunebreNecrologi::IsConfigurato()) { return; }

        // Dati freschi: la cache breve della lista viene saltata.
        PortaleFunebreNecrologi_Repository::SvuotaLista();
        $lista = PortaleFunebreNecrologi_Repository::GetLista();

        if (is_wp_error($lista)) { return; }

        $prima    = self::Registro();
        $primo    = !get_option(self::OPZIONE_REGISTRO);
        $ora      = time();
        $registro = [];
        $cambiati = [];

        foreach ($lista as $dati) {
            $necro    = new PortaleFunebreNecrologi_Necrologio($dati);
            $slug     = $necro->Slug();
            $impronta = md5(wp_json_encode($dati));

            if (!isset($prima[$slug])) {
                $registro[$slug] = ['h' => $impronta, 'c' => $primo ? 0 : $ora, 'm' => $primo ? 0 : $ora];
                $cambiati[]      = $slug;
            } elseif ($prima[$slug]['h'] !== $impronta) {
                $registro[$slug] = ['h' => $impronta, 'c' => (int) $prima[$slug]['c'], 'm' => $ora];
                $cambiati[]      = $slug;
            } else {
                $registro[$slug] = $prima[$slug];
            }
        }

        $rimossi = array_keys(array_diff_key($prima, $registro));

        update_option(self::OPZIONE_REGISTRO, $registro, false);

        $stato              = self::Stato();
        $stato['controllo'] = $ora;

        $toccati = array_merge($cambiati, $rimossi);

        if ($toccati) {

            $url = array_map(['PortaleFunebreNecrologi_Necrologio', 'UrlDaSlug'], $toccati);

            if (!$primo) {
                foreach ($toccati as $slug) { PortaleFunebreNecrologi_Repository::SvuotaSingolo($slug); }
                self::SvuotaCachePagine(array_merge($url, self::UrlPagineLista()));
            }

            if (self::IsIndexNowAttivo()) {
                $esito                   = self::InviaIndexNow($url);
                $stato['indexnow']       = $ora;
                $stato['indexnow_esito'] = $esito;
                $stato['indexnow_url']   = count($url);
            }
        }

        update_option(self::OPZIONE_STATO, $stato, false);
    }

    /* ------------------------------------------------------------------
     * Cache di pagina
     * ---------------------------------------------------------------- */

    /** Pagine che mostrano lista o slider, piu' la home (dove di solito c'e' lo slider). */
    private static function UrlPagineLista() {
        $url = [home_url('/')];
        foreach (array_keys(PortaleFunebreNecrologi_Sitemap::PagineConShortcode()) as $id) {
            $url[] = get_permalink($id);
        }
        return array_values(array_unique(array_filter($url)));
    }

    /** Svuota la cache di pagina degli URL indicati nei plugin di cache piu' diffusi. */
    static function SvuotaCachePagine(array $urls) {

        $urls = array_values(array_unique(array_filter($urls)));

        if (!$urls) { return; }

        if (function_exists('rocket_clean_files')) { rocket_clean_files($urls); }

        foreach ($urls as $url) {
            do_action('litespeed_purge_url', $url);
            do_action('cache_enabler_clear_page_cache_by_url', $url);
            if (function_exists('w3tc_flush_url'))          { w3tc_flush_url($url); }
            if (function_exists('wpsc_delete_url_cache'))   { wpsc_delete_url_cache($url); }
            if (function_exists('sg_cachepress_purge_cache')) { sg_cachepress_purge_cache($url); }
        }

        /** Per integrare altri sistemi di cache (CDN, reverse proxy, ...). */
        do_action('portale_funebre_necrologi_svuota_cache_url', $urls);
    }

    /* ------------------------------------------------------------------
     * IndexNow
     * ---------------------------------------------------------------- */

    /** Attivo se abilitato nelle impostazioni e se il sito non scoraggia i motori di ricerca. */
    static function IsIndexNowAttivo() {
        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        $abilitato    = !($impostazioni && isset($impostazioni['seo_indexnow']) && $impostazioni['seo_indexnow'] === '0');
        return (bool) apply_filters('portale_funebre_necrologi_indexnow_attivo', $abilitato && get_option('blog_public'));
    }

    /** Chiave IndexNow del sito, generata alla prima richiesta. */
    static function Chiave() {
        $chiave = get_option(self::OPZIONE_CHIAVE);
        if (!is_string($chiave) || !preg_match('/^[a-f0-9]{32}$/', $chiave)) {
            $chiave = md5(wp_generate_password(32, false) . home_url());
            update_option(self::OPZIONE_CHIAVE, $chiave, false);
        }
        return $chiave;
    }

    static function UrlChiave() {
        return home_url('/' . self::Chiave() . '.txt');
    }

    /** Pubblica il file della chiave (/{chiave}.txt) richiesto dal protocollo IndexNow. */
    static function ServiChiave() {

        if (!isset($_SERVER['REQUEST_URI'])) { return; }

        $percorso = (string) wp_parse_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH);

        // Controllo veloce prima di leggere l'opzione: riguarda solo /{32 caratteri esadecimali}.txt
        if (!preg_match('#/[a-f0-9]{32}\.txt$#', $percorso)) { return; }

        $chiave = get_option(self::OPZIONE_CHIAVE);

        if (!is_string($chiave) || $chiave === '' || $percorso !== (string) wp_parse_url(self::UrlChiave(), PHP_URL_PATH)) { return; }

        status_header(200);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex');
        echo esc_html($chiave);
        exit;
    }

    /** @return string Esito leggibile, mostrato nelle impostazioni. */
    private static function InviaIndexNow(array $urls) {

        $urls = array_slice(array_values(array_unique($urls)), 0, self::INDEXNOW_MAX_URL);

        $risposta = wp_remote_post(self::INDEXNOW_ENDPOINT, [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'    => wp_json_encode([
                'host'        => wp_parse_url(home_url(), PHP_URL_HOST),
                'key'         => self::Chiave(),
                'keyLocation' => self::UrlChiave(),
                'urlList'     => $urls,
            ]),
        ]);

        if (is_wp_error($risposta)) { return 'errore: ' . $risposta->get_error_message(); }

        $codice = (int) wp_remote_retrieve_response_code($risposta);

        return in_array($codice, [200, 202], true) ? 'ok (' . $codice . ')' : 'rifiutato (HTTP ' . $codice . ')';
    }

}
