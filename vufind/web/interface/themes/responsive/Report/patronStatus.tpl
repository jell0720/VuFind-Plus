{strip}
	<div id="main-content" class="col-md-12">
		{if $user}
			<h1>Patron Status Report</h1>
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
			<form id="patronStatusInput" method="post" enctype="multipart/form-data">
				<fieldset>
					<legend>Patron Report Files</legend>
					<div class="form-group">
						<label for="patronReport">Patron Report: </label><input type="file" name="patronReport" id="patronReport">
					</div>
					<div class="form-group">
						<label for="itemReport">Item Report: </label><input type="file" name="itemReport" id="itemReport">
					</div>
					<input type="submit" name="submit" id="submit" value="Generate Report" onclick="return processPatronStatusSubmit();" class="btn btn-primary"/>
					<div class="warning" style="display:none" id="patronStatusProcessing">
						Processing the patron status report.  This may take several minutes.  Please do not refresh the page.
					</div>
				</fieldset>
			</form>
		{else}
			You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
		{/if}
	</div>
{/strip}
{literal}
<script type="text/javascript">
	function processPatronStatusSubmit(){
		$("#submit").hide();
		$("#patronStatusProcessing").show();
		return true;
	}
</script>
{/literal}