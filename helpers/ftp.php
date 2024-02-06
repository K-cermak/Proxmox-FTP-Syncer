<?php
    function getFtpConnection($host, $port, $user, $pass, $path) {
        //disable error reporting
        error_reporting(0);

        //check if port is number
        if (!is_numeric($port)) {
            errorMessage("ERROR: Port must be a number.");
            return false;
        }

        $ftp = ftp_connect($host, $port);
        if ($ftp === false) {
            errorMessage("ERROR: Unable to connect to the FTP server.");
            return false;
        }
        $login = ftp_login($ftp, $user, $pass);
        if ($login === false) {
            errorMessage("ERROR: Unable to login to the FTP server.");
            return false;
        }
        $chdir = ftp_chdir($ftp, $path);
        if ($chdir === false) {
            errorMessage("ERROR: Unable to change directory on the FTP server.");
            return false;
        }
        return $ftp;

        //enable error reporting
        error_reporting(1);
    }


    function getNewFiles($type) {
        global $emailStats;

        $ftp = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
        if ($ftp === false) {
            $emailStats["isok"] = false;
            $emailStats["errors"][] = "Unable to connect to the ORIGIN server.";
            return;
        }

        if ($type == "list") {
            boldMessage("All files:");
        } else {
            boldMessage("New files:");
        }

        $newFiles = ftp_nlist($ftp, ".");
        $dbConnection = connectToDb(); 
        $listedAnythingNew = false;

        for ($i = 0; $i < count($newFiles); $i++) {
            //skip folders
            if (ftp_size($ftp, $newFiles[$i]) == -1) {
                continue;
            }

            //skip files that are already in the database
            if ($type != "list" && getFileData($dbConnection, $newFiles[$i]) !== false) {
                continue;
            }

            echo "    " . $newFiles[$i];
            $listedAnythingNew = true;

            //ading to DB
            if ($type == "light" || $type == "classic") {
                if (KEEP_FILES_FOR == 0) {
                    $toDeleteOn = "9999-12-31 23:59:59";
                } else {
                    $toDeleteOn = date("Y-m-d H:i:s", strtotime("+" . KEEP_FILES_FOR . " days"));
                }
                $emailStats["detected"] += 1;
                addFile($dbConnection, $newFiles[$i], $toDeleteOn);
                successMessage(" - added to the database.", false);
            }
            if ($type == "list") {
                //print state and deletion date
                $fileData = getFileData($dbConnection, $newFiles[$i]);
                echo " | State: " . getState($fileData["state"]) . ", Deletion date: " . $fileData["toDelete"];
            }

            echo "\n";
        }
        ftp_close($ftp);

        //when nothing found
        if (!$listedAnythingNew) {
            errorMessage("    Warning: No new files found.");
            $emailStats["isok"] = false;
            $emailStats["errors"][] = "No new files found.";
            if ($type == "classic" && EXTEND_BACKUP_ON_ERROR != 0) {
                editDeletionDate($dbConnection, EXTEND_BACKUP_ON_ERROR);
                successMessage("    Deletion date of all files has been updated.");
            }
        }
    }


    function getLostFiles($type) {
        global $emailStats;

        $ftp = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
        if ($ftp === false) {
            $emailStats["isok"] = false;
            $emailStats["errors"][] = "Unable to connect to the ORIGIN server.";
            return;
        }

        boldMessage("Lost files:");

        $dbConnection = connectToDb(); 
        $allFiles = getFilesInState($dbConnection, 0);
        $listedAnything = false;

        for ($i = 0; $i < count($allFiles); $i++) {
            //if file not in FTP
            if (ftp_size($ftp, $allFiles[$i]["fileName"]) != -1) {
                continue;
            }

            echo "    " . $allFiles[$i]["fileName"];
            $listedAnything = true;

            //editing DB
            if ($type == "light" || $type == "classic") {
                $emailStats["lost"] += 1;
                $emailStats["isok"] = false;
                changeFileState($dbConnection, $allFiles[$i]["fileName"], 5);
                successMessage(" - edited in the database.", false);
            }

            echo "\n";
        }
        ftp_close($ftp);

        //when nothing found
        if (!$listedAnything) {
            successMessage("    No lost files.");
        }
    }


    function sync() {
        global $emailStats;

        //get all files in DB with state 0
        $dbConnection = connectToDb();
        $files = getFilesInState($dbConnection, 0);

        if (count($files) == 0) {
            errorMessage("\nNo files to sync.");
            return;
        }
        
        $origin = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
        $destination = getFtpConnection(DEST_HOST, DEST_PORT, DEST_USER, DEST_PASS, DEST_PATH);

        if ($origin === false || $destination === false) {
            $emailStats["isok"] = false;
            $emailStats["errors"][] = "Unable to connect to the ORIGIN or DESTINATION server.";
            return;
        }

        $text = ngettext("file", "files", count($files));
        $errors = 0;

        echo "Syncing " . count($files) . " $text...\n";
        echo "Started at: " . date("Y-m-d H:i:s") . "\n";

        for ($i = 0; $i < count($files); $i++) {
            progressBar($i, count($files));
            $fileName = $files[$i]["fileName"];
            changeFileState($dbConnection, $fileName, 1);

            // download file from origin to temp
            $temp = tempnam(sys_get_temp_dir(), "syncer");
            ftp_get($origin, $temp, $fileName, FTP_BINARY);
            if (!file_exists($temp)) {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                echo "\n";
                errorMessage("ERROR: Unable to download the file from the ORIGIN server (File: $fileName).");
                continue;
            }

            // upload file from temp to destination
            ftp_put($destination, $fileName, $temp, FTP_BINARY);

            // delete file from temp
            unlink($temp);

            $emailStats["uploaded"] += 1;
            changeFileState($dbConnection, $fileName, 2);
        }

        progressBar(count($files), count($files));
        ftp_close($origin);
        echo "\nFinished at: " . date("Y-m-d H:i:s") . "\n";
        
        if ($errors == 0) {
            successMessage("Syncing successful.");
        } else {
            $emailStats["isok"] = false;
            $emailStats["errors"][] = "Syncing successful, but " . $errors . " files failed.";
            errorMessage("Successfully synced " . (count($files) - $errors) . "/" . count($files) . " " . $text . ", " .  $errors . " failed.");
        }
    }


    function deleteOld() {
        global $emailStats;

        $dbConnection = connectToDb();
        $files = getFilesToDelete($dbConnection, 2);

        if (count($files) == 0) {
            errorMessage("No files to delete.");
            return;
        }

        $ftp = getFtpConnection(DEST_HOST, DEST_PORT, DEST_USER, DEST_PASS, DEST_PATH);
        if ($ftp === false) {
            errorMessage("Unable to connect to the DESTINATION server.");
            return;
        }

        $text = ngettext("file", "files", count($files));
        $errors = 0;

        echo "\nDeleting " . count($files) . " $text...\n";
        echo "Started at: " . date("Y-m-d H:i:s") . "\n";

        for ($i = 0; $i < count($files); $i++) {
            progressBar($i, count($files));
            $fileName = $files[$i]["fileName"];
            changeFileState($dbConnection, $fileName, 3);

            // delete file from destination
            if (!ftp_delete($ftp, $fileName)) {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                errorMessage("ERROR: Unable to delete the file from the DESTINATION server (File: $fileName).");
                continue;
            }

            $emailStats["deleted"] += 1;
            changeFileState($dbConnection, $fileName, 4);
        }

        progressBar(count($files), count($files));
        ftp_close($ftp);
        echo "\nFinished at: " . date("Y-m-d H:i:s") . "\n";

        if ($errors == 0) {
            successMessage("Deletion successful.");
        } else {
            $emailStats["isok"] = false;
            $emailStats["errors"][] = "Deletion successful, but " . $errors . " files failed.";
            errorMessage("Successfully deleted " . (count($files) - $errors) . "/" . count($files) . " " . $text . ", " .  $errors . " failed.");
        }


    }
?>