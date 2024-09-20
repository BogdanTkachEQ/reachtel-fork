<?php

// http://stackoverflow.com/questions/20137317/what-built-in-codesniff-could-i-use-to-ensure-there-are-no-spaces-before-parenth

class ReachTEL_Sniffs_Methods_MethodDeclarationSniff implements PHP_CodeSniffer_Sniff {

	public function register() {
		return [ T_FUNCTION, T_CLOSURE ];
	}

	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {

		$tokens = $phpcsFile->getTokens();

		while ($tokens[$stackPtr]['code'] != T_OPEN_PARENTHESIS) {
			$stackPtr++;
		}

		if ($tokens[$stackPtr - 1]['code'] == T_WHITESPACE) {
			$place = $tokens[$stackPtr - 2]['content'] . $tokens[$stackPtr - 1]['content'] . $tokens[$stackPtr]['content'];
			$error = "Space(s) between function name and opening parenthesis detected, found \"$place\"";
			$phpcsFile->addError($error, $stackPtr - 1);
		}
	}
}
