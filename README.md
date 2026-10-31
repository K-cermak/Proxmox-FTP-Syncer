# Proxmox FTP Syncer

By Karel Cermak | [Karlosoft](https://karlosoft.com)

<img src="https://cdn.karlosoft.com/cdn-data/ks/img/proxsync/github.png" width="700" alt="Proxmox FTP Syncer">

<br>

## What is Proxmox FTP Syncer?
- Proxmox is good at backing up your VMs, but it cannot copy those backups to a remote FTP server. This tool fills that gap: <b>it syncs your backups to a remote server over FTP</b>.
- With this CLI tool, you can easily and <b>automatically sync your Proxmox backups to a remote FTP server</b>, such as your NAS.

<br>

## How does it work?
- You create a new folder on your Proxmox server (bare metal) and set it as the backup location for your virtual machines. In Proxmox, you configure the backup job to delete old backups regularly, for example to keep only the last 5. Meanwhile, this tool copies the backups to a remote server over FTP.

<br>

## Features
- Copies backups from Proxmox to a remote server over FTP.
- Automatically removes old backups from the remote server.
- Sends email or Discord notifications about successful and failed runs.
- Automatically postpones the removal of old backups when no new backups appear, which may indicate a problem.
- Simple command-line control and easy integration with cron.

<br>

## Getting started
- Installation instructions: [`INSTALL.md`](INSTALL.md).
- Configuration and usage: [`USAGE.md`](USAGE.md) (or run `php syncer.php help`).

<br>

## When not to use this tool
- You use NFS or similar network storage.
- You use Proxmox Backup Server.
- You back up very often (e.g. hourly). This tool is designed for daily or weekly backups.
- Your backups are very large (several hundred GB). The tool transfers files one at a time without any parallelism, so large backups can take a long time.
- The tool cannot copy data directly between Proxmox and the remote server, so every file is temporarily stored on its own disk. It is therefore best to run it in a VM on an HDD with plenty of free space (the more, the better; I use a 120 GB disk). Running it on an SSD can significantly shorten the SSD's lifespan.

<br>

## Can I use this tool to transfer other backups?
- Yes. The tool is not limited to Proxmox backups. You can use it to transfer any files from one FTP server to another (e.g. WordPress backups).
- Whatever creates the backups must also delete old backups from the original storage, because this tool cannot do that. It must also never create two files with the same name (the file name should contain a timestamp or a random ID).

<br>
<br>

---

#### This project is not affiliated with Proxmox Server Solutions GmbH. It is an independent tool for syncing Proxmox VE backups to an FTP server, which Proxmox VE does not support out of the box. Developed by Karel Cermak (info@karlosoft.com).
