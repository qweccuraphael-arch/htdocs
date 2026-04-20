# Forgot Password & Reset for Admin and Artist in music-platform3

**Information Gathered:** 
- Admin login uses direct DB query in admin/login.php
- Artist login uses ArtistController
- Artist model has updatePassword()
- No reset tokens table or functions
- sendEmail() and rateLimitCheck() ready
- Similar to music-platform setup

**Plan:**
1. Update database.sql: Add admin_reset_tokens and artist_reset_tokens tables
2. Add reset functions to app/helpers/auth.php (adminSendResetEmail, adminValidateResetToken, adminResetPassword, artistSendResetEmail, artistValidateResetToken, artistResetPassword)
3. Create admin/forgot-password.php, admin/reset-password.php
4. Create artist/forgot-password.php, artist/reset-password.php
5. Edit admin/login.php and artist/login.php: Add "Forgot Password?" link below form
6. Test

**Dependent Files:**
- database.sql
- app/helpers/auth.php
- admin/login.php, artist/login.php
- New: admin/artist/forgot-password.php, reset-password.php

**Followup:**
- Import updated database.sql
- Test login → forgot → email → reset → login
- Update SMTP in config/app.php

Proceed?

