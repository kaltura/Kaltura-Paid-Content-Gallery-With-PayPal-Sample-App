<div class="accordian">
	<ul>
		<li>Apply Price to Individual Entries</li>
		<li>
			<div class="searchDiv">
				<p>Search by name, description, or tags:</p>
				<input type="text" id="searchBar" autofocus="autofocus">
				<button id="searchButton" class="searchButtonClass" type="button" onclick="showEntries()">Search</button>
				<button id="showButton" type="button" onclick="showEntries(1, '')">Show All</button>
			</div>
			<div id="entriesGallery"></div>
			<img src="lib/loadBar.gif" id="entryLoadBar">
			<div id="entryFields" style="display: none;"></div>
		</li>
		<li>Apply Price to Categories</li>
		<li>
			<div class="searchDiv" style="margin-bottom: 8px;">
				<p>Search by name, description, or tags:</p>
				<input type="text" id="catSearchBar" autofocus="autofocus">
				<button id="searchCatButton" class="searchButtonClass" type="button" onclick="showCategories()">Search</button>
				<button id="showCatButton" type="button" onclick="showCategories(1, '')">Show All</button>
			</div>
			<select id="categoriesSelect" data-placeholder="Choose a Category" style="width:500px;" size="20" class="box"></select>
			<img src="lib/loadBar.gif" style="display: none;" id="categoryLoadBar">
			<div id="leftCategoryCol" style="width:350px;float:left;">
				<div id="categoryFields" style="display: none;"></div>
			</div>
			<div style="clear:both;"></div>		
		</li>
	</ul>
</div>
<script type="text/javascript" src="lib/jMenu.js"></script>