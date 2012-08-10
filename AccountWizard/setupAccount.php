<?php
//This script creates the new access control profile and the
//required metadata profiles for the account's entires and categories

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

$accessControl = new KalturaAccessControl();
$accessControl->name = $_REQUEST['name'];
$accessControl->systemName = $_REQUEST['name'];
$accessControlRestrictions = array();
$accessControlRestrictions0 = new KalturaSessionRestriction();
$accessControlRestrictions[0] = $accessControlRestrictions0;
$accessControl->description = 'Automatically generated KS profile';
//If the preview options was selected, apply the preview time to the profile
if($_REQUEST['preview'] == 'true') {
	$accessControlRestrictions1 = new KalturaPreviewRestriction();
	$accessControlRestrictions1->previewLength = $_REQUEST['time'];
	$accessControlRestrictions[1] = $accessControlRestrictions1;
	$accessControl->description = $accessControl->description.' with a '.$_REQUEST['time'].' second preview';
}
$accessControl->restrictions = $accessControlRestrictions;
$results = $client->accessControl->add($accessControl);

//Adds the metadata profile for entries
$filter = new KalturaMetadataProfileFilter();
$filter->nameEqual = 'PayPal (Entries)';
$pager = new KalturaFilterPager();
$pager->pageSize = 500;
$pager->pageIndex = 1;
$results = $client->metadataProfile->listAction($filter, $pager);
if($results->totalCount == 0) {
	$xsdData = file_get_contents('paypalSchema.sdx');
	$metadataProfile = new KalturaMetadataProfile();
	$metadataProfile->metadataObjectType = KalturaMetadataObjectType::ENTRY;
	$metadataProfile->name = 'PayPal (Entries)';
	$metadataProfile->createMode = KalturaMetadataProfileCreateMode::API;
	$viewsData = "";
	$results = $client->metadataProfile->add($metadataProfile, $xsdData, $viewsData);
}

//Adds the metadata profiles for categories
$filter = new KalturaMetadataProfileFilter();
$filter->nameEqual = 'PayPal (Categories)';
$pager = new KalturaFilterPager();
$pager->pageSize = 500;
$pager->pageIndex = 1;
$results = $client->metadataProfile->listAction($filter, $pager);
if($results->totalCount == 0) {
	$xsdData = file_get_contents('paypalCategoriesSchema.sdx');
	$metadataProfile = new KalturaMetadataProfile();
	$metadataProfile->metadataObjectType = 2;
	$metadataProfile->name = 'PayPal (Categories)';
	$metadataProfile->createMode = KalturaMetadataProfileCreateMode::API;
	$viewsData = "";
	$results = $client->metadataProfile->add($metadataProfile, $xsdData, $viewsData);
}