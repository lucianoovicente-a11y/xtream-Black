<?php  

/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */
public function __construct()
{
    spl_autoload_register(function($classname) {
        if(file_exists(app_path() . '/classes/".$class.".class.php'))
            include app_path() . '/classes/".$class.".class.php';
    });
}