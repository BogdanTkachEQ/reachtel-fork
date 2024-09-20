<?php

// Load Smarty

function api_templates_start(){

	global $te;

	$te = new Smarty;
	$te->setPluginsDir(__DIR__ . '/lib/smarty_plugins');

	$te->escape_html = true;

	$te->compile_dir = TE_SMARTY_COMPILE_DIR;

	api_templates_assign("TE_IMAGE_LOCATION", TE_IMAGE_LOCATION);

	return $te;

}

// Assign value

function api_templates_assign($variable, $value){

	global $te;

	if(!isset($te)) $te = api_templates_start();

	$te->assign($variable, $value);

	return true;

}

// Display

function api_templates_display($template){

	global $te;
	global $smarty_notifications;

	if(!isset($te)) $te = api_templates_start();

	api_templates_assign("search_notification_pagination_template", TE_TEMPLATE_DIRECTORY . "/search_notification_pagination.tpl");

	if($smarty_notifications) api_templates_assign("smarty_notifications", $smarty_notifications);

	if(isset($_SESSION['userid'])) {
		api_templates_assign("SESSION_USERID", $_SESSION['userid']);
		api_templates_assign("SESSION_DISPLAYNAME", $_SESSION['displayname']);
		api_templates_assign("SESSION_USERNAME", $_SESSION['username']);
	}

	if($template == "footer.tpl") {
		api_templates_assign("load_time", api_misc_loadtime_end());
		api_templates_assign("server", sprintf('%s (%s)', gethostname(), $_SERVER['SERVER_ADDR']));
	}

	if($template == "header.tpl") $template = "header-cobalt.tpl";
	if($template == "footer.tpl") $template = "footer-cobalt.tpl";


	if(($template == "footer-cobalt.tpl") AND (isset($_GET['profile']) OR isset($_POST['profile']))) {

		$run_id = api_misc_profiling_save();

		if(defined('PROFILE_USE')) $profileuse = PROFILE_USE;
		else $profileuse = "morpheus";

		api_templates_assign("profiledata", "<a href='/xhprof_html/?run=" . $run_id . "&source=" . $profileuse . "'>profile</a>");

	}

	// Cache Buster
    if(defined('RELEASE_VERSION')) {
        api_templates_assign("releaseVersion", RELEASE_VERSION);
    } else {
        api_templates_assign("releaseVersion", date("YW"));
    }

	$te->display(TE_TEMPLATE_DIRECTORY . "/" . $template);

	flush();
	ob_implicit_flush(1);

	return true;

}

// Fetch

function api_templates_fetch($template){

	global $te;
	global $smarty_notifications;

	if(!isset($te)) $te = api_templates_start();

  // Check if the template variable is a full directory path.
	if(preg_match("/\//", $template)) $location = $template;
	else $location = TE_TEMPLATE_DIRECTORY . "/" . $template;

	return $te->fetch($location);


}

// Notification management

function api_templates_notify($type, $message){

	global $smarty_notifications;

	if(($type != "message") AND ($type != "notice") AND ($type != "error") AND ($type != "success")) return false;

	api_templates_assign("notification_template", TE_TEMPLATE_DIRECTORY . "/notification.tpl");

	$smarty_notifications[$type][] = $message;

	return true;

}

function api_templates_paginate($totalCount, $showing, $offset){

	$paginationsize = api_templates_paginatesize();

	api_templates_assign("paginate_template", TE_TEMPLATE_DIRECTORY . "/pagination.tpl");

	api_templates_assign("paginate_show", $showing);
	api_templates_assign("paginate_page", ceil(($offset + $paginationsize) / $paginationsize));
	api_templates_assign("paginate_pages", ceil($totalCount / $paginationsize));
	api_templates_assign("paginate_last", (ceil($totalCount / $paginationsize) - 1 ) * $paginationsize);
	api_templates_assign("paginate_count", $totalCount);
	api_templates_assign("paginate_offset", $offset + $paginationsize);
	api_templates_assign("paginate_increment", $paginationsize);


}

function api_templates_paginate2($elements, $search = false, $offset = 0, $key = false, $sortby = false){

	if(!is_array($elements)) return array();

	if(!empty($search)) {
		api_templates_assign("search", $search);

		if(is_array($key)){

			$items = array();

			foreach($key as $searchkey) $items = $items + api_misc_namesearch($elements, $search, false, $searchkey);

		} else $items = api_misc_namesearch($elements, $search, false, $key);

	} else {

		api_templates_assign("search", "");

		$items = $elements;
	}

	if($sortby !== FALSE) $items = api_misc_natcasesortbykey($items, $sortby);
	else natcasesort($items);

	if(is_array($items)) $totalCount = count($items);
	else $totalCount = 0;

	$cutoff = api_templates_paginatesize();

	$showItems = array();
	$showing = 0;
	$i = 0;

	if(is_array($items)) {
		foreach($items as $key => $value) {

			if(($showing < $cutoff) AND ($i >= $offset)) {
				$showItems[$key] = $value;
				$showing++;
			}
			$i++;
			if($showing >= $cutoff) break;

		}

	}

	api_templates_assign("paginate_template", TE_TEMPLATE_DIRECTORY . "/pagination.tpl");

	api_templates_assign("paginate_show", $showing);
	api_templates_assign("paginate_page", ceil(($offset + $cutoff) / $cutoff));
	api_templates_assign("paginate_pages", ceil($totalCount / $cutoff));
	api_templates_assign("paginate_last", (ceil($totalCount / $cutoff) - 1 ) * $cutoff);
	api_templates_assign("paginate_count", $totalCount);
	api_templates_assign("paginate_offset", $offset + $cutoff);
	api_templates_assign("paginate_increment", $cutoff);

	return $showItems;

}

function api_templates_paginatesize(){

	$paginationsize = api_users_setting_getsingle($_SESSION['userid'], "guipagination");

	if(!($paginationsize > 0)) return GUI_PAGINATE_LIMIT;
	else return $paginationsize;

}

?>