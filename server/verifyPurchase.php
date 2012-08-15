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
$filter = new KalturaUserFilter();
$filter->idEqual = $USER_ID;
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$filterAdvancedSearch = new KalturaMetadataSearchItem();
$filterAdvancedSearch->type = KalturaSearchOperatorType::SEARCH_AND;
$filterAdvancedSearch->metadataProfileId = PAYPAL_USER_METADATA_PROFILE_ID;
$filterAdvancedSearchItems = array();
$filterAdvancedSearchItems0 = new KalturaSearchCondition();
if(is_numeric($_REQUEST['id']))
	$filterAdvancedSearchItems0->field = "/*[local-name()='metadata']/*[local-name()='PurchasedCategories']";
else
	$filterAdvancedSearchItems0->field = "/*[local-name()='metadata']/*[local-name()='PurchasedEntries']";
$filterAdvancedSearchItems0->value = $_REQUEST['id'];
$filterAdvancedSearchItems[0] = $filterAdvancedSearchItems0;
$filterAdvancedSearch->items = $filterAdvancedSearchItems;
$filter->advancedSearch = $filterAdvancedSearch;
$results = $client->user->listAction($filter, $pager)->objects;
if(count($results) > 0) {
	$response = 'true';
}
echo $response;