#! /usr/bin/php
<?PHP
$webRoot = '/srv/www/vhosts/production';
$webDir = 'http';
$configFile = 'backup.json';

// both of the following global variables should be set to false
// for proper operation. $dryrun=true; will cause all shell functions
// (other than ls) to be skipped (but printed to console)
// $dryroll=true; will cause only the the roll function to be
// skipped (while $dryrun=false;)
global $dryrun;
$dryrun = false;

global $dryroll;
$dryroll = false;

/**
 * wrapper for shell_exec, prints, the command and output
 * or just the command when global $dryrun=true;
 *
 * @param string $cmd            
 */
function shell_exec_($cmd) {

    global $dryrun;
    if ($dryrun) {
        echo '   # ' . $cmd . "\n";
    } else {
        echo '   -> ' . $cmd . "\n";
        echo shell_exec($cmd);
    }
}

/**
 * removes the oldest files matching a name pattern, so that $num files remain.
 *
 * @param string $name
 *            used in ls, (should be an absolute path) with filename pattern ending in *
 * @param number $num
 *            number of log files to keep
 */
function rollBackups($name, $num = 2) {

    $lsBackupsCmd = 'ls -tU1 ' . $name;
    echo '   -> ' . $lsBackupsCmd . "\n";
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

/**
 * bytes to human readable string
 *
 * @param int $bytes            
 * @return string
 */
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

/**
 * Backup Script
 */

$lsVhostsCmd = 'ls -1 ' . $webRoot;
echo '-> ' . $lsVhostsCmd . "\n";
$vhostDocumentRoots = explode("\n", trim(shell_exec($lsVhostsCmd)));
echo 'Scanning ' . count($vhostDocumentRoots) . ' Vhosts' . "\n";

$countNoConfigs = 0;
$countTasks = 0;
$countErrors = 0;

foreach ($vhostDocumentRoots as $vhostFolder) {
    
    $vhostRoot = $webRoot . '/' . trim($vhostFolder);
    $documentRoot = $vhostRoot . '/' . $webDir;
    $configPath = $vhostRoot . '/' . $configFile;
    
    if (file_exists($documentRoot) && is_dir($documentRoot)) {
        if (file_exists($configPath)) {
            
            echo '   ' . $vhostRoot . ":" . "\n";
            
            $config = json_decode(file_get_contents($configPath));
            if (!is_object($config)) {
                echo 'Unable to read: ' . $configPath . "\n";
                $countErrors ++;
                continue;
            }
            
            chdir($vhostRoot);
            echo '   # (cd ' . getcwd() . ')' . "\n";
            $zipPrefix = 'host_backup_';
            $dateStr = date('Y-M-D H:i');
            
            $zip = $zipPrefix . $dateStr . '.zip';
            $zipCmd = 'zip -r -p -q ' . escapeshellarg($zip) . ' ' . escapeshellarg($webDir);
            
            if (key_exists('exclude', $config)) {
                $exclude = $config->exclude;
                if (is_array($exclude)) {
                    $exclude = implode(' ', $exclude);
                }
                if (is_string($exclude)) {
                    $zipCmd .= ' -x ' . $exclude;
                } else {
                    echo 'Expected String or Array for key: `exclude` in backup.json' . "\n";
                    $countErrors ++;
                    continue;
                }
            }
            
            echo '   archiving folder `' . $webDir . '` -> ' . $zip . "\n";
            $time = microtime(true);
            shell_exec_($zipCmd);
            echo '   ' . round(microtime(true) - $time, 2) . 's ' . formatBytes(filesize($zip)) . "\n";
            echo '   rolling backups like:  `' . $zipPrefix . '*' . '`' . "\n";
            rollBackups($vhostRoot . '/' . $zipPrefix . '*', 2);
            
            if (key_exists('database', $config)) {
                $db = $config->database;
                if (is_string($db)) {
                    $sqlPrefix = 'db_backup_';
                    $sql = $sqlPrefix . $dateStr . '.sql';
                    $dbCmd = 'mysqldump ' . escapeshellarg($db) . ' > ' . escapeshellarg($sql);
                    echo '   dumping database `' . $db . '` -> ' . $sql . "\n";
                    $time = microtime(true);
                    shell_exec_($dbCmd);
                    echo '   ' . round(microtime(true) - $time, 2) . 's ' . formatBytes(filesize($sql)) . "\n";
                    
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
    if ($countErrors) {
        echo 'Aborted ' . $countErrors . ' vhost', ($countErrors == 1 ? ' becuase of an error' : 's becuase of errors') .
             "\n";
    }
    echo 'Backed up ' . $countTasks . ' vhost', ($countTasks == 1 ? '' : 's') . "\n";
}
echo 'Done' . "\n";

