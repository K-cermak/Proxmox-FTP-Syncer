<?php
    define("CREDENTIALS_FILE", "credentials.php");
    define("DB_FILE", "/crons/db.sqlite"); //should be absolute path
    
    define("KEEP_FILES_FOR", 90); //after sync files will be kept for 90 days, 0 will keep files forever
    define("EXTEND_BACKUP_ON_ERROR", 8); //if not discovered new files, old files will be kept for 8 more days, 0 wont modify the date

    define("SEND_EMAIL", "always"); //no, on_error, always
?>