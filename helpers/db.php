<?php
    function checkInstalledSqlite() {
        if (!extension_loaded('pdo_sqlite')) {
            errorMessage("ERROR: PDO - SQLite extension is not installed. Please install it and try again.");
            exit(1);
        }
    }

    function build() {
        if (!canDelete()) {
            return;
        }


        //continue
    }

    function canDelete() {
        if (!file_exists(DB_FILE)) {
            return True;
        }

        errorMessage("Are you sure you want to DELETE your old database and create a new one? This operation is irreversible! Type 'destroy' to continue: ");
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        if(trim($line) != 'destroy'){
            echo "Cancelled..\n";
            return False;
        }

        fclose($handle);
        //delete old db
        unlink(DB_FILE);
        return True;
    }
?>