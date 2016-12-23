<?php
	
	//error_reporting(E_ALL);
	date_default_timezone_set('Europe/Rome');

	session_start();

	require_once __DIR__ . '/vendor/autoload.php';
	include('./classes/my_class/auth_google.php');
	include('./classes/my_class/dbs.php');
	include('./config/my_config.php');	
	include('./classes/my_class/myLogPHP.php');

	// inizializzo la classe per i log
  	$log = new MyLogPHP('./log/log_script_report.'.date("Y-m-d").'.csv');
  	$log->info('The processor starts here.');


 	//connessione al db
 	$db = new dbs(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT, DB_TYPE);
 	$log->info("connessione al DB - DB_HOST:".DB_HOST." DB_USER: ".DB_USER." DB_NAME: ".DB_NAME." DB_TYPE: ".DB_TYPE, 'DB');

 	//preparo la query che mi ritorna tutte le righe 
 	//$sql = "SELECT * FROM reti.vw_collegamenti limit 10 OFFSET 10";
 	// eseguo la query
 	$res_row_source = $db-> query(SQL_QUERY);

 	// guardo quante righe mi sono state restituite
 	$num_results_source = $res_row_source->rowCount();
 	$log->info('numero di record recuperati dal DB: '.$num_results_source, 'INFO');
//echo "\nnumero di record ritornati: ".$num_results_source."\n";
	if($num_results_source > 0){
 		$result = $db->result2array_google($res_row_source);
 	} 	

 	// ho i risultati nell'array
 	// li devo scrivere sul foglio di Google
 	$log->info('inizio autenticazione alle API Spreadsheets v 4.0 di Google', 'INFO');
 	$googleSheets = new googleSheets(GOOGLE_AUTH, GOOGLE_SPREADSHEET_ID, GOOGLE_GID);
 	//faccio l'autenticazione
 	$client = $googleSheets->get_client();

 	$access_token = $googleSheets -> __Google_OAuth2_Authentication($client);

	//scrivo le righe
	$client->setAccessToken($access_token);
	$token = $access_token['access_token'];
	$log->info('token di autenticazione ottenuto: '.$token, 'INFO');
//echo $token;

	$log->info('inizio scrittura sul foglio Google', 'INFO');
	$log->info('GOOGLE_SPREADSHEET_ID: '.GOOGLE_SPREADSHEET_ID."GOOGLE_GID: ".GOOGLE_GID, 'INFO');
	// Get the API client and construct the service object.
	$service = new Google_Service_Sheets($client);

	// dal gid mi ricavo il titolo del foglio
	// e lo uso per calcolare il range in A!annotation
	$title = '';
	$sheetInfo = $service->spreadsheets->get($googleSheets->keySheet, array());
	foreach ($sheetInfo['modelData']['sheets'] as $value) {
		if($value['properties']['sheetId'] == $googleSheets->gidSheet){
			$title = $value['properties']['title'];
			break;
		}
	}

	$range = $title."!".GOOGLE_RANGE;

	// passo i record (come righe) da scrivere
	$service_value_range = new Google_Service_Sheets_ValueRange(array(
	  'values' => $result	  
	));

	$params = array(
	  'valueInputOption' => 'RAW'
	);


	if(GOOGLE_SPREADSHEET_WRITE=='OVERWRITE'){
		// se non dichiarato un range diverso, scrive le nuove righe all'inzio del foglio
		// e sovrascrive le altre
		// documentazione
		// https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets.values/update
		$response_update = $service->spreadsheets_values->update($googleSheets->keySheet, $range, $service_value_range, $params);
	}else{
		// scrive dall'ultima riga
		// documentazione
		// https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets.values/append
		$response_update = $service->spreadsheets_values->append($googleSheets->keySheet, $range, $service_value_range, $params);
	}

	// response dell'operazione di update
	var_dump($response_update);
	$log->info('response operazione di update', 'INFO');
	$log->info('operazione di update avvenuta con successo!!!', 'INFO');

 	// close db connection
 	$db->close();
 	exit();