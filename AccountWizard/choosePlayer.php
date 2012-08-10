<?php
//This script generates the page for creating a new player with built-in purchase buttons

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

//Lists the admin's players that do not have a custom buy button yet
$filter = new KalturaUiConfFilter();
$filter->orderBy = '-updatedAt';
$filter->objTypeEqual = KalturaUiConfObjType::PLAYER;
$filter->tagsMultiLikeOr = 'kdp3';
$filter->creationModeEqual = KalturaUiConfCreationMode::WIZARD;
$pager = new KalturaFilterPager();
$pager->pageSize = 10;
$pager->pageIndex = 1;
$results = $client->uiConf->listAction($filter, $pager)->objects;
?>

<div id="chooseText" style="padding-bottom: 5px;">Choose a Player: </div>

<select data-placeholder="Choose a Player" id="playerChoice" class="czntags" style="width:450px;" tabindex="2">
	<?php 
	foreach($results as $player) {
		$pos = strpos($player->name, 'Buy Button');
		if($pos === false)
			echo '<option value="'.$player->id.'">'.$player->id.': '.$player->name.'</option>';
	}
	?>
</select>

<div id="screenChoice" style="padding-top: 5px;">Which screens should include a buy button?</div>
<div id="checks" style="margin-top: 5px;">
	<div><input type="checkbox" id="check0" checked> Before play</div>
	<div><input type="checkbox" id="check1" checked> During play, when mouse is on screen</div>
	<div><input type="checkbox" id="check2" checked> When paused</div>
	<div><input type="checkbox" id="check3" checked> End play</div>
</div>

<div id="buyFunction" style="padding-top: 5px;">What is the name of your buy function? (Default is provided)
	<input type="text" id="functionName" value="kalturaPayPalBuyHandler" />
</div>

<div id="newPlayer" style="padding-top: 5px;">
	<div><input type="checkbox" id="newCheck" checked> Create as a new player (If not selected, this will overwrite the old player)</div>
</div>

<button id="submitPlayer" type="button" class="submitPlayer" onclick="createPlayer()">Submit</button>
<img src="lib/loader.gif" id="submitLoader" style="display: none;">