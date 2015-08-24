#! /usr/bin/php
<?PHP
$webRoot = '/srv/www/vhosts/production/';
$webDir = 'http';
$configFile = 'backup.json';

global $dryrun;
$dryrun = true;

global $dryroll;
$dryroll = true;

function shell_exec_($cmd) {

    global $dryrun;
    if ($dryrun) {
        echo '   # ' . $cmd . "\n";
    } else {
        shell_exec($cmd);
    }
}

function rollBackups($name, $num = 2) {

    $lsBackupsCmd = 'ls -tU1 ' . $name;
    echo '   # ' . $lsBackupsCmd . "\n";
    $roll = explode("\n", trim(shell_exec($lsBackupsCmd)));
    
    if (count($roll) == 1 && stripos($roll[0], 'No such file or directory') !== false) {
        echo '   - ' . $roll[0] . "\n";
        return;
    }
    
    usort($roll, function ($a, $b) {
        return filectime($b) - filectime($a);
    });
    
    global $dryroll;
    global $dryrun;
    
    $dryrunTemp = $dryrun;
    $dryrun = ($dryroll || $dryrun);
    // added the ability to dryrun just the roll function //restores $dryrun after running shell_exec_
    
    if (count($roll) > $num) {
        foreach (array_slice($roll, $num) as $old) {
            $rmCmd = 'rm \'' . $old . '\' -r -f';
            shell_exec_($rmCmd);
        }
    }
    $dryrun = $dryrunTemp;
}

$lsVhostsCmd = 'ls -1 ' . $webRoot;
echo '# ' . $lsVhostsCmd . "\n";
$vhostDocumentRoots = explode("\n", trim(shell_exec($lsVhostsCmd)));
echo 'Scanning ' . count($vhostDocumentRoots) . ' Vhosts' . "\n";
$countNoConfigs = 0;
$countTasks = 0;
foreach ($vhostDocumentRoots as $vhostRoot) {
    
    $vhostRoot = $webRoot . trim($vhostRoot);
    $documentRoot = $vhostRoot . '/' . $webDir;
    $configPath = $vhostRoot . '/' . $configFile;
    
    if (file_exists($documentRoot) && is_dir($documentRoot)) {
        if (file_exists($configPath)) {
            
            echo '   ' . $vhostRoot . ":" . "\n";
            
            $config = json_decode(file_get_contents($configPath));
            
            chdir(dirname($vhostRoot));
            $zipPrefix = 'host_backup_';
            $zip = $zipPrefix . date('Y-M-D H:i') . '.zip';
            $zipCmd = 'zip -r -p ' . escapeshellarg($zip) . ' ' . escapeshellarg($webDir);
            
            echo '   archiving folder `' . $webDir . '` -> ' . $zip . "\n";
            shell_exec_($zipCmd);
            
            echo '   rolling backups like:  `' . $zipPrefix . '*' . '`' . "\n";
            rollBackups($vhostRoot . '/' . $zipPrefix . '*', 2);
            
            if (key_exists('database', $config)) {
                $db = $config->database;
                if (is_string($db)) {
                    $sqlPrefix = 'db_backup_';
                    $sql = $sqlPrefix . date('Y-M-D H:i') . '.sql';
                    $dbCmd = 'mysqldump ' . escapeshellarg($db) . ' > ' . escapeshellcmd($sql);
                    echo '   dumping database `' . $db . '` -> ' . $sql . "\n";
                    shell_exec_($dbCmd);
                    
                    echo '   rolling backups like:  `' . $sqlPrefix . '*' . '`' . "\n";
                    rollBackups($vhostRoot . '/' . $sqlPrefix . '*', 2);
                }
            }
            $countTasks ++;
        } else {
            $countNoConfigs ++;
        }
    }
}

if ($countNoConfigs === count($vhostDocumentRoots)) {
    echo 'Did not find any backup.json files' . "\n";
} else {
    echo 'Done, backed up ' . $countTasks . ' vhost', ($countTasks == 1 ? '' : 's') . "\n";
}

