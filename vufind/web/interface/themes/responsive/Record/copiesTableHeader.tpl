{strip}
	{* resize the columns when  including the lastcheckin box
 xs-5 : 41.6667%
 xs-4 : 33.3333%  (1/3)
 xs-3 : 25%       (1/4)
 xs-2 : 16.6667% (1/6)
 *}
	<div class="row">
		<div class="col-xs-{if $showLastCheckIn}4{else}5{/if} ">
			<strong><u>Location</u></strong>
		</div>
		<div class="holdingsCallNumber col-xs-{if $showLastCheckIn}3{else}4{/if}">
			<strong><u>Call Number</u></strong>
		</div>
		<div class="col-xs-{if $showLastCheckIn}3{else}3{/if}">
			<strong><u>Status</u></strong>
		</div>
		{if $showLastCheckIn}
			<div class="col-xs-2">
				<strong><u>Last Check-In</u></strong>
			</div>
		{/if}
	</div>
{/strip}