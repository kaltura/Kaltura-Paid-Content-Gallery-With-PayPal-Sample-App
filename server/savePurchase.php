<?php
//Updates the metadata for the user whenever they purchase anything
require_once("kalturaConfig.php");
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('client/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
global $USER_ID;
$ks = $client->session->start(ADMIN_SECRET, $USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);

$filter = new KalturaMetadataFilter();
$filter->metadataProfileIdEqual = PAYPAL_USER_METADATA_PROFILE_ID;
$filter->metadataObjectTypeEqual = 3;
$filter->objectIdEqual = $USER_ID;
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$metadataPlugin = KalturaMetadataClientPlugin::get($client);
$results = $metadataPlugin->metadata->listAction($filter, $pager)->objects;
//If they already have a metadata object, update it
if(count($results) > 0) {
	$result = $results[0];
	$xml = simplexml_load_string($result->xml);
	$xmlString = '<metadata>';
	$switched = false;
	//Parses the xml object and creates a sorted string (Kaltura will not accept properly sorted XML)
	foreach($xml as $key => $node) {
		//Will place the new entry/category id between the entries and categories
		if($key == 'PurchasedCategories' && !$switched) {
			if(is_numeric($_REQUEST['id'])) {
				$xmlString .= '<PurchasedCategories>'.$_REQUEST['id'].'</PurchasedCategories>';
			}
			else {
				$xmlString .= '<PurchasedEntries>'.$_REQUEST['id'].'</PurchasedEntries>';
			}
			$switched = true;
		}
		$xmlString .= '<'.$key.'>'.$node.'</'.$key.'>';
	}
	$xmlString .= '</metadata>';
	$results = $client->metadata->update($result->id, $xmlString);
	print_r($results);
}
//If no metadata object exists, create it
else {
	$xml = "";
	if(is_numeric($_REQUEST['id'])) {
		$xml = simplexml_load_string('<metadata><PurchasedEntries></PurchasedEntries><PurchasedCategories>'.$_REQUEST['id'].'</PurchasedCategories></metadata>');
	}
	else {
		$xml = simplexml_load_string('<metadata><PurchasedEntries>'.$_REQUEST['id'].'</PurchasedEntries><PurchasedCategories></PurchasedCategories></metadata>');
	}
	$results = $client->metadata->add(PAYPAL_USER_METADATA_PROFILE_ID, 3, $USER_ID, $xml->asXML());
	print_r($results);
}
echo 'success';