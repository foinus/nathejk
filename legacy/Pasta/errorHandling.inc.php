<?php

/**
 * This function should be specified as argument to set_error_handler().
 * It prints out nice error messages.
 * @package  Pasta
 */
function pasta_errorHandling_handleError($errorNumber, $message, $fileName, $lineNumber, $vars, $trace = null)
{
    // Check whether this kind of error should be reported.
    // Note that e.g. Smarty deliberately lowers the error reporting level.
    if (!($errorNumber & error_reporting()) ||
        $errorNumber == E_STRICT &&
        (strpos($fileName, '/pear/') !== false ||
         strpos($fileName, '/smarty/') !== false ||
         preg_match('/^Non-static method (DB|HTTP|Mail|Mail_RFC822|PEAR)::/', $message))) {

        if ($errorNumber & (E_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR)) {
            exit;
        }
        return;
    }

    // We cannot require_once the Pasta_Debug_Error class at all places (doing
    // so may trigger a fatal error), so we cannot use it for displaying
    // certain errors.
    if ($errorNumber == E_STRICT &&
        !class_exists('Pasta_Debug_Error', false) &&
        $message == 'Assigning the return value of new by reference is deprecated') {

        print "Strict warning: $message in $fileName on line $lineNumber";
        return;
    }

    // We cannot have E_STRICT error reporting when using PEAR
    // (Pasta_Debug_Error uses Pasta_TableRow that uses PEAR's DB),
    // so we lower it for a while.
    $oldLevel = error_reporting(E_ALL);

    $trace = debug_backtrace();
    if (isset($trace[0]['function']) && $trace[0]['function'] == __FUNCTION__) {
        if (isset($trace[0]['file'])) {
            // When e.g. an "Undefined variable: foo" error occurs, the first
            // frame is the error handler itself, but the 'file' and 'line'
            // entries point to the line containing the undefined variable.
            unset($trace[0]['function']);
            unset($trace[0]['args']);
        } else {
            // When a built-in PHP function triggers an error, the first frame
            // is the error handler itself, and the second frame the function
            // that generated the error.
            array_shift($trace);
        }
    }

    require_once 'Pasta/Debug/Error.class.php';
    $error = new Pasta_Debug_Error();
    $error->errorNumber = $errorNumber;
    $error->message     = $message;
    $error->fileName    = $fileName;
    $error->lineNumber  = $lineNumber;
    $error->globals     = Pasta_Debug::getGlobals();
    $error->stackTrace  = $trace;

    $error->handle(true);

    error_reporting($oldLevel);
}

/**
 * This function should be specified as argument to set_exception_handler().
 * @package  Pasta
 */
function pasta_errorHandling_handleException($exception)
{
    $trace = $exception->getTrace();
    // Add artificial frame to make the trace identical to that generated by
    // debug_backtrace() in pasta_errorHandling_handleError
    $frame = array(
        'file' => $exception->getFile(),
        'line' => $exception->getLine());
    array_unshift($trace, $frame);

    require_once 'Pasta/Debug/Error.class.php';
    try {
        $error = new Pasta_Debug_Error();
        $error->message     = $exception->getMessage();
        $error->errorNumber = E_USER_ERROR;
        $error->filename    = $exception->getFile();
        $error->lineNumber  = $exception->getLine();
        $error->stackTrace  = $trace;

        // Handle exits the script, unless an exception is thrown
        $error->handle(true);
    } catch (Exception $e) {
        // The error handler should not throw exceptions, even if the
        // database is unavailable, so this is just an extra precaution
        if (isset($_SERVER['PEYTZ_DEV'])) {
            print '<p><b>Error</b>: Error handler threw an exception:</p>';
            print Pasta_Debug::stackTraceToHtml($e->getTrace(), true);
            print '<p>Original exception was:</p>';
            print Pasta_Debug::stackTraceToHtml($trace, true);
        }
    }
    print("\nInternal server error (while executing error handler)\nexit(75)");
    exit(75); // 75 makes postfix keep mail on its queue and try again instead of bouncing it
}

set_exception_handler('pasta_errorHandling_handleException');

// As long as PEAR isn't E_STRICT compatible, it would break projects not using
// this error handler, if E_STRICT was specified in php.inc.
if (isset($_SERVER['PEYTZ_DEV'])) {
    error_reporting(E_ALL /*| E_STRICT*/);
    set_error_handler('pasta_errorHandling_handleError');
} else {
    // Save a bit of time by not handling E_STRICT errors
    set_error_handler('pasta_errorHandling_handleError', E_ALL);
}

?>
