{* {strip} *}
	<div class="col-xs-12">
		<h2>
			{$title|escape}
		</h2>
		<div class="large-image-wrapper">
			<div class="large-image-content">
				<div id="pika-openseadragon" class="openseadragon"></div>
			</div>
		</div>

		<div id="alternate-image-navigator">
			<img src="{$front_thumbnail}" class="img-responsive alternate-image" onclick="VuFind.Archive.openSeaDragonViewer.goToPage(0);">
			<img src="{$back_thumbnail}" class="img-responsive alternate-image" onclick="VuFind.Archive.openSeaDragonViewer.goToPage(1);">
		</div>

		<div id="image-download-options">
			{if $anonymousLcDownload || ($user && $verifiedLcDownload)}
				<a class="btn btn-default" href="/Archive/{$pid}/DownloadLC">Download Large Image</a>
			{elseif (!$user && $verifiedLcDownload)}
				<a class="btn btn-default" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadLC">Login to Download Large Image</a>
			{/if}
			{if $anonymousMasterDownload || ($user && $verifiedMasterDownload)}
				<a class="btn btn-default" href="/Archive/{$pid}/DownloadOriginal">Download Original Image</a>
			{elseif (!$user && $verifiedLcDownload)}
				<a class="btn btn-default" onclick="return VuFind.Account.followLinkIfLoggedIn(this)" href="/Archive/{$pid}/DownloadOriginal">Login to Download Original Image</a>
			{/if}
		</div>

		{include file="Archive/metadata.tpl"}
	</div>
	<script src="{$path}/js/openseadragon/openseadragon.js" ></script>
	<script src="{$path}/js/openseadragon/djtilesource.js" ></script>
	<script type="text/javascript">
		$(document).ready(function(){ldelim}
			if (!$('#pika-openseadragon').hasClass('processed')) {ldelim}
				var openSeadragonSettings = {ldelim}
					"pid":"{$pid}",
					"resourceUri":{$front_image|@json_encode nofilter},
					"tileSize":256,
					"tileOverlap":0,
					"id":"pika-openseadragon",
					"settings": {ldelim}
							"id":"pika-openseadragon",
							"prefixUrl":"https:\/\/islandora.marmot.org\/sites\/all\/libraries\/openseadragon\/images\/",
							"debugMode":false,
							"djatokaServerBaseURL":"https:\/\/islandora.marmot.org\/adore-djatoka\/resolver",
							"tileSize":256,
							"tileOverlap":0,
							"animationTime":1.5,
							"blendTime":0.1,
							"alwaysBlend":false,
							"autoHideControls":1,
							"immediateRender":true,
							"wrapHorizontal":false,
							"wrapVertical":false,
							"wrapOverlays":false,
							"panHorizontal":1,
							"panVertical":1,
							"minZoomImageRatio":0.35,
							"maxZoomPixelRatio":2,
							"visibilityRatio":0.5,
							"springStiffness":5,
							"imageLoaderLimit":5,
							"clickTimeThreshold":300,
							"clickDistThreshold":5,
							"zoomPerClick":2,
							"zoomPerScroll":1.2,
							"zoomPerSecond":2,
							"showNavigator":1,
							"defaultZoomLevel":1
					{rdelim}
				{rdelim};
				openSeadragonSettings.settings.tileSources = new Array();
				var frontTile = new OpenSeadragon.DjatokaTileSource(
						"https://islandora.marmot.org/adore-djatoka/resolver",
						'{$front_image}',
						openSeadragonSettings.settings
				);
				openSeadragonSettings.settings.tileSources.push(frontTile);
				var backTile = new OpenSeadragon.DjatokaTileSource(
						"https://islandora.marmot.org/adore-djatoka/resolver",
						'{$back_image}',
						openSeadragonSettings.settings
				);
				openSeadragonSettings.settings.tileSources.push(backTile);

				VuFind.Archive.openSeaDragonViewer = new OpenSeadragon(openSeadragonSettings.settings);
				//VuFind.Archive.initializeOpenSeadragon(viewer);
				$('#pika-openseadragon').addClass('processed');
			{rdelim}
		{rdelim});
	</script>
{* {/strip} *}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
		{rdelim});
</script>