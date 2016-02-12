{strip}
	{if $user->cat_username}
		{if $profile->web_note}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->web_note}</div>
			</div>
		{/if}

		{* Alternate Mobile MyAccount Menu *}
		{include file="MyAccount/mobilePageHeader.tpl"}

		{include file="MyAccount/availableHoldsNotice.tpl" noLink=1}

		{* Check to see if there is data for the section *}
		<p class="holdSectionBody">
			{if $libraryHoursMessage}
				<div class="libraryHours alert alert-success">{$libraryHoursMessage}</div>
			{/if}

			<p>
				{translate text="Items on hold includes titles in Overdrive."}
			</p>

			{foreach from=$recordList item=sectionData key=sectionKey}
				<h3>{if $sectionKey == 'available'}Holds - Ready{else}Holds - Not Ready{/if}</h3>
				{* Note: These Titles are custom for Arlington *}
				<p class="alert alert-info">
					{if $sectionKey == 'available'}
						{translate text="available hold summary"}
						{*These titles have arrived at the library or are available online for you to use.*}
					{else}
						{*{translate text="These titles are currently checked out to other patrons."}  We will notify you{if not $notification_method or $notification_method eq 'Unknown'}{else} via {$notification_method}{/if} when a title is available.*}
						{* Only show the notification method when it is known and set *}

						{* Arlington Custom Text *}
						These holds are not ready, and we will send notification once they become available. <a href="http://library.arlingtonva.us/services/accounts-and-borrowing/holds">Learn about freezing holds.</a>  
					{/if}
				</p>
				{if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}
					{* Make sure there is a break between the form and the table *}
					{if !$hideCoversFormDisplayed}
						{* Display the Hide Covers switch above the first section that has holds; and only display it once *}
						<div id="pager" class="navbar form-inline">
							<label for="hideCovers" class="control-label checkbox pull-right"> Hide Covers <input id="hideCovers" type="checkbox" onclick="VuFind.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
						</div>
						{assign var="hideCoversFormDisplayed" value=true}
						{else}
						<br>
					{/if}
					<div class="striped">
						{foreach from=$recordList.$sectionKey item=record name="recordLoop"}
							{if $record.holdSource == 'ILS'}
								{include file="MyAccount/ilsHold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
							{elseif $record.holdSource == 'OverDrive'}
								{include file="MyAccount/overdriveHold.tpl" record=$record section=$sectionKey resultIndex=$smarty.foreach.recordLoop.iteration}
							{else}
								<div class="row">
									Unknown record source {$record.holdSource}
								</div>
							{/if}
						{/foreach}
					</div>

					{* Code to handle updating multiple holds at one time *}
					<br>
					<div class="holdsWithSelected{$sectionKey}">
						<form id="withSelectedHoldsFormBottom{$sectionKey}" action="{$fullPath}">
							<div>
								<input type="hidden" name="withSelectedAction" value="">
								<div id="holdsUpdateSelected{$sectionKey}Bottom" class="holdsUpdateSelected{$sectionKey}">
									{*
									<input type="submit" class="btn btn-sm btn-warning" name="cancelSelected" value="Cancel Selected" onclick="return VuFind.Account.cancelSelectedHolds()">
									*}
									<input type="submit" class="btn btn-sm btn-default" id="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}Bottom" name="exportToExcel{if $sectionKey=='available'}Available{else}Unavailable{/if}" value="Export to Excel" >
								</div>
							</div>
						</form>
					</div>
				{else} {* Check to see if records are available *}
					{if $sectionKey == 'available'}
						{translate text='no_holds_ready_pickup'}
					{else}
						{translate text='You do not have any pending holds.'}
					{/if}

				{/if}
			{/foreach}
		</div>
{* Holds not displayed in a html table, so this code does not apply any more.
		<script type="text/javascript">
			$(function() {literal} { {/literal}
				$("#holdsTableavailable").tablesorter({literal}{cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: false}, 3: {sorter : 'date'}, 4: {sorter : 'date'}, 7: { sorter: false} } }{/literal});
			{literal} }); {/literal}
		</script>*}
	{else} {* Check to see if user is logged in *}
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	{/if}
{/strip}