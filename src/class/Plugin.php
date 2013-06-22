<?php
/**
 * Plugin class can be used to manage plugins
 */
class Plugin
{
    public static $dir = "plugins";
    public static $hooks = array();

    public static function init() {
        $arrayPlugins = glob(self::$dir. '/*.php');
      
        if(is_array($arrayPlugins)) {  
            foreach($arrayPlugins as $plugin) {  
                include $plugin;  
            }  
        }
    }

    public static function addHook($hookName, $functionName, $priority = 10) {
        self::$hooks[$hookName][$priority][] = $functionName;
    } 

    public static function callHook($hookName, $hookArguments = null) {
	if(isset(self::$hooks[$hookName])) {
            ksort(self::$hooks[$hookName]);
            foreach (self::$hooks[$hookName] as $hooks) {
                foreach($hooks as $functionName) {
                    call_user_func_array($functionName, $hookArguments);
                }    
            }
        } 
    }
}
