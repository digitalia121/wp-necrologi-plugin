<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Markup dei necrologi generato lato server, cosi' che i motori di ricerca
 * trovino nell'HTML nomi, testi e link.
 *
 * Riproduce fedelmente le funzioni JavaScript storiche (crea_necrologio_loop,
 * crea_necrologio_slide, mostra_dati_cerimonia, crea_necrologio_singolo):
 * stesse classi, stessa struttura e stessa lettura delle impostazioni, in
 * modo che temi e CSS personalizzati continuino a funzionare.
 */
class PortaleFunebreNecrologi_Render {

    /**
     * Il rendering lato server e' attivo, salvo che sia stato disattivato
     * nelle impostazioni (modalita' di compatibilita').
     */
    static function IsAttivo() {
        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        $attivo       = !($impostazioni && isset($impostazioni['seo_rendering_server']) && $impostazioni['seo_rendering_server'] === '0');
        return (bool) apply_filters('portale_funebre_necrologi_rendering_server', $attivo);
    }

    /** Box della lista necrologi (crea_necrologio_loop). */
    static function Loop(PortaleFunebreNecrologi_Necrologio $necro) {

        $url     = $necro->Url();
        $bottone = self::Uguale('con_bottone', '1');
        $nome    = esc_html($necro->Nome());

        $apri  = $bottone ? '<a href="' . esc_url($url) . '">' : '';
        $chiudi = $bottone ? '</a>' : '';

        $html  = '<div class="necro-content"><picture>' . $apri . self::Img($necro, 'lazy') . $chiudi . '</picture>';
        $html .= '<div class="necro-description">' . $apri . '<h2>' . $nome . '</h2>' . $chiudi;

        $morte = $necro->DataMorte();
        $eta   = $necro->Eta();

        if (self::Uguale('mostra_eta', '1') && $eta !== null) {

            $html .= self::Vero('mostra_dettagli')
                ? '<div class="eta-defunto"><span class="titolo-eta-defunto">Età:</span> ' . $eta . ' Anni</div>'
                : '<div class="eta-defunto">' . $eta . ' Anni</div>';

            if (self::Uguale('mostra_decesso', '1') && $morte) {
                $html .= self::Vero('mostra_dettagli')
                    ? '<div class="data-decesso">Data del decesso: ' . self::DataBreve($morte) . '</div>'
                    : '<div class="data-decesso">' . self::DataBreve($morte) . '</div>';
            }
        }

        $html .= self::Cerimonia('Funerale', $necro->Cerimonia('funerale'), self::Opzione('cerimonia'));
        $html .= self::Cerimonia('Rosario', $necro->Cerimonia('rosario'), self::Opzione('rosario'));
        $html .= '</div>';

        if ($bottone) {
            $html .= '<div class="button-wrapper"><a class="button bottone-vai-al-necrologio" href="' . esc_url($url) . '"> Vai al necrologio</a></div>';
        }

        $html .= '</div>';

        return $bottone
            ? '<div class="necrologio-loop">' . $html . '</div>'
            : '<a href="' . esc_url($url) . '" class="necrologio-loop">' . $html . '</a>';
    }

    /** Slide dello slider (crea_necrologio_slide). */
    static function Slide(PortaleFunebreNecrologi_Necrologio $necro) {

        $html  = '<div class="necro-content">';
        $html .= '<picture>' . self::Img($necro, 'lazy') . '</picture>';
        $html .= '<div class="necro-description">';
        $html .= '<h2>' . esc_html($necro->Nome()) . '</h2>';

        $morte = $necro->DataMorte();
        $eta   = $necro->Eta();

        if (self::Uguale('slide_mostra_eta', '1') && $eta !== null && self::Vero('mostra_eta')) {
            $html .= '<div class="eta-defunto">' . $eta . ' Anni</div>';
        }

        if (self::Uguale('slide_mostra_decesso', '1') && $morte) {
            $html .= '<div class="data-decesso">' . self::DataBreve($morte) . '</div>';
        }

        if (self::Vero('slide_cerimonia')) {
            $html .= self::Cerimonia('Funerale', $necro->Cerimonia('funerale'), self::Opzione('cerimonia'));
        }
        if (self::Vero('slide_rosario')) {
            $html .= self::Cerimonia('Rosario', $necro->Cerimonia('rosario'), self::Opzione('rosario'));
        }

        $html .= '</div> </div>';

        return '<a href="' . esc_url($necro->Url()) . '" class="necrologio-slide">' . $html . '</a>';
    }

    /** Eta' mostrata sopra al testo del necrologio singolo. */
    static function EtaSingolo(PortaleFunebreNecrologi_Necrologio $necro) {

        $eta = $necro->Eta();

        if (!self::Uguale('eta_su_singolo', '1') || $eta === null) { return ''; }

        return self::Vero('mostra_dettagli')
            ? '<div class="eta-defunto"><span class="titolo-eta-defunto">Età:</span> ' . $eta . ' Anni</div>'
            : '<div class="eta-defunto">' . $eta . ' Anni</div>';
    }

    /** Foto del necrologio singolo, cliccabile come in passato. */
    static function FotoSingolo(PortaleFunebreNecrologi_Necrologio $necro) {
        $img = $necro->Immagine();
        if (!$img) { return ''; }
        return '<a href="' . esc_url($img) . '" target="_blank"><img src="' . esc_url($img) . '" alt="' . esc_attr(self::AltFoto($necro)) . '" fetchpriority="high"/></a>';
    }

    /** Link aggiuntivi del necrologio singolo. */
    static function Links(PortaleFunebreNecrologi_Necrologio $necro) {
        $links = $necro->Links();
        if (!$links) { return ''; }
        $html = '<h5>Altri link</h5>';
        foreach ($links as $link) {
            $html .= '<a href="' . esc_url($link['url']) . '" target="_blank">' . esc_html($link['testo']) . '</a>';
        }
        return $html;
    }

    /**
     * Dati che il JavaScript del necrologio singolo usa per le parti
     * interattive. Recapiti e IBAN restano fuori dall'HTML indicizzabile e
     * vengono inseriti nella pagina dal JavaScript, come in passato.
     */
    static function DatiScript(PortaleFunebreNecrologi_Necrologio $necro) {

        $dati = $necro->Dati();

        $out = [
            'slug'                => $necro->Slug(),
            'cordogli'            => $necro->Cordogli(),
            'contatto_principale' => ['whatsapp' => isset($dati->contatto_principale->whatsapp) ? (string) $dati->contatto_principale->whatsapp : ''],
            'mappa'               => $necro->Mappa(),
        ];

        if (self::Vero('mostra_donazioni') && isset($dati->donazioni) && is_object($dati->donazioni)) {
            $out['donazioni'] = [
                'ente_donazione'    => isset($dati->donazioni->ente_donazione) ? (string) $dati->donazioni->ente_donazione : '',
                'iban_donazione'    => isset($dati->donazioni->iban_donazione) ? (string) $dati->donazioni->iban_donazione : '',
                'codfisc_donazione' => isset($dati->donazioni->codfisc_donazione) ? (string) $dati->donazioni->codfisc_donazione : '',
            ];
        }

        return $out;
    }

    /** Dati di una cerimonia (mostra_dati_cerimonia). */
    static function Cerimonia($titolo, array $cerimonia, $formato) {

        if (!$formato) { return ''; }

        $dettagli = self::Uguale('mostra_dettagli', '1');
        $data     = '';

        if ($cerimonia['data'] || $cerimonia['ora_da']) {
            if ($dettagli) { $data .= ' <span class="titoletto">Data:</span> '; }
            $giorno = PortaleFunebreNecrologi_Necrologio::Data($cerimonia['data']);
            $data  .= '<span class="data-cerimonia">' . ($giorno ? self::DataBreve($giorno) : '') . '</span> ';
            if (self::Uguale('mostra_orari', '1')) {
                if ($dettagli) { $data .= ' - '; }
                $data .= ' <span class="ora_cerionia">' . esc_html($cerimonia['ora_da']) . '</span>';
            }
        }

        $tit   = '<h4>' . esc_html($titolo) . ':</h4>';
        $luogo = '';

        if ($cerimonia['luogo']) {
            if ($dettagli) { $luogo = ' <span class="titoletto">Luogo:</span> '; }
            $luogo .= '<span class="luogo-cerimonia">' . esc_html($cerimonia['luogo']) . '</span>';
        }

        switch ($formato) {
            case 'luogo_data':
                if (!$luogo && !$data) { return ''; }
                return $tit . '<div class="data-e-luogo"><div class="luogo-wrap">' . $luogo . '</div> <div class="data-wrap">' . $data . '</div></div>';
            case 'data_luogo':
                if (!$luogo && !$data) { return ''; }
                return $tit . '<div class="data-e-luogo"><div class="data-wrap">' . $data . '</div> <div class="luogo-wrap">' . $luogo . '</div></div>';
            case 'data':
                if (!$data) { return ''; }
                return $tit . '<div class="data-e-luogo">' . $data . '</div>';
            case 'luogo':
                if (!$luogo) { return ''; }
                return $tit . '<div class="data-e-luogo">' . $luogo . '</div>';
        }

        return '';
    }

    /**
     * Ordina la lista come ordina_necrologi_lista del JavaScript.
     *
     * @param PortaleFunebreNecrologi_Necrologio[] $necrologi
     */
    static function Ordina(array $necrologi) {

        $ordine = (string) self::Opzione('ordine_lista');

        if (!$ordine) { return $necrologi; }

        $confronta_nomi = static function ($a, $b) {
            return strcasecmp(remove_accents($a->Nome()), remove_accents($b->Nome()));
        };

        usort($necrologi, static function ($a, $b) use ($ordine, $confronta_nomi) {
            switch ($ordine) {
                case 'alfabetico_asc':  return $confronta_nomi($a, $b);
                case 'alfabetico_desc': return $confronta_nomi($b, $a);
                case 'data_decesso_asc':
                case 'data_decesso_desc':
                    $ta = $a->DataMorte();
                    $tb = $b->DataMorte();
                    if (!$ta && !$tb) { return 0; }
                    if (!$ta) { return 1; }
                    if (!$tb) { return -1; }
                    $diff = $ta->getTimestamp() <=> $tb->getTimestamp();
                    return ($ordine === 'data_decesso_desc') ? -$diff : $diff;
            }
            return 0;
        });

        return $necrologi;
    }

    /**
     * Lista dei necrologi pronta per il rendering.
     *
     * @return PortaleFunebreNecrologi_Necrologio[]|null null se il portale non risponde.
     */
    static function Necrologi() {
        $lista = PortaleFunebreNecrologi_Repository::GetLista();
        if (is_wp_error($lista)) { return null; }
        return array_map(static function ($dati) { return new PortaleFunebreNecrologi_Necrologio($dati); }, $lista);
    }

    static function AltFoto(PortaleFunebreNecrologi_Necrologio $necro) {
        return 'Foto di ' . $necro->Nome();
    }

    /** Data nel formato breve italiano usato dal JavaScript (toLocaleDateString it-IT). */
    static function DataBreve(DateTimeInterface $data) {
        return $data->format('d/m/Y');
    }

    private static function Img(PortaleFunebreNecrologi_Necrologio $necro, $loading) {
        $img = $necro->Immagine();
        if (!$img) { return ''; }
        return '<img src="' . esc_url($img) . '" alt="' . esc_attr(self::AltFoto($necro)) . '" loading="' . esc_attr($loading) . '" decoding="async"/>';
    }

    private static function Opzione($chiave) {
        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        return ($impostazioni && isset($impostazioni[$chiave])) ? $impostazioni[$chiave] : null;
    }

    /** Equivale al confronto JavaScript `settings.x == valore`. */
    static function Uguale($chiave, $valore) {
        $v = self::Opzione($chiave);
        return $v !== null && (string) (is_bool($v) ? (int) $v : $v) === $valore;
    }

    /**
     * Equivale al test JavaScript `if (settings.x)`: la stringa "0" e' vera.
     * Alcune opzioni sono sempre state lette cosi' e il loro comportamento
     * non deve cambiare.
     */
    static function Vero($chiave) {
        $v = self::Opzione($chiave);
        return !($v === null || $v === '' || $v === false || $v === 0 || $v === 0.0);
    }

}
