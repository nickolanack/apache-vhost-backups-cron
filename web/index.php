<?php

/**
 * simple html page to display backup files, and allow downloads.
 * this should be proctected by basic auth.
 */
$dir = dirname(__DIR__);
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

foreach (array_filter(scandir(), 
    function ($file) use($dir) {
        
        if (is_file($dir . DS . $file)) {
            return true;
        }
        
        return false;
    }) as $p) {}


