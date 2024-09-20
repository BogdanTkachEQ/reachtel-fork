	<div class="container">
		<link href="/css/secure.css" rel="stylesheet">

		<form class="form-signin" method="post" action="">
{if !empty($logo)}
			<div class="text-center"><img src="{$logo}" alt="Logo" title="Logo"/></div>
{/if}
			<h2 class="form-signin-heading">Secure document</h2>
{if $action == "authfail"}
			<div class="alert alert-danger" role="alert">Invalid authentication details.</div>
{/if}
{if ($action == "auth") OR ($action == "authfail")}
			<h4>To access the document, please provide the following information:</h4>
{if $type == "email"}
			<label for="destination">Email address:</label>
			<input type="text" id="destination" name="destination" placeholder="e.g. john.smith@example.com" class="form-control" required autofocus autocomplete="off">
{else}
			<label for="destination">Mobile number:</label>
			<input type="text" id="destination" name="destination" placeholder="e.g. 0400111222" class="form-control" required autofocus autocomplete="off">
{/if}
{if !isset($hideauth)}
			<label for="auth">{$authmessage|default:'Authentication value'}:</label>
			<input type="text" id="auth" name="auth" class="form-control" placeholder="{(isset($authmessageexample))?$authmessageexample:''}" required autocomplete="off">
{/if}
{if isset($authmessage2)}
			<label for="auth2">{$authmessage2|default:'Second authentication value'}:</label>
			<input type="text" id="auth2" name="auth2" class="form-control" placeholder="{(isset($authmessageexample2))?$authmessageexample2:''}" required autocomplete="off">
{/if}
			<input type="hidden" name="enctargetid" value="{$enctargetid}" />
			<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
{else if $action == "blocked"}
			<h4>Sorry, access to this resource has been blocked.</h4>
			<p>Please contact the message sender for more information.</p>
{else if $action == "saved"}
			<h4>Form submitted</h4>
			<p>Thank you. The information has been saved.</p>
{else}
			<h4>Sorry, that link is invalid.</h4>
			<p>Please contact the message sender for more information.</p>
{/if}
		</form>

	</div> <!-- /container -->
