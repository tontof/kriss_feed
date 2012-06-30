<?php
/**
 * Feed Conf is a class to configurate Feed reader.
 */
class Feed_Conf
{
    /**
     * Configuration file
     */
    private $_file = '';

    /**
     * Login
     */
    public $login = '';

    /**
     * Hash of the password
     */
    public $hash = '';

    /**
     * Salt
     */
    public $salt = '';

    /**
     * Feed title
     */
    public $title = "Kriss feed";

    /**
     * Redirector (e.g. http://anonym.to/? will mask the HTTP_REFERER)
     * (consider only links in the article, not images and media)
     */
    public $redirector = '';

    /**
     * Shaarli link
     */
    public $shaarli = '';

    /**
     * Number of entries to display per page
     */
    public $byPage = 10;

    /**
     * Max number of articles by channel
     */
    public $maxItems = 100;

    /**
     * Max number of minutes between each update of channel
     */
    public $maxUpdate = 60;

    /**
     * Reversed order
     */
    public $reverseOrder = true;

    /**
     * New items (or all items)
     */
    public $newItems = true;

    /**
     * Expanded view (or list view)
     */
    public $expandedView = true;

    /**
     * Default view (show, reader)
     */
    public $defaultView = 'show';

    /**
     * Public/private feed reader
     */
    public $public = false;

    /**
     * Kriss_feed version
     */
    public $version;

    /**
     * Constructor
     *
     * @param string $configFile Configuration file
     * @param string $version    Kriss feed version
     */
    public function __construct($configFile, $version)
    {
        $this->_file = $configFile;
        $this->version = $version;

        // Loading user config
        if (file_exists($this->_file)) {
            include_once $this->_file;
        } else {
            $this->_install();
        }
    }

    /**
     * Installation of the configuration file
     */
    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])) {
            $this->setSalt(sha1(uniqid('', true).'_'.mt_rand()));
            $this->setLogin($_POST['setlogin']);
            $this->setHash($_POST['setpassword']);

            if (!is_dir(DATA_DIR)) {
                if (!@mkdir(DATA_DIR, 0755)) {
                    echo '
<script>
 alert("Error: can not create '.DATA_DIR.' directory, check permissions");
 document.location=window.location.href;
</script>';
                }
                @chmod(DATA_DIR, 0755);
                if (!is_file(DATA_DIR.'/.htaccess')) {
                    if (!@file_put_contents(
                        DATA_DIR.'/.htaccess',
                        "Allow from none\nDeny from all\n"
                    )) {
                        echo '
<script>
 alert("Can not protect '.DATA_DIR.'");
 document.location=window.location.href;
</script>';
                    }
                }
            }

            if ($this->write()) {
                echo '
<script>
 alert("Your simple and smart (or stupid) feed reader is now configured.");
 document.location=window.location.href;
</script>';
            } else {
                echo '
<script>
 alert("Error: can not write config and data files.");
 document.location=window.location.href;
</script>';
            }
            Session::logout();
        } else {
            echo '
<h1>Feed reader installation</h1>
<form method="post" action="">
  <p><label>Login: <input type="text" name="setlogin" /></label></p>
  <p><label>Password: <input type="password" name="setpassword" /></label></p>
  <p><input type="submit" value="OK" class="submit" /></p>
</form>';
        }
        exit();
    }

    /**
     * Hydate to set all configuration variables
     *
     * @param array $data List of variable to hydrate
     */
    public function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            // get setter
            $method = 'set'.ucfirst($key);
            // if setter exists just call it
            // (php is not case-sensitive with functions)
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Login setter
     *
     * @param string $login New login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * Public setter
     *
     * @param string $public New public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * Hash setter
     *
     * @param string $pass New hash
     */
    public function setHash($pass)
    {
        $this->hash = sha1($pass.$this->login.$this->salt);
    }

    /**
     * Salt setter
     *
     * @param string $salt New salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * Title setter
     *
     * @param string $title New title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Redirector setter
     *
     * @param string $redirector New redirector
     */
    public function setRedirector($redirector)
    {
        $this->redirector = $redirector;
    }

    /**
     * ByPage setter
     *
     * @param string $byPage New byPage
     */
    public function setByPage($byPage)
    {
        $this->byPage = $byPage;
    }

    /**
     * Shaarli setter
     *
     * @param string $url New shaarli
     */
    public function setShaarli($url)
    {
        $this->shaarli = $url;
    }

    /**
     * MaxUpdate setter
     *
     * @param string $max New maxUpdate
     */
    public function setMaxUpdate($max)
    {
        $this->maxUpdate = $max;
    }

    /**
     * MaxItems setter
     *
     * @param string $max New maxItems
     */
    public function setMaxItems($max)
    {
        $this->maxItems = $max;
    }

    /**
     * NewItems setter
     *
     * @param true|false $new New newItems
     */
    public function setNewItems($new)
    {
        $this->newItems = $new;
    }

    /**
     * ExpandedView setter
     *
     * @param true|false $expandedView New expandedView
     */
    public function setExpandedView($expandedView)
    {
        $this->expandedView = $expandedView;
    }

    /**
     * DefaultView setter
     *
     * @param string $defaultView New defaultView
     */
    public function setDefaultView($defaultView)
    {
        if ($defaultView == 'show') {
            $this->defaultView = 'show';
        } elseif ($defaultView == 'reader') {
            $this->defaultView = 'reader';
        }
    }

    /**
     * ReverseOrder setter
     *
     * @param string $reverseOrder New reverseOrder
     */
    public function setReverseOrder($reverseOrder)
    {
        $this->reverseOrder = $reverseOrder;
    }

    /**
     * Write configuration file
     *
     * @return true|false True if file successfully saved, false otherwise
     */
    public function write()
    {
        $data = array('login', 'hash', 'salt', 'title', 'redirector', 'public',
                      'byPage', 'reverseOrder', 'maxUpdate', 'shaarli',
                      'maxItems', 'newItems', 'expandedView', 'defaultView');
        $out = '<?php';
        $out .= "\n";

        foreach ($data as $key) {
            $value = strtr($this->$key, array('$' => '\\$', '"' => '\\"'));
            $out .= '$this->'.$key.' = "'.$value."\";\n";
        }

        $out .= '?>';

        if (!@file_put_contents($this->_file, $out)) {
            return false;
        }

        return true;
    }
}
