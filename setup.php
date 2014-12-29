<?php
require 'proc_exec.php';

// Project folder is current folder
$pf = getcwd();

# Get config.php directory
$cd = file_exists("$pf/config.sample.php") ? $pf : "$pf/config";

// Create config.php
e('Making a copy of config.sample.php to config.php', "cp $cd/config.sample.php $cd/config.php");

// Replace database password in config.php
e('Replacing DATABASE_PASSWORD in config.php', "sed \"s/'DATABASE_PASSWORD', '\*\*\*\*\*\*'/'DATABASE_PASSWORD', ''/g\" -i $cd/config.php");

e('Create database',"mysql -u root -e 'create database teodor;'");
e('Import SQL', "mysql -u root -D teodor < doc/database.sql");

function e($msg, $cmd = null)
{
    $cmd = empty($cmd) ? $msg : $cmd;

    // Pretty print comment and command
    echo "\n$msg...\n$cmd\n";

    // Execute command
    $exit_code = proc_exec($cmd);

    // Abort script if the command failed
    if ($exit_code > 0){
        exit(1);
    }
}