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

        //state = 0 - discovered, 1 - uploading, 2 - uploaded, 3 - deleting, 4 - deleted, 5 - lost

        $connection = connectToDb();
        $connection->exec("CREATE TABLE `files` (
            `fileName` TEXT NOT NULL,
            `state` INTEGER NOT NULL DEFAULT 0,
            `discoveredOn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `toDelete` DATETIME
        )");

        successMessage("Database created successfully.");
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
        unlink(DB_FILE); //delete old db
        return True;
    }


    function connectToDb() {
        $conn = new PDO("sqlite:" . DB_FILE);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //enable foreign keys
        $conn->exec("PRAGMA foreign_keys = ON;");
        if (!$conn) {
            errorMessage("ERROR: Could not connect to the database.");
            exit(1);
        }
        return $conn;
    }
?>