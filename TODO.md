# Fix Rate Limiting for Account Recovery

## Status: [ ] In Progress

### Step 1: Create this TODO.md ✅

### Step 2: [ ] Increase rate limits in forgot-password.php files
- Edit `music-platform3/artist/forgot-password.php`: 3,3600 → 20,14400
- Edit `music-platform3/admin/forgot-password.php`: 3,3600 → 20,14400

### Step 3: [ ] Clear all existing rate-limit files
- Run: `rm c:/xampp/htdocs/music-platform3/storage/logs/ratelimit_*.json`

### Step 4: [ ] Test recovery flows
- Visit artist/admin forgot-password.php
- Send multiple requests → no block

### Step 5: [ ] Mark complete + cleanup TODO.md

**Notes:**
- Allows 20 requests / 4 hours per IP
- Files auto-cleaned by cron >1hr daily
- For production: Add CAPTCHA after 10 attempts

