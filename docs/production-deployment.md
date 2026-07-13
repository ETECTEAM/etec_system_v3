# Production Deployment — Contabo VPS

Companion to [vps-deployment-specs.md](./vps-deployment-specs.md) (which
covers *sizing/choosing* the VPS). This doc covers everything from a fresh
`root@` login to a running production stack.

Stack: Docker Compose (app = PHP-FPM, nginx = static/PHP proxy inside
Docker, host Nginx = TLS termination + reverse proxy, MySQL 8, a dedicated
queue-worker container, a scheduler container). See
[docker-compose.prod.yml](../docker-compose.prod.yml).

Reference specs (from vps-deployment-specs.md): Contabo Cloud VPS 10 — 4
vCPU / 8 GB RAM / 75 GB NVMe, Ubuntu, Singapore region, Auto Backup add-on.

---

## 0. Before you start

- **Push the `production` branch to GitHub first.** At the time this doc was
  written, local `production` was 125 commits ahead of `origin/production` —
  the deploy script pulls from `origin`, so nothing below works until you
  `git push origin production`.
- **Rotate the Telegram bot token.** `.env.example` has a real bot token
  committed in git history (pushed to `origin/production` and other
  branches). Generate a new one with @BotFather before going live and only
  ever put the new value in the server's `.env` (never commit it).
- Have ready: your domain name (or skip SSL steps and use the bare IP
  temporarily), and Contabo's initial root password (emailed after VPS
  creation).

---

## 1. Initial server hardening

SSH in as root using the password Contabo emailed you:

```bash
ssh root@YOUR_SERVER_IP
```

### 1.1 Create a sudo user, don't stay on root

```bash
adduser deploy
usermod -aG sudo deploy
```

### 1.2 Set up SSH keys (from your local machine)

```bash
ssh-copy-id deploy@YOUR_SERVER_IP
# or manually: paste your ~/.ssh/id_ed25519.pub into
# /home/deploy/.ssh/authorized_keys on the server
```

Confirm you can log in as `deploy` with the key **before** touching root
login — don't lock yourself out.

```bash
ssh deploy@YOUR_SERVER_IP
```

### 1.3 Disable root SSH login and password auth

Edit `/etc/ssh/sshd_config`:

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

```bash
sudo systemctl restart sshd
```

### 1.4 UFW firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose
```

Do **not** open 8001, 3307, 8081, or 9000 — those stay bound to
`127.0.0.1` only (already enforced in `docker-compose.prod.yml`).

### 1.5 fail2ban

```bash
sudo apt update
sudo apt install -y fail2ban
sudo systemctl enable --now fail2ban
```

Default jail config (`sshd` jail) is enough for a single-app box; no custom
jail needed unless you see abuse in `sudo fail2ban-client status sshd`.

### 1.6 Basic OS hygiene

```bash
sudo apt update && sudo apt full-upgrade -y
sudo timedatectl set-timezone Asia/Phnom_Penh   # or whatever matches your users
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 2. Install Docker Engine + Compose plugin

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker deploy
newgrp docker   # or log out/in for the group change to take effect

docker --version
docker compose version
```

---

## 3. Clone the repo

```bash
sudo mkdir -p /opt/etec-system
sudo chown deploy:deploy /opt/etec-system
git clone -b production https://github.com/knr-smey/etec_system_v3.git /opt/etec-system
cd /opt/etec-system
```

(Use an SSH deploy key or a GitHub PAT here if the repo stays private —
plain HTTPS clone will prompt for credentials interactively otherwise.)

---

## 4. Configure `.env`

```bash
cp .env.production.example .env
nano .env
```

Fill in every `CHANGE_ME_*` value:

- `APP_URL` — your real domain, `https://`
- `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD` — generate with `openssl rand -base64 32`
- `MAIL_*` — your SMTP provider
- `TELEGRAM_BOT_TOKEN` / `TELEGRAM_ADMIN_CHAT_ID` / `TELEGRAM_WEBHOOK_SECRET`
  — the **rotated** token from step 0, not the leaked one

Generate `APP_KEY` after the app container exists (step 6) — don't hand-roll
it.

`chmod 600 .env` once filled in.

---

## 5. Install Nginx (host) + Certbot for SSL

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
```

Point your domain's DNS `A` record at the VPS IP now, before requesting a
certificate (Let's Encrypt needs to reach the server over HTTP to validate).

Install the reverse-proxy config:

```bash
sudo cp deploy/nginx/host-reverse-proxy.conf /etc/nginx/sites-available/etec-system.conf
sudo sed -i "s/DOMAIN/your-real-domain.com/g" /etc/nginx/sites-available/etec-system.conf
sudo ln -s /etc/nginx/sites-available/etec-system.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
```

`nginx -t` will complain about the missing certificate — that's expected;
certbot creates it next:

```bash
sudo certbot --nginx -d your-real-domain.com -d www.your-real-domain.com
```

Certbot edits the site config in place and sets up its own renewal timer
(`systemctl status certbot.timer` — check it's active). No cron needed.

---

## 6. Bring up the app stack

```bash
cd /opt/etec-system
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d mysql
# wait for mysql healthcheck to pass
docker compose -f docker-compose.prod.yml ps

docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate
docker compose -f docker-compose.prod.yml run --rm app php artisan migrate --force

docker compose -f docker-compose.prod.yml up -d
```

This starts `app` (PHP-FPM), `nginx` (in-container, serves static +
proxies PHP to `app`, bound to `127.0.0.1:8001`), `queue`
(`php artisan queue:work`), `scheduler` (`php artisan schedule:run` loop),
and `mysql`. The host Nginx from step 5 fronts all of it on 443.

`phpmyadmin` is **not** started by default (it's behind the `tools`
[profile](https://docs.docker.com/compose/how-tos/profiles/)). See §9.

---

## 7. Process management — what's supervising what

| Process | Supervised by |
|---|---|
| PHP-FPM (`app`) | Docker (`restart: unless-stopped`) |
| Queue worker (`queue`) | Docker (`restart: unless-stopped`) — this replaces the dev-only `composer run dev` concurrent process |
| Scheduler (`scheduler`) | Docker (`restart: unless-stopped`), loops `schedule:run` every 60s |
| In-container Nginx | Docker (`restart: unless-stopped`) |
| MySQL | Docker (`restart: unless-stopped`) |
| Whole stack on VPS reboot | systemd unit, [deploy/systemd/etec-system.service](../deploy/systemd/etec-system.service) |
| Host Nginx (TLS) | systemd (`nginx.service`, enabled by the apt package) |

Install the boot-resilience unit:

```bash
sudo cp deploy/systemd/etec-system.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now etec-system.service
```

**Laravel Pail and the Vite dev server are dev-only and are not part of the
production stack at all.** Pail is a `require-dev` package excluded by
`composer install --no-dev`; the frontend is built once at image-build time
via `npm run build` (see the Dockerfile's `frontend` stage) and served as
static files, not through `vite dev`. Use `docker compose -f
docker-compose.prod.yml logs -f app` (or `storage/logs` inside the
`storage_data` volume) for production log access instead of Pail.

---

## 8. Deploying updates

```bash
cd /opt/etec-system
./deploy/deploy.sh
```

This does: `git fetch` + hard-reset to `origin/production`, rebuild images
(bakes in `composer install --no-dev` and `npm run build`), run migrations,
recreate `app`/`queue`/`scheduler`/`nginx`, warm `config`/`route`/`view`
caches, restart the queue worker so it picks up new code, prune old images.

Run it from `/opt/etec-system` on the server, not from your dev machine.

---

## 9. Contabo-specific notes

- **Two firewalls exist: Contabo's control-panel firewall and UFW.** If you
  enabled a firewall in the Contabo customer control panel (Network >
  Firewall), it's enforced *before* traffic reaches UFW. Either keep both in
  sync (allow 22/80/443 on both) or disable the control-panel one and rely
  on UFW alone — don't assume UFW rules are sufficient if the control-panel
  firewall is also active and more restrictive.
- **Default SSH port stays 22** unless you changed it — Contabo doesn't
  change this by default. If you do move it, update both firewalls and
  `fail2ban`'s jail config.
- **Backups/snapshots:** the Auto Backup add-on (recommended in
  vps-deployment-specs.md) takes VPS-level snapshots on Contabo's schedule.
  That's a disaster-recovery net, not a substitute for logical DB backups —
  consider adding a `mysqldump` cron writing to a separate location (or
  object storage) so you can restore a single table/day without rolling
  back the entire VPS.
- **Reverse DNS (rDNS):** set this in the Contabo control panel (VPS
  details > Network) if outbound mail matters (SMTP relay reputation,
  Telegram webhook calls don't need it). Point the PTR record at your
  domain if you're sending mail directly from the VPS; if you're using an
  external SMTP provider (recommended — see `.env.production.example`'s
  `MAIL_*` block), rDNS on the VPS itself is less critical.

---

## 10. Post-deploy checklist

- [ ] `https://your-domain.com` loads, padlock valid (`curl -I
      https://your-domain.com`)
- [ ] HTTP redirects to HTTPS (`curl -I http://your-domain.com`)
- [ ] Login works end-to-end (exercises DB connection + session)
- [ ] `docker compose -f docker-compose.prod.yml ps` — all containers
      `Up`/healthy, none restarting in a loop
- [ ] Queue worker is actually processing: trigger the Telegram
      admin-approval flow and confirm the message arrives, or check
      `docker compose -f docker-compose.prod.yml logs queue`
- [ ] Scheduler container logs show `schedule:run` ticking without errors
- [ ] `docker compose -f docker-compose.prod.yml exec app tail -f
      storage/logs/laravel.log` — no unexpected errors on a fresh request
- [ ] `APP_DEBUG=false` confirmed (hit a 500 deliberately, e.g. bad route,
      and make sure no stack trace/debug page is shown)
- [ ] phpMyAdmin is **not** running (`docker compose -f
      docker-compose.prod.yml ps phpmyadmin` shows nothing) — if you need it
      temporarily, bring it up with `--profile tools`, use it over an SSH
      tunnel only, then take it back down
- [ ] `.env` is `chmod 600` and not committed (`git status` clean, `.env`
      not tracked)
- [ ] UFW + Contabo control-panel firewall both allow only 22/80/443
- [ ] `systemctl status certbot.timer` is active (auto-renewal works)
- [ ] `systemctl status etec-system.service` enabled, survives a `sudo
      reboot`
- [ ] Old leaked `TELEGRAM_BOT_TOKEN` revoked via @BotFather, new token only
      ever in server-side `.env`
