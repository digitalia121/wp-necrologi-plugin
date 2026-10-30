/*
 * Pagina del singolo necrologio nel backend: box SEO in stile Yoast.
 * Anteprime di Google e Facebook, barre di lunghezza e analisi si aggiornano
 * mentre si scrive; la logica dei segnaposto e' la stessa usata lato server.
 */
jQuery(function ($) {

    let $pagina = $('.pfn-nd');

    if (!$pagina.length) { return; }

    let dati = $pagina.data('editor') || {};
    let $form = $pagina.find('.pfn-nd-form');

    let $titolo = $('#pfn-titolo');
    let $desc = $('#pfn-descrizione');
    let $chiave = $('#pfn-frase-chiave');
    let $socialTitolo = $('#pfn-social-titolo');
    let $socialDesc = $('#pfn-social-descrizione');
    let $indicizza = $('#pfn-indicizza');

    /* ---------- testi effettivi (come PortaleFunebreNecrologi_Seo) ---------- */

    let sostituisci = function (modello) {
        let testo = String(modello);
        $.each(dati.valori || {}, function (segnaposto, valore) { testo = testo.split(segnaposto).join(valore); });
        testo = testo.replace(/\s+/g, ' ').replace(/\s*([\-|–—·,])(?:\s*[\-|–—·,])+/g, ' $1').replace(/\s+,/g, ',');
        return testo.replace(/^[\s\-|–—·,]+|[\s\-|–—·,]+$/g, '');
    };

    let accorcia = function (testo, max) {
        testo = $.trim(testo);
        if (testo.length <= max) { return testo; }
        let taglio = testo.slice(0, max);
        let spazio = taglio.lastIndexOf(' ');
        if (spazio > max * 0.6) { taglio = taglio.slice(0, spazio); }
        return taglio.replace(/[\s,.;:\-–]+$/, '') + '…';
    };

    let titoloEffettivo = function () {
        return sostituisci($.trim($titolo.val()) || dati.titolo_generale) || (dati.valori || {})['{nome}'] || '';
    };

    let descEffettiva = function () {
        return $.trim($desc.val()) ? accorcia(sostituisci($desc.val()), dati.max_desc || 155) : (dati.descrizione_auto || '');
    };

    // Lunghezza senza i tagli: serve per la barra e per l'analisi.
    let descPiena = function () {
        return $.trim($desc.val()) ? sostituisci($desc.val()) : (dati.descrizione_auto || '');
    };

    /* ---------- anteprime ---------- */

    let urlLeggibile = function (url) {
        try {
            let u = new URL(url);
            let parti = u.pathname.split('/').filter(Boolean);
            return u.host + (parti.length ? ' › ' + parti.join(' › ') : '');
        } catch (e) { return url; }
    };

    let normalizza = function (testo) {
        return String(testo || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').trim();
    };

    // Frase chiave: presente per intero, oppure tutte le sue parole.
    let contiene = function (testo, chiave) {
        let t = normalizza(testo);
        let k = normalizza(chiave);
        if (!k) { return 'no'; }
        if (t.indexOf(k) !== -1) { return 'frase'; }
        let parole = k.split(' ').filter(function (p) { return p.length > 2; });
        return parole.length && parole.every(function (p) { return t.indexOf(p) !== -1; }) ? 'parole' : 'no';
    };

    let barra = function (per, lunghezza, minimo, massimo) {
        let $b = $pagina.find('.pfn-nd-barra[data-per="' + per + '"]');
        let stato = lunghezza === 0 ? '' : (lunghezza < minimo ? 'is-warn' : (lunghezza > massimo ? 'is-bad' : 'is-ok'));
        $b.removeClass('is-ok is-warn is-bad').addClass(stato);
        $b.find('span').css('width', Math.min(100, Math.round(lunghezza / massimo * 100)) + '%');
    };

    let analisi = function (titolo, desc) {

        let voci = [];
        let chiave = $.trim($chiave.val());
        let aggiungi = function (stato, testo) { voci.push([stato, testo]); };

        if (!chiave) {
            aggiungi('', 'Imposta una frase chiave per controllare titolo, description e testo.');
        } else {
            let esito = contiene(titolo, chiave);
            aggiungi(esito === 'frase' ? 'is-ok' : (esito === 'parole' ? 'is-warn' : 'is-bad'),
                esito === 'frase' ? 'La frase chiave è nel titolo SEO.' : (esito === 'parole' ? 'Il titolo contiene le parole della frase chiave, ma non la frase intera.' : 'La frase chiave non è nel titolo SEO.'));

            esito = contiene(desc, chiave);
            aggiungi(esito === 'no' ? 'is-bad' : 'is-ok', esito === 'no' ? 'La frase chiave non è nella meta description.' : 'La frase chiave è nella meta description.');

            esito = contiene(dati.testo, chiave);
            aggiungi(esito === 'no' ? 'is-warn' : 'is-ok', esito === 'no' ? 'La frase chiave non compare nel testo del necrologio.' : 'La frase chiave compare nel testo del necrologio.');
        }

        let lt = titolo.length;
        aggiungi(lt < 30 ? 'is-warn' : (lt > 60 ? 'is-bad' : 'is-ok'),
            lt < 30 ? 'Titolo SEO corto (' + lt + ' caratteri): c\'è spazio per il paese o il nome del sito.' : (lt > 60 ? 'Titolo SEO lungo (' + lt + ' caratteri): Google lo taglierà.' : 'Lunghezza del titolo SEO ottimale (' + lt + ' caratteri).'));

        let ld = descPiena().length;
        aggiungi(ld < 80 ? 'is-warn' : (ld > (dati.max_desc || 155) ? 'is-warn' : 'is-ok'),
            ld < 80 ? 'Meta description corta (' + ld + ' caratteri).' : (ld > (dati.max_desc || 155) ? 'Meta description lunga: verrà accorciata a ' + (dati.max_desc || 155) + ' caratteri.' : 'Lunghezza della meta description ottimale (' + ld + ' caratteri).'));

        aggiungi(dati.ha_foto ? 'is-ok' : 'is-warn', dati.ha_foto ? 'Foto presente, con testo alternativo.' : 'Il necrologio non ha una foto.');

        if ($indicizza.val() === '1') {
            aggiungi('is-bad', 'Il necrologio è escluso dai motori di ricerca.');
        } else if (!dati.sito_pubblico) {
            aggiungi('is-warn', 'Il sito chiede ai motori di non indicizzarlo (Impostazioni › Lettura).');
        } else {
            aggiungi('is-ok', 'Il necrologio può comparire nei risultati di ricerca.');
        }

        let $lista = $pagina.find('.pfn-nd-analisi').empty();
        voci.forEach(function (v) { $lista.append($('<li></li>').addClass(v[0]).text(v[1])); });
    };

    let aggiorna = function () {

        let titolo = titoloEffettivo();
        let desc = descEffettiva();

        $pagina.find('.pfn-nd-g-url').text(urlLeggibile(dati.url));
        $pagina.find('.pfn-nd-g-titolo').text(titolo);
        $pagina.find('.pfn-nd-g-desc').text(desc);

        $pagina.find('.pfn-nd-fb-titolo').text($.trim($socialTitolo.val()) ? sostituisci($socialTitolo.val()) : titolo);
        $pagina.find('.pfn-nd-fb-desc').text($.trim($socialDesc.val()) ? accorcia(sostituisci($socialDesc.val()), 200) : desc);

        barra('titolo', titolo.length, 30, 60);
        barra('descrizione', descPiena().length, 80, dati.max_desc || 155);

        analisi(titolo, desc);
    };

    $pagina.on('input change', 'input, textarea, select', aggiorna);

    /* ---------- schede ---------- */

    $pagina.on('click', '.pfn-nd-scheda', function () {
        let scheda = $(this).data('scheda');
        $pagina.find('.pfn-nd-scheda').removeClass('is-attiva').attr('aria-selected', 'false');
        $(this).addClass('is-attiva').attr('aria-selected', 'true');
        $pagina.find('.pfn-nd-pannello').prop('hidden', true).filter('[data-pannello="' + scheda + '"]').prop('hidden', false);
        $pagina.find('.pfn-nd-scheda-attiva').val(scheda);
    });

    /* ---------- segnaposto ---------- */

    $pagina.on('click', '.pfn-nd-chip', function () {
        let campo = document.getElementById($(this).closest('.pfn-nd-chips').data('bersaglio'));
        if (!campo) { return; }
        let segnaposto = this.getAttribute('data-segnaposto');
        let inizio = campo.selectionStart != null ? campo.selectionStart : campo.value.length;
        let fine = campo.selectionEnd != null ? campo.selectionEnd : campo.value.length;
        campo.value = campo.value.slice(0, inizio) + segnaposto + campo.value.slice(fine);
        campo.focus();
        campo.setSelectionRange(inizio + segnaposto.length, inizio + segnaposto.length);
        $(campo).trigger('input');
    });

    /* ---------- immagine social ---------- */

    let $img = $pagina.find('.pfn-nd-fb-img img');
    let $mediaId = $pagina.find('.pfn-nd-media-id');
    let $rimuovi = $pagina.find('.pfn-nd-media-rimuovi');
    let frame = null;

    $pagina.on('click', '.pfn-nd-media-scegli', function () {
        if (!window.wp || !wp.media) { return; }
        if (!frame) {
            frame = wp.media({ title: 'Immagine per i social', button: { text: 'Usa questa immagine' }, library: { type: 'image' }, multiple: false });
            frame.on('select', function () {
                let allegato = frame.state().get('selection').first().toJSON();
                let src = allegato.sizes && allegato.sizes.large ? allegato.sizes.large.url : allegato.url;
                $mediaId.val(allegato.id).trigger('change');
                $img.attr('src', src).prop('hidden', false);
                $rimuovi.prop('hidden', false);
            });
        }
        frame.open();
    });

    $rimuovi.on('click', function () {
        $mediaId.val('').trigger('change');
        $img.attr('src', dati.foto || '').prop('hidden', !dati.foto);
        $rimuovi.prop('hidden', true);
    });

    /* ---------- modifiche non salvate ---------- */

    // La scheda aperta non e' una modifica.
    let valori = function () { return $form.find(':input').not('.pfn-nd-scheda-attiva').serialize(); };
    let iniziale = valori();
    let inviato = false;

    $pagina.on('input change', 'input, textarea, select', function () {
        $pagina.find('.pfn-nd-modificato').prop('hidden', valori() === iniziale);
    });

    $form.on('submit', function () { inviato = true; });

    $(window).on('beforeunload', function () {
        if (!inviato && valori() !== iniziale) { return true; }
    });

    aggiorna();
});
