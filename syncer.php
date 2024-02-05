<?php
    //todo:
        // sync, autorun
        // fix not completed

        // emails
        // license, README.. 


    //disable time limit
    set_time_limit(0);

    include "settings.php";
    include CREDENTIALS_FILE;
    include "helpers/help.php";
    include "helpers/terminal.php";
    include "helpers/db.php";
    include "helpers/ftp.php";
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
    } else if ($arg == "discovery") {
        $type = $argv[2] ?? "safe";
        getNewFiles($type);
    }

    //job - conn, discovery, sync, delete old


?>