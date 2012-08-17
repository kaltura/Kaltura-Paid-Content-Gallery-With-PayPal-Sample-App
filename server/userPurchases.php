<div class='accordian'>
	<ul style="height: 460px;">
		<li>Purchased Entries</li>
		<li id='entryAccordian'>
			<div class="searchDiv">
				<p>Search by name, description, or tags:</p>
				<input type="text" id="userSearchBar" autofocus="autofocus">
				<button id="userSearchButton" type="button" onclick="showPurchasedEntries()">Search</button>
				<button id="userShowButton" type="button" onclick="showPurchasedEntries(1, '')">Show All</button>
			</div>
			<div id="purchasedEntryGallery"></div>
		</li>
		<li>Purchased Channels</li>
		<li id='catAccordian'>
			<div class="searchDiv" style="margin-bottom: 8px;">
				<p>Search by name, description, or tags:</p>
				<input type="text" id="userCatSearchBar" autofocus="autofocus">
				<button id="userCatSearchButton" type="button" onclick="showPurchasedCategories()">Search</button>
				<button id="userCatShowButton" type="button" onclick="showPurchasedCategories(1, '')">Show All</button>
			</div>
			<div id="purchasedCatGallery"></div>
		</li>
	</ul>
</div>
<script type="text/javascript" src="client/jMenu.js"></script>
<link href="client/jMenu.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript">
	$(document).ready(function() {
		showPurchasedEntries(1, '');
		showPurchasedCategories(1, '');
		$('#userSearchBar').keyup(function(event) {
			if(event.which == 13)
				showPurchasedEntries();
		});
		$('#userCatSearchBar').keyup(function(event) {
			if(event.which == 13)
				showPurchasedCategories();
		});
	});

	function showPurchasedEntries(page, terms) {
		$('#entryAccordian').mask('Loading...');
		if(terms == '')
			$('#userSearchBar').val('');
		$.ajax({
			type: "POST",
			url: "server/reloadPurchasedEntries.php",
			data: {pagenum: page, search: $('#userSearchBar').val()}
		}).done(function(msg) {
			$('#entryAccordian').unmask();
			$('#purchasedEntryGallery').html(msg);
			$(".thumblink").click(function () {
				$('#purchaseWindow').hide();
				$('#purchaseWindow').html('');
				currentEntry = $(this).attr('rel');
				checkAccess($(this).attr('rel'), $(this).attr('cats'));
				$.colorbox.close();
		    });
		});
	}

	function userPurchasePagerClicked(pageNumber, search) {
		showPurchasedEntries(pageNumber, search);
	}

	function showPurchasedCategories(page, terms) {
		$('#catAccordian').mask('Loading...');
		if(terms == '')
			$('#userCatSearchBar').val('');
		$.ajax({
			type: "POST",
			url: "server/reloadPurchasedCategories.php",
			data: {pagenum: page, search: $('#userCatSearchBar').val()}
		}).done(function(msg) {
			$('#catAccordian').unmask();
			$('#purchasedCatGallery').html(msg);
			$('.categoryLink').click(function() {
				$('#searchBar').val('');
				if(categoryId != 0)
					categoryId.css('borderColor', 'black');
				currentCategory = $(this).attr('rel');
				showEntries(1, '', currentCategory);
				$.colorbox.close();
			});
		});
	}

	function userCatPurchasePagerClicked(pageNumber, search) {
		showPurchasedCategories(pageNumber, search);
	}
</script>