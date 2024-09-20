<?php

class ReachTEL_Sniffs_Operators_LogicalOperatorsLineBreakSniff implements PHP_CodeSniffer_Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return PHP_CodeSniffer_Tokens::$booleanOperators;
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

		if (isset($tokens[$stackPtr + 1]) && $tokens[$stackPtr + 1]['code'] == T_WHITESPACE) {
			if (preg_match('/[\t ]*\n/', $tokens[$stackPtr + 1]['content'])) {
				$error = 'Newline detected after logical operator, logical operators should be on the following line';
				$phpcsFile->addError($error, $stackPtr);
			}
		}
	}
}
