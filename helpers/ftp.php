<?php
    function getFtpConnection($host, $port, $user, $pass, $path) {
        if (!is_numeric($port)) {
            errorMessage("ERROR: Port must be a number.");
            return false;
        }

        $ftp = @ftp_connect($host, $port, 6000);
        if ($ftp === false) {
            errorMessage("ERROR: Unable to connect to the FTP server.");
            return false;
        }

        $login = @ftp_login($ftp, $user, $pass);
        if ($login === false) {
            errorMessage("ERROR: Unable to log in to the FTP server.");
            return false;
        }

        ftp_pasv($ftp, true);

        $chdir = @ftp_chdir($ftp, $path);
        if ($chdir === false) {
            errorMessage("ERROR: Unable to open the configured directory on the FTP server.");
            return false;
        }
        return $ftp;
    }

    // Unreachable ORIGIN or no new backups may mean Proxmox stopped backing up, so old copies must not expire meanwhile.
    function extendBackupOnError($type) {
        if ($type == "classic" && EXTEND_BACKUP_ON_ERROR != 0) {
            editDeletionDate(connectToDb(), EXTEND_BACKUP_ON_ERROR);
            successMessage("    Deletion of all files has been postponed by " . EXTEND_BACKUP_ON_ERROR . " " . ngettext("day", "days", EXTEND_BACKUP_ON_ERROR) . ".");
        }
    }

    function getNewFiles($type) {
        global $syncStats;

        $ftp = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
        if ($ftp === false) {
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Unable to connect to the ORIGIN server.";
            extendBackupOnError($type);
            return;
        }

        if ($type == "list") {
            boldMessage("All files:");
        } else {
            boldMessage("New files:");
        }

        $newFiles = ftp_nlist($ftp, ".");
        if ($newFiles === false) {
            ftp_close($ftp);
            errorMessage("ERROR: Unable to list files on the ORIGIN server.");
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Unable to list files on the ORIGIN server.";
            extendBackupOnError($type);
            return;
        }

        $dbConnection = connectToDb();
        $listedAnythingNew = false;

        for ($i = 0; $i < count($newFiles); $i++) {
            // SIZE fails for directories, so this skips them
            if (ftp_size($ftp, $newFiles[$i]) == -1) {
                continue;
            }

            if ($type != "list" && getFileData($dbConnection, $newFiles[$i]) !== false) {
                continue;
            }

            echo "    " . $newFiles[$i];
            $listedAnythingNew = true;

            if ($type == "light" || $type == "classic") {
                if (KEEP_FILES_FOR == 0) {
                    $toDeleteOn = KEEP_FOREVER_DATE;
                } else {
                    $toDeleteOn = date("Y-m-d H:i:s", strtotime("+" . KEEP_FILES_FOR . " days"));
                }
                $syncStats["detected"] += 1;
                addFile($dbConnection, $newFiles[$i], $toDeleteOn);
                successMessage(" - added to the database.", false);
            }

            if ($type == "list") {
                $fileData = getFileData($dbConnection, $newFiles[$i]);
                if ($fileData === false) {
                    echo " | Not in the database";
                } else {
                    echo " | State: " . getState($fileData["state"]) . ", Deletion date: " . $fileData["toDelete"];
                }
            }

            echo "\n";
        }
        ftp_close($ftp);

        if (!$listedAnythingNew) {
            errorMessage("    WARNING: No new files found.");
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "No new files found.";
            extendBackupOnError($type);
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
            // a file that still exists on the ORIGIN server is not lost
            if (ftp_size($ftp, $allFiles[$i]["fileName"]) != -1) {
                continue;
            }

            echo "    " . $allFiles[$i]["fileName"];
            $listedAnything = true;

            if ($type == "light" || $type == "classic") {
                $syncStats["lost"] += 1;
                $syncStats["isok"] = false;
                changeFileState($dbConnection, $allFiles[$i]["fileName"], 5);
                successMessage(" - marked as lost in the database.", false);
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
                $syncStats["errors"][] = "Unable to connect to the ORIGIN server.";
                return;
            }

            $originSize = ftp_size($origin, $fileName);
            $temp = tempnam(sys_get_temp_dir(), "syncer");
            $downloaded = $temp !== false && ftp_get($origin, $temp, $fileName, FTP_BINARY);
            ftp_close($origin);

            if (!$downloaded || filesize($temp) !== $originSize) {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                if ($temp !== false) {
                    unlink($temp);
                }
                echo "\n";
                errorMessage("ERROR: The download from the ORIGIN server failed or is incomplete (file: $fileName).");
                continue;
            }

            $destination = getFtpConnection(DEST_HOST, DEST_PORT, DEST_USER, DEST_PASS, DEST_PATH);    
            if ($destination === false) {
                $syncStats["isok"] = false;
                $syncStats["errors"][] = "Unable to connect to the DESTINATION server.";
                unlink($temp);
                return;
            }

            $uploaded = ftp_put($destination, $fileName, $temp, FTP_BINARY);
            // -1 means the DESTINATION server doesn't support SIZE, so the upload can't be verified further.
            $destinationSize = $uploaded ? ftp_size($destination, $fileName) : -1;

            if ($uploaded && ($destinationSize == -1 || $destinationSize === $originSize)) {
                $syncStats["uploaded"] += 1;
                changeFileState($dbConnection, $fileName, 2);

            } else {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                echo "\n";
                errorMessage("ERROR: The upload to the DESTINATION server failed or is incomplete (file: $fileName).");
            }

            ftp_close($destination);
            unlink($temp);
        }

        progressBar(count($files), count($files));
        echo "\nFinished at: " . date("Y-m-d H:i:s") . "\n";
        
        if ($errors == 0) {
            successMessage("Sync completed successfully.");
        } else {
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Sync completed, but " . $errors . " " . ngettext("file", "files", $errors) . " failed.";
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
            errorMessage("ERROR: Unable to connect to the DESTINATION server.");
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

            if (!ftp_delete($ftp, $fileName)) {
                $errors += 1;
                changeFileState($dbConnection, $fileName, 5);
                errorMessage("ERROR: Unable to delete the file from the DESTINATION server (file: $fileName).");
                continue;
            }

            $syncStats["deleted"] += 1;
            changeFileState($dbConnection, $fileName, 4);
        }

        progressBar(count($files), count($files));
        ftp_close($ftp);
        echo "\nFinished at: " . date("Y-m-d H:i:s") . "\n";

        if ($errors == 0) {
            successMessage("Deletion completed successfully.");
        } else {
            $syncStats["isok"] = false;
            $syncStats["errors"][] = "Deletion completed, but " . $errors . " " . ngettext("file", "files", $errors) . " failed.";
            errorMessage("Successfully deleted " . (count($files) - $errors) . "/" . count($files) . " " . $text . ", " .  $errors . " failed.");
        }
    }
?>