<?php
set_time_limit(0);
//Creates the list of channels that can be browsed
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

//Creates a previous page arrow if not on the first page
if($_REQUEST['page'] > 1) {
	$prevPage = $_REQUEST['page'] - 1;
	echo '<div id="leftTriangle"><a class="categoryPage" rel="'.$prevPage.'" ><img src="client/triangle.png"></a></div>';
}

//Retrieves a certain amount of categories and iterates through them
$filter = new KalturaCategoryFilter();
$filter->orderBy = '-createdAt';
$pager = new KalturaFilterPager();
$pageSize = 7;
$pager->pageSize = $pageSize;
$pager->pageIndex = $_REQUEST['page'];
$categories = $client->category->listAction($filter, $pager);
$categoryCount = 0;
foreach($categories->objects as $category) {
	$paidCategory = false;
	$filter = new KalturaCategoryFilter();
	$filter->idEqual = $category->id;
	$pager = new KalturaFilterPager();
	$pager->pageSize = 1;
	$pager->pageIndex = 1;
	$filterAdvancedSearch = new KalturaMetadataSearchItem();
	$filterAdvancedSearch->type = KalturaSearchOperatorType::SEARCH_AND;
	$filterAdvancedSearch->metadataProfileId = PAYPAL_CATEGORY_METADATA_PROFILE_ID;
	$filterAdvancedSearchItems = array();
	$filterAdvancedSearchItems0 = new KalturaSearchCondition();
	$filterAdvancedSearchItems0->field = "/*[local-name()='metadata']/*[local-name()='Paid']";
	$filterAdvancedSearchItems0->value = 'true';
	$filterAdvancedSearchItems[0] = $filterAdvancedSearchItems0;
	$filterAdvancedSearch->items = $filterAdvancedSearchItems;
	$filter->advancedSearch = $filterAdvancedSearch;
	$payCheck = $client->category->listAction($filter, $pager)->objects;
	if(count($payCheck) > 0)
		$paidCategory = true;
	//Then retrieves a group of entries in each category to create preview thumbnails
	$filter = new KalturaMediaEntryFilter();
	$filter->orderBy = KalturaMediaEntryOrderBy::CREATED_AT_DESC;
	$filter->categoriesIdsMatchAnd = $category->id;
	$pager = new KalturaFilterPager();
	$pager->pageSize = 6;
	$pager->pageIndex = 1;
	$entries = $client->media->listAction($filter, $pager)->objects;
	$count = 0;
	$link = "";
	$link .= '<div id="'.$category->id.'" class="categories">';
	$link .= '<div class="category">';
	foreach($entries as $categoryEntry) {
		$id = $categoryEntry->id;
		$entry = $client->media->get($id);
		$name = $entry->name;
		$display =  "<img width='30' height='17' src='".$entry->thumbnailUrl."' title='".$id." ".$name."' >";
		$link .= $display;
		++$count;
		if($count == 3)
			$link .= '<div class="clearCategory"></div>';
	}
	$link .= '</div>';
	$link .= '<div class="categoryName">'.$category->name.'</div>';
	if($paidCategory)
		$link .= '<img class="channelSigns" src="client/channeldollarsign.png">';
	$link .= '</div>';
	++$categoryCount;
	$categoryLink = "<a class='categoryLink' rel='{$category->id}' >{$link}</a>";
	echo $categoryLink;
}
//Creates a next page arrow if there are entries left to be displayed
if($categories->totalCount > $pageSize * $_REQUEST['page']) {
	$nextPage = $_REQUEST['page'] + 1;
	echo '<div id="rightTriangle"><a class="categoryPage" rel="'.$nextPage.'"><img src="client/triangle.png"></a></div>';
}
echo '<div class="clear"></div>';