
--- 

# WARNING ⚠️⚠️
- In this guide, you are working with the Proxmox installation, in case of an error, data loss can occur. The author of this guide bears no responsibility for data loss and other problems caused.
- Be aware that by installing FTP servers you can cause system vulnerability if you do not follow security rules. Set very strong passwords and make sure that FTP is not accessible from the internet.

--- 

<br>

## Creating a backup drive
- Go to Proxmox VE.
- In the `Datacenter`, select `Storage` and choose `Add` -> `Directory`.
- Set `ID` to `backup` and `Directory` to the path where backups will be stored. For example `/media/drive/`.
- In `Content`, select `Disk Image` and `VZDump Backup File` and click `OK`.


<br>

## Creating a backup
- Go to the `Backup` tab, click on `Add`.
- For `Storage`, select the name of your disk (e.g., `backup`) and choose the backup time and machines to be backed up.
- In `Retention` tab, set `Keep Last` to the desired number of backups that will be kept on Proxmox.

<br>

## FTP Installation
- Connect to Proxmox (bare metal) via SSH or through the service in Proxmox.
- Update the package list: `apt-get update`
- Install vsftpd: `apt-get install vsftpd`
- Go to the configuration file: `nano /etc/vsftpd.conf`. Instead of nano, you can use another text editor.
- Set the configuration according to the following example. If the line does not exist, add it. If it exists, uncomment it and modify it if needed.

```
anonymous_enable=NO
local_enable=YES
write_enable=NO
chroot_local_user=YES
allow_writeable_chroot=YES
local_root=/media/drive/dump    #!adjust the path according to your settings and add /dump at the end
user_sub_token=$USER
```

- Create a new user who will have access to backups: `useradd -d /media/drive/dump ftpuser`. Instead of `ftpuser`, you can use a different name.
- Set a password for the new user: `passwd ftpuser`. Use a strong password.
- Restart vsftpd: `service vsftpd restart`.
- Test the connection to the FTP server using an FTP client (e.g., Total Commander) and the new user credentials.

<br>

## PHP Installation
- Create a new virtual machine that will take care of backup synchronization.
- Update the package list: `sudo apt-get update`.
- Install PHP: `sudo apt-get install php-cli`.
- Install the library for sqlite: `sudo apt-get install php-sqlite3`.

- Create a folder and place the files of this repository in it.
- You can learn how to setup and use this tool in the file [`USAGE.md`](USAGE.md).

<br>

# Configuration of Cron

- Install cron if you don’t have it: `sudo apt-get install cron`.
- Enter the command: `crontab -e`.
- Set cron to run after backup (for example, if Proxmox backup takes place every Sunday at 3:00, set cron on Sunday at 6:00). Example of cron:
```
0 6 * * 0 php /var/www/proxmox-ftp-syncer/syncer.php autorun
```
- Save the file and exit the editor.