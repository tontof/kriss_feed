<?php
include("rain.tpl.class.php");

class Rain extends RainTPL
{
    public function compileTemplate( $template_code, $tpl_basedir )
    {
        return parent::compileTemplate( $template_code, $tpl_basedir );
    }
}