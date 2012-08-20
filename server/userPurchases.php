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
			$(".userthumblink").click(function () {
				$('#purchaseWindow').hide();
				$('#purchaseWindow').html('');
				if(entryId != 0)
					entryId.children('#play').hide();
				entryId = $(this);
				entryId.children('#play').show();
				currentEntry = $(this).attr('rel');
				checkAccess(currentEntry, $(this).attr('cats'));
				var arr = $('.entriesDiv').children().children('.thumblink');
				for(var i = 0; i < arr.length; ++i) {
					if($(arr[i]).attr('rel') == currentEntry) {
						entryId = $(arr[i]);
						entryId.children('#play').hide();
						break;
					}
				}
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
			$('.userCategoryLink').click(function() {
				$('#searchBar').val('');
				if(categoryId != 0)
					categoryId.children('.categoryName').css('background', 'white');
				currentCategory = $(this).attr('rel');
				$('#searchText').text('Search "' + $(this).children().attr('title') + '" by name, description, or tags: ');
				showEntries(1, '', currentCategory);
				var arr = $('#categoryList').children('.categoryLink');
				for(var i = 0; i < arr.length; ++i) {
					if($(arr[i]).attr('rel') == currentCategory) {
						categoryId = $(arr[i]).children();
						categoryId.children('.categoryName').css('background', '#FFF500');
						break;
					}
				}
				$.colorbox.close();
			});
		});
	}

	function userCatPurchasePagerClicked(pageNumber, search) {
		showPurchasedCategories(pageNumber, search);
	}
</script>