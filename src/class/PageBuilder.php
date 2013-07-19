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
    private function init()
    {
        $this->tpl = true;
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
            $this->init(); // Lazy initialization
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
     *
     * @return boolean true if no problem, false if page does not exist
     */
    public function renderPage($page, $exit = true)
    {
        if ($this->tpl===false) {
            $this->init(); // Lazy initialization
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
            return false;
        }
        if ($exit) {
            exit();
        }

        return true;
    }
}
