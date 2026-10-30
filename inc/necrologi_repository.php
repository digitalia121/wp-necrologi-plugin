<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Unico punto di lettura dei necrologi dal portale, con cache.
 *
 * - Cache breve (transient, durata impostabile): evita di interrogare il
 *   portale a ogni visita.
 * - Copia di riserva (7 giorni): usata quando il portale non risponde, cosi'
 *   le pagine restano online anche durante un disservizio.
 * - Anche l'esito "necrologio inesistente" viene messo in cache per qualche
 *   minuto, per non far interrogare il portale dai bot che provano slug a caso.
 *
 * I dati restano sul portale: qui ci sono solo copie temporanee che scadono.
 */
class PortaleFunebreNecrologi_Repository {

    const DURATA_PREDEFINITA = 10;                 // minuti
    const DURATE             = [5, 10, 15, 30, 60]; // minuti selezionabili nelle impostazioni
    const DURATA_RISERVA     = WEEK_IN_SECONDS;
    const DURATA_ASSENTE     = 5 * MINUTE_IN_SECONDS;

    const OPZIONE_VERSIONE = 'portale_funebre_necrologi_cache_versione';
    const ASSENTE          = 'assente';

    /** Risultati gia' letti durante la richiesta corrente. */
    private static $memoria = [];

    /**
     * Restituisce il necrologio con lo slug indicato.
     *
     * @return object|false|WP_Error L'oggetto del portale, false se il
     *                               necrologio non esiste, WP_Error se il
     *                               portale non e' raggiungibile e non c'e'
     *                               una copia di riserva.
     */
    static function GetSingolo($slug) {

        $slug = sanitize_title($slug);

        if ($slug === '') { return false; }

        $chiave = 's_' . md5($slug);

        if (array_key_exists($chiave, self::$memoria)) { return self::$memoria[$chiave]; }

        $in_cache = get_transient(self::Chiave($chiave));

        if ($in_cache === self::ASSENTE) { return self::$memoria[$chiave] = false; }
        if (is_object($in_cache))        { return self::$memoria[$chiave] = $in_cache; }

        $esito = self::InterpretaSingolo(self::Api() ? self::Api()->TrovaNecrologioSingolo($slug) : null);

        if (is_object($esito)) {
            set_transient(self::Chiave($chiave), $esito, self::Durata());
            set_transient('pfn_r_' . $chiave, $esito, self::DURATA_RISERVA);
        } elseif ($esito === false) {
            set_transient(self::Chiave($chiave), self::ASSENTE, self::DURATA_ASSENTE);
            delete_transient('pfn_r_' . $chiave);
        } else {
            $riserva = get_transient('pfn_r_' . $chiave);
            if (is_object($riserva)) { $esito = $riserva; }
        }

        return self::$memoria[$chiave] = $esito;
    }

    /**
     * Restituisce tutti i necrologi pubblicati, nell'ordine del portale.
     *
     * @return array|WP_Error
     */
    static function GetLista() {

        if (array_key_exists('lista', self::$memoria)) { return self::$memoria['lista']; }

        $in_cache = get_transient(self::Chiave('lista'));

        if (is_array($in_cache)) { return self::$memoria['lista'] = $in_cache; }

        $risposta = self::Api() ? self::Api()->TrovaTuttiNecrologi() : null;

        if (is_array($risposta)) {
            // Come in passato, si scartano le voci senza nome.
            $lista = array_values(array_filter($risposta, static function ($necro) {
                return is_object($necro) && !empty($necro->nome_defunto) && !empty($necro->slug);
            }));
            set_transient(self::Chiave('lista'), $lista, self::Durata());
            set_transient('pfn_r_lista', $lista, self::DURATA_RISERVA);
            return self::$memoria['lista'] = $lista;
        }

        $riserva = get_transient('pfn_r_lista');

        if (is_array($riserva)) { return self::$memoria['lista'] = $riserva; }

        return self::$memoria['lista'] = new WP_Error('portale_funebre_non_disponibile', 'Il portale dei necrologi non risponde.');
    }

    /**
     * Invalida la cache breve di tutti i necrologi (le copie di riserva
     * restano). Si usa al salvataggio delle impostazioni.
     */
    static function SvuotaCache() {
        update_option(self::OPZIONE_VERSIONE, time(), true);
        self::$memoria = [];
    }

    /** Invalida la cache breve di un singolo necrologio. */
    static function SvuotaSingolo($slug) {
        $chiave = 's_' . md5(sanitize_title($slug));
        delete_transient(self::Chiave($chiave));
        unset(self::$memoria[$chiave]);
    }

    /** Invalida la cache breve della lista. */
    static function SvuotaLista() {
        delete_transient(self::Chiave('lista'));
        unset(self::$memoria['lista']);
    }

    /** Durata della cache breve, in secondi. */
    static function Durata() {

        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        $minuti       = ($impostazioni && isset($impostazioni['seo_cache_minuti'])) ? absint($impostazioni['seo_cache_minuti']) : self::DURATA_PREDEFINITA;

        if (!in_array($minuti, self::DURATE, true)) { $minuti = self::DURATA_PREDEFINITA; }

        return (int) apply_filters('portale_funebre_necrologi_durata_cache', $minuti * MINUTE_IN_SECONDS);
    }

    /**
     * Classifica la risposta del portale per un singolo necrologio.
     * Per uno slug inesistente il portale risponde ["Necrologio non trovato"];
     * qualunque altra risposta senza dati validi e' considerata un errore,
     * cosi' un disservizio non viene mai scambiato per un necrologio rimosso.
     */
    private static function InterpretaSingolo($risposta) {

        if (is_object($risposta) && !empty($risposta->nome_defunto) && !empty($risposta->slug)) {
            return $risposta;
        }

        if (is_array($risposta)) {
            foreach ($risposta as $valore) {
                if (is_string($valore) && stripos($valore, 'non trovato') !== false) { return false; }
            }
        }

        return new WP_Error('portale_funebre_non_disponibile', 'Il portale dei necrologi non risponde.');
    }

    private static function Chiave($nome) {
        return 'pfn_' . (int) get_option(self::OPZIONE_VERSIONE, 0) . '_' . $nome;
    }

    private static function Api() {
        return PortaleFunebreNecrologi::IsConfigurato() ? new PortaleFunebreNecrologi_API() : null;
    }

}
