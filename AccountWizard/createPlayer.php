<?php
//This script generates and saves a player in the account

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

$choices = $_REQUEST['choices'];
$buyFunction = $_REQUEST['buyFunction'];
//If the user chose to have a new player generated, clone the old one
if($choices[4])
	$id = $client->uiConf->cloneAction($_REQUEST['player'])->id;
//Otherwise we will add all the features to the selected player
else
	$id = $_REQUEST['player'];
//The custom flashVars are introduced based on the administrator's choices
$uiConf = $client->uiConf->get($id);
$xml = $uiConf->confFile;
$xml2 = $uiConf->confFileFeatures;
$dom  = new DOMDocument();
$dom2 = new DOMDocument();
$dom->loadXML($xml);
$dom2->loadXML($xml2);
$xpath = new DOMXpath($dom);
$xpath2 = new DOMXpath($dom2);
$uiVarsNode = $xpath->query('/layout/uiVars')->item(0);
$uiVarsNode2 = $xpath2->query('/snapshot/uiVars')->item(0);
$buyButton = simplexml_load_file('buyButton.xml');
foreach($buyButton as $key => $fields) {
	$pre = "";
	switch ($key) {
		case 'onControlsBarFields':
			$pre = 'controls';
			break;
		case 'startScreenFields':
			if($choices[0] == 'true')
				$pre = 'start';
			break;
		case 'playScreenFields':
			if($choices[1] == 'true')
				$pre = 'play';
			break;
		case 'pauseScreenFields':
			if($choices[2] == 'true')
				$pre = 'pause';
			break;
		case 'endScreenFields':
			if($choices[3] == 'true')
				$pre = 'end';
			break;
	}
	if($pre != "") {
		//These fields are created for every "screen" and control bar
		foreach($fields->field as $field) {
			$uiVar = $dom->createElement('var');
			$uiVar->setAttribute('key', $pre.$field->key);
			$uiVar->setAttribute('value', $field->value);
			$uiVar->setAttribute('overrideFlashvar', 'true');
			$uiVarsNode->appendChild($uiVar);
			
			$uiVar2 = $dom2->createElement('var');
			$uiVar2->setAttribute('key', $pre.$field->key);
			$uiVar2->setAttribute('value', $field->value);
			$uiVar2->setAttribute('overrideFlashvar', 'true');
			$uiVarsNode2->appendChild($uiVar2);
		}
		//These fields are created for every "screen" and control bar
		foreach($buyButton->generalFields->field as $field) {
			$uiVar = $dom->createElement('var');
			$uiVar->setAttribute('key', $pre.$field->key);
			$uiVar->setAttribute('value', $field->value);
			$uiVar->setAttribute('overrideFlashvar', 'true');
			$uiVarsNode->appendChild($uiVar);
				
			$uiVar2 = $dom2->createElement('var');
			$uiVar2->setAttribute('key', $pre.$field->key);
			$uiVar2->setAttribute('value', $field->value);
			$uiVar2->setAttribute('overrideFlashvar', 'true');
			$uiVarsNode2->appendChild($uiVar2);
		}
		//If the user enters a custom javascript buy function, overwrite the default
		if($buyFunction != 'kalturaPayPalBuyHandler') {
			$uiVar = $dom->createElement('var');
			$uiVar->setAttribute('key', $pre.'kp_buyBtn.kClick');
			$uiVar->setAttribute('value', 'jsCall('.$buyFunction.', mediaProxy.entry.id)');
			$uiVar->setAttribute('overrideFlashvar', 'true');
			$uiVarsNode->appendChild($uiVar);
			
			$uiVar2 = $dom2->createElement('var');
			$uiVar->setAttribute('key', $pre.'kp_buyBtn.kClick');
			$uiVar->setAttribute('value', 'jsCall('.$buyFunction.', mediaProxy.entry.id)');
			$uiVar2->setAttribute('overrideFlashvar', 'true');
			$uiVarsNode2->appendChild($uiVar2);
		}
		//These fields are specifically for on screen controls
		if($pre != 'controls') {
			foreach($buyButton->onVideoScreenFields->field as $field) {
				$uiVar = $dom->createElement('var');
				$uiVar->setAttribute('key', $pre.$field->key);
				$uiVar->setAttribute('value', $field->value);
				$uiVar->setAttribute('overrideFlashvar', 'true');
				$uiVarsNode->appendChild($uiVar);
			
				$uiVar2 = $dom2->createElement('var');
				$uiVar2->setAttribute('key', $pre.$field->key);
				$uiVar2->setAttribute('value', $field->value);
				$uiVar2->setAttribute('overrideFlashvar', 'true');
				$uiVarsNode2->appendChild($uiVar2);
			}
		}
	}
}
$uiConf->confFile = $dom->saveXML();
$uiConf->confFileFeatures = $dom2->saveXML();
$uiConf->name = $uiConf->name.' (Buy Button)';
$results = $client->uiConf->update($id, $uiConf);
echo 'success';