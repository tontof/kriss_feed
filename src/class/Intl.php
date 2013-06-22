<?php
/**
 * Intl class can be used to translate messages
 */
class Intl
{
    public static $lazy;
    public static $lang;
    public static $dir;
    public static $domain;
    public static $messages = array();

    /**
     * load translated messages using $dir and $lang
     */
    public static function init($lang = "en_GB",
                                $dir = "locale",
                                $domain = "messages")
    {
        self::$lazy = false;
        self::$lang = $lang;
        self::$dir = $dir;
        self::$domain = $domain;
    }

    public static function load($lang) {
        self::$lazy = true;

        if (file_exists(self::$dir.'/'.$lang.'/LC_MESSAGES/'.self::$domain.'.po')) {
            self::$messages[$lang] = self::phpmo_parse_po_file(self::$dir.'/'.$lang.'/LC_MESSAGES/'.self::$domain.'.po');
        } else {
            Plugin::callHook('Intl_init_'.$lang, array(&self::$messages));
        }
        
        return isset(self::$messages[$lang])?self::$messages[$lang]:array();
    }

    /**
     * translate simple message
     *
     * @param string $string to be translated
     * @param string $context of the translation
     */
    public static function msg($string, $context = "")
    {
        if (!self::$lazy) {
            self::load(self::$lang);
        }

        return self::n_msg($string, '', 0, $context);
    }

    /**
     * translate plural message
     * plural : https://developer.mozilla.org/en-US/docs/gettext
     *
     * @param string $string to be translated
     * @param string $plural to be translated when plural
     * @param integer $count
     * @param string $context of the translation
     */
    public static function n_msg($string, $plural, $count, $context = "")
    {
        if (!self::$lazy) {
            self::load(self::$lang);
        }

        // TODO extract Plural-Forms from po file
        // https://www.gnu.org/savannah-checkouts/gnu/gettext/manual/html_node/Plural-forms.html
        $count = $count > 1 ? 1 : 0;

        if (isset(self::$messages[self::$lang][$string])
            && !empty(self::$messages[self::$lang][$string]['msgstr'][$count])) {
            return self::$messages[self::$lang][$string]['msgstr'][$count];
        }

        if ($count != 0) {
            return $plural;
        }

        return $string;
    }

    /* Parse gettext .po files.
     *
     * based on
     * php.mo 0.1 by Joss Crowcroft (http://www.josscrowcroft.com)
     * @link https://github.com/josscrowcroft/php.mo
     * @link http://www.gnu.org/software/gettext/manual/gettext.html */
    public static function phpmo_parse_po_file($in) {
	// read .po file
	$fh = fopen($in, 'r');
	if ($fh === false) {
            // Could not open file resource
            return false;
	}

	// results array
	$hash = array ();
	// temporary array
	$temp = array ();
	// state
	$state = null;
	$fuzzy = false;

	// iterate over lines
	while(($line = fgets($fh, 65536)) !== false) {
            $line = trim($line);
            if ($line === '' || strpos($line, ' ') === false)
                continue;

            list ($key, $data) = preg_split('/\s/', $line, 2);
		
            switch ($key) {
            case '#,' : // flag...
                $fuzzy = in_array('fuzzy', preg_split('/,\s*/', $data));
            case '#' : // translator-comments
            case '#.' : // extracted-comments
            case '#:' : // reference...
            case '#|' : // msgid previous-untranslated-string
                // start a new entry
                if (sizeof($temp) && array_key_exists('msgid', $temp) && array_key_exists('msgstr', $temp)) {
                    if (!$fuzzy)
                        $hash[] = $temp;
                    $temp = array ();
                    $state = null;
                    $fuzzy = false;
                }
                break;
            case 'msgctxt' :
                // context
            case 'msgid' :
                // untranslated-string
            case 'msgid_plural' :
                // untranslated-string-plural
                $state = $key;
                $temp[$state] = $data;
                break;
            case 'msgstr' :
                // translated-string
                $state = 'msgstr';
                $temp[$state][] = $data;
                break;
            default :
                if (strpos($key, 'msgstr[') !== FALSE) {
                    // translated-string-case-n
                    $state = 'msgstr';
                    $temp[$state][] = $data;
                } else {
                    // continued lines
                    switch ($state) {
                    case 'msgctxt' :
                    case 'msgid' :
                    case 'msgid_plural' :
                        $temp[$state] .= "\n" . $line;
                        break;
                    case 'msgstr' :
                        $temp[$state][sizeof($temp[$state]) - 1] .= "\n" . $line;
                        break;
                    default :
                        // parse error
                        fclose($fh);
                        return FALSE;
                    }
                }
                break;
            }
	}
	fclose($fh);
	
	// add final entry
	if ($state == 'msgstr')
            $hash[] = $temp;

	// Cleanup data, merge multiline entries, reindex hash for ksort
	$temp = $hash;
	$hash = array ();
	foreach ($temp as $entry) {
            foreach ($entry as & $v) {
                $v = self::phpmo_clean_helper($v);
                if ($v === FALSE) {
                    // parse error
                    return FALSE;
                }
            }
            $hash[$entry['msgid']] = $entry;
	}

	return $hash;
    }

    public static function phpmo_clean_helper($x) {
	if (is_array($x)) {
            foreach ($x as $k => $v) {
                $x[$k] = self::phpmo_clean_helper($v);
            }
	} else {
            if ($x[0] == '"')
                $x = substr($x, 1, -1);
            $x = str_replace("\"\n\"", '', $x);
            // TODO: check with phpmo escape $
            // $x = str_replace('$', '\\$', $x);
	}
	return $x;
    }
}
