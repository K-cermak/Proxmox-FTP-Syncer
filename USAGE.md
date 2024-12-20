## Basic settings
- Go to the `settings.php file` and set the following variables:

    - `CREDENTIALS_FILE` - Path to the file with login credentials.
    - `DB_FILE` - Path to the database file. Do not create a database, just enter the absolute path to the file with the .sqlite extension.
    - `KEEP_FILES_FOR` - Enter the number of days for which backups will be kept on the FTP server. When set to 0, backups will not be deleted.
    - `EXTEND_BACKUP_ON_ERROR` - Enter the number of days by which the retention of backups on the FTP server will be extended if a new backup created by Proxmox is not found. When set to 0, backups will not be extended.
    - `SEND_EMAIL` - The value `always` will send an informational email after each backup. The value `on_error` will send an email only in case of an error. The value `no` will not send an email at all.


<br>

## FTP Settings
- Go to the file with your keys (the same as the set variable `CREDENTIALS_FILE` above).
- Enter the login details for the FTP servers. The ORIGIN server is the server from which backups are downloaded (Proxmox), the DESTINATION server is the server (for example, NAS, AWS S3) where backups are stored.

<br>

## Email Settings
- Go to the file with your keys (the same as the set variable `CREDENTIALS_FILE` above).
- Enter the SMTP details for the email server. Your email provider should provide these.
- In `SEND_TO`, enter the email addresses to which informational emails will be sent. If you want to send an email to multiple addresses, separate them with a comma without a space.

<br>

## Discord Notifications (Webhooks)
- Go to the file with your keys (the same as the set variable `CREDENTIALS_FILE` above).
- Copy the Discord webhook URL and paste it into the `DISCORD_WEBHOOK` variable.
- If you set that you want to be pinged, enter your Discord ID in the `DISCORD_PING_ID` variable.

<br>

## Database Initialization
- Run the command `php syncer.php create-db`.

<br>

## Verification of Settings
- Run the command `php syncer.php check-connection` to check the connection to the FTP servers.
- Run the command `php syncer.php check-settings`  to check the set variables.
- Run the command `php syncer.php check-email` to send a test email.

<br>

## Run the Synchronization
- You can manually start the backup with the command `php syncer.php autorun`.
- You can also set up a CRON job to run the command `php syncer.php autorun` at a specific time (more in the file [`INSTALL.md`](INSTALL.md)).

<br>

## Help
- You can display all available commands using the command `php syncer.php help`.