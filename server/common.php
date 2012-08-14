<?php
//This script is used to communicate with PayPal's servers for video purchases
define("UID", "thomas_1342191536_biz_api1.kaltura.com");  // Replace the string with your API User ID
define("PASSWORD", "1342191559");  // Replace the value with your API password
define("SIG", "AKRqrAaiE6xWp4IfVwhAfU4OHaYBAaUxSlN06OFKBNbuBTl9-zmKkaVI");  // Replace the string with your API Signature
define("VER", "85");
define("URLBASE", "https://api-3t.sandbox.paypal.com/nvp");
define("URLREDIRECTINCONTEXT", "https://www.sandbox.paypal.com/incontext");
define("URLREDIRECT", "https://www.sandbox.paypal.com/webscr");

function parseString($string = null) {
    $recordString = explode("&", $string);
    foreach ($recordString as $value){
        $singleRecord = explode("=", $value);
        $allRecords[$singleRecord[0]] = $singleRecord[1];
    }
    return $allRecords;
}
    
function runCurl($url, $postVals = null) {
    $ch = curl_init($url);
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3
    );
    if ($postVals != null){
        $options[CURLOPT_POSTFIELDS] = $postVals;
        $options[CURLOPT_CUSTOMREQUEST] = "POST";  
    }	
	$header = array('X-PAYPAL-REQUEST-SOURCE' => 'HTML5 Toolkit PHP');
	$options[CURLOPT_HTTPHEADER] = $header;
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__).'/cert/api_cert_chain.crt');
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}