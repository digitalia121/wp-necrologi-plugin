<?php 

use function PortaleFunebreNecrologi\get_plugin_asset_url as get_asset_url;

if (!defined('ABSPATH')) { exit; }

$impostazioni = PortaleFunebreNecrologi::GetImpostazioni();


$tipi_visualizzazione = [
    'grid'  => 'Griglia',
    'slide' => 'Slider'
];
$valori_slide_res = [
    '1'  => '1',
    '2'  => '2',
    '3'  => '3',
    '4'  => '4',
    '5'  => '5',
    '6'  => '6',
    '7'  => '7',
    '8'  => '8',
    '11' => '11',
    '10' => '10',
    '12' => '12'
];
$valori_num_cols = [
    '1'  => '1',
    '2'  => '2',
    '3'  => '3',
    '4'  => '4',
    '5'  => '5',
    '6'  => '6',
    '7'  => '7',
    '8'  => '8'
];
$profili = [
    'orrizontale' => 'Orrizontale',
    'verticale'   => 'Verticale'
];
$ordini_lista = [
    ''                     => 'Nessun ordine',
    'alfabetico_asc'       => 'Ordine alfabetico crescente',
    'alfabetico_desc'      => 'Ordine alfabetico decrescente',
    'data_decesso_asc'     => 'Data decesso crescente',
    'data_decesso_desc'    => 'Data decesso decrescente'
];

$valori_cerimonia = [
    ''           => 'Nascondi',
    'data_luogo' => 'Data e luogo Funerale',
    'luogo_data' => 'Luogo e data Funerale',
    'data'       => 'Data Funerale',
    'luogo'      => 'Luogo funerale'
];
$valori_rosario = [
    ''           => 'Nascondi',
    'data_luogo' => 'Data e luogo Rosario',
    'luogo_data' => 'Luogo e data Rosario',
    'data'       => 'Data Rosario',
    'luogo'      => 'Luogo Rosario'
];


/*

- Data Funerale / Data e luogo Funerale / Luogo funerale
- Data Rosario / Data e luogo Rosario / Luogo Rosario
- Mostra Età
- Data Decesso
- Mostra con Bottone

*/

$slide_res_desktop     = (isset($impostazioni['slide_res_desktop']))     ? $impostazioni['slide_res_desktop']              : 4;
$slide_res_tablet      = (isset($impostazioni['slide_res_tablet']))      ? $impostazioni['slide_res_tablet']               : 3;
$slide_res_mobile      = (isset($impostazioni['slide_res_mobile']))      ? $impostazioni['slide_res_mobile']               : 2;
$slide_cerimonia       = (isset($impostazioni['slide_cerimonia']))       ? $impostazioni['slide_cerimonia']                : '';
$slide_rosario         = (isset($impostazioni['slide_rosario']))         ? $impostazioni['slide_rosario']                  : '';
$slide_mostra_decesso  = (isset($impostazioni['slide_mostra_decesso']))  ? boolval($impostazioni['slide_mostra_decesso'])  : false;
$slide_mostra_eta      = (isset($impostazioni['slide_mostra_eta']))      ? boolval($impostazioni['slide_mostra_eta'])      : false;
$slide_mostra_orari    = (isset($impostazioni['slide_mostra_orari']))    ? boolval($impostazioni['slide_mostra_orari'])    : false;
$slide_mostra_dettagli = (isset($impostazioni['slide_mostra_dettagli'])) ? boolval($impostazioni['slide_mostra_dettagli']) : false;

$tipo_vis        = (isset($impostazioni['tipo_visualizzazione'])) ? $impostazioni['tipo_visualizzazione'] :'grid';
$colore_box      = (isset($impostazioni['colore_box']))      ? $impostazioni['colore_box']   : '#f7f6ed';
$colore_page     = (isset($impostazioni['colore_page']))     ? $impostazioni['colore_page']   : '#f9eee4';
$num_cols        = (isset($impostazioni['num_cols']))        ? $impostazioni['num_cols'] : '6';
$profilo_box     = (isset($impostazioni['profilo_box']))     ? $impostazioni['profilo_box'] : 'verticale';
$ordine_lista    = (isset($impostazioni['ordine_lista']))    ? $impostazioni['ordine_lista'] : '';
$colore_testo    = (isset($impostazioni['colore_testo']))    ? $impostazioni['colore_testo'] : '#1d1d1d';
$api_key         = (isset($impostazioni['api_key']))         ? $impostazioni['api_key']      : '';
$client_id       = (isset($impostazioni['client_id']))       ? $impostazioni['client_id']    : '';
$slug_singolo    = (isset($impostazioni['slug_singolo']))    ? $impostazioni['slug_singolo'] : 'necrologio';
$cerimonia       = (isset($impostazioni['cerimonia']))       ? $impostazioni['cerimonia'] : '';
$rosario         = (isset($impostazioni['rosario']))         ? $impostazioni['rosario'] : '';
$con_bottone     = (isset($impostazioni['con_bottone']))     ? boolval($impostazioni['con_bottone']) : false;
$mostra_decesso  = (isset($impostazioni['mostra_decesso']))  ? boolval($impostazioni['mostra_decesso']) : false;
$mostra_eta      = (isset($impostazioni['mostra_eta']))      ? boolval($impostazioni['mostra_eta']) : false;
$mostra_donazioni= (isset($impostazioni['mostra_donazioni']))? boolval($impostazioni['mostra_donazioni']) : false;
$mostra_orari    = (isset($impostazioni['mostra_orari']))    ? boolval($impostazioni['mostra_orari']) : false;
$mostra_dettagli = (isset($impostazioni['mostra_dettagli'])) ? boolval($impostazioni['mostra_dettagli']) : false;
$form_come_popup = (isset($impostazioni['form_come_popup'])) ? boolval($impostazioni['form_come_popup']) : true;
$single_layout   = (isset($impostazioni['single_layout']))   ? $impostazioni['single_layout'] : "1";
$share_on_fb     = (isset($impostazioni['share_on_fb']))     ? boolval($impostazioni['share_on_fb']) : false;
$share_on_tw     = (isset($impostazioni['share_on_tw']))     ? boolval($impostazioni['share_on_tw']) : false;
$share_on_wa     = (isset($impostazioni['share_on_wa']))     ? boolval($impostazioni['share_on_wa']) : false;
$eta_su_singolo  = (isset($impostazioni['eta_su_singolo']))  ? boolval($impostazioni['eta_su_singolo']) : false;
$link_gdpr       = (isset($impostazioni['link_gdpr']))       ? $impostazioni['link_gdpr'] : '';

$titolo_hero     = isset($impostazioni['titolo_hero'])       ? $impostazioni['titolo_hero'] : 'NECROLOGIO';
$testo_hero      = isset($impostazioni['testo_hero'])        ? $impostazioni['testo_hero'] : 'Invia il tuo messaggio privato di condoglianze direttamente alla famiglia.';
$sfondo_hero     = isset($impostazioni['sfondo_hero'])       ? $impostazioni['sfondo_hero'] : null;
$defunto_in_hero = (isset($impostazioni['defunto_in_hero'])) ? boolval($impostazioni['defunto_in_hero']) : false;
$massimizza_foto = (isset($impostazioni['massimizza_foto'])) ? boolval($impostazioni['massimizza_foto']) : false;


if (isset($_POST['cambia_impostazioni'])) {

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Non hai i permessi per modificare queste impostazioni.', 'portale-funebre-necrologi'));
    }

    check_admin_referer('gestione_necrologi_save_settings', 'gestione_necrologi_nonce');

    $posted = wp_unslash($_POST);

    $field = static function ($key, $default = '') use ($posted) {
        return isset($posted[$key]) ? sanitize_text_field($posted[$key]) : $default;
    };
    $checkbox = static function ($key) use ($posted) {
        return (isset($posted[$key]) && $posted[$key]) ? '1' : '0';
    };
    $color = static function ($key, $default) use ($field) {
        $value = sanitize_hex_color($field($key, $default));
        return $value ? $value : $default;
    };

    $tipo_vis        = $field('tipo_vis', 'grid');
    $colore_box      = $color('colore_box', '#f7f6ed');
    $colore_page     = $color('colore_page', '#f9eee4');
    $profilo_box     = $field('profilo_box', 'verticale');
    $ordine_lista    = $field('ordine_lista');
    if (!array_key_exists($ordine_lista, $ordini_lista)) {
        $ordine_lista = '';
    }
    $link_gdpr       = esc_url_raw($field('link_gdpr'));
    $colore_testo    = $color('colore_testo', '#1d1d1d');
    $api_key         = $field('api_key');
    $client_id       = $field('client_id');
    $slug_singolo    = sanitize_title($field('slug_singolo', 'necrologio'));
    $cerimonia       = $field('cerimonia');
    $rosario         = $field('rosario');
    $titolo_hero     = $field('titolo_hero');
    $testo_hero      = $field('testo_hero');
    $sfondo_hero     = absint($field('sfondo_hero'));

    $slide_res_desktop     = $field('slide_res_desktop', '4');
    $slide_res_tablet      = $field('slide_res_tablet', '3');
    $slide_res_mobile      = $field('slide_res_mobile', '2');
    $slide_cerimonia       = $field('slide_cerimonia');
    $slide_rosario         = $field('slide_rosario');
    $slide_mostra_decesso  = $checkbox('slide_mostra_decesso');
    $slide_mostra_eta      = $checkbox('slide_mostra_eta');
    $slide_mostra_orari    = $checkbox('slide_mostra_orari');
    $slide_mostra_dettagli = $checkbox('slide_mostra_dettagli');

    $mostra_decesso  = $checkbox('mostra_decesso');
    $mostra_eta      = $checkbox('mostra_eta');
    $eta_su_singolo  = $checkbox('eta_su_singolo');
    $mostra_donazioni= $checkbox('mostra_donazioni');
    $mostra_orari    = $checkbox('mostra_orari');
    $mostra_dettagli = $checkbox('mostra_dettagli');
    $con_bottone     = $checkbox('con_bottone');
    $form_come_popup = $checkbox('form_come_popup');
    $share_on_fb     = $checkbox('share_on_fb');
    $share_on_tw     = $checkbox('share_on_tw');
    $share_on_wa     = $checkbox('share_on_wa');
    $defunto_in_hero = $checkbox('defunto_in_hero');
    $massimizza_foto = $checkbox('massimizza_foto');
    $single_layout   = $field('single_layout', '1');

    $impostazioni['tipo_visualizzazione'] = $tipo_vis;
    
    $impostazioni['slide_res_desktop']     = $slide_res_desktop;
    $impostazioni['slide_res_tablet']      = $slide_res_tablet;
    $impostazioni['slide_res_mobile']      = $slide_res_mobile;
    $impostazioni['slide_cerimonia']       = $slide_cerimonia;
    $impostazioni['slide_rosario']         = $slide_rosario;
    $impostazioni['slide_mostra_decesso']  = $slide_mostra_decesso;
    $impostazioni['slide_mostra_eta']      = $slide_mostra_eta;
    $impostazioni['slide_mostra_orari']    = $slide_mostra_orari;
    $impostazioni['slide_mostra_dettagli'] = $slide_mostra_dettagli;

    $impostazioni['colore_box']      = $colore_box;
    $impostazioni['colore_page']     = $colore_page;
    $impostazioni['link_gdpr']       = $link_gdpr;
    $impostazioni['profilo_box']     = $profilo_box;
    $impostazioni['ordine_lista']    = $ordine_lista;
    //$impostazioni['num_cols']        = $num_cols;
    $impostazioni['colore_testo']    = $colore_testo;
    $impostazioni['api_key']         = $api_key;
    $impostazioni['client_id']       = $client_id;
    $impostazioni['slug_singolo']    = $slug_singolo;
    $impostazioni['cerimonia']       = $cerimonia;
    $impostazioni['rosario']         = $rosario;
    $impostazioni['con_bottone']     = $con_bottone;
    $impostazioni['mostra_decesso']  = $mostra_decesso;
    $impostazioni['mostra_eta']      = $mostra_eta;
    $impostazioni['mostra_orari']    = $mostra_orari;
    $impostazioni['mostra_dettagli'] = $mostra_dettagli;
    $impostazioni['single_layout']   = $single_layout;
    $impostazioni['form_come_popup'] = $form_come_popup;
    $impostazioni['mostra_donazioni']= $mostra_donazioni;
    $impostazioni['share_on_fb']     = $share_on_fb;
    $impostazioni['share_on_tw']     = $share_on_tw;
    $impostazioni['share_on_wa']     = $share_on_wa;
    $impostazioni['titolo_hero']     = $titolo_hero;
    $impostazioni['testo_hero']      = $testo_hero;
    $impostazioni['sfondo_hero']     = $sfondo_hero;
    $impostazioni['defunto_in_hero'] = $defunto_in_hero;
    $impostazioni['massimizza_foto'] = $massimizza_foto;
    $impostazioni['eta_su_singolo']  = $eta_su_singolo;

    /*

    - Data Funerale / Data e luogo Funerale / Luogo funerale
    - Data Rosario / Data e luogo Rosario / Luogo Rosario
    - Mostra Età
    - Data Decesso
    - Mostra con Bottone

    */
    add_rewrite_rule('^'.$slug_singolo.'/([^/]+)?', 'index.php?necro_slug=$matches[1]', 'top');
    flush_rewrite_rules(  );

    $impostazioni->Salva();

}

?>

<form method="POST">
    <?php wp_nonce_field('gestione_necrologi_save_settings', 'gestione_necrologi_nonce'); ?>

    <div class="impostazioni-header">

        <nav class="privacy-settings-tabs-wrapper hide-if-no-js" aria-label="Menu secondario">
            <a class="privacy-settings-tab active" to="slider" aria-current="true">Slider per Anteprima</a>
            <a class="privacy-settings-tab" to="lista">Lista Necrologi</a>
            <a class="privacy-settings-tab" to="singolo">Necrologio singolo</a>
            <a class="privacy-settings-tab" to="integrazione">Integrazione API</a>
        </nav>

    </div>

    <div class="impostazioni-content">

        <content class="active" tab="slider">

            <h3>Visualizzazione</h3>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Desktop: numero di slide</label>
                <select name="slide_res_desktop">
                    <?php foreach ($valori_slide_res as $key => $value) { ?>
                        <option <?php if ($key == $slide_res_desktop) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Tablet: numero di slide</label>
                <select name="slide_res_tablet">
                    <?php foreach ($valori_slide_res as $key => $value) { ?>
                        <option <?php if ($key == $slide_res_tablet) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Mobile: numero di slide</label>
                <select name="slide_res_mobile">
                    <?php foreach ($valori_slide_res as $key => $value) { ?>
                        <option <?php if ($key == $slide_res_mobile) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <h3>Dati della cerimonia</h3>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Funerale: mostra</label>
                <select name="slide_cerimonia">
                    <?php foreach ($valori_cerimonia as $key => $value) { ?>
                        <option <?php if ($key == $slide_cerimonia) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Rosario: mostra </label>
                <select name="slide_rosario">
                    <?php foreach ($valori_rosario as $key => $value) { ?>
                        <option <?php if ($key == $slide_rosario) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Data del decesso</label>
                <?php if ($slide_mostra_decesso) {  ?>
                    <p>Mostra data del decesso <input name="slide_mostra_decesso" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra data del decesso <input name="slide_mostra_decesso" type="checkbox"/></p>
                <?php } ?>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Orari</label>
                <?php if ($slide_mostra_orari) {  ?>
                    <p>Mostra anche gli orari <input name="slide_mostra_orari" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra anche gli orari <input name="slide_mostra_orari" type="checkbox"/></p>
                <?php } ?>
            </div>
            
            <div class="dgplugin-input-wrap" style="display:none">
                <label>Mostra dettagli</label>
                <?php if ($slide_mostra_dettagli) {  ?>
                    <p>Mostra anche i titoli dei dettagli <input name="slide_mostra_dettagli" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra anche i titoli dei dettagli <input name="slide_mostra_dettagli" type="checkbox"/></p>
                <?php } ?>
            </div>


            <div class="dgplugin-input-wrap">
                <label>Età del Defunto</label>
                <?php if ($slide_mostra_eta) {  ?>
                    <p>Mostra Età del Defunto <input name="slide_mostra_eta" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra Età del Defunto <input name="slide_mostra_eta" type="checkbox"/></p>
                <?php } ?>
            </div>

        </content>

        <content tab="lista">


            <h3>Lista necrologi</h3>

            <div class="dgplugin-input-wrap">
                <label>Tipo di Visualizzazione</label>
                <select name="tipo_vis">
                    <?php foreach ($tipi_visualizzazione as $key => $value) { ?>
                        <option <?php if ($key == $tipo_vis) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>

            <?php 
    /*
            <div class="dgplugin-input-wrap num-cols-wrap">
                <label>Numero di colonne griglia</label>
                <select name="num_cols">
                    <?php foreach ($valori_num_cols as $key => $value) { ?>
                        <option <?php if ($key == $num_cols) { echo 'selected'; } ?> value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php } ?>
                </select>
            </div>

            */
            ?>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Profilo del box</label>
                <select name="profilo_box">
                    <?php foreach ($profili as $key => $value) { ?>
                        <option <?php if ($key == $profilo_box) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Ordine visualizzazione</label>
                <select name="ordine_lista">
                    <?php foreach ($ordini_lista as $key => $value) { ?>
                        <option <?php if ($key == $ordine_lista) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dgplugin-input-wrap">
                <label>Colore del Box</label>
                <input name="colore_box" type="color" value="<?php echo esc_attr($colore_box); ?>"/>
            </div>
            <div class="dgplugin-input-wrap">
                <label>Colore Testo</label>
                <input name="colore_testo" type="color" value="<?php echo esc_attr($colore_testo); ?>"/>
            </div>
            <h3>Dati della cerimonia</h3>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Funerale: mostra</label>
                <select name="cerimonia">
                    <?php foreach ($valori_cerimonia as $key => $value) { ?>
                        <option <?php if ($key == $cerimonia) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dgplugin-input-wrap box-prof-wrap">
                <label>Rosario: mostra </label>
                <select name="rosario">
                    <?php foreach ($valori_rosario as $key => $value) { ?>
                        <option <?php if ($key == $rosario) { echo 'selected'; } ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Bottone di Accesso al Necrologio</label>
                <?php if ($con_bottone) {  ?>
                    <p>Mostra un bottone invece che il link <input name="con_bottone" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra un bottone invece che il link <input name="con_bottone" type="checkbox"/></p>
                <?php } ?>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Data del decesso</label>
                <?php if ($mostra_decesso) {  ?>
                    <p>Mostra data del decesso <input name="mostra_decesso" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra data del decesso <input name="mostra_decesso" type="checkbox"/></p>
                <?php } ?>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Orari</label>
                <?php if ($mostra_orari) {  ?>
                    <p>Mostra anche gli orari <input name="mostra_orari" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra anche gli orari <input name="mostra_orari" type="checkbox"/></p>
                <?php } ?>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Mostra dettagli</label>
                <?php if ($mostra_dettagli) {  ?>
                    <p>Mostra anche i titoli dei dettagli <input name="mostra_dettagli" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra anche i titoli dei dettagli <input name="mostra_dettagli" type="checkbox"/></p>
                <?php } ?>
            </div>


            <div class="dgplugin-input-wrap">
                <label>Età del Defunto</label>
                <?php if ($mostra_eta) {  ?>
                    <p>Mostra Età del Defunto <input name="mostra_eta" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra Età del Defunto <input name="mostra_eta" type="checkbox"/></p>
                <?php } ?>
            </div>

        </content>

        <content tab="singolo">

            <?php

                $layone_img = get_asset_url('lay-one.svg');
                $laytwo_img = get_asset_url('lay-two.svg');
            ?>

            <h3>Necrologio singolo</h3>

            <div class="dgplugin-input-wrap layout">
                <label>Layout del necrologio</label>
                <p>
                    <?php if ($single_layout == 1) { ?>
                        <span><img src="<?php echo esc_url($laytwo_img); ?>"/><input type="radio" name="single_layout" value="1" checked/></span>
                    <?php } else { ?>
                        <span><img src="<?php echo esc_url($laytwo_img); ?>"/><input type="radio" name="single_layout" value="1"/></span>
                    <?php } ?>
                    <?php if ($single_layout == 2) { ?>
                        <span><img src="<?php echo esc_url($layone_img); ?>"/><input type="radio" name="single_layout" value="2" checked/></span>
                    <?php } else { ?>
                        <span><img src="<?php echo esc_url($layone_img); ?>"/><input type="radio" name="single_layout" value="2"/></span>
                    <?php } ?>
                </p>
            </div>
            
            <div class="dgplugin-input-wrap">
                <label>Titolo Hero Section</label>
                <input type="text" name="titolo_hero" value="<?php echo esc_attr($titolo_hero); ?>">
            </div>

            <div class="dgplugin-input-wrap">
                <label>Testo Hero Section</label>
                <input type="text" name="testo_hero" value="<?php echo esc_attr($testo_hero); ?>">
            </div>

            <div class="dgplugin-input-wrap">
                <label>Sfondo Hero Section</label>
                <div style="display: flex; gap: 10px; align-items: center; justify-content: space-between; padding: 0px 10px">
                    <input type="hidden" name="sfondo_hero" id="sfondo_hero" value="<?php echo esc_attr($sfondo_hero); ?>">
                    <button type="button" class="button" id="upload_hero_bg">Carica immagine</button>
                    <span class="img-previewer" id="preview_hero_bg">
                        <?php if ($sfondo_hero) echo wp_get_attachment_image($sfondo_hero, 'thumbnail'); ?>
                    </span>
                </div>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Nome del defunto</label>
                <?php if ($defunto_in_hero) {  ?>
                    <p>Mostra nella Hero Section<input name="defunto_in_hero" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra nella Hero Section<input name="defunto_in_hero" type="checkbox"/></p>
                <?php } ?>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Massimizza foto</label>
                <?php if ($massimizza_foto) {  ?>
                    <p>Mostra foto principale grande <input name="massimizza_foto" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra foto principale grande <input name="massimizza_foto" type="checkbox"/></p>
                <?php } ?>
            </div>
            
            <div class="dgplugin-input-wrap">
                <label>Età del Defunto</label>
                <?php if ($eta_su_singolo) {  ?>
                    <p>Mostra Età del Defunto <input name="eta_su_singolo" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra Età del Defunto <input name="eta_su_singolo" type="checkbox"/></p>
                <?php } ?>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Sezione Donazioni</label>
                <?php if ($mostra_donazioni) {  ?>
                    <p>Mostra sezione donazioni <input name="mostra_donazioni" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra sezione donazioni <input name="mostra_donazioni" type="checkbox"/></p>
                <?php } ?>
            </div>
            
            <div class="dgplugin-input-wrap">
                <label>Form di raccolta Cordogli</label>
                <?php if ($form_come_popup) {  ?>
                    <p>Mostra come Popup<input name="form_come_popup" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Mostra come Popup<input name="form_come_popup" type="checkbox"/></p>
                <?php } ?>
            </div>
            <div class="dgplugin-input-wrap">
                <label>Colore della pagina</label>
                <input name="colore_page" type="color" value="<?php echo esc_attr($colore_page); ?>"/>
            </div>

            <div class="dgplugin-input-wrap">
                <label>Facebook</label>
                <?php if ($share_on_fb) {  ?>
                    <p>Abilita condivisione su Facebook<input name="share_on_fb" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Abilita condivisione su Facebook<input name="share_on_fb" type="checkbox"/></p>
                <?php } ?>
            </div>
            <div class="dgplugin-input-wrap">
                <label>WhatsApp</label>
                <?php if ($share_on_wa) {  ?>
                    <p>Abilita condivisione su WhatsApp<input name="share_on_wa" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Abilita condivisione su WhatsApp<input name="share_on_wa" type="checkbox"/></p>
                <?php } ?>
            </div>
            <div class="dgplugin-input-wrap">
                <label>Twitter (X)</label>
                <?php if ($share_on_tw) {  ?>
                    <p>Abilita condivisione su X<input name="share_on_tw" type="checkbox" checked/></p>
                <?php } else { ?>
                    <p>Abilita condivisione su X<input name="share_on_tw" type="checkbox"/></p>
                <?php } ?>
            </div>


        </content>

        <content tab="integrazione">
            <h3>G.D.P.R.</h3>
            <div class="dgplugin-input-wrap">
                <label>Link della Privacy Policy</label>
                <input name="link_gdpr" type="text" value="<?php echo esc_url($link_gdpr); ?>"/>
            </div>

            <h3>Integrazione API</h3>
            <div class="dgplugin-input-wrap">
                <label>Api Key</label>
                <input name="api_key" type="text" value="<?php echo esc_attr($api_key); ?>"/>
            </div>
            <div class="dgplugin-input-wrap">
                <label>Client ID</label>
                <input name="client_id" type="text" value="<?php echo esc_attr($client_id); ?>"/>
            </div>
            <div class="dgplugin-input-wrap">
                <label>Slug della pagina necrologi</label>
                <input name="slug_singolo" type="text" value="<?php echo esc_attr($slug_singolo); ?>"/>
            </div>
            

        </content>

    </div>

    <br><hr><br>

    <input type="submit" class="button button-primary" name="cambia_impostazioni" value="SALVA"/>

</form>
