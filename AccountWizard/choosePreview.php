<script type="text/javascript">
	$(document).ready(function() {
		//Clear the name field of the default value when clicked
		$('#accessName').focus(function() {
			if($(this).val() == 'Enter a name for the Access Control Profile')
				$(this).val('');
		});
		//If name field is empty, replace with default value
		$('#accessName').blur(function() {
			if($(this).val() == '')
				$(this).val('Enter a name for the Access Control Profile');
		});
		//Only allow numbers in the time field
		$("#previewTime").numeric({ negative: false });
	});

	//Toggle preview on/off
	$("#previewCheck").click(function() {
		if ($(this).is(":checked")) {
			$("#previewFields").show();
			$("#previewFill").hide();
		}
		else {
			$("#previewFields").hide();
			$("#previewFill").show();
		}
	});

	//Submits the selected options
	function submitSetup() {
		//If no preview time is entered, do not allow the user to proceed
		if($('#previewTime').val() == '' && $('#previewFields').css('display') != 'none') {
			$('#previewTime').focus();
		}
		else {
			$('#previewButton').hide();
			$('#submitPreviewButton').show();
			$.ajax({
				type: "POST",
				url: "setupAccount.php",
				data: {session: kalturaSession, partnerId: partnerId, name: $('#accessName').val(), preview: $('#previewCheck').is(':checked'), time: $('#previewTime').val()}
			}).done(function(msg) {
				$('#submitPreviewButton').hide();
				$('#setupAccessProfile').hide();
				$('#loginForm').animate({height: "256px", marginTop: "-130px"}, 200, function() {
					$('#loader').hide();
					$('#buttons').show();
				});
				$('#description').text("First setup your account for use with the tools. Create a PayPal ready player and setup the media that you would like to be paid content. Finally, generate a configuration file for the gallery.");
			});
		}
	}
</script>
<div id="previewCheckDiv" style="padding-top: 5px;">
	<div><input type="checkbox" id="previewCheck"> Enable preview</div>
</div>
<div id="nameDiv">
	<input type="text" id="accessName" value="Enter a name for the Access Control Profile" />
</div>
<div id="previewFill" style="height: 43px;"></div>
<div id="previewFields" style="display: none;">
	<div id="timeDiv">
		<input type="text" id="previewTime" size="10"> seconds
	</div>
</div>
<button id="previewButton" type="button" class="previewButton" onclick="submitSetup()">Submit</button>
<img src="lib/loader.gif" id="submitPreviewButton" style="display: none;">