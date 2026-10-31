<?php
    function getHelp() {
        echo "Usage: php syncer.php <COMMAND> [OPTION]\n";

        //autorun
        boldMessage("       autorun", true);
        echo "        - Discover new files, sync them and delete expired ones (ideal for cron).\n\n";

        //check connection
        boldMessage("       check-connection", true);
        echo "        - Check the connection to both FTP servers.\n\n";

        //check email
        boldMessage("       check-email", true);
        echo "        - Send a test email.\n\n";

        //check discord
        boldMessage("       check-discord [ping]", true);
        echo "        - Send a test Discord message. Add 'ping' to also mention the configured user.\n\n";

        //check settings
        boldMessage("       check-settings", true);
        echo "        - Validate the values in settings.php.\n\n";

        //create DB
        boldMessage("       create-db", true);
        echo "        - Create a new database. ";
        errorMessage("(WARNING: This deletes all data in the existing database!)\n");

        //delete
        boldMessage("       delete", true);
        echo "        - Delete expired files from the DESTINATION server.\n\n";

        //discovery
        boldMessage("       discovery [list|safe|light|classic]", true);
        echo "        - Discover new files on the ORIGIN server.\n";
        echo "            list - list all files, including known ones; no database changes.\n";
        echo "            safe - list new files only; no database changes (default).\n";
        echo "            light - add new files to the database; do not postpone deletion when no new files are found.\n";
        echo "            classic - add new files to the database; postpone deletion when no new files are found.\n\n";

        //extend-backup
        boldMessage("       extend-backup <DAYS>", true);
        echo "        - Postpone the deletion of all files by DAYS (a negative number brings it forward).\n\n";

        //fix
        boldMessage("       fix <upload|delete|error>", true);
        echo "        - Reset files stuck in an unfinished or failed state.\n";
        echo "            upload - move all files from state '" . getState(1) . "' to '" . getState(0) . "'.\n";
        echo "            delete - move all files from state '" . getState(3) . "' to '" . getState(2) . "'.\n";
        echo "            error - move all files from state '" . getState(5) . "' to '" . getState(0) . "'.\n\n";

        //help
        boldMessage("       help", true);
        echo "        - Show this help.\n\n";

        //sync
        boldMessage("       sync", true);
        echo "        - Copy new files to the DESTINATION server.\n\n";
    }

    function checkInstalledSqlite() {
        if (!extension_loaded('pdo_sqlite')) {
            errorMessage("ERROR: The PDO SQLite extension is not installed. Please install it and try again.");
            exit(1);
        }
    }

    function checkInstalledFtp() {
        if (!extension_loaded('ftp')) {
            errorMessage("ERROR: The FTP extension is not installed. Please install it and try again.");
            exit(1);
        }
    }

    function checkConnection() {
        echo "Checking the connection to the ORIGIN server... ";
        $origin = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);

        if ($origin !== false) {
            successMessage("OK");
        }

        echo "Checking the connection to the DESTINATION server... ";
        $destination = getFtpConnection(DEST_HOST, DEST_PORT, DEST_USER, DEST_PASS, DEST_PATH);

        if ($destination !== false) {
            successMessage("OK");
        }

        if (ORIGIN_HOST == DEST_HOST && ORIGIN_PORT == DEST_PORT && ORIGIN_USER == DEST_USER && ORIGIN_PASS == DEST_PASS && ORIGIN_PATH == DEST_PATH) {
            errorMessage("WARNING: The ORIGIN and DESTINATION servers are the same.");
        }
    }

    function checkSettings() {
        if (!is_numeric(KEEP_FILES_FOR) || KEEP_FILES_FOR < 0 || KEEP_FILES_FOR != round(KEEP_FILES_FOR)) {
            errorMessage("ERROR: KEEP_FILES_FOR must be a whole number greater than or equal to 0.");
        } else if (KEEP_FILES_FOR == 0) {
            successMessage("KEEP_FILES_FOR is set to 0, files will be kept forever.");
        } else {
            $days = KEEP_FILES_FOR . " " . ngettext("day", "days", KEEP_FILES_FOR);
            successMessage("KEEP_FILES_FOR is set to $days.", false);
            echo " Files will be deleted from the DESTINATION server after this period.\n";
        }

        if (!is_numeric(EXTEND_BACKUP_ON_ERROR) || EXTEND_BACKUP_ON_ERROR < 0 || EXTEND_BACKUP_ON_ERROR != round(EXTEND_BACKUP_ON_ERROR)) {
            errorMessage("ERROR: EXTEND_BACKUP_ON_ERROR must be a whole number greater than or equal to 0.");
        } else if (EXTEND_BACKUP_ON_ERROR == 0) {
            successMessage("EXTEND_BACKUP_ON_ERROR is set to 0, deletion will not be postponed when no new files are found.");
        } else {
            $days = EXTEND_BACKUP_ON_ERROR . " " . ngettext("day", "days", EXTEND_BACKUP_ON_ERROR);
            successMessage("EXTEND_BACKUP_ON_ERROR is set to $days.", false);
            echo " Deletion will be postponed by $days whenever no new files are found.\n";
        }

        if (SEND_EMAIL == "no") {
            successMessage("SEND_EMAIL is set to 'no', emails will not be sent.");
        } else if (SEND_EMAIL == "on_error") {
            successMessage("SEND_EMAIL is set to 'on_error', emails will be sent only when an error occurs.");
        } else if (SEND_EMAIL == "always") {
            successMessage("SEND_EMAIL is set to 'always', an email will be sent after every run.");
        } else {
            errorMessage("ERROR: SEND_EMAIL must be 'no', 'on_error' or 'always'. Please fix it in settings.php.");
        }

        if (SEND_DISCORD_HOOKS == "no") {
            successMessage("SEND_DISCORD_HOOKS is set to 'no', Discord messages will not be sent.");
        } else if (SEND_DISCORD_HOOKS == "on_error") {
            successMessage("SEND_DISCORD_HOOKS is set to 'on_error', Discord messages will be sent only when an error occurs.");
        } else if (SEND_DISCORD_HOOKS == "always") {
            successMessage("SEND_DISCORD_HOOKS is set to 'always', a Discord message will be sent after every run.");
        } else {
            errorMessage("ERROR: SEND_DISCORD_HOOKS must be 'no', 'on_error' or 'always'. Please fix it in settings.php.");
        }

        if (PING_USER == "no") {
            successMessage("PING_USER is set to 'no', the user will not be mentioned in Discord messages.");
        } else if (PING_USER == "on_error") {
            successMessage("PING_USER is set to 'on_error', the user will be mentioned only when an error occurs.");
        } else if (PING_USER == "always") {
            successMessage("PING_USER is set to 'always', the user will be mentioned in every Discord message.");
        } else {
            errorMessage("ERROR: PING_USER must be 'no', 'on_error' or 'always'. Please fix it in settings.php.");
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