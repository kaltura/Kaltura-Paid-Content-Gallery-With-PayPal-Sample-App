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
		if ($('#paidCheck').is(":checked")) {
			$("#payFields").show();
			$("#filler").hide();
		}
		else {
			$("#payFields").hide();
			$("#filler").show();
		}
		
		jQuery('.czntags').chosen({search_contains: true});
		
		// set the price and tax fields to positive floats only
		$("#priceField").numeric({ negative: false });
		$("#taxField").numeric({ negative: false });
		// limit the range of the tax field to 0-100
		$("#taxField").keyup(function (event){
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
		if(isNaN(val)) return 0;
		return val;
	}

	//Toggle pay options on/off
	$("#paidCheck").click(function() {
		if ($(this).is(":checked")) {
			$("#payFields").show();
			$("#filler").hide();
		}
		else {
			$("#payFields").hide();
			$("#filler").show();
		}
	});

	//Submits the category to either be granted paid or free status
	function submitCategory() {
		$('#submitCategory').hide();
		$('#submitCategoryPriceLoader').show();
		checked = ($('#paidCheck').is(":checked"));
		priceVal = parseFloat($('#priceField').val());
		taxVal = validateRange(parseFloat($('#taxField').val()));
		currencyVal = $.trim($('#currencySelect').val());
		accessVal = $.trim($('#accessChoice').val());
		$.ajax({
			type: "POST",
			url: "submitCategory.php",
			data: {	session: "<?php echo $_REQUEST['session']; ?>", 
					partnerId: "<?php echo $_REQUEST['partnerId']; ?>", 
					categoryId: "<?php echo $_REQUEST['categoryId']; ?>",
					isChecked: checked, 
					price: priceVal,
					tax: taxVal,
					currency: currencyVal,
					access: accessVal
				  }
		}).done(function(msg) {
			if (msg != 'success') {
				// something went bad, show the error message:
				$('.formelements').hide();
				$('#filler').show();
				$('#filler').html(msg);
			} else {
				// saved, close the lightbox
				$.colorbox.close();
			}
		});
	}
</script>
<?php
$category = $client->category->get($_REQUEST['categoryId']);
echo '<h2 id="categoryName" style="font-weight: bold;">'.$category->name.' ('.$category->id.') </h2>';
$paidCheck = "";
$pager = new KalturaFilterPager();
$pageSize = 50;
$pager->pageSize = $pageSize;
$metadataFilter = new KalturaMetadataFilter();
$metadataFilter->objectIdEqual = $category->id;
$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::CATEGORY;
$metadataPlugin = KalturaMetadataClientPlugin::get($client);
$metaResults = $metadataPlugin->metadata->listAction($metadataFilter, $pager)->objects;
$paid = '';
$price = '';
$taxPercent = '';
$currency = '';
//Retrives the metadata information if it already exists for the category
foreach($metaResults as $metaResult) {
	$metadataProfile = $client->metadataProfile->get($metaResult->metadataProfileId);
	if($metadataProfile->name == 'PayPal (Categories)') {
		$xml = simplexml_load_string($metaResult->xml);
		$paid = strtolower($xml->Paid);
		$price = (float) $xml->Price;
		$taxPercent = (float) $xml->TaxPercent; //This number should be a percent (0-100), does not have to be a whole number
		$currency = (string) $xml ->CurrencyCode; //This should follow the ISO 4217 standard
		break;
	}
}
$display = 'display: none,';
$displayFiller = "";
if($paid == 'true') {
	$paidCheck = "checked";
	$display = "";
	$displayFiller = 'display: none,';
}
$currencyCodes = array("USD","AUD","CAD","CZK","DKK","EUR","HKD","HUF","JPY","NOK","NZD","PLN","GBP","SGD","SEK","CHF");
$filter = new KalturaAccessControlProfileFilter();
$filter->orderBy = '-createdAt';
$pager = new KalturaFilterPager();
$pager->pageSize = 500;
$pager->pageIndex = 1;
$accessProfiles = $client->accessControlProfile->listAction($filter, $pager)->objects;
?>
<div id="paid" class="formelements">
	<input type="checkbox" id="paidCheck" <?php echo $paidCheck; ?> /> Paid Content
</div>
<div id="filler" style="height: 250px"></div>
<div id="payFields" style="height: 250px" class="formelements">
	<div id="priceDiv" class="payFieldClass">
		Price: <input type="text" id="priceField" value="<?php echo $price; ?>" />
	</div>
	<div id="taxDiv" class="payFieldClass">
		Tax: <input type="text" id="taxField" value="<?php echo $taxPercent; ?>" />%
	</div>
	<div id="currencyDiv" class="payFieldClass">Currency: 
		<select data-placeholder="Choose currency Country Code" id="currencySelect" class="czntags" style="width:450px;" tabindex="2">
			<?php
			foreach($currencyCodes as $code) {
				echo '<option value="'.$code.'"'.($code == $currency ? 'selected' : '').'>'.$code.'</option>';
			}
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
<button id="submitCategory" type="button" class="submitEntry" onclick="submitCategory()">Submit</button>
<img src="lib/loader.gif" id="submitCategoryPriceLoader" style="display: none;">