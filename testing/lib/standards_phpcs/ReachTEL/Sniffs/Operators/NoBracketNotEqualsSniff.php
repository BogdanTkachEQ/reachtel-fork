<?php

class ReachTEL_Sniffs_Operators_NoBracketNotEqualsSniff implements PHP_CodeSniffer_Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [T_IS_NOT_EQUAL];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int                  $stackPtr  The position of the current token in
	 *                                        the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		if ($tokens[$stackPtr]['content'] === '<>') {
			$error = 'Not Equals should be !== or != not <>';
			$phpcsFile->addError($error, $stackPtr, 'UseOfBrackedNotEquals');
		}
	}
}
