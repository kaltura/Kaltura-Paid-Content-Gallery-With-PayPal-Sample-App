<?php
//Creates a window when a video's free preview ends and informs the user of payment options
require_once("kalturaConfig.php");
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('client/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
global $USER_ID;
$ks = $client->generateSession(ADMIN_SECRET, $USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);

$filter = new KalturaMetadataFilter();
$filter->objectIdEqual = $_REQUEST['entryId'];
$pager = new KalturaFilterPager();
$pager->pageSize = 500;
$pager->pageIndex = 1;
$metaResults = $client->metadata->listAction($filter, $pager)->objects;
$price = 0;
$currencyCode = 'USD';
$tax = 0;
$also = "";
//Checks to see if the individual video has a price and displays it
foreach($metaResults as $metaResult) {
	if($client->metadataProfile->get($metaResult->metadataProfileId)->name == 'PayPal (Entries)')
		$xml = simplexml_load_string($metaResult->xml);
		$price = (float) $xml->Price;
		$currencyCode = (string) $xml->CurrencyCode;
		$tax = (float) $xml->TaxPercent;
		$also = 'also ';
		break;
}
echo '<div>';
if($price != 0) {
	echo 'This is a paid item. You can purchase access to watch it:';
	echo '<button id="buyNowButton" class="buyButton" type="button" onclick="bill('."'".$_REQUEST['entryId']."'".')">Buy Now</button>';
	echo ' for '.$currencyCode.' '.number_format($price * (1 + .01 * $tax), 2);
}
echo '</div>';
//Checks to see if the video belongs to a channel and gives the user the option to buy that instead
$categoryList = $client->media->get($_REQUEST['entryId'])->categoriesIds;
if($categoryList != '') {
	$categories = explode(',', $categoryList);
	foreach($categories as $category) {
		$filter = new KalturaMetadataFilter();
		$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$filter->objectIdEqual = trim($category);
		$metaResults = $client->metadata->listAction($filter, $pager)->objects;
		foreach($metaResults as $metaResult) {
			if($client->metadataProfile->get($metaResult->metadataProfileId)->name == 'PayPal (Categories)') {
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