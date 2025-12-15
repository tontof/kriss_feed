<?php
/**
 * Useful php snippets
 *
 * PHP version 5
 *
 * Features:
 * - initPHP, isUrl, isEmail, formatBBCode, formatText, getUrl, rrmdir,
 *   humanBytes, returnBytes, getMaxFileSize, smallHash
 * TODO:
 *
 */

if (!function_exists("http_get_last_response_headers")) {
    function http_get_last_response_headers() {
        if (!isset($http_response_header) ) {
            return null;
        }
        return $http_response_header;
    }
}


class MyTool
{
    // http://php.net/manual/en/function.libxml-set-streams-context.php
    static $opts;
    static $redirects = 20;

    const ERROR_UNKNOWN_CODE = 1;
    const ERROR_LOCATION = 2;
    const ERROR_TOO_MANY_REDIRECTS = 3;
    const ERROR_NO_CURL = 4;

    /** 
     * loadUrl
     * http://stackoverflow.com/questions/2511410/curl-follow-location-error
     *
     * @param string  $url to load
     *
     * @return array $output with header, code, data, error
     */
    public static function loadUrl($url)
    {
        $redirects = self::$redirects;
        $opts = self::$opts;
        $header = '';
        $code = '';
        $data = '';
        $error = '';

        if (in_array('curl', get_loaded_extensions())) {
            $ch = curl_init($url);

            if (!empty($opts)) {
                if (!empty($opts['http']['timeout'])) {
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $opts['http']['timeout']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, $opts['http']['timeout']);
                }
                if (!empty($opts['http']['user_agent'])) {
                    curl_setopt($ch, CURLOPT_USERAGENT, $opts['http']['user_agent']);
                }
                if (!empty($opts['http']['headers'])) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['http']['headers']);
                }
            }
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);

            if ((!ini_get('open_basedir') && !ini_get('safe_mode')) || $redirects < 1) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $redirects > 0);
                curl_setopt($ch, CURLOPT_MAXREDIRS, $redirects);
                $data = curl_exec($ch);
            } else {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
                do {
                    $data = curl_exec($ch);
                    if (curl_errno($ch)) {
                        break;
                    }
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    /**
                     * 301 Moved Permanently
                     * 302 Found
                     * 303 See Other
                     * 307 Temporary Redirect
                     */
                    if ($code != 301 && $code != 302 && $code!=303 && $code!=307) {
                        break;
                    }

                    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($data, 0, $headerSize);
                    if (!preg_match('/^(?:Location|URI): ([^\r\n]*)[\r\n]*$/im', $header, $matches)) {
                        $error = self::ERROR_LOCATION;
                        break;
                    }
                    curl_setopt($ch, CURLOPT_URL, $matches[1]);
                } while (--$redirects);

                if (!$redirects) {
                    $error = self::ERROR_TOO_MANY_REDIRECTS;
                }
            }

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($data, 0, $headerSize);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $data = substr($data, $headerSize);
            $error = curl_error($ch);
        } else {
            $context = stream_context_create($opts);
            if ($stream = fopen($url, 'r', false, $context)) {
                $data = stream_get_contents($stream);
                $http_response_header = http_get_last_response_headers();
                $status = $http_response_header[0];
                $code = explode(' ', $status);
                if (count($code)>1) {
                    $code = $code[1];
                } else {
                    $code = '';
                    $error = self::ERROR_UNKNOWN_CODE;
                }
                $header = implode("\r\n", $http_response_header);
                fclose($stream);

                if (substr($data,0,1) != '<') {
                    $decoded = gzdecode($data);
                    if (substr($decoded,0,1) == '<') {
                        $data = $decoded;
                    }
                }
            } else {
                $error = self::ERROR_NO_CURL;
            }
        }

        return array(
            'header' => $header,
            'code' => $code,
            'data' => $data,
            'error' => self::getError($error),
        );
    }

    /**
     * Get human readable error
     *
     * @param integer $error Number of error occured during a feed update
     *
     * @return string String of the corresponding error
     */
    public static function getError($error)
    {
        switch ($error) {
        case self::ERROR_UNKNOWN_CODE:
            return Intl::msg('Http code not valid');
            break;
        case self::ERROR_LOCATION:
            return Intl::msg('Location not found');
            break;
        case self::ERROR_TOO_MANY_REDIRECTS:
            return Intl::msg('Too many redirects');
            break;
        case self::ERROR_NO_CURL:
            return Intl::msg('Error when loading without curl');
            break;
        default:
            return $error;
            break;
        }
    }

    /**
     * Test if php version is greater than 5, set error reporting, deal
     * with magic quotes for POST, GET and COOKIE and initialize bufferization
     */
    public static function initPhp()
    {
        define('START_TIME', microtime(true));

        if (phpversion() < 5) {
            die("Argh you don't have PHP 5 !");
        }

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        function stripslashesDeep($value) {
            return is_array($value)
                ? array_map('stripslashesDeep', $value)
                : stripslashes($value);
        }
            
            if (version_compare(PHP_VERSION, '7.4', '<')) {
                if (get_magic_quotes_gpc()) {
                    $_POST = array_map('stripslashesDeep', $_POST);
                    $_GET = array_map('stripslashesDeep', $_GET);
                    $_COOKIE = array_map('stripslashesDeep', $_COOKIE);
                }
            }

        ob_start();
    }

    /**
     * Test if parameter is an URL
     * use http://www.php.net/manual/en/function.filter-var.php instead ?
     *
     * @param string $url Url to check
     *
     * @return true|false True if paramater is a URL, false otherwise
     */
    public static function isUrl($url)
    {
        // http://neo22s.com/check-if-url-exists-and-is-online-php/
        $pattern='|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';

        return preg_match($pattern, $url);
    }

    /**
     * Test if parameter is an email
     * use http://www.php.net/manual/en/function.filter-var.php instead ?
     *
     * @param string $email Email to check
     *
     * @return true|false   True if paramater is an email, false otherwise
     */
    public static function isEmail($email)
    {
        $pattern = "/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2, 4}$/i";

        return (preg_match($pattern, $email));
    }

    /**
     * Format given text using BBCode with corresponding tags
     *
     * @param string $text BBCodeText to format
     *
     * @return string      Converted text
     */
    public static function formatBBCode($text)
    {
        $replace = array(
            '/\[m\](.+?)\[\/m\]/is'
            => '/* moderate */',
            '/\[b\](.+?)\[\/b\]/is'
            => '<strong>$1</strong>',
            '/\[i\](.+?)\[\/i\]/is'
            => '<em>$1</em>',
            '/\[s\](.+?)\[\/s\]/is'
            => '<del>$1</del>',
            '/\[u\](.+?)\[\/u\]/is'
            => '<span style="text-decoration: underline;">$1</span>',
            '/\[url\](.+?)\[\/url]/is'
            => '<a href="$1">$1</a>',
            '/\[url=(\w+:\/\/[^\]]+)\](.+?)\[\/url]/is'
            => '<a href="$1">$2</a>',
            '/\[quote\](.+?)\[\/quote\]/is'
            => '<blockquote>$1</blockquote>',
            '/\[code\](.+?)\[\/code\]/is'
            => '<code>$1</code>',
            '/\[([^[]+)\|([^[]+)\]/is'
            => '<a href="$2">$1</a>'
            );
        $text = preg_replace(
            array_keys($replace),
            array_values($replace),
            $text
        );

        return $text;
    }

    /**
     * Format text to emphasize html, php, URL and wikipedia URL
     *
     * @param string $text Original text to format
     *
     * @return string      Converted text
     */
    public static function formatText($text)
    {
        $text = preg_replace_callback(
            '/<code_html>(.*?)<\/code_html>/is',
            create_function(
                '$matches',
                'return htmlspecialchars($matches[1]);'
            ),
            $text
        );
        $text = preg_replace_callback(
            '/<code_php>(.*?)<\/code_php>/is',
            create_function(
                '$matches',
                'return highlight_string("<?php $matches[1] ?>", true);'
            ),
            $text
        );
        $text = preg_replace('/<br \/>/is', '', $text);

        $text = preg_replace(
            '#(^|\s)([a-z]+://([^\s\w/]?[\w/])*)(\s|$)#im',
            '\\1<a href="\\2">\\2</a>\\4',
            $text
        );
        $text = preg_replace(
            '#(^|\s)wp:?([a-z]{2}|):([\w]+)#im',
            '\\1<a href="http://\\2.wikipedia.org/wiki/\\3">\\3</a>',
            $text
        );
        $text = str_replace(
            'http://.wikipedia.org/wiki/',
            'http://www.wikipedia.org/wiki/',
            $text
        );
        $text = str_replace('\wp:', 'wp:', $text);
        $text = str_replace('\http:', 'http:', $text);
        $text = MyTool::formatBBCode($text);
        $text = nl2br($text);

        return $text;
    }

    /**
     * Returns the server URL (including port and http/https), without path.
     * eg. "http://myserver.com:8080"
     * You can append $_SERVER['SCRIPT_NAME'] to get the current script URL.
     * http://sebsauvage.net/wiki/doku.php?id=php:shaarli
     *
     * @return string URL website
     */
    public static function getUrl()
    {
        $base =  isset($GLOBALS['BASE_URL'])?$GLOBALS['BASE_URL']:'';
        if (!empty($base)) {
            return $base;
        }

        $https = (!empty($_SERVER['HTTPS'])
                  && (strtolower($_SERVER['HTTPS']) == 'on'))
            || (isset($_SERVER["SERVER_PORT"])
                && $_SERVER["SERVER_PORT"] == '443'); // HTTPS detection.
        $serverport = (!isset($_SERVER["SERVER_PORT"])
                       || $_SERVER["SERVER_PORT"] == '80'
                       || ($https && $_SERVER["SERVER_PORT"] == '443')
                       ? ''
                       : ':' . $_SERVER["SERVER_PORT"]);

        $scriptname = str_replace('/index.php', '/', $_SERVER["SCRIPT_NAME"]);

        if (!isset($_SERVER["SERVER_NAME"])) {
            return $scriptname;
        }

        return 'http' . ($https ? 's' : '') . '://'
            . $_SERVER["SERVER_NAME"] . $serverport . $scriptname;
    }

    /**
     * Remove not empty directory using unlink
     *
     * @param $string $dir Directory to remove
     */
    public static function rrmdir($dir)
    {
        if (is_dir($dir) && ($d = @opendir($dir))) {
            while (($file = @readdir($d)) !== false) {
                if ( $file != '.' && $file != '..' ) {
                    unlink($dir . '/' . $file);
                }
            }
        }
    }

    /**
     * Convert a number of bytes into human readable number of bytes
     * http://www.php.net/manual/fr/function.disk-free-space.php#103382
     *
     * @param integer $bytes Number of bytes to convert into human readable
     *
     * @return string        String of human readable number of bytes
     */
    public static function humanBytes($bytes)
    {
        $siPrefix = array( 'bytes', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
        $base = 1024;
        $class = min((int) log($bytes, $base), count($siPrefix) - 1);
        $val = sprintf('%1.2f', $bytes / pow($base, $class));

        return $val . ' ' . $siPrefix[$class];
    }

    /**
     * Convert post_max_size/upload_max_filesize (eg.'16M') parameters to bytes.
     *
     * @param string $val Value to convert
     *
     * @return interg     Number of bytes corresponding to the given value
     */
    public static function returnBytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $value = intval($val);
        switch($last)
        {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
        }

        return $value;
    }

    /**
     * Try to determine max file size for uploads (POST).
     * http://sebsauvage.net/wiki/doku.php?id=php:shaarli
     *
     * @return integer Number of bytes
    */
    public static function getMaxFileSize()
    {
        $sizePostMax   = MyTool::returnBytes(ini_get('post_max_size'));
        $sizeUploadMax = MyTool::returnBytes(ini_get('upload_max_filesize'));

        // Return the smaller of two:
        return min($sizePostMax, $sizeUploadMax);
    }

    /**
     * Returns the small hash of a string
     * http://sebsauvage.net/wiki/doku.php?id=php:shaarli
     * eg. smallHash('20111006_131924') --> yZH23w
     * Small hashes:
     * - are unique (well, as unique as crc32, at last)
     * - are always 6 characters long.
     * - only use the following characters: a-z A-Z 0-9 - _ @
     * - are NOT cryptographically secure (they CAN be forged)
     *
     * @param string $text Text to convert into small hash
     *
     * @return string      Small hash corresponding to the given text
     */
    public static function smallHash($text)
    {
        $t = rtrim(base64_encode(hash('crc32', $text, true)), '=');
        // Get rid of characters which need encoding in URLs.
        $t = str_replace('+', '-', $t);
        $t = str_replace('/', '_', $t);
        $t = str_replace('=', '@', $t);

        return $t;
    }

    /**
     * Render json from data
     *
     * @param mixed $data data to convert into json
     */
    public static function renderJson($data)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json; charset=UTF-8');

        echo json_encode($data);
        exit();
    }

    /**
     * Grab to local a page/an image and save it into file and return a link
     * to local if success or original link if it fails
     *
     * @param string $url to be downloaded
     * @param string $file to be saved
     * @param bool   $force to force update
     */
    public static function grabToLocal($url, $file, $force = false)
    {
        if ((!file_exists($file) || $force) && in_array('curl', get_loaded_extensions())){
            $ch = curl_init ($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $raw = curl_exec($ch);
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
                $fp = fopen($file, 'x');
                fwrite($fp, $raw);
                fclose($fp);
            }
            curl_close ($ch);
        }
    }

    /**
     * Redirect depending on returnurl form or REFERER
     *
     * @param string $rurl Url to be redirected to
     */
    public static function redirect($rurl = '')
    {
        if ($rurl === '') {
            // if (!empty($_SERVER['HTTP_REFERER']) && strcmp(parse_url($_SERVER['HTTP_REFERER'],PHP_URL_HOST),$_SERVER['SERVER_NAME'])==0)
            $rurl = (empty($_SERVER['HTTP_REFERER'])?'?':$_SERVER['HTTP_REFERER']);
            if (isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
        }

        // prevent loop
        if (empty($rurl) || parse_url($rurl, PHP_URL_QUERY) === $_SERVER['QUERY_STRING']) {
            $rurl = MyTool::getUrl();
        }

        if (substr($rurl, 0, 1) !== '?') {
            $ref = MyTool::getUrl();
            if (substr($rurl, 0, strlen($ref)) !== $ref) {
                $rurl = $ref;
            }
        }
        header('Location: '.$rurl);
        exit();
    }
}
