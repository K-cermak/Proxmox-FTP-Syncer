-- jak vytvořit v proxmoxu složku

-- tento návod se vztahuje pouze pokud nepoužíváte k připojení k Proxmoxu FTP

sudo apt-get install vsftpd

sudo nano /etc/vsftpd.conf

anonymous_enable=NO
local_enable=YES
write_enable=NO
chroot_local_user=YES
allow_writeable_chroot=YES
local_root=/data/backup/dump ; edit this line
user_sub_token=$USER



-- user
sudo useradd -d /media/hdd/backups ftpuser
sudo passwd ftpuser


-- finish
sudo service vsftpd restart