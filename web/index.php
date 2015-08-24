<?php

/**
 * simple html page to display backup files, and allow downloads.
 * this should be proctected by basic auth.
 */
require __DIR__ . '/vendor/autoload.php';

$dir = dirname(__DIR__);
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

$files = array_filter(scandir(), 
    function ($file) use($dir) {
        
        if (is_file($dir . DS . $file)) {
            return true;
        }
        
        return false;
    });

foreach ($files as $p) {
    
    echo $p;
}


