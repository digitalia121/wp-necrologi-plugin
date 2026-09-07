<?php 

if (!defined('ABSPATH')) { exit; }

if (!PortaleFunebreNecrologi::IsConfigurato()) {  echo 'necrologio'; return; }

$impostazioni = PortaleFunebreNecrologi::GetImpostazioni();

$slug_singolo    = (isset($impostazioni['slug_singolo']))  ? $impostazioni['slug_singolo'] : 'necrologio';
$slug_necrologio = get_query_var('necro_slug');

$massimizza_foto = (isset($impostazioni['massimizza_foto']))  ? boolval($impostazioni['massimizza_foto']) : false;

add_filter( 'pre_get_document_title', function( $title ) use ($slug_necrologio) { 
  $strings = explode('-',$slug_necrologio);
  return ucfirst($strings[0]).' '.ucfirst($strings[1]).' - '.get_bloginfo( 'name' );
});

$colore = (isset($impostazioni['colore_box'])) ? $impostazioni['colore_box'] : '#000000';

$tipo_layout = 'layout-default';

if (isset($impostazioni['single_layout'])) {
  $tipo = $impostazioni['single_layout'];
  if ($tipo == 2) {
    $tipo_layout = 'layout-alt';
  }
  
}

$share_on_fb     = (isset($impostazioni['share_on_fb']))     ? boolval($impostazioni['share_on_fb']) : false;
$share_on_tw     = (isset($impostazioni['share_on_tw']))     ? boolval($impostazioni['share_on_tw']) : false;
$share_on_wa     = (isset($impostazioni['share_on_wa']))     ? boolval($impostazioni['share_on_wa']) : false;

$share_on_attivo = $share_on_fb || $share_on_tw || $share_on_wa;

$classes = '';

$mostra_come_popup = isset($impostazioni['form_come_popup']) && $impostazioni['form_come_popup'];

if (!$mostra_come_popup) { 
  $classes .= ' no-popup';
}
if ($massimizza_foto) {
  $classes .= ' foto-massimizzata';
}

$pvcy_link =  (isset($impostazioni['link_gdpr'])) ? $impostazioni['link_gdpr'] : '';


$titolo_hero = (isset($impostazioni['titolo_hero'])) ? $impostazioni['titolo_hero'] : '';
$testo_hero  = (isset($impostazioni['testo_hero']))  ? $impostazioni['testo_hero']  : '';
$bg_url      = '';
if (isset($impostazioni['sfondo_hero']) && $impostazioni['sfondo_hero']) {
  $bg_url = wp_get_attachment_url($impostazioni['sfondo_hero']);
}
$defunto_in_hero = (isset($impostazioni['defunto_in_hero'])) ? boolval($impostazioni['defunto_in_hero']) : false;

?>

<div class="necrologi-loader"><div class="dg_spinner"></div></div>

<div
  class="post-necrologio portale-funebre-post <?php echo esc_attr($classes); ?>"
  style="display: none"
  data-pfn-single="1"
  data-slug-necrologio="<?php echo esc_attr($slug_necrologio); ?>"
  data-not-found-url="<?php echo esc_url(get_site_url().'/not-found'); ?>"
  data-share-url="<?php echo esc_url(get_site_url() . '/pf-share/' . $slug_necrologio); ?>"
  data-no-popup="<?php echo $mostra_come_popup ? '0' : '1'; ?>">


  <?php if ($titolo_hero || $testo_hero): ?>
      <div class="necro-hero-section" style="background-image: url('<?php echo esc_url($bg_url); ?>')">
          <div class="necrohero-inner">
            <?php if ($titolo_hero): ?><h2 class="titolo-hero"><?php echo esc_html($titolo_hero); ?></h2><?php endif; ?>
            <?php if ($testo_hero): ?><p><?php echo esc_html($testo_hero); ?></p><?php endif; ?>
          </div>
      </div>
  <?php endif; ?>

  <div class="necrologio-wrapper <?php echo esc_attr($tipo_layout); ?>">
    
    <div class="necro-content">
      
      <picture></picture>
      
      <?php  if (!$defunto_in_hero && $tipo_layout == 'layout-alt') {?>
        <h1 class="necro-nome-defunto"></h1>
      <?php } ?>

    </div>
    
    <?php if (!$massimizza_foto) { ?> 
      <div class="necro-center">
        
        <?php if (!$defunto_in_hero && $tipo_layout != 'layout-alt') {?>
          <h2 class="necro-nome-defunto"></h2>
        <?php } ?>

        <div class="necro-testo"></div>
        
      </div>
    <?php } ?>

    <div class="necro-dettagli">
      
      <?php if ($massimizza_foto) { ?>

        <?php if (!$defunto_in_hero) {?>
          <h2 class="necro-nome-defunto"></h2>
        <?php } ?>

        <div class="necro-testo"></div>
        
      <?php } ?>

      <div class="bottoni-azioni">

        <button class="button invia-cordoglio">Invia un messaggio di cordoglio</button>
        <a class="button scrivi-su-whatsapp" href="#" target="_blank">
          <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" viewBox="0 0 418.135 418.135" xml:space="preserve">
            <g>
              <path d="M198.929,0.242C88.5,5.5,1.356,97.466,1.691,208.02c0.102,33.672,8.231,65.454,22.571,93.536 L2.245,408.429c-1.191,5.781,4.023,10.843,9.766,9.483l104.723-24.811c26.905,13.402,57.125,21.143,89.108,21.631   c112.869,1.724,206.982-87.897,210.5-200.724C420.113,93.065,320.295-5.538,198.929,0.242z M323.886,322.197   c-30.669,30.669-71.446,47.559-114.818,47.559c-25.396,0-49.71-5.698-72.269-16.935l-14.584-7.265l-64.206,15.212l13.515-65.607   l-7.185-14.07c-11.711-22.935-17.649-47.736-17.649-73.713c0-43.373,16.89-84.149,47.559-114.819   c30.395-30.395,71.837-47.56,114.822-47.56C252.443,45,293.218,61.89,323.887,92.558c30.669,30.669,47.559,71.445,47.56,114.817   C371.446,250.361,354.281,291.803,323.886,322.197z"/>
              <path d="M309.712,252.351l-40.169-11.534c-5.281-1.516-10.968-0.018-14.816,3.903l-9.823,10.008 c-4.142,4.22-10.427,5.576-15.909,3.358c-19.002-7.69-58.974-43.23-69.182-61.007c-2.945-5.128-2.458-11.539,1.158-16.218   l8.576-11.095c3.36-4.347,4.069-10.185,1.847-15.21l-16.9-38.223c-4.048-9.155-15.747-11.82-23.39-5.356   c-11.211,9.482-24.513,23.891-26.13,39.854c-2.851,28.144,9.219,63.622,54.862,106.222c52.73,49.215,94.956,55.717,122.449,49.057   c15.594-3.777,28.056-18.919,35.921-31.317C323.568,266.34,319.334,255.114,309.712,252.351z"/>
            </g>
          </svg>
          <span>Invia cordoglio su whatsapp</span>
        </a>

      </div>

      <div class="custom-links"></div>
      

      <?php if ($share_on_attivo) { ?> 

        <div class="social-share">
          
          <h5>CONDIVIDI IL NECROLOGIO</h5>

          <div class="shs-icon-wrapper">

            <?php if ($share_on_fb) { ?> 
              <a class="share-on fb" href="#" target="_blank">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <rect width="24" height="24" fill="none"/>
                  <path d="M5,3H19a2,2,0,0,1,2,2V19a2,2,0,0,1-2,2H5a2,2,0,0,1-2-2V5A2,2,0,0,1,5,3M18,5H15.5A3.5,3.5,0,0,0,12,8.5V11H10v3h2v7h3V14h3V11H15V9a1,1,0,0,1,1-1h2Z"/>
                </svg>
              </a>
            <?php } ?>

            <?php if ($share_on_tw) { ?> 
              <a class="share-on tw" href="#" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" style="background: #000; margin: 4px" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 512 509">
                  <rect rx="115.61" ry="115.61"/><path fill="#fff" fill-rule="nonzero" d="M323.74 148.35h36.12l-78.91 90.2 92.83 122.73h-72.69l-56.93-74.43-65.15 74.43h-36.14l84.4-96.47-89.05-116.46h74.53l51.46 68.04 59.53-68.04zm-12.68 191.31h20.02l-129.2-170.82H180.4l130.66 170.82z"/>
                </svg>
              </a>
            <?php } ?>

            <?php if ($share_on_wa) { ?> 
              <a class="share-on wa" href="#" data-action="share/whatsapp/share" target="_blank">
                <svg fill="#000000" version="1.1" id="Icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                    viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                  <g id="WA_Logo">
                    <g>
                      <path fill-rule="evenodd" clip-rule="evenodd" d="M20.5,3.5C18.25,1.25,15.2,0,12,0C5.41,0,0,5.41,0,12c0,2.11,0.65,4.11,1.7,5.92
                        L0,24l6.33-1.55C8.08,23.41,10,24,12,24c6.59,0,12-5.41,12-12C24,8.81,22.76,5.76,20.5,3.5z M12,22c-1.78,0-3.48-0.59-5.01-1.49
                        l-0.36-0.22l-3.76,0.99l1-3.67l-0.24-0.38C2.64,15.65,2,13.88,2,12C2,6.52,6.52,2,12,2c2.65,0,5.2,1.05,7.08,2.93S22,9.35,22,12
                        C22,17.48,17.47,22,12,22z M17.5,14.45c-0.3-0.15-1.77-0.87-2.04-0.97c-0.27-0.1-0.47-0.15-0.67,0.15
                        c-0.2,0.3-0.77,0.97-0.95,1.17c-0.17,0.2-0.35,0.22-0.65,0.07c-0.3-0.15-1.26-0.46-2.4-1.48c-0.89-0.79-1.49-1.77-1.66-2.07
                        c-0.17-0.3-0.02-0.46,0.13-0.61c0.13-0.13,0.3-0.35,0.45-0.52s0.2-0.3,0.3-0.5c0.1-0.2,0.05-0.37-0.02-0.52
                        C9.91,9.02,9.31,7.55,9.06,6.95c-0.24-0.58-0.49-0.5-0.67-0.51C8.22,6.43,8.02,6.43,7.82,6.43S7.3,6.51,7.02,6.8
                        C6.75,7.1,5.98,7.83,5.98,9.3c0,1.47,1.07,2.89,1.22,3.09c0.15,0.2,2.11,3.22,5.1,4.51c0.71,0.31,1.27,0.49,1.7,0.63
                        c0.72,0.23,1.37,0.2,1.88,0.12c0.57-0.09,1.77-0.72,2.02-1.42c0.25-0.7,0.25-1.3,0.17-1.42C18,14.68,17.8,14.6,17.5,14.45z"/>
                    </g>
                  </g>
                </svg>
              </a>
            <?php } ?>
            
          </div>

        </div>

      <?php } ?>

    </div>

  </div>

  <div class="sezione-donazioni">
        
  </div>
  

  <div class="necro-mappa">

    <h2>LUOGO DELLA CERIMONIA</h2>

    <div class="mappa-cerimonia" id="cerimoniaMap"></div>

  </div>

  <div class="cordo-form-wrapper" id="form-di-cordoglio">

    <div class="form-container">

      <div class="form-buttons">
        <button class="manda-con-email">MANDA CON EMAIL</button>
        <button class="manda-con-whatsapp">MANDA CON WHATSAPP</button>
        <button class="manda-con-pdf">MANDA CON PDF</button>
      </div>

      <form action="#" method="post">
        <input type="hidden" name="tipo" value="pdf">
        <input type="hidden" name="slug" value="">
        <button class="popclose" type="button">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <g id="Menu / Close_SM">
              <path id="Vector" d="M16 16L12 12M12 12L8 8M12 12L16 8M12 12L8 16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
          </svg>
        </button>
        <div class="input-content">
          <div class="form-row">
            <div class="form-group">
              <label for="first-name">Nome*</label>
              <input type="text" id="first-name" name="first-name" required>
            </div>
            <div class="form-group">
              <label for="last-name">Cognome*</label>
              <input type="text" id="last-name" name="last-name" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="email">Email*</label>
              <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
              <label for="phone">Telefono*</label>
              <input type="tel" id="phone" name="phone" required>
            </div>
          </div>
          <div class="form-group full">
            <label for="message">Messaggio di Cordoglio*</label>
            <textarea id="message" name="message" rows="5" required></textarea>
          </div>
          <?php if ($pvcy_link) { ?>
            <div class="form-group pvcy-group full">
                <p><input type="checkbox" name="privacy" required/><label for="privacy">Ho letto e accetto la <a target="_blank" href="<?php echo esc_url($pvcy_link); ?>">privacy policy</a>*</label></p>
            </div>
          <?php } ?>
          <div class="convalidazione"></div>
          <button type="button" class="invia-a-api disabled">Invia</button>
        </div>
      </form>
    </div>
  </div>

</div>

