window.DgPlugin = new function () {

    const config = window.portaleFunebreNecrologiData || {};
    const plug_vars = config.vars || {};

    this.site_url = config.siteUrl || '';

    this.get_var = function (vname) { return plug_vars[vname]; };

    this.ajax = function (action_required, callback, data_c) {

        if (!callback) { return; }

        let dati_call = {
            action: 'portale_funebre_necrologi_ajax',
            dgplugin_action: action_required,
            nonce: config.nonce || ''
        };

        if (data_c) { for (let i in data_c) { dati_call[i] = data_c[i]; } }

        jQuery.ajax({
            type: 'POST',
            url:  config.ajaxUrl || '',
            data: dati_call,
            success: function (response) { if (!response.success) { console.log("NO SUCCESS"); } if (callback) { callback(response.data); } },
            error: function (error) { console.error('Error:', error); }
        });

    };

};

window.DgNecrologi = new function () {

    let settings = DgPlugin.get_var('necro_settings');

    let get_img_url = function (img_name) { return DgPlugin.get_var('necro_img_url')+img_name; };
    let get_necro_url = function (necro_slug) {
        let single = DgNecrologi.slugs.slug_singolo;
        return DgPlugin.get_var('necrologio_url')+'/'+necro_slug;
    };

    let singolo = {};

    let raccogli_dati_cordoglio = function ($form) {
        let out = {};
        $form.find('input, select, textarea').each(function () {
            let kname = this.getAttribute('name');
            let value = this.value;
            out[kname] = value;
        });
        return out;
    };

    let scheda_donazioni = function (dati) {

        if (!dati.ente_donazione && !dati.iban_donazione && !dati.codfisc_donazione) { return ''; }
        
        return `<div class="scheda-invito">
            <div class="scheda-invito-head">Donazioni</div>
            <div class="scheda-invito-body"><ul>
                <li><span>Ente benefico:</span>`+dati.ente_donazione+`</li>
                <li><span>Iban:</span>`+dati.iban_donazione+`</li>
                <li><span>Codice fiscale:</span>`+dati.codfisc_donazione+`</li>
            </ul></div>
        </div>`;
    }

    let mostra_dati_cerimonia = function (what, dati_cerimonia, impostazioni) {
        if (!impostazioni) { return ''; }
        let data_cerimonia = '';
        if (dati_cerimonia.data || dati_cerimonia.ora_da) {
            if (settings.mostra_dettagli == '1') { data_cerimonia += ' <span class="titoletto">Data:</span> '; }
            data_cerimonia += '<span class="data-cerimonia">'+(dati_cerimonia.data ? (new Date(dati_cerimonia.data)).toLocaleDateString("it-IT") : '')+'</span> ';
            if (settings.mostra_orari == '1') {
                if (settings.mostra_dettagli == '1') { data_cerimonia += ' - '; }
                data_cerimonia += ' <span class="ora_cerionia">'+(dati_cerimonia.ora_da ? dati_cerimonia.ora_da : '')+'</span>';
            }
            
        }
        let tit = '<h4>'+what+':</h4>';
        let luogo = '';
        if (dati_cerimonia.luogo)  { 
            if (settings.mostra_dettagli == '1') { luogo = ' <span class="titoletto">Luogo:</span> '+luogo; }
            luogo += '<span class="luogo-cerimonia">'+dati_cerimonia.luogo+'</span>';
        }
        switch(impostazioni) {
            case "luogo_data":
                if (!luogo && !data_cerimonia) { return ''; }
                return tit+'<div class="data-e-luogo"><div class="luogo-wrap">'+luogo+'</div> <div class="data-wrap">'+data_cerimonia+'</div></div>';
            case "data_luogo":
                if (!luogo && !data_cerimonia) { return ''; }
                return tit+'<div class="data-e-luogo"><div class="data-wrap">'+data_cerimonia+'</div> <div class="luogo-wrap">'+luogo+'</div></div>';
            case "data":
                if (!data_cerimonia) { return ''; }
                return tit+'<div class="data-e-luogo">'+data_cerimonia+'</div>';
            case "luogo":
                if (!luogo) { return ''; }
                return tit+'<div class="data-e-luogo">'+luogo+'</div>';

        }
    }

    this.crea_necrologio_loop = function (necro) {
        
        let link_div =  document.createElement('a');

        let necroURL = get_necro_url(necro.slug);
        link_div.setAttribute('href',necroURL);

        let mostra_un_bottone = (settings.con_bottone == '1') ? true : false;

        let out = (mostra_un_bottone) ? document.createElement('div') : link_div;
        
        out.classList.add('necrologio-loop');
        
        let thumbnail = get_img_url(necro.thumbnail);

        let defunto   = necro.dati_defunto;
        
        
        let loop_code = '<div class="necro-content"><picture>';
        if (mostra_un_bottone) { loop_code += '<a href="'+necroURL+'">'; }
        loop_code    += ((necro.thumbnail) ? '<img src="'+thumbnail+'"/>' : '');
        if (mostra_un_bottone) { loop_code += '</a>'; }
        loop_code    += '</picture><div class="necro-description">';
        if (mostra_un_bottone) { loop_code += '<a href="'+necroURL+'">'; }
        loop_code    += '<h2>'+necro.nome_defunto+'</h2>';
        if (mostra_un_bottone) { loop_code += '</a>'; }
        
        if (defunto) {
            
            let data_morte   = (defunto.data_morte)  ? (new Date(defunto.data_morte)) : null;
            let data_nascita = (defunto.data_nascita)  ? (new Date(defunto.data_nascita)) : null;

            if (settings.mostra_eta == '1' && data_morte && data_nascita) {

                let anni = data_morte.getFullYear() - data_nascita.getFullYear();

                if (settings.mostra_dettagli) {
                    loop_code += '<div class="eta-defunto"><span class="titolo-eta-defunto">Età:</span> '+anni+' Anni</div>';
                } else {
                    loop_code += '<div class="eta-defunto">'+anni+' Anni</div>';
                }

                if (settings.mostra_decesso == '1' && data_morte) {
                    if (settings.mostra_dettagli) {
                        loop_code += '<div class="data-decesso">Data del decesso: '+data_morte.toLocaleDateString("it-IT")+'</div>';
                    } else {
                        loop_code += '<div class="data-decesso">'+data_morte.toLocaleDateString("it-IT")+'</div>';
                    }
                    
                }
                
            }
        }
        
        loop_code    += mostra_dati_cerimonia('Funerale', necro.funerale, settings.cerimonia); 
        loop_code    += mostra_dati_cerimonia('Rosario', necro.rosario, settings.rosario);
        loop_code    += '</div>';
        if (mostra_un_bottone) {
            loop_code += '<div class="button-wrapper"><a class="button bottone-vai-al-necrologio" href="'+get_necro_url(necro.slug)+'"> Vai al necrologio</a></div>';
        }
        loop_code    += '</div>';
        out.innerHTML = loop_code;

        return out;

    };

    this.crea_necrologio_slide = function (necro) {
        
        let link_div =  document.createElement('a');
        link_div.setAttribute('href',get_necro_url(necro.slug));
        
        let out = link_div;
        
        out.classList.add('necrologio-slide');
        
        let thumbnail = get_img_url(necro.thumbnail);

        let defunto   = necro.dati_defunto;
        
        let loop_code = '<div class="necro-content">';
        loop_code    += '<picture>'+((necro.thumbnail) ? '<img src="'+thumbnail+'"/>' : '')+'</picture>';
        loop_code    += '<div class="necro-description">';
        loop_code    += '<h2>'+necro.nome_defunto+'</h2>';
        
        if (defunto) {
            
            let data_morte   = (defunto.data_morte)  ? (new Date(defunto.data_morte)) : null;
            let data_nascita = (defunto.data_nascita)  ? (new Date(defunto.data_nascita)) : null;

            if (settings.slide_mostra_eta == '1' && data_morte && data_nascita) {

                let anni = data_morte.getFullYear() - data_nascita.getFullYear();

                if (settings.mostra_eta) {
                    loop_code += '<div class="eta-defunto">'+anni+' Anni</div>';
                }
                
            }
            
            if (settings.slide_mostra_decesso == '1' && data_morte) {
                    
                loop_code += '<div class="data-decesso">'+data_morte.toLocaleDateString("it-IT")+'</div>';
                
            }

            if (settings.slide_cerimonia) {
                loop_code    += mostra_dati_cerimonia('Funerale', necro.funerale, settings.cerimonia); 
            }
            if (settings.slide_rosario) {
                loop_code    += mostra_dati_cerimonia('Rosario', necro.rosario, settings.rosario);
            }
            
        }
        
        loop_code    += '</div> </div>';
        out.innerHTML = loop_code;

        return out;

    };

    this.crea_necrologio_singolo = function (necro) {
        
        let necroContent = document.querySelector('.post-necrologio');
        let nomeDefunto  = necroContent.querySelector('.necro-nome-defunto');
        let fotoDefunto  = necroContent.querySelector('picture');
        let testoDefunto = necroContent.querySelector('.necro-testo');
        let mappaWrapper = necroContent.querySelector('.necro-mappa');
        let sezioneDona  = necroContent.querySelector('.sezione-donazioni');

        
        let defunto   = necro.dati_defunto;

        if (settings.defunto_in_hero == "1") {
            nomeDefunto  = necroContent.querySelector('.necro-hero-section h1');
        }

        let titolo_cerimonia = (necro && necro.nome_defunto) ? necro.nome_defunto : '';

        if (!titolo_cerimonia) {
            necroContent.innerHTML = '<h1>Non Trovato</h1><p>Questo necrologio non è stato trovato</p>';
            mappaWrapper.innerHTML = '';
        }

        let TESTO_NECROLOGIO = '';
        
        let data_morte   = (defunto.data_morte)   ? (new Date(defunto.data_morte)) : null;
        let data_nascita = (defunto.data_nascita) ? (new Date(defunto.data_nascita)) : null;

        if (settings.eta_su_singolo == '1' && data_morte && data_nascita) { 

            let anni = data_morte.getFullYear() - data_nascita.getFullYear();

            if (settings.mostra_dettagli) {
                TESTO_NECROLOGIO += '<div class="eta-defunto"><span class="titolo-eta-defunto">Età:</span> '+anni+' Anni</div>';
            } else {
                TESTO_NECROLOGIO += '<div class="eta-defunto">'+anni+' Anni</div>';
            }

        }

        let mono      = (necro.cordogli.whatsapp ^ necro.cordogli.email ^ necro.cordogli.pdf) ? true : false;
        let nessuno   = !necro.cordogli.whatsapp && !necro.cordogli.email && !necro.cordogli.pdf;
        let tipo_mono = '';

        if (mono) {
            if (necro.cordogli.email) { tipo_mono = 'email'; }
            if (necro.cordogli.pdf) { tipo_mono = 'pdf'; }
            if (necro.cordogli.whatsapp) { tipo_mono = 'whatsapp'; }
        } else {
            tipo_mono = 'pdf';
            mono      = true;
            nessuno   = false;
        }

        singolo.is_mono              = mono;
        singolo.mono_tipo            = tipo_mono;
        singolo.has_nessun_cordoglio = nessuno;

        if (settings.mostra_donazioni) {
            let donacode = scheda_donazioni(necro.donazioni);
            if (donacode) {
                sezioneDona.innerHTML = donacode;
            } else {
                sezioneDona.remove();
            }
        }

        nomeDefunto.innerText = titolo_cerimonia;

        if (necro.thumbnail) {
            let immagine = get_img_url(necro.thumbnail);
            fotoDefunto.innerHTML = '<a href="'+immagine+'" target="_blank"><img src="'+immagine+'"/></a>';
        }
        if (necro.testo) {
            TESTO_NECROLOGIO += necro.testo;
        }

        testoDefunto.innerHTML = TESTO_NECROLOGIO;

        let sluggerInput = document.querySelector('.cordo-form-wrapper form input[name="slug"]');
        sluggerInput.value = necro.slug;

        return singolo;
        
    };


    let validaMail = function (email) {
        return email.match(
            /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        );
    };

    let convalida_form = function ($form) {

        let is_valid = true;

        $form.find('.convalidazione').html('<div class="dg_spinner"></div>');

        $form.find('input[required], textarea[required], select[required]').each(function () {
            if (!is_valid) { return; }
            let mex = '';
            
            if (!this.id) {
                if (this.checked) { return; }
                is_valid = false;
                mex ='Devi prima accettare la privacy policy!';
            } else {
                if (this.value) { 
                    if (this.getAttribute('type') == 'email') {
                        if (validaMail(this.value)) { return; }
                        else {
                            mex ='l\'email inserita è in un formato non valido';
                            is_valid = false;
                        }
                    } else {
                        return;

                    }
                }
                is_valid = false;
                if (!mex) {
                    let campo = $form.find('label[for="'+this.id+'"]').text();
                    mex = campo+' è un campo obbligatorio';
                }
            }
            $form.find('.convalidazione').html('<h4 class="messaggio-error">'+mex+'</h4>');
        });

        if (is_valid) {
            $form.find('button.invia-a-api').removeClass('disabled');
        } else {
            $form.find('button.invia-a-api').addClass('disabled');
        }

        return is_valid;

    };


    this.invia_cordoglio = function ($form_wrapper) {

        $form = $form_wrapper.find('form');

        if (convalida_form($form)) {

            let dati = raccogli_dati_cordoglio($form);
            
            DgPlugin.ajax('invia_cordoglio_api',function (res) {
                if (res.status == 'success') {
                    $form.find('.input-content').html('<div class="necrologi-loader"><h2 class="messaggio-success">Invio riuscito</h2><p>Il tuo cordoglio è stato inviato. Grazie</p></div>');
                } else {
                    $form.find('.input-content').html('<div class="necrologi-loader"><h1 class="messaggio-error">Errore</h1><p>Abbiamo riscontrato un errore. Si prega di riprovare piu tardi</p></div>');
                }
                
            },dati);

        }
        
    };
    
};

jQuery(document).ready(function ($) {

    let crea_embed_map = function (indirizzo, zoomL) {
        let econded_ind = indirizzo.replaceAll(',', '').replaceAll(' ', '%20');
        if (!zoomL) { zoomL = 14; }
        let map_url = 'https://maps.google.com/maps?width=100%25&amp;height=450&amp;hl=en&amp;q=' + econded_ind + '&amp;t=&amp;z=' + zoomL + '&amp;ie=UTF8&amp;iwloc=B&amp;output=embed';
        return '<iframe src="' + map_url + '" width="100%" height="450" style="border:0;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
    };

    $('.lista-necrologi[data-pfn-list]').each(function () {
        let $looper = $(this);
        let $loader = $looper.prev('.necrologi-loader');

        DgNecrologi.slugs = {
            slug_singolo: $looper.data('slug-singolo')
        };

        DgPlugin.ajax('get_lista_necrologi', function (cerimonie) {
            for (let i in cerimonie) {
                if (cerimonie[i].nome_defunto) {
                    let n_div = DgNecrologi.crea_necrologio_loop(cerimonie[i]);
                    $looper.append(n_div);
                }
            }
            $loader.css({display: 'none'});
        });

        if ($looper.data('tipo-vis') === 'grid' && $looper.hasClass('slider') && typeof $looper.slick === 'function') {
            $looper.slick({
                dots: false,
                infinite: true,
                speed: 300,
                slidesToShow: 8,
                slidesToScroll: 8,
                responsive: [
                    { breakpoint: 1024, settings: { slidesToShow: 6, slidesToScroll: 6, infinite: true, dots: true } },
                    { breakpoint: 800, settings: { slidesToShow: 4, slidesToScroll: 4, infinite: true, dots: true } },
                    { breakpoint: 600, settings: { slidesToShow: 3, slidesToScroll: 3, infinite: true, dots: true } },
                    { breakpoint: 480, settings: { slidesToShow: 2, slidesToScroll: 2 } },
                    { breakpoint: 380, settings: { slidesToShow: 1, slidesToScroll: 1 } }
                ]
            });
        }
    });

    $('.lista-necrologi[data-pfn-slider]').each(function () {
        let $looper = $(this);
        let $loader = $looper.prev('.necrologi-loader');
        let slideDesktop = parseInt($looper.data('slide-desktop'), 10) || 4;
        let slideTablet = parseInt($looper.data('slide-tablet'), 10) || 3;
        let slideMobile = parseInt($looper.data('slide-mobile'), 10) || 2;

        DgNecrologi.slugs = {
            slug_singolo: $looper.data('slug-singolo')
        };

        DgPlugin.ajax('get_anteprima_necrologi', function (cerimonie) {
            for (let i in cerimonie) {
                if (cerimonie[i].nome_defunto) {
                    let n_div = DgNecrologi.crea_necrologio_slide(cerimonie[i]);
                    $looper.append(n_div);
                }
            }

            $loader.css({display: 'none'});

            if (typeof $looper.slick == 'function') {
                $looper.slick({
                    dots: false,
                    infinite: true,
                    speed: 300,
                    slidesToShow: Math.ceil(slideDesktop * 1.14),
                    prevArrow: $looper.attr('data-prev-arrow'),
                    nextArrow: $looper.attr('data-next-arrow'),
                    slidesToScroll: 1,
                    responsive: [
                        { breakpoint: 1980, settings: { slidesToShow: slideDesktop } },
                        { breakpoint: 1280, settings: { slidesToShow: slideTablet } },
                        { breakpoint: 768, settings: { slidesToShow: slideMobile } },
                        { breakpoint: 480, settings: { slidesToShow: 1 } }
                    ]
                });
            }
        });
    });

    $('.post-necrologio[data-pfn-single]').each(function () {
        let $post = $(this);
        let $loader = $('.necrologi-loader');
        let $necroMappa = $post.find('.necro-mappa');
        let slug = $post.data('slug-necrologio');
        let noPopUp = $post.data('no-popup') == 1;

        DgPlugin.ajax('get_necrologio_singolo', function (cerimonia) {
            if (!cerimonia) {
                window.location = $post.data('not-found-url');
                return;
            }

            let agenzia = 'Onoranze Funebri';

            if (cerimonia.agenzia && cerimonia.agenzia.ragione_sociale) {
                agenzia = cerimonia.agenzia.ragione_sociale;
            }

            document.title = cerimonia.nome_defunto + ' - ' + agenzia;

            let $formCord = $post.find('.cordo-form-wrapper');
            let dati_necro = DgNecrologi.crea_necrologio_singolo(cerimonia);

            if (noPopUp) {
                $formCord.attr('active', dati_necro.mono_tipo);
            }

            if (!cerimonia.cordogli.whatsapp) {
                $post.find('button.manda-con-whatsapp').remove();
                $post.find('.button.scrivi-su-whatsapp').remove();
            } else if (cerimonia.contatto_principale.whatsapp) {
                let link_wt = 'https://api.whatsapp.com/send?phone=' + cerimonia.contatto_principale.whatsapp + '&text=';
                $post.find('.button.scrivi-su-whatsapp').attr('href', link_wt);
            }
            if (!cerimonia.cordogli.email) { $post.find('button.manda-con-email').remove(); }
            if (!cerimonia.cordogli.pdf) { $post.find('button.manda-con-pdf').remove(); }

            $loader.fadeOut();

            if (dati_necro.has_nessun_cordoglio) {
                $post.find('.invia-cordoglio').remove();
            }

            if (cerimonia.mappa) {
                $necroMappa.find('.mappa-cerimonia').html(crea_embed_map(cerimonia.mappa), 14);
            } else {
                $necroMappa.remove();
            }

            $post.find('.invia-cordoglio').click(function () {
                if (noPopUp) {
                    $("html, body").animate({ scrollTop: $('#form-di-cordoglio').offset().top - 70 }, 1000);
                    return;
                }
                if (dati_necro.is_mono) {
                    $formCord.attr('active', dati_necro.mono_tipo);
                    return;
                }
                $formCord.attr('active', '');
            });

            $post.find('button.popclose').click(function () {
                $formCord.removeAttr('active');
            });

            $post.find('.form-buttons .manda-con-email').click(function () {
                $formCord.attr('active', 'email');
                $formCord.find('input[name="tipo"]').val('email');
            });
            $post.find('.form-buttons .manda-con-pdf').click(function () {
                $formCord.attr('active', 'pdf');
                $formCord.find('input[name="tipo"]').val('pdf');
            });
            $post.find('.form-buttons .manda-con-whatsapp').click(function () {
                $formCord.attr('active', 'whatsapp');
                $formCord.find('input[name="tipo"]').val('whatsapp');
            });

            $post.find('form .invia-a-api').click(function() {
                DgNecrologi.invia_cordoglio($formCord);
            });

            $post.fadeIn();

            const shareUrl = $post.data('share-url');

            if (cerimonia.lista_links && cerimonia.lista_links.length > 0) {
                let $cusLinkSec = $post.find('div.custom-links');
                $cusLinkSec.append('<h5>Altri link</h5>');
                let links = cerimonia.lista_links;
                for (let i in links) {
                    $cusLinkSec.append('<a href="' + links[i].url + '" target="_blank">' + links[i].testo + '</a>');
                }
            }

            $post.find('.social-share .shs-icon-wrapper').each(function () {
                $post.find('.share-on.fb').attr('href', `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`);
                $post.find('.share-on.wa').attr('href', `https://wa.me/?text=${shareUrl}`);
                $post.find('.share-on.tw').attr('href', `https://x.com/intent/post?text=${shareUrl}`);
            });
        }, { slug: slug });
    });

});
