<?php

class Make
{
    public static function replaceAutoload($code)
    {
        $replace = '';
        $arrayClass = glob('class/*.php');

        if(is_array($arrayClass)) {  
            foreach($arrayClass as $class) {
                $replace .= 'del>'.self::loadFile($class);
            }  
        }

        $replace = preg_replace('/^del><\?php/m', '', $replace);

        return self::replaceFunction($code, '__autoload', $replace);
    }

    public static function includeFile($filename)
    {
        $base64 = array('ico');

        $return = file_get_contents($filename);
        if (in_array(pathinfo($filename, PATHINFO_EXTENSION), $base64)) {
            $return = base64_encode($return);
        }

        return $return;
    }

    public static function includeDir($dirname)
    {
        $arrayFile = glob($dirname.'/*.php');

        $return = '';
        if(is_array($arrayFile)) {  
            foreach($arrayFile as $filename) {
                $return .= 'del>'.self::includeFile($filename);
            }  
        }

        $return = preg_replace('/^del><\?php/m', '', $return);

        return $return;
    }

    public static function includeFiles($code)
    {
        $lines = explode(PHP_EOL, $code);
        
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/include\("([^"]+)"\)/', $lines[$i], $matches)) {
                if (is_file($matches[1])) {
                    $lines[$i] = self::includeFile($matches[1]);
                } elseif (is_dir($matches[1])) {
                    $lines[$i] = self::includeDir($matches[1]);
                }
            }
        }

        return implode("\n", $lines);
    }

    public static function insertFunction($code, $function, $className)
    {
        $lines = explode(PHP_EOL, $code);

        $begin = -1;
        $end = -1;
        for ($i = 0; $i < count($lines); $i++) {
            if ($begin === -1 && preg_match('/^class(\s)+'.$className.'$/', $lines[$i])) {
                $begin = $i;
            }
            if ($end === -1 && $begin !== -1 && preg_match('/^}$/', $lines[$i], $match)) {
                $end = $i;
                break;
            }
        }

        if ($begin !== -1 & $end !== -1) {
            $lines[$end-1] .= "\n".$function;
        }

        return implode("\n", $lines);
    }

    public static function replaceIncludeRainTpl($code)
    {
        $return = '';
        if (preg_match('/.*Rain.*dirname\("(.*)"\).*/U', $code, $matches)) {
            $return = $matches[1];
        }

        return '<?php FeedPage::'.$return.'Tpl(); ?>';
    }

    public static function replaceRainTpl($code)
    {
        $function = self::loadFunction($code, 'draw');
        $function = preg_replace('/public /m', '', $function);

        $code = self::replaceFunction($code, 'draw');

        include "inc/lib/raintpl/Rain.php";
        raintpl::configure( 'tpl_dir', "class/tpl/");
        if (!is_dir('tmp')) { mkdir('tmp',0705); chmod('tmp',0705); }
        raintpl::configure( 'cache_dir', "tmp/");
        raintpl::configure( 'path_replace', false );

        $arrayTpl = glob("class/tpl/*.html");

        $rain = new Rain;
        foreach($arrayTpl as $tpl) {
            $tpl = str_replace('.html', '', basename($tpl));
            $test = file_get_contents('class/tpl/'.$tpl.'.html');
            $test = str_replace( array("<?","?>"), array("&lt;?","?&gt;"), $test );

            $tmp = $rain->compileTemplate($test, 'class/tpl');

            $lines = explode(PHP_EOL, $tmp);
        
            for ($i = 0; $i < count($lines); $i++) {
                if (preg_match('/Rain/', $lines[$i])) {
                    $lines[$i] = self::replaceIncludeRainTpl($lines[$i]);
                }
            }

            $function = '
    public static function '.$tpl.'Tpl()
    {
        extract(FeedPage::$var);
?>
'.implode("\n", $lines).'
<?php
    }
';
            $code = self::insertFunction($code, $function, 'FeedPage');
        }

        $code = preg_replace('/=\$this->var\[/', '=FeedPage::$var[', $code);

        return $code;
    }

    public static function removeComments($code)
    {
        $lines = explode(PHP_EOL, $code);

        $begin = -1;
        for ($i = 0; $i < count($lines); $i++) {
            if ($begin === -1 && preg_match('/\/\*\*/', $lines[$i])) {
                $begin = $i;
            }
            if ($begin !== -1 && preg_match('/\*\//', $lines[$i])) {
                array_splice($lines, $begin, $i-$begin+1);
                $i = $begin - 1;
                $begin = -1;
            }
        }

        return implode("\n", $lines);
    }

    public static function replaceFunction($code, $name, $replace = '')
    {
        $lines = explode(PHP_EOL, $code);

        $begin = -1;
        $end = -1;
        $tab = '';
        for ($i = 0; $i < count($lines); $i++) {
            if ($begin === -1 && preg_match('/(\s*).*function.*'.$name.'.*/', $lines[$i], $match)) {
                $begin = $i;
                $tab = $match[1];
            }
            if ($end === -1 && $begin !== -1 && preg_match('/^'.$tab.'}/', $lines[$i], $match)) {
                $end = $i;
            }
        }

        if ($begin !== -1 & $end !== -1) {
            array_splice($lines, $begin, $end-$begin+1);
            if (!empty($replace)) {
                $lines[$begin] = $replace;
            }
        }

        return implode("\n", $lines);
    }

    public static function loadFunction($code, $name)
    {
        $lines = explode(PHP_EOL, $code);

        $begin = -1;
        $end = -1;
        $tab = '';
        for ($i = 0; $i < count($lines); $i++) {
            if ($begin === -1 && preg_match('/(\s*).*function.*'.$name.'.*/', $lines[$i], $match)) {
                $begin = $i;
                $tab = $match[1];
            }
            if ($end === -1 && $begin !== -1 && preg_match('/^'.$tab.'}/', $lines[$i], $match)) {
                $end = $i;
            }
        }

        $return = '';
        if ($begin !== -1 & $end !== -1) {
            $return = array_splice($lines, $begin, $end-$begin+1);
            $return = implode("\n", $return);
        }

        return $return;
    }

    public static function loadFile($file)
    {
        return file_get_contents($file);
    }
}

$code = Make::loadFile('kriss_feed.php');
$code = Make::replaceAutoload($code);
$code = Make::removeComments($code);
$code = Make::replaceRainTpl($code);
$code = Make::includeFiles($code);

echo $code;