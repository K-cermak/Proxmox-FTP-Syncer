<?php
    function checkConnection() {
        echo "Checking connection to the ORIGIN server... ";
        $origin = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);

        if ($origin !== false) {
            successMessage("Connection to the ORIGIN server successful.");
        }

        echo "Checking connection to the DESTINATION server... ";
        $destination = getFtpConnection(DEST_HOST, DEST_PORT, DEST_USER, DEST_PASS, DEST_PATH);

        if ($destination !== false) {
            successMessage("Connection to the DESTINATION server successful.");
        }

        if (ORIGIN_HOST == DEST_HOST && ORIGIN_PORT == DEST_PORT && ORIGIN_USER == DEST_USER && ORIGIN_PASS == DEST_PASS && ORIGIN_PATH == DEST_PATH) {
            errorMessage("WARNING: The ORIGIN and DESTINATION servers are the same.");
        }
    }

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
        $ftp = getFtpConnection(ORIGIN_HOST, ORIGIN_PORT, ORIGIN_USER, ORIGIN_PASS, ORIGIN_PATH);
        if ($ftp === false) {
            return;
        }

        if ($type == "list") {
            boldMessage("All files:");
        } else {
            boldMessage("New files:");
        }

        $newFiles = ftp_nlist($ftp, ".");
        $dbConnection = connectToDb(); 
        $listedAnythigNew = false;

        for ($i = 0; $i < count($newFiles); $i++) {
            //skip folders
            if (ftp_size($ftp, $newFiles[$i]) == -1) {
                continue;
            }

            //skip files that are already in the database
            if ($type != "list" && getFileData($newFiles[$i], $dbConnection) !== false) {
                continue;
            }

            echo "    " . $newFiles[$i];
            $listedAnythigNew = true;

            //ading to DB
            if ($type == "light" || $type == "classic") {
                $toDeleteOn = date("Y-m-d H:i:s", strtotime("+" . KEEP_FILES_FOR . " days"));
                addFile($newFiles[$i], $toDeleteOn, $dbConnection);
                successMessage(" - added to the database.", false);
            }

            echo "\n";
        }
        ftp_close($ftp);

        //when nothing found
        if (!$listedAnythigNew) {
            errorMessage("    Warning: No new files found.");
            if ($type == "classic") {
                editDeletionDate($dbConnection);
                successMessage("    Deletion date of all files has been updated.");
            }
        }
        
    }

?>