<?php

class ReachTEL_Sniffs_Classes_ClassDeclarationSniff implements PHP_CodeSniffer_Sniff {

	public function register() {
		return [
			T_CLASS,
			T_INTERFACE,
		];
	}

	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$this->process_open($phpcsFile, $stackPtr);
		$this->process_close($phpcsFile, $stackPtr);
	}


	/**
	 * Processes the opening section of a class declaration.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int                  $stackPtr  The position of the current token
	 *                                        in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_open(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$this->check_opening_whitespace($phpcsFile, $stackPtr);

		// Check alignment of the keyword and braces.
		if ($tokens[($stackPtr - 1)]['code'] === T_WHITESPACE) {
			$prevContent = $tokens[($stackPtr - 1)]['content'];
			if ($prevContent !== $phpcsFile->eolChar) {
				$blankSpace = substr($prevContent, strpos($prevContent, $phpcsFile->eolChar));
				$spaces     = strlen($blankSpace);

				if (in_array($tokens[($stackPtr - 2)]['code'], array(T_ABSTRACT, T_FINAL)) === true
					&& $spaces !== 1
				) {
					$type        = strtolower($tokens[$stackPtr]['content']);
					$prevContent = strtolower($tokens[($stackPtr - 2)]['content']);
					$error       = 'Expected 1 space between %s and %s keywords; %s found';
					$data        = array(
						$prevContent,
						$type,
						$spaces,
					);
					$phpcsFile->addError($error, $stackPtr, 'SpaceBeforeKeyword', $data);
				}
			}
		}

		// We'll need the indent of the class/interface declaration for later.
		$classIndent = 0;
		for ($i = ($stackPtr - 1); $i > 0; $i--) {
			if ($tokens[$i]['line'] === $tokens[$stackPtr]['line']) {
				continue;
			}

			// We changed lines.
			if ($tokens[($i + 1)]['code'] === T_WHITESPACE) {
				$classIndent = strlen($tokens[($i + 1)]['content']);
			}

			break;
		}

		$keyword      = $stackPtr;
		$openingBrace = $tokens[$stackPtr]['scope_opener'];
		$className    = $phpcsFile->findNext(T_STRING, $stackPtr);

		$classOrInterface = strtolower($tokens[$keyword]['content']);

		// Spacing of the keyword.
		$gap = $tokens[($stackPtr + 1)]['content'];
		if (strlen($gap) !== 1) {
			$found = strlen($gap);
			$error = 'Expected 1 space between %s keyword and %s name; %s found';
			$data  = array(
				$classOrInterface,
				$classOrInterface,
				$found,
			);
			$phpcsFile->addError($error, $stackPtr, 'SpaceAfterKeyword', $data);
		}

		// Check after the class/interface name.
		$gap = $tokens[($className + 1)]['content'];
		if (strlen($gap) !== 1) {
			$found = strlen($gap);
			$error = 'Expected 1 space after %s name; %s found';
			$data  = array(
				$classOrInterface,
				$found,
			);
			$phpcsFile->addError($error, $stackPtr, 'SpaceAfterName', $data);
		}

		// Check positions of the extends and implements keywords.
		foreach (array('extends', 'implements') as $keywordType) {
			$keyword = $phpcsFile->findNext(constant('T_' . strtoupper($keywordType)), ($stackPtr + 1), $openingBrace);
			if ($keyword !== false) {
				if ($tokens[$keyword]['line'] !== $tokens[$stackPtr]['line']) {
					$error = 'The ' . $keywordType . ' keyword must be on the same line as the %s name';
					$data  = array($classOrInterface);
					$phpcsFile->addError($error, $keyword, ucfirst($keywordType) . 'Line', $data);
				}
				else {
					// Check the whitespace before. Whitespace after is checked
					// later by looking at the whitespace before the first class name
					// in the list.
					$gap = strlen($tokens[($keyword - 1)]['content']);
					if ($gap !== 1) {
						$error = 'Expected 1 space before ' . $keywordType . ' keyword; %s found';
						$data  = array($gap);
						$phpcsFile->addError($error, $keyword, 'SpaceBefore' . ucfirst($keywordType), $data);
					}
				}
			}
		}

		// Check each of the extends/implements class names. If the extends/implements
		// keyword is the last content on the line, it means we need to check for
		// the multi-line format, so we do not include the class names
		// from the extends/implements list in the following check.
		// Note that classes can only extend one other class, so they can use a
		// multi-line implements format, whereas an interface can extend multiple
		// other interfaces, and so uses a multi-line extends format.
		if ($tokens[$stackPtr]['code'] === T_INTERFACE) {
			$keywordTokenType = T_EXTENDS;
		}
		else {
			$keywordTokenType = T_IMPLEMENTS;
		}

		$implements          = $phpcsFile->findNext($keywordTokenType, ($stackPtr + 1), $openingBrace);
		$multiLineImplements = false;
		if ($implements !== false) {
			$next = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($implements + 1), $openingBrace, true);
			if ($tokens[$next]['line'] > $tokens[$implements]['line']) {
				$multiLineImplements = true;
			}
		}

		$find = array(
			T_STRING,
			$keywordTokenType,
		);

		$classNames = array();
		$nextClass  = $phpcsFile->findNext($find, ($className + 2), ($openingBrace - 1));
		while ($nextClass !== false) {
			$classNames[] = $nextClass;
			$nextClass    = $phpcsFile->findNext($find, ($nextClass + 1), ($openingBrace - 1));
		}

		$classCount         = count($classNames);
		$checkingImplements = false;
		foreach ($classNames as $i => $className) {
			if ($tokens[$className]['code'] == $keywordTokenType) {
				$checkingImplements = true;
				continue;
			}

			if ($checkingImplements === true
				&& $multiLineImplements === true
				&& ($tokens[($className - 1)]['code'] !== T_NS_SEPARATOR
					|| $tokens[($className - 2)]['code'] !== T_STRING)
			) {
				$prev = $phpcsFile->findPrevious(
					array(
						T_NS_SEPARATOR,
						T_WHITESPACE,
					),
					($className - 1),
					$implements,
					true
				);

				if ($tokens[$prev]['line'] !== ($tokens[$className]['line'] - 1)) {
					if ($keywordTokenType === T_EXTENDS) {
						$error = 'Only one interface may be specified per line in a multi-line extends declaration';
						$phpcsFile->addError($error, $className, 'ExtendsInterfaceSameLine');
					}
					else {
						$error = 'Only one interface may be specified per line in a multi-line implements declaration';
						$phpcsFile->addError($error, $className, 'InterfaceSameLine');
					}
				}
				else {
					$prev     = $phpcsFile->findPrevious(T_WHITESPACE, ($className - 1), $implements);
					$found    = strlen($tokens[$prev]['content']);
					$expected = ($classIndent + $this->indent);
					if ($found !== $expected) {
						$error = 'Expected %s spaces before interface name; %s found';
						$data  = array(
							$expected,
							$found,
						);
						$phpcsFile->addError($error, $className, 'InterfaceWrongIndent', $data);
					}
				}
			}
			elseif ($tokens[($className - 1)]['code'] !== T_NS_SEPARATOR
				|| $tokens[($className - 2)]['code'] !== T_STRING
			) {
				if ($tokens[($className - 1)]['code'] === T_COMMA
					|| ($tokens[($className - 1)]['code'] === T_NS_SEPARATOR
						&& $tokens[($className - 2)]['code'] === T_COMMA)
				) {
					$error = 'Expected 1 space before "%s"; 0 found';
					$data  = array($tokens[$className]['content']);
					$phpcsFile->addError($error, ($nextComma + 1), 'NoSpaceBeforeName', $data);
				}
				else {
					if ($tokens[($className - 1)]['code'] === T_NS_SEPARATOR) {
						$spaceBefore = strlen($tokens[($className - 2)]['content']);
					}
					else {
						$spaceBefore = strlen($tokens[($className - 1)]['content']);
					}

					if ($spaceBefore !== 1) {
						$error = 'Expected 1 space before "%s"; %s found';
						$data  = array(
							$tokens[$className]['content'],
							$spaceBefore,
						);
						$phpcsFile->addError($error, $className, 'SpaceBeforeName', $data);
					}
				}
			}

			if ($tokens[($className + 1)]['code'] !== T_NS_SEPARATOR
				&& $tokens[($className + 1)]['code'] !== T_COMMA
			) {
				if ($i !== ($classCount - 1)) {
					// This is not the last class name, and the comma
					// is not where we expect it to be.
					if ($tokens[($className + 2)]['code'] !== $keywordTokenType) {
						$error = 'Expected 0 spaces between "%s" and comma; %s found';
						$data  = array(
							$tokens[$className]['content'],
							strlen($tokens[($className + 1)]['content']),
						);
						$phpcsFile->addError($error, $className, 'SpaceBeforeComma', $data);
					}
				}

				$nextComma = $phpcsFile->findNext(T_COMMA, $className);
			}
			else {
				$nextComma = ($className + 1);
			}
		}

	}


	public function check_opening_whitespace(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		$lastToken = $tokens[$stackPtr]['scope_opener'];
		$nextToken = $phpcsFile->findNext(T_WHITESPACE, ($lastToken + 1), null, true);

		$uses_count = 0;

		while ($tokens[$nextToken]['code'] === T_USE) {
			$uses_count++;
			if ($tokens[$nextToken]['line'] !== ($tokens[$lastToken]['line'] + 1)) {
				$error = 'There must be no empty lines between uses and the start of a class';
				$data  = array($tokens[$stackPtr]['content']);
				$phpcsFile->addError($error, $lastToken, 'EmptyLineAtStartOfClass', $data);
			}

			// Find the end of the use. This could be a simple use or one a use with insteadof
			$nextSemicolon = $phpcsFile->findNext(T_SEMICOLON, ($nextToken + 1));
			$nextBracket = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, ($nextToken + 1));

			// If we have one of the braced traits then skip to the close brace
			if ($nextSemicolon > $nextBracket) {
				$lastToken = $phpcsFile->findNext(T_CLOSE_CURLY_BRACKET, ($nextToken + 1));
			}
			else {
				$lastToken = $nextSemicolon;
			}

			$nextToken = $phpcsFile->findNext(T_WHITESPACE, ($lastToken + 1), null, true);
		}

		// Ignore empty classes
		if ($tokens[$nextToken]['code'] === T_CLOSE_CURLY_BRACKET) {
			return;
		}

		// No need for space between uses and the rest of the code.  When there isn't any uses
		if ($uses_count == 0) {
			return;
		}

		if ($tokens[$nextToken]['line'] !== ($tokens[$lastToken]['line'] + 2)) {
			$error = 'There must be one empty line at the start of the class after all uses';
			$data  = array($tokens[$stackPtr]['content']);
			$phpcsFile->addError($error, $lastToken, 'EmptyLineAtStartOfClass', $data);
		}
	}

	/**
	 * Processes the closing section of a class declaration.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int                  $stackPtr  The position of the current token
	 *                                        in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process_close(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// Check that the closing brace comes right after the code body.
		$closeBrace  = $tokens[$stackPtr]['scope_closer'];
		$prevContent = $phpcsFile->findPrevious(T_WHITESPACE, ($closeBrace - 1), null, true);
		if ($prevContent !== $tokens[$stackPtr]['scope_opener']
			&& $tokens[$prevContent]['line'] !== ($tokens[$closeBrace]['line'] - 1)
		) {
			$error = 'The closing brace for the %s must go on the next line after the body';
			$data  = array($tokens[$stackPtr]['content']);
			$phpcsFile->addError($error, $closeBrace, 'CloseBraceAfterBody', $data);
		}

		// Check the closing brace is on it's own line, but allow
		// for comments like "
		$nextContent = $phpcsFile->findNext(T_COMMENT, ($closeBrace + 1), null, true);
		if ($tokens[$nextContent]['content'] !== $phpcsFile->eolChar
			&& $tokens[$nextContent]['line'] === $tokens[$closeBrace]['line']
		) {
			$type  = strtolower($tokens[$stackPtr]['content']);
			$error = 'Closing %s brace must be on a line by itself';
			$data  = array($tokens[$stackPtr]['content']);
			$phpcsFile->addError($error, $closeBrace, 'CloseBraceSameLine', $data);
		}

	}
}
