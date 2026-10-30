

jQuery(document).ready(function ($) {

    $('#upload_hero_bg').on('click', function (e) {

        e.preventDefault();

        const frame = wp.media({
            title: 'Seleziona sfondo hero',
            button: { text: 'Usa questa immagine' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#sfondo_hero').val(attachment.id);
            const thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            $('#preview_hero_bg').html('<img src="' + thumb + '" />');
        });

        frame.open();

    });

    $('.impostazioni-header a[to]').click(function () {
        $('.impostazioni-header a[to]').removeClass('active').removeAttr('aria-current');
        let to = this.getAttribute('to');
        // Un solo parametro "tab", aggiornato al posto di essere accodato.
        let url = new URL(window.location.href);
        url.searchParams.set('tab', to);
        history.replaceState(null, '', url);
        this.classList.add('active');
        this.setAttribute('aria-current', 'true');
        $('.impostazioni-content content[tab]').removeClass('active');
        $('.impostazioni-content content[tab="' + to + '"]').addClass('active');
    });

    let opened_tab = new URLSearchParams(window.location.search).get('tab');

    if (opened_tab) {
        $('.impostazioni-header a[to="' + opened_tab.replace(/[^a-z0-9_-]/gi, '') + '"]:not([hidden])').trigger('click');
    }

    // "Non raccogliere i cordogli" nasconde il tab dell'editor del form; l'editor
    // viene avvisato della modalita' scelta per adattare l'anteprima.
    $('#modalita_form').on('change', function () {
        $('.impostazioni-header a[to="form"]').prop('hidden', this.value === 'nessuna');
        $(document).trigger('pfn:modalita-form', [this.value]);
    });

    // Tab SEO: segnaposto cliccabili e anteprima del risultato di ricerca.
    let $anteprima = $('.pfn-seo-anteprima');

    if ($anteprima.length) {

        let dati = $anteprima.data('anteprima') || {};
        let $titolo = $('#seo_titolo');
        let $desc = $('#seo_descrizione');
        let $ultimo = $titolo;

        let sostituisci = function (modello) {
            let testo = String(modello);
            $.each(dati.valori || {}, function (segnaposto, valore) { testo = testo.split(segnaposto).join(valore); });
            // Separatori rimasti orfani quando un segnaposto e' vuoto (come lato server).
            testo = testo.replace(/\s+/g, ' ').replace(/\s*([\-|–—·,])(?:\s*[\-|–—·,])+/g, ' $1').replace(/\s+,/g, ',');
            return testo.replace(/^[\s\-|–—·,]+|[\s\-|–—·,]+$/g, '');
        };

        let accorcia = function (testo, max) {
            if (testo.length <= max) { return testo; }
            let taglio = testo.slice(0, max);
            let spazio = taglio.lastIndexOf(' ');
            if (spazio > max * 0.6) { taglio = taglio.slice(0, spazio); }
            return taglio.replace(/[\s,.;:\-–]+$/, '') + '…';
        };

        let aggiorna = function () {
            let titolo = sostituisci($.trim($titolo.val()) || dati.titolo);
            let desc = $.trim($desc.val()) ? sostituisci($desc.val()) : (dati.valori['{testo}'] || '');
            $anteprima.find('.pfn-seo-anteprima-titolo').text(titolo);
            $anteprima.find('.pfn-seo-anteprima-desc').text(accorcia(desc, dati.max_desc || 155));
        };

        $titolo.add($desc).on('input', aggiorna).on('focus', function () { $ultimo = $(this); });

        $('.pfn-seo-chip').on('click', function () {
            let campo = $ultimo.get(0);
            let segnaposto = this.getAttribute('data-segnaposto');
            let inizio = campo.selectionStart != null ? campo.selectionStart : campo.value.length;
            let fine = campo.selectionEnd != null ? campo.selectionEnd : campo.value.length;
            campo.value = campo.value.slice(0, inizio) + segnaposto + campo.value.slice(fine);
            campo.focus();
            campo.setSelectionRange(inizio + segnaposto.length, inizio + segnaposto.length);
            aggiorna();
        });

        aggiorna();
    }

});
