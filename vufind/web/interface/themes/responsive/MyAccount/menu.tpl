{strip}
{if $user != false}
	{* Setup the accoridon *}
	<div id="home-account-links" class="sidebar-links row"{if $displaySidebarMenu} style="display: none"{/if}>
		<div class="panel-group accordion" id="account-link-accordion">
			{* My Account *}
			<a id="account-menu"></a>
			{if $module == 'MyAccount' || $module == 'MyResearch' || ($module == 'Search' && $action == 'Home') || ($module == 'MaterialsRequest' && $action == 'MyRequests')}
				{assign var="curSection" value=true}
			{else}
				{assign var="curSection" value=false}
			{/if}

		<div class="panel{if $displaySidebarMenu || $curSection} active{/if}">
				{* With SidebarMenu on, we should always keep the MyAccount Panel open. *}

				{* Clickable header for my account section *}
				<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myAccountPanel">
					<div class="panel-heading">
						<div class="panel-title">
							{*MY ACCOUNT*}
							{translate text="My Account"}
						</div>
					</div>
				</a>
				{*  This content is duplicated in MyAccount/mobilePageHeader.tpl; Update any changes there as well *}
				<div id="myAccountPanel" class="panel-collapse collapse{if  $displaySidebarMenu || $curSection} in{/if}">
					<div class="panel-body">
						{assign var="totalFines" value=$user->getTotalFines()}
						{if $totalFines > 0 || ($showExpirationWarnings && $user->expireClose)}
							<div id="myAccountFines">
								{if $totalFines > 0}
									{if $showEcommerceLink && $totalFines > $minimumFineAmount}
										<div class="myAccountLink">
											<a href="{$ecommerceLink}" target="_blank"{if $showRefreshAccountButton} onclick="VuFind.Account.ajaxLightbox('{$path}/AJAX/JSON?method=getPayFinesAfterAction')"{/if}  style="color:red; font-weight:bold;">
												Your account{if count($user->getLinkedUsers())>0}s have{else} has{/if} ${$totalFines|number_format:2} in fines.
											</a>
										</div>
										<div class="myAccountLink">
											<a href="{$ecommerceLink}" target="_blank"{if $showRefreshAccountButton} onclick="VuFind.Account.ajaxLightbox('{$path}/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
												{if $payFinesLinkText}{$payFinesLinkText}{else}Pay Fines Online{/if}
											</a>
										</div>
									{else}
										<div class="myAccountLink" title="Please contact your local library to pay fines or charges." style="color:red; font-weight:bold;" onclick="alert('Please contact your local library to pay fines or charges.')">
											Your account{if count($user->getLinkedUsers())>0}s have{else} has{/if} ${$totalFines|number_format:2} in fines.
										</div>
									{/if}
								{/if}

								{if $showExpirationWarnings && $user->expireClose}
									<div class="myAccountLink">
										<a class="alignright" title="Please contact your local library to have your library card renewed." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">
											{if $user->expired}
												Your library card expired on {$user->expires}.
											{else}
												Your library card will expire on {$user->expires}.
											{/if}
										</a>
							</div>
								{/if}
							</div>
							<hr class="menu">
						{/if}

						<div class="myAccountLink{if $action=="CheckedOut"} active{/if}">
							<a href="{$path}/MyAccount/CheckedOut" id="checkedOut">
								Checked Out Titles <span class="badge">{$user->getNumCheckedOutTotal()}</span>
							</a>
						</div>
						<div class="myAccountLink{if $action=="Holds"} active{/if}">
							<a href="{$path}/MyAccount/Holds" id="holds">
								Titles On Hold <span class="badge">{$user->getNumHoldsTotal()}</span>
								{if $user->getNumHoldsAvailableTotal() && $user->getNumHoldsAvailableTotal() > 0}
									&nbsp;<span class="label label-success">{$user->getNumHoldsAvailableTotal()} ready for pick up</span>
								{/if}
							</a>
						</div>

						{if $enableMaterialsBooking}
						<div class="myAccountLink{if $action=="Bookings"} active{/if}">
							<a href="{$path}/MyAccount/Bookings" id="bookings">
								Scheduled Items  <span class="badge">{$user->getNumBookingsTotal()}</span>
							</a>
						</div>
						{/if}
						<div class="myAccountLink{if $action=="ReadingHistory"} active{/if}">
							<a href="{$path}/MyAccount/ReadingHistory">
								Reading History {if $user->readingHistorySize}<span class="badge">{$user->readingHistorySize}</span>{/if}
							</a>
						</div>

						{if $showFines}
							<div class="myAccountLink{if $action=="Fines"} active{/if}" title="Fines and account messages"><a href="{$path}/MyAccount/Fines">{translate text='Fines and Messages'}</a></div>
						{/if}
						{if $enableMaterialsRequest}
							<div class="myAccountLink{if $pageTemplate=="myMaterialRequests.tpl"} active{/if}" title="Materials Requests">
								<a href="{$path}/MaterialsRequest/MyRequests">{translate text='Materials Requests'} <span class="badge">{$user->numMaterialsRequests}</span></a>
							</div>
						{/if}
						{if $showRatings}
							<hr class="menu">
							<div class="myAccountLink{if $action=="MyRatings"} active{/if}"><a href="{$path}/MyAccount/MyRatings">{translate text='Titles You Rated'}</a></div>
							{if $user->disableRecommendations == 0}
								<div class="myAccountLink{if $action=="SuggestedTitles"} active{/if}"><a href="{$path}/MyAccount/SuggestedTitles">{translate text='Recommended For You'}</a></div>
							{/if}
						{/if}
						<hr class="menu">
						<div class="myAccountLink{if $pageTemplate=="profile.tpl"} active{/if}"><a href="{$path}/MyAccount/Profile">Account Settings</a></div>
						{* Only highlight saved searches as active if user is logged in: *}
						<div class="myAccountLink{if $user && $pageTemplate=="history.tpl"} active{/if}"><a href="{$path}/Search/History?require_login">{translate text='history_saved_searches'}</a></div>
					</div>
				</div>
			</div>

			{* My Lists*}
			{if $lists || $showConvertListsFromClassic}
				{if $action == 'MyList'}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myListsPanel">
						<div class="panel-heading">
							<div class="panel-title">
								My Lists
							</div>
						</div>
					</a>
					<div id="myListsPanel" class="panel-collapse collapse{if $action == 'MyRatings' || $action == 'Suggested Titles' || $action == 'MyList'} in{/if}">
						<div class="panel-body">
							{if $showConvertListsFromClassic}
								<div class="myAccountLink"><a href="{$path}/MyAccount/ImportListsFromClassic" class="btn btn-sm btn-default">Import Existing Lists</a></div>
								<br>
							{/if}

							{foreach from=$lists item=list}
								{if $list.id != -1}
									<div class="myAccountLink"><a href="{$list.url}">{$list.name}{if $list.numTitles} ({$list.numTitles}){/if}</a></div>
									{*<div class="myAccountLink"><a href="{$list.url}">{$list.name}{if $list.numTitles} <span class="badge">{$list.numTitles}</span>{/if}</a></div>*}
								{/if}
							{/foreach}
						</div>
					</div>
				</div>
			{/if}

			{if $tagList}
				<div class="panel">
					<a data-toggle="collapse" data-parent="#account-link-accordion" href="#myTagsPanel">
						<div class="panel-heading">
							<div class="panel-title collapsed">
								My Tags
							</div>
						</div>
					</a>
					<div id="myTagsPanel" class="panel-collapse collapse">
						<div class="panel-collapse">
							<div class="panel-body">
								{foreach from=$tagList item=tag}
									<div class="myAccountLink">
										<a href='{$path}/Search/Results?lookfor={$tag->tag|escape:"url"}&amp;basicType=tag'>{$tag->tag|escape:"html"}</a> ({$tag->cnt})&nbsp;
										<a href='#' onclick="return VuFind.Account.removeTag('{$tag->tag}');">
											<span class="glyphicon glyphicon-remove-circle" title="Delete Tag">&nbsp;</span>
										</a>
									</div>
								{/foreach}
							</div>
						</div>
					</div>
				</div>
			{/if}

			{* Admin Functionality if Available *}
			{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor') || $user->hasRole('libraryManager') || $user->hasRole('locationManager'))}
				{if in_array($action, array('Libraries', 'Locations', 'IPAddresses', 'ListWidgets', 'BrowseCategories', 'UserSuggestions', 'PTypes', 'CirculationStatuses', 'LoanRules', 'LoanRuleDeterminers', 'AccountProfiles', 'NYTLists'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#vufindMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Pika Configuration
							</div>
						</div>
					</a>
					<div id="vufindMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{* Library Admin Actions *}
							{if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('libraryManager'))}
								<div class="adminMenuLink{if $action == "Libraries"} active{/if}"><a href="{$path}/Admin/Libraries">Library Systems</a></div>
							{/if}
							{if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('libraryManager') || $user->hasRole('locationManager'))}
								<div class="adminMenuLink{if $action == "Locations"} active{/if}"><a href="{$path}/Admin/Locations">Locations</a></div>
							{/if}
							{if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('libraryManager') || $user->hasRole('locationManager'))}
								<div class="adminMenuLink{if $action == "BlockPatronAccountLinks"} active{/if}"><a href="{$path}/Admin/BlockPatronAccountLinks">Block Patron Account Linking</a></div>
							{/if}

							{* OPAC Admin Actions*}
							{if $user->hasRole('opacAdmin')}
								<div class="adminMenuLink{if $action == "IPAddresses"} active{/if}"><a href="{$path}/Admin/IPAddresses">IP Addresses</a></div>
							{/if}

							{* Content Editor Actions *}
							<div class="adminMenuLink{if $action == "ListWidgets"} active{/if}"><a href="{$path}/Admin/ListWidgets">List Widgets</a></div>
							<div class="adminMenuLink{if $action == "BrowseCategories"} active{/if}"><a href="{$path}/Admin/BrowseCategories">Browse Categories</a></div>
							{if ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('libraryManager') || $user->hasRole('contentEditor'))}
								<div class="adminMenuLink{if $action == "NYTLists"} active{/if}"><a href="{$path}/Admin/NYTLists">NY Times Lists</a></div>
							{/if}

							{* OPAC Admin Actions*}
							{if $user->hasRole('opacAdmin')}
								<div class="adminMenuLink{if $action == "UserSuggestions"} active{/if}"><a href="{$path}/Admin/UserSuggestions">User Suggestions</a></div>
								{* Sierra/Millennium OPAC Admin Actions*}
								{if ($ils == 'Millennium' || $ils == 'Sierra' || $ils == 'Horizon')}
								<div class="adminMenuLink{if $action == "PTypes"} active{/if}"><a href="{$path}/Admin/PTypes">P-Types</a></div>
								{/if}
								{if ($ils == 'Millennium' || $ils == 'Sierra')}
								<div class="adminMenuLink{if $action == "CirculationStatuses"} active{/if}"><a href="{$path}/Admin/CirculationStatuses">Circulation Statuses</a></div>
								<div class="adminMenuLink{if $action == "LoanRules"} active{/if}"><a href="{$path}/Admin/LoanRules">Loan Rules</a></div>
								<div class="adminMenuLink{if $action == "LoanRuleDeterminers"} active{/if}"><a href="{$path}/Admin/LoanRuleDeterminers">Loan Rule Determiners</a></div>
								{/if}
								{* OPAC Admin Actions*}
								<div class="adminMenuLink{if $action == "AccountProfiles"} active{/if}"><a href="{$path}/Admin/AccountProfiles">Account Profiles</a></div>
							{/if}

						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('userAdmin') || $user->hasRole('opacAdmin'))}
				{if in_array($action, array('Administrators', 'DBMaintenance', 'DBMaintenanceEContent', 'PHPInfo', 'OpCacheInfo', 'Variables', 'CronLog'))
				|| ($module == 'Admin' && $action == 'Home')}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#adminMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								System Administration
							</div>
						</div>
					</a>
					<div id="adminMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{if $user->hasRole('userAdmin')}
								<div class="adminMenuLink {if $action == "Administrators"}active{/if}"><a href="{$path}/Admin/Administrators">Administrators</a></div>
							{/if}
							<div class="adminMenuLink{if $action == "DBMaintenance"} active{/if}"><a href="{$path}/Admin/DBMaintenance">DB Maintenance - Pika</a></div>
							<div class="adminMenuLink{if $action == "DBMaintenanceEContent"} active{/if}"><a href="{$path}/Admin/DBMaintenanceEContent">DB Maintenance - EContent</a></div>
							<div class="adminMenuLink{if $module == 'Admin' && $action == "Home"} active{/if}"><a href="{$path}/Admin/Home">Solr Information</a></div>
							<div class="adminMenuLink{if $action == "PHPInfo"} active{/if}"><a href="{$path}/Admin/PHPInfo">PHP Information</a></div>
							<div class="adminMenuLink{if $action == "MemCacheInfo"} active{/if}"><a href="{$path}/Admin/MemCacheInfo">MemCache Information</a></div>
							<div class="adminMenuLink{if $action == "OpCacheInfo"} active{/if}"><a href="{$path}/Admin/OpCacheInfo">OpCache Information</a></div>
							<div class="adminMenuLink{if $action == "Variables"} active{/if}"><a href="{$path}/Admin/Variables">System Variables</a></div>
							<div class="adminMenuLink{if $action == "CronLog"} active{/if}"><a href="{$path}/Admin/CronLog">Cron Log</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('libraryAdmin') || $user->hasRole('opacAdmin') || $user->hasRole('cataloging'))}
				{if in_array($action, array('ReindexLog', 'OverDriveExtractLog', 'IndexingStats', 'IndexingProfiles', 'TranslationMaps'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#indexingMenuGroup" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Indexing Information
							</div>
						</div>
					</a>
					<div id="indexingMenuGroup" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink {if $action == "IndexingStats"}active{/if}"><a href="{$path}/Admin/IndexingStats">Indexing Statistics</a></div>
							<div class="adminMenuLink {if $action == "ReindexLog"}active{/if}"><a href="{$path}/Admin/ReindexLog">Reindex Log</a></div>
							<div class="adminMenuLink {if $action == "OverDriveExtractLog"}active{/if}"><a href="{$path}/Admin/OverDriveExtractLog">OverDrive Extract Log</a></div>
							<div class="adminMenuLink {if $action == "IndexingProfiles"}active{/if}"><a href="{$path}/Admin/IndexingProfiles">Indexing Profiles</a></div>
							<div class="adminMenuLink {if $action == "TranslationMaps"}active{/if}"><a href="{$path}/Admin/TranslationMaps">Translation Maps</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && $enableMaterialsRequest && ($user->hasRole('cataloging') || $user->hasRole('library_material_requests') || $user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
				{if in_array($action, array('ManageRequests', 'SummaryReport', 'UserReport', 'ManageStatuses'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#materialsRequestMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Materials Requests
							</div>
						</div>
					</a>
					<div id="materialsRequestMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "ManageRequests"}active{/if}"><a href="{$path}/MaterialsRequest/ManageRequests">Manage Requests</a></div>
							<div class="adminMenuLink{if $action == "SummaryReport"}active{/if}"><a href="{$path}/MaterialsRequest/SummaryReport">Summary Report</a></div>
							<div class="adminMenuLink{if $action == "UserReport"}active{/if}"><a href="{$path}/MaterialsRequest/UserReport">Report By User</a></div>
							<div class="adminMenuLink{if $action == "ManageStatuses"}active{/if}"><a href="{$path}/Admin/ManageStatuses">Manage Statuses</a></div>
							<div class="adminMenuLink"><a href="https://docs.google.com/document/d/1s9qOhlHLfQi66qMMt5m-dJ0kGNyHiOjSrqYUbe0hEcA">Documentation</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('cataloging') || $user->hasRole('opacAdmin'))}
				{if in_array($action, array('MergedGroupedWorks', 'NonGroupedRecords', 'AuthorEnrichment'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#catalogingRequestMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Cataloging
							</div>
						</div>
					</a>
					<div id="catalogingRequestMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "MergedGroupedWorks"}active{/if}"><a href="{$path}/Admin/MergedGroupedWorks">Grouped Work Merging</a></div>
							<div class="adminMenuLink{if $action == "NonGroupedRecords"}active{/if}"><a href="{$path}/Admin/NonGroupedRecords">Records To Not Merge</a></div>
							<div class="adminMenuLink{if $action == "AuthorEnrichment"}active{/if}"><a href="{$path}/Admin/AuthorEnrichment">Author Enrichment</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('archives') || $user->hasRole('opacAdmin'))}
				{if in_array($action, array('ArchiveSubjects', 'ArchiveRequests'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#archivesMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Archives
							</div>
						</div>
					</a>
					<div id="archivesMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "ArchiveRequests"}active{/if}"><a href="{$path}/Admin/ArchiveRequests">Archive Material Requests</a></div>
							<div class="adminMenuLink{if $action == "ArchiveSubjects"}active{/if}"><a href="{$path}/Admin/ArchiveSubjects">Archive Subject Control</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('circulationReports'))}
				{if $module == 'Circa'}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#circulationMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Circulation
							</div>
						</div>
					</a>
					<div id="circulationMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "Home" && $module == "Circa"}active{/if}"><a href="{$path}/Circa/Home">Inventory</a></div>
							<div class="adminMenuLink{if $action == "OfflineCirculation" && $module == "Circa"} active{/if}"><a href="{$path}/Circa/OfflineCirculation">Offline Circulation</a></div>
							<div class="adminMenuLink{if $action == "OfflineHoldsReport" && $module == "Circa"}active{/if}"><a href="{$path}/Circa/OfflineHoldsReport">Offline Holds Report</a></div>
							<div class="adminMenuLink{if $action == "OfflineCirculationReport" && $module == "Circa"}active{/if}"><a href="{$path}/Circa/OfflineCirculationReport">Offline Circulation Report</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('contentEditor'))}
				{if $module == "EditorialReview"}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#editorialReviewMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Editorial Reviews
							</div>
						</div>
					</a>
					<div id="editorialReviewMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							<div class="adminMenuLink{if $action == "Edit" && $module == "EditorialReview"}active{/if}"><a href="{$path}/EditorialReview/Edit">New Review</a></div>
							<div class="adminMenuLink{if $action == "Search" && $module == "EditorialReview"}active{/if}"><a href="{$path}/EditorialReview/Search">Search Existing Reviews</a></div>
						</div>
					</div>
				</div>
			{/if}

			{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('locationReports') || $user->hasRole('contentEditor'))}
				{if in_array($action, array('Dashboard', 'Searches', 'PageViews', 'ILSIntegration', 'ReportPurchase', 'ReportExternalLinks', 'PatronStatus', 'DetailedReport', 'StudentReport'))}
					{assign var="curSection" value=true}
				{else}
					{assign var="curSection" value=false}
				{/if}
				<div class="panel{if $curSection} active{/if}">
					<a href="#reportsMenu" data-toggle="collapse" data-parent="#adminMenuAccordion">
						<div class="panel-heading">
							<div class="panel-title">
								Reports
							</div>
						</div>
					</a>
					<div id="reportsMenu" class="panel-collapse collapse {if $curSection}in{/if}">
						<div class="panel-body">
							{if $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin'))}
								{*
								<div class="adminMenuLink{if $action == "Dashboard"}active{/if}"><a href="{$path}/Report/Dashboard">Dashboard</a></div>
								<div class="adminMenuLink{if $action == "Searches"}active{/if}"><a href="{$path}/Report/Searches">Searches</a></div>
								<div class="adminMenuLink">&nbsp;&nbsp;<a href="{$path}/Report/DetailedReport?source=searchesByScope">Searches by Scope</a></div>
								<div class="adminMenuLink{if $action == "PageViews"}active{/if}"><a href="{$path}/Report/PageViews">Page Views</a></div>
								<div class="adminMenuLink">&nbsp;&nbsp;<a href="{$path}/Report/DetailedReport?source=pageViewsByTheme">Page Views by Theme</a></div>
								<div class="adminMenuLink{if $action == "ILSIntegration"}active{/if}"><a href="{$path}/Report/ILSIntegration">ILS Integration</a></div>
								<div class="adminMenuLink">&nbsp;&nbsp;<a href="{$path}/Report/DetailedReport?source=holdsByResult">Holds Placed</a></div>
								<div class="adminMenuLink">&nbsp;&nbsp;<a href="{$path}/Report/DetailedReport?source=renewalsByResult">Renewals</a></div>
								*}
								<div class="adminMenuLink{if $action == "ReportPurchase"}active{/if}"><a href="{$path}/Report/ReportPurchase">Purchase Tracking</a></div>
								<div class="adminMenuLink{if $action == "ReportExternalLinks"}active{/if}"><a href="{$path}/Report/ReportExternalLinks">External Link Tracking</a></div>
								<div class="adminMenuLink{if $action == "PatronStatus"}active{/if}"><a href="{$path}/Report/PatronStatus">Patron Status</a></div>
							{/if}
							{if $ils == 'Sierra' && $user && ($user->hasRole('opacAdmin') || $user->hasRole('libraryAdmin') || $user->hasRole('locationReports'))}
								<div class="adminMenuLink{if $action == "StudentReport"}active{/if}"><a href="{$path}/Report/StudentReport">Student Reports</a></div>
							{/if}
						</div>
					</div>
				</div>
			{/if}
		</div>
	</div>
{/if}
{/strip}
