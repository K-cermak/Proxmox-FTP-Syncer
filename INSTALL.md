
---

# WARNING ⚠️⚠️
- This guide involves changes to your Proxmox installation. A mistake can lead to data loss. The author of this guide accepts no responsibility for data loss or any other problems caused.
- Installing an FTP server can make your system vulnerable if you do not follow security best practices. Use a strong password and make sure the FTP server is not accessible from the internet.

---

<br>

## Creating a backup storage
- Open the Proxmox VE web interface.
- In `Datacenter`, select `Storage` and choose `Add` -> `Directory`.
- Set `ID` to `backup` and `Directory` to the path where backups will be stored, for example `/media/drive/`.
- In `Content`, select `Disk image` and `VZDump backup file` and click `Add`.

<br>

## Creating a backup job
- In `Datacenter`, go to the `Backup` tab and click `Add`.
- For `Storage`, select your storage (e.g. `backup`), then choose the schedule and the machines to back up.
- In the `Retention` tab, set `Keep Last` to the number of backups that should be kept on Proxmox.

<br>

## FTP installation
- Connect to Proxmox (bare metal) via SSH or via the `Shell` in the Proxmox web interface.
- Update the package list: `apt-get update`.
- Install vsftpd: `apt-get install vsftpd`.
- Open the configuration file: `nano /etc/vsftpd.conf` (you can use any other text editor instead of nano).
- Set the configuration according to the following example. If a line does not exist, add it. If it exists, uncomment it and adjust it if needed.

```
anonymous_enable=NO
local_enable=YES
write_enable=NO
chroot_local_user=YES
allow_writeable_chroot=YES
# Adjust the path to your storage and add /dump at the end
local_root=/media/drive/dump
user_sub_token=$USER
```

> vsftpd does not support comments at the end of a line, so keep every comment on its own line.

- Create a new user with access to the backups: `useradd -d /media/drive/dump ftpuser`. You can use a different name instead of `ftpuser`.
- Set a password for the new user: `passwd ftpuser`. Use a strong password.
- Restart vsftpd: `service vsftpd restart`.
- Test the connection with an FTP client (e.g. Total Commander) using the new user's credentials.

<br>

## PHP installation
- Create a new virtual machine that will handle the backup synchronization.
- Update the package list: `sudo apt-get update`.
- Install PHP: `sudo apt-get install php-cli`.
- Install the SQLite extension: `sudo apt-get install php-sqlite3`.
- Install the cURL extension (required for Discord notifications): `sudo apt-get install php-curl`.
- Create a folder and copy the files from this repository into it.
- To learn how to configure and use the tool, see [`USAGE.md`](USAGE.md).

<br>

## Cron configuration
- Install cron if you don't have it yet: `sudo apt-get install cron`.
- Run `crontab -e`.
- Schedule the tool to run after the Proxmox backup has finished. For example, if the backup runs every Sunday at 3:00, schedule the tool for Sunday at 6:00:
```
0 6 * * 0 php /var/www/proxmox-ftp-syncer/syncer.php autorun
```
- Save the file and close the editor.
