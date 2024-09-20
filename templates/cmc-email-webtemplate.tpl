<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Email broadcast</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style>
		body { background: #F2F2F2; padding-top: 65px; text-align: center; font-family: Arial, Helvetica, sans-serif; text-align: center; }
		a, a:hover { color: #185787; }
		p { margin: 0; padding: 0 0 18px 0; font-size: 12px; color: #666; line-height: 18px; }
		.clear { clear: both; }
		.container { width: 580px; background: #FFF; position: relative; margin: 0 auto; padding: 0; }
		.container img { float: left; }
		.footer { color: #797c80; font-size: 12px; border-left: 1px solid #DDD; border-right: 1px solid #DDD; padding-top: 3px; padding-left: 39px; padding-right: 13px; padding-bottom: 1px; text-align: left; }
		.iframe { border: 1px solid #ccc; position: relative; margin: 0 auto; width: 800px; height: 500px; }
		.title { padding-top: 34px; padding-left: 39px; padding-right: 39px; text-align: left; border-left: 1px solid #DDD; border-right: 1px solid #DDD; }
		.title h2 { font-size: 30px; color: #262626; font-weight: normal; margin: 0 0 13px 0; padding: 0; letter-spacing: 0; }
		.title h3 { font-size: 17px; font-weight: normal; margin-bottom: 19px; }
	</style>
</head>
<body>
	<div class="container">
		<img src="//static.reachtel.com.au/top.gif" width="580" height="8" />
		<div class="clear"></div>
		<div class="title">
{if $action == "unsubscribe"}
			<h2>Unsubscribe confirmation page</h2>
			<form action="?" method="post">
				<input type="hidden" name="guid" value="{$guid}" />
				<input type="hidden" name="hmac" value="{$hmac}" />
				<input type="hidden" name="action" value="unsubscribe" />
				<input type="submit" name="confirm" value="Confirm unsubscribe?" style="font-size: 20pt;" />
			</form>
{else if $action == "unsubscribeconfirm"}
			<h2>Unsubscribe confirmation page</h2>
			<span style="color: red;">Unsubscribe complete.</span>
{else}
			<span style="color: red;">Sorry, that is not a valid request.</span>
{/if}
		</div>
		<div class="footer">&nbsp;</div>
		<img src="//static.reachtel.com.au/bottom.gif" width="580" height="8" />
		<div class="clear"></div>
	</div>
</body>
</html>