<?php
//This script generates the page for creating a gallery configuration file

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);

//Generates a list of players to select from
$filter = new KalturaUiConfFilter();
$filter->orderBy = '-createdAt';
$filter->objTypeEqual = KalturaUiConfObjType::PLAYER;
$filter->tagsMultiLikeOr = 'kdp3';
$filter->creationModeEqual = KalturaUiConfCreationMode::WIZARD;
$pager = new KalturaFilterPager();
$pager->pageSize = 500;
$pager->pageIndex = 1;
$results = $client->uiConf->listAction($filter, $pager)->objects;
?>

<script type="text/javascript">
	$(document).ready(function() {
		jQuery('.czntags').chosen({search_contains: true});
	});
</script>

Select the default player for unpaid content
<div id="defaultPlayer" style="margin-top: 4px; margin-bottom: 4px;">
	<select data-placeholder="Choose a Default Player" id="selectDefaultPlayer" class="czntags" style="width:450px;" tabindex="2">
		<?php
		foreach($results as $player) {
			$pos = strpos($player->name, 'Buy Button');
			//For the default player selection, only display players without a buy button
			if($pos === false)
				echo '<option value="'.$player->id.'">'.$player->id.': '.$player->name.'</option>';
		}
		?>
	</select>
</div>
Select the player for paid content
<div id="paidPlayer" style="margin-top: 4px; margin-bottom: 4px;">
	<select data-placeholder="Choose a Paid Content Player" id="selectPaidPlayer" class="czntags" style="width:450px;" tabindex="2">
		<?php 
		foreach($results as $player) {
			$pos = strpos($player->name, 'Buy Button');
			//For the paid content player selection, only display players with a buy button
			if($pos !== false)
				echo '<option value="'.$player->id.'">'.$player->id.': '.$player->name.'</option>';
		}
		?>
	</select>
</div>
<button id="downloadButton" type="button" class="downloadButton" onclick="downloadConfig()">Download</button>