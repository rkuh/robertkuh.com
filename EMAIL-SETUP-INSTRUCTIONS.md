# Schedule Form Email Setup Instructions

## What Was Fixed

Your contact form wasn't sending emails because:

1. **PHP `mail()` function limitations** - The basic PHP `mail()` function often fails on modern hosting without proper SMTP authentication
2. **Missing error logging** - There was no way to diagnose what was failing
3. **No SMTP authentication** - Modern email servers require authenticated SMTP to prevent spam

## What Was Implemented

### 1. Enhanced Logging System
- All form submissions are now logged to `_form_log/debug.log`
- Success and failure states are tracked
- Detailed error messages for troubleshooting

### 2. PHPMailer with SMTP Support
- Professional email library (PHPMailer v6.9.1)
- Full SMTP authentication support
- Falls back to basic `mail()` if SMTP isn't configured

### 3. Dual-Mode Operation
- **With SMTP config**: Uses authenticated SMTP (reliable)
- **Without SMTP config**: Falls back to basic `mail()` (may work on some servers)

## Setup Instructions for SiteGround SMTP

### Step 1: Get Your Email Password
You'll need the password for rob@robertkuh.com. If you don't have it:
1. Log into your SiteGround account
2. Go to **Email → Accounts**
3. Find rob@robertkuh.com
4. Either use the existing password or create a new one

### Step 2: Create SMTP Configuration File

Copy the SiteGround template to create your config:

```bash
cp smtp-config-siteground.example.php smtp-config.php
```

Then edit `smtp-config.php` and update the password:

```php
<?php
return [
    'host' => 'mail.robertkuh.com',
    'username' => 'rob@robertkuh.com',
    'password' => 'YOUR_ACTUAL_EMAIL_PASSWORD',  // <-- UPDATE THIS
    'port' => 587,
    'encryption' => 'tls',
];
```

### Step 3: Secure the Config File

**IMPORTANT**: Never commit smtp-config.php to git (it's already in .gitignore)

Set proper permissions:
```bash
chmod 600 smtp-config.php
```

### Step 4: Test the Form

1. Visit your website's contact form
2. Submit a test message
3. Check for emails at rob@robertkuh.com
4. If it doesn't work, check the debug logs:

```bash
cat _form_log/debug.log
```

## Troubleshooting

### No emails received?

1. **Check the debug log**:
   ```bash
   tail -50 _form_log/debug.log
   ```

2. **Common issues**:
   - Wrong password in smtp-config.php
   - Port blocked by firewall (try port 465 with 'ssl' encryption)
   - SMTP server hostname incorrect
   - Email account doesn't exist

3. **Try alternative SiteGround settings**:
   If port 587 doesn't work, edit smtp-config.php:
   ```php
   'port' => 465,
   'encryption' => 'ssl',
   ```

### Check if basic mail() works

Without SMTP configured, the system will try PHP's basic `mail()` function. Check the logs to see if it's working.

### Still not working?

Contact SiteGround support and ask for:
- SMTP server hostname for robertkuh.com
- Recommended port and encryption (TLS/SSL)
- Whether SMTP is enabled for your account

## File Structure

```
robertkuh.com/
├── contact.php                           # Main form handler (enhanced version)
├── contact.php.backup                    # Original version (backup)
├── smtp-config.php                       # YOUR CONFIG (create from template)
├── smtp-config-siteground.example.php    # SiteGround template
├── smtp-config.example.php               # Generic template
├── PHPMailer/                            # Email library
│   └── src/
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
└── _form_log/                            # Logs (auto-created)
    ├── debug.log                         # Detailed debugging
    ├── contact.log                       # Submission history
    └── .htaccess                         # Protects logs from web access

```

## Security Notes

- ✅ `smtp-config.php` is excluded from git
- ✅ `_form_log/` directory is protected with .htaccess
- ✅ Honeypot field prevents spam
- ✅ Input validation and sanitization
- ✅ Email validation
- ✅ Debug logs don't expose sensitive data

## Next Steps

1. Create `smtp-config.php` with your email password
2. Test the contact form
3. Check `_form_log/debug.log` if issues occur
4. Delete `EMAIL-SETUP-INSTRUCTIONS.md` after setup is complete

---

**Need help?** Check the debug logs first, they'll tell you exactly what's failing.
