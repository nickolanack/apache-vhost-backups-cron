<?php

/**
 * simple html page to display backup files, and allow downloads.
 * this should be proctected by basic auth.
 */
include_once __DIR__ . '/vendor/nickolanack/scaffolds/defines.php';

HTML('document', 
    array(
        'buffered' => false, // default is true, it would buffer the body, and actual process the body before the header
                             // so that includes could be added in the body and written to the head
        'title' => 'Vhost Backup Files',
        'generator' => 'Nick Blackwell | https://people.ok.ubc.ca/nblackwe',
        'header' => function () {
            ?>
<style type="text/css">
body {
	margin: 30px;
	margin-top: 65px;
	background-color: white;
	min-height: 100px;
	border-radius: 5px;
	border: 1px solid rgba(0, 0, 0, 0.06);
	height: calc(100% - 62px);
	font-family: monospace;
	color: darkslateblue;
}

body:before {
	content: "Apache Log Monitor";
	position: absolute;
	top: 19px;
	left: 40px;
	font-family: sans-serif;
	font-weight: 100;
	font-size: 30px;
}

body:after {
	content: attr(data-state);
	display: inline-block;
	height: 50px;
	line-height: 45px;
	text-indent: 20px;
}

html {
	background-color: #f9f9f9;
	height: 100%;
}

body>div {
	padding: 10px;
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);
	overflow-wrap: break-word;
}
</style>
<?php
        },
        'body' => function () {
            
            HTML('article', 
                array(
                    'author' => 'Nick Blackwell',
                    'authorLink' => 'https://people.ok.ubc.ca/nblackwe',
                    'title' => 'Backup Files',
                    'text' => array(
                        function () {
                            $dir = dirname(__DIR__);
                            if (!defined('DS')) {
                                define('DS', DIRECTORY_SEPARATOR);
                            }
                            
                            $files = array_filter(scandir($dir), 
                                function ($file) use($dir) {
                                    
                                    if (is_file($dir . DS . $file)) {
                                        return true;
                                    }
                                    
                                    return false;
                                });
                            
                            foreach ($files as $p) {
                                
                                echo $p . "<br/>";
                            }
                        }
                    ),
                    'footer' => 'copyright Nick Blackwell ' . date('Y')
                ));
        }
    ));



