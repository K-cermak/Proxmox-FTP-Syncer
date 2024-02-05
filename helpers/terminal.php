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

    function successMessage($message, $newLine = true) {
        echo "\033[32m$message\033[0m";
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

    function progressBar($done, $total) {
        $perc = floor(($done / $total) * 100);
        $left = 100 - $perc;
        $write = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total", "", "");
        fwrite(STDERR, $write);
    }
?>