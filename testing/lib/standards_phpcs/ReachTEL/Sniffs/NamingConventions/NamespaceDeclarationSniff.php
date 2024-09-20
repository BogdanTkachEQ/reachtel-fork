<?php

class ReachTEL_Sniffs_NamingConventions_NamespaceDeclarationSniff  implements PHP_CodeSniffer_Sniff {
	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(T_NAMESPACE);

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

		$end  = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
		$next = $phpcsFile->findNext(T_WHITESPACE, ($end + 1), null, true);
		$diff = ($tokens[$next]['line'] - $tokens[$end]['line'] - 1);
		if ($diff !== 1) {
			if ($diff < 0) {
				$diff = 0;
			}

			$error = 'There must be one blank line after the namespace declaration %s found;';
			$data  = array($diff);
			$phpcsFile->addError($error, $stackPtr, 'SpaceAfterNamespace', $data);
		}

	}

}