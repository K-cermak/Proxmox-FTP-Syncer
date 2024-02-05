<?php
    //todo:
        // create and reset DB
        // settings
        // sync
        // emails
        // license, README.. 


    //disable time limit
    set_time_limit(0);

    include "settings.php";
    include CREDENTIALS_FILE;
    include "helpers/help.php";
    include "helpers/terminal.php";
    include "helpers/db.php";
    copyright();
    checkInstalledSqlite();


    $arg = $argv[1] ?? '?';
    if ($arg == "?" || $arg == "help") {
        if ($arg == "?") {
            errorMessage("ERROR: Not command line arguments found.");
        }
        getHelp();
        exit(0);
    }

    if ($arg == "create-db") {
        build();
    }
?>