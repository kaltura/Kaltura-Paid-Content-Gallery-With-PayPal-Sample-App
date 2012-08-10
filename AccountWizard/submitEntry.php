<?php
//This script submits the entered information for the selected video.
//This can either make a free video into paid content or
//reverse the process to make the video free again.

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

//This applies a paid content profile to the entry
if($_REQUEST['isChecked'] == 1) {
	$filter = new KalturaMetadataProfileFilter();
	$filter->nameEqual = 'PayPal (Entries)';
	$pager = new KalturaFilterPager();
	$pager->pageSize = 10;
	$pager->pageIndex = 1;	
	$metaProfiles = $client->metadataProfile->listAction($filter, $pager);
	$profileId = $metaProfiles->objects[0]->id;
	$filter = new KalturaMetadataFilter();
	$filter->metadataProfileIdEqual = $profileId;
	$filter->objectIdIn = $_REQUEST['entryId'];
	$pager = new KalturaFilterPager();
	$pager->pageSize = 10;
	$pager->pageIndex = 1;
	$metadatas = $client->metadata->listAction($filter, $pager);
	if($metadatas->totalCount > 0) {
		$metadata = $metadatas->objects[0];
		$xml = simplexml_load_string($metadata->xml);
		$xml->Paid = 'true';
		$xml->Price = (float) $_REQUEST['price'];
		$xml->TaxPercent = (float) $_REQUEST['tax']; //This number should be a percent (0-100), does not have to be a whole number
		$xml->CurrencyCode = (string) $_REQUEST['currency']; //This should follow the ISO 4217 standard
		$update = $client->metadata->update($metadata->id, $xml->asXML());
	}
	else {
		$xml = simplexml_load_string('<metadata><Paid>true</Paid><Price></Price><TaxPercent></TaxPercent><CurrencyCode></CurrencyCode></metadata>');
		$xml->Paid = 'true';
		$xml->Price = (float) $_REQUEST['price'];
		$xml->TaxPercent = (float) $_REQUEST['tax']; //This number should be a percent (0-100), does not have to be a whole number
		$xml->CurrencyCode = (string) $_REQUEST['currency']; //This should follow the ISO 4217 standard
		$update = $client->metadata->add($profileId, KalturaMetadataObjectType::ENTRY, $_REQUEST['entryId'], $xml->asXML());
	}
	//The entry is given the selected access control profile
	$mediaEntry = new KalturaMediaEntry();
	$mediaEntry->accessControlId = $_REQUEST['access'];
	$update = $client->media->update($_REQUEST['entryId'], $mediaEntry);
	echo 'success';
}
//This makes the entry free
else {
	//Sets the metadata profile's paid flag to false
	$filter = new KalturaMetadataFilter();
	$filter->orderBy = '-createdAt';
	$filter->objectIdEqual = $_REQUEST['entryId'];
	$pager = new KalturaFilterPager();
	$pager->pageSize = 500;
	$pager->pageIndex = 1;
	$metadatas = $client->metadata->listAction($filter, $pager)->objects;
	foreach($metadatas as $metadata) {
		if($client->metadataProfile->get($metadata->metadataProfileId)->name == 'PayPal (Entries)') {
			$xml = simplexml_load_string($metadata->xml);
			$xml->Paid = 'false';
			$update = $client->metadata->update($metadata->id, $xml->asXML());
			break;
		}
	}
	//Finds all the categories that the entry is included in
	$filter = new KalturaCategoryEntryFilter();
	$filter->entryIdEqual = $_REQUEST['entryId'];
	$filter->orderBy = '-createdAt';
	$pager = new KalturaFilterPager();
	$pager->pageSize = 500;
	$pager->pageIndex = 1;
	$entryCategories = $client->categoryEntry->listAction($filter, $pager)->objects;
	//Checks to see if one of those categories is a premium (paid) category
	$foundPaidProfile = false;
	$paidProfile = false;
	foreach($entryCategories as $entryCategory) {
		$filter = new KalturaMetadataFilter();
		$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
		$filter->objectIdEqual = $entryCategory->categoryId;
		$pager = new KalturaFilterPager();
		$pager->pageSize = 500;
		$pager->pageIndex = 1;
		$categoryMetadatas = $client->metadata->listAction($filter, $pager)->objects;
		foreach($categoryMetadatas as $categoryMetadata) {
			$xml = simplexml_load_string($categoryMetadata->xml);
			foreach($xml->children() as $child) {
				if($child->getName() == 'Paid') {
					$foundPaidProfile = true;
					if($xml->Paid == 'true')
						$paidProfile = true;
					break;
				}
			}
		}
	}
	//If the entry does not belong to any paid categories we can remove the access control profile
	if(!$paidProfile) {
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
		//Sets the entry to have the default access control profile
		$entry = $client->media->get($_REQUEST['entryId']);
		if($entry->accessControlId != $default) {
			$mediaEntry = new KalturaMediaEntry();
			$mediaEntry->accessControlId = $default;
			$update = $client->media->update($_REQUEST['entryId'], $mediaEntry);
		}
	}
	echo 'success';
}