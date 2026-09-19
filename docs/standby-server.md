# Standby server

The site is stateless apart from MySQL. A standby is therefore a second Coolify host (the VPS at `187.77.176.96`) running the same repository, with the database refreshed from the primary every hour. Switching over means changing one DNS record.

## 1. The standby host

Ubuntu VPS at `187.77.176.96` (2 vCPU, 8 GB, 96 GB disk). Coolify 4.3.23 is installed; the dashboard is `http://187.77.176.96:8000`. Port 8000 is open to the internet, so register the admin account immediately and then restrict that port in the provider's firewall to your own IP.

Root SSH still accepts passwords. Switch it to keys once your own key is in `/root/.ssh/authorized_keys`: set `PasswordAuthentication no` in `/etc/ssh/sshd_config` and restart `ssh`.

## 2. Coolify

Already installed with:

```sh
curl -fsSL https://cdn.coollabs.io/coolify/install.sh | bash
```

Coolify's own environment file lives at `/data/coolify/source/.env`; copy it into a password manager.

## 3. Deploy the application

Coolify > Projects > New > New resource > Docker Compose, from the GitHub repository `maxwellvn/kingdom-producers-summit`, branch `main`. Set the same environment variables as the primary (see README.md). Two of them differ on the standby:

- `APP_URL`: keep the production URL. After failover the standby is the production site.
- `BACKUP_SSH_HOST`: leave unset. The sync script exits at once when it is missing, so the standby never pushes into itself.

Under Domains, enter the production hostname for the `app` service. Coolify requests a Let's Encrypt certificate only once DNS points at it, which is after failover; until then the site answers on the raw IP over HTTP, which is enough to check that it deploys.

Deploy. The container runs migrations and creates an empty schema; the first sync overwrites it.

## 4. Allow the primary to push dumps

Already done on the current standby: user `standby`, `/usr/local/bin/standby-restore`, and the forced-command key are in place. The steps below are for rebuilding it on a new host.

On the standby, as root:

```sh
useradd -m -s /bin/sh -G docker standby   # not "backup": that name is a built-in Debian system user
install -m 755 /var/www/standby-restore.sh /usr/local/bin/standby-restore   # copy docker/standby-restore.sh from the repo
touch /var/log/standby-restore.log && chown standby /var/log/standby-restore.log
```

Generate a key pair on your laptop:

```sh
ssh-keygen -t ed25519 -f producers-standby -N '' -C producers-standby
```

Add the public key to `/home/standby/.ssh/authorized_keys` on the standby, prefixed with a forced command so the key can do nothing but run the restore:

```
command="/usr/local/bin/standby-restore",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ssh-ed25519 AAAA... producers-standby
```

Permissions: `chmod 700 /home/standby/.ssh && chmod 600 /home/standby/.ssh/authorized_keys && chown -R standby:standby /home/standby/.ssh`.

## 5. Schedule the sync on the primary

In Coolify on the primary, open the application and add two environment variables:

- `BACKUP_SSH_HOST` = `187.77.176.96`
- `BACKUP_SSH_KEY_B64` = `base64 -w0 producers-standby` (the private key, one line)

Redeploy so the image picks up the MySQL and SSH clients added to the Dockerfile. Then under Scheduled Tasks add:

- Name: `standby-sync`
- Container: `app`
- Command: `sh /var/www/html/bin/backup-sync.sh`
- Frequency: `0 * * * *`

The task page's "Run now" button is the manual sync. Run it once immediately and check the standby: `tail /var/log/standby-restore.log` should show a `restore ok` line, and the standby's admin login should list the primary's registrations.

## 6. Failover

1. Confirm the primary is actually down and not merely slow; a sync that ran during the outage would carry nothing new anyway.
2. In Coolify on the primary, if it is still reachable, delete the `standby-sync` scheduled task so a recovering primary cannot overwrite the now-live standby.
3. At the DNS provider, change the A record for the production hostname to the standby's static IP. Coolify's proxy issues the HTTPS certificate within a minute or two of DNS propagating.
4. Log in and confirm scanning works; the camera needs HTTPS.

Registrations made in the last hour before the outage exist only on the primary. Recover them from its disk later if the volume survives.

## 7. Returning to the primary

Reverse the direction: set `BACKUP_SSH_HOST` and the key on the standby pointing at the primary, run the task once, then move DNS back and delete that task again. The standby keeps `BACKUP_SSH_HOST` unset during normal operation.
