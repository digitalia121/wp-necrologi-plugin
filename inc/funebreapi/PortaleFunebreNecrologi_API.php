<?php 

if (!defined('ABSPATH')) { exit; }

if (!defined('PORTALE_FUNEBRE_NECROLOGI_API_INCLUDED')) {
    die('');
}

class PortaleFunebreNecrologi_API {

    private static $END_POINT='',$API_KEY='',$CLIENT_ID='';

    private $urlParams = '';

    private static $inited=false;

    private $token=null;

    function __construct() {

        /*$res = self::portale_call('api/authorize',[
            'api_key'   => self::$API_KEY,
            'client_id' => self::$CLIENT_ID
        ]);*/
        
        $this->urlParams = '?api_key='.self::$API_KEY.'&client_id='.self::$CLIENT_ID;
        
        /*$res = json_decode($res, true);

        $this->token = $res['token'];*/

    }

    public static function GetEndPoint() { return self::$END_POINT; }

    public static function GetAsseturl($asset_name = '', $type='') {

        if ($type == 'img') {
            return self::$END_POINT.'/upload/media/'.$asset_name;
        }

        return '-';

    }

    public static function GetImgUrl($image_name = '') {
        return self::GetAsseturl($image_name, 'img');
    }

    public static function GetLoginPath() { return self::$END_POINT.'/login/'; }
    
    /**
     * Tempo massimo (secondi) per le letture: le pagine pubbliche vengono
     * costruite lato server e non devono restare appese se il portale e' lento.
     */
    const TIMEOUT_LETTURA = 10;

    /** L'invio di un cordoglio puo' richiedere di generare un PDF sul portale. */
    const TIMEOUT_INVIO = 60;

    public function TrovaStatistiche() {

        $res2 = self::portale_call('api/user_stats'.$this->urlParams,['token' => $this->token]);

        return self::decodifica($res2);

    }

    public function InviaCordoglio(array $dati) {
        $dati['token'] = $this->token;
        $res2 = self::portale_call('api/add_cordoglio'.$this->urlParams,$dati, self::TIMEOUT_INVIO);
        return self::decodifica($res2);
    }


    public function TrovaNecrologioSingolo(string $slug, $full=false) {

        $res2 = self::portale_call('api/get_necrolog'.$this->urlParams,['slug' => $slug,'get_full' => $full]);

        return self::decodifica($res2);

    }

    public function TrovaTuttiNecrologi($full=false, int $limite=0) {

        $res2 = self::portale_call('api/necrologies_list'.$this->urlParams,[ 'get_full' => $full, 'limit' => $limite ]);

        return self::decodifica($res2);

    }

    /**
     * Decodifica la risposta del portale. Restituisce null se la chiamata
     * non e' andata a buon fine o se la risposta non e' JSON valido.
     */
    private static function decodifica($risposta) {

        if (!is_string($risposta) || $risposta === '') { return null; }

        return json_decode($risposta);

    }

    static function Config() {

        foreach (func_get_arg(0) as $key => $val) {
            self::$$key = $val;
        }

        self::$inited = true;

    }

    /**
     * Esegue la chiamata al portale e ne restituisce il corpo, oppure null in
     * caso di errore di rete. Il corpo viene restituito anche per le risposte
     * diverse da 200: per un necrologio inesistente il portale risponde 404
     * con un messaggio in JSON, che chi chiama deve poter leggere.
     */
    private static function portale_call($entry_point, array $data, $timeout = self::TIMEOUT_LETTURA) {

        $url = self::$END_POINT.'/'.$entry_point;

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body'        => $data,
            'timeout'     => (int) apply_filters('portale_funebre_necrologi_api_timeout', $timeout, $entry_point),
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        return wp_remote_retrieve_body($response);

    }

}
