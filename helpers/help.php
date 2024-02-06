<?php
    function getHelp() {
        echo "Usage: php syncer.php [OPTION]\n";
    
        //autorun
        boldMessage("       autorun", false);
        echo " - discover new files, synchronize and delete old files (optimal for cron)\n";

        //check connection
        boldMessage("       check-connection", false);
        echo " - check the connection to the FTP servers\n";

        //check settings
        boldMessage("       check-settings", false);
        echo " - check the settings\n";

        //create DB
        boldMessage("       create-db", false);
        echo " - create a new database";
        errorMessage(" (WARNING: This will delete all data in the current database!)");

        //delete
        boldMessage("       delete", false);
        echo " - delete old files\n";

        //discovery
        boldMessage("       discovery", false);
        echo " [list|safe|light|classic] - discover new files\n";
        echo "            list - just list all files (even old), no changes in DB\n";
        echo "            safe - just list new files, no changes in DB [default]\n";
        echo "            light - will add new files in DB, but wont edit files deletion date when not found anything new\n";
        echo "            classic - will add new files in DB, will edit files deletion date when not found anything new\n";

        //extend-backup
        boldMessage("       extend-backup", false);
        echo " [DAYS] - extend the backup for DAYS (negative will decrease the deletion date)\n";

        //fix
        boldMessage("       fix", false);
        echo " [upload|delete] - fix errors in the database\n";
        echo "            upload - change state of all files in state '" . getState(1) . "' to '" . getState(0) . "'\n";
        echo "            delete - change state of all files in state '" . getState(3) . "' to '" . getState(2) . "'\n";
        
        //help
        boldMessage("       help", false);
        echo " - display this help\n";

        //sync
        boldMessage("       sync", false);
        echo " - synchronize files to DESTINATION\n";
    }


    function checkInstalledSqlite() {
        if (!extension_loaded('pdo_sqlite')) {
            errorMessage("ERROR: PDO - SQLite extension is not installed. Please install it and try again.");
            exit(1);
        }
    }


    function checkConnection() {
        echo "Checking connection to the ORIGIN server... ";
        $origin = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);

        if ($origin !== false) {
            successMessage("Connection to the ORIGIN server successful.");
        }

        echo "Checking connection to the DESTINATION server... ";
        $destination = getFtpConnection(DEST_HOST, DEST_PORT, DEST_USER, DEST_PASS, DEST_PATH);

        if ($destination !== false) {
            successMessage("Connection to the DESTINATION server successful.");
        }

        if (ORIGIN_HOST == DEST_HOST && ORIGIN_PORT == DEST_PORT && ORIGIN_USER == DEST_USER && ORIGIN_PASS == DEST_PASS && ORIGIN_PATH == DEST_PATH) {
            errorMessage("WARNING: The ORIGIN and DESTINATION servers are the same.");
        }
    }


    function checkSettings() {
        //if KEEP_FILES_FOR is not number, is not set or is less than 0, or is decimal
        if (!is_numeric(KEEP_FILES_FOR) || KEEP_FILES_FOR < 0 || KEEP_FILES_FOR != round(KEEP_FILES_FOR)) {
            errorMessage("ERROR: KEEP_FILES_FOR is not set, is not a number, is less than 0 or is decimal.");
        } else if (KEEP_FILES_FOR == 0) {
            successMessage("KEEP_FILES_FOR is set to 0, files will be kept forever.");
        } else {
            $days = KEEP_FILES_FOR . " " . ngettext("day", "days", KEEP_FILES_FOR);
            successMessage("KEEP_FILES_FOR is set to $days.", false);
            echo " Files will be deleted after this period.\n";
        }

        //if EXTEND_BACKUP_ON_ERROR is not number, is not set or is less than 0, or is decimal
        if (!is_numeric(EXTEND_BACKUP_ON_ERROR) || EXTEND_BACKUP_ON_ERROR < 0 || EXTEND_BACKUP_ON_ERROR != round(EXTEND_BACKUP_ON_ERROR)) {
            errorMessage("ERROR: PAUSE_SYNC_ON_ERROR is not set, is not a number, is less than 0 or is decimal.");
        } else if (EXTEND_BACKUP_ON_ERROR == 0) {
            successMessage("PAUSE_SYNC_ON_ERROR is set to 0, files will be deleted as usual if new files are not detected.");
        } else {
            $days = EXTEND_BACKUP_ON_ERROR . " " . ngettext("day", "days", EXTEND_BACKUP_ON_ERROR);
            successMessage("PAUSE_SYNC_ON_ERROR is set to $days.", false);
            echo " Deleting files will be delayed for $days if synchronization fails.\n";
        }
    }
    

    function getState($state) {
        if ($state == 0) {
            return "New";
        } else if ($state == 1) {
            return "Syncing";
        } else if ($state == 2) {
            return "Synced";
        } else if ($state == 3) {
            return "Deleting from DESTINATION server";
        } else if ($state == 4) {
            return "Deleted from DESTINATION server";
        } else if ($state == 5) {
            return "Error / Lost";
        }
    }
?>