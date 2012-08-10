<?php
//This script generates the page for applying a paid profile to categories

//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('lib/php5/KalturaClient.php');
$config = new KalturaConfiguration($_REQUEST['partnerId']);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$client->setKs($_REQUEST['session']);
?>
<script type="text/javascript">
	$(document).ready(function() {
		//If the paid content check is selected, display more options
		//Otherwise, hide them
		if ($('#payCheck').is(":checked")) {
			$("#payFields").show();
			$("#fillDiv").hide();
		}
		else {
			$("#payFields").hide();
			$("#fillDiv").show();
		}
		
		jQuery('.czntags').chosen({search_contains: true});
		
		// set the price and tax fields to positive floats only
		$("#entryPrice").numeric({ negative: false });
		$("#entryTax").numeric({ negative: false });
		// limit the range of the tax field to 0-100
		$("#entryTax").keyup(function (event){
			if(event.which != 190) {
				taxvalue = parseFloat($(this).val());
				$(this).val(validateRange(taxvalue));
			}
			return;
		});
	});

	//Ensures that the value entered must be a valid percentage
	function validateRange(val) {
		if (val < 0) return 0;
		if (val > 100) return 100;
		if (isNaN(val)) return 0;
		return val;
	}

	//Toggle pay options on/off
	$("#payCheck").click(function() {
		if ($(this).is(":checked")) {
			$("#payFields").show();
			$("#fillDiv").hide();
		}
		else {
			$("#payFields").hide();
			$("#fillDiv").show();
		}
	});

	//Submits the entry to either be granted paid or free status
	submitEntry = function () {
		$('#submitEntry').hide();
		$('#submitEntryPriceLoader').show();
		var checked = 0;
		if ($('#payCheck').is(":checked")) checked = 1;
		priceVal = parseFloat($('#entryPrice').val());
		taxVal = validateRange(parseFloat($('#entryTax').val()));
		currencyVal = $.trim($('#currencyChoice').val());
		accessVal = $.trim($('#accessChoice').val());
		$.ajax({
			type: "POST",
			url: "submitEntry.php",
			data: { session: "<?php echo $_REQUEST['session']; ?>", 
					partnerId: "<?php echo $_REQUEST['partnerId']; ?>", 
					entryId: "<?php echo $_REQUEST['entryId']; ?>",
					isChecked: checked,
					price: priceVal,
					tax: taxVal,
					currency: currencyVal,
					access: accessVal
				   }
		}).done(function(msg) {
			$('#submitEntryPriceLoader').hide();
			if (msg != 'success') {
				// something went bad, show the error message:
				$('.formelements').hide();
				$('#fillDiv').show();
				$('#fillDiv').html(msg);
			} else {
				// saved, close the lightbox
				$.colorbox.close();
			}
		});
	};
</script>

<?php
$entry = $client->media->get($_REQUEST['entryId']);
$accessId = $entry->accessControlId;
echo '<h2 id="entryName" style="font-weight: bold;">'.$entry->name.' ('.$entry->id.') </h2>';
$payCheck = "";
$pager = new KalturaFilterPager();
$pageSize = 50;
$pager->pageSize = $pageSize;
$metadataFilter = new KalturaMetadataFilter();
$metadataFilter->objectIdEqual = $entry->id;
$metaResults = $client->metadata->listAction($metadataFilter, $pager)->objects;
$paid = '';
$price = '';
$taxPercent = '';
$currency = '';
//Retrives the metadata information if it already exists for the video
foreach($metaResults as $metaResult) {
	$metadataProfile = $client->metadataProfile->get($metaResult->metadataProfileId);
	if($metadataProfile->name == 'PayPal (Entries)') {
		$xml = simplexml_load_string($metaResult->xml);
		$paid = strtolower($xml->Paid);
		$price = number_format((float) $xml->Price, 2);
		$taxPercent = (float) $xml->TaxPercent; //This number should be a percent (0-100), does not have to be a whole number
		$currency = (string) $xml ->CurrencyCode; //This should follow the ISO 4217 standard
		break;
	}
}
$display = 'display: none,';
$displayFiller = "";
if($paid == 'true') {
	$payCheck = "checked";
	$display = "";
	$displayFiller = 'display: none,';
}
$currencyCodes = array("USD","AUD","CAD","CZK","DKK","EUR","HKD","HUF","JPY","NOK","NZD","PLN","GBP","SGD","SEK","CHF");
$filter = new KalturaAccessControlProfileFilter();
$filter->orderBy = "-createdAt";
$pager = new KalturaFilterPager();
$pager->pageSize = 500;
$pager->pageIndex = 1;
$accessProfiles = $client->accessControlProfile->listAction($filter, $pager)->objects;
?>
<div id="paid"><input type="checkbox" id="payCheck" <?php echo $payCheck; ?> > Paid Content</div>
<div id="fillDiv" style="height: 180px"></div>
<div id="payFields" style="display:none; height: 180px;">
	<div id="priceDiv" class="payFieldClass">Price: <input type="text" id="entryPrice" value="<?php echo $price; ?>"></div>
	<div id="taxDiv" class="payFieldClass">Tax: <input type="text" id="entryTax" value="<?php echo $taxPercent; ?>"> %</div>
	<div id="currencyDiv" class="payFieldClass">Currency: 
		<select data-placeholder="Choose a Currency" id="currencyChoice" class="czntags" style="width:450px;" tabindex="2">
			<?php 
			foreach($currencyCodes as $code)
				echo '<option value="'.$code.'"'.($code == $currency ? 'selected' : '').'>'.$code.'</option>';
			?>
		</select>		
	</div>
	<div id="accessDiv" class="payFieldClass">Choose an Access Control Profile: 
		<select data-placeholder="Choose an Access Control Profile" id="accessChoice" class="czntags" style="width:450px;" tabindex="2">
			<?php 
			foreach($accessProfiles as $profile)
				echo '<option value="'.$profile->id.'"'.($profile->id == $accessId ? 'selected' : '').'>'.$profile->id.': '.$profile->name.'</option>';
			?>
		</select>
	</div>
</div>
<button id="submitEntry" type="button" class="submitEntry" onclick="submitEntry()">Submit</button>
<img src="lib/loader.gif" id="submitEntryPriceLoader" style="display: none;">