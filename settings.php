<?php
    define("CREDENTIALS_FILE", "credentials.php");
    define("DB_FILE", "/crons/db.sqlite"); // must be an absolute path

    define("KEEP_FILES_FOR", 90); // days to keep synced files on the DESTINATION server, 0 keeps them forever
    define("EXTEND_BACKUP_ON_ERROR", 8); // days to postpone deletion by when no new files are found, 0 disables it

    define("SEND_EMAIL", "always"); // no, on_error or always

    define("SEND_DISCORD_HOOKS", "always"); // no, on_error or always (https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks)
    define("PING_USER", "on_error"); // no, on_error or always (https://support.discord.com/hc/en-us/articles/206346498-Where-can-I-find-my-User-Server-Message-ID)
?>