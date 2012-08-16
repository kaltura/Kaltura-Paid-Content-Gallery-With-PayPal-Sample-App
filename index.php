<!DOCTYPE HTML>
<?php
require_once('server/kalturaConfig.php');
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Kaltura Paid-Content Gallery Sample App</title>
	<!-- Style Includes -->
	<link href="client/style.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="client/loadmask/jquery.loadmask.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="client/colorbox/example4/colorbox.css" />
	<!-- Script Includes -->
	<script src="https://www.paypalobjects.com/js/external/dg.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="client/pptransact.js"></script>
	<script src="http://html5.kaltura.org/js"></script>
	<script type="text/javascript" src="client/loadmask/jquery.loadmask.min.js"></script>
	<script src="client/colorbox/colorbox/jquery.colorbox.js"></script>
	<!-- Page Scripts -->
	<script>
		//are we loading the page or just calling ajax triggered by user interaction?
		var firstload = true;
		//Keeps track of the page being viewed
		var currentPage = 1;
		//Keeps track of the video being viewed
		var currentEntry = 0;
		//Keeps track of the search terms
		var currentSearch = "";
		//Keeps track of the channel being viewed
		var currentCategory = "";
		//Used to track the category link
		var categoryId = 0;

		$(document).ready(function(e) {
			if(1 == <?php
						if(ADMIN_SECRET == 'xxx' || PARTNER_ID == 000)
							echo 1;
						else
							echo 0;
					?>) {
				$('#searchButton').attr('disabled', 'disabled');
				$('#showButton').attr('disabled', 'disabled');
				$('#searchBar').attr('disabled', 'disabled');
				$('#searchBar').blur();
				$('#channels').hide();
			}
			else {
				$('#failConfig').hide();
				showPurchases();
				//When the page loads, show the available channels
				showCategories(1);
				//When the page loads, show the entries
				showEntries(1, '');
				$('#searchBar').keyup(function(event) {
					if(event.which == 13)
						showEntries();
				});
			}
		});

		//Uncomment this line to use the HTML5 player instead
		//mw.setConfig('KalturaSupport.LeadWithHTML5', true);
		
		//INITIALIZE SESSION WITH APPROPRIATE LANGUAGE
		pptransact.init('php',false);

		//Initializes the PayPal express checkout billing system
		function bill(entryId) {
			pptransact.bill({
				userId:'<?php echo $USER_ID; ?>',
				itemId:entryId,
				itemQty:'1',
				successCallback: function(ret) {
					//bill success
					savePurchase(ret);
				},
				failCallback: function() {
					//bill canceled
				}
			});
		}

		function savePurchase(ret) {
			$.ajax({
				type: "POST",
				url: "server/savePurchase.php",
				data: {id: ret}
			}).done(function(msg) {
				$('#purchaseWindow').hide();
				checkAccess(currentEntry, ret);
			});
		}

		//Verifies whether or not a video has been paid for
		var verifySyncRes = null;
		function verify(entryId) {
			verifySyncRes = false;
			$.ajax({
				type: "POST",
				async: false,
				url: "server/verifyPurchase.php",
				data: {id: entryId}
			}).done(function(msg) {
				if(msg == 'true')
					verifySyncRes = true;
			});
			//This method verifies entries using HTML5 local storage
			/*
			pptransact.verify({
				userId:'<?php echo $USER_ID; ?>',
				itemId:entryId,
				successCallback: function() {
					//verify success
					verifySyncRes = true;
				},
				failCallback: function() {
					//verify cancelled
					verifySyncRes = false;
				}
			});
			*/
			return verifySyncRes;
		}
		
		// Loads the video is a Kaltura Dynamic Player
		function loadVideo(ks,uiConfId,entryId) {
			kWidget.embed({
				'targetId': 'playerDiv',
				'wid': '_<?php echo PARTNER_ID; ?>',
				'uiconf_id' : uiConfId,
				'entry_id' : entryId,
				'width': 400,
				'height': 300,
				'flashvars':{
					'externalInterfaceDisabled' : false,
					'autoPlay' : true,
					'disableAlerts': true,
					'entryId': entryId,
					'ks': ks
				},
				'readyCallback': function( playerId ){
					window.kdp = $('#'+playerId).get(0);
					kdp.addJsListener("freePreviewEnd", 'freePreviewEndHandler');
				}
			});
		}

		//Responds to the page number index that is clicked
		function pagerClicked (pageNumber, search, category) {
			currentPage = pageNumber;
			showEntries(pageNumber, search, category);
		}

		//Show all the entries for a given page based on the channel and search terms or lack thereof
		function showEntries(page, terms, cat) {
			$('#purchaseWindow').hide();
			if(cat == "")
				currentCategory = cat;
			if(!cat)
				cat = currentCategory;
			if(terms == "")
				$('#searchBar').val('');
			currentSearch = $('#searchBar').val();
			$('body').mask("Loading...");
			$.ajax({
				type: "POST",
				url: "server/reloadEntries.php",
				data: {pagenum: page, search: $('#searchBar').val(), category: cat}
			}).done(function(msg) {
				$('#entryLoadBar').hide();
				$('body').unmask();
				$('#entryList').html(msg);
				//This is called whenever a video's thumbnail is clicked
				$(".thumblink").click(function () {
					$('#purchaseWindow').hide();
					$('#purchaseWindow').html('');
					currentEntry = $(this).attr('rel');
					checkAccess($(this).attr('rel'), $(this).attr('cats'));
					window.scrollTo(0,document.body.scrollHeight);
			    });
			    //Loads a video the first time the page loads
			    if(firstload) {
				    currentEntry = $('.thumblink:first').attr('rel');
				    checkAccess($('.thumblink:first').attr('rel'), $('.thumblink:first').attr('cats'));
					firstload = false;
			    }
			});
		}

		//Shows a list of channels that may be clicked on
		function showCategories(page) {
			$.ajax({
				type: "POST",
				url: "server/reloadCategories.php",
				data: {page: page}
			}).done(function(msg) {
				$('#categoryList').unmask();
				$('#categoryList').html(msg);
				//When a channel is clicked, all the entries in that channel are shown
				//When viewing a channel, searching will search in that channel only
				$('.categoryLink').click(function() {
					$('#searchBar').val('');
					if(categoryId != 0)
						categoryId.css('borderColor', 'black');
					categoryId = $(this).children();
					$(this).children().css('borderColor', 'blue');
					currentCategory = $(this).attr('rel');
					showEntries(1, currentSearch, currentCategory);
				});
				//Shows more channels to choose from
				$('.categoryPage').click(function() {
					$('#searchBar').val('');
					currentCategory = $(this).attr('rel');
					$('#categoryList').mask('Loading...');
					showCategories($(this).attr('rel'));
				});
			});
		}

		//Checks whether an entry is paid content or free
		//If it is in fact paid, determine if it has been bought either
		//individually or as part of a channel
		function checkAccess(id, cats) {
			var categories = cats.split(',');
			$('body').mask('Loading...');
			$.ajax({
				type: "POST",
				url: "server/inventory.php",
				data: {entryId: id}
			}).done(function(msg) {
				$('body').unmask();
				if(msg == 'false') {
					// This entry is free to watch
					$('#purchaseWindow').hide();
					loadVideo('', '<?php echo PLAYER_UICONF_ID; ?>', id);
				} else {
					var bool = false;
					for(var i = 0; i < categories.length; ++i) {
						if(categories[i] != "")
							bool = verify(categories[i]);
						if(bool) {
							$('#purchaseWindow').hide();
							$.ajax({
								type: "POST",
								url: "server/kaltura.php",
								data: {entryId: id}
							}).done(function(msg) {
								loadVideo(msg, '<?php echo PLAYER_UICONF_ID; ?>', id);
							});
							break;
						}
					}
					if(!bool) {
						bool = verify(id);
						$('#purchaseWindow').hide();
						if(bool) {
							$.ajax({
								type: "POST",
								url: "server/kaltura.php",
								data: {entryId: id}
							}).done(function(msg) {
								loadVideo(msg, '<?php echo PLAYER_UICONF_ID; ?>', id);
							});
						}
						else
							loadVideo('','<?php echo BUY_BUTTON_PLAYER_UICONF_ID; ?>', id);
					}
				}
			});
		}

		function showPurchases() {
			$.ajax({
				type: "POST",
				url: "server/reloadPurchases.php",
				data: {all: 'false'}
			}).done(function(msg) {
				if(msg == 0)
					$('#welcomeMessage').html('Welcome <?php echo $USER_ID; ?>, you have not purchased anything yet.');
				else {
					$('#welcomeMessage').html('Welcome <?php echo $USER_ID; ?>, you have previously bought the following items:');
					var response = JSON && JSON.parse(msg) || $.parseJSON(msg);
					$('#userVideos').html(response[0]);
					$('#userChannels').html(response[1]);
					//This is called whenever a video's thumbnail is clicked
					$(".thumblink").click(function () {
						$('#purchaseWindow').hide();
						$('#purchaseWindow').html('');
						currentEntry = $(this).attr('rel');
						checkAccess($(this).attr('rel'), $(this).attr('cats'));
						window.scrollTo(0,document.body.scrollHeight);
				    });
				}
			});
		}

		function showAllPurchases() {
			$.colorbox({width:"50%", href:"server/userPurchases.php?all=true"});
		}

		//This is shown when a video's free preview ends and a purchase
		//is required to continue viewing the content
		function showPurchaseWindow(entryId) {
			kdp.sendNotification('doPause');
			$('#purchaseWindow').css('top', $('#playerDiv').offset().top);
			$('#purchaseWindow').css('left', $('#playerDiv').offset().left);
			$('#purchaseWindow').css('width', parseInt($('#playerDiv').css('width')) - 24);
			$('#purchaseWindow').css('height', parseInt($('#playerDiv').css('height')) - 24);
			$.ajax({
				type: "POST",
				url: "server/payment.php",
				data: {entryId: entryId}
			}).done(function(msg) {
				$('#purchaseWindow').show();
				$('#purchaseWindow').html(msg);
			});
		}

		//The default function that is called when the buy button is clicked
		//in the KDP
		function kalturaPayPalBuyHandler (entryId) {
			showPurchaseWindow(entryId);
		}

		//This is the KDP's end of preview event handler
		function freePreviewEndHandler() {
			showPurchaseWindow(kdp.evaluate('{configProxy.flashvars.entryId}'));
		}
		
		jQuery.fn.exists = function() { return (this.length > 0); };
	</script>
</head>
<body>
	<div id="wrapper">
		<div id="failConfig" class="notep">NOTE: Make sure to generate a configuration file using the PayPal Account Wizard.</div>
		<div><img src="client/loadBar.gif" style="display: none;" id="loadBar"></div>
		<h1>Kaltura Paid-Content Gallery Sample App</h1>
		<div class="notep"><strong>This application is using a Sandbox PayPal demo account.</strong> All pay-videos are set with a 10sec free preview.<br/>To purchase, use the following credentials - user: <span class="italicbold">john_1344640136_per@kaltura.com</span> &nbsp; pass: <span class="italicbold">kaltura2012</span></div>
		<div id="userDiv">
			<div id="welcomeMessage">Welcome <?php echo $USER_ID; ?></div>
			<div id="userVideos" style="float: left;"></div>
			<div id="userChannels"></div>
			<div id="viewPurchases"></div>
		</div>
		<div class="searchDiv">
			Search by name, description, or tags: <input type="text" id="searchBar" autofocus="autofocus">
			<button id="searchButton" class="searchButtonClass" type="button" onclick="showEntries()">Search</button>
			<button id="showButton" type="button" onclick="showEntries(1, '', '')">Show All</button>
		</div>
	</div>
	<div class="capsule">
		<img src="client/loadBar.gif" style="display: none;" id="entryLoadBar">
		<div id="channels">
			<h2 style="margin-top: 0px;">Channels</h2>
			<div id="categoryList"></div>
		</div>	
		<div id="entryList"></div>
		<div id="playerDivContainer"><div id="playerDiv"></div></div>
		<div id="clearDiv" style="clear:both"></div>
		<div id="adminDiv">
			<button id="adminButton" type="button" onclick="location.href='AccountWizard'">Admin Account Wizard</button>
		</div>
	</div>
	<div id="purchaseWindow"></div>
</body>
</html>