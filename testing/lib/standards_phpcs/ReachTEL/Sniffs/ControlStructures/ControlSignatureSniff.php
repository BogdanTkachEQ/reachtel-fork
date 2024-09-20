<?php

if (class_exists('Squiz_Sniffs_ControlStructures_ControlSignatureSniff', true) === false) {
	throw new PHP_CodeSniffer_Exception('Class Squiz_Sniffs_ControlStructures_ControlSignatureSniff not found');
}

class ReachTEL_Sniffs_ControlStructures_ControlSignatureSniff extends Squiz_Sniffs_ControlStructures_ControlSignatureSniff {
	protected function getPatterns() {
		return array(
			'try {EOL',
			'}EOLcatch (...) {EOL',
			'do {EOL',
			'}EOL...while (...);EOL',
			'while (...) {EOL',
			'for (...) {EOL',
			'if (...) {EOL',
			'foreach (...) {EOL',
			'}EOLelseif (...) {EOL',
			'}EOLelse {EOL',
		);
	}
}
