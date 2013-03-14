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
 * - prevent brute force (ban IP)
 *
 * HOWTOUSE:
 * - Just call Session::init(); to initialize session and
 *   check if connected with Session::isLogged()
 */
class Session
{
    /**
     * If the user does not access any page within this time,
     * his/her session is considered expired (in seconds).
     */
    public static $inactivityTimeout = 3600;

    /**
     * Static session
     */
    private static $_instance;

    /**
     * constructor
     */
    private function __construct()
    {
        // Force cookie path (but do not change lifetime)
        $cookie=session_get_cookie_params();
        // Default cookie expiration and path.
        session_set_cookie_params($cookie['lifetime'], dirname($_SERVER["SCRIPT_NAME"]).'/');
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
    public static function init()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Session();
        }
    }

    /**
     * Returns the IP address, user agent and language of the client
     * (Used to prevent session cookie hijacking.)
     *
     * @return string Hash of all information
     */
    private static function _allInfo()
    {
        $infos = $_SERVER["REMOTE_ADDR"];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $infos.=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $infos.='_'.$_SERVER['HTTP_CLIENT_IP'];
        }
        $infos.='_'.$_SERVER['HTTP_USER_AGENT'];
        $infos.='_'.$_SERVER['HTTP_ACCEPT_LANGUAGE'];

        return sha1($infos);
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
        if ($login == $loginTest && $password==$passwordTest) {
            // Generate unique random number to sign forms (HMAC)
            $_SESSION['uid'] = sha1(uniqid('', true).'_'.mt_rand());
            $_SESSION['info']=Session::_allInfo();
            $_SESSION['username']=$login;
            // Set session expiration.
            $_SESSION['expires_on']=time()+Session::$inactivityTimeout;

            foreach ($pValues as $key => $value) {
                $_SESSION[$key] = $value;
            }

            return true;
        }
        Session::logout();

        return false;
    }

    /**
     * Unset SESSION variable to force logout
     */
    public static function logout()
    {
        unset($_SESSION['uid'], $_SESSION['info'], $_SESSION['expires_on']);
    }

    /**
     * Make sure user is logged in.
     *
     * @return true|false True if user is logged in, false otherwise
     */
    public static function isLogged()
    {
        if (!isset ($_SESSION['uid'])
            || $_SESSION['info']!=Session::_allInfo()
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
    public static function getToken()
    {
        if (!isset($_SESSION['tokens'])) {
            $_SESSION['tokens']=array();
        }
        // We generate a random string and store it on the server side.
        $rnd = sha1(uniqid('', true).'_'.mt_rand());
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
