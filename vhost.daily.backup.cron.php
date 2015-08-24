#! /usr/bin/php
<?PHP
$webRoot = '/srv/www/vhosts/production/';
$webDir = 'http';
$configFile = 'backup.json';

global $dryrun;
$dryrun = true;

function shell_exec_($cmd) {

    global $dryrun;
    if ($dryrun) {
        echo $cmd . "\n";
    } else {
        shell_exec($cmd);
    }
}

function rollBackups($name, $num = 2) {

    $roll = explode("\n", trim(shell_exec_('ls -tUC1 ' . $name)));
    
    usort($roll, function ($a, $b) {
        return filectime($b) - filectime($a);
    });
    if (count($roll) > $num) {
        foreach (array_slice($roll, $num) as $old) {
            $rmCmd = 'rm \'' . $old . '\' -r -f';
            shell_exec_($rmCmd);
        }
    }
}

$vhostDocumentRoots = explode("\n", trim(shell_exec_('ls ' . ($webRoot))));
foreach ($vhostDocumentRoots as $vhostRoot) {
    
    $vhostRoot = $webRoot . trim($vhostRoot);
    $documentRoot = $vhostRoot . '/' . $webDir;
    $configPath = $vhostRoot . '/' . $configFile;
    
    if (file_exists($documentRoot) && file_exists($configPath)) {
        $config = json_decode(file_get_contents($configPath));
        
        chdir(dirname($vhostRoot));
        $zip = 'host_backup_' . date('Y-M-D H:i') . '.zip';
        $zipCmd = 'zip -r -p ' . escapeshellarg($zip) . ' ' . escapeshellarg($webDir);
        
        shell_exec_($zipCmd);
        
        if (key_exists('database', $config)) {
            $db = $config->database;
            if (is_string($db)) {
                
                $sql = 'db_backup_' . date('Y-M-D H:i') . '.sql';
                $dbCmd = 'mysqldump ' . escapeshellarg($db) . ' > ' . escapeshellcmd($sql);
                shell_exec_($dbCmd);
            }
        }
    }
}

