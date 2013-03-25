<?
/**
 * PEAR, the PHP Extension and Application Repository
 *
 * PEAR class and PEAR_Error class
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Sterling Hughes <sterling@php.net>
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2010 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: PEAR.php 299159 2010-05-08 22:32:52Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 0.1
 */

/**#@+
 * ERROR constants
 */
define('PEAR_ERROR_RETURN',     1);
define('PEAR_ERROR_PRINT',      2);
define('PEAR_ERROR_TRIGGER',    4);
define('PEAR_ERROR_DIE',        8);
define('PEAR_ERROR_CALLBACK',  16);
/**
 * WARNING: obsolete
 * @deprecated
 */
define('PEAR_ERROR_EXCEPTION', 32);
/**#@-*/
define('PEAR_ZE2', (function_exists('version_compare') &&
                    version_compare(zend_version(), "2-dev", "ge")));

if (substr(PHP_OS, 0, 3) == 'WIN') {
    define('OS_WINDOWS', true);
    define('OS_UNIX',    false);
    define('PEAR_OS',    'Windows');
} else {
    define('OS_WINDOWS', false);
    define('OS_UNIX',    true);
    define('PEAR_OS',    'Unix'); // blatant assumption
}

$GLOBALS['_PEAR_default_error_mode']     = PEAR_ERROR_RETURN;
$GLOBALS['_PEAR_default_error_options']  = E_USER_NOTICE;
$GLOBALS['_PEAR_destructor_object_list'] = array();
$GLOBALS['_PEAR_shutdown_funcs']         = array();
$GLOBALS['_PEAR_error_handler_stack']    = array();

@ini_set('track_errors', true);

/**
 * Base class for other PEAR classes.  Provides rudimentary
 * emulation of destructors.
 *
 * If you want a destructor in your class, inherit PEAR and make a
 * destructor method called _yourclassname (same name as the
 * constructor, but with a "_" prefix).  Also, in your constructor you
 * have to call the PEAR constructor: $this->PEAR();.
 * The destructor method will be called without parameters.  Note that
 * at in some SAPI implementations (such as Apache), any output during
 * the request shutdown (in which destructors are called) seems to be
 * discarded.  If you need to get any debug information from your
 * destructor, use error_log(), syslog() or something similar.
 *
 * IMPORTANT! To use the emulated destructors you need to create the
 * objects by reference: $obj =& new PEAR_child;
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2006 The PHP Group
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.1
 * @link       http://pear.php.net/package/PEAR
 * @see        PEAR_Error
 * @since      Class available since PHP 4.0.2
 * @link        http://pear.php.net/manual/en/core.pear.php#core.pear.pear
 */
class PEAR
{
    /**
     * Whether to enable internal debug messages.
     *
     * @var     bool
     * @access  private
     */
    var $_debug = false;

    /**
     * Default error mode for this object.
     *
     * @var     int
     * @access  private
     */
    var $_default_error_mode = null;

    /**
     * Default error options used for this object when error mode
     * is PEAR_ERROR_TRIGGER.
     *
     * @var     int
     * @access  private
     */
    var $_default_error_options = null;

    /**
     * Default error handler (callback) for this object, if error mode is
     * PEAR_ERROR_CALLBACK.
     *
     * @var     string
     * @access  private
     */
    var $_default_error_handler = '';

    /**
     * Which class to use for error objects.
     *
     * @var     string
     * @access  private
     */
    var $_error_class = 'PEAR_Error';

    /**
     * An array of expected errors.
     *
     * @var     array
     * @access  private
     */
    var $_expected_errors = array();

    /**
     * Constructor.  Registers this object in
     * $_PEAR_destructor_object_list for destructor emulation if a
     * destructor object exists.
     *
     * @param string $error_class  (optional) which class to use for
     *        error objects, defaults to PEAR_Error.
     * @access public
     * @return void
     */
    function PEAR($error_class = null)
    {
        $classname = strtolower(get_class($this));
        if ($this->_debug) {
            print "PEAR constructor called, class=$classname\n";
        }

        if ($error_class !== null) {
            $this->_error_class = $error_class;
        }

        while ($classname && strcasecmp($classname, "pear")) {
            $destructor = "_$classname";
            if (method_exists($this, $destructor)) {
                global $_PEAR_destructor_object_list;
                $_PEAR_destructor_object_list[] = &$this;
                if (!isset($GLOBALS['_PEAR_SHUTDOWN_REGISTERED'])) {
                    register_shutdown_function("_PEAR_call_destructors");
                    $GLOBALS['_PEAR_SHUTDOWN_REGISTERED'] = true;
                }
                break;
            } else {
                $classname = get_parent_class($classname);
            }
        }
    }

    /**
     * Destructor (the emulated type of...).  Does nothing right now,
     * but is included for forward compatibility, so subclass
     * destructors should always call it.
     *
     * See the note in the class desciption about output from
     * destructors.
     *
     * @access public
     * @return void
     */
    function _PEAR() {
        if ($this->_debug) {
            printf("PEAR destructor called, class=%s\n", strtolower(get_class($this)));
        }
    }

    /**
    * If you have a class that's mostly/entirely static, and you need static
    * properties, you can use this method to simulate them. Eg. in your method(s)
    * do this: $myVar = &PEAR::getStaticProperty('myclass', 'myVar');
    * You MUST use a reference, or they will not persist!
    *
    * @access public
    * @param  string $class  The calling classname, to prevent clashes
    * @param  string $var    The variable to retrieve.
    * @return mixed   A reference to the variable. If not set it will be
    *                 auto initialised to NULL.
    */
    function &getStaticProperty($class, $var)
    {
        static $properties;
        if (!isset($properties[$class])) {
            $properties[$class] = array();
        }

        if (!array_key_exists($var, $properties[$class])) {
            $properties[$class][$var] = null;
        }

        return $properties[$class][$var];
    }

    /**
    * Use this function to register a shutdown method for static
    * classes.
    *
    * @access public
    * @param  mixed $func  The function name (or array of class/method) to call
    * @param  mixed $args  The arguments to pass to the function
    * @return void
    */
    function registerShutdownFunc($func, $args = array())
    {
        // if we are called statically, there is a potential
        // that no shutdown func is registered.  Bug #6445
        if (!isset($GLOBALS['_PEAR_SHUTDOWN_REGISTERED'])) {
            register_shutdown_function("_PEAR_call_destructors");
            $GLOBALS['_PEAR_SHUTDOWN_REGISTERED'] = true;
        }
        $GLOBALS['_PEAR_shutdown_funcs'][] = array($func, $args);
    }

    /**
     * Tell whether a value is a PEAR error.
     *
     * @param   mixed $data   the value to test
     * @param   int   $code   if $data is an error object, return true
     *                        only if $code is a string and
     *                        $obj->getMessage() == $code or
     *                        $code is an integer and $obj->getCode() == $code
     * @access  public
     * @return  bool    true if parameter is an error
     */
    function isError($data, $code = null)
    {
        if (!is_a($data, 'PEAR_Error')) {
            return false;
        }

        if (is_null($code)) {
            return true;
        } elseif (is_string($code)) {
            return $data->getMessage() == $code;
        }

        return $data->getCode() == $code;
    }

    /**
     * Sets how errors generated by this object should be handled.
     * Can be invoked both in objects and statically.  If called
     * statically, setErrorHandling sets the default behaviour for all
     * PEAR objects.  If called in an object, setErrorHandling sets
     * the default behaviour for that object.
     *
     * @param int $mode
     *        One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
     *        PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
     *        PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION.
     *
     * @param mixed $options
     *        When $mode is PEAR_ERROR_TRIGGER, this is the error level (one
     *        of E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
     *
     *        When $mode is PEAR_ERROR_CALLBACK, this parameter is expected
     *        to be the callback function or method.  A callback
     *        function is a string with the name of the function, a
     *        callback method is an array of two elements: the element
     *        at index 0 is the object, and the element at index 1 is
     *        the name of the method to call in the object.
     *
     *        When $mode is PEAR_ERROR_PRINT or PEAR_ERROR_DIE, this is
     *        a printf format string used when printing the error
     *        message.
     *
     * @access public
     * @return void
     * @see PEAR_ERROR_RETURN
     * @see PEAR_ERROR_PRINT
     * @see PEAR_ERROR_TRIGGER
     * @see PEAR_ERROR_DIE
     * @see PEAR_ERROR_CALLBACK
     * @see PEAR_ERROR_EXCEPTION
     *
     * @since PHP 4.0.5
     */
    function setErrorHandling($mode = null, $options = null)
    {
        if (isset($this) && is_a($this, 'PEAR')) {
            $setmode     = &$this->_default_error_mode;
            $setoptions  = &$this->_default_error_options;
        } else {
            $setmode     = &$GLOBALS['_PEAR_default_error_mode'];
            $setoptions  = &$GLOBALS['_PEAR_default_error_options'];
        }

        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $setmode = $mode;
                $setoptions = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $setmode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $setoptions = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
    }

    /**
     * This method is used to tell which errors you expect to get.
     * Expected errors are always returned with error mode
     * PEAR_ERROR_RETURN.  Expected error codes are stored in a stack,
     * and this method pushes a new element onto it.  The list of
     * expected errors are in effect until they are popped off the
     * stack with the popExpect() method.
     *
     * Note that this method can not be called statically
     *
     * @param mixed $code a single error code or an array of error codes to expect
     *
     * @return int     the new depth of the "expected errors" stack
     * @access public
     */
    function expectError($code = '*')
    {
        if (is_array($code)) {
            array_push($this->_expected_errors, $code);
        } else {
            array_push($this->_expected_errors, array($code));
        }
        return count($this->_expected_errors);
    }

    /**
     * This method pops one element off the expected error codes
     * stack.
     *
     * @return array   the list of error codes that were popped
     */
    function popExpect()
    {
        return array_pop($this->_expected_errors);
    }

    /**
     * This method checks unsets an error code if available
     *
     * @param mixed error code
     * @return bool true if the error code was unset, false otherwise
     * @access private
     * @since PHP 4.3.0
     */
    function _checkDelExpect($error_code)
    {
        $deleted = false;
        foreach ($this->_expected_errors as $key => $error_array) {
            if (in_array($error_code, $error_array)) {
                unset($this->_expected_errors[$key][array_search($error_code, $error_array)]);
                $deleted = true;
            }

            // clean up empty arrays
            if (0 == count($this->_expected_errors[$key])) {
                unset($this->_expected_errors[$key]);
            }
        }

        return $deleted;
    }

    /**
     * This method deletes all occurences of the specified element from
     * the expected error codes stack.
     *
     * @param  mixed $error_code error code that should be deleted
     * @return mixed list of error codes that were deleted or error
     * @access public
     * @since PHP 4.3.0
     */
    function delExpect($error_code)
    {
        $deleted = false;
        if ((is_array($error_code) && (0 != count($error_code)))) {
            // $error_code is a non-empty array here; we walk through it trying
            // to unset all values
            foreach ($error_code as $key => $error) {
                $deleted =  $this->_checkDelExpect($error) ? true : false;
            }

            return $deleted ? true : PEAR::raiseError("The expected error you submitted does not exist"); // IMPROVE ME
        } elseif (!empty($error_code)) {
            // $error_code comes alone, trying to unset it
            if ($this->_checkDelExpect($error_code)) {
                return true;
            }

            return PEAR::raiseError("The expected error you submitted does not exist"); // IMPROVE ME
        }

        // $error_code is empty
        return PEAR::raiseError("The expected error you submitted is empty"); // IMPROVE ME
    }

    /**
     * This method is a wrapper that returns an instance of the
     * configured error class with this object's default error
     * handling applied.  If the $mode and $options parameters are not
     * specified, the object's defaults are used.
     *
     * @param mixed $message a text error message or a PEAR error object
     *
     * @param int $code      a numeric error code (it is up to your class
     *                  to define these if you want to use codes)
     *
     * @param int $mode      One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
     *                  PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
     *                  PEAR_ERROR_CALLBACK, PEAR_ERROR_EXCEPTION.
     *
     * @param mixed $options If $mode is PEAR_ERROR_TRIGGER, this parameter
     *                  specifies the PHP-internal error level (one of
     *                  E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
     *                  If $mode is PEAR_ERROR_CALLBACK, this
     *                  parameter specifies the callback function or
     *                  method.  In other error modes this parameter
     *                  is ignored.
     *
     * @param string $userinfo If you need to pass along for example debug
     *                  information, this parameter is meant for that.
     *
     * @param string $error_class The returned error object will be
     *                  instantiated from this class, if specified.
     *
     * @param bool $skipmsg If true, raiseError will only pass error codes,
     *                  the error message parameter will be dropped.
     *
     * @access public
     * @return object   a PEAR error object
     * @see PEAR::setErrorHandling
     * @since PHP 4.0.5
     */
    function &raiseError($message = null,
                         $code = null,
                         $mode = null,
                         $options = null,
                         $userinfo = null,
                         $error_class = null,
                         $skipmsg = false)
    {
        // The error is yet a PEAR error object
        if (is_object($message)) {
            $code        = $message->getCode();
            $userinfo    = $message->getUserInfo();
            $error_class = $message->getType();
            $message->error_message_prefix = '';
            $message     = $message->getMessage();
        }

        if (
            isset($this) &&
            isset($this->_expected_errors) &&
            count($this->_expected_errors) > 0 &&
            count($exp = end($this->_expected_errors))
        ) {
            if ($exp[0] == "*" ||
                (is_int(reset($exp)) && in_array($code, $exp)) ||
                (is_string(reset($exp)) && in_array($message, $exp))
            ) {
                $mode = PEAR_ERROR_RETURN;
            }
        }

        // No mode given, try global ones
        if ($mode === null) {
            // Class error handler
            if (isset($this) && isset($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            // Global error handler
            } elseif (isset($GLOBALS['_PEAR_default_error_mode'])) {
                $mode    = $GLOBALS['_PEAR_default_error_mode'];
                $options = $GLOBALS['_PEAR_default_error_options'];
            }
        }

        if ($error_class !== null) {
            $ec = $error_class;
        } elseif (isset($this) && isset($this->_error_class)) {
            $ec = $this->_error_class;
        } else {
            $ec = 'PEAR_Error';
        }

        if (intval(PHP_VERSION) < 5) {
            // little non-eval hack to fix bug #12147
            include 'PEAR/FixPHP5PEARWarnings.php';
            return $a;
        }

        if ($skipmsg) {
            $a = new $ec($code, $mode, $options, $userinfo);
        } else {
            $a = new $ec($message, $code, $mode, $options, $userinfo);
        }

        return $a;
    }

    /**
     * Simpler form of raiseError with fewer options.  In most cases
     * message, code and userinfo are enough.
     *
     * @param mixed $message a text error message or a PEAR error object
     *
     * @param int $code      a numeric error code (it is up to your class
     *                  to define these if you want to use codes)
     *
     * @param string $userinfo If you need to pass along for example debug
     *                  information, this parameter is meant for that.
     *
     * @access public
     * @return object   a PEAR error object
     * @see PEAR::raiseError
     */
    function &throwError($message = null, $code = null, $userinfo = null)
    {
        if (isset($this) && is_a($this, 'PEAR')) {
            $a = &$this->raiseError($message, $code, null, null, $userinfo);
            return $a;
        }

        $a = &PEAR::raiseError($message, $code, null, null, $userinfo);
        return $a;
    }

    function staticPushErrorHandling($mode, $options = null)
    {
        $stack       = &$GLOBALS['_PEAR_error_handler_stack'];
        $def_mode    = &$GLOBALS['_PEAR_default_error_mode'];
        $def_options = &$GLOBALS['_PEAR_default_error_options'];
        $stack[] = array($def_mode, $def_options);
        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $def_mode = $mode;
                $def_options = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $def_mode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $def_options = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
        $stack[] = array($mode, $options);
        return true;
    }

    function staticPopErrorHandling()
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        $setmode     = &$GLOBALS['_PEAR_default_error_mode'];
        $setoptions  = &$GLOBALS['_PEAR_default_error_options'];
        array_pop($stack);
        list($mode, $options) = $stack[sizeof($stack) - 1];
        array_pop($stack);
        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $setmode = $mode;
                $setoptions = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $setmode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $setoptions = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
        return true;
    }

    /**
     * Push a new error handler on top of the error handler options stack. With this
     * you can easily override the actual error handler for some code and restore
     * it later with popErrorHandling.
     *
     * @param mixed $mode (same as setErrorHandling)
     * @param mixed $options (same as setErrorHandling)
     *
     * @return bool Always true
     *
     * @see PEAR::setErrorHandling
     */
    function pushErrorHandling($mode, $options = null)
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        if (isset($this) && is_a($this, 'PEAR')) {
            $def_mode    = &$this->_default_error_mode;
            $def_options = &$this->_default_error_options;
        } else {
            $def_mode    = &$GLOBALS['_PEAR_default_error_mode'];
            $def_options = &$GLOBALS['_PEAR_default_error_options'];
        }
        $stack[] = array($def_mode, $def_options);

        if (isset($this) && is_a($this, 'PEAR')) {
            $this->setErrorHandling($mode, $options);
        } else {
            PEAR::setErrorHandling($mode, $options);
        }
        $stack[] = array($mode, $options);
        return true;
    }

    /**
    * Pop the last error handler used
    *
    * @return bool Always true
    *
    * @see PEAR::pushErrorHandling
    */
    function popErrorHandling()
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        array_pop($stack);
        list($mode, $options) = $stack[sizeof($stack) - 1];
        array_pop($stack);
        if (isset($this) && is_a($this, 'PEAR')) {
            $this->setErrorHandling($mode, $options);
        } else {
            PEAR::setErrorHandling($mode, $options);
        }
        return true;
    }

    /**
    * OS independant PHP extension load. Remember to take care
    * on the correct extension name for case sensitive OSes.
    *
    * @param string $ext The extension name
    * @return bool Success or not on the dl() call
    */
    function loadExtension($ext)
    {
        if (extension_loaded($ext)) {
            return true;
        }

        // if either returns true dl() will produce a FATAL error, stop that
        if (
            function_exists('dl') === false ||
            ini_get('enable_dl') != 1 ||
            ini_get('safe_mode') == 1
        ) {
            return false;
        }

        if (OS_WINDOWS) {
            $suffix = '.dll';
        } elseif (PHP_OS == 'HP-UX') {
            $suffix = '.sl';
        } elseif (PHP_OS == 'AIX') {
            $suffix = '.a';
        } elseif (PHP_OS == 'OSX') {
            $suffix = '.bundle';
        } else {
            $suffix = '.so';
        }

        return @dl('php_'.$ext.$suffix) || @dl($ext.$suffix);
    }
}

function _PEAR_call_destructors()
{
    global $_PEAR_destructor_object_list;
    if (is_array($_PEAR_destructor_object_list) &&
        sizeof($_PEAR_destructor_object_list))
    {
        reset($_PEAR_destructor_object_list);
        if (PEAR_ZE2) {
            $destructLifoExists = PEAR5::getStaticProperty('PEAR', 'destructlifo');
        } else {
            $destructLifoExists = PEAR::getStaticProperty('PEAR', 'destructlifo');
        }

        if ($destructLifoExists) {
            $_PEAR_destructor_object_list = array_reverse($_PEAR_destructor_object_list);
        }

        while (list($k, $objref) = each($_PEAR_destructor_object_list)) {
            $classname = get_class($objref);
            while ($classname) {
                $destructor = "_$classname";
                if (method_exists($objref, $destructor)) {
                    $objref->$destructor();
                    break;
                } else {
                    $classname = get_parent_class($classname);
                }
            }
        }
        // Empty the object list to ensure that destructors are
        // not called more than once.
        $_PEAR_destructor_object_list = array();
    }

    // Now call the shutdown functions
    if (
        isset($GLOBALS['_PEAR_shutdown_funcs']) &&
        is_array($GLOBALS['_PEAR_shutdown_funcs']) &&
        !empty($GLOBALS['_PEAR_shutdown_funcs'])
    ) {
        foreach ($GLOBALS['_PEAR_shutdown_funcs'] as $value) {
            call_user_func_array($value[0], $value[1]);
        }
    }
}

/**
 * Standard PEAR error class for PHP 4
 *
 * This class is supserseded by {@link PEAR_Exception} in PHP 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Gregory Beaver <cellog@php.net>
 * @copyright  1997-2006 The PHP Group
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.9.1
 * @link       http://pear.php.net/manual/en/core.pear.pear-error.php
 * @see        PEAR::raiseError(), PEAR::throwError()
 * @since      Class available since PHP 4.0.2
 */
class PEAR_Error
{
    var $error_message_prefix = '';
    var $mode                 = PEAR_ERROR_RETURN;
    var $level                = E_USER_NOTICE;
    var $code                 = -1;
    var $message              = '';
    var $userinfo             = '';
    var $backtrace            = null;

    /**
     * PEAR_Error constructor
     *
     * @param string $message  message
     *
     * @param int $code     (optional) error code
     *
     * @param int $mode     (optional) error mode, one of: PEAR_ERROR_RETURN,
     * PEAR_ERROR_PRINT, PEAR_ERROR_DIE, PEAR_ERROR_TRIGGER,
     * PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION
     *
     * @param mixed $options   (optional) error level, _OR_ in the case of
     * PEAR_ERROR_CALLBACK, the callback function or object/method
     * tuple.
     *
     * @param string $userinfo (optional) additional user/debug info
     *
     * @access public
     *
     */
    function PEAR_Error($message = 'unknown error', $code = null,
                        $mode = null, $options = null, $userinfo = null)
    {
        if ($mode === null) {
            $mode = PEAR_ERROR_RETURN;
        }
        $this->message   = $message;
        $this->code      = $code;
        $this->mode      = $mode;
        $this->userinfo  = $userinfo;

        if (PEAR_ZE2) {
            $skiptrace = PEAR5::getStaticProperty('PEAR_Error', 'skiptrace');
        } else {
            $skiptrace = PEAR::getStaticProperty('PEAR_Error', 'skiptrace');
        }

        if (!$skiptrace) {
            $this->backtrace = debug_backtrace();
            if (isset($this->backtrace[0]) && isset($this->backtrace[0]['object'])) {
                unset($this->backtrace[0]['object']);
            }
        }

        if ($mode & PEAR_ERROR_CALLBACK) {
            $this->level = E_USER_NOTICE;
            $this->callback = $options;
        } else {
            if ($options === null) {
                $options = E_USER_NOTICE;
            }

            $this->level = $options;
            $this->callback = null;
        }

        if ($this->mode & PEAR_ERROR_PRINT) {
            if (is_null($options) || is_int($options)) {
                $format = "%s";
            } else {
                $format = $options;
            }

            printf($format, $this->getMessage());
        }

        if ($this->mode & PEAR_ERROR_TRIGGER) {
            trigger_error($this->getMessage(), $this->level);
        }

        if ($this->mode & PEAR_ERROR_DIE) {
            $msg = $this->getMessage();
            if (is_null($options) || is_int($options)) {
                $format = "%s";
                if (substr($msg, -1) != "\n") {
                    $msg .= "\n";
                }
            } else {
                $format = $options;
            }
            die(sprintf($format, $msg));
        }

        if ($this->mode & PEAR_ERROR_CALLBACK && is_callable($this->callback)) {
            call_user_func($this->callback, $this);
        }

        if ($this->mode & PEAR_ERROR_EXCEPTION) {
            trigger_error("PEAR_ERROR_EXCEPTION is obsolete, use class PEAR_Exception for exceptions", E_USER_WARNING);
            eval('$e = new Exception($this->message, $this->code);throw($e);');
        }
    }

    /**
     * Get the error mode from an error object.
     *
     * @return int error mode
     * @access public
     */
    function getMode()
    {
        return $this->mode;
    }

    /**
     * Get the callback function/method from an error object.
     *
     * @return mixed callback function or object/method array
     * @access public
     */
    function getCallback()
    {
        return $this->callback;
    }

    /**
     * Get the error message from an error object.
     *
     * @return  string  full error message
     * @access public
     */
    function getMessage()
    {
        return ($this->error_message_prefix . $this->message);
    }

    /**
     * Get error code from an error object
     *
     * @return int error code
     * @access public
     */
     function getCode()
     {
        return $this->code;
     }

    /**
     * Get the name of this error/exception.
     *
     * @return string error/exception name (type)
     * @access public
     */
    function getType()
    {
        return get_class($this);
    }

    /**
     * Get additional user-supplied information.
     *
     * @return string user-supplied information
     * @access public
     */
    function getUserInfo()
    {
        return $this->userinfo;
    }

    /**
     * Get additional debug information supplied by the application.
     *
     * @return string debug information
     * @access public
     */
    function getDebugInfo()
    {
        return $this->getUserInfo();
    }

    /**
     * Get the call backtrace from where the error was generated.
     * Supported with PHP 4.3.0 or newer.
     *
     * @param int $frame (optional) what frame to fetch
     * @return array Backtrace, or NULL if not available.
     * @access public
     */
    function getBacktrace($frame = null)
    {
        if (defined('PEAR_IGNORE_BACKTRACE')) {
            return null;
        }
        if ($frame === null) {
            return $this->backtrace;
        }
        return $this->backtrace[$frame];
    }

    function addUserInfo($info)
    {
        if (empty($this->userinfo)) {
            $this->userinfo = $info;
        } else {
            $this->userinfo .= " ** $info";
        }
    }

    function __toString()
    {
        return $this->getMessage();
    }

    /**
     * Make a string representation of this object.
     *
     * @return string a string with an object summary
     * @access public
     */
    function toString()
    {
        $modes = array();
        $levels = array(E_USER_NOTICE  => 'notice',
                        E_USER_WARNING => 'warning',
                        E_USER_ERROR   => 'error');
        if ($this->mode & PEAR_ERROR_CALLBACK) {
            if (is_array($this->callback)) {
                $callback = (is_object($this->callback[0]) ?
                    strtolower(get_class($this->callback[0])) :
                    $this->callback[0]) . '::' .
                    $this->callback[1];
            } else {
                $callback = $this->callback;
            }
            return sprintf('[%s: message="%s" code=%d mode=callback '.
                           'callback=%s prefix="%s" info="%s"]',
                           strtolower(get_class($this)), $this->message, $this->code,
                           $callback, $this->error_message_prefix,
                           $this->userinfo);
        }
        if ($this->mode & PEAR_ERROR_PRINT) {
            $modes[] = 'print';
        }
        if ($this->mode & PEAR_ERROR_TRIGGER) {
            $modes[] = 'trigger';
        }
        if ($this->mode & PEAR_ERROR_DIE) {
            $modes[] = 'die';
        }
        if ($this->mode & PEAR_ERROR_RETURN) {
            $modes[] = 'return';
        }
        return sprintf('[%s: message="%s" code=%d mode=%s level=%s '.
                       'prefix="%s" info="%s"]',
                       strtolower(get_class($this)), $this->message, $this->code,
                       implode("|", $modes), $levels[$this->level],
                       $this->error_message_prefix,
                       $this->userinfo);
    }
}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
 ?>
 
 <?php
/**
 * This is only meant for PHP 5 to get rid of certain strict warning
 * that doesn't get hidden since it's in the shutdown function
 */
class PEAR5
{
    /**
    * If you have a class that's mostly/entirely static, and you need static
    * properties, you can use this method to simulate them. Eg. in your method(s)
    * do this: $myVar = &PEAR5::getStaticProperty('myclass', 'myVar');
    * You MUST use a reference, or they will not persist!
    *
    * @access public
    * @param  string $class  The calling classname, to prevent clashes
    * @param  string $var    The variable to retrieve.
    * @return mixed   A reference to the variable. If not set it will be
    *                 auto initialised to NULL.
    */
    static function &getStaticProperty($class, $var)
    {
        static $properties;
        if (!isset($properties[$class])) {
            $properties[$class] = array();
        }

        if (!array_key_exists($var, $properties[$class])) {
            $properties[$class][$var] = null;
        }

        return $properties[$class][$var];
    }
}
?>

<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stig Bakken <ssb@php.net>                                   |
// |          Chuck Hagenbuch <chuck@horde.org>                           |
// +----------------------------------------------------------------------+
//
// $Id: Socket.php,v 1.38 2008/02/15 18:24:17 chagenbu Exp $

//require_once 'PEAR.php';

define('NET_SOCKET_READ',  1);
define('NET_SOCKET_WRITE', 2);
define('NET_SOCKET_ERROR', 4);

/**
 * Generalized Socket class.
 *
 * @version 1.1
 * @author Stig Bakken <ssb@php.net>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
class Net_Socket extends PEAR {

    /**
     * Socket file pointer.
     * @var resource $fp
     */
    var $fp = null;

    /**
     * Whether the socket is blocking. Defaults to true.
     * @var boolean $blocking
     */
    var $blocking = true;

    /**
     * Whether the socket is persistent. Defaults to false.
     * @var boolean $persistent
     */
    var $persistent = false;

    /**
     * The IP address to connect to.
     * @var string $addr
     */
    var $addr = '';

    /**
     * The port number to connect to.
     * @var integer $port
     */
    var $port = 0;

    /**
     * Number of seconds to wait on socket connections before assuming
     * there's no more data. Defaults to no timeout.
     * @var integer $timeout
     */
    var $timeout = false;

    /**
     * Number of bytes to read at a time in readLine() and
     * readAll(). Defaults to 2048.
     * @var integer $lineLength
     */
    var $lineLength = 2048;

    /**
     * Connect to the specified port. If called when the socket is
     * already connected, it disconnects and connects again.
     *
     * @param string  $addr        IP address or host name.
     * @param integer $port        TCP port number.
     * @param boolean $persistent  (optional) Whether the connection is
     *                             persistent (kept open between requests
     *                             by the web server).
     * @param integer $timeout     (optional) How long to wait for data.
     * @param array   $options     See options for stream_context_create.
     *
     * @access public
     *
     * @return boolean | PEAR_Error  True on success or a PEAR_Error on failure.
     */
    function connect($addr, $port = 0, $persistent = null, $timeout = null, $options = null)
    {
        if (is_resource($this->fp)) {
            @fclose($this->fp);
            $this->fp = null;
        }

        if (!$addr) {
            return $this->raiseError('$addr cannot be empty');
        } elseif (strspn($addr, '.0123456789') == strlen($addr) ||
                  strstr($addr, '/') !== false) {
            $this->addr = $addr;
        } else {
            $this->addr = @gethostbyname($addr);
        }

        $this->port = $port % 65536;

        if ($persistent !== null) {
            $this->persistent = $persistent;
        }

        if ($timeout !== null) {
            $this->timeout = $timeout;
        }

        $openfunc = $this->persistent ? 'pfsockopen' : 'fsockopen';
        $errno = 0;
        $errstr = '';
        $old_track_errors = @ini_set('track_errors', 1);
        if ($options && function_exists('stream_context_create')) {
            if ($this->timeout) {
                $timeout = $this->timeout;
            } else {
                $timeout = 0;
            }
            $context = stream_context_create($options);

            // Since PHP 5 fsockopen doesn't allow context specification
            if (function_exists('stream_socket_client')) {
                $flags = $this->persistent ? STREAM_CLIENT_PERSISTENT : STREAM_CLIENT_CONNECT;
                $addr = $this->addr . ':' . $this->port;
                $fp = stream_socket_client($addr, $errno, $errstr, $timeout, $flags, $context);
            } else {
                $fp = @$openfunc($this->addr, $this->port, $errno, $errstr, $timeout, $context);
            }
        } else {
            if ($this->timeout) {
                $fp = @$openfunc($this->addr, $this->port, $errno, $errstr, $this->timeout);
            } else {
                $fp = @$openfunc($this->addr, $this->port, $errno, $errstr);
            }
        }

        if (!$fp) {
            if ($errno == 0 && isset($php_errormsg)) {
                $errstr = $php_errormsg;
            }
            @ini_set('track_errors', $old_track_errors);
            return $this->raiseError($errstr, $errno);
        }

        @ini_set('track_errors', $old_track_errors);
        $this->fp = $fp;

        return $this->setBlocking($this->blocking);
    }

    /**
     * Disconnects from the peer, closes the socket.
     *
     * @access public
     * @return mixed true on success or a PEAR_Error instance otherwise
     */
    function disconnect()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        @fclose($this->fp);
        $this->fp = null;
        return true;
    }

    /**
     * Find out if the socket is in blocking mode.
     *
     * @access public
     * @return boolean  The current blocking mode.
     */
    function isBlocking()
    {
        return $this->blocking;
    }

    /**
     * Sets whether the socket connection should be blocking or
     * not. A read call to a non-blocking socket will return immediately
     * if there is no data available, whereas it will block until there
     * is data for blocking sockets.
     *
     * @param boolean $mode  True for blocking sockets, false for nonblocking.
     * @access public
     * @return mixed true on success or a PEAR_Error instance otherwise
     */
    function setBlocking($mode)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $this->blocking = $mode;
        socket_set_blocking($this->fp, $this->blocking);
        return true;
    }

    /**
     * Sets the timeout value on socket descriptor,
     * expressed in the sum of seconds and microseconds
     *
     * @param integer $seconds  Seconds.
     * @param integer $microseconds  Microseconds.
     * @access public
     * @return mixed true on success or a PEAR_Error instance otherwise
     */
    function setTimeout($seconds, $microseconds)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return socket_set_timeout($this->fp, $seconds, $microseconds);
    }

    /**
     * Sets the file buffering size on the stream.
     * See php's stream_set_write_buffer for more information.
     *
     * @param integer $size     Write buffer size.
     * @access public
     * @return mixed on success or an PEAR_Error object otherwise
     */
    function setWriteBuffer($size)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $returned = stream_set_write_buffer($this->fp, $size);
        if ($returned == 0) {
            return true;
        }
        return $this->raiseError('Cannot set write buffer.');
    }

    /**
     * Returns information about an existing socket resource.
     * Currently returns four entries in the result array:
     *
     * <p>
     * timed_out (bool) - The socket timed out waiting for data<br>
     * blocked (bool) - The socket was blocked<br>
     * eof (bool) - Indicates EOF event<br>
     * unread_bytes (int) - Number of bytes left in the socket buffer<br>
     * </p>
     *
     * @access public
     * @return mixed Array containing information about existing socket resource or a PEAR_Error instance otherwise
     */
    function getStatus()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return socket_get_status($this->fp);
    }

    /**
     * Get a specified line of data
     *
     * @access public
     * @return $size bytes of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    function gets($size)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return @fgets($this->fp, $size);
    }

    /**
     * Read a specified amount of data. This is guaranteed to return,
     * and has the added benefit of getting everything in one fread()
     * chunk; if you know the size of the data you're getting
     * beforehand, this is definitely the way to go.
     *
     * @param integer $size  The number of bytes to read from the socket.
     * @access public
     * @return $size bytes of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    function read($size)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return @fread($this->fp, $size);
    }

    /**
     * Write a specified amount of data.
     *
     * @param string  $data       Data to write.
     * @param integer $blocksize  Amount of data to write at once.
     *                            NULL means all at once.
     *
     * @access public
     * @return mixed If the socket is not connected, returns an instance of PEAR_Error
     *               If the write succeeds, returns the number of bytes written
     *               If the write fails, returns false.
     */
    function write($data, $blocksize = null)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        if (is_null($blocksize) && !OS_WINDOWS) {
            return @fwrite($this->fp, $data);
        } else {
            if (is_null($blocksize)) {
                $blocksize = 1024;
            }

            $pos = 0;
            $size = strlen($data);
            while ($pos < $size) {
                $written = @fwrite($this->fp, substr($data, $pos, $blocksize));
                if ($written === false) {
                    return false;
                }
                $pos += $written;
            }

            return $pos;
        }
    }

    /**
     * Write a line of data to the socket, followed by a trailing "\r\n".
     *
     * @access public
     * @return mixed fputs result, or an error
     */
    function writeLine($data)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return fwrite($this->fp, $data . "\r\n");
    }

    /**
     * Tests for end-of-file on a socket descriptor.
     *
     * Also returns true if the socket is disconnected.
     *
     * @access public
     * @return bool
     */
    function eof()
    {
        return (!is_resource($this->fp) || feof($this->fp));
    }

    /**
     * Reads a byte of data
     *
     * @access public
     * @return 1 byte of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    function readByte()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        return ord(@fread($this->fp, 1));
    }

    /**
     * Reads a word of data
     *
     * @access public
     * @return 1 word of data from the socket, or a PEAR_Error if
     *         not connected.
     */
    function readWord()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 2);
        return (ord($buf[0]) + (ord($buf[1]) << 8));
    }

    /**
     * Reads an int of data
     *
     * @access public
     * @return integer  1 int of data from the socket, or a PEAR_Error if
     *                  not connected.
     */
    function readInt()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 4);
        return (ord($buf[0]) + (ord($buf[1]) << 8) +
                (ord($buf[2]) << 16) + (ord($buf[3]) << 24));
    }

    /**
     * Reads a zero-terminated string of data
     *
     * @access public
     * @return string, or a PEAR_Error if
     *         not connected.
     */
    function readString()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $string = '';
        while (($char = @fread($this->fp, 1)) != "\x00")  {
            $string .= $char;
        }
        return $string;
    }

    /**
     * Reads an IP Address and returns it in a dot formatted string
     *
     * @access public
     * @return Dot formatted string, or a PEAR_Error if
     *         not connected.
     */
    function readIPAddress()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $buf = @fread($this->fp, 4);
        return sprintf('%d.%d.%d.%d', ord($buf[0]), ord($buf[1]),
                       ord($buf[2]), ord($buf[3]));
    }

    /**
     * Read until either the end of the socket or a newline, whichever
     * comes first. Strips the trailing newline from the returned data.
     *
     * @access public
     * @return All available data up to a newline, without that
     *         newline, or until the end of the socket, or a PEAR_Error if
     *         not connected.
     */
    function readLine()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $line = '';
        $timeout = time() + $this->timeout;
        while (!feof($this->fp) && (!$this->timeout || time() < $timeout)) {
            $line .= @fgets($this->fp, $this->lineLength);
            if (substr($line, -1) == "\n") {
                return rtrim($line, "\r\n");
            }
        }
        return $line;
    }

    /**
     * Read until the socket closes, or until there is no more data in
     * the inner PHP buffer. If the inner buffer is empty, in blocking
     * mode we wait for at least 1 byte of data. Therefore, in
     * blocking mode, if there is no data at all to be read, this
     * function will never exit (unless the socket is closed on the
     * remote end).
     *
     * @access public
     *
     * @return string  All data until the socket closes, or a PEAR_Error if
     *                 not connected.
     */
    function readAll()
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $data = '';
        while (!feof($this->fp)) {
            $data .= @fread($this->fp, $this->lineLength);
        }
        return $data;
    }

    /**
     * Runs the equivalent of the select() system call on the socket
     * with a timeout specified by tv_sec and tv_usec.
     *
     * @param integer $state    Which of read/write/error to check for.
     * @param integer $tv_sec   Number of seconds for timeout.
     * @param integer $tv_usec  Number of microseconds for timeout.
     *
     * @access public
     * @return False if select fails, integer describing which of read/write/error
     *         are ready, or PEAR_Error if not connected.
     */
    function select($state, $tv_sec, $tv_usec = 0)
    {
        if (!is_resource($this->fp)) {
            return $this->raiseError('not connected');
        }

        $read = null;
        $write = null;
        $except = null;
        if ($state & NET_SOCKET_READ) {
            $read[] = $this->fp;
        }
        if ($state & NET_SOCKET_WRITE) {
            $write[] = $this->fp;
        }
        if ($state & NET_SOCKET_ERROR) {
            $except[] = $this->fp;
        }
        if (false === ($sr = stream_select($read, $write, $except, $tv_sec, $tv_usec))) {
            return false;
        }

        $result = 0;
        if (count($read)) {
            $result |= NET_SOCKET_READ;
        }
        if (count($write)) {
            $result |= NET_SOCKET_WRITE;
        }
        if (count($except)) {
            $result |= NET_SOCKET_ERROR;
        }
        return $result;
    }

    /**
     * Turns encryption on/off on a connected socket.
     *
     * @param bool    $enabled  Set this parameter to true to enable encryption
     *                          and false to disable encryption.
     * @param integer $type     Type of encryption. See
     *                          http://se.php.net/manual/en/function.stream-socket-enable-crypto.php for values.
     *
     * @access public
     * @return false on error, true on success and 0 if there isn't enough data and the
     *         user should try again (non-blocking sockets only). A PEAR_Error object
     *         is returned if the socket is not connected
     */
    function enableCrypto($enabled, $type)
    {
        if (version_compare(phpversion(), "5.1.0", ">=")) {
            if (!is_resource($this->fp)) {
                return $this->raiseError('not connected');
            }
            return @stream_socket_enable_crypto($this->fp, $enabled, $type);
        } else {
            return $this->raiseError('Net_Socket::enableCrypto() requires php version >= 5.1.0');
        }
    }

}
?>

<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2004, Richard Heyes                                |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard at php net>                            |
// +-----------------------------------------------------------------------+
//
// $Id: URL.php,v 1.49 2007/06/28 14:43:07 davidc Exp $
//
// Net_URL Class


class Net_URL
{
    var $options = array('encode_query_keys' => false);
    /**
    * Full url
    * @var string
    */
    var $url;

    /**
    * Protocol
    * @var string
    */
    var $protocol;

    /**
    * Username
    * @var string
    */
    var $username;

    /**
    * Password
    * @var string
    */
    var $password;

    /**
    * Host
    * @var string
    */
    var $host;

    /**
    * Port
    * @var integer
    */
    var $port;

    /**
    * Path
    * @var string
    */
    var $path;

    /**
    * Query string
    * @var array
    */
    var $querystring;

    /**
    * Anchor
    * @var string
    */
    var $anchor;

    /**
    * Whether to use []
    * @var bool
    */
    var $useBrackets;

    /**
    * PHP4 Constructor
    *
    * @see __construct()
    */
    function Net_URL($url = null, $useBrackets = true)
    {
        $this->__construct($url, $useBrackets);
    }

    /**
    * PHP5 Constructor
    *
    * Parses the given url and stores the various parts
    * Defaults are used in certain cases
    *
    * @param string $url         Optional URL
    * @param bool   $useBrackets Whether to use square brackets when
    *                            multiple querystrings with the same name
    *                            exist
    */
    function __construct($url = null, $useBrackets = true)
    {
        $this->url = $url;
        $this->useBrackets = $useBrackets;

        $this->initialize();
    }

    function initialize()
    {
        $HTTP_SERVER_VARS  = !empty($_SERVER) ? $_SERVER : $GLOBALS['HTTP_SERVER_VARS'];

        $this->user        = '';
        $this->pass        = '';
        $this->host        = '';
        $this->port        = 80;
        $this->path        = '';
        $this->querystring = array();
        $this->anchor      = '';

        // Only use defaults if not an absolute URL given
        if (!preg_match('/^[a-z0-9]+:\/\//i', $this->url)) {
            $this->protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');

            /**
            * Figure out host/port
            */
            if (!empty($HTTP_SERVER_VARS['HTTP_HOST']) && 
                preg_match('/^(.*)(:([0-9]+))?$/U', $HTTP_SERVER_VARS['HTTP_HOST'], $matches)) 
            {
                $host = $matches[1];
                if (!empty($matches[3])) {
                    $port = $matches[3];
                } else {
                    $port = $this->getStandardPort($this->protocol);
                }
            }

            $this->user        = '';
            $this->pass        = '';
            $this->host        = !empty($host) ? $host : (isset($HTTP_SERVER_VARS['SERVER_NAME']) ? $HTTP_SERVER_VARS['SERVER_NAME'] : 'localhost');
            $this->port        = !empty($port) ? $port : (isset($HTTP_SERVER_VARS['SERVER_PORT']) ? $HTTP_SERVER_VARS['SERVER_PORT'] : $this->getStandardPort($this->protocol));
            $this->path        = !empty($HTTP_SERVER_VARS['PHP_SELF']) ? $HTTP_SERVER_VARS['PHP_SELF'] : '/';
            $this->querystring = isset($HTTP_SERVER_VARS['QUERY_STRING']) ? $this->_parseRawQuerystring($HTTP_SERVER_VARS['QUERY_STRING']) : null;
            $this->anchor      = '';
        }

        // Parse the url and store the various parts
        if (!empty($this->url)) {
            $urlinfo = parse_url($this->url);

            // Default querystring
            $this->querystring = array();

            foreach ($urlinfo as $key => $value) {
                switch ($key) {
                    case 'scheme':
                        $this->protocol = $value;
                        $this->port     = $this->getStandardPort($value);
                        break;

                    case 'user':
                    case 'pass':
                    case 'host':
                    case 'port':
                        $this->$key = $value;
                        break;

                    case 'path':
                        if ($value{0} == '/') {
                            $this->path = $value;
                        } else {
                            $path = dirname($this->path) == DIRECTORY_SEPARATOR ? '' : dirname($this->path);
                            $this->path = sprintf('%s/%s', $path, $value);
                        }
                        break;

                    case 'query':
                        $this->querystring = $this->_parseRawQueryString($value);
                        break;

                    case 'fragment':
                        $this->anchor = $value;
                        break;
                }
            }
        }
    }
    /**
    * Returns full url
    *
    * @return string Full url
    * @access public
    */
    function getURL()
    {
        $querystring = $this->getQueryString();

        $this->url = $this->protocol . '://'
                   . $this->user . (!empty($this->pass) ? ':' : '')
                   . $this->pass . (!empty($this->user) ? '@' : '')
                   . $this->host . ($this->port == $this->getStandardPort($this->protocol) ? '' : ':' . $this->port)
                   . $this->path
                   . (!empty($querystring) ? '?' . $querystring : '')
                   . (!empty($this->anchor) ? '#' . $this->anchor : '');

        return $this->url;
    }

    /**
    * Adds or updates a querystring item (URL parameter).
    * Automatically encodes parameters with rawurlencode() if $preencoded
    *  is false.
    * You can pass an array to $value, it gets mapped via [] in the URL if
    * $this->useBrackets is activated.
    *
    * @param  string $name       Name of item
    * @param  string $value      Value of item
    * @param  bool   $preencoded Whether value is urlencoded or not, default = not
    * @access public
    */
    function addQueryString($name, $value, $preencoded = false)
    {
        if ($this->getOption('encode_query_keys')) {
            $name = rawurlencode($name);
        }

        if ($preencoded) {
            $this->querystring[$name] = $value;
        } else {
            $this->querystring[$name] = is_array($value) ? array_map('rawurlencode', $value): rawurlencode($value);
        }
    }

    /**
    * Removes a querystring item
    *
    * @param  string $name Name of item
    * @access public
    */
    function removeQueryString($name)
    {
        if ($this->getOption('encode_query_keys')) {
            $name = rawurlencode($name);
        }

        if (isset($this->querystring[$name])) {
            unset($this->querystring[$name]);
        }
    }

    /**
    * Sets the querystring to literally what you supply
    *
    * @param  string $querystring The querystring data. Should be of the format foo=bar&x=y etc
    * @access public
    */
    function addRawQueryString($querystring)
    {
        $this->querystring = $this->_parseRawQueryString($querystring);
    }

    /**
    * Returns flat querystring
    *
    * @return string Querystring
    * @access public
    */
    function getQueryString()
    {
        if (!empty($this->querystring)) {
            foreach ($this->querystring as $name => $value) {
                // Encode var name
                $name = rawurlencode($name);

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $querystring[] = $this->useBrackets ? sprintf('%s[%s]=%s', $name, $k, $v) : ($name . '=' . $v);
                    }
                } elseif (!is_null($value)) {
                    $querystring[] = $name . '=' . $value;
                } else {
                    $querystring[] = $name;
                }
            }
            $querystring = implode(ini_get('arg_separator.output'), $querystring);
        } else {
            $querystring = '';
        }

        return $querystring;
    }

    /**
    * Parses raw querystring and returns an array of it
    *
    * @param  string  $querystring The querystring to parse
    * @return array                An array of the querystring data
    * @access private
    */
    function _parseRawQuerystring($querystring)
    {
        $parts  = preg_split('/[' . preg_quote(ini_get('arg_separator.input'), '/') . ']/', $querystring, -1, PREG_SPLIT_NO_EMPTY);
        $return = array();

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                $value = substr($part, strpos($part, '=') + 1);
                $key   = substr($part, 0, strpos($part, '='));
            } else {
                $value = null;
                $key   = $part;
            }

            if (!$this->getOption('encode_query_keys')) {
                $key = rawurldecode($key);
            }

            if (preg_match('#^(.*)\[([0-9a-z_-]*)\]#i', $key, $matches)) {
                $key = $matches[1];
                $idx = $matches[2];

                // Ensure is an array
                if (empty($return[$key]) || !is_array($return[$key])) {
                    $return[$key] = array();
                }

                // Add data
                if ($idx === '') {
                    $return[$key][] = $value;
                } else {
                    $return[$key][$idx] = $value;
                }
            } elseif (!$this->useBrackets AND !empty($return[$key])) {
                $return[$key]   = (array)$return[$key];
                $return[$key][] = $value;
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
    * Resolves //, ../ and ./ from a path and returns
    * the result. Eg:
    *
    * /foo/bar/../boo.php    => /foo/boo.php
    * /foo/bar/../../boo.php => /boo.php
    * /foo/bar/.././/boo.php => /foo/boo.php
    *
    * This method can also be called statically.
    *
    * @param  string $path URL path to resolve
    * @return string      The result
    */
    function resolvePath($path)
    {
        $path = explode('/', str_replace('//', '/', $path));

        for ($i=0; $i<count($path); $i++) {
            if ($path[$i] == '.') {
                unset($path[$i]);
                $path = array_values($path);
                $i--;

            } elseif ($path[$i] == '..' AND ($i > 1 OR ($i == 1 AND $path[0] != '') ) ) {
                unset($path[$i]);
                unset($path[$i-1]);
                $path = array_values($path);
                $i -= 2;

            } elseif ($path[$i] == '..' AND $i == 1 AND $path[0] == '') {
                unset($path[$i]);
                $path = array_values($path);
                $i--;

            } else {
                continue;
            }
        }

        return implode('/', $path);
    }

    /**
    * Returns the standard port number for a protocol
    *
    * @param  string  $scheme The protocol to lookup
    * @return integer         Port number or NULL if no scheme matches
    *
    * @author Philippe Jausions <Philippe.Jausions@11abacus.com>
    */
    function getStandardPort($scheme)
    {
        switch (strtolower($scheme)) {
            case 'http':    return 80;
            case 'https':   return 443;
            case 'ftp':     return 21;
            case 'imap':    return 143;
            case 'imaps':   return 993;
            case 'pop3':    return 110;
            case 'pop3s':   return 995;
            default:        return null;
       }
    }

    /**
    * Forces the URL to a particular protocol
    *
    * @param string  $protocol Protocol to force the URL to
    * @param integer $port     Optional port (standard port is used by default)
    */
    function setProtocol($protocol, $port = null)
    {
        $this->protocol = $protocol;
        $this->port     = is_null($port) ? $this->getStandardPort($protocol) : $port;
    }

    /**
     * Set an option
     *
     * This function set an option
     * to be used thorough the script.
     *
     * @access public
     * @param  string $optionName  The optionname to set
     * @param  string $value       The value of this option.
     */
    function setOption($optionName, $value)
    {
        if (!array_key_exists($optionName, $this->options)) {
            return false;
        }

        $this->options[$optionName] = $value;
        $this->initialize();
    }

    /**
     * Get an option
     *
     * This function gets an option
     * from the $this->options array
     * and return it's value.
     *
     * @access public
     * @param  string $opionName  The name of the option to retrieve
     * @see    $this->options
     */
    function getOption($optionName)
    {
        if (!isset($this->options[$optionName])) {
            return false;
        }

        return $this->options[$optionName];
    }

}
?>






<?php
/**
 * Class for performing HTTP requests
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2002-2007, Richard Heyes
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2002-2007 Richard Heyes
 * @license     http://opensource.org/licenses/bsd-license.php New BSD License
 * @version     CVS: $Id: Request.php,v 1.63 2008/10/11 11:07:10 avb Exp $
 * @link        http://pear.php.net/package/HTTP_Request/
 */

/**
 * PEAR and PEAR_Error classes (for error handling)
 */
/**#@+
 * Constants for HTTP request methods
 */
define('HTTP_REQUEST_METHOD_GET',     'GET',     true);
define('HTTP_REQUEST_METHOD_HEAD',    'HEAD',    true);
define('HTTP_REQUEST_METHOD_POST',    'POST',    true);
define('HTTP_REQUEST_METHOD_PUT',     'PUT',     true);
define('HTTP_REQUEST_METHOD_DELETE',  'DELETE',  true);
define('HTTP_REQUEST_METHOD_OPTIONS', 'OPTIONS', true);
define('HTTP_REQUEST_METHOD_TRACE',   'TRACE',   true);
/**#@-*/

/**#@+
 * Constants for HTTP request error codes
 */
define('HTTP_REQUEST_ERROR_FILE',             1);
define('HTTP_REQUEST_ERROR_URL',              2);
define('HTTP_REQUEST_ERROR_PROXY',            4);
define('HTTP_REQUEST_ERROR_REDIRECTS',        8);
define('HTTP_REQUEST_ERROR_RESPONSE',        16);
define('HTTP_REQUEST_ERROR_GZIP_METHOD',     32);
define('HTTP_REQUEST_ERROR_GZIP_READ',       64);
define('HTTP_REQUEST_ERROR_GZIP_DATA',      128);
define('HTTP_REQUEST_ERROR_GZIP_CRC',       256);
/**#@-*/

/**#@+
 * Constants for HTTP protocol versions
 */
define('HTTP_REQUEST_HTTP_VER_1_0', '1.0', true);
define('HTTP_REQUEST_HTTP_VER_1_1', '1.1', true);
/**#@-*/

if (extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload'))) {
   /**
    * Whether string functions are overloaded by their mbstring equivalents
    */
    define('HTTP_REQUEST_MBSTRING', true);
} else {
   /**
    * @ignore
    */
    define('HTTP_REQUEST_MBSTRING', false);
}

/**
 * Class for performing HTTP requests
 *
 * Simple example (fetches yahoo.com and displays it):
 * <code>
 * $a = &new HTTP_Request('http://www.yahoo.com/');
 * $a->sendRequest();
 * echo $a->getResponseBody();
 * </code>
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Request
{
   /**#@+
    * @access private
    */
    /**
    * Instance of Net_URL
    * @var Net_URL
    */
    var $_url;

    /**
    * Type of request
    * @var string
    */
    var $_method;

    /**
    * HTTP Version
    * @var string
    */
    var $_http;

    /**
    * Request headers
    * @var array
    */
    var $_requestHeaders;

    /**
    * Basic Auth Username
    * @var string
    */
    var $_user;

    /**
    * Basic Auth Password
    * @var string
    */
    var $_pass;

    /**
    * Socket object
    * @var Net_Socket
    */
    var $_sock;

    /**
    * Proxy server
    * @var string
    */
    var $_proxy_host;

    /**
    * Proxy port
    * @var integer
    */
    var $_proxy_port;

    /**
    * Proxy username
    * @var string
    */
    var $_proxy_user;

    /**
    * Proxy password
    * @var string
    */
    var $_proxy_pass;

    /**
    * Post data
    * @var array
    */
    var $_postData;

   /**
    * Request body
    * @var string
    */
    var $_body;

   /**
    * A list of methods that MUST NOT have a request body, per RFC 2616
    * @var array
    */
    var $_bodyDisallowed = array('TRACE');

   /**
    * Methods having defined semantics for request body
    *
    * Content-Length header (indicating that the body follows, section 4.3 of
    * RFC 2616) will be sent for these methods even if no body was added
    *
    * @var array
    */
    var $_bodyRequired = array('POST', 'PUT');

   /**
    * Files to post
    * @var array
    */
    var $_postFiles = array();

    /**
    * Connection timeout.
    * @var float
    */
    var $_timeout;

    /**
    * HTTP_Response object
    * @var HTTP_Response
    */
    var $_response;

    /**
    * Whether to allow redirects
    * @var boolean
    */
    var $_allowRedirects;

    /**
    * Maximum redirects allowed
    * @var integer
    */
    var $_maxRedirects;

    /**
    * Current number of redirects
    * @var integer
    */
    var $_redirects;

   /**
    * Whether to append brackets [] to array variables
    * @var bool
    */
    var $_useBrackets = true;

   /**
    * Attached listeners
    * @var array
    */
    var $_listeners = array();

   /**
    * Whether to save response body in response object property
    * @var bool
    */
    var $_saveBody = true;

   /**
    * Timeout for reading from socket (array(seconds, microseconds))
    * @var array
    */
    var $_readTimeout = null;

   /**
    * Options to pass to Net_Socket::connect. See stream_context_create
    * @var array
    */
    var $_socketOptions = null;
   /**#@-*/

    /**
    * Constructor
    *
    * Sets up the object
    * @param    string  The url to fetch/access
    * @param    array   Associative array of parameters which can have the following keys:
    * <ul>
    *   <li>method         - Method to use, GET, POST etc (string)</li>
    *   <li>http           - HTTP Version to use, 1.0 or 1.1 (string)</li>
    *   <li>user           - Basic Auth username (string)</li>
    *   <li>pass           - Basic Auth password (string)</li>
    *   <li>proxy_host     - Proxy server host (string)</li>
    *   <li>proxy_port     - Proxy server port (integer)</li>
    *   <li>proxy_user     - Proxy auth username (string)</li>
    *   <li>proxy_pass     - Proxy auth password (string)</li>
    *   <li>timeout        - Connection timeout in seconds (float)</li>
    *   <li>allowRedirects - Whether to follow redirects or not (bool)</li>
    *   <li>maxRedirects   - Max number of redirects to follow (integer)</li>
    *   <li>useBrackets    - Whether to append [] to array variable names (bool)</li>
    *   <li>saveBody       - Whether to save response body in response object property (bool)</li>
    *   <li>readTimeout    - Timeout for reading / writing data over the socket (array (seconds, microseconds))</li>
    *   <li>socketOptions  - Options to pass to Net_Socket object (array)</li>
    * </ul>
    * @access public
    */
    function HTTP_Request($url = '', $params = array())
    {
        $this->_method         =  HTTP_REQUEST_METHOD_GET;
        $this->_http           =  HTTP_REQUEST_HTTP_VER_1_1;
        $this->_requestHeaders = array();
        $this->_postData       = array();
        $this->_body           = null;

        $this->_user = null;
        $this->_pass = null;

        $this->_proxy_host = null;
        $this->_proxy_port = null;
        $this->_proxy_user = null;
        $this->_proxy_pass = null;

        $this->_allowRedirects = false;
        $this->_maxRedirects   = 3;
        $this->_redirects      = 0;

        $this->_timeout  = null;
        $this->_response = null;

        foreach ($params as $key => $value) {
            $this->{'_' . $key} = $value;
        }

        if (!empty($url)) {
            $this->setURL($url);
        }

        // Default useragent
        $this->addHeader('User-Agent', 'PEAR HTTP_Request class ( http://pear.php.net/ )');

        // We don't do keep-alives by default
        $this->addHeader('Connection', 'close');

        // Basic authentication
        if (!empty($this->_user)) {
            $this->addHeader('Authorization', 'Basic ' . base64_encode($this->_user . ':' . $this->_pass));
        }

        // Proxy authentication (see bug #5913)
        if (!empty($this->_proxy_user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($this->_proxy_user . ':' . $this->_proxy_pass));
        }

        // Use gzip encoding if possible
        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && extension_loaded('zlib')) {
            $this->addHeader('Accept-Encoding', 'gzip');
        }
    }

    /**
    * Generates a Host header for HTTP/1.1 requests
    *
    * @access private
    * @return string
    */
    function _generateHostHeader()
    {
        if ($this->_url->port != 80 AND strcasecmp($this->_url->protocol, 'http') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port != 443 AND strcasecmp($this->_url->protocol, 'https') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port == 443 AND strcasecmp($this->_url->protocol, 'https') == 0 AND strpos($this->_url->url, ':443') !== false) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } else {
            $host = $this->_url->host;
        }

        return $host;
    }

    /**
    * Resets the object to its initial state (DEPRECATED).
    * Takes the same parameters as the constructor.
    *
    * @param  string $url    The url to be requested
    * @param  array  $params Associative array of parameters
    *                        (see constructor for details)
    * @access public
    * @deprecated deprecated since 1.2, call the constructor if this is necessary
    */
    function reset($url, $params = array())
    {
        $this->HTTP_Request($url, $params);
    }

    /**
    * Sets the URL to be requested
    *
    * @param  string The url to be requested
    * @access public
    */
    function setURL($url)
    {
        $this->_url = &new Net_URL($url, $this->_useBrackets);

        if (!empty($this->_url->user) || !empty($this->_url->pass)) {
            $this->setBasicAuth($this->_url->user, $this->_url->pass);
        }

        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http) {
            $this->addHeader('Host', $this->_generateHostHeader());
        }

        // set '/' instead of empty path rather than check later (see bug #8662)
        if (empty($this->_url->path)) {
            $this->_url->path = '/';
        }
    }

   /**
    * Returns the current request URL
    *
    * @return   string  Current request URL
    * @access   public
    */
    function getUrl()
    {
        return empty($this->_url)? '': $this->_url->getUrl();
    }

    /**
    * Sets a proxy to be used
    *
    * @param string     Proxy host
    * @param int        Proxy port
    * @param string     Proxy username
    * @param string     Proxy password
    * @access public
    */
    function setProxy($host, $port = 8080, $user = null, $pass = null)
    {
        $this->_proxy_host = $host;
        $this->_proxy_port = $port;
        $this->_proxy_user = $user;
        $this->_proxy_pass = $pass;

        if (!empty($user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
        }
    }

    /**
    * Sets basic authentication parameters
    *
    * @param string     Username
    * @param string     Password
    */
    function setBasicAuth($user, $pass)
    {
        $this->_user = $user;
        $this->_pass = $pass;

        $this->addHeader('Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
    }

    /**
    * Sets the method to be used, GET, POST etc.
    *
    * @param string     Method to use. Use the defined constants for this
    * @access public
    */
    function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
    * Sets the HTTP version to use, 1.0 or 1.1
    *
    * @param string     Version to use. Use the defined constants for this
    * @access public
    */
    function setHttpVer($http)
    {
        $this->_http = $http;
    }

    /**
    * Adds a request header
    *
    * @param string     Header name
    * @param string     Header value
    * @access public
    */
    function addHeader($name, $value)
    {
        $this->_requestHeaders[strtolower($name)] = $value;
    }

    /**
    * Removes a request header
    *
    * @param string     Header name to remove
    * @access public
    */
    function removeHeader($name)
    {
        if (isset($this->_requestHeaders[strtolower($name)])) {
            unset($this->_requestHeaders[strtolower($name)]);
        }
    }

    /**
    * Adds a querystring parameter
    *
    * @param string     Querystring parameter name
    * @param string     Querystring parameter value
    * @param bool       Whether the value is already urlencoded or not, default = not
    * @access public
    */
    function addQueryString($name, $value, $preencoded = false)
    {
        $this->_url->addQueryString($name, $value, $preencoded);
    }

    /**
    * Sets the querystring to literally what you supply
    *
    * @param string     The querystring data. Should be of the format foo=bar&x=y etc
    * @param bool       Whether data is already urlencoded or not, default = already encoded
    * @access public
    */
    function addRawQueryString($querystring, $preencoded = true)
    {
        $this->_url->addRawQueryString($querystring, $preencoded);
    }

    /**
    * Adds postdata items
    *
    * @param string     Post data name
    * @param string     Post data value
    * @param bool       Whether data is already urlencoded or not, default = not
    * @access public
    */
    function addPostData($name, $value, $preencoded = false)
    {
        if ($preencoded) {
            $this->_postData[$name] = $value;
        } else {
            $this->_postData[$name] = $this->_arrayMapRecursive('urlencode', $value);
        }
    }

   /**
    * Recursively applies the callback function to the value
    *
    * @param    mixed   Callback function
    * @param    mixed   Value to process
    * @access   private
    * @return   mixed   Processed value
    */
    function _arrayMapRecursive($callback, $value)
    {
        if (!is_array($value)) {
            return call_user_func($callback, $value);
        } else {
            $map = array();
            foreach ($value as $k => $v) {
                $map[$k] = $this->_arrayMapRecursive($callback, $v);
            }
            return $map;
        }
    }

   /**
    * Adds a file to form-based file upload
    *
    * Used to emulate file upload via a HTML form. The method also sets
    * Content-Type of HTTP request to 'multipart/form-data'.
    *
    * If you just want to send the contents of a file as the body of HTTP
    * request you should use setBody() method.
    *
    * @access public
    * @param  string    name of file-upload field
    * @param  mixed     file name(s)
    * @param  mixed     content-type(s) of file(s) being uploaded
    * @return bool      true on success
    * @throws PEAR_Error
    */
    function addFile($inputName, $fileName, $contentType = 'application/octet-stream')
    {
        if (!is_array($fileName) && !is_readable($fileName)) {
            return PEAR::raiseError("File '{$fileName}' is not readable", HTTP_REQUEST_ERROR_FILE);
        } elseif (is_array($fileName)) {
            foreach ($fileName as $name) {
                if (!is_readable($name)) {
                    return PEAR::raiseError("File '{$name}' is not readable", HTTP_REQUEST_ERROR_FILE);
                }
            }
        }
        $this->addHeader('Content-Type', 'multipart/form-data');
        $this->_postFiles[$inputName] = array(
            'name' => $fileName,
            'type' => $contentType
        );
        return true;
    }

    /**
    * Adds raw postdata (DEPRECATED)
    *
    * @param string     The data
    * @param bool       Whether data is preencoded or not, default = already encoded
    * @access public
    * @deprecated       deprecated since 1.3.0, method setBody() should be used instead
    */
    function addRawPostData($postdata, $preencoded = true)
    {
        $this->_body = $preencoded ? $postdata : urlencode($postdata);
    }

   /**
    * Sets the request body (for POST, PUT and similar requests)
    *
    * @param    string  Request body
    * @access   public
    */
    function setBody($body)
    {
        $this->_body = $body;
    }

    /**
    * Clears any postdata that has been added (DEPRECATED).
    *
    * Useful for multiple request scenarios.
    *
    * @access public
    * @deprecated deprecated since 1.2
    */
    function clearPostData()
    {
        $this->_postData = null;
    }

    /**
    * Appends a cookie to "Cookie:" header
    *
    * @param string $name cookie name
    * @param string $value cookie value
    * @access public
    */
    function addCookie($name, $value)
    {
        $cookies = isset($this->_requestHeaders['cookie']) ? $this->_requestHeaders['cookie']. '; ' : '';
        $this->addHeader('Cookie', $cookies . $name . '=' . $value);
    }

    /**
    * Clears any cookies that have been added (DEPRECATED).
    *
    * Useful for multiple request scenarios
    *
    * @access public
    * @deprecated deprecated since 1.2
    */
    function clearCookies()
    {
        $this->removeHeader('Cookie');
    }

    /**
    * Sends the request
    *
    * @access public
    * @param  bool   Whether to store response body in Response object property,
    *                set this to false if downloading a LARGE file and using a Listener
    * @return mixed  PEAR error on error, true otherwise
    */
    function sendRequest($saveBody = true)
    {
        if (!is_a($this->_url, 'Net_URL')) {
            return PEAR::raiseError('No URL given', HTTP_REQUEST_ERROR_URL);
        }

        $host = isset($this->_proxy_host) ? $this->_proxy_host : $this->_url->host;
        $port = isset($this->_proxy_port) ? $this->_proxy_port : $this->_url->port;

        if (strcasecmp($this->_url->protocol, 'https') == 0) {
            // Bug #14127, don't try connecting to HTTPS sites without OpenSSL
            if (version_compare(PHP_VERSION, '4.3.0', '<') || !extension_loaded('openssl')) {
                return PEAR::raiseError('Need PHP 4.3.0 or later with OpenSSL support for https:// requests',
                                        HTTP_REQUEST_ERROR_URL);
            } elseif (isset($this->_proxy_host)) {
                return PEAR::raiseError('HTTPS proxies are not supported', HTTP_REQUEST_ERROR_PROXY);
            }
            $host = 'ssl://' . $host;
        }

        // magic quotes may fuck up file uploads and chunked response processing
        $magicQuotes = ini_get('magic_quotes_runtime');
        ini_set('magic_quotes_runtime', false);

        // RFC 2068, section 19.7.1: A client MUST NOT send the Keep-Alive
        // connection token to a proxy server...
        if (isset($this->_proxy_host) && !empty($this->_requestHeaders['connection']) &&
            'Keep-Alive' == $this->_requestHeaders['connection'])
        {
            $this->removeHeader('connection');
        }

        $keepAlive = (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && empty($this->_requestHeaders['connection'])) ||
                     (!empty($this->_requestHeaders['connection']) && 'Keep-Alive' == $this->_requestHeaders['connection']);
        $sockets   = &PEAR::getStaticProperty('HTTP_Request', 'sockets');
        $sockKey   = $host . ':' . $port;
        unset($this->_sock);

        // There is a connected socket in the "static" property?
        if ($keepAlive && !empty($sockets[$sockKey]) &&
            !empty($sockets[$sockKey]->fp))
        {
            $this->_sock =& $sockets[$sockKey];
            $err = null;
        } else {
            $this->_notify('connect');
            $this->_sock =& new Net_Socket();
            $err = $this->_sock->connect($host, $port, null, $this->_timeout, $this->_socketOptions);
        }
        PEAR::isError($err) or $err = $this->_sock->write($this->_buildRequest());

        if (!PEAR::isError($err)) {
            if (!empty($this->_readTimeout)) {
                $this->_sock->setTimeout($this->_readTimeout[0], $this->_readTimeout[1]);
            }

            $this->_notify('sentRequest');

            // Read the response
            $this->_response = &new HTTP_Response($this->_sock, $this->_listeners);
            $err = $this->_response->process(
                $this->_saveBody && $saveBody,
                HTTP_REQUEST_METHOD_HEAD != $this->_method
            );

            if ($keepAlive) {
                $keepAlive = (isset($this->_response->_headers['content-length'])
                              || (isset($this->_response->_headers['transfer-encoding'])
                                  && strtolower($this->_response->_headers['transfer-encoding']) == 'chunked'));
                if ($keepAlive) {
                    if (isset($this->_response->_headers['connection'])) {
                        $keepAlive = strtolower($this->_response->_headers['connection']) == 'keep-alive';
                    } else {
                        $keepAlive = 'HTTP/'.HTTP_REQUEST_HTTP_VER_1_1 == $this->_response->_protocol;
                    }
                }
            }
        }

        ini_set('magic_quotes_runtime', $magicQuotes);

        if (PEAR::isError($err)) {
            return $err;
        }

        if (!$keepAlive) {
            $this->disconnect();
        // Store the connected socket in "static" property
        } elseif (empty($sockets[$sockKey]) || empty($sockets[$sockKey]->fp)) {
            $sockets[$sockKey] =& $this->_sock;
        }

        // Check for redirection
        if (    $this->_allowRedirects
            AND $this->_redirects <= $this->_maxRedirects
            AND $this->getResponseCode() > 300
            AND $this->getResponseCode() < 399
            AND !empty($this->_response->_headers['location'])) {


            $redirect = $this->_response->_headers['location'];

            // Absolute URL
            if (preg_match('/^https?:\/\//i', $redirect)) {
                $this->_url = &new Net_URL($redirect);
                $this->addHeader('Host', $this->_generateHostHeader());
            // Absolute path
            } elseif ($redirect{0} == '/') {
                $this->_url->path = $redirect;

            // Relative path
            } elseif (substr($redirect, 0, 3) == '../' OR substr($redirect, 0, 2) == './') {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $redirect = Net_URL::resolvePath($redirect);
                $this->_url->path = $redirect;

            // Filename, no path
            } else {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $this->_url->path = $redirect;
            }

            $this->_redirects++;
            return $this->sendRequest($saveBody);

        // Too many redirects
        } elseif ($this->_allowRedirects AND $this->_redirects > $this->_maxRedirects) {
            return PEAR::raiseError('Too many redirects', HTTP_REQUEST_ERROR_REDIRECTS);
        }

        return true;
    }

    /**
     * Disconnect the socket, if connected. Only useful if using Keep-Alive.
     *
     * @access public
     */
    function disconnect()
    {
        if (!empty($this->_sock) && !empty($this->_sock->fp)) {
            $this->_notify('disconnect');
            $this->_sock->disconnect();
        }
    }

    /**
    * Returns the response code
    *
    * @access public
    * @return mixed     Response code, false if not set
    */
    function getResponseCode()
    {
        return isset($this->_response->_code) ? $this->_response->_code : false;
    }

    /**
    * Returns the response reason phrase
    *
    * @access public
    * @return mixed     Response reason phrase, false if not set
    */
    function getResponseReason()
    {
        return isset($this->_response->_reason) ? $this->_response->_reason : false;
    }

    /**
    * Returns either the named header or all if no name given
    *
    * @access public
    * @param string     The header name to return, do not set to get all headers
    * @return mixed     either the value of $headername (false if header is not present)
    *                   or an array of all headers
    */
    function getResponseHeader($headername = null)
    {
        if (!isset($headername)) {
            return isset($this->_response->_headers)? $this->_response->_headers: array();
        } else {
            $headername = strtolower($headername);
            return isset($this->_response->_headers[$headername]) ? $this->_response->_headers[$headername] : false;
        }
    }

    /**
    * Returns the body of the response
    *
    * @access public
    * @return mixed     response body, false if not set
    */
    function getResponseBody()
    {
        return isset($this->_response->_body) ? $this->_response->_body : false;
    }

    /**
    * Returns cookies set in response
    *
    * @access public
    * @return mixed     array of response cookies, false if none are present
    */
    function getResponseCookies()
    {
        return isset($this->_response->_cookies) ? $this->_response->_cookies : false;
    }

    /**
    * Builds the request string
    *
    * @access private
    * @return string The request string
    */
    function _buildRequest()
    {
        $separator = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $querystring = ($querystring = $this->_url->getQueryString()) ? '?' . $querystring : '';
        ini_set('arg_separator.output', $separator);

        $host = isset($this->_proxy_host) ? $this->_url->protocol . '://' . $this->_url->host : '';
        $port = (isset($this->_proxy_host) AND $this->_url->port != 80) ? ':' . $this->_url->port : '';
        $path = $this->_url->path . $querystring;
        $url  = $host . $port . $path;

        if (!strlen($url)) {
            $url = '/';
        }

        $request = $this->_method . ' ' . $url . ' HTTP/' . $this->_http . "\r\n";

        if (in_array($this->_method, $this->_bodyDisallowed) ||
            (0 == strlen($this->_body) && (HTTP_REQUEST_METHOD_POST != $this->_method ||
             (empty($this->_postData) && empty($this->_postFiles)))))
        {
            $this->removeHeader('Content-Type');
        } else {
            if (empty($this->_requestHeaders['content-type'])) {
                // Add default content-type
                $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            } elseif ('multipart/form-data' == $this->_requestHeaders['content-type']) {
                $boundary = 'HTTP_Request_' . md5(uniqid('request') . microtime());
                $this->addHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
            }
        }

        // Request Headers
        if (!empty($this->_requestHeaders)) {
            foreach ($this->_requestHeaders as $name => $value) {
                $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
                $request      .= $canonicalName . ': ' . $value . "\r\n";
            }
        }

        // Method does not allow a body, simply add a final CRLF
        if (in_array($this->_method, $this->_bodyDisallowed)) {

            $request .= "\r\n";

        // Post data if it's an array
        } elseif (HTTP_REQUEST_METHOD_POST == $this->_method &&
                  (!empty($this->_postData) || !empty($this->_postFiles))) {

            // "normal" POST request
            if (!isset($boundary)) {
                $postdata = implode('&', array_map(
                    create_function('$a', 'return $a[0] . \'=\' . $a[1];'),
                    $this->_flattenArray('', $this->_postData)
                ));

            // multipart request, probably with file uploads
            } else {
                $postdata = '';
                if (!empty($this->_postData)) {
                    $flatData = $this->_flattenArray('', $this->_postData);
                    foreach ($flatData as $item) {
                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $item[0] . '"';
                        $postdata .= "\r\n\r\n" . urldecode($item[1]) . "\r\n";
                    }
                }
                foreach ($this->_postFiles as $name => $value) {
                    if (is_array($value['name'])) {
                        $varname       = $name . ($this->_useBrackets? '[]': '');
                    } else {
                        $varname       = $name;
                        $value['name'] = array($value['name']);
                    }
                    foreach ($value['name'] as $key => $filename) {
                        $fp       = fopen($filename, 'r');
                        $basename = basename($filename);
                        $type     = is_array($value['type'])? @$value['type'][$key]: $value['type'];

                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $varname . '"; filename="' . $basename . '"';
                        $postdata .= "\r\nContent-Type: " . $type;
                        $postdata .= "\r\n\r\n" . fread($fp, filesize($filename)) . "\r\n";
                        fclose($fp);
                    }
                }
                $postdata .= '--' . $boundary . "--\r\n";
            }
            $request .= 'Content-Length: ' .
                        (HTTP_REQUEST_MBSTRING? mb_strlen($postdata, 'iso-8859-1'): strlen($postdata)) .
                        "\r\n\r\n";
            $request .= $postdata;

        // Explicitly set request body
        } elseif (0 < strlen($this->_body)) {

            $request .= 'Content-Length: ' .
                        (HTTP_REQUEST_MBSTRING? mb_strlen($this->_body, 'iso-8859-1'): strlen($this->_body)) .
                        "\r\n\r\n";
            $request .= $this->_body;

        // No body: send a Content-Length header nonetheless (request #12900),
        // but do that only for methods that require a body (bug #14740)
        } else {

            if (in_array($this->_method, $this->_bodyRequired)) {
                $request .= "Content-Length: 0\r\n";
            }
            $request .= "\r\n";
        }

        return $request;
    }

   /**
    * Helper function to change the (probably multidimensional) associative array
    * into the simple one.
    *
    * @param    string  name for item
    * @param    mixed   item's values
    * @return   array   array with the following items: array('item name', 'item value');
    * @access   private
    */
    function _flattenArray($name, $values)
    {
        if (!is_array($values)) {
            return array(array($name, $values));
        } else {
            $ret = array();
            foreach ($values as $k => $v) {
                if (empty($name)) {
                    $newName = $k;
                } elseif ($this->_useBrackets) {
                    $newName = $name . '[' . $k . ']';
                } else {
                    $newName = $name;
                }
                $ret = array_merge($ret, $this->_flattenArray($newName, $v));
            }
            return $ret;
        }
    }


   /**
    * Adds a Listener to the list of listeners that are notified of
    * the object's events
    *
    * Events sent by HTTP_Request object
    * - 'connect': on connection to server
    * - 'sentRequest': after the request was sent
    * - 'disconnect': on disconnection from server
    *
    * Events sent by HTTP_Response object
    * - 'gotHeaders': after receiving response headers (headers are passed in $data)
    * - 'tick': on receiving a part of response body (the part is passed in $data)
    * - 'gzTick': on receiving a gzip-encoded part of response body (ditto)
    * - 'gotBody': after receiving the response body (passes the decoded body in $data if it was gzipped)
    *
    * @param    HTTP_Request_Listener   listener to attach
    * @return   boolean                 whether the listener was successfully attached
    * @access   public
    */
    function attach(&$listener)
    {
        if (!is_a($listener, 'HTTP_Request_Listener')) {
            return false;
        }
        $this->_listeners[$listener->getId()] =& $listener;
        return true;
    }


   /**
    * Removes a Listener from the list of listeners
    *
    * @param    HTTP_Request_Listener   listener to detach
    * @return   boolean                 whether the listener was successfully detached
    * @access   public
    */
    function detach(&$listener)
    {
        if (!is_a($listener, 'HTTP_Request_Listener') ||
            !isset($this->_listeners[$listener->getId()])) {
            return false;
        }
        unset($this->_listeners[$listener->getId()]);
        return true;
    }


   /**
    * Notifies all registered listeners of an event.
    *
    * @param    string  Event name
    * @param    mixed   Additional data
    * @access   private
    * @see      HTTP_Request::attach()
    */
    function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }
}


/**
 * Response class to complement the Request class
 *
 * @category    HTTP
 * @package     HTTP_Request
 * @author      Richard Heyes <richard@phpguru.org>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.4.4
 */
class HTTP_Response
{
    var $_sock;
    var $_protocol;
	var $_code;
	var $_reason;
	
    var $_headers;
	
    var $_cookies;
	
    var $_body = '';
	
    var $_chunkLength = 0;
	
    var $_listeners = array();
	
    var $_toRead;
	
    function HTTP_Response(&$sock, &$listeners)
    {
        $this->_sock      =& $sock;
        $this->_listeners =& $listeners;
    }
	
    function process($saveBody = true, $canHaveBody = true)
    {
        do {
            $line = $this->_sock->readLine();
            if (!preg_match('!^(HTTP/\d\.\d) (\d{3})(?: (.+))?!', $line, $s)) {
                return PEAR::raiseError('Malformed response', HTTP_REQUEST_ERROR_RESPONSE);
            } else {
                $this->_protocol = $s[1];
                $this->_code     = intval($s[2]);
                $this->_reason   = empty($s[3])? null: $s[3];
            }
            while ('' !== ($header = $this->_sock->readLine())) {
                $this->_processHeader($header);
            }
        } while (100 == $this->_code);

        $this->_notify('gotHeaders', $this->_headers);
		
        $canHaveBody = $canHaveBody && $this->_code >= 200 &&
                       $this->_code != 204 && $this->_code != 304;
					   
        $chunked = isset($this->_headers['transfer-encoding']) && ('chunked' == $this->_headers['transfer-encoding']);
        $gzipped = isset($this->_headers['content-encoding']) && ('gzip' == $this->_headers['content-encoding']);
        $hasBody = false;
        if ($canHaveBody && ($chunked || !isset($this->_headers['content-length']) ||
                0 != $this->_headers['content-length']))
        {
            if ($chunked || !isset($this->_headers['content-length'])) {
                $this->_toRead = null;
            } else {
                $this->_toRead = $this->_headers['content-length'];
            }
            while (!$this->_sock->eof() && (is_null($this->_toRead) || 0 < $this->_toRead)) {
                if ($chunked) {
                    $data = $this->_readChunked();
                } elseif (is_null($this->_toRead)) {
                    $data = $this->_sock->read(4096);
                } else {
                    $data = $this->_sock->read(min(4096, $this->_toRead));
                    $this->_toRead -= HTTP_REQUEST_MBSTRING? mb_strlen($data, 'iso-8859-1'): strlen($data);
                }
                if ('' == $data && (!$this->_chunkLength || $this->_sock->eof())) {
                    break;
                } else {
                    $hasBody = true;
                    if ($saveBody || $gzipped) {
                        $this->_body .= $data;
                    }
                    $this->_notify($gzipped? 'gzTick': 'tick', $data);
                }
            }
        }

        if ($hasBody) {
            if ($gzipped) {
                $body = $this->_decodeGzip($this->_body);
                if (PEAR::isError($body)) {
                    return $body;
                }
                $this->_body = $body;
                $this->_notify('gotBody', $this->_body);
            } else {
                $this->_notify('gotBody');
            }
        }
        return true;
    }
	
    function _processHeader($header)
    {
        if (false === strpos($header, ':')) {
            return;
        }
        list($headername, $headervalue) = explode(':', $header, 2);
        $headername  = strtolower($headername);
        $headervalue = ltrim($headervalue);

        if ('set-cookie' != $headername) {
            if (isset($this->_headers[$headername])) {
                $this->_headers[$headername] .= ',' . $headervalue;
            } else {
                $this->_headers[$headername]  = $headervalue;
            }
        } else {
            $this->_parseCookie($headervalue);
        }
    }
	
    function _parseCookie($headervalue)
    {
        $cookie = array(
            'expires' => null,
            'domain'  => null,
            'path'    => null,
            'secure'  => false
        );
		
        if (!strpos($headervalue, ';')) {
            $pos = strpos($headervalue, '=');
            $cookie['name']  = trim(substr($headervalue, 0, $pos));
            $cookie['value'] = trim(substr($headervalue, $pos + 1));
        } else {
            $elements = explode(';', $headervalue);
            $pos = strpos($elements[0], '=');
            $cookie['name']  = trim(substr($elements[0], 0, $pos));
            $cookie['value'] = trim(substr($elements[0], $pos + 1));

            for ($i = 1; $i < count($elements); $i++) {
                if (false === strpos($elements[$i], '=')) {
                    $elName  = trim($elements[$i]);
                    $elValue = null;
                } else {
                    list ($elName, $elValue) = array_map('trim', explode('=', $elements[$i]));
                }
                $elName = strtolower($elName);
                if ('secure' == $elName) {
                    $cookie['secure'] = true;
                } elseif ('expires' == $elName) {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ('path' == $elName || 'domain' == $elName) {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        $this->_cookies[] = $cookie;
    }
	
    function _readChunked()
    {
	
        if (0 == $this->_chunkLength) {
            $line = $this->_sock->readLine();
            if (preg_match('/^([0-9a-f]+)/i', $line, $matches)) {
                $this->_chunkLength = hexdec($matches[1]);
				
                if (0 == $this->_chunkLength) {
                    $this->_sock->readLine();
                    return '';
                }
            } else {
                return '';
            }
        }
        $data = $this->_sock->read($this->_chunkLength);
        $this->_chunkLength -= HTTP_REQUEST_MBSTRING? mb_strlen($data, 'iso-8859-1'): strlen($data);
        if (0 == $this->_chunkLength) {
            $this->_sock->readLine();
        }
        return $data;
    }
	
    function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }
	
    function _decodeGzip($data)
    {
        if (HTTP_REQUEST_MBSTRING) {
            $oldEncoding = mb_internal_encoding();
            mb_internal_encoding('iso-8859-1');
        }
        $length = strlen($data);
		
        if (18 > $length || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            return $data;
        }
        $method = ord(substr($data, 2, 1));
        if (8 != $method) {
            return PEAR::raiseError('_decodeGzip(): unknown compression method', HTTP_REQUEST_ERROR_GZIP_METHOD);
        }
        $flags = ord(substr($data, 3, 1));
        if ($flags & 224) {
            return PEAR::raiseError('_decodeGzip(): reserved bits are set', HTTP_REQUEST_ERROR_GZIP_DATA);
        }
		
        $headerLength = 10;
		
        if ($flags & 4) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $extraLength = unpack('v', substr($data, 10, 2));
            if ($length - $headerLength - 2 - $extraLength[1] < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $extraLength[1] + 2;
        }
		
        if ($flags & 8) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $filenameLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $filenameLength || $length - $headerLength - $filenameLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $filenameLength + 1;
        }
		
        if ($flags & 16) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $commentLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $commentLength || $length - $headerLength - $commentLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $headerLength += $commentLength + 1;
        }
		
        if ($flags & 1) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short', HTTP_REQUEST_ERROR_GZIP_DATA);
            }
            $crcReal   = 0xffff & crc32(substr($data, 0, $headerLength));
            $crcStored = unpack('v', substr($data, $headerLength, 2));
            if ($crcReal != $crcStored[1]) {
                return PEAR::raiseError('_decodeGzip(): header CRC check failed', HTTP_REQUEST_ERROR_GZIP_CRC);
            }
            $headerLength += 2;
        }
		
        $tmp = unpack('V2', substr($data, -8));
        $dataCrc  = $tmp[1];
        $dataSize = $tmp[2];

        $unpacked = gzinflate(substr($data, $headerLength, -8));
        if (false === $unpacked) {
            return PEAR::raiseError('_decodeGzip(): gzinflate() call failed', HTTP_REQUEST_ERROR_GZIP_READ);
        } elseif ($dataSize != strlen($unpacked)) {
            return PEAR::raiseError('_decodeGzip(): data size check failed', HTTP_REQUEST_ERROR_GZIP_READ);
        } elseif ((0xffffffff & $dataCrc) != (0xffffffff & crc32($unpacked))) {
            return PEAR::raiseError('_decodeGzip(): data CRC check failed', HTTP_REQUEST_ERROR_GZIP_CRC);
        }
        if (HTTP_REQUEST_MBSTRING) {
            mb_internal_encoding($oldEncoding);
        }
        return $unpacked;
    }
}