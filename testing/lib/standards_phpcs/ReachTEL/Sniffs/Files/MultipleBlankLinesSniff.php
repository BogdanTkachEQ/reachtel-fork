<?php

class ReachTEL_Sniffs_Files_MultipleBlankLinesSniff implements PHP_CodeSniffer_Sniff {

	public function register() {
		return [ T_OPEN_TAG, T_WHITESPACE ];
	}

	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {

		$tokens = $phpcsFile->getTokens();

		$original_stackPtr = $stackPtr;
		$blank_lines = 0;

		while (isset($tokens[++$stackPtr]) && $tokens[$stackPtr]['code'] == T_WHITESPACE) {
			if (preg_match('/[\t ]*\n/', $tokens[$stackPtr]['content'])) {
				$blank_lines++;
			}
		}

		if ($blank_lines > 1) {
			$error = 'Excessive blank lines detected';
			$phpcsFile->addError($error, $original_stackPtr);
		}
	}
}
