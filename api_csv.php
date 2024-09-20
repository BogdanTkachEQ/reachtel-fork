<?php
/**
 * CSV Functions
 *
 * @author			dave.ertel@equifax.com
 * @copyright		ReachTel (ABN 40 133 677 933)
 */

define('API_CSV_HANDLER', 'php://temp/maxmemory:134217728');

/**
 * Generate CSV line from array
 *
 * @param array  $row
 * @param string $delimiter
 * @param string $enclosure
 * @param string $escape_char
 * @param string $eol
 * @return false | string
 */
function api_csv_line(array $row, $delimiter = ",", $enclosure = '"', $escape_char = "\\", $eol = PHP_EOL) {
	$handle = fopen(API_CSV_HANDLER, 'w+');

	$result = api_csv_fputcsv_eol($handle, $row, $delimiter, $enclosure, $escape_char, $eol);

	if ($result !== false) {
		rewind($handle);
		$result = stream_get_contents($handle);
		fclose($handle);
	}

	return $result;
}

/**
 * Write CSV data to a handle from array
 *
 * @param resource $handle
 * @param array    $data
 * @param string   $delimiter
 * @param string   $enclosure
 * @param string   $escape_char
 * @param string   $eol
 * @return false | int
 */
function api_csv_handle($handle, array $data, $delimiter = ",", $enclosure = '"', $escape_char = "\\", $eol = PHP_EOL) {
	if (! is_resource($handle)) {
		return false;
	}

	$result = 0;
	foreach ($data as $row) {
		$rowresult = api_csv_fputcsv_eol($handle, (array) $row, $delimiter, $enclosure, $escape_char, $eol);

		if ($rowresult === false) {
			return false;
		}

		$result += $rowresult;
	}

	return $result;
}

/**
 * @param resource $handle
 * @param array    $row
 * @param string   $delimiter
 * @param string   $enclosure
 * @param string   $escape_char
 * @param string   $eol
 * @return boolean | integer
 */
function api_csv_fputcsv_eol($handle, array $row, $delimiter = ",", $enclosure = '"', $escape_char = "\\", $eol = PHP_EOL) {
    $result = fputcsv($handle, $row, $delimiter, $enclosure, $escape_char);
    if($eol !== PHP_EOL && fseek($handle, 0 - strlen(PHP_EOL), SEEK_CUR) === 0) {
        fwrite($handle, $eol);
        $result += (strlen($eol) - strlen(PHP_EOL));
    }

    return $result;
}

/**
 * Write CSV data to a file from array
 *
 * @param string  $filename
 * @param array   $data
 * @param string  $delimiter
 * @param string  $enclosure
 * @param string  $escape_char
 * @param boolean $append
 * @param string  $eol
 * @return false | int
 */
function api_csv_file($filename, array $data, $delimiter = ",", $enclosure = '"', $escape_char = "\\", $append = false, $eol = PHP_EOL) {
	$handle = fopen($filename, $append ? 'c' : 'w');
	if ($append) {
		fseek($handle, 0, SEEK_END);
	}
	$result = api_csv_handle($handle, $data, $delimiter, $enclosure, $escape_char, $eol);
	fclose($handle);

	return $result;
}

/**
 * Return CSV data as a string from array
 *
 * @param array  $data
 * @param string $delimiter
 * @param string $enclosure
 * @param string $escape_char
 * @param string $eol
 * @return false | string
 */
function api_csv_string(array $data, $delimiter = ",", $enclosure = '"', $escape_char = "\\", $eol = PHP_EOL) {
	$handle = fopen(API_CSV_HANDLER, 'w+');

	$result = api_csv_handle($handle, $data, $delimiter, $enclosure, $escape_char, $eol);
	if ($result === false) {
		return false;
	}

	rewind($handle);
	$result = stream_get_contents($handle, $result - strlen($eol));
	fclose($handle);

	return $result;
}

/**
 * Add CSV colum names for each rows
 *
 * @param array  $data
 * @param string $sort
 * @return array
 */
function api_csv_add_row_keys(array $data, $sort = false) {
	if (!$data) {
		return $data;
	}

	$rows = [];
	$headers = array_shift($data);

	if (in_array($sort = strtolower($sort), ['asc', 'desc'])) {
		$sort == 'asc' ? asort($headers) : arsort($headers);
	}

	foreach ($data as $i => $row) {
		foreach ($headers as $k => $header) {
			$rows[$i][$header] = $row[$k];
		}
	}

	array_unshift($rows, array_values($headers));

	return $rows;
}
