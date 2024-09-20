<?php 
/** 
* Highlights a text by searching a word in it. 
*/ 
function smarty_modifier_highlight($string = '', $search = '') 
{ 
	if(empty($string) OR empty($search)) return $string;

	$needle = str_replace("\\*", ".+", preg_quote($search, "/"));

	return preg_replace("/(" . $needle . ")/i", "<strong>\$1</strong>", $string);

} 