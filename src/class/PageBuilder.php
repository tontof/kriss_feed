<?php
/**
 * This class is in charge of building the final page.
 * (This is basically a wrapper around RainTPL which pre-fills some fields.)
 * p = new PageBuilder;
 * p.assign('myfield','myvalue');
 * p.renderPage('mytemplate');
 */
class PageBuilder
{
    private $pageClass;
    public $var = array();

    /**
     * __construct
     *
     * @param string $pageClass name of the class containing page template
     */
    public function __construct($pageClass)
    {
        $this->pageClass = $pageClass;
        $pageClass::$pb = $this;
    }

    /**
     * The following assign() method is basically the same as RainTPL
     * (except that it's lazy)
     *
     * @param string $variable name of the variable
     * @param mixed  $value    value of the variable
     */
    public function assign($variable, $value = null)
    {
        if (is_array($variable)) {
            $this->var += $variable;
        } else {
            $this->var[$variable] = $value;
        }
    }

    /**
     * Render a specific page (using a template).
     * eg. pb.renderPage('picwall')
     * 
     * @param string $page page to render
     *
     * @return boolean true if no problem, false if page does not exist
     */
    public function renderPage($page, $exit = true)
    {
        $this->assign('template', $page);
        $method = $page.'Tpl';
        if (method_exists($this->pageClass, $method)) {
            $classPage = new $this->pageClass;
            $classPage->init($this->var);
            ob_start();
            $classPage->$method();
            ob_end_flush();
        } else {
            $this->draw($page);
        }
        if ($exit) {
            exit();
        }

        return true;
    }

    public function draw($file)
    {
        include "inc/lib/raintpl/rain.tpl.class.php";
        raintpl::configure( 'tpl_dir', "class/tpl/");
        if (!is_dir('tmp')) { mkdir('tmp',0705); chmod('tmp',0705); }
        raintpl::configure( 'cache_dir', "tmp/");
        raintpl::configure( 'path_replace', false );

        $this->tpl = new RainTPL;
        $this->tpl->assign($this->var);
        $this->tpl->draw($file);
        //include $file;
    }
}
