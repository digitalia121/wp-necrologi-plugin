<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Un necrologio letto dal portale, con i dati gia' pronti per pagina, meta
 * SEO e dati strutturati. Avvolge l'oggetto restituito dall'API senza
 * modificarlo.
 */
class PortaleFunebreNecrologi_Necrologio {

    /** Tipi di cerimonia restituiti dal portale. */
    const CERIMONIE = ['funerale', 'rosario', 'partenza', 'tumulazione_ceneri', 'chiusura_feretro'];

    private $dati;

    function __construct($dati) {
        $this->dati = $dati;
    }

    /** L'oggetto originale del portale. */
    function Dati() { return $this->dati; }

    function Slug() { return isset($this->dati->slug) ? sanitize_title($this->dati->slug) : ''; }

    function Nome() { return isset($this->dati->nome_defunto) ? trim(wp_strip_all_tags((string) $this->dati->nome_defunto)) : ''; }

    /** Testo del necrologio in HTML (filtrato). */
    function Testo() {
        return isset($this->dati->testo) ? wp_kses_post((string) $this->dati->testo) : '';
    }

    /** Testo del necrologio senza markup, con le entita' HTML decodificate. */
    function TestoSemplice() {
        $testo = html_entity_decode(wp_strip_all_tags($this->Testo()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $testo));
    }

    function Url() { return self::UrlDaSlug($this->Slug()); }

    function Immagine() {
        return !empty($this->dati->thumbnail) ? PortaleFunebreNecrologi_API::GetImgUrl($this->dati->thumbnail) : '';
    }

    /**
     * Immagine per le anteprime dei social: la copia ridotta salvata in
     * uploads, che fa usare a Facebook la scheda compatta con la foto
     * verticale a sinistra.
     *
     * @return array{url:string,width:int,height:int}|null
     */
    function ImmagineSocial() {
        $immagine = $this->Immagine();
        return $immagine ? PortaleFunebreNecrologi::GetImmagineShare($immagine) : null;
    }

    function Agenzia() {
        if (isset($this->dati->agenzia->ragione_sociale) && $this->dati->agenzia->ragione_sociale) {
            return trim((string) $this->dati->agenzia->ragione_sociale);
        }
        return '';
    }

    /** @return DateTimeImmutable|null */
    function DataMorte() { return self::Data(isset($this->dati->dati_defunto->data_morte) ? $this->dati->dati_defunto->data_morte : ''); }

    /** @return DateTimeImmutable|null */
    function DataNascita() { return self::Data(isset($this->dati->dati_defunto->data_nascita) ? $this->dati->dati_defunto->data_nascita : ''); }

    /**
     * Eta' come l'ha sempre mostrata il plugin: differenza tra gli anni di
     * morte e di nascita.
     */
    function Eta() {
        $morte   = $this->DataMorte();
        $nascita = $this->DataNascita();
        return ($morte && $nascita) ? ((int) $morte->format('Y') - (int) $nascita->format('Y')) : null;
    }

    /**
     * Dati di una cerimonia (funerale, rosario, ...), sempre con tutte le chiavi.
     *
     * @return array{luogo:string,data:string,ora_da:string,ora_a:string}
     */
    function Cerimonia($tipo) {
        $cerimonia = (isset($this->dati->$tipo) && is_object($this->dati->$tipo)) ? $this->dati->$tipo : new stdClass();
        $out       = [];
        foreach (['luogo', 'data', 'ora_da', 'ora_a'] as $campo) {
            $out[$campo] = isset($cerimonia->$campo) ? trim(wp_strip_all_tags((string) $cerimonia->$campo)) : '';
        }
        return $out;
    }

    /** Canali di cordoglio abilitati dal portale per questo necrologio. */
    function Cordogli() {
        $cordogli = isset($this->dati->cordogli) ? $this->dati->cordogli : new stdClass();
        return [
            'email'    => !empty($cordogli->email),
            'whatsapp' => !empty($cordogli->whatsapp),
            'pdf'      => !empty($cordogli->pdf),
        ];
    }

    /**
     * Quale form di cordoglio aprire, con la stessa logica storica del
     * JavaScript (crea_necrologio_singolo).
     *
     * @return array{is_mono:bool,mono_tipo:string,has_nessun_cordoglio:bool}
     */
    function StatoCordogli() {
        $c       = $this->Cordogli();
        $mono    = (bool) ((int) $c['whatsapp'] ^ (int) $c['email'] ^ (int) $c['pdf']);
        $nessuno = !$c['whatsapp'] && !$c['email'] && !$c['pdf'];
        $tipo    = '';

        if ($mono) {
            if ($c['email'])    { $tipo = 'email'; }
            if ($c['pdf'])      { $tipo = 'pdf'; }
            if ($c['whatsapp']) { $tipo = 'whatsapp'; }
        } else {
            $tipo    = 'pdf';
            $mono    = true;
            $nessuno = false;
        }

        return ['is_mono' => $mono, 'mono_tipo' => $tipo, 'has_nessun_cordoglio' => $nessuno];
    }

    function Mappa() { return isset($this->dati->mappa) ? trim((string) $this->dati->mappa) : ''; }

    /** Link aggiuntivi impostati sul portale. @return array<array{url:string,testo:string}> */
    function Links() {
        $out = [];
        if (empty($this->dati->lista_links) || !is_array($this->dati->lista_links)) { return $out; }
        foreach ($this->dati->lista_links as $link) {
            if (!is_object($link) || empty($link->url)) { continue; }
            $out[] = ['url' => (string) $link->url, 'testo' => isset($link->testo) ? (string) $link->testo : (string) $link->url];
        }
        return $out;
    }

    /** URL pubblico di un necrologio, coerente con la struttura dei permalink. */
    static function UrlDaSlug($slug) {
        return home_url(user_trailingslashit(self::SlugPagina() . '/' . sanitize_title($slug)));
    }

    /** Modello di URL per il JavaScript: %slug% viene sostituito dallo slug. */
    static function ModelloUrl() {
        return home_url(user_trailingslashit(self::SlugPagina() . '/%slug%'));
    }

    /** Slug della pagina che ospita i necrologi (impostazione "slug_singolo"). */
    static function SlugPagina() {
        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        return ($impostazioni && !empty($impostazioni['slug_singolo'])) ? $impostazioni['slug_singolo'] : 'necrologio';
    }

    /** Converte una data del portale (Y-m-d...) in DateTimeImmutable, senza fuso orario. */
    static function Data($valore) {
        if (!$valore || !preg_match('/^(\d{4}-\d{2}-\d{2})/', (string) $valore, $m)) { return null; }
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $m[1], new DateTimeZone('UTC'));
        return $data ?: null;
    }

}
