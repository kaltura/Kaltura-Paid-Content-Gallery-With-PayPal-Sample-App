<?php
if(array_key_exists('entryId', $_REQUEST))
	isPayItem($_REQUEST['entryId']);

//Gets a video or channel's payment information and returns a PayPal compatible data array
function getItem($itemId){
	require_once('kalturaConfig.php');
	//Includes the client library and starts a Kaltura session to access the API
	//More informatation about this process can be found at:
	//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
	require_once('client/KalturaClient.php');
	$config = new KalturaConfiguration(PARTNER_ID);
	$config->serviceUrl = 'http://www.kaltura.com/';
	$client = new KalturaClient($config);
	global $USER_ID;
	$ks = $client->generateSession(ADMIN_SECRET, $USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
	$client->setKs($ks);
	$array = array();
	$pager = new KalturaFilterPager();
	$pageSize = 500;
	$pager->pageSize = $pageSize;
	$metadataFilter = new KalturaMetadataFilter();
	$metadataFilter->objectIdEqual = $itemId;
	$metaResults = $client->metadata->listAction($metadataFilter, $pager);
	if(count($metaResults->objects) > 0) {
		foreach($metaResults->objects as $metaResult) {
			if($metaResult->metadataProfileId == PAYPAL_METADATA_PROFILE_ID) {
				$xml = simplexml_load_string($metaResult->xml);
				$entry = $client->media->get($itemId);
				$price = (float) $xml->Price;
				$taxPercent = (float) $xml->TaxPercent; //This number should be a percent (0-100), does not have to be a whole number
				$currency = (string) $xml ->CurrencyCode; //This should follow the ISO 4217 standard
				$array =  array("name" => $entry->name, "number" => $itemId, "category" => 'Digital', "amt" => $price, "taxamt" => $taxPercent, "currency" => $currency);
			}
		}
	}
	else {
		$pager = new KalturaFilterPager();
		$pageSize = 500;
		$pager->pageSize = $pageSize;
		$filter = new KalturaMetadataFilter();
		$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$filter->objectIdEqual = $itemId;
		$metaResults = $client->metadata->listAction($filter, $pager);
		foreach($metaResults->objects as $metaResult) {
			if($metaResult->metadataProfileId == PAYPAL_CATEGORY_METADATA_PROFILE_ID) {
				$xml = simplexml_load_string($metaResult->xml);
				$category = $client->category->get($itemId);
				$price = (float) $xml->Price;
				$taxPercent = (float) $xml->TaxPercent; //This number should be a percent (0-100), does not have to be a whole number
				$currency = (string) $xml ->CurrencyCode; //This should follow the ISO 4217 standard
				$array =  array("name" => $category->name.' Channel', "number" => $itemId, "category" => 'Digital', "amt" => $price, "taxamt" => $taxPercent, "currency" => $currency);
			}
		}
	}
	return $array;
}

//Checks to see if a video is free or paid
function isPayItem($entryId) {
	require_once('kalturaConfig.php');
	require_once('client/KalturaClient.php');
	$config = new KalturaConfiguration(PARTNER_ID);
	$config->serviceUrl = 'http://www.kaltura.com/';
	$client = new KalturaClient($config);
	global $USER_ID;
	$ks = $client->generateSession(ADMIN_SECRET, $USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
	$client->setKs($ks);
	$entry = $client->media->get($entryId);
	$paid = '';
	if($entry->categoriesIds != '') {
		$categories = explode(',', $entry->categoriesIds);
		foreach($categories as $category) {
			$filter = new KalturaMetadataFilter();
			$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
			$filter->objectIdEqual = trim($category);
			$pager = new KalturaFilterPager();
			$pager->pageSize = 500;
			$pager->pageIndex = 1;
			$categoryMetadatas = $client->metadata->listAction($filter, $pager)->objects;
			foreach($categoryMetadatas as $categoryMetadata) {
				$categoryMetadataProfile = $client->metadataProfile->get($categoryMetadata->metadataProfileId);
				if($categoryMetadata->metadataProfileId == PAYPAL_CATEGORY_METADATA_PROFILE_ID) {
					$xml = simplexml_load_string($categoryMetadata->xml);
					if($paid != 'true')
						$paid = strtolower($xml->Paid);
					//Only need to find one instance of a paid category
					break;
				}
			}
		}
	}
	//If the video is not part of a paid channel, see if the video itself is paid
	if($paid != 'true') {
		$pager = new KalturaFilterPager();
		$pageSize = 50;
		$pager->pageSize = $pageSize;
		$metadataFilter = new KalturaMetadataFilter();
		$metadataFilter->objectIdEqual = $entryId;
		$metaResults = $client->metadata->listAction($metadataFilter, $pager)->objects;
		foreach($metaResults as $metaResult) {
			if($metaResult->metadataProfileId == PAYPAL_METADATA_PROFILE_ID) {
				$xml = simplexml_load_string($metaResult->xml);
				$paid = strtolower($xml->Paid);
				break;
			}
		}
	}
	if($paid == '')
		echo 'false';
	else
		echo $paid;
}