<?php
//Validates if an entry or a category has been purchased
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

$response = 'false';
$filter = new KalturaMetadataFilter();
$filter->metadataProfileIdEqual = PAYPAL_USER_METADATA_PROFILE_ID;
$filter->metadataObjectTypeEqual = 3;
$filter->objectIdEqual = $USER_ID;
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$results = $client->metadata->listAction($filter, $pager)->objects;
if(count($results) > 0) {
	$result = $results[0];
	$xml = simplexml_load_string($result->xml);
	foreach($xml as $key => $value) {
		if($_REQUEST['id'] == $value) {
			$response = 'true';
			break;
		}
	}
}
echo $response;