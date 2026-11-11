<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Controllo periodico dei necrologi (WP-Cron, ogni minuto).
 *
 * Chiede al portale solo i necrologi modificati dall'ultimo controllo
 * (parametro "modificati_dal"), li unisce alla copia locale della lista e,
 * per i necrologi nuovi, modificati o rimossi:
 * - aggiorna le date di ultima modifica usate dalla sitemap;
 * - svuota la cache del plugin e quella di pagina dei principali plugin di
 *   cache, cosi' le correzioni (es. un orario) compaiono subito;
 * - avvisa Bing e gli altri motori aderenti tramite IndexNow.
 *
 * La lista completa viene riscaricata solo al primo controllo, una volta al
 * giorno come verifica, dopo che il controllo e' rimasto fermo per qualche
 * minuto, o se il portale non gestisce gli aggiornamenti incrementali.
 */
class PortaleFunebreNecrologi_Monitor {

    const EVENTO     = 'portale_funebre_necrologi_controllo';
    const INTERVALLO = 'portale_funebre_necrologi_1_minuto';

    const OPZIONE_REGISTRO = 'portale_funebre_necrologi_registro';
    const OPZIONE_STATO    = 'portale_funebre_necrologi_monitor_stato';
    const OPZIONE_CHIAVE   = 'portale_funebre_necrologi_indexnow_chiave';
    /** Lista del portale cosi' com'e' arrivata, base per unire gli aggiornamenti incrementali. */
    const OPZIONE_LISTA    = 'portale_funebre_necrologi_lista_portale';

    const BLOCCO = 'pfn_controllo_in_corso';

    /** Oltre questo intervallo senza controlli il controllo e' considerato fermo. */
    const MAX_PAUSA = 5 * MINUTE_IN_SECONDS;

    /** Ogni quanto si riscarica comunque la lista completa, come verifica. */
    const INTERVALLO_COMPLETO = DAY_IN_SECONDS;

    /** Margine sull'istante di partenza, per non perdere modifiche salvate a cavallo di due controlli. */
    const SOVRAPPOSIZIONE = 2 * MINUTE_IN_SECONDS;

    const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
    const INDEXNOW_MAX_URL  = 10000;

    /** Immagini per i social preparate a ogni controllo (le altre al giro successivo o alla prima visita). */
    const IMMAGINI_PER_CONTROLLO = 20;

    static function Init() {
        add_filter('cron_schedules', [__CLASS__, 'Intervalli']);
        add_action(self::EVENTO, [__CLASS__, 'Controlla']);
        add_action('init', [__CLASS__, 'Pianifica']);
        add_action('parse_request', [__CLASS__, 'ServiChiave'], 0);
    }

    static function Intervalli($intervalli) {
        $intervalli[self::INTERVALLO] = ['interval' => MINUTE_IN_SECONDS, 'display' => 'Ogni minuto (Portale Funebre Necrologi)'];
        return $intervalli;
    }

    static function Pianifica() {
        // Le versioni precedenti pianificavano il controllo ogni 15 minuti: va ripianificato.
        if (wp_next_scheduled(self::EVENTO) && wp_get_schedule(self::EVENTO) !== self::INTERVALLO) {
            wp_clear_scheduled_hook(self::EVENTO);
        }
        if (!wp_next_scheduled(self::EVENTO)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, self::INTERVALLO, self::EVENTO);
        }
    }

    /** Il controllo di ogni minuto sta girando (l'ultimo e' di pochi minuti fa). */
    static function IsAttivo() {
        $stato = self::Stato();
        return !empty($stato['controllo']) && time() - (int) $stato['controllo'] <= self::MAX_PAUSA;
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

        // Un controllo alla volta: con l'intervallo di un minuto un portale lento potrebbe farli sovrapporre.
        if (get_transient(self::BLOCCO)) { return; }
        set_transient(self::BLOCCO, 1, 2 * MINUTE_IN_SECONDS);

        try {
            self::Esegui();
        } finally {
            delete_transient(self::BLOCCO);
        }
    }

    /**
     * Lista aggiornata del portale (non filtrata), con l'istante del portale da
     * cui partire al controllo successivo.
     *
     * @return array{0:array,1:int,2:bool}|null [lista, prossimo "modificati_dal", incrementale] o null se il portale non risponde.
     */
    private static function ListaAggiornata(array $stato) {

        $api  = new PortaleFunebreNecrologi_API();
        $ora  = time();
        $base = get_option(self::OPZIONE_LISTA);

        $completo = !is_array($base)
            || empty($stato['portale_ora'])
            || empty($stato['completo']) || $ora - (int) $stato['completo'] >= self::INTERVALLO_COMPLETO
            || empty($stato['controllo']) || $ora - (int) $stato['controllo'] > self::MAX_PAUSA;

        if (!$completo) {
            $risposta = $api->TrovaNecrologiModificati(max(1, (int) $stato['portale_ora'] - self::SOVRAPPOSIZIONE));
            $unita    = self::UnisciModifiche($base, $risposta);
            if ($unita) { return [$unita[0], $unita[1], true]; }
            // Il portale ha risposto con la lista intera (versione senza aggiornamenti incrementali).
            if (self::IsLista($risposta)) { return [$risposta, $ora, false]; }
            if (!is_object($risposta)) { return null; }
        }

        $risposta = $api->TrovaTuttiNecrologi();

        if (!self::IsLista($risposta)) { return null; }

        // La lista completa non dice l'ora del portale: si riparte da quella del sito, con un margine ampio
        // per eventuali differenze tra gli orologi. Dal controllo successivo vale l'ora restituita dal portale.
        return [$risposta, $ora - 10 * MINUTE_IN_SECONDS, false];
    }

    /**
     * Lista di necrologi valida. Un errore del portale puo' arrivare come
     * array di messaggi (es. ["Accesso non consentito"]): non va scambiato per
     * una lista vuota, che farebbe risultare rimossi tutti i necrologi.
     */
    private static function IsLista($risposta) {
        if (!is_array($risposta)) { return false; }
        foreach ($risposta as $voce) {
            if (!is_object($voce)) { return false; }
        }
        return true;
    }

    /**
     * Applica alla lista precedente i necrologi modificati, nell'ordine e con
     * le sole voci indicate dal portale (le assenti sono state rimosse).
     *
     * @return array{0:array,1:int}|null null se la risposta non e' incrementale o non torna con la lista precedente.
     */
    private static function UnisciModifiche(array $base, $risposta) {

        if (!is_object($risposta) || !isset($risposta->ora, $risposta->modificati, $risposta->slug)
            || !is_array($risposta->modificati) || !is_array($risposta->slug)) {
            return null;
        }

        $per_slug = [];
        foreach (array_merge($base, $risposta->modificati) as $dati) {
            if (is_object($dati) && !empty($dati->slug)) { $per_slug[$dati->slug] = $dati; }
        }

        $lista = [];
        foreach ($risposta->slug as $slug) {
            // Un necrologio pubblicato che non si conosce: meglio riscaricare tutto.
            if (!isset($per_slug[$slug])) { return null; }
            $lista[] = $per_slug[$slug];
        }

        return [$lista, (int) $risposta->ora];
    }

    private static function Esegui() {

        $stato      = self::Stato();
        $aggiornata = self::ListaAggiornata($stato);

        if ($aggiornata === null) { return; }

        list($grezza, $portale_ora, $incrementale) = $aggiornata;

        update_option(self::OPZIONE_LISTA, $grezza, false);
        PortaleFunebreNecrologi_Repository::ImpostaLista($grezza);
        $lista = PortaleFunebreNecrologi_Repository::FiltraLista($grezza);

        $prima    = self::Registro();
        $primo    = !get_option(self::OPZIONE_REGISTRO);
        $ora      = time();
        $registro = [];
        $cambiati = [];
        $necrologi = [];

        $stato['portale_ora']  = $portale_ora;
        $stato['incrementale'] = $incrementale;
        if (!$incrementale) { $stato['completo'] = $ora; }

        // L'impronta si calcola su tutta la lista (non serve il portale): conta come "cambiato" solo cio' che e' davvero diverso.
        foreach ($lista as $dati) {
            $necro    = new PortaleFunebreNecrologi_Necrologio($dati);
            $slug     = $necro->Slug();
            $impronta = md5(wp_json_encode($dati));

            $necrologi[$slug] = $necro;

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

        $stato['controllo'] = $ora;

        // Copia ridotta della foto per le anteprime dei social, pronta prima che qualcuno condivida il necrologio.
        foreach (array_slice($cambiati, 0, self::IMMAGINI_PER_CONTROLLO) as $slug) {
            $necrologi[$slug]->ImmagineSocial();
        }

        $toccati = array_merge($cambiati, $rimossi);

        if ($toccati) {

            $url = array_map(['PortaleFunebreNecrologi_Necrologio', 'UrlDaSlug'], $toccati);

            if (!$primo) {
                foreach ($toccati as $slug) { PortaleFunebreNecrologi_Repository::SvuotaSingolo($slug); }
                self::SvuotaCachePagine(array_merge($url, self::UrlPagineLista()));
            }

            // A IndexNow solo i necrologi indicizzabili (e quelli rimossi, che vanno tolti dai motori).
            $url_indexnow = array_map(['PortaleFunebreNecrologi_Necrologio', 'UrlDaSlug'], array_merge(
                array_filter($cambiati, ['PortaleFunebreNecrologi_SeoNecrologio', 'IsIndicizzabile']),
                $rimossi
            ));

            if ($url_indexnow && self::IsIndexNowAttivo()) {
                $stato['indexnow']       = $ora;
                $stato['indexnow_esito'] = self::InviaIndexNow($url_indexnow);
                $stato['indexnow_url']   = count($url_indexnow);
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

    /** Plugin di cache di pagina attivi tra quelli che il plugin sa svuotare. */
    static function PluginCachePagina() {
        $trovati = [
            'WP Rocket'        => function_exists('rocket_clean_files'),
            'LiteSpeed Cache'  => defined('LSCWP_V'),
            'W3 Total Cache'   => function_exists('w3tc_flush_url'),
            'WP Super Cache'   => function_exists('wpsc_delete_url_cache'),
            'SiteGround Speed Optimizer' => function_exists('sg_cachepress_purge_cache'),
            'Cache Enabler'    => class_exists('Cache_Enabler'),
        ];
        return array_keys(array_filter($trovati));
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
