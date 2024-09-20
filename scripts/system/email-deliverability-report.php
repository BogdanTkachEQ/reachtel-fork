<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(113);

// Numbers of days back
$days = isset($tags['days-back']) ? $tags['days-back'] : 31;
// List of response data to count. Note you can add math operations (e.g: TRACK_UNIQUE/SENT*100)
$responses = isset($tags['response-list']) ? explode(',', $tags['response-list']) : ['SENT', 'TRACK'];
// Group the data by month or days etc...
$date_group_format = isset($tags['group-date-format']) ? $tags['group-date-format'] : '%Y-%m-%d';

print "Extracting deliverability email data from the past {$days} days with:\n" .
	" > values: " . implode(', ', $responses) . "\n" .
	" > group format: {$date_group_format}\n\n";

// split email action response data and math operations
$actions = preg_grep('/^[A-Z]+$/', $responses);
$maths = array_diff($responses, $actions);

$data = api_email_deliverability_data($days, $actions, $date_group_format);

if(!$data) {
	print "ERROR: No data could be found\n";
	exit;
}

// add date in data to generate csv
$csv = [];
foreach($data as $date => $day_data) {
	$row = [];
	foreach($day_data as $name => $ids) {
		$name = strtoupper($name);
		$ids = explode(',', $ids);
		$row[$name] = count($ids);
		$row["{$name}_UNIQUE"] = count(array_unique($ids));
	}

	if ($maths) {
		// Longest keys first for replacing
		$replace = $row;
		uksort($replace, function($a, $b) {
			return strlen($b) >= strlen($a);
		});

		// apply maths to existing values
		foreach($maths as $k => $math) {
			$math = str_replace(array_keys($replace), $replace, $math);
			// check this is a valid operation
			if (!preg_match('/^[\d\+\-\*\/]+$/', $math)) {
				print "WARNING: '{$math}' is not a valid operation!\n";
				unset($maths[$k]);
				continue;
			}
			$sql = sprintf('SELECT (%s) AS VAL;', str_replace(array_keys($replace), $replace, $math));
			$rs = api_db_query_read($sql);
			if ($rs) {
				$row[$math] = $rs->Fields("VAL");
			}
		}
	}

	$csv[] = ['DATE' => $date] + $row;
}

// headers
array_unshift($csv, array_keys(current($csv)));
$content = api_csv_string($csv);

$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] Email deliverability Report - " . date("d/m/Y");
$email["textcontent"] = "Hello,\n\nPlease find attached the email deliverability report.";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["attachments"] = [[
	"content" => $content,
	"filename" => "ReachTEL-Deliverability-Report-" . date("Y-m-d") . ".csv"
]];

api_email_template($email);

print "Email sent to {$tags["reporting-destination"]}.\n";
