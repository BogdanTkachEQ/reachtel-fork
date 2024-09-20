<?php
// https://github.com/kukulich/php-coding-standard/blob/master/Kukulich/Sniffs/Objects/InstantiationParenthesisSniff.php

/**
 * Kukulich_Sniffs_Objects_InstantiationParenthesisSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Jaroslav Hanslík
 * @license  http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Kukulich_Sniffs_Objects_InstantiationParenthesisSniff.
 *
 * Sniffs instatiations of new objects and checks if there a parenthesises after class name.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Jaroslav Hanslík
 * @license  http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ReachTEL_Sniffs_Objects_InstantiationParenthesisSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_NEW);

    }//end register()


    /**
     * Process the tokens that this sniff is listening for.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $end         = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1), null);
        $parenthesis = $phpcsFile->findNext(T_OPEN_PARENTHESIS, ($stackPtr + 1), $end);
        $classId     = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), $end, true);

        if (false === $parenthesis) {
            $className = $tokens[$classId]['content'];
            $error     = 'Instantiation of object must be followed by parenthesis; expected "new %1$s()", found "new %1$s"';
            $phpcsFile->addError($error, $stackPtr, 'Parenthesis', array($className));
        }

    }//end process()


}//end class

?>
