<?php
    //disable time limit
    set_time_limit(0);

    //for email
    $emailStats = array(
        "detected" => 0,
        "lost" => 0,
        "uploaded" => 0,
        "deleted" => 0,
        "isok" => true,
        "errors" => []
    );

    require "settings.php";
    require CREDENTIALS_FILE;
    require "helpers/help.php";
    require "helpers/terminal.php";
    require "helpers/db.php";
    require "helpers/ftp.php";
    require 'email/Exception.php';
    require 'email/PHPMailer.php';
    require 'email/SMTP.php';
    require 'helpers/email.php';

    copyright();
    checkInstalledSqlite();

    $arg = $argv[1] ?? '?';
    if ($arg == "?" || $arg == "help") {
        if ($arg == "?") {
            errorMessage("ERROR: Not command line arguments found.");
        }
        getHelp();
    
    } else if ($arg == "create-db") {
        build();
    } else if ($arg == "check-connection") {
        checkConnection();
    } else if ($arg == "check-settings") {
        checkSettings();
    } else if ($arg == "check-email") {
        testEmail();
    } else if ($arg == "discovery") {
        $type = $argv[2] ?? "safe";
        getNewFiles($type);
        getLostFiles($type);
    } else if ($arg == "sync") {
        sync();
    } else if ($arg == "delete") {
        deleteOld();
    } else if ($arg == "autorun") {
        $start = microtime(true);
        getNewFiles("classic");
        getLostFiles("classic");
        sync();
        deleteOld();

        $end = microtime(true);
        $emailStats["time"] = $end - $start;
        sendReport();        

    } else if ($arg == "extend-backup") {
        $days = $argv[2] ?? 0;
        extendBackup($days);
    } else if ($arg == "fix") {
        $fixing = $argv[2] ?? "-";
        fixErrors($fixing);
    } else {
        errorMessage("ERROR: Invalid command line argument.");
        getHelp();
    }


?>