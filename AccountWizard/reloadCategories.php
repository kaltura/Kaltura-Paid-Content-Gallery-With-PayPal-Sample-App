<?php
//This script displays all the available categories on the account

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

//Filters the entries so that they are ordered by descending creation order
//In other words, the newer videos show up on the front page
$filter = new KalturaCategoryFilter();
$pager = new KalturaFilterPager();
//Displays 200 categories per page
$pageSize = 200;
$page = 1;
//Retrieves the correct page number
if(array_key_exists('pagenum', $_REQUEST))
	$page = $_REQUEST['pagenum'];
//If a search has been made, display only the entries that match the search terms
$search = trim($_REQUEST['search']);
function escapeChar($input)
{
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
$pager->pageSize = $pageSize;
$pager->pageIndex = $page;
$results = $client->category->listAction($filter, $pager);
$count = $results->totalCount;

//This function creates a link to other entry pages
function create_pager  ($pageNumber, $current_page, $pageSize, $count, $js_callback_paging_clicked) {
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
	$pagerString .= create_pager ($pageNumber , $page  , $pageSize , $count , "pagerCatClicked");
}

$beforePageString = "";
$afterPageString = "";
$prevPage = $page - 1;
if($page > 1) $beforePageString .= "<a title='{$prevPage}' href='javascript:pagerCatClicked ($prevPage, \"$search\")'>Previous</a> ";
// add page 0 if not in list
if($startPage == 1 && $count > 0) $beforePageString .= create_pager(0, $page, $pageSize, $count, "pagerCatClicked");
$nextPage = $page + 1;
if ($page < $veryLastPage) $afterPageString .= "<a title='{$nextPage}' href='javascript:pagerCatClicked ($nextPage, \"$search\")'>Next</a> ";
$pagerString = "<span style=\"color:#ccc;\">Total (" . $count . ") </span>" . $beforePageString . $pagerString . $afterPageString;

echo '<div class="pagerDiv">'.$pagerString.'</div>';
//Uses a counter to keep track of each entry on the page
//Many elements such as id's and name's rely on this counter
$count = 0;
//Loops through every entry on your current page
foreach ($results->objects as $result) {
	$fullName = $result->fullName;
	$entriesCount = $result->entriesCount;
	$description = str_replace("'", '', $result->description);
	$description = str_replace("\"", '', $description);
	$id = $result->id;
	$display = "<option class=\"categoryOption\" title=\"{$description}\" value=\"{$id}\">{$fullName} [{$id}] (entries: {$entriesCount})</option>";
	echo $display.PHP_EOL;
	++$count;
}