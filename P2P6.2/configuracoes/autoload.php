<?php  

/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */

spl_autoload_register(function($class) {
    if(file_exists(__DIR__ . '/../classes/'.$class.'.class.php'))
        include __DIR__ . '/../classes/'.$class.'.class.php';
});