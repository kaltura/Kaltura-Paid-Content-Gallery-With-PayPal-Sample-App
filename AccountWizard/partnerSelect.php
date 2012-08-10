<?php
//This script displays the selector for choosing a partner on the account
	$response = $_REQUEST['response'];
?>
<label style="margin-left: 82px;">Select a Partner ID</label>
<div id="partnerSelect">
	<select data-placeholder="Choose a Partner ID" id="partnerChoice" class="czntags" style="width:250px;" tabindex="2">
		<?php
		for($i = 1; $i < $response[0] + 1; ++$i) {
			echo '<option value="'.$response[$i][0].'">'.$response[$i][0].': '.$response[$i][1].'</option>';
		}
		?>
	</select>
</div>
<div id="partnerButton">
	<button id="sumbitPartner" type="button" class="submitPartner" onclick="partnerSubmit()">Login</button>
</div>
<img src="lib/loginLoader.gif" id="partnerLoader" style="display: none;">