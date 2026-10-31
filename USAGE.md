## Basic settings
- Open the `settings.php` file and set the following constants:

    - `CREDENTIALS_FILE` - Path to the file with your credentials.
    - `DB_FILE` - Absolute path to the database file (with the `.sqlite` extension). Do not create the file yourself; the `create-db` command will create it.
    - `KEEP_FILES_FOR` - Number of days for which backups are kept on the DESTINATION server. If set to 0, backups are never deleted.
    - `EXTEND_BACKUP_ON_ERROR` - Number of days by which the deletion of backups on the DESTINATION server is postponed when no new backup is found (or the ORIGIN server is unreachable). If set to 0, deletion is never postponed.
    - `SEND_EMAIL` - `always` sends a report email after every run, `on_error` only when an error occurs, and `no` disables emails.
    - `SEND_DISCORD_HOOKS` - `always` sends a Discord notification after every run, `on_error` only when an error occurs, and `no` disables Discord notifications.
    - `PING_USER` - `always` mentions the user in every Discord notification, `on_error` only when an error occurs, and `no` never mentions the user.

<br>

## FTP settings
- Open your credentials file (the one set in `CREDENTIALS_FILE` above).
- Enter the login details for both FTP servers. The ORIGIN server is where the backups are downloaded from (Proxmox). The DESTINATION server is where the backups are stored (e.g. a NAS).

<br>

## Email settings
- Open your credentials file (the one set in `CREDENTIALS_FILE` above).
- Enter the SMTP details of your email server. Your email provider should give you these.
- In `SEND_TO`, enter the email addresses that should receive the reports. To use multiple addresses, separate them with commas and no spaces.

<br>

## Discord notifications (webhooks)
- Open your credentials file (the one set in `CREDENTIALS_FILE` above).
- Copy your Discord webhook URL and paste it into `DISCORD_WEBHOOK`.
- If you want to be mentioned in notifications (see `PING_USER`), enter your Discord user ID in `DISCORD_PING_ID`.

<br>

## Database initialization
- Run `php syncer.php create-db`.

<br>

## Verifying the settings
- Run `php syncer.php check-connection` to check the connection to both FTP servers.
- Run `php syncer.php check-settings` to validate the values in `settings.php`.
- Run `php syncer.php check-email` to send a test email.
- Run `php syncer.php check-discord` to send a test Discord notification (add `ping` to also mention the user).

<br>

## Running the synchronization
- Run `php syncer.php autorun` to start the synchronization manually.
- You can also set up a cron job that runs `php syncer.php autorun` at a specific time (see [`INSTALL.md`](INSTALL.md)).

<br>

## Help
- Run `php syncer.php help` to list all available commands.
