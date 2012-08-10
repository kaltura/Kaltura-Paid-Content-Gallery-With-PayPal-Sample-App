<?php
//This script submits the entered information for the selected category.
//This can either make a free category into a paid content category or
//reverse the process to make the category free again.

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

//This applies a paid content profile to the category
if(strtolower($_REQUEST['isChecked']) == 'true') {
	//The category is given the appropriate metadata profile
	$pager = new KalturaFilterPager();
	$pageSize = 50;
	$pager->pageSize = $pageSize;
	$metadataFilter = new KalturaMetadataFilter();
	$metadataFilter->objectIdEqual = $_REQUEST['categoryId'];
	$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$metaResults = $metadataPlugin->metadata->listAction($metadataFilter, $pager)->objects;
	if (count($metaResults) > 0) {
		// if there is already such metadata, get it and let's update it:
		foreach($metaResults as $metaResult) {
			$metadataProfile = $client->metadataProfile->get($metaResult->metadataProfileId);
			if($metadataProfile->name == 'PayPal (Categories)') {
				$xml = simplexml_load_string($metaResult->xml);
				$xml->Paid = strtolower($_REQUEST['isChecked']);
				$xml->Price = (float) $_REQUEST['price'];
				$xml->TaxPercent = (float) $_REQUEST['tax']; //This number should be a percent (0-100), does not have to be a whole number
				$xml->CurrencyCode = (string) $_REQUEST['currency']; //This should follow the ISO 4217 standard
				$xmlData = $xml->asXML();
				$results = $metadataPlugin->metadata->update($metaResult->id, $xmlData);
				break;
			}
		}
	} 
	else {
		// if the metadata profile doesn't exist on this category,
		// validate that we indeed have this metadata profile setup on the account (verify that the metadata schema exists):
		$filter = new KalturaMetadataProfileFilter();
		$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$filter->nameEqual = 'PayPal (Categories)';
		$results = $metadataPlugin->metadataProfile->listAction($filter, $pager)->objects;
		if (count($results) > 0) {
			//the schema is defined on this account, create a new metadata on this category:
			//note: a smarter way would be to get the xsd and built this xml according to it...
			$xml = simplexml_load_string('<metadata><Paid>true</Paid><Price></Price><TaxPercent></TaxPercent><CurrencyCode></CurrencyCode></metadata>');
			$xml->Paid = strtolower($_REQUEST['isChecked']);
			$xml->Price = (float) $_REQUEST['price'];
			$xml->TaxPercent = (float) $_REQUEST['tax']; //This number should be a percent (0-100), does not have to be a whole number
			$xml->CurrencyCode = (string) $_REQUEST['currency']; //This should follow the ISO 4217 standard
			$xmlData = $xml->asXML();
			$results = $metadataPlugin->metadata->add($results[0]->id, KalturaMetadataObjectType::CATEGORY, $_REQUEST['categoryId'], $xmlData);
		} 
		else {
			//there's no such schema defined, tell the user to add the schema using the account setup wizard
			// do something...
			echo 'The payments schema isn\'t defined on this account. Please use the account setup wizard to add the payments schema to your account before setting price for categories/entries.';
		}
	}
	//Every entry in the category is given the selected metadata profile
	//If a new entry is added to a category afterwards, the process must be repeated through the wizard
	$filter = new KalturaCategoryEntryFilter();
	$filter->orderBy = '-createdAt';
	$filter->categoryIdEqual = $_REQUEST['categoryId'];
	$pager = new KalturaFilterPager();
	$pager->pageSize = 500;
	$pager->pageIndex = 1;
	$entries = $client->categoryEntry->listAction($filter, $pager)->objects;
	foreach($entries as $entry) {
		$mediaEntry = new KalturaMediaEntry();
		$mediaEntry->accessControlId = $_REQUEST['access'];
		$update = $client->media->update($entry->entryId, $mediaEntry);
	}
	echo 'success';
}
//This returns a paid category to being free
else {
	//Sets the metadata profile's paid flag to false
	$pager = new KalturaFilterPager();
	$pageSize = 500;
	$pager->pageSize = $pageSize;
	$metadataFilter = new KalturaMetadataFilter();
	$metadataFilter->objectIdEqual = $_REQUEST['categoryId'];
	$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
	$metadataPlugin = KalturaMetadataClientPlugin::get($client);
	$metaResults = $metadataPlugin->metadata->listAction($metadataFilter, $pager)->objects;
	foreach($metaResults as $metaResult) {
		$metadataProfile = $client->metadataProfile->get($metaResult->metadataProfileId);
		if($metadataProfile->name == 'PayPal (Categories)') {
			$xml = simplexml_load_string($metaResult->xml);
			$xml->Paid = 'false';
			$xmlData = $xml->asXML();
			$results = $metadataPlugin->metadata->update($metaResult->id, $xmlData);
			break;
		}
	}
	//Finds the default access control profile
	$filter = new KalturaAccessControlProfileFilter();
	$filter->orderBy = '+createdAt';
	$pager = new KalturaFilterPager();
	$pager->pageSize = 500;
	$pager->pageIndex = 1;
	$profiles = $client->accessControlProfile->listAction($filter, $pager);
	$default = 0;
	foreach($profiles->objects as $profile) {
		if($profile->isDefault) {
			$default = $profile->id;
			break;
		}
	}
	//Sets every video in the category to the default access control profile
	$filter = new KalturaCategoryEntryFilter();
	$filter->orderBy = '-createdAt';
	$filter->categoryIdEqual = $_REQUEST['categoryId'];
	$pager = new KalturaFilterPager();
	$pager->pageSize = 500;
	$pager->pageIndex = 1;
	$entries = $client->categoryEntry->listAction($filter, $pager)->objects;
	foreach($entries as $entry) {
		if($client->media->get($entry->entryId)->accessControlId != $default) {
			$mediaEntry = new KalturaMediaEntry();
			$mediaEntry->accessControlId = $default;
			$update = $client->media->update($entry->entryId, $mediaEntry);
		}
	}
	echo 'success';
}