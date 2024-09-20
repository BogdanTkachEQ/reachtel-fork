<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>{$title} - ReachTEL</title>

	<link rel="stylesheet" href="/css/design.css?v={$releaseVersion}" />
	<link rel="stylesheet" href="/css/jquery.timepicker.min.css" />
	<link rel="stylesheet" href="/css/jquery-ui-1.8.14.custom.css" />

	<script src="/js/jquery-3.3.1.min.js" type="text/javascript"></script>
	<script src="/js/jquery-migrate-3.0.0.min.js" type="text/javascript"></script>
	<script src="/js/jquery-ui.min.js?v={$releaseVersion}" type="text/javascript"></script>
	<script src="/js/lodash.min.js?v={$releaseVersion}" type="text/javascript"></script>
	<script src="/js/moment.min.js?v={$releaseVersion}" type="text/javascript"></script>


	<script type="text/javascript" src="/js/jquery.timepicker.min.js?v={$releaseVersion}"></script>
	<script type="text/javascript" src="/js/js.cookie.min.js?v={$releaseVersion}"></script>
	<script type="text/javascript" src="/js/classes/ProcessQueue.js?v={$releaseVersion}"></script>
	<script type="text/javascript" src="/js/morpheus.js?v={$releaseVersion}"></script>
	<script type="text/javascript" src="//www.google.com/jsapi?key=ABQIAAAA-SbxgqENroL8jNXD9pV-lRSKwZfYSyEfHaGPsrzFsZfq_exjhBTL3hbh_mCYM7F15itLdhEJmtY78Q"></script>
	<script type="text/javascript">
		google.load("visualization", "1", {ldelim}packages:["corechart"]{rdelim});
	</script>

</head>
<body {if !empty($jscriptOnLoad)}onload="{$jscriptOnLoad}"{/if}>

  <!-- Header Cap -->

	<div class="header-cap">
		<div class="left">
			<h1><a href=""><strong>ReachTEL Pty Ltd</strong> ~</a> &nbsp;</h1>
		</div>
		<div class="right">
			<ul>
				<li class="first">Logged in as <strong>{$SESSION_DISPLAYNAME|default:"Unknown user"}</strong></li>

				<li><a href="admin_listuser.php?name={$SESSION_USERNAME|urlencode}">My Account</a></li>
				<li><a href="admin_landing.php?action=logout">Logout</a></li>
			</ul>
		</div>
		<div class="clear-both"></div>
	</div>
	<!-- /Header Cap -->

	<!-- Top Navigation -->

	<div class="top-navigation">
		<div class="clear-both"></div>
	</div>
	<!-- /Top Navigation -->

	<!-- Main Content Wrapper -->
	<div class="wrapper">
		<div class="content-right">
			<div class="inner-wrapper-right">
