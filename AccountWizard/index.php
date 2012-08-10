<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>PayPal Account Wizard</title>
	<!-- Style Includes -->
	<link href="style.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="lib/chosen/chosen.css" rel="stylesheet" />
	<link rel="stylesheet" href="lib/colorbox/example4/colorbox.css" />
	<link href="lib/jQueryUI/jquery-ui-1.8.18.custom.css" rel="stylesheet" type="text/css" />
	<!-- Script Includes -->
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="lib/chosen/chosen.jquery.js"></script>
	<script src="lib/jquery.json-2.3.min.js"></script>
	<script src="lib/colorbox/colorbox/jquery.colorbox.js"></script>
	<script src="lib/jquery.numeric.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js" type="text/javascript"></script>	
	<script src="lib/jquery.fileDownload/jquery.fileDownload.js" type="text/javascript"></script>
	<!-- Page Scripts -->
	<script type="text/javascript">
		var kalturaSession = "";
		var partnerId = 0;
		
		//Validation written with help from http://yensdesign.com/tutorials/validateform/validation.js
		//Makes sure that the email/partner ID/password are valid for login submission
		function validEmail(input) {
			var filter = /^[a-zA-Z0-9]+[a-zA-Z0-9_.-]+[a-zA-Z0-9_-]+@[a-zA-Z0-9]+[a-zA-Z0-9.-]+[a-zA-Z0-9]+.[a-z]{2,4}$/;
			if(!filter.test(input.value)) {
				input.setCustomValidity("Invalid email");
				return false;
			}
			else {
				input.setCustomValidity('');
				return true;
			}
		}

		function validPassword(input) {
			if(input.value == '') {
				input.setCustomValidity("Please enter a password");
				return false;
			}
			else {
				input.setCustomValidity('');
				return true;
			}
		}

		//Calls getSession.php to actually sign into the Kaltura API and generate a session key
		function loginSubmit() {
			$('#loginButton').hide();
			$('#loginLoader').show();
			$.ajax({
				type: "POST",
				url: "getSession.php",
				data: {email: $('#email').val(), partnerId: 0, password: $('#password').val()}
			}).done(function(msg) {
				$('#loginLoader').hide();
				if(msg == "loginfail") {
					alert("Invalid username/password");
					$('#loginButton').show();
				}
				else {
					response = $.evalJSON(msg);
					if(response[0] == 1) {
						kalturaSession = response[1];
						partnerId = response[2];
						$('#userLogin').hide();
						$('#loginButton').hide();
						$('#loginFooter').hide();
						$('#loginForm').animate({height: "256px", marginTop: "-130px"}, 400);
						$('#description').text("First setup your account for use with the tools. Create a PayPal ready player and setup the media that you would like to be paid content. Finally, generate a configuration file for the gallery.");
						$('#page').slideDown();
					}
					else
						partnerLogin(response);
				}
			});
		}

		//This lets the user select a partner to log into
		//This is only displayed if there is more than one partner on an account
		function partnerLogin(response) {
			$('#email').attr("readonly", "readonly");
			$('#password').attr("readonly", "readonly");
			$('#loginButton').hide();
			$('#loginFooter').hide();
			$.ajax({
				type: "POST",
				url: "partnerSelect.php",
				data: {response: response}
			}).done(function(msg) {
				$('#description').text("Choose the partner ID you want to use.");
				$('#partnerLogin').html(msg);
				$('#loginForm').animate({height: "432px", marginTop: "-216px"}, 400, function() {
					$('#partnerLogin').slideDown();
				});
				$('#email').keyup(function(event) {
					if(event.which == 13)
						partnerSubmit();
				});
				$('#password').keyup(function(event) {
					if(event.which == 13)
						partnerSubmit();
				});
				jQuery('.czntags').chosen({search_contains: true});
			});
		}

		//Submits the partner for login
		function partnerSubmit() {
			$('#sumbitPartner').hide();
			$('#partnerLoader').show();
			$.ajax({
				type: "POST",
				url: "getSession.php",
				data: {email: $('#email').val(), partnerId: $('#partnerChoice').val(), password: $('#password').val()}
			}).done(function(msg) {
				$('#partnerLoader').hide();
				if(msg == "loginfail") {
					alert("Invalid username/password");
					$('#loginButton').show();
				}
				else if(msg == 'idfail') {
					alert("Invalid Partner ID");
					$('#loginButton').show();
				}
				else {
					response = $.evalJSON(msg);
					kalturaSession = response[1];
					partnerId = $('#partnerChoice').val();
					$('#userLogin').hide();
					$('#loginButton').hide();
					$('#loginFooter').hide();
					$('#loginForm').animate({height: "256px", marginTop: "-130px"}, 400);
					$('#description').text("First setup your account for use with the tools. Create a PayPal ready player and setup the media that you would like to be paid content. Finally, generate a configuration file for the gallery.");
					$('#page').slideDown();
				}
			});
		}

		//Displays the window that sets up the account with the
		//necessary metadata profiles and creates an access control profile
		function setupAccount() {
			$('#buttons').hide();
			$.ajax({
				type: "POST",
				url: "choosePreview.php",
				data: {session: kalturaSession, partnerId: partnerId}
			}).done(function(msg) {
				$('#loginForm').animate({height: "225px", marginTop: "-112.5px"}, 200, function() {
					$('#setupAccessProfile').show();
					$('#setupAccessProfile').html(msg);
				});
				$('#description').text("Configure the access control profile that your paid content will use.");
			});
		}

		//Displays the menu for creating a new paid content player based on a variety of options
		function choosePlayer() {
			$('#buttons').hide();
			$('#loader').show();
			$.ajax({
				type: "POST",
				url: "choosePlayer.php",
				data: {session: kalturaSession, partnerId: partnerId}
			}).done(function(msg) {
				$('#loader').hide();
				$('#players').html(msg);
				$('#loginForm').animate({height: "330px", width: "500px", margin: "-165px 0 0 -250px"}, 400, function() {
					$('#players').slideDown(300);
					jQuery('.czntags').chosen({search_contains: true});
				});
				$('#description').text("Choose a player to build the PayPal functions on top of. Then select all the properties that you would like it to possess.");
			});
		}

		//Submits the player to apply the new settings
		function createPlayer() {
			var checks = new Array();
			for(var i = 0; i < 4; ++i) {
				checks[i] = $('#check' + i).is(':checked');
			}
			checks[4] = $('#newCheck').is(':checked');
			console.log(checks);
			$('#submitPlayer').hide();
			$('#submitLoader').show();
			$.ajax({
				type: "POST",
				url: "createPlayer.php",
				data: {session: kalturaSession, partnerId: partnerId, player: $('#playerChoice').val(), choices: checks, buyFunction: $('#functionName').val()}
			}).done(function(msg) {
				if(msg == 'success') {
					$('#players').hide();
					$('#loginForm').animate({height: "256px", width: "334px", margin: "-130px 0 0 -166px"}, 300, function() {
						$('#buttons').slideDown();
					});
					$('#description').text("First setup your account for use with the tools. Create a PayPal ready player and setup the media that you would like to be paid content. Finally, generate a configuration file for the gallery.");
				}
				else
					alert("Failed to create player");
			});
		}

		//Displays the window for applying prices to individual entries or categories
		function choosePricing() {
			showEntries(1, '');
			showCategories(1, '');
			$('#buttons').hide();
			$('#loader').show();
			$.ajax({
				type: "POST",
				url: "choosePricing.php",
				data: {session: kalturaSession, partnerId: partnerId}
			}).done(function(msg) {
				$('#pricing').html(msg);
				$('#loader').hide();
				$('#entriesGallery').show();
				$('#loginForm').animate({height: "570px", width: "800px", margin: "-285px 0 0 -400px"}, 400, function() {
					$('#pricing').slideDown(400, function() {
						$('#loginFooter').show();
					});
					$('#doneButton').show();
				});
				$('#description').text("Select either the entry or the category tab. Then click the content that you would like to charge for.");
				$('#searchBar').keyup(function(event) {
				if(event.which == 13)
					showEntries();
				});
				$('#catSearchBar').keyup(function(event) {
					if(event.which == 13)
						showCategories();
				});
			});
		}

		//Returns to the main menu from the pricing window
		function finishPricing() {
			$('#loginFooter').hide();
			$('#pricing').hide();
			$('#loginForm').animate({height: "256px", width: "334px", margin: "-136px 0 0 -166px"}, 400, function() {
				$('#buttons').slideDown();
			});
			$('#description').text("First setup your account for use with the tools. Create a PayPal ready player and setup the media that you would like to be paid content. Finally, generate a configuration file for the gallery.");
		}

		//Entries pager handler - Responds to the page number index that is clicked
		function pagerClicked (pageNumber, search)	{
			currentPage = pageNumber;
			showEntries(pageNumber, search);
		}

		//Show all the entries for a given page based on search terms or lack thereof
		function showEntries(page, terms) {
			if(terms == "")
				$('#searchBar').val('');
			$('#entryLoadBar').show();
			$('#entriesGallery').hide();
			$('#entryFields').hide();
			var search = $('#searchBar').val();
			if(search == null)
				search = '';
			$.ajax({
				type: "POST",
				url: "reloadEntries.php",
				data: {pagenum: page, search: search, session: kalturaSession, partnerId: partnerId}
			}).done(function(msg) {
				$('#entryLoadBar').hide();
				$('#entriesGallery').html(msg);
				$('#entriesGallery').show();
				$(".thumblink").click(function () {
					$(".thumblink").colorbox({width:"50%", href:"entryElements.php?session="+kalturaSession+"&partnerId="+partnerId+"&entryId="+$(this).attr('rel')});
			    });
			});
		}
		
		//Categories pager handler - Responds to the page number index that is clicked
		function pagerCatClicked (pageNumber, search)	{
			currentPage = pageNumber;
			showCategories(pageNumber, search);
		}
		
		//Show all the categories for a given page based on search terms or lack thereof
		function showCategories(page, terms) {
			if(terms == "")
				$('#catSearchBar').val('');
			$('#categoryLoadBar').show();
			$('#categoriesSelect').hide();
			$('#categoryFields').hide();
			var search = $('#catSearchBar').val();
			if(search == null)
				search = '';
			$.ajax({
				type: "POST",
				url: "reloadCategories.php",
				data: {pagenum: page, search: search, session: kalturaSession, partnerId: partnerId}
			}).done(function(msg) {
				$('#categoryLoadBar').hide();
				$('#categoriesSelect').html(msg);
				$('#categoriesSelect').show();
				$(".categoryOption").click(function () {
					$(".categoryOption").colorbox({width:"50%", href:"categoryElements.php?session="+kalturaSession+"&partnerId="+partnerId+"&categoryId="+$(this).attr('value')});
			    });
			});
		}

		//Displays the window for generating a gallery configuration file
		function generateConfig() {
			$('#buttons').hide();
			$('#loader').show();
			$.ajax({
				type: "POST",
				url: "generateConfig.php",
				data: {session: kalturaSession, partnerId: partnerId}
			}).done(function(msg) {
				$('#loader').hide();
				$('#loginForm').animate({height: "292px", width: "500px", marginTop: "-146px", marginLeft: "-250px"}, 200, function() {
					$('#config').html(msg);
					$('#config').slideDown(400);
				});
				$('#description').text("Choose the options for your kaltura configuration file.");
			});
		}

		//Submits the options for generating a configuration file
		function downloadConfig() {
			var downloadFile = "downloadConfig.php?session=" + kalturaSession + "&partnerId=" + partnerId + "&default=" + $('#selectDefaultPlayer').val() + "&paid=" + $('#selectPaidPlayer').val();
			var $preparingFileModal = $("#preparing-file-modal");
	        $preparingFileModal.dialog({ modal: true });
	        $.fileDownload(downloadFile, {
	            successCallback: function(url) {
	                $preparingFileModal.dialog('close');
	                $('#config').hide();
	                $('#loginForm').animate({height: "256px", width: "334px", margin: "-136px 0 0 -166px"}, 400, function() {
	    				$('#buttons').slideDown();
	    			});
	    			$('#description').text("First setup your account for use with the tools. Create a PayPal ready player and setup the media that you would like to be paid content. Finally, generate a configuration file for the gallery.");
	            },
	            failCallback: function(responseHtml, url) {
	 
	                $preparingFileModal.dialog('close');
	                $("#error-modal").dialog({ modal: true });
	            }
	        });
		}
	</script>
</head>
<body>
	<form method="post" id="loginForm" action="javascript:loginSubmit();" class="box login">
		<header style="text-align:center;">
			<label><h1 style="font-weight:bold;">PayPal Account Wizard</h1></label>
			<p id="description" style="padding-bottom:10px;">Login to your Kaltura account to proceed.</p>
		</header>
		<div id="userLogin">
			<fieldset class="boxBody">
				<label>Email</label>
				<input type="text" tabindex="1" id="email" oninput="validEmail(this)" autofocus="autofocus" required>
				<label>Password</label>
				<input type="password" tabindex="1" id="password" oninput="validPassword(this)" required>
				<div id="partnerLogin" style="display: none;"></div>
			</fieldset>
		</div>
		<div id="page" class="boxBody" style="display: none;">
			<div id="buttons">
				<button id="setupButton" type="button" class="setup" onclick="setupAccount()">Setup Account</button>
				<button id="addPlayer" type="button" class="setup" onclick="choosePlayer()" style="margin-top: 4px;">Add PayPal Player</button>
				<button id="setPrices" type="button" class="setup" onclick="choosePricing()" style="margin-top: 4px;">Setup Media Pricing</button>
				<button id="generateButton" type="button" class="setup" onclick="generateConfig()" style="margin-top: 4px;">Generate Config File</button>
			</div>
			<div id="setupAccessProfile" style="display: none;"></div>
			<div id="players" style="display: none;"></div>
			<div id="pricing" style="display: none;"></div>
			<div id="config" style="display: none;"></div>
			<img src="lib/loader.gif" id="loader" style="display: none;">
		</div>
		<footer id="loginFooter">
			<input type="submit" class="btnLogin" value="Login" id="loginButton" tabindex="4">
			<img src="lib/loginLoader.gif" id="loginLoader" style="display: none; margin: 9px 130px;">
			<button id="doneButton" type="button" class="doneButton" onclick="finishPricing()" style="display: none;">Finish</button>
		</footer>
	</form>
	<div style='display:none'>
		<div id='inline_content' style='padding:10px; background:#fff;'>
		<p><strong>This content comes from a hidden element on this page.</strong></p>
		<p>The inline option preserves bound JavaScript events and changes, and it puts the content back where it came from when it is closed.</p>
		<p><a id="click" href="#" style='padding:5px; background:#ccc;'>Click me, it will be preserved!</a></p>
		
		<p><strong>If you try to open a new ColorBox while it is already open, it will update itself with the new content.</strong></p>
		<p>Updating Content Example:<br />
		<a class="ajax" href="../content/flash.html">Click here to load new content</a></p>
		</div>
	</div>
		<div id="preparing-file-modal" title="Preparing report..." style="display: none;">
	    We are preparing your report, please wait...
	     
	    <!--Throw what you'd like for a progress indicator below-->
	    <div class="ui-progressbar-value ui-corner-left ui-corner-right" style="width: 100%; height:22px; margin-top: 20px;"></div>
	</div>
	<div id="error-modal" title="Error" style="display: none;">
	    There was a problem generating your report, please try again.
	</div>
</body>
</html>