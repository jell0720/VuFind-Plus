{strip}
	<div id="home-page-login" class="text-center row">
		<div class="logoutOptions hidden-phone" {if !$user} style="display: none;"{/if}>
			<a id="myAccountNameLink" href="{$path}/MyAccount/Home">Logged In As {$user->firstname|capitalize} {$user->lastname|capitalize|substr:0:1}.</a>
		</div>
		<div class="logoutOptions" {if !$user} style="display: none;"{/if}>
			<a href="{$path}/MyAccount/Logout" id="logoutLink" >{translate text="Log Out"}</a>
		</div>
		<div class="loginOptions" {if $user} style="display: none;"{/if}>
			{if $showLoginButton == 1}
				<a href="{$path}/MyAccount/Home" class='loginLink' title='Login To My Account' onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text="LOGIN TO MY ACCOUNT"}</a>
			{/if}
		</div>
	</div>
{/strip}