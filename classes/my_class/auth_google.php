<?php

//require_once dirname(__FILE__).'./../vendor/google-api-php-client/src/Google/Client.php';
//require_once dirname(__FILE__).'./../vendor/google-api-php-client/src/Google/Service/Drive.php';   

//require_once dirname(__FILE__).'./../../vendor/google-api-php-client/vendor/autoload.php';
//require_once dirname(__FILE__).'./../../vendor/google-api-php-client/src/Google/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
define('SCOPES', implode(' ', array(
  Google_Service_Sheets::SPREADSHEETS)
));


class googleSheets{
    
    private $googleclient;
    private $key_file;
    public  $keySheet;
    public  $gidSheet;

    public function __construct($key_file, $keySheet, $gidSheet){
        $this->key_file = $key_file;
        $this->keySheet = $keySheet;
        $this->gidSheet = $gidSheet;
    }
    
    //public function get_access_token(){
    public function get_client(){
        
        //setto le costanti
        //$this->__setcostant();
        
        //instanzio le api di google
        $client = new Google_Client();
        $client->setApplicationName("SpreadSheet");
        $this->googleclient = $client;
echo "creato client";
        //return $this->__Google_OAuth2_Authentication($client);
        //$this->__Google_OAuth2_Authentication($this->googleclient);
        return $this->googleclient;
    }


    public function _gid_to_wid($gid) {
        // (gid xor 31578) encoded in base 36

        // nuovi spreadsheet
        $xorval = $gid > 31578 ? 474 : 31578;
        $letter = $gid > 31578 ? 'o' : '';
        $xor = (int)($gid ^ $xorval);
        $res = $letter.(string)(base_convert($xor, 10, 36));
        return $res;
    
    }
    
    private function _wid_to_gid($wid) {
                
        // nuovi spreadsheet
        $lenght = strlen($wid);
        //echo "<br/> lenght: ".$lenght;
        $widval = $lenght > 3 ? substr($wid,1) : $wid;
        //echo "<br/> widval: ".$widval;
        $xorval = $lenght > 3 ? 474 : 31578;
        //echo "<br/> xorval: ".$xorval;
        $res = (int)(base_convert($widval, 36, 10)) ^ $xorval;
        return $res;
    
    }

    public function __Google_OAuth2_Authentication($client){
                

        if ($credentials_file = $this->checkServiceAccountCredentialsFile()) {
          // set the location manually
          $scopes = array('https://www.googleapis.com/auth/drive','https://www.googleapis.com/auth/spreadsheets');
          //$cred =$client->loadServiceAccountJson($credentials_file, $scopes);
          $cred = $client->setAuthConfig($credentials_file);
          
echo "set credentials Ok";
        } else {
          echo $this->missingServiceAccountDetailsWarning();
          exit;
        }
        $client->setScopes($scopes);
        /*$client->setScopes([
            "https://sites.google.com/feeds/",
            "https://spreadsheets.google.com/feeds/",
            "https://www.googleapis.com/auth/drive",
            "https://www.googleapis.com/auth/drive.appdata",
            "https://www.googleapis.com/auth/drive.file",
            "https://www.googleapis.com/auth/drive.metadata.readonly",
            "https://www.googleapis.com/auth/drive.readonly",
        ]);     */

        //$client->setScopes(SCOPES);
 
       
        // will output:
        // array(4) {
        //   ["access_token"]=>
        //   string(73) "ya29.FAKsaByOPoddfzvKRo_LBpWWCpVTiAm4BjsvBwxtN7IgSNoUfcErBk_VPl4iAiE1ntb_"
        //   ["token_type"]=>
        //   string(6) "Bearer"
        //   ["expires_in"]=>
        //   int(3593)
        //   ["created"]=>
        //   int(1445548590)
        // }

        /*if ($client->isAccessTokenExpired()) {
            //$accessToken = $client->refreshTokenWithAssertion();
            $accessToken = $client->getAuth()->refreshTokenWithAssertion($cred);
        }else{
            $accessToken = $client->getAccessToken();
        }*/


        /*if($client->getAuth()->isAccessTokenExpired()) {
          $client->getAuth()->refreshTokenWithAssertion($cred);
        }*/
        if($client->isAccessTokenExpired()) {
          $client->refreshTokenWithAssertion($cred);
        }
        $accessToken = $client->getAccessToken();

        //$token = $accessToken[0]['access_token'];
        //$_SESSION['service_token'] = $token;

        //$client->setAccessToken($accessToken);

        return $accessToken;

    }

    private function letterToColumn($letter)
    {
        $number = 0;
        $lenght = strlen($letter);
        for($i=0; $i<$lenght; $i++)
        {
                
            $number = $number + (ord($letter[$i]) - 64) * pow(26, $lenght - $i -1);
        }

        return $number;    
    }

    public function __retrieve_name_column_from_letter($access_token, $keysheet, $wid, $columns){
        //recupero il nome della label dalla lettera passata

        $columns = unserialize($columns);
        for($i=0;$i<count($columns);$i++){
            $position = $this->letterToColumn($columns[$i]);
            $url = "https://spreadsheets.google.com/feeds/cells/$keysheet/$wid/private/full/R1C".$position.'?v=3.0&access_token='.$access_token.'&alt=json'; 
            //echo $url;
            $returned_content = file_get_contents($url);
            $SpreadsheetResults = json_decode($returned_content, true);
            $label_result = strtolower(str_replace(' ','', $SpreadsheetResults['entry']['content']['$t'])); 
            $label[$i] = str_replace(array( '(', ')', '/', '_', '<', '>', '+', '=', '\'', ',','!'), '', $label_result); 
            //se ci sono,rimuovo le parentesi 
            //$label[$i] = str_replace(array( '(', ')', '/', '_', '<', '>', '+', '=', '\'', ','), '', $label);             
                     
        }
   
        $labels['labels'] = $label;
        return $labels;
    }


    public function __gdocCall($caso, $access_token, $keysheet, $wid = null, $position = null, $sq = null, $query = null){

        if($wid == null){
            $wid = 'od6';
        }
        
        //setto la cUrl per la chiamata
        switch ($caso){
            case 1:
                $url = "https://spreadsheets.google.com/feeds/list/$keysheet/$wid/private/"
                . "full?v=3.0&access_token=$access_token&alt=json";
       
            break;
            case 2:
                $url = "https://spreadsheets.google.com/feeds/cells/$keysheet/$wid/private/"
                    . "full/R1C$position?v=3.0&access_token=$access_token&alt=json";
                
            break;
            case 3: //leggo solo una determinata riga, filtro, restituisce json (più lenta e meno sicura del metodo 4)
                $url = "https://spreadsheets.google.com/feeds/list/$keysheet/$wid/private/"
                . "full?v=3.0&access_token=$access_token&alt=json&sq=".str_replace("'", '', $sq['nomecampo'])."=".$sq['valorecampo'];
            break;
            case 4: //leggo solo una determinata riga, sql query, restituisce un csv
                //in questo caso, passo il gid
                $url = "https://docs.google.com/spreadsheets/d/$keysheet/gviz/tq?tq=$query"
                . "&v=3.0&headers=1&access_token=$access_token&tqx=reqId:".md5($wid).";out:csv&gid=$wid";  
                //echo $url;
            break;
            case 5: //come il 4, ma per i documenti con i gid vecchi
                $url = "https://spreadsheets.google.com/tq?tq=$query"
                . "&v=3.0&access_token=$access_token&tqx=reqId:".md5($wid).";out:csv&gid=$wid&key=$keysheet";  //in questo caso, passo il gid
            break;
            case 6:
                // es. mi ritorna il nome attuale della label nella colonna A - serve il gid - NON FUNZIONA
                //https://spreadsheets.google.com/a/lepida.it/tq?key=0AiPjR2KDALmhdFJDVjZ5QW5xcXJhRlI5cGVQam1QZEE&gid=od6&tq=select+A+limit+0
                $url = "https://spreadsheets.google.com/a/lepida.it/tq?key=$keysheet&gid=$wid&tq=select%20B%2CH%2CI%2CAI%2CAO&access_token=$access_token&alt=csv";
                //echo $url;               
            default :
            break;
     
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
       
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $google_sheet = curl_exec($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);

       
        return $google_sheet;

        
    }

    public function ClearAssociativeArray($Array){

        foreach ($Array as $key => $value) {
            unset($Array [$key]);
        }

        return $Array;
    }

    private function checkServiceAccountCredentialsFile()
    {
        // service account creds
        $application_creds = dirname(dirname(dirname(__FILE__)))."/".$this->key_file;
        //$application_creds = '/var/www/html/geocoding/'.$this->key_file;
echo "credentials: ".$application_creds;
        return file_exists($application_creds) ? $application_creds : false;
    }

    private function missingServiceAccountDetailsWarning()
    {   
       $ret = "
            <h3 class='warn'>
              Warning: You need download your Service Account Credentials JSON from the
              <a href='http://developers.google.com/console'>Google API console</a>.
            </h3>";

        return $ret;
    }
}
    