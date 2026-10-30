/**
 * Editor del form di cordoglio (Impostazioni > Form di cordoglio).
 *
 * Lavora su una copia della configurazione e la serializza nel campo nascosto
 * #pfn-form-config, salvato insieme alle altre impostazioni. La sanificazione
 * definitiva avviene in PHP (inc/form_cordoglio.php): qui si cura solo
 * l'esperienza d'uso e un'anteprima fedele al frontend.
 */
jQuery(function ($) {

    const root = document.getElementById('pfn-form-editor');
    const hidden = document.getElementById('pfn-form-config');
    if (!root || !hidden) { return; }

    let DATI = {};
    try { DATI = JSON.parse(root.getAttribute('data-editor') || '{}'); } catch (e) { DATI = {}; }

    const BASE        = DATI.base || {};
    const CAMPO_VUOTO = DATI.campo_vuoto || {};
    const RISERVATE   = DATI.riservate || [];
    const CTX         = DATI.contesto || {};
    const PREDEFINITA = DATI.predefinita || { fields: [], style: {}, texts: {}, opts: {} };

    const TIPI = {
        text:       { nome: 'Testo breve',     icona: 'dashicons-editor-textcolor' },
        textarea:   { nome: 'Testo lungo',     icona: 'dashicons-editor-paragraph' },
        email:      { nome: 'Email',           icona: 'dashicons-email' },
        tel:        { nome: 'Telefono',        icona: 'dashicons-phone' },
        number:     { nome: 'Numero',          icona: 'dashicons-calculator' },
        url:        { nome: 'Link (URL)',      icona: 'dashicons-admin-links' },
        date:       { nome: 'Data',            icona: 'dashicons-calendar-alt' },
        time:       { nome: 'Ora',             icona: 'dashicons-clock' },
        select:     { nome: 'Menu a tendina',  icona: 'dashicons-arrow-down-alt2' },
        radio:      { nome: 'Scelta singola',  icona: 'dashicons-marker' },
        checkboxes: { nome: 'Scelta multipla', icona: 'dashicons-forms' },
        consent:    { nome: 'Consenso',        icona: 'dashicons-yes-alt' },
        heading:    { nome: 'Titolo sezione',  icona: 'dashicons-heading' },
        paragraph:  { nome: 'Paragrafo',       icona: 'dashicons-text' }
    };

    const GRUPPI_TIPI = [
        { titolo: 'Campi di testo', tipi: ['text', 'textarea', 'email', 'tel', 'number', 'url', 'date', 'time'] },
        { titolo: 'Scelte',         tipi: ['select', 'radio', 'checkboxes', 'consent'] },
        { titolo: 'Impaginazione',  tipi: ['heading', 'paragraph'] }
    ];

    const LARGHEZZE = [[3, '1/4'], [4, '1/3'], [6, '1/2'], [8, '2/3'], [9, '3/4'], [12, 'Intera']];

    // Specchio di TAGLIE_CAMPI e VARIABILI_COLORI in inc/form_cordoglio.php.
    const TAGLIE = { s: ['6px', '13px'], m: ['10px', ''], l: ['14px', '17px'], xl: ['18px', '19px'] };
    const VAR_COLORI = {
        colore_form:          '--pfn-cf-form-bg',
        colore_etichette:     '--pfn-cf-label',
        colore_input:         '--pfn-cf-input-bg',
        colore_input_testo:   '--pfn-cf-input-text',
        colore_input_bordo:   '--pfn-cf-input-border',
        colore_focus:         '--pfn-cf-focus',
        colore_bottone:       '--pfn-cf-btn-bg',
        colore_bottone_testo: '--pfn-cf-btn-text',
        colore_link:          '--pfn-cf-link',
        colore_asterisco:     '--pfn-cf-req'
    };

    // Colore mostrato nel selettore quando il valore e' vuoto (= stile predefinito).
    const COLORI_PREDEFINITI = {
        colore_sfondo:        CTX.colore_page || '#f9eee4',
        colore_form:          CTX.colore_box || '#f7f6ed',
        colore_etichette:     '#ffffff',
        colore_input:         '#ffffff',
        colore_input_testo:   '#1d1d1d',
        colore_input_bordo:   '#555555',
        colore_focus:         '#888888',
        colore_bottone:       '#ffffff',
        colore_bottone_testo: CTX.colore_box || '#f7f6ed',
        colore_link:          '#ffffff',
        colore_asterisco:     '#ffffff'
    };

    const TEMI = [
        { nome: 'Predefinito', colori: {} },
        { nome: 'Notte', colori: { colore_sfondo: '#0f1115', colore_form: '#1f2329', colore_etichette: '#f1f1f1', colore_input: '#2b3038', colore_input_testo: '#ffffff', colore_input_bordo: '#3b414b', colore_focus: '#c9a96e', colore_bottone: '#c9a96e', colore_bottone_testo: '#1f2329', colore_link: '#c9a96e', colore_asterisco: '#c9a96e' } },
        { nome: 'Chiaro', colori: { colore_sfondo: '#f4f1ec', colore_form: '#ffffff', colore_etichette: '#333333', colore_input: '#ffffff', colore_input_testo: '#222222', colore_input_bordo: '#d5d0c8', colore_focus: '#8a7454', colore_bottone: '#8a7454', colore_bottone_testo: '#ffffff', colore_link: '#8a7454', colore_asterisco: '#b0413e' } },
        { nome: 'Sabbia', colori: { colore_sfondo: '#efe6da', colore_form: '#6f5b45', colore_etichette: '#fff8ef', colore_input: '#fffaf3', colore_input_testo: '#3b2f22', colore_input_bordo: '#bfa98f', colore_focus: '#e9d3b3', colore_bottone: '#e9d3b3', colore_bottone_testo: '#4a3b2b', colore_link: '#ffe7c4', colore_asterisco: '#ffd29a' } },
        { nome: 'Blu notte', colori: { colore_sfondo: '#e8edf3', colore_form: '#1d3557', colore_etichette: '#ffffff', colore_input: '#f8fafc', colore_input_testo: '#1d3557', colore_input_bordo: '#9fb3c8', colore_focus: '#a8dadc', colore_bottone: '#a8dadc', colore_bottone_testo: '#1d3557', colore_link: '#a8dadc', colore_asterisco: '#f4a261' } }
    ];

    const SEZIONI_ASPETTO = [
        { titolo: 'Colori', icona: 'dashicons-art', temi: true, controlli: [
            { bind: 'style.colore_sfondo', tipo: 'colore', label: 'Sfondo dietro al form', aiuto: 'In popup è il velo sopra la pagina, in pagina è il colore della sezione.' },
            { bind: 'style.bg_opacity', tipo: 'range', label: 'Opacità del velo (solo popup)', min: 0, max: 100, unita: '%' },
            { bind: 'style.colore_form', tipo: 'colore', label: 'Sfondo del form' },
            { bind: 'style.colore_etichette', tipo: 'colore', label: 'Etichette e testi' },
            { bind: 'style.colore_input', tipo: 'colore', label: 'Sfondo dei campi' },
            { bind: 'style.colore_input_testo', tipo: 'colore', label: 'Testo dei campi' },
            { bind: 'style.colore_input_bordo', tipo: 'colore', label: 'Bordo dei campi' },
            { bind: 'style.colore_focus', tipo: 'colore', label: 'Bordo del campo attivo' },
            { bind: 'style.colore_bottone', tipo: 'colore', label: 'Sfondo dei bottoni' },
            { bind: 'style.colore_bottone_testo', tipo: 'colore', label: 'Testo dei bottoni' },
            { bind: 'style.colore_link', tipo: 'colore', label: 'Link' },
            { bind: 'style.colore_asterisco', tipo: 'colore', label: 'Asterisco obbligatorio' }
        ] },
        { titolo: 'Contenitore', icona: 'dashicons-align-center', controlli: [
            { bind: 'style.max_width', tipo: 'range', label: 'Larghezza massima', min: 280, max: 1600, step: 10, unita: 'px' },
            { bind: 'style.padding', tipo: 'range', label: 'Spazio interno', min: 0, max: 80, unita: 'px' },
            { bind: 'style.form_radius', tipo: 'range', label: 'Arrotondamento angoli', min: 0, max: 40, unita: 'px' },
            { bind: 'style.shadow', tipo: 'toggle', label: 'Ombra attorno al form' }
        ] },
        { titolo: 'Campi', icona: 'dashicons-editor-table', controlli: [
            { bind: 'style.field_size', tipo: 'seg', label: 'Dimensione dei campi', scelte: [['s', 'S'], ['m', 'M'], ['l', 'L'], ['xl', 'XL']] },
            { bind: 'style.label_position', tipo: 'seg', label: 'Etichette', scelte: [['top', 'Sopra il campo'], ['hidden', 'Nascoste (segnaposto)']], aiuto: 'Con le etichette nascoste, se manca un segnaposto viene usata l\'etichetta.' },
            { bind: 'style.label_size', tipo: 'range', label: 'Dimensione etichette', min: 0, max: 32, unita: 'px', auto: true },
            { bind: 'style.input_radius', tipo: 'range', label: 'Arrotondamento campi', min: 0, max: 30, unita: 'px' },
            { bind: 'style.border_width', tipo: 'range', label: 'Spessore bordo', min: 0, max: 6, unita: 'px' },
            { bind: 'style.gap_x', tipo: 'range', label: 'Spazio tra le colonne', min: 0, max: 60, unita: 'px' },
            { bind: 'style.gap_y', tipo: 'range', label: 'Spazio tra le righe', min: 0, max: 60, unita: 'px' }
        ] },
        { titolo: 'Titolo e bottone', icona: 'dashicons-button', controlli: [
            { bind: 'style.title_size', tipo: 'range', label: 'Dimensione del titolo', min: 0, max: 48, unita: 'px', auto: true },
            { bind: 'style.btn_align', tipo: 'seg', label: 'Bottone di invio', scelte: [['stretch', 'Intero'], ['left', 'Sinistra'], ['center', 'Centro'], ['right', 'Destra']] },
            { bind: 'style.btn_radius', tipo: 'range', label: 'Arrotondamento bottoni', min: 0, max: 40, unita: 'px' }
        ] }
    ];

    const SEZIONI_TESTI = [
        { titolo: 'Intestazione del form', icona: 'dashicons-heading', controlli: [
            { bind: 'texts.title', tipo: 'testo', label: 'Titolo', placeholder: 'Nessun titolo' },
            { bind: 'texts.subtitle', tipo: 'area', label: 'Testo introduttivo', placeholder: 'Nessun testo' }
        ] },
        { titolo: 'Bottoni', icona: 'dashicons-button', controlli: [
            { bind: 'texts.submit', tipo: 'testo', label: 'Bottone di invio' },
            { bind: 'texts.btn_email', tipo: 'testo', label: 'Scelta "Email"' },
            { bind: 'texts.btn_whatsapp', tipo: 'testo', label: 'Scelta "WhatsApp"' },
            { bind: 'texts.btn_pdf', tipo: 'testo', label: 'Scelta "PDF"', aiuto: 'Le tre scelte compaiono prima del form quando il necrologio accetta più modalità di invio.' }
        ] },
        { titolo: 'Dopo l\'invio', icona: 'dashicons-yes', controlli: [
            { bind: 'texts.success_title', tipo: 'testo', label: 'Titolo di conferma' },
            { bind: 'texts.success_text', tipo: 'area', label: 'Testo di conferma' },
            { bind: 'texts.error_title', tipo: 'testo', label: 'Titolo di errore' },
            { bind: 'texts.error_text', tipo: 'area', label: 'Testo di errore' }
        ] },
        { titolo: 'Messaggi di convalida', icona: 'dashicons-warning', controlli: [
            { bind: 'texts.required_msg', tipo: 'testo', label: 'Campo obbligatorio mancante', aiuto: '{campo} viene sostituito con l\'etichetta del campo.' },
            { bind: 'texts.email_msg', tipo: 'testo', label: 'Email non valida' },
            { bind: 'texts.privacy_msg', tipo: 'testo', label: 'Privacy non accettata' }
        ] },
        { titolo: 'Comportamento', icona: 'dashicons-admin-settings', controlli: [
            { bind: 'opts.show_asterisk', tipo: 'toggle', label: 'Mostra l\'asterisco sui campi obbligatori' },
            { bind: 'opts.append_extra', tipo: 'toggle', label: 'Accoda i campi personalizzati al messaggio', aiuto: 'Il portale funebre conosce solo i campi predefiniti: con questa opzione le risposte ai campi aggiunti vengono scritte in fondo al messaggio ("Etichetta: valore"), così arrivano comunque alla famiglia.' }
        ] }
    ];

    /* ------------------------------------------------------------------ */
    /* Utilita'                                                            */
    /* ------------------------------------------------------------------ */

    const clone = function (o) { return JSON.parse(JSON.stringify(o)); };
    const esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    const nl2br = function (s) { return esc(s).replace(/\n/g, '<br>'); };
    const uid = function () { return 'f' + Math.random().toString(36).slice(2, 10); };
    const isStatico = function (t) { return t === 'heading' || t === 'paragraph'; };
    const haOpzioni = function (t) { return t === 'select' || t === 'radio' || t === 'checkboxes'; };
    const haSegnaposto = function (t) { return ['text', 'textarea', 'email', 'tel', 'number', 'url', 'select'].indexOf(t) !== -1; };
    const haLunghezza = function (t) { return ['text', 'textarea', 'email', 'tel', 'url'].indexOf(t) !== -1; };
    const haLimiti = function (t) { return t === 'number' || t === 'date' || t === 'time'; };
    const haPredefinito = function (t) { return t !== 'consent' && t !== 'checkboxes' && !isStatico(t); };
    const intero = function (v, min, max, def) {
        let n = parseInt(v, 10);
        if (isNaN(n)) { return def; }
        return Math.max(min, Math.min(max, n));
    };
    const nomeLarghezza = function (w) {
        for (let i = 0; i < LARGHEZZE.length; i++) { if (LARGHEZZE[i][0] === w) { return LARGHEZZE[i][1]; } }
        return Math.round(w / 12 * 100) + '%';
    };
    const slug = function (v) {
        return String(v || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
            .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 40);
    };
    const pulisciChiave = function (v) { return String(v || '').toLowerCase().replace(/[^a-z0-9_-]/g, ''); };
    const hex = function (v) {
        let m = String(v || '').trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (!m) { return null; }
        let h = m[1].toLowerCase();
        if (h.length === 3) { h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2]; }
        return '#' + h;
    };
    const rgba = function (h, opacita) {
        h = hex(h).slice(1);
        return 'rgba(' + parseInt(h.slice(0, 2), 16) + ', ' + parseInt(h.slice(2, 4), 16) + ', ' + parseInt(h.slice(4, 6), 16) + ', ' + Math.round(opacita) / 100 + ')';
    };

    const normalizza = function (cfg) {
        cfg = (cfg && typeof cfg === 'object') ? cfg : {};
        let ids = {};
        let campi = (Array.isArray(cfg.fields) ? cfg.fields : clone(PREDEFINITA.fields))
            .filter(function (f) { return f && typeof f === 'object' && TIPI[f.type]; })
            .map(function (f) {
                let c = Object.assign(clone(CAMPO_VUOTO), f);
                c.options = Array.isArray(f.options) ? f.options.map(String) : [];
                if (!c.id || ids[c.id]) { c.id = uid(); }
                ids[c.id] = true;
                return c;
            });
        return {
            fields: campi,
            style:  Object.assign(clone(PREDEFINITA.style), cfg.style || {}),
            texts:  Object.assign(clone(PREDEFINITA.texts), cfg.texts || {}),
            opts:   Object.assign(clone(PREDEFINITA.opts), cfg.opts || {})
        };
    };

    const leggi = function (path) {
        let p = path.split('.');
        return config[p[0]][p[1]];
    };
    const scrivi = function (path, v) {
        let p = path.split('.');
        config[p[0]][p[1]] = v;
    };

    /* ------------------------------------------------------------------ */
    /* Stato                                                               */
    /* ------------------------------------------------------------------ */

    let config = normalizza(DATI.config || PREDEFINITA);
    let dirty  = false;
    let autoKey = {};       // id => true finche' la chiave segue l'etichetta
    let selezionato = null;
    let timerToast = null;

    let ui = { tab: 'campi', aperti: [], device: 'desktop', modo: CTX.popup ? 'popup' : 'inline', contorni: false };
    try {
        let salvato = JSON.parse(window.sessionStorage.getItem('pfn-form-editor-ui') || 'null');
        if (salvato) { ui = Object.assign(ui, salvato); }
    } catch (e) { /* storage non disponibile */ }

    const salvaUi = function () {
        try { window.sessionStorage.setItem('pfn-form-editor-ui', JSON.stringify(ui)); } catch (e) { /* ignora */ }
    };

    const trova = function (id) {
        for (let i = 0; i < config.fields.length; i++) { if (config.fields[i].id === id) { return config.fields[i]; } }
        return null;
    };
    const indice = function (id) {
        for (let i = 0; i < config.fields.length; i++) { if (config.fields[i].id === id) { return i; } }
        return -1;
    };

    const chiaveUnica = function (k, id) {
        k = k || 'campo';
        let base = k, n = 2;
        let usata = function (c) {
            if (RISERVATE.indexOf(c) !== -1 || BASE[c] !== undefined) { return true; }
            return config.fields.some(function (f) { return f.id !== id && f.key === c && !isStatico(f.type); });
        };
        while (usata(k)) { k = base + '_' + n++; }
        return k;
    };

    const sincronizza = function () { hidden.value = JSON.stringify(config); };

    const modificato = function () {
        dirty = true;
        sincronizza();
        $root.find('.pfn-fe-dirty').prop('hidden', false);
        renderAvvisi();
        renderAnteprima();
    };

    /* ------------------------------------------------------------------ */
    /* Struttura                                                           */
    /* ------------------------------------------------------------------ */

    const $root = $(root);

    root.innerHTML =
        '<div class="pfn-fe-top">' +
            '<div class="pfn-fe-tabs" role="tablist">' +
                '<button type="button" role="tab" class="pfn-fe-tab" data-tab="campi"><span class="dashicons dashicons-list-view"></span>Campi</button>' +
                '<button type="button" role="tab" class="pfn-fe-tab" data-tab="aspetto"><span class="dashicons dashicons-admin-appearance"></span>Aspetto</button>' +
                '<button type="button" role="tab" class="pfn-fe-tab" data-tab="testi"><span class="dashicons dashicons-editor-quote"></span>Testi e messaggi</button>' +
            '</div>' +
            '<div class="pfn-fe-top-actions">' +
                '<span class="pfn-fe-dirty" hidden>Modifiche non salvate</span>' +
                '<div class="pfn-fe-menu-wrap">' +
                    '<button type="button" class="button pfn-fe-menu-btn" aria-haspopup="true" aria-expanded="false" title="Altre azioni"><span class="dashicons dashicons-ellipsis"></span></button>' +
                    '<div class="pfn-fe-menu" hidden>' +
                        '<button type="button" data-menu="export"><span class="dashicons dashicons-download"></span>Esporta configurazione</button>' +
                        '<button type="button" data-menu="import"><span class="dashicons dashicons-upload"></span>Importa configurazione</button>' +
                        '<hr>' +
                        '<button type="button" data-menu="reset" class="is-danger"><span class="dashicons dashicons-image-rotate"></span>Ripristina form predefinito</button>' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="button button-primary pfn-fe-save">Salva modifiche</button>' +
            '</div>' +
        '</div>' +
        '<div class="pfn-fe-body">' +
            '<div class="pfn-fe-panel">' +
                '<section class="pfn-fe-section" data-panel="campi">' +
                    '<div class="pfn-fe-fields-head">' +
                        '<div><h3>Campi del form</h3><p class="description">Trascina <span class="dashicons dashicons-menu"></span> per riordinare. La larghezza decide quali campi stanno sulla stessa riga.</p></div>' +
                        '<div class="pfn-fe-add-wrap">' +
                            '<button type="button" class="button pfn-fe-add-btn" aria-expanded="false"><span class="dashicons dashicons-plus-alt2"></span>Aggiungi campo</button>' +
                            '<div class="pfn-fe-palette" hidden></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="pfn-fe-notices"></div>' +
                    '<ul class="pfn-fe-fields"></ul>' +
                    '<div class="pfn-fe-empty" hidden><span class="dashicons dashicons-forms"></span><p>Il form non ha campi.</p><button type="button" class="button pfn-fe-add-btn-alt">Aggiungi il primo campo</button></div>' +
                '</section>' +
                '<section class="pfn-fe-section" data-panel="aspetto"></section>' +
                '<section class="pfn-fe-section" data-panel="testi"></section>' +
            '</div>' +
            '<aside class="pfn-fe-preview" aria-label="Anteprima del form">' +
                '<div class="pfn-fe-preview-bar">' +
                    '<strong>Anteprima</strong>' +
                    '<div class="pfn-fe-seg is-small" data-ui="device">' +
                        '<button type="button" data-val="desktop" title="Desktop"><span class="dashicons dashicons-desktop"></span></button>' +
                        '<button type="button" data-val="mobile" title="Mobile"><span class="dashicons dashicons-smartphone"></span></button>' +
                    '</div>' +
                    '<div class="pfn-fe-seg is-small" data-ui="modo">' +
                        '<button type="button" data-val="popup">Popup</button>' +
                        '<button type="button" data-val="inline">In pagina</button>' +
                    '</div>' +
                    '<label class="pfn-fe-toggle is-small" title="Mostra la griglia dei campi"><input type="checkbox" data-ui="contorni"><span class="pfn-fe-toggle-ui"></span><span>Griglia</span></label>' +
                '</div>' +
                '<div class="pfn-fe-stage"><div class="pfn-fe-scaler"><div class="pfn-fe-canvas"></div></div></div>' +
                '<p class="pfn-fe-preview-note"><span class="dashicons dashicons-info-outline"></span>Clicca un campo dell\'anteprima per modificarlo. La modalità reale (popup o in pagina) si sceglie in "Necrologio singolo".</p>' +
            '</aside>' +
        '</div>' +
        '<div class="pfn-fe-toast" role="status" aria-live="polite" hidden><span class="pfn-fe-toast-text"></span><button type="button" class="pfn-fe-toast-btn"></button></div>' +
        '<input type="file" accept="application/json,.json" class="pfn-fe-import-file" hidden>';

    const $lista  = $root.find('.pfn-fe-fields');
    const stage   = root.querySelector('.pfn-fe-stage');
    const scaler  = root.querySelector('.pfn-fe-scaler');
    const canvas  = root.querySelector('.pfn-fe-canvas');

    /* ------------------------------------------------------------------ */
    /* Controlli generici                                                  */
    /* ------------------------------------------------------------------ */

    const ctl = function (label, controllo, aiuto, classe) {
        return '<div class="pfn-fe-ctl' + (classe ? ' ' + classe : '') + '">' +
            '<span class="pfn-fe-ctl-label">' + label + '</span>' + controllo +
            (aiuto ? '<p class="pfn-fe-help">' + aiuto + '</p>' : '') +
        '</div>';
    };

    const seg = function (attr, valore, scelte, conBarra) {
        return '<div class="pfn-fe-seg" ' + attr + ' role="group">' + scelte.map(function (s) {
            let attivo = String(s[0]) === String(valore);
            let barra = conBarra ? '<i style="--w:' + (s[0] / 12) + '"></i>' : '';
            return '<button type="button" data-val="' + esc(s[0]) + '" class="' + (attivo ? 'is-active' : '') + '" aria-pressed="' + attivo + '">' + barra + '<span>' + esc(s[1]) + '</span></button>';
        }).join('') + '</div>';
    };

    const toggle = function (attr, attivo, label) {
        return '<label class="pfn-fe-toggle"><input type="checkbox" ' + attr + (attivo ? ' checked' : '') + '><span class="pfn-fe-toggle-ui"></span><span>' + label + '</span></label>';
    };

    const htmlControllo = function (c) {
        let v = leggi(c.bind);
        let attr = 'data-bind="' + c.bind + '"';
        switch (c.tipo) {
            case 'colore':
                let k = c.bind.split('.')[1];
                return ctl(c.label,
                    '<div class="pfn-fe-color' + (v ? '' : ' is-default') + '" ' + attr + '>' +
                        '<input type="color" class="pfn-fe-color-swatch" value="' + esc(v || COLORI_PREDEFINITI[k] || '#ffffff') + '" aria-label="' + esc(c.label) + '">' +
                        '<input type="text" class="code pfn-fe-color-hex" spellcheck="false" placeholder="Predefinito" value="' + esc(v) + '">' +
                        '<button type="button" class="pfn-fe-icon-btn pfn-fe-color-reset" title="Torna al colore predefinito"><span class="dashicons dashicons-image-rotate"></span></button>' +
                    '</div>', c.aiuto);
            case 'range':
                let testo = (c.auto && !v) ? '' : v;
                return ctl(c.label,
                    '<div class="pfn-fe-range" ' + attr + ' data-min="' + c.min + '" data-max="' + c.max + '">' +
                        '<input type="range" min="' + c.min + '" max="' + c.max + '" step="' + (c.step || 1) + '" value="' + esc(v) + '" aria-label="' + esc(c.label) + '">' +
                        '<span class="pfn-fe-range-val"><input type="text" inputmode="numeric" value="' + esc(testo) + '"' + (c.auto ? ' placeholder="Auto"' : '') + '><em>' + c.unita + '</em></span>' +
                    '</div>', c.aiuto);
            case 'seg':
                return ctl(c.label, seg(attr, v, c.scelte), c.aiuto);
            case 'toggle':
                return ctl('', toggle(attr, v, c.label), c.aiuto, 'is-toggle');
            case 'area':
                return ctl(c.label, '<textarea rows="3" ' + attr + ' placeholder="' + esc(c.placeholder || '') + '">' + esc(v) + '</textarea>', c.aiuto);
            default:
                return ctl(c.label, '<input type="text" ' + attr + ' value="' + esc(v) + '" placeholder="' + esc(c.placeholder || '') + '">', c.aiuto);
        }
    };

    const renderSezioni = function (panel, sezioni) {
        let h = '';
        sezioni.forEach(function (s) {
            h += '<div class="pfn-fe-card"><h4><span class="dashicons ' + s.icona + '"></span>' + s.titolo + '</h4>';
            if (s.temi) {
                h += '<div class="pfn-fe-themes"><span class="pfn-fe-ctl-label">Combinazioni rapide</span><div>' + TEMI.map(function (t, i) {
                    let c = t.colori;
                    let pallini = ['colore_form', 'colore_etichette', 'colore_bottone'].map(function (k) {
                        return '<i style="background:' + esc(c[k] || COLORI_PREDEFINITI[k]) + '"></i>';
                    }).join('');
                    return '<button type="button" class="pfn-fe-theme" data-tema="' + i + '"><span class="pfn-fe-theme-dots" style="background:' + esc(c.colore_sfondo || COLORI_PREDEFINITI.colore_sfondo) + '">' + pallini + '</span>' + esc(t.nome) + '</button>';
                }).join('') + '</div></div>';
            }
            h += '<div class="pfn-fe-card-grid">' + s.controlli.map(htmlControllo).join('') + '</div></div>';
        });
        $root.find('[data-panel="' + panel + '"]').html(h);
    };

    const renderPannelli = function () {
        renderSezioni('aspetto', SEZIONI_ASPETTO);
        renderSezioni('testi', SEZIONI_TESTI);
    };

    /* ------------------------------------------------------------------ */
    /* Lista campi                                                         */
    /* ------------------------------------------------------------------ */

    const htmlTesta = function (f, i) {
        let t = TIPI[f.type] || TIPI.text;
        let aperto = ui.aperti.indexOf(f.id) !== -1;
        let titolo = f.label ? (f.label.length > 70 ? f.label.slice(0, 70) + '…' : f.label) : '(senza testo)';
        let ultimo = i === config.fields.length - 1;
        return '' +
            '<span class="pfn-fe-handle dashicons dashicons-menu" title="Trascina per riordinare" aria-hidden="true"></span>' +
            '<span class="pfn-fe-ficon dashicons ' + t.icona + '" aria-hidden="true"></span>' +
            '<button type="button" class="pfn-fe-field-title" data-act="toggle" aria-expanded="' + aperto + '">' +
                '<span class="pfn-fe-field-name">' + esc(titolo) + (f.required ? '<em class="pfn-fe-req">*</em>' : '') + '</span>' +
                '<span class="pfn-fe-field-meta">' + esc(t.nome) + (!isStatico(f.type) && f.key ? ' · <code>' + esc(f.key) + '</code>' : '') + (f.enabled ? '' : ' · nascosto') + '</span>' +
            '</button>' +
            '<span class="pfn-fe-chips">' +
                (f.core ? '<span class="pfn-fe-chip is-core" title="Campo predefinito: il dato viene inviato al portale funebre">Portale</span>' : '') +
                (f.new_row ? '<span class="pfn-fe-chip" title="Inizia su una nuova riga"><span class="dashicons dashicons-editor-break"></span></span>' : '') +
                '<span class="pfn-fe-chip is-width" title="Larghezza su desktop"><i style="--w:' + (f.width / 12) + '"></i>' + esc(nomeLarghezza(f.width)) + '</span>' +
            '</span>' +
            (isStatico(f.type)
                ? '<span class="pfn-fe-req-toggle is-empty"></span>'
                : '<label class="pfn-fe-req-toggle" title="Campo obbligatorio"><input type="checkbox" data-quick="required"' + (f.required ? ' checked' : '') + '><span>Obbligatorio</span></label>') +
            '<span class="pfn-fe-actions">' +
                '<button type="button" class="pfn-fe-icon-btn" data-act="up" title="Sposta su"' + (i === 0 ? ' disabled' : '') + '><span class="dashicons dashicons-arrow-up-alt2"></span></button>' +
                '<button type="button" class="pfn-fe-icon-btn" data-act="down" title="Sposta giù"' + (ultimo ? ' disabled' : '') + '><span class="dashicons dashicons-arrow-down-alt2"></span></button>' +
                '<button type="button" class="pfn-fe-icon-btn" data-act="visibility" title="' + (f.enabled ? 'Nascondi dal form (senza eliminarlo)' : 'Mostra nel form') + '"><span class="dashicons ' + (f.enabled ? 'dashicons-visibility' : 'dashicons-hidden') + '"></span></button>' +
                '<button type="button" class="pfn-fe-icon-btn" data-act="duplicate" title="Duplica"><span class="dashicons dashicons-admin-page"></span></button>' +
                '<button type="button" class="pfn-fe-icon-btn is-danger" data-act="delete" title="Elimina"><span class="dashicons dashicons-trash"></span></button>' +
            '</span>';
    };

    const htmlCorpo = function (f) {
        let t = f.type;
        let base = f.core ? BASE[f.key] : null;
        let tipi = base ? base.tipi : Object.keys(TIPI);
        let h = '';

        let selTipo = '<select data-prop="type"' + (tipi.length < 2 ? ' disabled' : '') + '>' + tipi.map(function (k) {
            return '<option value="' + k + '"' + (k === t ? ' selected' : '') + '>' + esc(TIPI[k].nome) + '</option>';
        }).join('') + '</select>';

        h += '<div class="pfn-fe-grid2">';
        h += ctl('Tipo di campo', selTipo, (base && tipi.length < 2) ? 'Tipo fisso, richiesto dal portale funebre.' : '');
        if (!isStatico(t)) {
            h += f.core
                ? ctl('Nome tecnico', '<div class="pfn-fe-locked"><span class="dashicons dashicons-lock"></span><code>' + esc(f.key) + '</code></div>', 'È la chiave con cui il dato arriva al portale: non si può cambiare.')
                : ctl('Nome tecnico', '<input type="text" class="code" data-prop="key" spellcheck="false" value="' + esc(f.key) + '">', 'Si genera dall\'etichetta. Solo minuscole, numeri, - e _.');
        }
        h += '</div>';

        if (t === 'paragraph') {
            h += ctl('Testo', '<textarea rows="3" data-prop="label">' + esc(f.label) + '</textarea>');
        } else {
            let nomeEtichetta = (t === 'heading') ? 'Testo del titolo' : (t === 'consent' ? 'Testo del consenso' : 'Etichetta');
            h += ctl(nomeEtichetta, '<input type="text" data-prop="label" value="' + esc(f.label) + '">');
        }

        if (t === 'consent') {
            let privacy = f.key === 'privacy';
            h += '<div class="pfn-fe-grid2">';
            h += ctl('Testo del link', '<input type="text" data-prop="link_text" value="' + esc(f.link_text) + '" placeholder="es. privacy policy">');
            // type="text": un URL incompleto non deve bloccare l'invio del form delle impostazioni.
            h += ctl('Indirizzo del link', '<input type="text" inputmode="url" spellcheck="false" data-prop="link_url" value="' + esc(f.link_url) + '" placeholder="' + esc(privacy ? (CTX.link_gdpr || 'https://') : 'https://') + '">');
            h += '</div>';
            if (privacy) {
                h += '<p class="pfn-fe-help">Se l\'indirizzo è vuoto viene usato il link della Privacy Policy di "Integrazione API". Senza alcun link il consenso privacy non viene mostrato.</p>';
            }
        }

        if (haSegnaposto(t)) {
            h += ctl(t === 'select' ? 'Voce iniziale del menu' : 'Segnaposto', '<input type="text" data-prop="placeholder" value="' + esc(f.placeholder) + '" placeholder="' + (t === 'select' ? '—' : 'Testo d\'esempio dentro al campo') + '">');
        }

        if (haOpzioni(t)) {
            h += ctl('Opzioni', '<textarea rows="5" data-prop="options" placeholder="Una opzione per riga">' + esc(f.options.join('\n')) + '</textarea>', 'Una opzione per riga.');
        }

        if (haPredefinito(t)) {
            if (haOpzioni(t)) {
                h += ctl('Valore predefinito', '<select data-prop="default"><option value="">Nessuno</option>' + f.options.map(function (o) {
                    return '<option value="' + esc(o) + '"' + (o === f.default ? ' selected' : '') + '>' + esc(o) + '</option>';
                }).join('') + '</select>');
            } else {
                let tipoInput = (t === 'date' || t === 'time') ? t : 'text';
                h += ctl('Valore predefinito', '<input type="' + tipoInput + '" data-prop="default" value="' + esc(f.default) + '">');
            }
        }

        if (t === 'textarea' || haLimiti(t) || haLunghezza(t)) {
            h += '<div class="pfn-fe-grid2">';
            if (t === 'textarea') {
                h += ctl('Altezza (righe)', '<div class="pfn-fe-range" data-prop-range="rows" data-min="2" data-max="20"><input type="range" min="2" max="20" value="' + f.rows + '"><span class="pfn-fe-range-val"><input type="text" inputmode="numeric" value="' + f.rows + '"><em>righe</em></span></div>');
            }
            if (haLimiti(t)) {
                let tipoLim = (t === 'number') ? 'text" inputmode="decimal' : t;
                h += ctl('Minimo', '<input type="' + tipoLim + '" data-prop="min" value="' + esc(f.min) + '">');
                h += ctl('Massimo', '<input type="' + tipoLim + '" data-prop="max" value="' + esc(f.max) + '">');
            }
            if (haLunghezza(t)) {
                h += ctl('Lunghezza massima', '<input type="text" inputmode="numeric" data-prop="maxlength" value="' + (f.maxlength || '') + '" placeholder="Nessun limite">');
            }
            h += '</div>';
        }

        if (!isStatico(t)) {
            h += ctl('Testo di aiuto', '<input type="text" data-prop="help" value="' + esc(f.help) + '" placeholder="Compare in piccolo sotto il campo">');
        }

        h += '<div class="pfn-fe-subtitle"><span class="dashicons dashicons-layout"></span>Impaginazione</div>';
        h += ctl('Larghezza su desktop', seg('data-prop="width"', f.width, LARGHEZZE, true));
        h += ctl('Larghezza su mobile', seg('data-prop="width_mobile"', f.width_mobile, LARGHEZZE, true), 'Sotto i 600px di schermo.');
        h += '<div class="pfn-fe-toggles">';
        h += toggle('data-prop="new_row"', f.new_row, 'Inizia sempre su una nuova riga');
        if (!isStatico(t)) { h += toggle('data-prop="required"', f.required, 'Campo obbligatorio'); }
        h += '</div>';

        return h;
    };

    const htmlCampo = function (f, i) {
        let aperto = ui.aperti.indexOf(f.id) !== -1;
        let classi = ['pfn-fe-field'];
        if (aperto) { classi.push('is-open'); }
        if (!f.enabled) { classi.push('is-disabled'); }
        if (selezionato === f.id) { classi.push('is-selected'); }
        return '<li class="' + classi.join(' ') + '" data-id="' + esc(f.id) + '">' +
            '<div class="pfn-fe-field-head">' + htmlTesta(f, i) + '</div>' +
            '<div class="pfn-fe-field-body">' + (aperto ? htmlCorpo(f) : '') + '</div>' +
        '</li>';
    };

    const renderCampi = function () {
        $lista.html(config.fields.map(htmlCampo).join(''));
        $root.find('.pfn-fe-empty').prop('hidden', config.fields.length > 0);
        if ($lista.data('ui-sortable')) { $lista.sortable('refresh'); }
    };

    const aggiornaTesta = function (id) {
        let f = trova(id);
        let $li = $lista.children('[data-id="' + id + '"]');
        if (!f || !$li.length) { return; }
        $li.children('.pfn-fe-field-head').html(htmlTesta(f, indice(id)));
        $li.toggleClass('is-disabled', !f.enabled);
    };

    const aggiornaCorpo = function (id) {
        let f = trova(id);
        let $li = $lista.children('[data-id="' + id + '"]');
        if (f && $li.hasClass('is-open')) { $li.children('.pfn-fe-field-body').html(htmlCorpo(f)); }
    };

    const renderAvvisi = function () {
        let avvisi = [];
        let attivo = function (k) { return config.fields.some(function (f) { return f.key === k && f.enabled && !isStatico(f.type); }); };

        if (!attivo('message')) {
            avvisi.push(['warning', 'Il campo <strong>Messaggio</strong> non è nel form: alla famiglia arriveranno ' + (config.opts.append_extra ? 'solo le risposte ai campi personalizzati.' : 'solo i dati anagrafici.')]);
        }
        if (!attivo('email') && !attivo('phone')) {
            avvisi.push(['info', 'Non ci sono né <strong>Email</strong> né <strong>Telefono</strong>: la famiglia non potrà ricontattare chi scrive.']);
        }
        config.fields.forEach(function (f) {
            if (f.enabled && f.key === 'privacy' && !f.link_url && !CTX.link_gdpr) {
                avvisi.push(['info', 'Il consenso privacy resterà nascosto finché non imposti un link (nel campo o in "Integrazione API").']);
            }
            if (f.enabled && haOpzioni(f.type) && !f.options.length) {
                avvisi.push(['warning', 'Il campo <strong>' + esc(f.label || TIPI[f.type].nome) + '</strong> non ha opzioni.']);
            }
        });

        $root.find('.pfn-fe-notices').html(avvisi.map(function (a) {
            return '<div class="pfn-fe-notice is-' + a[0] + '"><span class="dashicons ' + (a[0] === 'warning' ? 'dashicons-warning' : 'dashicons-info-outline') + '"></span><p>' + a[1] + '</p></div>';
        }).join(''));
    };

    /* ------------------------------------------------------------------ */
    /* Aggiunta campi                                                      */
    /* ------------------------------------------------------------------ */

    const NUOVI = {
        consent:   { label: 'Acconsento a essere ricontattato dalla famiglia' },
        heading:   { label: 'Titolo della sezione' },
        paragraph: { label: 'Scrivi qui un testo informativo per chi compila il form.' },
        select:    { options: ['Opzione 1', 'Opzione 2', 'Opzione 3'] },
        radio:     { options: ['Opzione 1', 'Opzione 2', 'Opzione 3'] },
        checkboxes:{ options: ['Opzione 1', 'Opzione 2', 'Opzione 3'] },
        textarea:  { rows: 4 }
    };

    const renderPalette = function () {
        let h = '';
        GRUPPI_TIPI.forEach(function (g) {
            h += '<div class="pfn-fe-palette-group"><span class="pfn-fe-palette-title">' + g.titolo + '</span><div class="pfn-fe-palette-grid">';
            h += g.tipi.map(function (t) {
                return '<button type="button" data-add="' + t + '"><span class="dashicons ' + TIPI[t].icona + '"></span>' + esc(TIPI[t].nome) + '</button>';
            }).join('');
            h += '</div></div>';
        });

        let mancanti = Object.keys(BASE).filter(function (k) { return !config.fields.some(function (f) { return f.key === k; }); });
        if (mancanti.length) {
            h += '<div class="pfn-fe-palette-group"><span class="pfn-fe-palette-title">Campi predefiniti rimossi</span><div class="pfn-fe-palette-grid">';
            h += mancanti.map(function (k) {
                return '<button type="button" data-add-core="' + esc(k) + '"><span class="dashicons dashicons-backup"></span>' + esc(BASE[k].label) + '</button>';
            }).join('');
            h += '</div></div>';
        }

        h += '<p class="pfn-fe-palette-note">' + (selezionato && trova(selezionato) ? 'Il nuovo campo verrà inserito sotto a quello selezionato.' : 'Il nuovo campo verrà aggiunto in fondo.') + '</p>';
        $root.find('.pfn-fe-palette').html(h);
    };

    const apriPalette = function (apri) {
        let $p = $root.find('.pfn-fe-palette');
        if (apri) { renderPalette(); }
        $p.prop('hidden', !apri);
        $root.find('.pfn-fe-add-btn').attr('aria-expanded', apri);
    };

    const aggiungi = function (tipo, chiaveBase) {
        let f;

        if (chiaveBase) {
            let orig = PREDEFINITA.fields.filter(function (x) { return x.key === chiaveBase; })[0];
            f = Object.assign(clone(CAMPO_VUOTO), clone(orig));
            if (trova(f.id)) { f.id = uid(); }
        } else {
            f = Object.assign(clone(CAMPO_VUOTO), { id: uid(), type: tipo, label: TIPI[tipo].nome }, clone(NUOVI[tipo] || {}));
            f.key = isStatico(tipo) ? '' : chiaveUnica(slug(f.label), f.id);
            autoKey[f.id] = true;
        }

        let dopo = selezionato ? indice(selezionato) : -1;
        config.fields.splice(dopo >= 0 ? dopo + 1 : config.fields.length, 0, f);

        ui.aperti.push(f.id);
        selezionato = f.id;
        salvaUi();
        apriPalette(false);
        renderCampi();
        modificato();

        let li = $lista.children('[data-id="' + f.id + '"]').get(0);
        if (li) {
            li.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            let input = li.querySelector('[data-prop="label"]');
            if (input) { input.focus(); input.select(); }
        }
    };

    /* ------------------------------------------------------------------ */
    /* Anteprima                                                           */
    /* ------------------------------------------------------------------ */

    const variabili = function () {
        let s = config.style;
        let v = {
            '--dg-necro-colore-box': CTX.colore_box || '#f7f6ed',
            '--dg-necro-colore-page': CTX.colore_page || '#f9eee4',
            '--pfn-cf-max-width': s.max_width + 'px',
            '--pfn-cf-padding': s.padding + 'px',
            '--pfn-cf-radius': s.form_radius + 'px',
            '--pfn-cf-gap-x': s.gap_x + 'px',
            '--pfn-cf-gap-y': s.gap_y + 'px',
            '--pfn-cf-input-radius': s.input_radius + 'px',
            '--pfn-cf-border-width': s.border_width + 'px',
            '--pfn-cf-btn-radius': s.btn_radius + 'px',
            '--pfn-cf-input-pad': (TAGLIE[s.field_size] || TAGLIE.m)[0]
        };
        if ((TAGLIE[s.field_size] || TAGLIE.m)[1]) { v['--pfn-cf-input-font'] = TAGLIE[s.field_size][1]; }
        if (s.label_size) { v['--pfn-cf-label-size'] = s.label_size + 'px'; }
        if (s.title_size) { v['--pfn-cf-title-size'] = s.title_size + 'px'; }
        Object.keys(VAR_COLORI).forEach(function (k) { if (hex(s[k])) { v[VAR_COLORI[k]] = hex(s[k]); } });
        if (hex(s.colore_sfondo)) {
            v['--pfn-cf-overlay'] = rgba(s.colore_sfondo, s.bg_opacity);
            v['--pfn-cf-section-bg'] = hex(s.colore_sfondo);
        }
        return Object.keys(v).map(function (k) { return k + ':' + v[k]; }).join(';');
    };

    const classiStile = function () {
        let s = config.style;
        let c = ['pfn-cf-labels-' + s.label_position, 'pfn-cf-btn-' + s.btn_align];
        if (!s.shadow) { c.push('pfn-cf-no-shadow'); }
        if (hex(s.colore_bottone)) { c.push('pfn-cf-btn-custom'); }
        return c.join(' ');
    };

    const htmlAnteprimaCampo = function (f) {
        let t = f.type;
        let asterisco = (f.required && config.opts.show_asterisk) ? '<span class="pfn-cf-req">*</span>' : '';
        let nascosta = config.style.label_position === 'hidden';
        let segnaposto = f.placeholder || (nascosta ? f.label : '');
        let fantasma = f.key === 'privacy' && !f.link_url && !CTX.link_gdpr;
        let classi = ['form-group', 'pfn-cf-field', 'pfn-cf-type-' + t];
        if (f.new_row) { classi.push('pfn-cf-newrow'); }
        if (t === 'consent') { classi.push('pvcy-group'); }
        if (selezionato === f.id) { classi.push('is-selected'); }
        if (fantasma) { classi.push('is-ghost'); }

        let h = '<div class="' + classi.join(' ') + '" data-id="' + esc(f.id) + '" style="--pfn-span:' + f.width + ';--pfn-span-m:' + f.width_mobile + '">';
        h += '<span class="pfn-prev-badge">' + esc(nomeLarghezza(ui.device === 'mobile' ? f.width_mobile : f.width)) + '</span>';
        if (fantasma) { h += '<span class="pfn-prev-ghost">Nascosto: manca il link privacy</span>'; }

        switch (t) {
            case 'heading':
                h += '<h4 class="pfn-cf-heading-text">' + esc(f.label) + '</h4>';
                break;
            case 'paragraph':
                h += '<p class="pfn-cf-paragraph-text">' + nl2br(f.label) + '</p>';
                break;
            case 'consent':
                let link = f.link_text ? ' <a>' + esc(f.link_text) + '</a>' : '';
                h += '<p><span class="pfn-prev-check"></span><label>' + esc(f.label) + link + asterisco + '</label></p>';
                break;
            case 'radio':
            case 'checkboxes':
                h += '<span class="pfn-cf-label">' + esc(f.label) + asterisco + '</span><div class="pfn-cf-choices">';
                h += f.options.map(function (o) {
                    let on = f.default !== '' && f.default === o;
                    return '<label class="pfn-cf-choice"><span class="pfn-prev-check' + (t === 'radio' ? ' is-radio' : '') + (on ? ' is-on' : '') + '"></span><span>' + esc(o) + '</span></label>';
                }).join('');
                h += '</div>';
                break;
            case 'select':
                h += '<label class="pfn-cf-label">' + esc(f.label) + asterisco + '</label>';
                h += '<select tabindex="-1"><option>' + esc(f.default || segnaposto || '—') + '</option></select>';
                break;
            case 'textarea':
                h += '<label class="pfn-cf-label">' + esc(f.label) + asterisco + '</label>';
                h += '<textarea readonly tabindex="-1" rows="' + f.rows + '" placeholder="' + esc(segnaposto) + '">' + esc(f.default) + '</textarea>';
                break;
            default:
                h += '<label class="pfn-cf-label">' + esc(f.label) + asterisco + '</label>';
                h += '<input type="' + ((t === 'date' || t === 'time') ? t : 'text') + '" readonly tabindex="-1" placeholder="' + esc(segnaposto) + '" value="' + esc(f.default) + '">';
        }

        if (f.help && !isStatico(t)) { h += '<small class="pfn-cf-help">' + esc(f.help) + '</small>'; }

        return h + '</div>';
    };

    const renderAnteprima = function () {
        let t = config.texts;
        let popup = ui.modo === 'popup';
        let campi = config.fields.filter(function (f) { return f.enabled; }).map(htmlAnteprimaCampo).join('');

        stage.className = 'pfn-fe-stage' + (ui.device === 'mobile' ? ' is-mobile' : '') + (popup ? ' is-popup' : ' is-inline') + (ui.contorni ? ' show-grid' : '');

        canvas.innerHTML =
            '<div class="pfn-prev ' + classiStile() + '" style="' + esc(variabili()) + '">' +
                '<div class="form-container"><div class="pfn-prev-form">' +
                    (popup ? '<span class="pfn-prev-close" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 16L12 12M12 12L8 8M12 12L16 8M12 12L8 16" stroke-width="2" stroke-linecap="round"/></svg></span>' : '') +
                    '<div class="input-content">' +
                        (t.title ? '<h3 class="pfn-cf-title">' + esc(t.title) + '</h3>' : '') +
                        (t.subtitle ? '<p class="pfn-cf-subtitle">' + nl2br(t.subtitle) + '</p>' : '') +
                        (campi ? '<div class="pfn-cf-grid">' + campi + '</div>' : '<p class="pfn-prev-empty">Nessun campo visibile</p>') +
                        '<button type="button" tabindex="-1" class="invia-a-api">' + esc(t.submit) + '</button>' +
                    '</div>' +
                '</div></div>' +
            '</div>';

        adattaScala();
    };

    // L'anteprima desktop usa una larghezza "reale" e viene rimpicciolita per stare nel pannello.
    const adattaScala = function () {
        let s = config.style;
        let larghezza = (ui.device === 'mobile') ? 375 : Math.max(720, s.max_width + 2 * s.padding + 80);
        let disponibile = stage.clientWidth - 34; // padding dello stage
        let scala = Math.min(1, disponibile / larghezza);
        canvas.style.width = larghezza + 'px';
        canvas.style.transform = 'scale(' + scala + ')';
        scaler.style.width = (larghezza * scala) + 'px';
        scaler.style.height = (canvas.offsetHeight * scala) + 'px';
    };

    /* ------------------------------------------------------------------ */
    /* Selezione e schede                                                  */
    /* ------------------------------------------------------------------ */

    const mostraTab = function (tab) {
        ui.tab = tab;
        salvaUi();
        $root.find('.pfn-fe-tab').each(function () {
            let on = this.getAttribute('data-tab') === tab;
            this.classList.toggle('is-active', on);
            this.setAttribute('aria-selected', on);
        });
        $root.find('.pfn-fe-section').each(function () {
            this.hidden = this.getAttribute('data-panel') !== tab;
        });
    };

    const aggiornaUiBar = function () {
        $root.find('.pfn-fe-preview-bar [data-ui]').each(function () {
            let k = this.getAttribute('data-ui');
            if (this.type === 'checkbox') { this.checked = !!ui[k]; return; }
            $(this).children('button').each(function () {
                let on = this.getAttribute('data-val') === ui[k];
                this.classList.toggle('is-active', on);
                this.setAttribute('aria-pressed', on);
            });
        });
    };

    const seleziona = function (id, daAnteprima) {
        selezionato = id;
        if (daAnteprima) {
            mostraTab('campi');
            if (ui.aperti.indexOf(id) === -1) { ui.aperti.push(id); salvaUi(); }
        }
        renderCampi();
        renderAnteprima();
        if (daAnteprima) {
            let li = $lista.children('[data-id="' + id + '"]').get(0);
            if (li) {
                li.scrollIntoView({ block: 'center', behavior: 'smooth' });
                li.classList.add('is-flash');
                setTimeout(function () { li.classList.remove('is-flash'); }, 900);
            }
        }
    };

    const toast = function (testo, azione, callback) {
        let $t = $root.find('.pfn-fe-toast');
        $t.find('.pfn-fe-toast-text').text(testo);
        $t.find('.pfn-fe-toast-btn').text(azione || '').prop('hidden', !azione).off('click').on('click', function () {
            $t.prop('hidden', true);
            if (callback) { callback(); }
        });
        $t.prop('hidden', false);
        clearTimeout(timerToast);
        timerToast = setTimeout(function () { $t.prop('hidden', true); }, 7000);
    };

    /* ------------------------------------------------------------------ */
    /* Eventi: lista campi                                                 */
    /* ------------------------------------------------------------------ */

    $lista.on('click', '[data-act]', function () {
        let id = $(this).closest('.pfn-fe-field').attr('data-id');
        let f = trova(id);
        let i = indice(id);
        if (!f) { return; }

        switch (this.getAttribute('data-act')) {

            case 'toggle':
                let pos = ui.aperti.indexOf(id);
                if (pos === -1) { ui.aperti.push(id); } else { ui.aperti.splice(pos, 1); }
                selezionato = id;
                salvaUi();
                renderCampi();
                renderAnteprima();
                return;

            case 'up':
            case 'down':
                let j = this.getAttribute('data-act') === 'up' ? i - 1 : i + 1;
                if (j < 0 || j >= config.fields.length) { return; }
                config.fields.splice(j, 0, config.fields.splice(i, 1)[0]);
                renderCampi();
                modificato();
                $lista.children('[data-id="' + id + '"]').find('[data-act="' + this.getAttribute('data-act') + '"]:not(:disabled)').trigger('focus');
                return;

            case 'visibility':
                f.enabled = !f.enabled;
                aggiornaTesta(id);
                modificato();
                return;

            case 'duplicate':
                let copia = clone(f);
                copia.id = uid();
                copia.core = false;
                copia.label = f.label + ' (copia)';
                copia.key = isStatico(f.type) ? '' : chiaveUnica(slug(copia.label), copia.id);
                autoKey[copia.id] = true;
                config.fields.splice(i + 1, 0, copia);
                ui.aperti.push(copia.id);
                selezionato = copia.id;
                renderCampi();
                modificato();
                return;

            case 'delete':
                let rimosso = config.fields.splice(i, 1)[0];
                if (selezionato === id) { selezionato = null; }
                renderCampi();
                modificato();
                toast('"' + (rimosso.label || TIPI[rimosso.type].nome) + '" eliminato' + (rimosso.core ? ': non verrà più inviato al portale.' : '.'), 'Annulla', function () {
                    config.fields.splice(Math.min(i, config.fields.length), 0, rimosso);
                    renderCampi();
                    modificato();
                });
                return;
        }
    });

    $lista.on('change', '[data-quick="required"]', function () {
        let id = $(this).closest('.pfn-fe-field').attr('data-id');
        let f = trova(id);
        if (!f) { return; }
        f.required = this.checked;
        aggiornaTesta(id);
        $lista.children('[data-id="' + id + '"]').find('.pfn-fe-field-body [data-prop="required"]').prop('checked', f.required);
        modificato();
    });

    $lista.on('input change', '[data-prop]', function (e) {
        if (this.classList.contains('pfn-fe-seg')) { return; }

        let id = $(this).closest('.pfn-fe-field').attr('data-id');
        let f = trova(id);
        if (!f) { return; }

        let prop = this.getAttribute('data-prop');
        let v = (this.type === 'checkbox') ? this.checked : this.value;

        switch (prop) {

            case 'type':
                if (e.type !== 'change') { return; }
                f.type = v;
                if (haOpzioni(v) && !f.options.length) { f.options = clone(NUOVI.select.options); }
                if (isStatico(v)) { f.required = false; }
                else if (!f.key) { f.key = chiaveUnica(slug(f.label), id); autoKey[id] = true; }
                aggiornaCorpo(id);
                break;

            case 'label':
                f.label = v;
                if (autoKey[id] && !f.core && !isStatico(f.type)) {
                    f.key = chiaveUnica(slug(v), id);
                    $(this).closest('.pfn-fe-field-body').find('[data-prop="key"]').val(f.key);
                }
                break;

            case 'key':
                autoKey[id] = false;
                if (e.type === 'change') {
                    f.key = chiaveUnica(pulisciChiave(v) || slug(f.label), id);
                    this.value = f.key;
                } else {
                    f.key = pulisciChiave(v);
                }
                break;

            case 'options':
                f.options = v.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
                if (f.options.indexOf(f.default) === -1) { f.default = ''; }
                if (e.type === 'change') { aggiornaCorpo(id); }
                break;

            case 'maxlength':
                f.maxlength = intero(v, 0, 10000, 0);
                if (e.type === 'change') { this.value = f.maxlength || ''; }
                break;

            case 'required':
                f.required = v;
                break;

            default:
                f[prop] = v;
        }

        aggiornaTesta(id);
        modificato();
    });

    $lista.on('click', '.pfn-fe-seg[data-prop] button', function () {
        let $seg = $(this).closest('.pfn-fe-seg');
        let id = $seg.closest('.pfn-fe-field').attr('data-id');
        let f = trova(id);
        if (!f) { return; }
        f[$seg.attr('data-prop')] = intero(this.getAttribute('data-val'), 1, 12, 12);
        $seg.children().removeClass('is-active').attr('aria-pressed', 'false');
        $(this).addClass('is-active').attr('aria-pressed', 'true');
        aggiornaTesta(id);
        modificato();
    });

    $lista.on('input change', '[data-prop-range] input', function (e) {
        let $r = $(this).closest('.pfn-fe-range');
        let f = trova($r.closest('.pfn-fe-field').attr('data-id'));
        if (!f) { return; }
        let n = intero(this.value, +$r.attr('data-min'), +$r.attr('data-max'), f.rows);
        f[$r.attr('data-prop-range')] = n;
        if (this.type === 'range') { $r.find('input[type="text"]').val(n); }
        else {
            $r.find('input[type="range"]').val(n);
            if (e.type === 'change') { this.value = n; }
        }
        modificato();
    });

    $lista.sortable({
        handle: '.pfn-fe-handle',
        axis: 'y',
        items: '> li',
        placeholder: 'pfn-fe-field-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        update: function () {
            let ordine = $lista.children('li').map(function () { return this.getAttribute('data-id'); }).get();
            config.fields.sort(function (a, b) { return ordine.indexOf(a.id) - ordine.indexOf(b.id); });
            // Il ridisegno avviene dopo che jQuery UI ha concluso il trascinamento.
            setTimeout(function () { renderCampi(); modificato(); }, 0);
        }
    });

    /* ------------------------------------------------------------------ */
    /* Eventi: pannelli Aspetto e Testi                                    */
    /* ------------------------------------------------------------------ */

    $root.on('input change', '.pfn-fe-section [data-bind]:not(.pfn-fe-color):not(.pfn-fe-range):not(.pfn-fe-seg)', function () {
        scrivi(this.getAttribute('data-bind'), this.type === 'checkbox' ? this.checked : this.value);
        if (this.getAttribute('data-bind') === 'opts.append_extra') { renderAvvisi(); }
        modificato();
    });

    $root.on('click', '.pfn-fe-section .pfn-fe-seg[data-bind] button', function () {
        let $seg = $(this).closest('.pfn-fe-seg');
        scrivi($seg.attr('data-bind'), this.getAttribute('data-val'));
        $seg.children().removeClass('is-active').attr('aria-pressed', 'false');
        $(this).addClass('is-active').attr('aria-pressed', 'true');
        modificato();
    });

    $root.on('input change', '.pfn-fe-section .pfn-fe-range[data-bind] input', function (e) {
        let $r = $(this).closest('.pfn-fe-range');
        let bind = $r.attr('data-bind');
        let auto = this.getAttribute('placeholder') === 'Auto' || $r.find('input[placeholder="Auto"]').length > 0;
        let raw = this.value;
        let n = (auto && raw === '') ? 0 : intero(raw, +$r.attr('data-min'), +$r.attr('data-max'), leggi(bind));
        if (this.type === 'text' && e.type === 'input' && raw !== '' && isNaN(parseInt(raw, 10))) { return; }
        scrivi(bind, n);
        if (this.type === 'range') {
            $r.find('input[type="text"]').val(auto && !n ? '' : n);
        } else {
            $r.find('input[type="range"]').val(n);
            if (e.type === 'change') { this.value = (auto && !n) ? '' : n; }
        }
        modificato();
    });

    const impostaColore = function ($c, valore) {
        let k = $c.attr('data-bind').split('.')[1];
        scrivi($c.attr('data-bind'), valore);
        $c.toggleClass('is-default', !valore);
        $c.find('.pfn-fe-color-swatch').val(valore || COLORI_PREDEFINITI[k] || '#ffffff');
    };

    $root.on('input', '.pfn-fe-color-swatch', function () {
        let $c = $(this).closest('.pfn-fe-color');
        impostaColore($c, this.value);
        $c.find('.pfn-fe-color-hex').val(this.value);
        modificato();
    });

    $root.on('input change', '.pfn-fe-color-hex', function (e) {
        let $c = $(this).closest('.pfn-fe-color');
        let v = this.value.trim();
        if (v === '') { impostaColore($c, ''); modificato(); return; }
        let h = hex(v);
        if (h) { impostaColore($c, h); modificato(); }
        if (e.type === 'change') { this.value = leggi($c.attr('data-bind')); }
    });

    $root.on('click', '.pfn-fe-color-reset', function () {
        let $c = $(this).closest('.pfn-fe-color');
        impostaColore($c, '');
        $c.find('.pfn-fe-color-hex').val('');
        modificato();
    });

    $root.on('click', '.pfn-fe-theme', function () {
        let tema = TEMI[+this.getAttribute('data-tema')];
        Object.keys(COLORI_PREDEFINITI).forEach(function (k) { config.style[k] = tema.colori[k] || ''; });
        renderSezioni('aspetto', SEZIONI_ASPETTO);
        modificato();
    });

    /* ------------------------------------------------------------------ */
    /* Eventi: barra superiore, anteprima, palette                         */
    /* ------------------------------------------------------------------ */

    $root.on('click', '.pfn-fe-tab', function () { mostraTab(this.getAttribute('data-tab')); });

    $root.on('click', '.pfn-fe-preview-bar .pfn-fe-seg button', function () {
        ui[$(this).closest('.pfn-fe-seg').attr('data-ui')] = this.getAttribute('data-val');
        salvaUi();
        aggiornaUiBar();
        renderAnteprima();
    });

    $root.on('change', '.pfn-fe-preview-bar [data-ui="contorni"]', function () {
        ui.contorni = this.checked;
        salvaUi();
        renderAnteprima();
    });

    $(canvas).on('click', '.pfn-cf-field', function () { seleziona(this.getAttribute('data-id'), true); });

    $root.on('click', '.pfn-fe-add-btn', function (e) {
        e.stopPropagation();
        apriPalette($root.find('.pfn-fe-palette').prop('hidden'));
    });
    $root.on('click', '.pfn-fe-add-btn-alt', function (e) {
        e.stopPropagation();
        apriPalette(true);
    });
    $root.on('click', '[data-add]', function () { aggiungi(this.getAttribute('data-add')); });
    $root.on('click', '[data-add-core]', function () { aggiungi(null, this.getAttribute('data-add-core')); });

    $root.on('click', '.pfn-fe-menu-btn', function (e) {
        e.stopPropagation();
        let $m = $root.find('.pfn-fe-menu');
        $m.prop('hidden', !$m.prop('hidden'));
        $(this).attr('aria-expanded', !$m.prop('hidden'));
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.pfn-fe-palette, .pfn-fe-add-btn').length) { apriPalette(false); }
        if (!$(e.target).closest('.pfn-fe-menu-wrap').length) { $root.find('.pfn-fe-menu').prop('hidden', true); }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            apriPalette(false);
            $root.find('.pfn-fe-menu').prop('hidden', true);
        }
    });

    // Invio dentro l'editor non deve inviare il form delle impostazioni.
    $root.on('keydown', 'input', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });

    $root.on('click', '[data-menu]', function () {
        $root.find('.pfn-fe-menu').prop('hidden', true);

        switch (this.getAttribute('data-menu')) {

            case 'export':
                let blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
                let a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'form-cordoglio.json';
                document.body.appendChild(a);
                a.click();
                setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
                return;

            case 'import':
                $root.find('.pfn-fe-import-file').val('').trigger('click');
                return;

            case 'reset':
                if (!window.confirm('Ripristinare il form predefinito? Campi, aspetto e testi personalizzati verranno sostituiti (puoi ancora annullare non salvando).')) { return; }
                let precedente = clone(config);
                config = normalizza(clone(PREDEFINITA));
                ui.aperti = [];
                selezionato = null;
                renderTutto();
                modificato();
                toast('Form predefinito ripristinato.', 'Annulla', function () {
                    config = precedente;
                    renderTutto();
                    modificato();
                });
                return;
        }
    });

    $root.on('change', '.pfn-fe-import-file', function () {
        let file = this.files && this.files[0];
        if (!file) { return; }
        let reader = new FileReader();
        reader.onload = function () {
            let dati;
            try { dati = JSON.parse(reader.result); } catch (err) { dati = null; }
            if (!dati || !Array.isArray(dati.fields)) {
                toast('Il file non contiene una configurazione valida del form.');
                return;
            }
            let precedente = clone(config);
            config = normalizza(dati);
            ui.aperti = [];
            selezionato = null;
            renderTutto();
            modificato();
            toast('Configurazione importata: ricordati di salvare.', 'Annulla', function () {
                config = precedente;
                renderTutto();
                modificato();
            });
        };
        reader.readAsText(file);
    });

    $root.on('click', '.pfn-fe-save', function () {
        sincronizza();
        dirty = false;
        $('input[name="cambia_impostazioni"]').first().trigger('click');
    });

    $root.closest('form').on('submit', function () {
        sincronizza();
        dirty = false;
    });

    window.addEventListener('beforeunload', function (e) {
        if (!dirty) { return; }
        e.preventDefault();
        e.returnValue = '';
    });

    let timerResize = null;
    window.addEventListener('resize', function () {
        clearTimeout(timerResize);
        timerResize = setTimeout(adattaScala, 100);
    });

    // L'editor puo' essere inizializzato con il tab nascosto: si ricalcola la scala quando diventa visibile.
    if (window.ResizeObserver) {
        let ultimaLarghezza = 0;
        new ResizeObserver(function () {
            if (stage.clientWidth === ultimaLarghezza) { return; }
            ultimaLarghezza = stage.clientWidth;
            window.requestAnimationFrame(adattaScala);
        }).observe(stage);
    }

    /* ------------------------------------------------------------------ */
    /* Avvio                                                               */
    /* ------------------------------------------------------------------ */

    const renderTutto = function () {
        renderPannelli();
        renderCampi();
        renderAvvisi();
        renderAnteprima();
    };

    ui.aperti = ui.aperti.filter(function (id) { return !!trova(id); });
    mostraTab(ui.tab);
    aggiornaUiBar();
    renderTutto();
    sincronizza();

});
