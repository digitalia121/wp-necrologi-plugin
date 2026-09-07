<?php 

use function PortaleFunebreNecrologi\get_plugin_page_url;

if (!defined('ABSPATH')) { exit; }

$api = new PortaleFunebreNecrologi_API();

$cerimonie = $api->TrovaTuttiNecrologi(true);

$portale_url  = PortaleFunebreNecrologi_API::GetEndPoint().'/area-riservata';
$cordogli_url = get_plugin_page_url('cordogli').'&defunto=';

$format_data_italiana = static function ($data) {
    $data = trim((string) $data);
    if (!$data) {
        return '';
    }

    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $data, $matches)) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $matches[1]);
        if ($date) {
            return $date->format('d/m/Y');
        }
    }

    $timestamp = strtotime($data);
    return $timestamp ? wp_date('d/m/Y', $timestamp) : $data;
};

$format_dati_cerimonia = static function ($cerimonia) use ($format_data_italiana) {
    if (!$cerimonia) {
        return '';
    }

    $luogo = isset($cerimonia->luogo) ? $cerimonia->luogo : '';
    $data = isset($cerimonia->data) ? $format_data_italiana($cerimonia->data) : '';
    $ora = isset($cerimonia->ora_da) ? $cerimonia->ora_da : '';

    return esc_html($luogo) . ' (<b>' . esc_html($data) . '</b> ' . esc_html($ora) . ')';
};

?>

<table class="wp-list-table tabella-iscritti widefat fixed striped necrologi">
    <thead><tr><th style="width: 40px"> </th><th>Nome defunto</th><th>funerale</th><th>rosario</th><th>feretro</th><th>Azione</th></tr></thead>
    <tbody>
        <?php

            foreach ($cerimonie as $cer) {

                $funerale = $cer->funerale;
                $feretro  = $cer->chiusura_feretro;
                $rosario  = $cer->rosario;

                $dati_funerale = $format_dati_cerimonia($funerale);
                $dati_feretro  = $format_dati_cerimonia($feretro);
                $dati_rosario  = $format_dati_cerimonia($rosario);

                $azioni = '<a target="_blank" href="' . esc_url($portale_url) . '">edita sul portale</a>';
                
                $num_cordo = count($cer->cordogli->email) + count($cer->cordogli->pdf) + count($cer->cordogli->whatsapp);

                if ($num_cordo) {
                    $azioni .= ' | <a href="' . esc_url(wp_nonce_url($cordogli_url . $cer->slug, 'portale_funebre_necrologi_view_condolences')) . '">vedi i cordogli</a>';
                }
                
                $thumb = PortaleFunebreNecrologi_API::GetImgUrl($cer->thumbnail);

                $img = '<img src="' . esc_url($thumb) . '" style="width: 40px"/>';

                echo '<tr><td>' . wp_kses_post($img) . '</td><td><b>' . esc_html($cer->nome_defunto) . '</b></td><td>' . wp_kses_post($dati_funerale) . '</td><td>' . wp_kses_post($dati_rosario) . '</td><td>' . wp_kses_post($dati_feretro) . '</td><td>' . wp_kses_post($azioni) . '</td></tr>';


            }

        ?>
    </tbody>
    <tfoot><tr><th style="width: 40px"> </th><th>Nome defunto</th><th>funerale</th><th>rosario</th><th>feretro</th><th>Azione</th></tr></tfoot>
</table>

<?php

if (count($cerimonie) < 1) {
    echo '<p>Non ci sono cerimonie caricate al momento</p>';
}
