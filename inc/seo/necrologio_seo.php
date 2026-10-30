<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Impostazioni SEO del singolo necrologio (come il box di Yoast su un
 * articolo): titolo, description, frase chiave, indicizzazione, canonical e
 * anteprima social. Valgono sopra ai modelli generali della tab SEO.
 *
 * I necrologi non sono post di WordPress: i dati stanno in un'unica opzione
 * (slug => impostazioni), senza autoload, e solo per i necrologi modificati.
 */
class PortaleFunebreNecrologi_SeoNecrologio {

    const OPZIONE = 'portale_funebre_necrologi_seo_necrologi';
    const AZIONE  = 'portale_funebre_necrologi_salva_seo';

    /** Campi salvati, con il loro valore vuoto. */
    const CAMPI = [
        'titolo'             => '',
        'descrizione'        => '',
        'frase_chiave'       => '',
        'noindex'            => '',
        'canonical'          => '',
        'social_titolo'      => '',
        'social_descrizione' => '',
        'social_immagine'    => 0,
    ];

    /** @return array Impostazioni del necrologio, sempre con tutti i campi. */
    static function Get($slug) {
        $tutte = self::Tutte();
        $slug  = sanitize_title($slug);
        return array_merge(self::CAMPI, isset($tutte[$slug]) ? $tutte[$slug] : []);
    }

    /** @return array<string,array> */
    static function Tutte() {
        $tutte = get_option(self::OPZIONE, []);
        return is_array($tutte) ? $tutte : [];
    }

    /** Il necrologio puo' comparire nei risultati di ricerca. */
    static function IsIndicizzabile($slug) {
        return self::Get($slug)['noindex'] !== '1';
    }

    /** Il necrologio ha almeno un'impostazione SEO personalizzata. */
    static function IsPersonalizzato($slug) {
        return array_diff_assoc(self::Get($slug), self::CAMPI) !== [];
    }

    static function Salva($slug, array $dati) {

        $slug  = sanitize_title($slug);
        $tutte = self::Tutte();
        $pulite = self::Sanitizza($dati);

        if (array_diff_assoc($pulite, self::CAMPI)) {
            $tutte[$slug] = array_diff_assoc($pulite, self::CAMPI); // solo i campi valorizzati
        } else {
            unset($tutte[$slug]);
        }

        update_option(self::OPZIONE, $tutte, false);
    }

    static function Sanitizza(array $dati) {
        $out = self::CAMPI;
        foreach (['titolo', 'descrizione', 'frase_chiave', 'social_titolo', 'social_descrizione'] as $campo) {
            $out[$campo] = isset($dati[$campo]) ? sanitize_text_field($dati[$campo]) : '';
        }
        $out['noindex']         = !empty($dati['noindex']) ? '1' : '';
        $out['canonical']       = isset($dati['canonical']) ? esc_url_raw(trim($dati['canonical'])) : '';
        $out['social_immagine'] = isset($dati['social_immagine']) ? absint($dati['social_immagine']) : 0;
        if ($out['social_immagine'] && !wp_attachment_is_image($out['social_immagine'])) { $out['social_immagine'] = 0; }
        return $out;
    }

    /**
     * Salvataggio dal box SEO della pagina del necrologio (admin-post.php),
     * poi ritorno alla pagina con un avviso.
     */
    static function GestisciSalvataggio() {

        $slug = isset($_POST['necrologio']) ? sanitize_title(wp_unslash($_POST['necrologio'])) : '';

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Non hai i permessi per modificare queste impostazioni.', 'portale-funebre-necrologi'), 403);
        }

        check_admin_referer(self::AZIONE . '_' . $slug);

        $dati = isset($_POST['pfn_seo']) && is_array($_POST['pfn_seo']) ? wp_unslash($_POST['pfn_seo']) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitizzato in Salva().

        self::Salva($slug, $dati);

        // La pagina pubblica cambia subito: si svuota la sua cache di pagina.
        PortaleFunebreNecrologi_Monitor::SvuotaCachePagine([PortaleFunebreNecrologi_Necrologio::UrlDaSlug($slug)]);

        $ritorno = add_query_arg(
            ['necrologio' => $slug, 'aggiornato' => '1'],
            \PortaleFunebreNecrologi\get_plugin_page_url('necrologi') ?: admin_url('admin.php?page=portale-funebre-necrologi-necrologi')
        );

        $scheda = isset($_POST['pfn_scheda']) ? sanitize_key(wp_unslash($_POST['pfn_scheda'])) : '';
        if ($scheda) { $ritorno = add_query_arg('scheda', $scheda, $ritorno); }

        wp_safe_redirect($ritorno);
        exit;
    }

}
