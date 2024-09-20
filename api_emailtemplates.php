<?php

require_once("api_db.php");

// Add or Update email templates

/**
 * @param $template_name
 * @return mixed
 */
function api_emailtemplates_sanitise_template_name($template_name){
	return filter_var(strip_tags($template_name), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
}

/**
 * @param $emailtemplate
 * @param null $user_group_id
 * @return bool|false|int
 */
function api_emailtemplates_add($emailtemplate, $user_group_id = null){

	// Even though preg_match is used for emailtemplate validation still we have used filter_var to fix fortify issue.
	$emailtemplate = api_emailtemplates_sanitise_template_name($emailtemplate);
	if(!preg_match("/^([a-z0-9\-_]){4,50}$/i", $emailtemplate)) return api_error_raise("Sorry, that is not a valid email template name");

	if(api_emailtemplates_checknameexists($emailtemplate)) return api_error_raise("Sorry, an email template with the name '" . $emailtemplate . "' already exists");

	$lastid = api_keystore_increment("EMAILTEMPLATES", 0, "nextid");

	api_emailtemplates_setting_set($lastid, "name", $emailtemplate);
	api_emailtemplates_setting_set($lastid, "groupowner", $user_group_id === null ? 2 : $user_group_id);
	api_emailtemplates_setting_set($lastid, "version", 1);

	copy(READ_LOCATION . EMAILTEMPLATE_LOCATION . "/default-html.tpl", SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $emailtemplate . ".tpl");
	copy(READ_LOCATION . EMAILTEMPLATE_LOCATION . "/default-text.tpl", SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-text-" . $emailtemplate . ".tpl");

	chmod(SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $emailtemplate . ".tpl", 0664);
	chmod(SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-text-" . $emailtemplate . ".tpl", 0664);

	return $lastid;

}

/**
 *
 * Renames the given template id's to $new_name including updating the DB and template file names.
 *
 * Attempts to be resilient and atomic.
 *
 * @param $template_id
 * @param $new_name
 * @return bool
 * @throws Exception
 */
function api_emailtemplates_rename_template($template_id, $new_name){

	if (!isset($template_id) || !api_emailtemplates_checkidexists($template_id)){
		throw new InvalidArgumentException("A valid template id must be specified");
	}

	$new_name = api_emailtemplates_sanitise_template_name($new_name);
	if(!preg_match("/^([a-z0-9\-_]){4,50}$/i", $new_name)){
		throw new InvalidArgumentException("That template name is invalid");
	}
	
	if (!$new_name){
		throw new InvalidArgumentException("A new template name must be specified");
	}

	if (api_emailtemplates_checknameexists($new_name)){
		throw new InvalidArgumentException("Template name already exists: ".$new_name);
	}

	$existing_name = api_emailtemplates_setting_getsingle($template_id, "name");

	$new_html_path = SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $new_name . ".tpl";
	$old_html_path = SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $existing_name . ".tpl";
	$new_text_path = SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-text-" . $new_name . ".tpl";
	$old_text_path = SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-text-" . $existing_name . ".tpl";

	if (file_exists($new_html_path)) {
		throw new RuntimeException("A template with the same name already exists in the filesystem: ".$new_html_path);
	}
	if(file_exists($new_text_path)){
		throw new RuntimeException("A template with the same name already exists in the filesystem: ".$new_text_path);
	}

	if(!copy($old_html_path, $new_html_path)){
		throw new RuntimeException("Could not copy template file: ".$new_html_path);
	}

	if(!copy($old_text_path, $new_text_path)){
		unlink($new_html_path);
		throw new RuntimeException("Could not copy template file: ".$old_text_path);
	}

	if(!chmod($new_html_path, 0664) || !chmod($new_text_path, 0664)){
		unlink($new_text_path);
		unlink($new_html_path);
		throw new RuntimeException("Could not update the template files permissions");
	}

	if(!api_emailtemplates_setting_set($template_id, "name", $new_name)) {
		unlink($new_text_path);
		unlink($new_html_path);
		throw new RuntimeException("Could not update the template name ".$new_name." in the database");
	}

	unlink($old_html_path);
	unlink($old_text_path);
	return true;

}

// Check if email template already assigned

function api_emailtemplates_checknameexists($emailtemplate){ return api_keystore_checkkeyexists("EMAILTEMPLATES", "name", $emailtemplate); }

// Check if the current email template id exists

function api_emailtemplates_checkidexists($emailtemplateid){

	if(!is_numeric($emailtemplateid)) return false;

	if(api_keystore_get("EMAILTEMPLATES", $emailtemplateid, "name") !== FALSE) return true;
	else return false;

}

function api_emailtemplates_nametoid($name){

	$id = api_emailtemplates_checknameexists($name);

	if(is_numeric($id)) return $id;
	else return false;

}

// Delete email template

function api_emailtemplates_delete($emailtemplate){

	if(!api_emailtemplates_checkidexists($emailtemplate)) return false;

	$name = api_emailtemplates_setting_getsingle($emailtemplate, "name");

	api_keystore_purge("EMAILTEMPLATES", $emailtemplate);
    // Unlink dial plan file
	$html_path = SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $name . ".tpl";
	$text_path = SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-text-" . $name . ".tpl";

	if(file_exists($html_path)) {
		unlink($html_path);
	}
	if(file_exists($text_path)) {
		unlink($text_path);
	}
	return true;

}

// List email template files
function api_emailtemplates_listall($short = 0, $checkUser = false){

	$emailtemplates = api_keystore_getids("EMAILTEMPLATES", "name", true);

	$emailtemplate = array();

	if($emailtemplates !== FALSE){


		if($checkUser) {
			$groups = api_security_groupaccess($_SESSION['userid']);
			$groupowner = api_keystore_getids("EMAILTEMPLATES", "groupowner", true);
		}


		if($emailtemplates) foreach($emailtemplates as $id => $name){

			if($checkUser AND ($groups["isadmin"] OR in_array($groupowner[$id], $groups["groups"]))) $emailtemplate[$id] = $name;
			elseif(!$checkUser) $emailtemplate[$id] = $name;


		}

	}

	if($emailtemplate) natcasesort($emailtemplate);

	return $emailtemplate;
}

/**
 * Returns an array of the given group's template ids
 *
 * @param $group_id
 * @return array|false
 */
function api_emailtemplates_get_group_template_ids($group_id){
	if(!is_numeric($group_id)){
		throw new InvalidArgumentException("Group id must be specified");
	}

	return api_keystore_getidswithvalue("EMAILTEMPLATES", "groupowner", $group_id);
}

function api_emailtemplates_has_user_got_access($user_id, $email_template_id) {
	return api_users_has_access_to_module($user_id, $email_template_id, 'EMAILTEMPLATES');
}

function api_emailtemplates_list_user_groups_that_can_update($user_id) {
	$groups = api_security_groupaccess($user_id);
	return ($groups['isadmin']) ? api_groups_listall() : api_groups_listall_for_user($user_id);
}

/**
 * Takes email template content and returns an array that describes the length of the string
 *
 * @param string $content
 * @return array
 */
function api_emailtemplates_content($emailtemplateid) {

	$regex = "/\[%([^%]+)%\]/i";

	if(!api_emailtemplates_checkidexists($emailtemplateid)) {
		return api_error_raise("Sorry, that is not a valid email template id");
	}

	$emailtemplate = api_emailtemplates_setting_getsingle($emailtemplateid, "name");

	$content = file_get_contents(SAVE_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $emailtemplate . ".tpl");

	$hosttrack = api_hosts_gettrack();
	$result = array("contentlink" => "{$hosttrack}/view.php?tv=" . api_misc_crypt_safe($emailtemplateid),
		"hasmergefields" => false,
		"mergefields" => []);

    if(empty($content)) return $result;

    preg_replace($regex, "", $content, -1, $count);

    // These fields are automatically generated so aren't user supplied merge fields
    // We will add each found merge field to this array to de-dupe any future merge fields
    $skip = ["targetkey", "targetid", "destination", "campaignid", "enctargetid", "rt-date", "rt-time"];

    if($count) {
		$result["hasmergefields"] = true;

		preg_match_all($regex, $content, $matches);

		foreach($matches[1] as $key => $match) {
        	if (preg_match("/^(.*)\|(.*)$/", $match, $fallbackMatches)) {
        		if(in_array($fallbackMatches[1], $skip)) {
        			continue;
        		}
        		$skip[] = $fallbackMatches[1];
                $result["mergefields"][] = [
					"field" => $fallbackMatches[1],
					"fallback" => $fallbackMatches[2],
				];
        	} else {
        		if(in_array($match, $skip)) {
        			continue;
        		}
        		$skip[] = $match;
                $result["mergefields"][] = [
					"field" => $match,
					"fallback" => false,
                ];
        	}
		}

    }

    return $result;
}

// Email template settings

  // Add or update setting

function api_emailtemplates_setting_set($emailtemplateid, $setting, $value) { return api_keystore_set("EMAILTEMPLATES", $emailtemplateid, $setting, $value); }

  // Delete setting

    // Single
function api_emailtemplates_setting_delete_single($emailtemplateid, $setting) { return api_keystore_delete("EMAILTEMPLATES", $emailtemplateid, $setting); }

  // Get

    // Single

function api_emailtemplates_setting_getsingle($emailtemplateid, $setting) { return api_keystore_get("EMAILTEMPLATES", $emailtemplateid, $setting); }

    // All

function api_emailtemplates_setting_getall($emailtemplateid) { return api_keystore_getnamespace("EMAILTEMPLATES", $emailtemplateid); }
