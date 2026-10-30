<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Dati strutturati (schema.org) del necrologio: il defunto (Person) e il
 * funerale (Event). Con Yoast vengono aggiunti al suo grafo, altrimenti il
 * plugin stampa un grafo completo.
 */
class PortaleFunebreNecrologi_Schema {

    static function IsAttivo() {
        $impostazioni = PortaleFunebreNecrologi::GetImpostazioni();
        $attivo       = !($impostazioni && isset($impostazioni['seo_schema']) && $impostazioni['seo_schema'] === '0');
        return (bool) apply_filters('portale_funebre_necrologi_schema_attivo', $attivo);
    }

    static function IdPersona(PortaleFunebreNecrologi_Necrologio $necro) {
        return $necro->Url() . '#defunto';
    }

    /** Nodi del necrologio da aggiungere a un grafo esistente. */
    static function Nodi(PortaleFunebreNecrologi_Necrologio $necro) {

        $nodi = [self::Persona($necro)];

        $funerale = self::Funerale($necro);
        if ($funerale) { $nodi[] = $funerale; }

        return (array) apply_filters('portale_funebre_necrologi_schema_nodi', $nodi, $necro);
    }

    /** Grafo completo, usato quando non c'e' un plugin SEO. */
    static function GrafoCompleto(PortaleFunebreNecrologi_Necrologio $necro) {

        $url  = $necro->Url();
        $home = home_url('/');

        $pagina = [
            '@type'       => 'WebPage',
            '@id'         => $url,
            'url'         => $url,
            'name'        => PortaleFunebreNecrologi_Seo::Titolo(),
            'description' => PortaleFunebreNecrologi_Seo::Descrizione(),
            'inLanguage'  => get_bloginfo('language'),
            'isPartOf'    => ['@id' => $home . '#website'],
            'about'       => ['@id' => self::IdPersona($necro)],
        ];
        if ($necro->Immagine()) { $pagina['thumbnailUrl'] = $necro->Immagine(); }

        $sito = [
            '@type' => 'WebSite',
            '@id'   => $home . '#website',
            'url'   => $home,
            'name'  => PortaleFunebreNecrologi_Seo::NomeSito(),
        ];

        return array_merge([$pagina, $sito], self::Nodi($necro));
    }

    private static function Persona(PortaleFunebreNecrologi_Necrologio $necro) {

        $persona = [
            '@type' => 'Person',
            '@id'   => self::IdPersona($necro),
            'name'  => $necro->Nome(),
        ];

        if ($necro->DataNascita()) { $persona['birthDate'] = $necro->DataNascita()->format('Y-m-d'); }
        if ($necro->DataMorte())   { $persona['deathDate'] = $necro->DataMorte()->format('Y-m-d'); }
        if ($necro->Immagine())    { $persona['image']     = $necro->Immagine(); }

        return $persona;
    }

    /** Il funerale come evento, solo se ci sono almeno data e luogo. */
    private static function Funerale(PortaleFunebreNecrologi_Necrologio $necro) {

        $funerale = $necro->Cerimonia('funerale');
        $inizio   = self::DataOra($funerale['data'], $funerale['ora_da']);

        if (!$inizio || !$funerale['luogo']) { return null; }

        $organizzatore = $necro->Agenzia() ? $necro->Agenzia() : PortaleFunebreNecrologi_Seo::NomeSito();

        $evento = [
            '@type'               => 'Event',
            '@id'                 => $necro->Url() . '#funerale',
            'name'                => 'Funerale di ' . $necro->Nome(),
            'startDate'           => $inizio,
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location'            => [
                '@type'   => 'Place',
                'name'    => $funerale['luogo'],
                'address' => $funerale['luogo'],
            ],
            'about'               => ['@id' => self::IdPersona($necro)],
            'organizer'           => ['@type' => 'Organization', 'name' => $organizzatore, 'url' => home_url('/')],
            'url'                 => $necro->Url(),
        ];

        $fine = self::DataOra($funerale['data'], $funerale['ora_a']);
        if ($funerale['ora_da'] && $funerale['ora_a'] && $fine > $inizio) { $evento['endDate'] = $fine; }

        if ($necro->Immagine()) { $evento['image'] = $necro->Immagine(); }

        return $evento;
    }

    /** Data (e ora, se presente) in formato ISO 8601 con il fuso orario del sito. */
    private static function DataOra($data, $ora) {

        $giorno = PortaleFunebreNecrologi_Necrologio::Data($data);

        if (!$giorno) { return ''; }

        if (!preg_match('/^(\d{1,2})[:.](\d{2})/', (string) $ora, $m)) { return $giorno->format('Y-m-d'); }

        $momento = DateTimeImmutable::createFromFormat('Y-m-d H:i', $giorno->format('Y-m-d') . ' ' . sprintf('%02d:%02d', $m[1], $m[2]), wp_timezone());

        return $momento ? $momento->format('c') : $giorno->format('Y-m-d');
    }

}
