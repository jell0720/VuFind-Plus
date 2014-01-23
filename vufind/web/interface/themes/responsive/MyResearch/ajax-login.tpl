<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h3 id="modal-title">Login</h3>
</div>
<div class="modal-body">
	<p class="text-error text-center" id="loginError" style="display: none"></p>
	<form method="post" action="{$path}/MyResearch/Home" id="loginForm" class="form-horizontal" role="form">
		<div id ='loginUsernameRow' class='form-group'>
			<label for="username" class='control-label col-sm-3'>{translate text='Username'}</label>
			<div class='col-sm-9'>
				<input type="text" name="username" id="username" value="{$username|escape}" size="28"/>
			</div>
		</div>
		<div id ='loginPasswordRow' class='form-group'>
			<label for="password" class='control-label col-sm-3'>{translate text='Password'}: </label>
			<div class='col-sm-9'>
				<input type="password" name="password" id="password" size="28"/>
			</div>
		</div>
		<div id ='loginPasswordRow2' class='form-group'>
			<div class='col-sm-offset-3 col-sm-9'>
				<label for="showPwd" class="checkbox">
					<input type="checkbox" id="showPwd" name="showPwd" onclick="return VuFind.pwdToText('password')"/>
					{translate text="Reveal Password"}
				</label>

				{if !$inLibrary}
					<label for="rememberMe" class="checkbox">
						<input type="checkbox" id="rememberMe" name="rememberMe"/>
						{translate text="Remember Me"}
					</label>
				{/if}
			</div>
		</div>
	</form>
	<script type="text/javascript">$('#username').focus().select();</script>
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	<input type="submit" name="submit" value="Login" id="loginFormSubmit" class="btn btn-primary" onclick="return VuFind.Account.processAjaxLogin()"/>
</div>
