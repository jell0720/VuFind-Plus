{strip}
	<div id="page-content" class="content">
		{if $error}<p class="error">{$error}</p>{/if}
		<div id="sidebar">
			<div class="button"><a href="{$path}/MyAccount/Home">Return to Account</a></div>
			<hr />

			{* Report filters *}
			<div class="sidegroup">
				<h4>Report Filters</h4>
				<div class="sidegroupContents">
					<form id="offlineHoldsFilter">
						<div>
							<div>
								<label for="startDate">Start Date</label> <input type="text" name="startDate" id="startDate" size="10" value="{$startDate|date_format:'%m/%d/%Y'}"/>
							</div>
							<div>
								<label for="endDate">End Date</label> <input type="text" name="endDate" id="endDate" size="10" value="{$endDate|date_format:'%m/%d/%Y'}"/>
							</div>

							<div>
								<input type="submit" name="updateFilters" value="Update Filters"/>
							</div>

						</div>
					</form>
				</div>
			</div>
		</div>

		<div id="main-content">
			<h2>Offline Holds</h2>
			{if count($offlineHolds) > 0}
				<table class="citation tablesorter" id="offlineHoldsReport" >
					<thead>
						<tr><th>Patron Barcode</th><th>Record Id</th><th>Title</th><th>Date Entered</th><th>Status</th><th>Notes</th></tr>
					</thead>
					<tbody>
						{foreach from=$offlineHolds item=offlineHold}
							{* TODO Update this to work with multi-ils installations*}
							<tr><td>{$offlineHold.patronBarcode}</td><td>{$offlineHold.bibId}</td><td><a href="{$path}/Record/{$offlineHold.bibId}">{$offlineHold.title}</a></td><td>{$offlineHold.timeEntered|date_format}</td><td>{$offlineHold.status}</td><td>{$offlineHold.notes}</td></tr>
						{/foreach}
					</tbody>
				</table>
			{else}
				<p>There are no offline holds to display.</p>
			{/if}
		</div>
	</div>
	<script	type="text/javascript">
		{literal}
		$(function() {
			$( "#startDate" ).datepicker({ showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
			$( "#endDate" ).datepicker({ showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
			$("#offlineHoldsReport").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
		});
		{/literal}
	</script>
{/strip}