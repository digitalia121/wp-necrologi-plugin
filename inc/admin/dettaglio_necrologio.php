<?php

use function PortaleFunebreNecrologi\get_plugin_page_url;

if (!defined('ABSPATH')) { exit; }

/*
 * Pagina di un singolo necrologio nel backend: tutte le informazioni del
 * portale e le impostazioni SEO del necrologio (come il box di Yoast su un
 * articolo). Impaginata come la schermata di modifica di WordPress.
 *
 * Variabili disponibili: $url_pagina (pagina "Necrologi").
 */

$slug = isset($_GET['necrologio']) ? sanitize_title(wp_unslash($_GET['necrologio'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo lettura.
$dati = PortaleFunebreNecrologi_Repository::GetSingoloCompleto($slug);

$torna = '<p><a href="' . esc_url($url_pagina) . '">&larr; Tutti i necrologi</a></p>';

if (is_wp_error($dati)) {
    echo $torna . '<div class="notice notice-error inline"><p>Il portale dei necrologi non risponde in questo momento. Riprova tra qualche minuto.</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}
if (!$dati) {
    echo $torna . '<div class="notice notice-warning inline"><p>Questo necrologio non esiste o è stato rimosso dal portale.</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}

$necro      = new PortaleFunebreNecrologi_Necrologio($dati);
$seo        = PortaleFunebreNecrologi_SeoNecrologio::Get($slug);
$url_sito   = $necro->Url();
$url_portale = PortaleFunebreNecrologi_API::GetAreaRiservataUrl();
$morte      = $necro->DataMorte();
$nascita    = $necro->DataNascita();
$eta        = $necro->Eta();
$pubblicato = !empty($dati->pubblicato_il) ? strtotime($dati->pubblicato_il) : false;
$scheda     = isset($_GET['scheda']) && in_array($_GET['scheda'], ['seo', 'social', 'avanzate'], true) ? sanitize_key($_GET['scheda']) : 'seo'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$data_breve = static function ($valore) {
    $data = PortaleFunebreNecrologi_Necrologio::Data($valore);
    return $data ? PortaleFunebreNecrologi_Render::DataBreve($data) : '';
};

/* Cerimonie: solo quelle previste. */
$nomi_cerimonie = [
    'funerale'           => 'Funerale',
    'rosario'            => 'Rosario',
    'partenza'           => 'Partenza',
    'chiusura_feretro'   => 'Chiusura feretro',
    'tumulazione_ceneri' => 'Tumulazione ceneri',
];
$cerimonie = [];
foreach ($nomi_cerimonie as $tipo => $nome_cerimonia) {
    $c = $necro->Cerimonia($tipo);
    if ($c['luogo'] || $c['data']) { $cerimonie[] = ['nome' => $nome_cerimonia] + $c; }
}
if (!empty($dati->visite_familiari) && is_array($dati->visite_familiari)) {
    foreach ($dati->visite_familiari as $visita) {
        if (!is_object($visita)) { continue; }
        $cerimonie[] = [
            'nome'   => 'Visita dei familiari',
            'luogo'  => isset($visita->luogo) ? (string) $visita->luogo : '',
            'data'   => isset($visita->data) ? (string) $visita->data : '',
            'ora_da' => isset($visita->ora_da) ? (string) $visita->ora_da : '',
            'ora_a'  => isset($visita->ora_a) ? (string) $visita->ora_a : '',
        ];
    }
}

/* Cordogli ricevuti, per canale. */
$nomi_canali = ['pdf' => 'PDF', 'email' => 'Email', 'whatsapp' => 'WhatsApp'];
$canali      = $necro->Cordogli();
$cordogli    = [];
foreach ($nomi_canali as $tipo => $nome_canale) {
    if (empty($dati->cordogli->$tipo) || !is_array($dati->cordogli->$tipo)) { continue; }
    foreach ($dati->cordogli->$tipo as $cordoglio) {
        if (is_object($cordoglio)) { $cordogli[] = ['canale' => $nome_canale, 'dati' => $cordoglio]; }
    }
}
$url_cordogli = wp_nonce_url(get_plugin_page_url('cordogli') . '&defunto=' . rawurlencode($slug), 'portale_funebre_necrologi_view_condolences');

/* Dati per l'anteprima e l'analisi SEO (JavaScript). */
$valori = [];
foreach (array_keys(PortaleFunebreNecrologi_Seo::SEGNAPOSTO) as $segnaposto) {
    $valori[$segnaposto] = PortaleFunebreNecrologi_Seo::Sostituisci($segnaposto, $necro);
}
$immagine_social = PortaleFunebreNecrologi_Seo::ImmagineDaMedia($seo['social_immagine']);
$foto_social     = $necro->Immagine();

$dati_editor = [
    'valori'           => $valori,
    'titolo_generale'  => PortaleFunebreNecrologi_Seo::ModelloTitolo(''),
    'descrizione_auto' => PortaleFunebreNecrologi_Seo::DescrizionePer($necro, ''),
    'max_desc'         => PortaleFunebreNecrologi_Seo::LUNGHEZZA_DESCRIZIONE,
    'testo'            => $necro->TestoSemplice(),
    'url'              => $url_sito,
    'dominio'          => wp_parse_url(home_url(), PHP_URL_HOST),
    'foto'             => $foto_social,
    'ha_foto'          => (bool) $foto_social,
    'sito_pubblico'    => (bool) get_option('blog_public'),
];

/* Stato della cache del necrologio. */
$cache         = PortaleFunebreNecrologi_Repository::StatoCacheSingolo($slug);
$cache_minuti  = (int) round(PortaleFunebreNecrologi_Repository::Durata() / MINUTE_IN_SECONDS);
$registro      = PortaleFunebreNecrologi_Monitor::Registro();
$registro      = isset($registro[$slug]) ? $registro[$slug] : null;
$file_social   = PortaleFunebreNecrologi::FileImmagineShare($necro->Immagine());
$dim_social    = ($file_social && file_exists($file_social['percorso'])) ? wp_getimagesize($file_social['percorso']) : false;
$cache_pagina  = PortaleFunebreNecrologi_Monitor::PluginCachePagina();
$tra           = static function ($ts) { return $ts > time() ? 'tra ' . human_time_diff(time(), $ts) : 'scaduta'; };

$campo = static function ($nome) { return 'pfn_seo[' . $nome . ']'; };

$chips = static function ($bersaglio) {
    $html = '<div class="pfn-nd-chips" data-bersaglio="' . esc_attr($bersaglio) . '">';
    foreach (PortaleFunebreNecrologi_Seo::SEGNAPOSTO as $segnaposto => $descrizione) {
        $html .= '<button type="button" class="pfn-nd-chip" data-segnaposto="' . esc_attr($segnaposto) . '" title="' . esc_attr($descrizione) . '">' . esc_html($segnaposto) . '</button>';
    }
    return $html . '</div>';
};

?>

<div class="pfn-nd" data-editor="<?php echo esc_attr(wp_json_encode($dati_editor)); ?>">

    <p class="pfn-nd-torna"><a href="<?php echo esc_url($url_pagina); ?>">&larr; Tutti i necrologi</a></p>

    <div class="pfn-nd-testata">
        <h2 class="pfn-nd-nome"><?php echo esc_html($necro->Nome()); ?></h2>
        <a class="button" href="<?php echo esc_url($url_sito); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-external" aria-hidden="true"></span> Vedi sul sito</a>
        <a class="button" href="<?php echo esc_url($url_portale); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span> Vedi sul portale</a>
    </div>

    <?php if (!empty($_GET['aggiornato'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <div class="notice notice-success is-dismissible inline"><p>Impostazioni SEO salvate. La pagina del necrologio è già aggiornata.</p></div>
    <?php } ?>
    <?php if (isset($_GET['cache'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <?php if ($_GET['cache'] === 'aggiornata') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
            <div class="notice notice-success is-dismissible inline"><p>Necrologio aggiornato dal portale: il sito mostra già i dati più recenti.</p></div>
        <?php } else { ?>
            <div class="notice notice-error is-dismissible inline"><p>Il portale non ha risposto: il sito continua a mostrare la copia salvata. Riprova tra qualche minuto.</p></div>
        <?php } ?>
    <?php } ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pfn-nd-form">
        <input type="hidden" name="action" value="<?php echo esc_attr(PortaleFunebreNecrologi_SeoNecrologio::AZIONE); ?>"/>
        <input type="hidden" name="necrologio" value="<?php echo esc_attr($slug); ?>"/>
        <input type="hidden" name="pfn_scheda" value="<?php echo esc_attr($scheda); ?>" class="pfn-nd-scheda-attiva"/>
        <?php wp_nonce_field(PortaleFunebreNecrologi_SeoNecrologio::AZIONE . '_' . $slug); ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">

                <div id="post-body-content">

                    <!-- Necrologio -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Necrologio</h2></div>
                        <div class="inside pfn-nd-necrologio">
                            <?php if ($necro->Immagine()) { ?>
                                <a class="pfn-nd-foto" href="<?php echo esc_url($necro->Immagine()); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url($necro->Immagine()); ?>" alt="<?php echo esc_attr(PortaleFunebreNecrologi_Render::AltFoto($necro)); ?>"/></a>
                            <?php } ?>
                            <div class="pfn-nd-necrologio-testo">
                                <dl class="pfn-nd-dati">
                                    <?php if ($nascita) { ?><dt>Nascita</dt><dd><?php echo esc_html(PortaleFunebreNecrologi_Seo::DataEstesa($nascita)); ?></dd><?php } ?>
                                    <?php if ($morte) { ?><dt>Decesso</dt><dd><?php echo esc_html(PortaleFunebreNecrologi_Seo::DataEstesa($morte)); ?></dd><?php } ?>
                                    <?php if ($eta !== null) { ?><dt>Età</dt><dd><?php echo esc_html($eta); ?> anni</dd><?php } ?>
                                    <?php if ($necro->Mappa()) { ?><dt>Luogo della cerimonia (mappa)</dt><dd><?php echo esc_html($necro->Mappa()); ?></dd><?php } ?>
                                </dl>
                                <div class="pfn-nd-epigrafe"><?php echo $necro->Testo() ? wp_kses_post($necro->Testo()) : '<p class="description">Nessun testo.</p>'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="postbox pfn-nd-seo" id="pfn-seo">
                        <div class="postbox-header">
                            <h2 class="hndle">SEO<?php if (PortaleFunebreNecrologi_Seo_Yoast::IsAttivo()) { ?> <span class="pfn-nd-yoast">integrato con Yoast SEO</span><?php } ?></h2>
                        </div>
                        <div class="inside">

                            <nav class="pfn-nd-schede" role="tablist">
                                <?php foreach (['seo' => ['dashicons-search', 'SEO'], 'social' => ['dashicons-share', 'Social'], 'avanzate' => ['dashicons-admin-generic', 'Avanzate']] as $id_scheda => $s) { ?>
                                    <button type="button" role="tab" class="pfn-nd-scheda<?php echo $scheda === $id_scheda ? ' is-attiva' : ''; ?>" data-scheda="<?php echo esc_attr($id_scheda); ?>" aria-selected="<?php echo $scheda === $id_scheda ? 'true' : 'false'; ?>"><span class="dashicons <?php echo esc_attr($s[0]); ?>" aria-hidden="true"></span><?php echo esc_html($s[1]); ?></button>
                                <?php } ?>
                            </nav>

                            <!-- Scheda SEO -->
                            <div class="pfn-nd-pannello" data-pannello="seo"<?php echo $scheda !== 'seo' ? ' hidden' : ''; ?>>

                                <div class="pfn-nd-campo">
                                    <label for="pfn-frase-chiave">Frase chiave</label>
                                    <input type="text" id="pfn-frase-chiave" class="regular-text" name="<?php echo esc_attr($campo('frase_chiave')); ?>" value="<?php echo esc_attr($seo['frase_chiave']); ?>" placeholder="es. <?php echo esc_attr(strtolower($necro->Nome())); ?>"/>
                                    <p class="description">Le parole con cui ci si aspetta che il necrologio venga cercato. Serve per l'analisi qui a fianco, non viene mostrata sul sito.</p>
                                </div>

                                <div class="pfn-nd-anteprima-google" aria-label="Anteprima su Google">
                                    <span class="pfn-nd-etichetta">Anteprima su Google</span>
                                    <div class="pfn-nd-g-sito">
                                        <span class="pfn-nd-g-icona" aria-hidden="true"><?php echo esc_html(strtoupper(mb_substr(PortaleFunebreNecrologi_Seo::NomeSito(), 0, 1))); ?></span>
                                        <span><span class="pfn-nd-g-nome"><?php echo esc_html(PortaleFunebreNecrologi_Seo::NomeSito()); ?></span><span class="pfn-nd-g-url"></span></span>
                                    </div>
                                    <div class="pfn-nd-g-titolo"></div>
                                    <div class="pfn-nd-g-desc"></div>
                                </div>

                                <div class="pfn-nd-campo">
                                    <label for="pfn-titolo">Titolo SEO</label>
                                    <input type="text" id="pfn-titolo" class="large-text pfn-nd-modello" name="<?php echo esc_attr($campo('titolo')); ?>" value="<?php echo esc_attr($seo['titolo']); ?>" placeholder="<?php echo esc_attr(PortaleFunebreNecrologi_Seo::ModelloTitolo('')); ?>"/>
                                    <div class="pfn-nd-barra" data-per="titolo"><span></span></div>
                                    <p class="description">Vuoto = modello generale della tab SEO delle impostazioni.</p>
                                    <?php echo $chips('pfn-titolo'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>

                                <div class="pfn-nd-campo">
                                    <label for="pfn-descrizione">Meta description</label>
                                    <textarea id="pfn-descrizione" class="large-text pfn-nd-modello" rows="3" name="<?php echo esc_attr($campo('descrizione')); ?>" placeholder="Automatica: <?php echo esc_attr($dati_editor['descrizione_auto']); ?>"><?php echo esc_textarea($seo['descrizione']); ?></textarea>
                                    <div class="pfn-nd-barra" data-per="descrizione"><span></span></div>
                                    <?php echo $chips('pfn-descrizione'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>

                            </div>

                            <!-- Scheda Social -->
                            <div class="pfn-nd-pannello" data-pannello="social"<?php echo $scheda !== 'social' ? ' hidden' : ''; ?>>

                                <div class="pfn-nd-anteprima-social" aria-label="Anteprima su Facebook">
                                    <span class="pfn-nd-etichetta">Anteprima su Facebook e WhatsApp</span>
                                    <div class="pfn-nd-fb">
                                        <div class="pfn-nd-fb-img"><img src="<?php echo esc_url($immagine_social ? $immagine_social['url'] : $foto_social); ?>" alt=""<?php echo ($immagine_social || $foto_social) ? '' : ' hidden'; ?>/></div>
                                        <div class="pfn-nd-fb-testo">
                                            <span class="pfn-nd-fb-dominio"><?php echo esc_html(strtoupper((string) $dati_editor['dominio'])); ?></span>
                                            <span class="pfn-nd-fb-titolo"></span>
                                            <span class="pfn-nd-fb-desc"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pfn-nd-campo">
                                    <label>Immagine social</label>
                                    <div class="pfn-nd-media">
                                        <input type="hidden" name="<?php echo esc_attr($campo('social_immagine')); ?>" value="<?php echo esc_attr($seo['social_immagine'] ? $seo['social_immagine'] : ''); ?>" class="pfn-nd-media-id"/>
                                        <button type="button" class="button pfn-nd-media-scegli">Scegli un'immagine</button>
                                        <button type="button" class="button-link pfn-nd-media-rimuovi"<?php echo $seo['social_immagine'] ? '' : ' hidden'; ?>>Usa la foto del necrologio</button>
                                    </div>
                                    <p class="description">Predefinita: la foto del necrologio in formato ridotto, che su Facebook produce la scheda compatta con la foto a sinistra.</p>
                                </div>

                                <div class="pfn-nd-campo">
                                    <label for="pfn-social-titolo">Titolo social</label>
                                    <input type="text" id="pfn-social-titolo" class="large-text pfn-nd-modello" name="<?php echo esc_attr($campo('social_titolo')); ?>" value="<?php echo esc_attr($seo['social_titolo']); ?>" placeholder="Uguale al titolo SEO"/>
                                    <?php echo $chips('pfn-social-titolo'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>

                                <div class="pfn-nd-campo">
                                    <label for="pfn-social-descrizione">Descrizione social</label>
                                    <textarea id="pfn-social-descrizione" class="large-text pfn-nd-modello" rows="2" name="<?php echo esc_attr($campo('social_descrizione')); ?>" placeholder="Uguale alla meta description"><?php echo esc_textarea($seo['social_descrizione']); ?></textarea>
                                    <?php echo $chips('pfn-social-descrizione'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </div>

                            </div>

                            <!-- Scheda Avanzate -->
                            <div class="pfn-nd-pannello" data-pannello="avanzate"<?php echo $scheda !== 'avanzate' ? ' hidden' : ''; ?>>

                                <div class="pfn-nd-campo">
                                    <label for="pfn-indicizza">Mostrare questo necrologio nei risultati di ricerca?</label>
                                    <select id="pfn-indicizza" name="<?php echo esc_attr($campo('noindex')); ?>">
                                        <option value="">Sì (predefinito)</option>
                                        <option value="1"<?php selected($seo['noindex'], '1'); ?>>No, escludilo dai motori di ricerca</option>
                                    </select>
                                    <p class="description">Con "No" la pagina riceve il meta <code>noindex</code> ed esce dalla sitemap. Utile quando la famiglia chiede che il nome non compaia su Google.</p>
                                    <?php if (!get_option('blog_public')) { ?>
                                        <p class="pfn-nd-avviso">Al momento tutto il sito chiede ai motori di non indicizzarlo (Impostazioni › Lettura).</p>
                                    <?php } ?>
                                </div>

                                <div class="pfn-nd-campo">
                                    <label for="pfn-canonical">URL canonico</label>
                                    <input type="url" id="pfn-canonical" class="large-text" name="<?php echo esc_attr($campo('canonical')); ?>" value="<?php echo esc_attr($seo['canonical']); ?>" placeholder="<?php echo esc_attr($url_sito); ?>"/>
                                    <p class="description">Da cambiare solo se lo stesso necrologio è pubblicato anche altrove e la versione principale è quella.</p>
                                </div>

                            </div>

                        </div>
                    </div>

                    <!-- Cerimonie -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Cerimonie</h2></div>
                        <div class="inside">
                            <?php if ($cerimonie) { ?>
                                <table class="widefat striped pfn-nd-tabella">
                                    <thead><tr><th>Cerimonia</th><th>Luogo</th><th>Data</th><th>Orario</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($cerimonie as $c) { ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($c['nome']); ?></strong></td>
                                                <td><?php echo $c['luogo'] ? esc_html($c['luogo']) : '&mdash;'; ?></td>
                                                <td><?php echo $c['data'] ? esc_html($data_breve($c['data'])) : '&mdash;'; ?></td>
                                                <td><?php echo $c['ora_da'] ? esc_html($c['ora_da'] . ($c['ora_a'] ? ' – ' . $c['ora_a'] : '')) : '&mdash;'; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            <?php } else { ?>
                                <p class="description">Nessuna cerimonia indicata sul portale.</p>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Cordogli -->
                    <div class="postbox" id="pfn-cordogli">
                        <div class="postbox-header"><h2 class="hndle">Cordogli ricevuti <span class="count">(<?php echo (int) count($cordogli); ?>)</span></h2></div>
                        <div class="inside">
                            <?php if ($cordogli) { ?>
                                <table class="widefat striped pfn-nd-tabella">
                                    <thead><tr><th>Da</th><th>Contatti</th><th>Messaggio</th><th>Canale</th><th>Data</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($cordogli as $c) {
                                            $cd = $c['dati'];
                                            $mittente = trim((isset($cd->nome) ? $cd->nome : '') . ' ' . (isset($cd->cognome) ? $cd->cognome : ''));
                                            $quando   = !empty($cd->data_cordoglio) ? strtotime($cd->data_cordoglio) : false;
                                            ?>
                                            <tr>
                                                <td><strong><?php echo $mittente !== '' ? esc_html($mittente) : '&mdash;'; ?></strong></td>
                                                <td>
                                                    <?php if (!empty($cd->email)) { ?><a href="mailto:<?php echo esc_attr($cd->email); ?>"><?php echo esc_html($cd->email); ?></a><br><?php } ?>
                                                    <?php if (!empty($cd->telefono)) { ?><?php echo esc_html($cd->telefono); ?><?php } ?>
                                                </td>
                                                <td><?php echo !empty($cd->messaggio) ? nl2br(esc_html($cd->messaggio)) : '<span class="description">Nessun messaggio</span>'; ?></td>
                                                <td><?php echo esc_html($c['canale']); ?></td>
                                                <td><?php echo $quando ? esc_html(date_i18n('d/m/Y H:i', $quando)) : '&mdash;'; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <p><a href="<?php echo esc_url($url_cordogli); ?>">Apri nella pagina Cordogli &rarr;</a></p>
                            <?php } else { ?>
                                <p class="description">Nessun cordoglio ricevuto finora.</p>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Contatti -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Contatti</h2></div>
                        <div class="inside pfn-nd-colonne">
                            <?php
                            $principale = isset($dati->contatto_principale) && is_object($dati->contatto_principale) ? $dati->contatto_principale : null;
                            $parroco    = isset($dati->parroco) && is_object($dati->parroco) ? $dati->parroco : null;
                            $altri      = !empty($dati->contatti) && is_array($dati->contatti) ? $dati->contatti : [];
                            $nessuno    = true;
                            ?>
                            <?php if ($principale && (!empty($principale->nome_cognome) || !empty($principale->email) || !empty($principale->telefono_fisso) || !empty($principale->whatsapp))) { $nessuno = false; ?>
                                <dl class="pfn-nd-dati">
                                    <dt>Contatto principale</dt><dd><strong><?php echo esc_html(isset($principale->nome_cognome) ? $principale->nome_cognome : ''); ?></strong></dd>
                                    <?php if (!empty($principale->telefono_fisso)) { ?><dt>Telefono</dt><dd><?php echo esc_html($principale->telefono_fisso); ?></dd><?php } ?>
                                    <?php if (!empty($principale->whatsapp)) { ?><dt>WhatsApp</dt><dd><?php echo esc_html($principale->whatsapp); ?></dd><?php } ?>
                                    <?php if (!empty($principale->email)) { ?><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr($principale->email); ?>"><?php echo esc_html($principale->email); ?></a></dd><?php } ?>
                                </dl>
                            <?php } ?>
                            <?php foreach ($altri as $contatto) { if (!is_object($contatto)) { continue; } $nessuno = false; ?>
                                <dl class="pfn-nd-dati">
                                    <dt>Altro contatto</dt><dd><strong><?php echo esc_html(isset($contatto->nome) ? $contatto->nome : ''); ?></strong></dd>
                                    <?php if (!empty($contatto->tel)) { ?><dt>Telefono</dt><dd><?php echo esc_html($contatto->tel); ?></dd><?php } ?>
                                    <?php if (!empty($contatto->mail)) { ?><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr($contatto->mail); ?>"><?php echo esc_html($contatto->mail); ?></a></dd><?php } ?>
                                </dl>
                            <?php } ?>
                            <?php if ($parroco && (!empty($parroco->nome_parroco) || !empty($parroco->telefono_parroco))) { $nessuno = false; ?>
                                <dl class="pfn-nd-dati">
                                    <dt>Parroco</dt><dd><strong><?php echo esc_html(isset($parroco->nome_parroco) ? $parroco->nome_parroco : ''); ?></strong></dd>
                                    <?php if (!empty($parroco->telefono_parroco)) { ?><dt>Telefono</dt><dd><?php echo esc_html($parroco->telefono_parroco); ?></dd><?php } ?>
                                </dl>
                            <?php } ?>
                            <?php if ($nessuno) { ?><p class="description">Nessun contatto indicato sul portale.</p><?php } ?>
                        </div>
                    </div>

                    <!-- Altre informazioni -->
                    <?php
                    $donazioni = isset($dati->donazioni) && is_object($dati->donazioni) ? $dati->donazioni : null;
                    $ha_donazioni = $donazioni && (!empty($donazioni->ente_donazione) || !empty($donazioni->iban_donazione) || !empty($donazioni->codfisc_donazione));
                    $fiori     = !empty($dati->lista_fiori) && is_array($dati->lista_fiori) ? $dati->lista_fiori : [];
                    $servizi   = !empty($dati->servizi) && is_array($dati->servizi) ? $dati->servizi : [];
                    $documenti = !empty($dati->documenti) ? array_filter((array) $dati->documenti, 'is_object') : [];
                    ?>
                    <?php if ($ha_donazioni || $fiori || $servizi || $documenti) { ?>
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Altre informazioni</h2></div>
                        <div class="inside pfn-nd-colonne">
                            <?php if ($ha_donazioni) { ?>
                                <dl class="pfn-nd-dati">
                                    <dt>Donazioni</dt><dd><strong><?php echo esc_html((string) $donazioni->ente_donazione); ?></strong></dd>
                                    <?php if (!empty($donazioni->iban_donazione)) { ?><dt>IBAN</dt><dd><code><?php echo esc_html($donazioni->iban_donazione); ?></code></dd><?php } ?>
                                    <?php if (!empty($donazioni->codfisc_donazione)) { ?><dt>Codice fiscale</dt><dd><?php echo esc_html($donazioni->codfisc_donazione); ?></dd><?php } ?>
                                </dl>
                            <?php } ?>
                            <?php if ($fiori) { ?>
                                <dl class="pfn-nd-dati">
                                    <dt>Fiori</dt>
                                    <?php foreach ($fiori as $fiore) { if (!is_object($fiore)) { continue; } ?>
                                        <dd><strong><?php echo esc_html(isset($fiore->tipo) ? $fiore->tipo : ''); ?></strong><?php echo !empty($fiore->dettagli) ? ' ' . esc_html($fiore->dettagli) : ''; ?></dd>
                                    <?php } ?>
                                </dl>
                            <?php } ?>
                            <?php if ($servizi) { ?>
                                <dl class="pfn-nd-dati">
                                    <dt>Servizi</dt>
                                    <dd><?php foreach ($servizi as $servizio) { ?><span class="pfn-nd-tag"><?php echo esc_html(ucfirst((string) $servizio)); ?></span><?php } ?></dd>
                                </dl>
                            <?php } ?>
                            <?php if ($documenti) { ?>
                                <dl class="pfn-nd-dati">
                                    <dt>Documenti</dt>
                                    <?php foreach ($documenti as $doc) { ?>
                                        <dd>
                                            <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
                                            <a href="<?php echo esc_url(PortaleFunebreNecrologi_API::GetDocUrl(isset($doc->file) ? $doc->file : '')); ?>" target="_blank" rel="noopener"><?php echo esc_html(isset($doc->name) ? $doc->name : (isset($doc->file) ? $doc->file : 'Documento')); ?></a>
                                            <?php if (!empty($doc->desc)) { ?><br><span class="description"><?php echo esc_html($doc->desc); ?></span><?php } ?>
                                        </dd>
                                    <?php } ?>
                                </dl>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>

                </div>

                <div id="postbox-container-1" class="postbox-container">

                    <!-- Stato e salvataggio -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Stato</h2></div>
                        <div class="inside pfn-nd-stato">
                            <ul>
                                <li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span>Pubblicato sul portale<?php if ($pubblicato) { ?> il <strong><?php echo esc_html(date_i18n('j F Y, H:i', $pubblicato)); ?></strong><?php } ?></span></li>
                                <li><span class="dashicons <?php echo $seo['noindex'] === '1' ? 'dashicons-hidden' : 'dashicons-visibility'; ?>" aria-hidden="true"></span><span><?php echo $seo['noindex'] === '1' ? 'Escluso dai motori di ricerca' : 'Visibile nei motori di ricerca'; ?></span></li>
                                <li><span class="dashicons dashicons-email-alt" aria-hidden="true"></span><span>Cordogli via
                                    <?php
                                    $attivi = array_keys(array_filter($canali));
                                    echo $attivi ? esc_html(implode(', ', array_map(static function ($c) use ($nomi_canali) { return $nomi_canali[$c]; }, $attivi))) : 'nessun canale';
                                    ?>
                                </span></li>
                            </ul>
                            <p class="pfn-nd-link">
                                <a href="<?php echo esc_url($url_sito); ?>" target="_blank" rel="noopener">Vedi sul sito</a>
                                <a href="<?php echo esc_url($url_portale); ?>" target="_blank" rel="noopener">Vedi sul portale</a>
                            </p>
                        </div>
                        <div class="pfn-nd-azioni">
                            <span class="pfn-nd-modificato" hidden>Modifiche non salvate</span>
                            <?php submit_button('Salva SEO', 'primary', 'submit', false); ?>
                        </div>
                    </div>

                    <!-- Cache -->
                    <div class="postbox" id="pfn-cache">
                        <div class="postbox-header"><h2 class="hndle">Cache</h2></div>
                        <div class="inside pfn-nd-stato pfn-nd-cache">
                            <ul>
                                <li>
                                    <span class="dashicons dashicons-database<?php echo $cache['breve']['presente'] ? ' is-ok' : ''; ?>" aria-hidden="true"></span>
                                    <span><strong>Copia usata dal sito</strong><br>
                                        <?php if ($cache['breve']['presente']) { ?>
                                            Presente<?php echo $cache['breve']['scade'] ? ', si rinnova ' . esc_html($tra($cache['breve']['scade'])) : ''; ?>.
                                        <?php } elseif ($cache['breve']['assente']) { ?>
                                            Il portale ha risposto "non trovato"<?php echo $cache['breve']['scade'] ? ', nuovo tentativo ' . esc_html($tra($cache['breve']['scade'])) : ''; ?>.
                                        <?php } else { ?>
                                            Non presente: verrà letta dal portale alla prossima visita.
                                        <?php } ?>
                                        <span class="description">Durata: <?php echo esc_html($cache_minuti); ?> minuti.</span>
                                    </span>
                                </li>
                                <li>
                                    <span class="dashicons dashicons-backup<?php echo $cache['riserva']['presente'] ? ' is-ok' : ''; ?>" aria-hidden="true"></span>
                                    <span><strong>Copia di riserva</strong><br>
                                        <?php if ($cache['riserva']['presente']) { ?>
                                            Presente<?php echo $cache['riserva']['scade'] ? ' fino al ' . esc_html(wp_date('j F, H:i', $cache['riserva']['scade'])) : ''; ?>. Usata se il portale non risponde.
                                        <?php } else { ?>
                                            Non presente.
                                        <?php } ?>
                                    </span>
                                </li>
                                <li>
                                    <span class="dashicons dashicons-format-image<?php echo $dim_social ? ' is-ok' : ''; ?>" aria-hidden="true"></span>
                                    <span><strong>Immagine per i social</strong><br>
                                        <?php if (!$necro->Immagine()) { ?>
                                            Il necrologio non ha una foto.
                                        <?php } elseif ($dim_social) { ?>
                                            <a href="<?php echo esc_url($file_social['url']); ?>" target="_blank" rel="noopener">Pronta</a> (<?php echo (int) $dim_social[0]; ?>×<?php echo (int) $dim_social[1]; ?> px).
                                        <?php } else { ?>
                                            Non ancora creata: verrà preparata al prossimo controllo o alla prima visita.
                                        <?php } ?>
                                    </span>
                                </li>
                                <li>
                                    <span class="dashicons dashicons-update<?php echo $registro ? ' is-ok' : ''; ?>" aria-hidden="true"></span>
                                    <span><strong>Controllo aggiornamenti</strong><br>
                                        <?php if (!$registro) { ?>
                                            Non ancora controllato (avviene ogni 15 minuti).
                                        <?php } else { ?>
                                            <?php echo $registro['c'] ? 'Rilevato il ' . esc_html(wp_date('j F, H:i', $registro['c'])) . '.' : 'Già presente al primo controllo.'; ?>
                                            <?php echo $registro['m'] && $registro['m'] !== $registro['c'] ? ' Ultima modifica rilevata il ' . esc_html(wp_date('j F, H:i', $registro['m'])) . '.' : ''; ?>
                                        <?php } ?>
                                    </span>
                                </li>
                                <li>
                                    <span class="dashicons dashicons-performance<?php echo $cache_pagina ? ' is-ok' : ''; ?>" aria-hidden="true"></span>
                                    <span><strong>Cache di pagina</strong><br>
                                        <?php echo $cache_pagina
                                            ? esc_html(implode(', ', $cache_pagina)) . ': svuotata in automatico quando il necrologio cambia.'
                                            : 'Nessun plugin di cache riconosciuto.'; ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <div class="pfn-nd-azioni">
                            <a class="button" href="<?php echo esc_url(PortaleFunebreNecrologi_CacheNecrologio::UrlAggiorna($slug)); ?>"><span class="dashicons dashicons-update" aria-hidden="true"></span> Aggiorna dal portale</a>
                        </div>
                    </div>

                    <!-- Analisi -->
                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Analisi SEO</h2></div>
                        <div class="inside">
                            <ul class="pfn-nd-analisi"></ul>
                            <p class="description">Si aggiorna mentre modifichi titolo, description e frase chiave.</p>
                        </div>
                    </div>

                </div>

            </div>
            <br class="clear"/>
        </div>
    </form>

</div>
