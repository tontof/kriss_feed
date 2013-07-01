<?php
/**
 * Intl class can be used to translate messages
 */
class Intl
{
    public static $lazy = false;
    public static $lang = "en_US";
    public static $dir = "locale";
    public static $domain = "messages";
    public static $messages = array();
    public static $langList = array();

    /**
     * load translated messages using $dir and $lang
     */
    public static function init()
    {
        $lang = self::$lang;
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
        } else if (isset($_SESSION['lang'])) {
            $lang = $_SESSION['lang'];
        }
         
        if (in_array($lang, array_keys(self::$langList))) {
            self::$lang = $lang;
        } else {
            unset($_SESSION['lang']);
        }
    }

    public static function addLang($lang, $name, $class) {
        self::$langList[$lang] = array(
            'name' => $name,
            'class' => $class
        );
    }

    public static function load($lang) {
        self::$lazy = true;

        if (file_exists(self::$dir.'/'.$lang.'/LC_MESSAGES/'.self::$domain.'.po')) {
            self::$messages[$lang] = self::compress(self::read(self::$dir.'/'.$lang.'/LC_MESSAGES/'.self::$domain.'.po'));
        } else if (class_exists('Intl_'.$lang)) {
            call_user_func_array(
                array('Intl_'.$lang, 'init'),
                array(&self::$messages)
            );
        }
        
        return isset(self::$messages[$lang])?self::$messages[$lang]:array();
    }

    public static function compress($hash) {
        foreach ($hash as $hashId => $hashArray) {
            $hash[$hashId] = $hashArray['msgstr'];
        }

        return $hash;
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
            && !empty(self::$messages[self::$lang][$string][$count])) {
            return self::$messages[self::$lang][$string][$count];
        }

        if ($count != 0) {
            return $plural;
        }

        return $string;
    }

    /**
     * Inspired from
     * https://raw.github.com/clinisbut/PHP-po-parser/master/poparser.php
     * Reads and parses strings in a .po file.
     *
     * \return An array of entries located in the file:
     * Format: array(
     * 'msgid'          => <string> ID of the message.
     * 'msgctxt'        => <string> Message context.
     * 'msgstr'         => <string> Message translation.
     * 'tcomment'       => <string> Comment from translator.
     * 'ccomment'       => <string> Extracted comments from code.
     * 'reference'      => <string> Location of string in code.
     * 'obsolete'       => <bool> Is the message obsolete?
     * 'fuzzy'          => <bool> Is the message "fuzzy"?
     * 'flags'          => <string> Flags of the entry. Internal usage.
     * )
     *
     * \todo: What means the line "#@ "???
     *
     * #~ (old entry)
     * # @ default
     * #, fuzzy
     * #~ msgid "Editar datos"
     * #~ msgstr "editar dades"
     */
    public static function read($pofile)
    {
        $handle = fopen( $pofile, 'r' );
        $hash = array();
        $fuzzy = false;
        $tcomment = $ccomment = $reference = null;
        $entry = $entryTemp = array();
        $state = null;
        $just_new_entry = false; // A new entry has ben just inserted

        while(!feof($handle)) {
            $line = trim( fgets($handle) );

            if($line==='') {
                if($just_new_entry) {
                    // Two consecutive blank lines
                    continue;
                }

                // A new entry is found!
                $hash[] = $entry;
                $entry = array();
                $state= null;
                $just_new_entry = true;
                continue;
            }

            $just_new_entry = false;

            $split = preg_split('/\s/ ', $line, 2 );
            $key = $split[0];
            $data = isset($split[1])? $split[1]:null;
                        
            switch($key) {
            case '#,':  //flag
                $entry['fuzzy'] = in_array('fuzzy', preg_split('/,\s*/', $data) );
                $entry['flags'] = $data;
                break;

            case '#':   //translation-comments
                $entryTemp['tcomment'] = $data;
                $entry['tcomment'] = $data;
                break;

            case '#.':  //extracted-comments
                $entryTemp['ccomment'] = $data;
                break;

            case '#:':  //reference
                $entryTemp['reference'][] = addslashes($data);
                $entry['reference'][] = addslashes($data);
                break;

            case '#|':  //msgid previous-untranslated-string
                // start a new entry
                break;
                                
            case '#@':  // ignore #@ default
                $entry['@'] = $data;
                break;

                // old entry
            case '#~':
                $key = explode(' ', $data );
                $entry['obsolete'] = true;
                switch( $key[0] )
                {
                case 'msgid': $entry['msgid'] = trim($data,'"');
                    break;

                case 'msgstr':  $entry['msgstr'][] = trim($data,'"');
                    break;
                default:        break;
                }
                                                        
                continue;
                break;

            case 'msgctxt' :
                // context
            case 'msgid' :
                // untranslated-string
            case 'msgid_plural' :
                // untranslated-string-plural
                $state = $key;
                $entry[$state] = $data;
                break;
                                
            case 'msgstr' :
                // translated-string
                $state = 'msgstr';
                $entry[$state][] = $data;
                break;

            default :

                if( strpos($key, 'msgstr[') !== FALSE ) {
                    // translated-string-case-n
                    $state = 'msgstr';
                    $entry[$state][] = $data;
                } else {
                    // continued lines
                    //echo "O NDE ELSE:".$state.':'.$entry['msgid'];
                    switch($state) {
                    case 'msgctxt' :
                    case 'msgid' :
                    case 'msgid_plural' :
                        //$entry[$state] .= "\n" . $line;
                        if(is_string($entry[$state])) {
                            // Convert it to array
                            $entry[$state] = array( $entry[$state] );
                        }
                        $entry[$state][] = $line;
                        break;
                                                                
                    case 'msgstr' :
                        //Special fix where msgid is ""
                        if($entry['msgid']=="\"\"") {
                            $entry['msgstr'][] = trim($line,'"');
                        } else {
                            //$entry['msgstr'][sizeof($entry['msgstr']) - 1] .= "\n" . $line;
                            $entry['msgstr'][]= trim($line,'"');
                        }
                        break;
                                                                
                    default :
                        throw new Exception('Parse ERROR!');
                        return FALSE;
                    }
                }
                break;
            }
        }
        fclose($handle);

        // add final entry
        if($state == 'msgstr') {
            $hash[] = $entry;
        }

        // Cleanup data, merge multiline entries, reindex hash for ksort
        $temp = $hash;
        $entries = array ();
        foreach($temp as $entry) {
            foreach($entry as & $v) {
                $v = self::clean($v);
                if($v === FALSE) {
                    // parse error
                    return FALSE;
                }
            }

            $id = is_array($entry['msgid'])? implode('',$entry['msgid']):$entry['msgid'];
                        
            $entries[ $id ] = $entry;
        }

        return $entries;
    }

    public function clean($x)
    {
        if(is_array($x)) {
            foreach($x as $k => $v) {
                $x[$k] = self::clean($v);
            }
        } else {
            // Remove " from start and end
            if($x == '') {
                return '';
            }

            if($x[0]=='"') {
                $x = substr($x, 1, -1);
            }

            $x = stripcslashes( $x );
        }

        return $x;
    }
}

