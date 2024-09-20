<?php

class ReachTEL_Sniffs_Namespaces_UseDeclarationSniff  implements PHP_CodeSniffer_Sniff {
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(T_USE);

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

		$uses = [];
		$use = $tokens[$stackPtr];
		while ($use) {
			if ($use['level'] > 0) break;
			$stackString = $phpcsFile->findNext(T_STRING, $stackPtr);
			$stackSemicolon = $phpcsFile->findNext(T_SEMICOLON, $stackPtr);
			$uses[$use['level']][$stackPtr] = $phpcsFile->getTokensAsString($stackString, $stackSemicolon - $stackString);
			$stackPtr = $phpcsFile->findNext(T_USE, $stackString);
			$use = $stackPtr ? $tokens[$stackPtr] : false;
		}

		foreach($uses as $uses_level) {
			// Duplicates
			if (count(array_unique($uses_level)) !== count($uses_level)) {
				$error = "Use namespace declarations have duplicates;";
				$phpcsFile->addError($error, key($uses_level), 'UseDeclarationSniff');
			}

			// Check order
			$expected_uses_level = $uses_level;
			asort($expected_uses_level);
			foreach($expected_uses_level as $expected_use) {
				$stackPtr = key($uses_level);
				$use_level = current($uses_level);
				if ($expected_use !== $use_level) {
					$basename = basename(str_replace('\\', '/', $use_level));
					$error = "Use namespace declaration '{$basename}' is not ordered;";
					$phpcsFile->addError($error, $stackPtr, 'UseDeclarationSniff');
					break;
				}
				next($uses_level);
			}
		}
	}

}