<?php
/**
 * Generic_Sniffs_NamingConventions_CamelCapsFunctionNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('PHP_CodeSniffer_Standards_AbstractScopeSniff', true) === false) {
	throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractScopeSniff not found');
}

/**
 * ReachTel_Sniffs_NamingConventions_UndercapsFunctionNameSniff
 *
 * Ensures method names are correct depending on whether they are public
 * or private, and that functions are named correctly.
 *
 * This file is a modification of the Generic_Sniffs_NamingConventions_CamelCapsFunctionNameSniff class
 * for ReachTel Lotteries which uses lowercase and underscore separation for function names.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 */
class ReachTEL_Sniffs_NamingConventions_NoMagicMethodSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{

	/**
	 * A list of all PHP magic methods.
	 *
	 * @var array
	 */
	protected $magicMethods = [
		'construct',
		'destruct',
		'call',
		'callstatic',
		'get',
		'set',
		'isset',
		'unset',
		'sleep',
		'wakeup',
		'tostring',
		'set_state',
		'clone',
		'invoke',
		'call',
	];

	/**
	 * A list of all PHP non-magic methods starting with a double underscore.
	 *
	 * These come from PHP modules such as SOAPClient.
	 *
	 * @var array
	 */
	protected $methodsDoubleUnderscore = [
		'soapcall',
		'getlastrequest',
		'getlastresponse',
		'getlastrequestheaders',
		'getlastresponseheaders',
		'getfunctions',
		'gettypes',
		'dorequest',
		'setcookie',
		'setlocation',
		'setsoapheaders',
	];

	/**
	 * A list of all PHP magic functions.
	 *
	 * @var array
	 */
	protected $magicFunctions = ['autoload'];

	/**
	 * Constructs a Generic_Sniffs_NamingConventions_CamelCapsFunctionNameSniff.
	 */
	public function __construct()
	{
		parent::__construct(array(T_CLASS, T_INTERFACE, T_TRAIT), array(T_FUNCTION), true);

	}

	/**
	 * Processes the tokens within the scope.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
	 * @param int                  $stackPtr  The position where this token was
	 *                                        found.
	 * @param int                  $currScope The position of the current scope.
	 *
	 * @return void
	 */
	protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
	{
		$methodName = $phpcsFile->getDeclarationName($stackPtr);
		if ($methodName === null) {
			// Ignore closures.
			return;
		}

		$className = $phpcsFile->getDeclarationName($currScope);
		$errorData = array($className.'::'.$methodName);

		// Is this a magic method. i.e., is prefixed with "__" ?
		if (preg_match('|^__|', $methodName) !== 0) {
			$magicPart = strtolower(substr($methodName, 2));
			if (in_array($magicPart, array_merge($this->magicMethods, $this->methodsDoubleUnderscore)) === false) {
				 $error = 'Method name "%s" is invalid; only PHP magic methods should be prefixed with a double underscore';
				 $phpcsFile->addError($error, $stackPtr, 'MethodDoubleUnderscore', $errorData);
			}

			return;
		}
	}


	/**
	 * Processes the tokens outside the scope.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
	 * @param int                  $stackPtr  The position where this token was
	 *                                        found.
	 *
	 * @return void
	 */
	protected function processTokenOutsideScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		$functionName = $phpcsFile->getDeclarationName($stackPtr);
		if ($functionName === null) {
			// Ignore closures.
			return;
		}

		$errorData = array($functionName);

		// Is this a magic function. i.e., it is prefixed with "__".
		if (preg_match('|^__|', $functionName) !== 0) {
			$magicPart = strtolower(substr($functionName, 2));
			if (in_array($magicPart, $this->magicFunctions) === false) {
				 $error = 'Function name "%s" is invalid; only PHP magic methods should be prefixed with a double underscore';
				 $phpcsFile->addError($error, $stackPtr, 'FunctionDoubleUnderscore', $errorData);
			}

			return;
		}
	}


}
