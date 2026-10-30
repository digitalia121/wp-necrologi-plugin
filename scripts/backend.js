

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

});
