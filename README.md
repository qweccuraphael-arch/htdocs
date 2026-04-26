# 🎵 BeatWave — Ghana's Music Platform

A full-featured music download platform with artist portal, admin dashboard, AdSense monetization, and automated email + SMS notifications.

---

## 🚀 Quick Setup

### 1. Database
```sql
mysql -u root -p < database.sql
```


### 2. Configuration

**`config/app.php`** — Update these values:
| Setting | Description |
|---|---|
| `APP_URL` | Your domain e.g. `https://beatwave.com` |
| `SMTP_USER` / `SMTP_PASS` | Gmail App Password for email |
| `SMS_API_KEY` | Arkesel API key (get at arkesel.com) |
| `SMS_SENDER_ID` | Your sender name (max 11 chars) |

**`config/ads.php`** — Add your AdSense publisher ID and slot IDs.

### 3. File Permissions
```bash
chmod 755 storage/music storage/logs storage/covers storage/photos
```

### 4. Web Server (Apache)
- Point document root to the project folder
- Ensure `mod_rewrite` is enabled
- The `.htaccess` handles security and routing

---

## 📁 Structure Overview

```
music-platform/
├── public/          ← User-facing site (homepage, song pages, download)
├── admin/           ← Admin panel (login: /admin/login.php)
├── artist/          ← Artist portal (login: /artist/login.php)
├── api/             ← JSON API endpoints
├── app/             ← Core MVC logic
├── config/          ← Settings (db, app, ads)
├── storage/         ← Protected file storage
├── database.sql     ← Full DB schema + default admin
└── cron.php         ← Scheduled tasks
```

---

## 📧 Email Notifications

Sent automatically via PHP `mail()` (or configure SMTP in `config/app.php`):

| Trigger | Recipient |
|---|---|
| Artist registers | Artist (welcome) + Admin (alert) |
| Song approved | Artist |
| Song rejected | Artist |
| Monthly report (cron) | All artists with earnings |

---

## 📱 SMS Notifications (Ghana)

Configured for **Arkesel** (default), **mNotify**, or **Twilio**:

| Trigger | Message |
|---|---|
| Registration | Welcome message |
| Song approved | "Your song is now LIVE!" |
| Song rejected | Rejection with reason |
| Monthly earnings | Earnings summary |

**Switch provider** in `config/app.php` → change `SMS_PROVIDER` to `arkesel`, `mnotify`, or `twilio`.

Ghana phone numbers (format `024XXXXXXX`) are **auto-converted** to international format `233XXXXXXXXX`.

---

## 💰 Earnings

- Artists earn **GHS 1.50 per download** (change `EARNINGS_PER_DOWNLOAD` in `config/app.php`)
- Earnings are recorded automatically on every download
- Monthly reports sent via cron job

### Cron Setup
```bash
# Add to crontab (crontab -e)
0 9 1 * * php /path/to/music-platform/cron.php monthly_reports
0 3 * * * php /path/to/music-platform/cron.php cleanup_logs
```

---

## 🔐 Security Features

- Password hashing (bcrypt)
- CSRF protection on all forms
- Rate limiting on downloads (10/min per IP)
- Referer check on download.php
- Storage directory blocked from direct access
- File type validation (MIME + extension)
- SQL injection prevention (PDO prepared statements)
- XSS prevention (htmlspecialchars throughout)
- Security headers via .htaccess

---

## 📞 Support

Built by **Raphael** — Ghana Web Dev & Tech Services  
📞 [0249740636](tel:0249740636)  
📧 qweccuraphael@gmail.com
