<?php

if (!defined('ABSPATH')) { exit; }

/**
 * Form dei messaggi di cordoglio: configurazione predefinita, sanificazione,
 * rendering nel necrologio singolo e convalida lato server.
 *
 * La configurazione vive in $impostazioni['form_cordoglio'] e viene modificata
 * dall'editor della pagina Impostazioni (scripts/form-editor.js).
 */
class PortaleFunebreNecrologi_FormCordoglio {

    const MAX_CAMPI   = 60;
    const MAX_OPZIONI = 100;

    const TIPI = ['text', 'email', 'tel', 'number', 'date', 'time', 'url', 'textarea', 'select', 'radio', 'checkboxes', 'consent', 'heading', 'paragraph'];

    const TIPI_CON_OPZIONI = ['select', 'radio', 'checkboxes'];
    const TIPI_STATICI     = ['heading', 'paragraph'];

    // Nomi gia' usati dal form o dalla chiamata ajax: un campo personalizzato non puo' usarli.
    const CHIAVI_RISERVATE = ['tipo', 'slug', 'action', 'dgplugin_action', 'nonce', 'token', 'api_key', 'client_id'];

    const LIMITI_STILE = [
        'max_width'    => [280, 1600],
        'padding'      => [0, 80],
        'form_radius'  => [0, 40],
        'gap_x'        => [0, 60],
        'gap_y'        => [0, 60],
        'input_radius' => [0, 30],
        'border_width' => [0, 6],
        'label_size'   => [0, 32],
        'title_size'   => [0, 48],
        'btn_radius'   => [0, 40],
        'bg_opacity'   => [0, 100],
    ];

    const SCELTE_STILE = [
        'field_size'     => ['s', 'm', 'l', 'xl'],
        'label_position' => ['top', 'hidden'],
        'btn_align'      => ['stretch', 'left', 'center', 'right'],
    ];

    // Padding e dimensione del testo dei campi per ogni taglia ('' = eredita dal tema).
    const TAGLIE_CAMPI = [
        's'  => ['6px', '13px'],
        'm'  => ['10px', ''],
        'l'  => ['14px', '17px'],
        'xl' => ['18px', '19px'],
    ];

    // Colore => variabile CSS usata da frontend.css.
    const VARIABILI_COLORI = [
        'colore_form'          => '--pfn-cf-form-bg',
        'colore_etichette'     => '--pfn-cf-label',
        'colore_input'         => '--pfn-cf-input-bg',
        'colore_input_testo'   => '--pfn-cf-input-text',
        'colore_input_bordo'   => '--pfn-cf-input-border',
        'colore_focus'         => '--pfn-cf-focus',
        'colore_bottone'       => '--pfn-cf-btn-bg',
        'colore_bottone_testo' => '--pfn-cf-btn-text',
        'colore_link'          => '--pfn-cf-link',
        'colore_asterisco'     => '--pfn-cf-req',
    ];

    /**
     * Campi predefiniti: la chiave e' il nome con cui il dato arriva al portale
     * funebre, quindi non e' modificabile. 'tipi' elenca i tipi consentiti.
     */
    static function CampiBase() {
        return [
            'first-name' => ['label' => 'Nome',                   'type' => 'text',     'tipi' => ['text'],              'width' => 6],
            'last-name'  => ['label' => 'Cognome',                'type' => 'text',     'tipi' => ['text'],              'width' => 6],
            'email'      => ['label' => 'Email',                  'type' => 'email',    'tipi' => ['email'],             'width' => 6],
            'phone'      => ['label' => 'Telefono',               'type' => 'tel',      'tipi' => ['tel', 'text'],       'width' => 6],
            'message'    => ['label' => 'Messaggio di Cordoglio', 'type' => 'textarea', 'tipi' => ['textarea', 'text'],  'width' => 12, 'rows' => 5],
            'privacy'    => ['label' => 'Ho letto e accetto la',  'type' => 'consent',  'tipi' => ['consent'],           'width' => 12, 'link_text' => 'privacy policy'],
        ];
    }

    static function CampoVuoto() {
        return [
            'id'           => '',
            'key'          => '',
            'core'         => false,
            'enabled'      => true,
            'type'         => 'text',
            'label'        => '',
            'placeholder'  => '',
            'help'         => '',
            'required'     => false,
            'width'        => 12,
            'width_mobile' => 12,
            'new_row'      => false,
            'rows'         => 4,
            'options'      => [],
            'default'      => '',
            'min'          => '',
            'max'          => '',
            'maxlength'    => 0,
            'link_url'     => '',
            'link_text'    => '',
        ];
    }

    static function Predefinita() {

        $campi = [];

        foreach (self::CampiBase() as $key => $base) {
            $campo = array_merge(self::CampoVuoto(), array_intersect_key($base, self::CampoVuoto()));
            $campo['id']       = 'core-' . $key;
            $campo['key']      = $key;
            $campo['core']     = true;
            $campo['required'] = true;
            $campi[] = $campo;
        }

        return [
            'fields' => $campi,
            'style'  => [
                'colore_sfondo'        => '',
                'bg_opacity'           => 50,
                'colore_form'          => '',
                'colore_etichette'     => '',
                'colore_input'         => '',
                'colore_input_testo'   => '',
                'colore_input_bordo'   => '',
                'colore_focus'         => '',
                'colore_bottone'       => '',
                'colore_bottone_testo' => '',
                'colore_link'          => '',
                'colore_asterisco'     => '',
                'max_width'            => 600,
                'padding'              => 20,
                'form_radius'          => 10,
                'shadow'               => true,
                'field_size'           => 'm',
                'label_position'       => 'top',
                'label_size'           => 0,
                'title_size'           => 0,
                'input_radius'         => 5,
                'border_width'         => 1,
                'gap_x'                => 16,
                'gap_y'                => 15,
                'btn_align'            => 'stretch',
                'btn_radius'           => 5,
            ],
            'texts'  => [
                'title'         => '',
                'subtitle'      => '',
                'submit'        => 'Invia',
                'btn_email'     => 'MANDA CON EMAIL',
                'btn_whatsapp'  => 'MANDA CON WHATSAPP',
                'btn_pdf'       => 'MANDA CON PDF',
                'success_title' => 'Invio riuscito',
                'success_text'  => 'Il tuo cordoglio è stato inviato. Grazie',
                'error_title'   => 'Errore',
                'error_text'    => 'Abbiamo riscontrato un errore. Si prega di riprovare piu tardi',
                'required_msg'  => '{campo} è un campo obbligatorio',
                'email_msg'     => 'l\'email inserita è in un formato non valido',
                'privacy_msg'   => 'Devi prima accettare la privacy policy!',
            ],
            'opts'   => [
                'show_asterisk' => true,
                'append_extra'  => true,
            ],
        ];
    }

    static function GetConfig($impostazioni) {
        $salvata = ($impostazioni && isset($impostazioni['form_cordoglio'])) ? $impostazioni['form_cordoglio'] : null;
        return is_array($salvata) ? self::Sanitizza($salvata) : self::Predefinita();
    }

    /**
     * Normalizza una configurazione proveniente dall'editor (o dal database):
     * tutto cio' che non e' riconosciuto viene scartato o riportato al default.
     */
    static function Sanitizza($raw) {

        if (is_string($raw)) { $raw = json_decode($raw, true); }

        $def = self::Predefinita();

        if (!is_array($raw)) { return $def; }

        $out = ['fields' => [], 'style' => [], 'texts' => [], 'opts' => []];

        $campi  = (isset($raw['fields']) && is_array($raw['fields'])) ? $raw['fields'] : $def['fields'];
        $chiavi = [];
        $ids    = [];

        foreach (array_slice($campi, 0, self::MAX_CAMPI) as $campo) {
            $campo = self::SanitizzaCampo($campo, $chiavi, $ids);
            if ($campo) { $out['fields'][] = $campo; }
        }

        $stile = (isset($raw['style']) && is_array($raw['style'])) ? $raw['style'] : [];

        foreach ($def['style'] as $k => $predefinito) {

            $valore = array_key_exists($k, $stile) ? $stile[$k] : $predefinito;

            if (is_bool($predefinito)) {
                $out['style'][$k] = self::Bool($valore);
            } elseif (isset(self::LIMITI_STILE[$k])) {
                $out['style'][$k] = self::Intervallo($valore, self::LIMITI_STILE[$k][0], self::LIMITI_STILE[$k][1], $predefinito);
            } elseif (isset(self::SCELTE_STILE[$k])) {
                $out['style'][$k] = in_array($valore, self::SCELTE_STILE[$k], true) ? $valore : $predefinito;
            } else {
                $colore = is_string($valore) ? sanitize_hex_color($valore) : '';
                $out['style'][$k] = $colore ? $colore : '';
            }
        }

        $testi = (isset($raw['texts']) && is_array($raw['texts'])) ? $raw['texts'] : [];

        foreach ($def['texts'] as $k => $predefinito) {

            $valore = (isset($testi[$k]) && is_scalar($testi[$k])) ? (string) $testi[$k] : $predefinito;
            $valore = in_array($k, ['subtitle', 'success_text', 'error_text'], true) ? sanitize_textarea_field($valore) : sanitize_text_field($valore);

            // Titolo e sottotitolo possono restare vuoti, gli altri testi no.
            if ($valore === '' && !in_array($k, ['title', 'subtitle'], true)) { $valore = $predefinito; }

            $out['texts'][$k] = self::Taglia($valore, 1000);
        }

        $opzioni = (isset($raw['opts']) && is_array($raw['opts'])) ? $raw['opts'] : [];

        foreach ($def['opts'] as $k => $predefinito) {
            $out['opts'][$k] = array_key_exists($k, $opzioni) ? self::Bool($opzioni[$k]) : $predefinito;
        }

        return $out;
    }

    private static function SanitizzaCampo($raw, array &$chiavi, array &$ids) {

        if (!is_array($raw)) { return null; }

        $base  = self::CampiBase();
        $campo = self::CampoVuoto();

        $tipo = (isset($raw['type']) && in_array($raw['type'], self::TIPI, true)) ? $raw['type'] : 'text';
        $key  = isset($raw['key']) ? sanitize_key($raw['key']) : '';

        $statico = in_array($tipo, self::TIPI_STATICI, true);

        if (!$statico && isset($base[$key]) && !isset($chiavi[$key])) {

            // Campo predefinito: chiave bloccata e tipo limitato a quelli compatibili col portale.
            $campo['core'] = true;
            if (!in_array($tipo, $base[$key]['tipi'], true)) { $tipo = $base[$key]['type']; }

        } elseif ($statico) {

            $key = '';

        } else {

            if ($key === '') { $key = 'campo'; }
            $originale = $key;
            $n = 2;
            while (isset($chiavi[$key]) || isset($base[$key]) || in_array($key, self::CHIAVI_RISERVATE, true)) {
                $key = $originale . '_' . $n++;
            }
        }

        if ($key !== '') { $chiavi[$key] = true; }

        $id = isset($raw['id']) ? sanitize_key($raw['id']) : '';
        if ($id === '' || isset($ids[$id])) { $id = 'f' . strtolower(wp_generate_password(8, false, false)); }
        $ids[$id] = true;

        $campo['id']           = $id;
        $campo['key']          = $key;
        $campo['type']         = $tipo;
        $campo['enabled']      = isset($raw['enabled']) ? self::Bool($raw['enabled']) : true;
        $campo['label']        = self::Taglia(($tipo === 'paragraph') ? self::Testo($raw, 'label', true) : self::Testo($raw, 'label'), 1000);
        $campo['placeholder']  = self::Taglia(self::Testo($raw, 'placeholder'), 200);
        $campo['help']         = self::Taglia(self::Testo($raw, 'help'), 500);
        $campo['required']     = !$statico && isset($raw['required']) && self::Bool($raw['required']);
        $campo['width']        = self::Intervallo(isset($raw['width']) ? $raw['width'] : 12, 1, 12, 12);
        $campo['width_mobile'] = self::Intervallo(isset($raw['width_mobile']) ? $raw['width_mobile'] : 12, 1, 12, 12);
        $campo['new_row']      = isset($raw['new_row']) && self::Bool($raw['new_row']);
        $campo['rows']         = self::Intervallo(isset($raw['rows']) ? $raw['rows'] : 4, 2, 30, 4);
        $campo['default']      = self::Taglia(self::Testo($raw, 'default'), 500);
        $campo['min']          = self::Taglia(self::Testo($raw, 'min'), 20);
        $campo['max']          = self::Taglia(self::Testo($raw, 'max'), 20);
        $campo['maxlength']    = self::Intervallo(isset($raw['maxlength']) ? $raw['maxlength'] : 0, 0, 10000, 0);
        $campo['link_url']     = isset($raw['link_url']) && is_string($raw['link_url']) ? esc_url_raw($raw['link_url']) : '';
        $campo['link_text']    = self::Taglia(self::Testo($raw, 'link_text'), 200);

        $opzioni = isset($raw['options']) ? $raw['options'] : [];
        if (is_string($opzioni)) { $opzioni = preg_split('/\r\n|\r|\n/', $opzioni); }

        if (is_array($opzioni)) {
            foreach ($opzioni as $opzione) {
                if (!is_scalar($opzione)) { continue; }
                $opzione = self::Taglia(sanitize_text_field((string) $opzione), 200);
                if ($opzione !== '' && count($campo['options']) < self::MAX_OPZIONI) { $campo['options'][] = $opzione; }
            }
        }

        if ($campo['label'] === '' && $campo['core']) { $campo['label'] = $base[$key]['label']; }

        return $campo;
    }

    /**
     * Variabili CSS (applicate su .cordo-form-wrapper) che sovrascrivono lo stile
     * predefinito di frontend.css. I colori vuoti non vengono emessi, cosi'
     * resta valido il colore ereditato dalle altre impostazioni.
     */
    static function VariabiliCss(array $config) {

        $s = $config['style'];

        $vars = [
            '--pfn-cf-max-width'    => $s['max_width'] . 'px',
            '--pfn-cf-padding'      => $s['padding'] . 'px',
            '--pfn-cf-radius'       => $s['form_radius'] . 'px',
            '--pfn-cf-gap-x'        => $s['gap_x'] . 'px',
            '--pfn-cf-gap-y'        => $s['gap_y'] . 'px',
            '--pfn-cf-input-radius' => $s['input_radius'] . 'px',
            '--pfn-cf-border-width' => $s['border_width'] . 'px',
            '--pfn-cf-btn-radius'   => $s['btn_radius'] . 'px',
            '--pfn-cf-input-pad'    => self::TAGLIE_CAMPI[$s['field_size']][0],
        ];

        if (self::TAGLIE_CAMPI[$s['field_size']][1]) { $vars['--pfn-cf-input-font'] = self::TAGLIE_CAMPI[$s['field_size']][1]; }
        if ($s['label_size']) { $vars['--pfn-cf-label-size'] = $s['label_size'] . 'px'; }
        if ($s['title_size']) { $vars['--pfn-cf-title-size'] = $s['title_size'] . 'px'; }

        foreach (self::VARIABILI_COLORI as $k => $var) {
            if ($s[$k]) { $vars[$var] = $s[$k]; }
        }

        if ($s['colore_sfondo']) {
            $vars['--pfn-cf-overlay']    = self::Rgba($s['colore_sfondo'], $s['bg_opacity']);
            $vars['--pfn-cf-section-bg'] = $s['colore_sfondo'];
        }

        $css = '';
        foreach ($vars as $k => $v) { $css .= $k . ':' . $v . ';'; }

        return $css;
    }

    static function ClassiCss(array $config) {

        $s = $config['style'];

        $classi = [
            'pfn-cf-labels-' . $s['label_position'],
            'pfn-cf-btn-' . $s['btn_align'],
        ];

        if (!$s['shadow'])        { $classi[] = 'pfn-cf-no-shadow'; }
        if ($s['colore_bottone']) { $classi[] = 'pfn-cf-btn-custom'; }

        return implode(' ', $classi);
    }

    /**
     * Il consenso privacy predefinito appare solo se c'e' un link da mostrare
     * (nel campo stesso o nelle impostazioni GDPR), come nelle versioni precedenti.
     */
    static function IsVisibile(array $campo, $privacy_url) {
        if (!$campo['enabled']) { return false; }
        if ($campo['key'] === 'privacy' && !$campo['link_url'] && !$privacy_url) { return false; }
        return true;
    }

    static function AttributiDati(array $config) {
        $t = $config['texts'];
        return [
            'data-msg-required' => $t['required_msg'],
            'data-msg-email'    => $t['email_msg'],
            'data-msg-privacy'  => $t['privacy_msg'],
            'data-success-title'=> $t['success_title'],
            'data-success-text' => $t['success_text'],
            'data-error-title'  => $t['error_title'],
            'data-error-text'   => $t['error_text'],
        ];
    }

    static function RenderCampi(array $config, $privacy_url) {

        $t = $config['texts'];

        if ($t['title'] !== '')    { echo '<h3 class="pfn-cf-title">' . esc_html($t['title']) . '</h3>'; }
        if ($t['subtitle'] !== '') { echo '<p class="pfn-cf-subtitle">' . nl2br(esc_html($t['subtitle'])) . '</p>'; }

        echo '<div class="pfn-cf-grid">';

        foreach ($config['fields'] as $campo) {
            if (self::IsVisibile($campo, $privacy_url)) { self::RenderCampo($campo, $config, $privacy_url); }
        }

        echo '</div>';
    }

    private static function RenderCampo(array $c, array $config, $privacy_url) {

        $tipo   = $c['type'];
        $id     = 'pfn-cf-' . ($c['key'] !== '' ? $c['key'] : $c['id']);
        $nome   = ($tipo === 'checkboxes') ? $c['key'] . '[]' : $c['key'];
        $hidden = $config['style']['label_position'] === 'hidden';

        $classi = ['form-group', 'pfn-cf-field', 'pfn-cf-type-' . $tipo];
        if ($c['new_row'])         { $classi[] = 'pfn-cf-newrow'; }
        if ($tipo === 'consent')   { $classi[] = 'pvcy-group'; }

        $asterisco = ($c['required'] && $config['opts']['show_asterisk']) ? '<span class="pfn-cf-req" aria-hidden="true">*</span>' : '';
        $required  = $c['required'] ? ' required' : '';
        $aiuto_id  = $id . '-help';
        $aiuto     = ($c['help'] !== '') ? ' aria-describedby="' . esc_attr($aiuto_id) . '"' : '';

        $placeholder = $c['placeholder'];
        if ($placeholder === '' && $hidden) { $placeholder = $c['label']; }

        echo '<div class="' . esc_attr(implode(' ', $classi)) . '"'
            . ' style="' . esc_attr('--pfn-span:' . $c['width'] . ';--pfn-span-m:' . $c['width_mobile']) . '"'
            . ' data-type="' . esc_attr($tipo) . '"'
            . ' data-key="' . esc_attr($c['key']) . '"'
            . ' data-label="' . esc_attr($c['label']) . '"'
            . ' data-required="' . ($c['required'] ? '1' : '0') . '">';

        switch ($tipo) {

            case 'heading':
                echo '<h4 class="pfn-cf-heading-text">' . esc_html($c['label']) . '</h4>';
                break;

            case 'paragraph':
                echo '<p class="pfn-cf-paragraph-text">' . nl2br(esc_html($c['label'])) . '</p>';
                break;

            case 'consent':
                $url = $c['link_url'] ? $c['link_url'] : (($c['key'] === 'privacy') ? $privacy_url : '');
                $link = '';
                if ($c['link_text'] !== '') {
                    $link = $url
                        ? ' <a target="_blank" rel="noopener noreferrer" href="' . esc_url($url) . '">' . esc_html($c['link_text']) . '</a>'
                        : ' ' . esc_html($c['link_text']);
                }
                echo '<p><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($nome) . '"' . $required . $aiuto . '/>'
                    . '<label for="' . esc_attr($id) . '">' . esc_html($c['label']) . $link . $asterisco . '</label></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parti gia' sanificate sopra.
                break;

            case 'radio':
            case 'checkboxes':
                $tipo_input = ($tipo === 'radio') ? 'radio' : 'checkbox';
                echo '<span class="pfn-cf-label" id="' . esc_attr($id) . '-label">' . esc_html($c['label']) . $asterisco . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $asterisco e' markup statico.
                echo '<div class="pfn-cf-choices" role="group" aria-labelledby="' . esc_attr($id) . '-label"' . $aiuto . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $aiuto e' gia' escapato.
                foreach ($c['options'] as $i => $opzione) {
                    $checked = ($c['default'] !== '' && $c['default'] === $opzione) ? ' checked' : '';
                    echo '<label class="pfn-cf-choice" for="' . esc_attr($id . '-' . $i) . '">'
                        . '<input type="' . esc_attr($tipo_input) . '" id="' . esc_attr($id . '-' . $i) . '" name="' . esc_attr($nome) . '" value="' . esc_attr($opzione) . '"' . $checked . '/>'
                        . '<span>' . esc_html($opzione) . '</span></label>';
                }
                echo '</div>';
                break;

            case 'select':
                echo '<label class="pfn-cf-label" for="' . esc_attr($id) . '">' . esc_html($c['label']) . $asterisco . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $asterisco e' markup statico.
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($nome) . '"' . $required . $aiuto . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributi statici o escapati.
                echo '<option value="">' . esc_html($placeholder !== '' ? $placeholder : '—') . '</option>';
                foreach ($c['options'] as $opzione) {
                    echo '<option value="' . esc_attr($opzione) . '"' . selected($c['default'], $opzione, false) . '>' . esc_html($opzione) . '</option>';
                }
                echo '</select>';
                break;

            case 'textarea':
                echo '<label class="pfn-cf-label" for="' . esc_attr($id) . '">' . esc_html($c['label']) . $asterisco . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $asterisco e' markup statico.
                echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($nome) . '" rows="' . esc_attr($c['rows']) . '"'
                    . ($placeholder !== '' ? ' placeholder="' . esc_attr($placeholder) . '"' : '')
                    . ($c['maxlength'] ? ' maxlength="' . esc_attr($c['maxlength']) . '"' : '')
                    . $required . $aiuto . '>' . esc_textarea($c['default']) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributi statici o escapati.
                break;

            default:
                $limiti = '';
                if (in_array($tipo, ['number', 'date', 'time'], true)) {
                    if ($c['min'] !== '') { $limiti .= ' min="' . esc_attr($c['min']) . '"'; }
                    if ($c['max'] !== '') { $limiti .= ' max="' . esc_attr($c['max']) . '"'; }
                } elseif ($c['maxlength']) {
                    $limiti .= ' maxlength="' . esc_attr($c['maxlength']) . '"';
                }
                echo '<label class="pfn-cf-label" for="' . esc_attr($id) . '">' . esc_html($c['label']) . $asterisco . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $asterisco e' markup statico.
                echo '<input type="' . esc_attr($tipo) . '" id="' . esc_attr($id) . '" name="' . esc_attr($nome) . '"'
                    . ($c['default'] !== '' ? ' value="' . esc_attr($c['default']) . '"' : '')
                    . ($placeholder !== '' ? ' placeholder="' . esc_attr($placeholder) . '"' : '')
                    . $limiti . $required . $aiuto . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributi statici o escapati.
                break;
        }

        if ($c['help'] !== '' && !in_array($tipo, self::TIPI_STATICI, true)) {
            echo '<small class="pfn-cf-help" id="' . esc_attr($aiuto_id) . '">' . esc_html($c['help']) . '</small>';
        }

        echo '</div>';
    }

    /**
     * Convalida lato server: restituisce il messaggio d'errore oppure ''.
     */
    static function Valida(array $config, array $dati, $privacy_url) {

        $t = $config['texts'];

        foreach ($config['fields'] as $c) {

            if (!self::IsVisibile($c, $privacy_url) || in_array($c['type'], self::TIPI_STATICI, true)) { continue; }

            $valore = self::Valore($dati, $c['key']);

            if ($c['required'] && $valore === '') {
                if ($c['key'] === 'privacy') { return $t['privacy_msg']; }
                return str_replace('{campo}', $c['label'], $t['required_msg']);
            }

            if ($valore !== '' && $c['type'] === 'email' && !is_email($valore)) { return $t['email_msg']; }
        }

        return '';
    }

    /**
     * Prepara i dati per il portale: i campi predefiniti mantengono le loro
     * chiavi, quelli personalizzati vengono (opzionalmente) accodati al
     * messaggio cosi' da arrivare comunque alla famiglia.
     */
    static function PreparaInvio(array $config, array $dati, $privacy_url) {

        $extra = [];

        foreach ($config['fields'] as $c) {

            if ($c['core'] || in_array($c['type'], self::TIPI_STATICI, true)) { continue; }

            if (!self::IsVisibile($c, $privacy_url)) {
                unset($dati[$c['key']]);
                continue;
            }

            $valore = self::Valore($dati, $c['key']);

            if ($c['type'] === 'consent') { $valore = ($valore !== '') ? 'Sì' : 'No'; }

            $dati[$c['key']] = $valore;

            if ($valore !== '') { $extra[] = ($c['label'] !== '' ? $c['label'] : $c['key']) . ': ' . $valore; }
        }

        foreach (array_keys(self::CampiBase()) as $key) {
            if (!isset($dati[$key]) || !is_scalar($dati[$key])) { $dati[$key] = ''; }
        }

        if ($config['opts']['append_extra'] && $extra) {
            $dati['message'] = trim($dati['message'] . "\n\n" . implode("\n", $extra));
        }

        return $dati;
    }

    /** Chiavi dei campi che possono contenere testo su piu' righe. */
    static function ChiaviMultilinea(array $config) {
        $out = [];
        foreach ($config['fields'] as $c) {
            if ($c['type'] === 'textarea') { $out[] = $c['key']; }
        }
        return $out;
    }

    private static function Valore(array $dati, $key) {
        if (!isset($dati[$key])) { return ''; }
        $valore = $dati[$key];
        if (is_array($valore)) {
            $valore = implode(', ', array_filter(array_map('strval', array_filter($valore, 'is_scalar')), 'strlen'));
        }
        return is_scalar($valore) ? trim((string) $valore) : '';
    }

    private static function Testo(array $raw, $k, $multilinea = false) {
        if (!isset($raw[$k]) || !is_scalar($raw[$k])) { return ''; }
        return $multilinea ? sanitize_textarea_field((string) $raw[$k]) : sanitize_text_field((string) $raw[$k]);
    }

    private static function Taglia($testo, $max) {
        return function_exists('mb_substr') ? mb_substr($testo, 0, $max) : substr($testo, 0, $max);
    }

    private static function Intervallo($valore, $min, $max, $predefinito) {
        if (!is_numeric($valore)) { return $predefinito; }
        return max($min, min($max, (int) $valore));
    }

    private static function Bool($valore) {
        return $valore === true || $valore === 1 || $valore === '1' || $valore === 'true';
    }

    private static function Rgba($hex, $opacita) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) { $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2]; }
        return sprintf('rgba(%d, %d, %d, %s)', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), round($opacita / 100, 2));
    }

}
