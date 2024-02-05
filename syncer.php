<?php
    //todo:
        // create and reset DB
        // settings
        // sync
        // emails

    include "terminal.php";
    copyright();

    $arg = $argv[1] ?? '?';
    if ($arg == "?" || $arg == "help") {
        if ($arg == "?") {
            errorMessage("ERROR: Not command line arguments found.");
        }
        include "help.php";
        getHelp();
        exit(0);
    }
?>