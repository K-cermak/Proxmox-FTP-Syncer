<?php
    /* DATABASE */
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


    /* FILES */
    function getFileData($connection, $fileName) {
        $stmt = $connection->prepare("SELECT * FROM files WHERE fileName = :fileName");
        $stmt->bindParam(':fileName', $fileName);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    function addFile($connection, $fileName, $toDelete) {
        $stmt = $connection->prepare("INSERT INTO files (fileName, toDelete) VALUES (:fileName, :toDelete)");
        $stmt->bindParam(':fileName', $fileName);
        $stmt->bindParam(':toDelete', $toDelete);
        $stmt->execute();
    }


    function getFilesInState($connection, $state) {
        $stmt = $connection->prepare("SELECT * FROM files WHERE state = :state");
        $stmt->bindParam(':state', $state);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function editDeletionDate($connection, $days) {
        //get all files in state 0 / 1 / 2
        $stmt = $connection->prepare("SELECT * FROM files WHERE state < 3");
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        for ($i = 0; $i < count($files); $i++) {
            $stmt = $connection->prepare("UPDATE files SET toDelete = :toDelete WHERE fileName = :fileName");
            $stmt->bindParam(':fileName', $files[$i]["fileName"]);
            $toDelete = date("Y-m-d H:i:s", strtotime("+" . $days . " days", strtotime($files[$i]["toDelete"])));
            $stmt->bindParam(':toDelete', $toDelete);
            $stmt->execute();
        }
    }


    function changeFileState($connection, $fileName, $state) {
        $stmt = $connection->prepare("UPDATE files SET state = :state WHERE fileName = :fileName");
        $stmt->bindParam(':fileName', $fileName);
        $stmt->bindParam(':state', $state);
        $stmt->execute();
    }


    function getFilesToDelete($connection) {
        $time = date("Y-m-d H:i:s");
        $stmt = $connection->prepare("SELECT * FROM files WHERE toDelete <= :time AND state = 2");
        $stmt->bindParam(':time', $time);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function extendBackup($days) {
        //if days is not number, is not set or is less than 0, or is decimal
        if (!is_numeric($days) || $days != round($days)) {
            errorMessage("ERROR: Days is not set, is not a number or is decimal.");
            return;
        }

        $connection = connectToDb();
        editDeletionDate($connection, $days);
        if ($days > 0) {
            successMessage("Backup extended for $days " . ngettext("day", "days", $days) . ".");
        } else {
            successMessage("Backup decreased for " . abs($days) . " " . ngettext("day", "days", abs($days)) . ".");
        }
    }


    function fixErrors($fixing) {
        if ($fixing != "upload" && $fixing != "delete") {
            errorMessage("ERROR: Invalid argument. Use 'upload' or 'delete'.");
            return;
        }

        if ($fixing == "upload") {
            $state = 1;
        } else if ($fixing == "delete") {
            $state = 3;
        }

        //get all files in state 1 / 3
        $connection = connectToDb();
        $stmt = $connection->prepare("SELECT * FROM files WHERE state = :state");
        $stmt->bindParam(':state', $state);
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($files) == 0) {
            successMessage("Database is clean. No errors found.");
            return;
        }

        for ($i = 0; $i < count($files); $i++) {
            $fileName = $files[$i]["fileName"];
            changeFileState($fileName, $state - 1, $connection);
        }

        $text = ngettext("file", "files", count($files));
        successMessage("All files in state '". getState($state) ."' are now in state '" . getState($state - 1) . "'. (" . count($files) . " " . $text . ")");
        echo "Please run 'php syncer.php autorun' to continue.\n";
    }

?>