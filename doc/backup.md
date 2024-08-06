You can create backup of your project in the `backups` folder.

# Backup with rclone
- Install `rclone` and `gzip` on your system
- Create a rclone config file at `~/.config/rclone/rclone.conf`
- Copy the `backup.sh.example` script into `backups/backup.sh`
- Make the script executable with `chmod +x backups/backup.sh`
- Edit the script to match your configuration
- Setup a cronjob to run it periodically 

## Example of rclone.conf for exoscale

```
[biblioteca-backup]
type = s3
provider = Other
env_auth = false
access_key_id = ...
secret_access_key = ...
region = ch-ge-2
endpoint = sos-ch-ge-2.exo.io
acl = private
```