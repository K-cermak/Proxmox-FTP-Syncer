<?php
    function getHelp() {
        echo "Usage: php syncer.php [OPTION]\n";
    
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

        //discovery
        boldMessage("       discovery", false);
        echo " [list|safe|light|classic] - discover new files\n";
        echo "            list - just list all files (even old), no changes in DB\n";
        echo "            safe - just list new files, no changes in DB [default]\n";
        echo "            light - will add new files in DB, but wont edit files deletion date when not found anything new\n";
        echo "            classic - will add new files in DB, will edit files deletion date when not found anything new\n";

        //help
        boldMessage("       help", false);
        echo " - display this help\n";
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

        //if PAUSE_SYNC_ON_ERROR is not number, is not set or is less than 0, or is decimal
        if (!is_numeric(PAUSE_SYNC_ON_ERROR) || PAUSE_SYNC_ON_ERROR < 0 || PAUSE_SYNC_ON_ERROR != round(PAUSE_SYNC_ON_ERROR)) {
            errorMessage("ERROR: PAUSE_SYNC_ON_ERROR is not set, is not a number, is less than 0 or is decimal.");
        } else if (PAUSE_SYNC_ON_ERROR == 0) {
            successMessage("PAUSE_SYNC_ON_ERROR is set to 0, files will be deleted as usual if new files are not detected.");
        } else {
            $days = PAUSE_SYNC_ON_ERROR . " " . ngettext("day", "days", PAUSE_SYNC_ON_ERROR);
            successMessage("PAUSE_SYNC_ON_ERROR is set to $days.", false);
            echo " Deleting files will be delayed for $days if synchronization fails.\n";
        }
    }
?>