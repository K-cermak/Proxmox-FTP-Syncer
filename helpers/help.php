<?php
    function getHelp() {
        echo "Usage: php syncer.php [OPTION]\n";
        //help
        boldMessage("       help", false);
        echo " - display this help\n";
    
        //create DB
        boldMessage("       create-db", false);
        echo " - create a new database";
        errorMessage(" (WARNING: This will delete all data in the current database!)");

    }

?>