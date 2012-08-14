<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
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
if(count($results) > 0) {
	$result = $results[0];
	$xml = simplexml_load_string($result->xml);
	if(is_numeric($_REQUEST['id'])) {
		$xml->PurchasedCategories[] = $_REQUEST['id'];
	}
	else {
		$xml->PurchasedEntries[] = $_REQUEST['id'];
	}
	print_r($xml->asXML());
	$results = $client->metadata->update($result->id, $xml->asXML());
	print_r($results);
}
else {
	$xml = simplexml_load_string('<metadata><PurchasedEntries></PurchasedEntries><PurchasedCategories></PurchasedCategories></metadata>');
	if(is_numeric($_REQUEST['id'])) {
		$xml->PurchasedCategories = $_REQUEST['id'];
	}
	else {
		$xml->PurchasedEntries = $_REQUEST['id'];
	}
	$results = $client->metadata->add(PAYPAL_USER_METADATA_PROFILE_ID, 3, $USER_ID, $xml->asXML());
	print_r($results);
}
echo 'success';