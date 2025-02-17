<?php
    function getFtpConnection($host, $port, $user, $pass, $path) {
        error_reporting(0);

        if (!is_numeric($port)) {
            errorMessage("ERROR: Port must be a number.");
            return false;
        }

        $ftp = ftp_connect($host, $port, 6000);
        if ($ftp === false) {
            errorMessage("ERROR: Unable to connect to the FTP server.");
            return false;
        }

        $login = ftp_login($ftp, $user, $pass);
        if ($login === false) {
            errorMessage("ERROR: Unable to login to the FTP server.");
            return false;
        }

        ftp_pasv($ftp, true);

        $chdir = ftp_chdir($ftp, $path);
        if ($chdir === false) {
            errorMessage("ERROR: Unable to change directory on the FTP server.");
            return false;
        }
        return $ftp;

        error_reporting(1);
    }

    function getNewFiles($type) {
        global $syncStats;

        $ftp = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
        if ($ftp === false) {
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Unable to connect to the ORIGIN server.";
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

            //adding to DB
            if ($type == "light" || $type == "classic") {
                if (KEEP_FILES_FOR == 0) {
                    $toDeleteOn = "9999-12-31 23:59:59";
                } else {
                    $toDeleteOn = date("Y-m-d H:i:s", strtotime("+" . KEEP_FILES_FOR . " days"));
                }
                $syncStats["detected"] += 1;
                addFile($dbConnection, $newFiles[$i], $toDeleteOn);
                successMessage(" - added to the database.", false);
            }

            if ($type == "list") {
                $fileData = getFileData($dbConnection, $newFiles[$i]);
                echo " | State: " . getState($fileData["state"]) . ", Deletion date: " . $fileData["toDelete"];
            }

            echo "\n";
        }
        ftp_close($ftp);

        if (!$listedAnythingNew) {
            errorMessage("    Warning: No new files found.");
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "No new files found.";
            if ($type == "classic" && EXTEND_BACKUP_ON_ERROR != 0) {
                editDeletionDate($dbConnection, EXTEND_BACKUP_ON_ERROR);
                successMessage("    Deletion date of all files has been updated.");
            }
        }
    }

    function getLostFiles($type) {
        global $syncStats;

        $ftp = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
        if ($ftp === false) {
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Unable to connect to the ORIGIN server.";
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

            if ($type == "light" || $type == "classic") {
                $syncStats["lost"] += 1;
                $syncStats["isok"] = false;
                changeFileState($dbConnection, $allFiles[$i]["fileName"], 5);
                successMessage(" - edited in the database.", false);
            }

            echo "\n";
        }
        ftp_close($ftp);

        if (!$listedAnything) {
            successMessage("    No lost files.");
        }
    }

    function sync() {
        global $syncStats;

        $dbConnection = connectToDb();
        $files = getFilesInState($dbConnection, 0);

        if (count($files) == 0) {
            errorMessage("\nNo files to sync.");
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

            $origin = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
            if ($origin === false) {
                $syncStats["isok"] = false;
                $syncStats["errors"][] = "Unable to connect to the ORIGIN or DESTINATION server.";
                return;
            }

            $temp = tempnam(sys_get_temp_dir(), "syncer");
            ftp_get($origin, $temp, $fileName, FTP_BINARY);
            ftp_close($origin);

            if (!file_exists($temp)) {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                echo "\n";
                errorMessage("ERROR: Unable to download the file from the ORIGIN server (File: $fileName).");
                continue;
            }

            $destination = getFtpConnection(DEST_HOST, DEST_PORT, DEST_USER, DEST_PASS, DEST_PATH);    
            if ($destination === false) {
                $syncStats["isok"] = false;
                $syncStats["errors"][] = "Unable to connect to the ORIGIN or DESTINATION server.";
                unlink($temp);
                return;
            }

            if (ftp_put($destination, $fileName, $temp, FTP_BINARY)) {
                $syncStats["uploaded"] += 1;
                changeFileState($dbConnection, $fileName, 2);

            } else {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                echo "\n";
                errorMessage("ERROR: Unable to upload the file to the DESTINATION server (File: $fileName).");
            }

            ftp_close($destination);
            unlink($temp);
        }

        progressBar(count($files), count($files));
        echo "\nFinished at: " . date("Y-m-d H:i:s") . "\n";
        
        if ($errors == 0) {
            successMessage("Syncing successful.");
        } else {
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Syncing successful, but " . $errors . " files failed.";
            errorMessage("Successfully synced " . (count($files) - $errors) . "/" . count($files) . " " . $text . ", " .  $errors . " failed.");
        }
    }

    function deleteOld() {
        global $syncStats;

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

            //delete file from destination
            if (!ftp_delete($ftp, $fileName)) {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                errorMessage("ERROR: Unable to delete the file from the DESTINATION server (File: $fileName).");
                continue;
            }

            $syncStats["deleted"] += 1;
            changeFileState($dbConnection, $fileName, 4);
        }

        progressBar(count($files), count($files));
        ftp_close($ftp);
        echo "\nFinished at: " . date("Y-m-d H:i:s") . "\n";

        if ($errors == 0) {
            successMessage("Deletion successful.");
        } else {
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Deletion successful, but " . $errors . " files failed.";
            errorMessage("Successfully deleted " . (count($files) - $errors) . "/" . count($files) . " " . $text . ", " .  $errors . " failed.");
        }
    }
?>