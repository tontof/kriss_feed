<?php
/**
 * Session management class
 *
 * PHP version 5
 *
 * http://www.developpez.net/forums/d51943/php/langage/sessions/
 * http://sebsauvage.net/wiki/doku.php?id=php:session
 * http://sebsauvage.net/wiki/doku.php?id=php:shaarli
 *
 * Features:
 * - Everything is stored on server-side (we do not trust client-side data,
 *   such as cookie expiration)
 * - IP addresses + user agent are checked on each access to prevent session
 *   cookie hijacking (such as Firesheep)
 * - Session expires on user inactivity (Session expiration date is
 *   automatically updated everytime the user accesses a page.)
 * - A unique secret key is generated on server-side for this session
 *   (and never sent over the wire) which can be used
 *   to sign forms (HMAC) (See $_SESSION['uid'] )
 * - Token management to prevent XSRF attacks.
 *
 * TODO:
 * - log login fail
 *
 * HOWTOUSE:
 * - Just call Session::init(); to initialize session and
 *   check if connected with Session::isLogged()
 */
class Session
{
    /**
     * Static session
     */
    private static $_instance;

    /**
     * If the user does not access any page within this time,
     * his/her session is considered expired (in seconds).
     */
    public static $inactivityTimeout = 3600;

    /**
     * If you get disconnected often or if your IP address changes often.
     * Let you disable session cookie hijacking protection
     */
    public static $disableSessionProtection = false;

    /**
     * Ban management
     * $banFile:     File storage for failures and bans.
     * $banAfter:    Ban IP after this many failures.
     * $banDuration: Ban duration for IP address after login failures
     *               (in seconds) (1800 sec. = 30 minutes)
     */
    public static $banFile = 'ipbans.php';
    public static $banAfter = 4;
    public static $banDuration = 1800;

    /**
     * constructor
     */
    private function __construct($banFile)
    {
        // Check ban configuration
        self::$banFile = $banFile;

        if (!is_file(self::$banFile)) {
            file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export(array('FAILURES'=>array(),'BANS'=>array()),true).";\n?>");
        }
        include self::$banFile;

        // Force cookie path (but do not change lifetime)
        $cookie=session_get_cookie_params();
        // Default cookie expiration and path.
        $cookiedir = '';
        if(dirname($_SERVER['SCRIPT_NAME'])!='/') {
            $cookiedir = dirname($_SERVER["SCRIPT_NAME"]).'/';
        }
        session_set_cookie_params($cookie['lifetime'], $cookiedir);
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()) {
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
            session_name('kriss');
            session_start();
        }
    }

    /**
     * initialize private instance of session class
     */
    public static function init($banFile)
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Session($banFile);
        }
    }

    /**
     * Signal a failed login. Will ban the IP if too many failures:
     */
    public static function banLoginFailed()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $gb = $GLOBALS['IPBANS'];

        if (!isset($gb['FAILURES'][$ip])) {
            $gb['FAILURES'][$ip] = 0;
        }
        $gb['FAILURES'][$ip]++;
        if ($gb['FAILURES'][$ip] > (self::$banAfter-1)) {
            $gb['BANS'][$ip]= time() + self::$banDuration;
        }

        $GLOBALS['IPBANS'] = $gb;
        file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
    }

    /**
     * Signals a successful login. Resets failed login counter.
     */
    function banLoginOk()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $gb = $GLOBALS['IPBANS'];
        unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
        $GLOBALS['IPBANS'] = $gb;
        file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
    }

    /**
     * Checks if the user CAN login. If 'true', the user can try to login.
     */
    function banCanLogin()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $gb = $GLOBALS['IPBANS'];
        if (isset($gb['BANS'][$ip])) {
            // User is banned. Check if the ban has expired:
            if ($gb['BANS'][$ip] <= time()) {
                // Ban expired, user can try to login again.
                unset($gb['FAILURES'][$ip]);
                unset($gb['BANS'][$ip]);
                file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
                return true; // Ban has expired, user can login.
            }
            return false; // User is banned.
        }
        return true; // User is not banned.
    }

    /**
     * Returns the IP address
     * (Used to prevent session cookie hijacking.)
     *
     * @return string IP address
     */
    private static function _allIPs()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $ip.= isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? '_'.$_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $ip.= isset($_SERVER['HTTP_CLIENT_IP']) ? '_'.$_SERVER['HTTP_CLIENT_IP'] : '';

        return $ip;
    }

    /**
     * Check that user/password is correct and then init some SESSION variables.
     *
     * @param string $login        Login reference
     * @param string $password     Password reference
     * @param string $loginTest    Login to compare with login reference
     * @param string $passwordTest Password to compare with password reference
     * @param array  $pValues      Array of variables to store in SESSION
     *
     * @return true|false          True if login and password are correct, false
     *                             otherwise
     */
    public static function login (
        $login,
        $password,
        $loginTest,
        $passwordTest,
        $pValues = array())
    {
        if (!self::banCanLogin()) {
            die('I said: NO. You are banned for the moment. Go away.');
        }
        if ($login == $loginTest && $password==$passwordTest) {
            self::banLoginOk();
            // Generate unique random number to sign forms (HMAC)
            $_SESSION['uid'] = sha1(uniqid('', true).'_'.mt_rand());
            $_SESSION['ip'] = Session::_allIPs();
            $_SESSION['username'] = $login;
            // Set session expiration.
            $_SESSION['expires_on'] = time() + Session::$inactivityTimeout;

            foreach ($pValues as $key => $value) {
                $_SESSION[$key] = $value;
            }

            return true;
        }
        self::banLoginFailed();
        Session::logout();

        return false;
    }

    /**
     * Unset SESSION variable to force logout
     */
    public static function logout()
    {
        unset($_SESSION['uid'], $_SESSION['ip'], $_SESSION['expires_on']);
    }

    /**
     * Make sure user is logged in.
     *
     * @return true|false True if user is logged in, false otherwise
     */
    public static function isLogged()
    {
        if (!isset ($_SESSION['uid'])
            || (Session::$disableSessionProtection == false
                && $_SESSION['ip']!=Session::_allIPs())
            || time()>=$_SESSION['expires_on']) {
            Session::logout();

            return false;
        }
        // User accessed a page : Update his/her session expiration date.
        if (time()+Session::$inactivityTimeout > $_SESSION['expires_on']) {
            $_SESSION['expires_on'] = time()+Session::$inactivityTimeout;
        }

        return true;
    }

    /**
     * Create a token, store it in SESSION and return it
     *
     * @return string Token created
     */
    public static function getToken($salt = '')
    {
        if (!isset($_SESSION['tokens'])) {
            $_SESSION['tokens']=array();
        }
        // We generate a random string and store it on the server side.
        $rnd = sha1(uniqid('', true).'_'.mt_rand().$salt);
        $_SESSION['tokens'][$rnd]=1;

        return $rnd;
    }

    /**
     * Tells if a token is ok. Using this function will destroy the token.
     *
     * @param string $token Token to test
     *
     * @return true|false   True if token is correct, false otherwise
     */
    public static function isToken($token)
    {
        if (isset($_SESSION['tokens'][$token])) {
            unset($_SESSION['tokens'][$token]); // Token is used: destroy it.

            return true; // Token is ok.
        }

        return false; // Wrong token, or already used.
    }
}//end class
