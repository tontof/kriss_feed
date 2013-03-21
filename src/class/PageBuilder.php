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
    private $tpl; // For lazy initialization

    private $pageClass;

    public $var = array();

    /**
     * __construct
     *
     * @param string $pageClass name of the class containing page template
     */
    public function __construct($pageClass)
    {
        $this->tpl = false;
        $this->pageClass = $pageClass;
    }

    /**
     * initialize
     */
    private function initialize()
    {
        $this->tpl = true;
        $ref = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
        $this->assign('referer', $ref);
    }

    // 
    /**
     * The following assign() method is basically the same as RainTPL
     * (except that it's lazy)
     *
     * @param string $variable name of the variable
     * @param mixed  $value    value of the variable
     */
    public function assign($variable, $value = null)
    {
        if ($this->tpl === false) {
            $this->initialize(); // Lazy initialization
        }
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
     */
    public function renderPage($page)
    {
        if ($this->tpl===false) {
            $this->initialize(); // Lazy initialization
        }
        $method = $page.'Tpl';
        if (method_exists($this->pageClass, $method)) {
            $this->assign('template', $page);
            $classPage = new $this->pageClass;
            $classPage->init($this->var);
            ob_start();
            $classPage->$method();
            ob_end_flush();
        } else {
            die("renderPage does not exist: ".$page);
        }
    }
}
