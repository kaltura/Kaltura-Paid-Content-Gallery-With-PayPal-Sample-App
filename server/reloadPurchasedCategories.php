<?php
//This script reloads the categories that the user has already purchased
set_time_limit(0);
//Displays all the entries that the user has purchased
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

//Retrieves a certain amount of categories and iterates through them
$filter = new KalturaCategoryFilter();
$filter->orderBy = '-createdAt';
$pager = new KalturaFilterPager();
$pageSize = 16;
$page = 1;
if(array_key_exists('pagenum', $_REQUEST))
	$page = $_REQUEST['pagenum'];
//If a search has been made, display only the categories that match the search terms
$search = trim($_REQUEST['search']);
function escapeChar($input) {
	$input = '\\'.$input[0];
	return $input;
}
$search = preg_replace_callback('|[#-+]|','escapeChar',$search);
$search = preg_replace_callback('|[--/]|','escapeChar',$search);
$search = preg_replace_callback('|!|','escapeChar',$search);
$search = preg_replace_callback('|"|','escapeChar',$search);
$search = preg_replace_callback('|-|','escapeChar',$search);
$search = preg_replace_callback('|\\/|','escapeChar',$search);
$filter->freeText = $search;
//Get the user's purchased categories
$categoryIdin = '';
$metaFilter = new KalturaMetadataFilter();
$metaFilter->metadataProfileIdEqual = PAYPAL_USER_METADATA_PROFILE_ID;
$metaFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::USER;
$metaFilter->objectIdEqual = $USER_ID;
$metaPager = new KalturaFilterPager();
$metaPager->pageSize = 1;
$metaPager->pageIndex = 1;
$results = $client->metadata->listAction($metaFilter, $metaPager)->objects;
if(count($results) > 0) {
	$result = $results[0];
	$xml = simplexml_load_string($result->xml);
	foreach($xml as $field => $value) {
		if($value != "")
			$categoryIdin .= $value.',';
	}
}
if($categoryIdin == "")
	$categoryIdin = "0";
else
	$categoryIdin = substr($categoryIdin, 0, -1);
$filter->idIn = $categoryIdin;
$pager->pageSize = $pageSize;
$pager->pageIndex = $page;
$categories = $client->category->listAction($filter, $pager);
$count = $categories->totalCount;
if($count > 0 && count($categories->objects) == 0)
	$count = 0;

//This function creates a set of links to other category pages
function create_gallery_pager  ($pageNumber, $current_page, $pageSize, $count, $js_callback_paging_clicked) {
	$search = trim($_REQUEST['search']);
	$pageNumber = (int)$pageNumber;
	$b = (($pageNumber+1) * $pageSize) ;
	$b = min ( $b , $count ); // don't let the page-end be bigger than the total count
	$a = min($pageNumber * $pageSize + 1,$count - ($count % $pageSize) + 1);
	$veryLastPage = (int)($count / $pageSize);
	$veryLastPage += ($count % $pageSize == 0) ? 0 : 1;
	if($pageNumber == $veryLastPage) {
		$pageToGoTo = $pageNumber;
		$pageToGoTo += (($pageNumber + 1) * $pageSize > $count) ? 0 : 1;
	}
	else
		$pageToGoTo = $pageNumber + 1;
	if ($pageToGoTo == $current_page)
		$str = "[<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\")'>{$a}-{$b}</a>] ";
	else
		$str =  "<a title='{$pageToGoTo}' href='javascript:{$js_callback_paging_clicked} ($pageToGoTo, \"$search\")'>{$a}-{$b}</a> ";
	return $str;
}
//The server may pull entries up to the hard limit. This number should not exceed 10000.
$hardLimit = 2000;
$pagerString = "";
$startPage = max(1, $page - 5);
$veryLastPage = (int)($count / $pageSize);
$veryLastPage += ($count % $pageSize == 0) ? 0 : 1;
$veryLastPage = min((int)($hardLimit / $pageSize), $veryLastPage);
$endPage = min($veryLastPage, $startPage + 10);
//Iterates to create several page links
for ($pageNumber = $startPage; $pageNumber < $endPage; ++$pageNumber) {
	$pagerString .= create_gallery_pager ($pageNumber , $page  , $pageSize , $count , "userCatPurchasePagerClicked");
}

$beforePageString = "";
$afterPageString = "";
$prevPage = $page - 1;
if($page > 1) $beforePageString .= "<a title='{$prevPage}' href='javascript:userCatPurchasePagerClicked($prevPage, \"$search\")'>Previous</a> ";
// add page 0 if not in list
if($startPage == 1 && $count > 0) $beforePageString .= create_gallery_pager(0, $page, $pageSize, $count, "userCatPurchasePagerClicked");
$nextPage = $page + 1;
if ($page < $veryLastPage) $afterPageString .= "<a title='{$nextPage}' href='javascript:userCatPurchasePagerClicked($nextPage, \"$search\")'>Next</a> ";
$pagerString = "<span style=\"color:#ccc;\">Total (" . $count . ") </span>" . $beforePageString . $pagerString . $afterPageString;

echo '<div class="pagerDiv">'.$pagerString.'</div>';

$categoryCount = 0;
foreach($categories->objects as $category) {
	//Retrieves a group of entries in each category to create preview thumbnails
	$filter = new KalturaMediaEntryFilter();
	$filter->orderBy = KalturaMediaEntryOrderBy::CREATED_AT_DESC;
	$filter->categoriesIdsMatchAnd = $category->id;
	$pager = new KalturaFilterPager();
	$pager->pageSize = 6;
	$pager->pageIndex = 1;
	$entries = $client->media->listAction($filter, $pager)->objects;
	$count = 0;
	$link = "";
	$link .= '<div id="'.$category->id.'" class="userCategories" title="'.$category->name.'">';
	$link .= '<div class="userCategory">';
	foreach($entries as $categoryEntry) {
		$id = $categoryEntry->id;
		$entry = $client->media->get($id);
		$name = $entry->name;
		$display =  "<img width='40' height='23' src='".$entry->thumbnailUrl."'>";
		$link .= $display;
		++$count;
		if($count == 3)
			$link .= '<div class="clearCategory"></div>';
	}
	$link .= '</div>';
	$link .= '<div class="categoryName">'.$category->name.'</div>';
	$link .= '</div>';
	++$categoryCount;
	$categoryLink = "<a class='userCategoryLink' rel='{$category->id}'>{$link}</a>";
	echo $categoryLink;
	//Only display 4 categories per row
	if($categoryCount > 0 && ($categoryCount) % 4 == 0)
		echo '<div style="clear: both;"></div>';
}
echo '<div class="clear"></div>';