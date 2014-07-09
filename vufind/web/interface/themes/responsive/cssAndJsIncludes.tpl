{strip}
	{* Include css as appropriate *}
	{css filename="main.css"}

	{* Include correct all javascript *}
	{if $debugJs}
		<script src="{$path}/js/jquery-1.9.1.min.js"></script>
		{* Load Libraries*}
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.tablesorter.min.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.validate.min.js"></script>

		<script src="{$path}/interface/themes/responsive/js/lib/recaptcha_ajax.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/rater.min.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/bootstrap.min.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jcarousel.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/bootstrap-datepicker.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jquery-ui-1.10.4.custom.min.js"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/bootstrap-switch.min.js"></script>

		{* Load application specific Javascript *}
		<script src="{$path}/interface/themes/responsive/js/vufind/globals.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/base.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/account.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/admin.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/browse.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/econtent-record.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/grouped-work.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/lists.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/lists-widgets.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/materials-request.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/overdrive.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/prospector.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/ratings.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/reading-history.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/record.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/responsive.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/results-list.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/searches.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/title-scroller.js"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/wikipedia.js"></script>
	{else}
		{* This is all merged using the merge_javascript.php file called automatically with a File Watcher*}
		{* Code is minified using uglify.js *}
		<script src="{$url}/interface/themes/responsive/js/vufind.min.js"></script>
	{/if}

	<script type="text/javascript">
		{* Override variables as needed *}
		{literal}
		$(document).ready(function(){
			{/literal}
			Globals.path = '{$path}';
			Globals.url = '{$url}';
			Globals.loggedIn = {$loggedIn};
			{if $automaticTimeoutLength}
			Globals.automaticTimeoutLength = {$automaticTimeoutLength};
			{/if}
			{if $automaticTimeoutLengthLoggedOut}
			Globals.automaticTimeoutLengthLoggedOut = {$automaticTimeoutLengthLoggedOut};
			{/if}
			{literal}
		});
		{/literal}
	</script>

	{if $includeAutoLogoutCode == true}
		{if $debugJs}
			<script type="text/javascript" src="{$path}/interface/themes/responsive/js/vufind/autoLogout.js"></script>
		{else}
			<script type="text/javascript" src="{$path}/interface/themes/responsive/js/vufind/autoLogout.min.js"></script>
		{/if}
	{/if}
	{if $additionalCss}
		<style type="text/css">
			{$additionalCss}
		</style>
	{/if}
{/strip}