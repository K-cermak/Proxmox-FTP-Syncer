<?php
    function copyright() {
        echo "\033[36mProxmox Syncer v1.0\n";
        echo "Developed by: @k-cermak | karlosoft.com\n";
        echo "Licensed under the MIT License.\n\033[0m";
        echo "-----------\n";
    }

    function errorMessage($message, $newLine = true) {
        echo "\033[31m$message \033[0m";
        if ($newLine) {
            echo "\n";
        }
    }

    function boldMessage($message, $newLine = true) {
        echo "\033[1m$message\033[0m";
        if ($newLine) {
            echo "\n";
        }
    }
?>