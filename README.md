# Proxmox FTP Syncer

By Karel Cermak | [Karlosoft](https://karlosoft.com).

<img src="https://cdn.karlosoft.com/cdn-data/ks/img/proxsync/github.png" width="700" alt="Proxmox FTP Syncer">

<br>

## What is Proxmox FTP Syncer?
- Proxmox is pretty good at backing up your VMs, but it's not so good at syncing those backups to a remote location without using Proxmox Backup Server. This script is a simple solution to that problem. <b>It uses FTP to sync the backups to a remote server</b>.
- With this CLI tool, you can easily and <b>automatically sync your Proxmox backups to a remote server using FTP</b> (to your NAS, AWS S3, etc.).

<br>

## How it works?
- You will create a new folder on your Proxmox server (bare metal) and set this folder as the backup location for your virtual servers. You will set it up so that old backups are regularly deleted, for example after 5 backups. In the meantime, this tool will synchronize these backups to a remote server using FTP.

<br>

## What can it do?
- Move backups from Proxmox to a remote server using FTP.
- Automatically remove old backups from FTP.
- Email notifications for successful and unsuccessful backups.
- Automatically delay the removal of old backups when new backups are unavailable (which may indicate a problem).
- Simple command line control and easy connection to CRON.

<br>

## How to start?
- You can find the installation in this file: [`INSTALL.md`](INSTALL.md).
- You can find the help in this file: [`USAGE.md`](USAGE.md). (or use the command `php syncer.php help`).

<br>

## When is it not good to use this tool?
- You are using Proxmox Backup Server.
- You make backups very often (hourly). This tool is more for backups made at daily or weekly intervals.
- Your backups are really large (several hundred GB). The tool is currently not written for multi-core processing, so if you have large backups, it may take a long time.
- This tool cannot copy data directly between Proxmox and FTP and must store data on its disk. Therefore, it is more suitable to use this tool in a VE that runs on HDD and has enough space (the more the better, for example I use 120 GB drive). Using on an SSD disk can significantly shorten its lifespan.

<br>

## Can I also use this utility to transfer backups of something else?
- Yes, you can. The tool is not limited to Proxmox backups only. You can use it to transfer any files from one server to another using FTP (WordPress backups, etc.).
- Ot is important that what creates the backups is able to delete the backups in the original storage (this utility cannot do that). It should also never create two files with the same name (i.e. the file name should have a timestamp or random ID in it).

<br>
<br>

---

#### This project is not affiliated with Proxmox Server Solutions GmbH. This is just a simple extension for backing up Proxmox VE to FTP, which is not possible by default. Developed by Karel Cermak (info@karlosoft.com).