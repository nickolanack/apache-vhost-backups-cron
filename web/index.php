<?php

/**
 * simple html page to display backup files, and allow downloads.
 * this should be proctected by basic auth.
 */
include_once __DIR__ . '/vendor/nickolanack/scaffolds/scaffolds/defines.php';

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
	content: "Apache Host Backups";
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

p.author {
	display: none;
}
</style>
<?php
        },
        'body' => function () {

            function formatBytes($bytes, $precision = 2) {

                $unit = [
                    "B",
                    "KB",
                    "MB",
                    "GB"
                ];
                $exp = floor(log($bytes, 1024)) | 0;
                return round($bytes / (pow(1024, $exp)), $precision) . $unit[$exp];
            }
            
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
                            ?><ul><?php
                            foreach ($files as $file) {
                                ?><li><a href="?download="
		<?php echo $file; ?>><?php
                                echo $file?></a> | <?php echo date('Y-m-d H:s:i', strtotime(filectime($dir . DS . $file))); ?> | <?php echo formatBytes(filesize($dir . DS . $file)); ?></li><?php
                            }
                            ?></ul><?php
                        }
                    ),
                    'footer' => function () {
                        HTML('license.mit');
                    }
                ));
        }
    ));



