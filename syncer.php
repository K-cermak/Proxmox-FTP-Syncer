<?php
    /*
    **     ___                                                  ___
    **    (  _`\                                               (  _`\
    **    | |_) ) _ __   _           ___ ___     _            | (_(_) _   _   ___     ___    __   _ __
    **    | ,__/'( '__)/'_`\ (`\/')/' _ ` _ `\ /'_`\ (`\/')   `\__ \ ( ) ( )/' _ `\ /'___) /'__`\( '__)
    **    | |    | |  ( (_) ) >  < | ( ) ( ) |( (_) ) >  <    ( )_) || (_) || ( ) |( (___ (  ___/| |
    **    (_)    (_)  `\___/'(_/\_)(_) (_) (_)`\___/'(_/\_)   `\____)`\__, |(_) (_)`\____)`\____)(_)
    **                                                              ( )_| |
    **                                                             `\___/'              Version 1.2
    **
    **    Proxmox Syncer v1.2
    **    Developed by: Karel Cermak | karlosoft.com
    **    Licensed under the MIT License.
    */

    set_time_limit(0); //disable time limit, change if you want

    $syncStats = array(
        "started" => date("Y-m-d H:i:s"),
        "detected" => 0,
        "lost" => 0,
        "uploaded" => 0,
        "deleted" => 0,
        "isok" => true,
        "errors" => []
    );

    require "settings.php";
    require CREDENTIALS_FILE;
    require "helpers/help.php";
    require "helpers/terminal.php";
    require "helpers/db.php";
    require "helpers/ftp.php";
    require 'email/Exception.php';
    require 'email/PHPMailer.php';
    require 'email/SMTP.php';
    require 'helpers/email.php';
    require "helpers/discord.php";

    copyright();
    checkInstalledSqlite();
    checkInstalledFtp();

    $arg = $argv[1] ?? '?';
    if ($arg == "?") {
        errorMessage("ERROR: Not command line arguments found.");
        getHelp();
        return;
    }

    switch ($arg) {
        case "create-db":
            buildDatabase();
            break;

            case "check-connection":
            checkConnection();
            break;

            case "check-settings":
            checkSettings();
            break;

            case "check-email":
            testEmail();
            break;

        case "check-discord":
            $ping = $argv[2] ?? false;
            if ($ping == "ping") {
                $ping = true;
            }

            testWebhook($ping);
            break;

        case "discovery":
            $type = $argv[2] ?? "safe";
            getNewFiles($type);
            getLostFiles($type);
            break;

        case "sync":
            sync();
            break;

        case "delete":
            deleteOld();
            break;

        case "autorun":
            $start = microtime(true);
            getNewFiles("classic");
            getLostFiles("classic");
            sync();
            deleteOld();

            $end = microtime(true);
            $syncStats["time"] = $end - $start;
            sendReport();
            sendWebhook();
            break;

        case "extend-backup":
            $days = $argv[2] ?? 0;
            extendBackup($days);
            break;

        case "fix":
            $fixing = $argv[2] ?? "-";
            fixErrors($fixing);
            break;

        case "help":
            getHelp();
            break;

        default:
            errorMessage("ERROR: Invalid command line argument.");
            getHelp();
            break;
    }
?>