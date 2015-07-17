<!DOCTYPE html>
<html lang="{$userLang}">
	<head>{strip}
		<title>{$pageTitle|truncate:64:"..."}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		{if $google_translate_key}
			<meta name="google-translate-customization" content="{$google_translate_key}" />
		{/if}
		{if $addHeader}{$addHeader}{/if}

		{if $metadataTemplate}
			{include file=$metadataTemplate}
		{/if}
		<link type="image/x-icon" href="{img filename=favicon.png}" rel="shortcut icon" />
		<link rel="search" type="application/opensearchdescription+xml" title="{$site.title} Catalog Search" href="{$path}/Search/OpenSearch?method=describe" />

		{include file="cssAndJsIncludes.tpl"}
		{/strip}
        {literal}
        <!-- Typekit JavaScript for Akagi Pro Typeface -->
        <script src="//use.typekit.net/uew0ppi.js"></script>
		<script>try{Typekit.load();}catch(e){}</script>
        {/literal}
	</head>
	<body class="module_{$module} action_{$action}" id="{$module}-{$action}">
		{strip}
		<div class="container">
			{if $systemMessage}
				<div id="system-message-header" class="row">{$systemMessage}</div>
			{/if}

			

			<div id="header-wrapper" class="row">
				<div id="header-container">
					{include file='header_responsive.tpl'}
				</div>
			</div>
            
            <div class="row breadcrumbs">
				<div class="hidden-xs col-xs-12 col-sm-9">
					{if $showBreadcrumbs}
					<ul class="breadcrumb">
						<li><a href="{$homeBreadcrumbLink}" id="home-breadcrumb"><i class="icon-home"></i> {translate text=$homeLinkText}</a> <span class="divider">&raquo;</span></li>
						{include file="$module/breadcrumbs.tpl"}
					</ul>
					{/if}
				</div>
				<a name="top"></a>
				<div class="col-xs-12 col-sm-3 text-right">
					{if $google_translate_key}
						{literal}
						<div id="google_translate_element">
							<script type="text/javascript">
								function googleTranslateElementInit() {
									new google.translate.TranslateElement({
										pageLanguage: 'en',
										layout: google.translate.TranslateElement.InlineLayout.SIMPLE
										{/literal}
										{if $google_included_languages}
										, includedLanguages: '{$google_included_languages}'
										{/if}
										{literal}
									}, 'google_translate_element');
								}
							</script>
							<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
						</div>
						{/literal}
					{/if}
				</div>
			</div>


			<div id="content-container" class="row">
				{if isset($sidebar)}
					{* Setup the left bar *}
					<div class="col-xs-12 col-sm-4 col-md-3 col-lg-3" id="side-bar">
						{include file="$sidebar"}
					</div>
					<div class="hidden-xs visible-sm col-xs-12 col-sm-8 col-md-9 col-lg-9" id="main-content-with-sidebar">
						{include file="$module/$pageTemplate"}
					</div>
				{else}
					{include file="$module/$pageTemplate"}
				{/if}
			</div>




		</div>
        
        <div id="footer-container" class="row">
            {include file="footer_responsive.tpl"}
        </div>
        
        			<div id="navigation-controls" class="navbar navbar-fixed-bottom row visible-xs hidden-sm hidden-md hidden-lg">
				<a href="#top"><div class="col-xs-6 text-center">Back To Top</div></a>
				{if $showLoginButton == 1}
					{if $user}
						<a href="#account-menu"><div class="col-xs-6 text-center">Account Menu</div></a>
					{else}
						<a href="{$path}/MyAccount/Home" title='Login' onClick="return VuFind.Account.followLinkIfLoggedIn(this);">
							<div class="col-xs-6 text-center">{translate text="Login"}</div>
						</a>
					{/if}
				{/if}
			</div>

		{include file="modal_dialog.tpl"}

			{* hold messages shouldn't be needed any longer. plb 2-13-2015 *}
		{if $hold_message}
			<script type="text/javascript">
				VuFind.showMessage('Hold Results', "{$hold_message|escape:'javascript'}");
			</script>
		{/if}

		{if $renew_message}
			<script type="text/javascript">
				VuFind.showMessage('Renewal Results', "{$renew_message|escape:'javascript'}");
			</script>
		{/if}

		{if $checkout_message}
			<script type="text/javascript">
				VuFind.showMessage('Checkout Results', "{$checkout_message|escape:'javascript'}");
			</script>
		{/if}

		{include file="tracking.tpl"}
		{/strip}
	</body>
</html>