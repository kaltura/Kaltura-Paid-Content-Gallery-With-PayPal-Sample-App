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

//Filters the entries so that they are ordered by descending creation order
//In other words, the newer videos show up on the front page
$filter = new KalturaMediaEntryFilter();
$filter->orderBy = "-createdAt";
$pager = new KalturaFilterPager();
//Displays 16 entries per page
$pageSize = 16;
$page = 1;
//Retrieves the correct page number
if(array_key_exists('pagenum', $_REQUEST))
	$page = $_REQUEST['pagenum'];
//If a search has been made, display only the entries that match the search terms
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
//Get the user's purchased entries
$entryIdin = '';
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
	foreach($xml->PurchasedEntries as $field => $value) {
		if($value != "")
			$entryIdin .= $value.',';
	}
}
if($entryIdin == "")
	$entryIdin = "0";
else
	$entryIdin = substr($entryIdin, 0, -1);
$filter->idIn = $entryIdin;
$pager->pageSize = $pageSize;
$pager->pageIndex = $page;
$results = $client->media->listAction($filter, $pager);
$count = $results->totalCount;

//This function creates a set of links to other entry pages
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
	$pagerString .= create_gallery_pager ($pageNumber , $page  , $pageSize , $count , "userPurchasePagerClicked");
}

$beforePageString = "";
$afterPageString = "";
$prevPage = $page - 1;
if($page > 1) $beforePageString .= "<a title='{$prevPage}' href='javascript:userPurchasePagerClicked($prevPage, \"$search\")'>Previous</a> ";
// add page 0 if not in list
if($startPage == 1 && $count > 0) $beforePageString .= create_gallery_pager(0, $page, $pageSize, $count, "userPurchasePagerClicked");
$nextPage = $page + 1;
if ($page < $veryLastPage) $afterPageString .= "<a title='{$nextPage}' href='javascript:userPurchasePagerClicked($nextPage, \"$search\")'>Next</a> ";
$pagerString = "<span style=\"color:#ccc;\">Total (" . $count . ") </span>" . $beforePageString . $pagerString . $afterPageString;

echo '<div class="pagerDiv">'.$pagerString.'</div>';
echo '<div class="entriesDiv" style="height: 295px; width: 515px;">';
//Uses a counter to keep track of each entry on the page
//Many elements such as id's and name's rely on this counter
$count = 0;
//Loops through every entry on your current page
foreach ($results->objects as $result) {
	//Creates a thumbnail that can be clicked to view the content
	$name = $result->name;
	$type = $result->mediaType;
	$id = $result->id;
	$categoryNames = explode(',', $result->categories);
	$title = $name."\n"."Belongs to channel(s): ";
	foreach($categoryNames as $categoryName)
		$title .= $categoryName.', ';
	$title = substr($title, 0, -2);
	$display =  $result->thumbnailUrl ? '<img width="120" height="68" id="thumb'.$count.'" src="'.$result->thumbnailUrl.'" title="'.$title.'" >' : '<div>'.$id.' '.$name.'</div>';
	$cats = $result->categoriesIds;
	$thumbnail = "<a class='userthumblink' rel='{$result->id}' cats='$cats' >{$display}</a>";
	echo '<div class="float1" style="margin-right: 7px;">';
	echo $thumbnail;
	echo '</div>';
	//Only show 5 entry thumbnails per row
	if($count > 0 && ($count + 1) % 4 == 0)
		echo '<div style="clear: both;"></div>';
	++$count;
}
echo '</div>';