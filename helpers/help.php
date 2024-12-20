<?php
    function getHelp() {
        echo "Usage: php syncer.php [COMMAND] [OPTION]\n";
    
        //autorun
        boldMessage("       autorun", true);
        echo "        - Discover new files, synchronize and delete old files (optimal for cron).\n\n";

        //check connection
        boldMessage("       check-connection", true);
        echo "        - Check the connection to the FTP servers.\n\n";

        //check email
        boldMessage("       check-email", true);
        echo "        - Send test email.\n\n";

        //check discord
        boldMessage("       check-discord [ping]", true);
        echo "        - Send a test Discord message. Add 'ping' to include a ping to specified user in the message.\n\n";

        //check settings
        boldMessage("       check-settings", true);
        echo "        - Check the settings.\n\n";

        //create DB
        boldMessage("       create-db", true);
        echo "        - Create a new database. ";
        errorMessage("(WARNING: This will delete all data in the current database!)\n");

        //delete
        boldMessage("       delete", true);
        echo "        - Delete old files.\n\n";

        //discovery
        boldMessage("       discovery [list|safe|light|classic]", true);
        echo "        - Discover new files.\n";
        echo "            list - just list all files (even old), no changes in DB.\n";
        echo "            safe - just list new files, no changes in DB (default).\n";
        echo "            light - will add new files in DB, but wont edit files deletion date when not found anything new.\n";
        echo "            classic - will add new files in DB, will edit files deletion date when not found anything new.\n\n";

        //extend-backup
        boldMessage("       extend-backup [DAYS]", true);
        echo "        - Extend the backup for DAYS (negative will decrease the deletion date).\n\n";

        //fix
        boldMessage("       fix [upload|delete|error]", true);
        echo "        - Fix errors in the database.\n";
        echo "            upload - change state of all files in state '" . getState(1) . "' to '" . getState(0) . "'.\n";
        echo "            delete - change state of all files in state '" . getState(3) . "' to '" . getState(2) . "'.\n";
        echo "            error - change state of all files in state '" . getState(5) . "' to '" . getState(0) . "'.\n\n";
        
        //help
        boldMessage("       help", true);
        echo "        - Display this help page.\n\n";

        //sync
        boldMessage("       sync", true);
        echo "        - Synchronize files to DESTINATION server.\n\n";
    }

    function checkInstalledSqlite() {
        if (!extension_loaded('pdo_sqlite')) {
            errorMessage("ERROR: PDO - SQLite extension is not installed. Please install it and try again.");
            exit(1);
        }
    }

    function checkInstalledFtp() {
        if (!extension_loaded('ftp')) {
            errorMessage("ERROR: FTP extension is not installed. Please install it and try again.");
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
        if (!is_numeric(KEEP_FILES_FOR) || KEEP_FILES_FOR < 0 || KEEP_FILES_FOR != round(KEEP_FILES_FOR)) {
            errorMessage("ERROR: KEEP_FILES_FOR is not set, is not a number, is less than 0 or is decimal.");
        } else if (KEEP_FILES_FOR == 0) {
            successMessage("KEEP_FILES_FOR is set to 0, files will be kept forever.");
        } else {
            $days = KEEP_FILES_FOR . " " . ngettext("day", "days", KEEP_FILES_FOR);
            successMessage("KEEP_FILES_FOR is set to $days.", false);
            echo " Files will be deleted after this period.\n";
        }

        if (!is_numeric(EXTEND_BACKUP_ON_ERROR) || EXTEND_BACKUP_ON_ERROR < 0 || EXTEND_BACKUP_ON_ERROR != round(EXTEND_BACKUP_ON_ERROR)) {
            errorMessage("ERROR: PAUSE_SYNC_ON_ERROR is not set, is not a number, is less than 0 or is decimal.");
        } else if (EXTEND_BACKUP_ON_ERROR == 0) {
            successMessage("PAUSE_SYNC_ON_ERROR is set to 0, files will be deleted as usual if new files are not detected.");
        } else {
            $days = EXTEND_BACKUP_ON_ERROR . " " . ngettext("day", "days", EXTEND_BACKUP_ON_ERROR);
            successMessage("PAUSE_SYNC_ON_ERROR is set to $days.", false);
            echo " Deleting files will be delayed for $days if synchronization fails.\n";
        }

        if (SEND_EMAIL == "no") {
            successMessage("SEND_EMAIL is set to 'no', emails will not be sent.");
        } else if (SEND_EMAIL == "on_error") {
            successMessage("SEND_EMAIL is set to 'on_error', emails will be sent only on errors.");
        } else if (SEND_EMAIL == "always") {
            successMessage("SEND_EMAIL is set to 'always', emails will be sent even if there are no errors.");
        } else {
            errorMessage("ERROR: SEND_EMAIL is not set to 'no', 'on_error' or 'always', fix it in your settings.php file.");
        }

        if (SEND_DISCORD_HOOKS == "no") {
            successMessage("SEND_DISCORD_HOOKS is set to 'no', Discord messages will not be sent.");
        } else if (SEND_DISCORD_HOOKS == "on_error") {
            successMessage("SEND_DISCORD_HOOKS is set to 'on_error', Discord messages will be sent only on errors.");
        } else if (SEND_DISCORD_HOOKS == "always") {
            successMessage("SEND_DISCORD_HOOKS is set to 'always', Discord messages will be sent even if there are no errors.");
        } else {
            errorMessage("ERROR: SEND_DISCORD_HOOKS is not set to 'no', 'on_error' or 'always', fix it in your settings.php file.");
        }

        if (PING_USER == "no") {
            successMessage("PING_USER is set to 'no', user will not be pinged in Discord messages.");
        } else if (PING_USER == "on_error") {
            successMessage("PING_USER is set to 'on_error', user will be pinged only on errors.");
        } else if (PING_USER == "always") {
            successMessage("PING_USER is set to 'always', user will be pinged even if there are no errors.");
        } else {
            errorMessage("ERROR: PING_USER is not set to 'no', 'on_error' or 'always', fix it in your settings.php file.");
        }
    }

    function getState($state) {
        switch ($state) {
            case 0:
                return "New";
            case 1:
                return "Syncing";
            case 2:
                return "Synced";
            case 3:
                return "Deleting from DESTINATION server";
            case 4:
                return "Deleted from DESTINATION server";
            case 5:
                return "Error / Lost";
        }

        return "Unknown";
    }
?>