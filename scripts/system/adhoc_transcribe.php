<?php
/**
 * Automatically bulk transcribe a given directory of wav files
 * and output a csv with transcriptions
 *
 * Usage: php ./adhoc_transcribe.php [./audio] [./transcriptions.csv]
 */

require_once("Morpheus/api.php");

// https://cloud.google.com/speech-to-text/pricing
const COST_PER_UNIT = 0.006;
const UNIT_LENGTH_SECONDS = 15;

// Help
if ($argc == 2 && ($argv[1] === '-h' || $argv[1] === '--help')) {
	echo "\nAutomatically bulk transcribe a given directory of wav files and output a csv with transcriptions\n\n";
	print_usage_and_exit();
}

// Parse cli args
$audio_dir = $argc > 1 ? $argv[1] : './audio/';
$output_file = $argc > 2 ? $argv[2] : './transcriptions.csv';

// Check inputs and outputs
if (!is_dir($audio_dir)) {
	echo "Error: $audio_dir is not a readable directory\n\n";
	print_usage_and_exit();
}
$audio_dir = realpath($audio_dir);

if (file_exists($output_file) && !is_writable($output_file)) {
	echo "Error: $output_file is not writable\n\n";
	print_usage_and_exit();
}

echo "Starting transcription of ${audio_dir}/*.wav\n\n";

// Get directory contents
$filenames = scandir($audio_dir);
$fh = fopen($output_file, 'w');
$first_row = true;

if ($filenames !== false && $fh !== false) {
	$cost_units = 0;
	$file_count = 0;

	// Transcribe each file and write to CSV
	foreach ($filenames as $filename) {
		if (substr($filename, strlen($filename) - 3) !== 'wav') {
			continue;
		}

		$result = transcribe_file($filename, $audio_dir);

		// Write header row from first row keys
		if ($first_row) {
			fputcsv($fh, array_keys($result));
			$first_row = false;
		}
		fputcsv($fh, $result);

		$file_count++;

		// Keep running total of number of cost units for each file
		$cost_units += ceil((($result['hours'] * 60 * 60) + ($result['minutes'] * 60) + $result['seconds']) / UNIT_LENGTH_SECONDS);
	}

	// if we've run through all the files and have no count, they were all non-wav
	if ($file_count === 0) {
		echo "Error: No wav files found to transcribe in $audio_dir\n\n";
		print_usage_and_exit();
	}

	// Add the estimated cost after a blank row
	fputcsv($fh, []);
	fputcsv($fh, ['Total estimated cost:', 'USD$' . number_format($cost_units * COST_PER_UNIT, 2)]);

	fclose($fh);

	echo 'Successfully transcribed ' . $file_count . ' file(s) and saved report to ' . $output_file . "\n";

	api_error_printiferror();
}

/**
 * Print usage info
 *
 * @return void
 */
function print_usage_and_exit() {
	echo "Usage: php adhoc_transcribe.php [./audio] [./transcriptions.csv]\n\n";
	exit;
}

/**
 * Transcribe a given file and return transcription
 *
 * @param string $filename
 * @param string $directory
 * @param boolean $debug
 *
 * @return array
 */
function transcribe_file($filename, $directory, $debug = false) {
	echo "Transcribing " . $filename . "...\n";

	$options = [
		'filename' => $directory . '/' . $filename,
	];

	// Get info about the file using soxi
	$audio = api_audio_information($options["filename"]);

	// duration is of form 00:00:01.08 = 8640 samples ~ 81 CDDA sectors - we only care about time
	$duration = explode(':', substr($audio['duration'], 0, strpos($audio['duration'], '=') - 1));

	$hours = $duration[0];
	$minutes = $duration[1];
	$seconds = $duration[2];

	if ($debug) {
		// Rather than hit the API, it's useful to have a debug response
		$result = [
			'utterance' => 'hello you have missed an important call from first energy about the late payment of your electricity bill please call one 300 42659 for at your earliest convenience',
			'confidence' => 0.91865295,
		];
	} else {
		// Actually hit the API
		$result = api_misc_speechrecognition($options);
	}

	$transcription = 'UNABLE TO TRANSCRIBE';
	$confidence = 0;
	if (isset($result) && $result['utterance'] && $result['confidence']) {
		$transcription = $result['utterance'];
		$confidence = $result['confidence'];
	}

	echo "Success!\n";

	return [
		'filename' => $filename,
		'duration' => "${hours}h ${minutes}m ${seconds}s",
		'hours' => $hours,
		'minutes' => $minutes,
		'seconds' => $seconds,
		'modifiedTime' => date("Y-m-d H:i:s", filemtime($options['filename'])),
		'confidence' => $confidence,
		'transcription' => $transcription,
	];
}
