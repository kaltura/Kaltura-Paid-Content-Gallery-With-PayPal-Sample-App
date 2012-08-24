<?php
//Creates a window when a video's free preview ends and informs the user of payment options
require_once("kalturaConfig.php");
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('client/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$config->format = KalturaClientBase::KALTURA_SERVICE_FORMAT_PHP;
$client = new KalturaClient($config);
global $USER_ID;
$ks = $client->generateSession(ADMIN_SECRET, $USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);

$filter = new KalturaMetadataFilter();
$filter->objectIdEqual = $_REQUEST['entryId']; //return only metadata for this entry
$filter->metadataProfileIdEqual = PAYPAL_METADATA_PROFILE_ID; //return only the relevant profile
$metaResults = $client->metadata->listAction($filter); //since we're limiting to entry id and profile this will return at most 1 result
$price = 0;
$currencyCode = 'USD';
$tax = 0;
$also = "";
//Checks to see if the individual video has a price and displays it
if ($metaResults->totalCount > 0) {
	$metaResult = $metaResults->objects[0];
	$xml = simplexml_load_string($metaResult->xml);
	$price = (float) $xml->Price;
	$currencyCode = (string) $xml->CurrencyCode;
	$tax = (float) $xml->TaxPercent;
	$also = 'also ';
}
echo '<div>';
if($price != 0) {
	echo 'This is a paid item. You can purchase access to watch it:';
	echo '<button id="buyNowButton" class="buyButton" type="button" onclick="bill('."'".$_REQUEST['entryId']."'".')">Buy Now</button>';
	echo ' for '.$currencyCode.' '.number_format($price * (1 + .01 * $tax), 2);
}
echo '</div>';
//Checks to see if the video belongs to a channel and gives the user the option to buy that instead
$categoryList = $client->media->get($_REQUEST['entryId'])->categoriesIds; //get all the categories this entry is in
if($categoryList != '') {
	$filter = new KalturaMetadataFilter();
	$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY; //search for all category metadatas
	$filter->objectIdIn = trim($categoryList); //return metadata for all categories of the given entry
	$filter->metadataProfileIdEqual = PAYPAL_CATEGORY_METADATA_PROFILE_ID; //return only the relevant profile
	$pager = new KalturaFilterPager();
	$pager->pageSize = 500;
	$pager->pageIndex = 1;
	$metaResults = $client->metadata->listAction($filter, $pager)->objects;
	$categories = explode(',', $categoryList);
	foreach($categories as $category) {
		foreach($metaResults as $metaResult) {
			if ($category == $metaResult->objectId) { //if we found the category has payment metadata:
				$xml = simplexml_load_string($metaResult->xml);
				if($xml->Paid == 'true') {
					$price = (float) $xml->Price;
					$currencyCode = (string) $xml->CurrencyCode;
					$tax = (float) $xml->TaxPercent;
					echo 'You can '.$also.'watch this entry and more on:';
					echo '<h2>'.$client->category->get(trim($category))->name.'</h2>';
					echo '<button id="buyCategoryButton" class="buyButton" type="button" onclick="bill('."'".trim($category)."'".')">Subscribe to this channel</button>';
					echo ' for '.$currencyCode.' '.number_format($price * (1 + .01 * $tax), 2);
				}
			}
		}
	}
}