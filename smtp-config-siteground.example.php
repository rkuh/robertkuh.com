<?php
// SiteGround SMTP Configuration Template
// Copy this file to smtp-config.php and fill in your email credentials

return [
    // SiteGround SMTP server - use your domain name
    // Format: mail.yourdomain.com
    'host' => 'mail.robertkuh.com',

    // Your full SiteGround email address
    'username' => 'rob@robertkuh.com',

    // Your email account password
    'password' => 'YOUR_EMAIL_PASSWORD_HERE',

    // SiteGround recommended port for TLS
    'port' => 587,

    // Use TLS encryption
    'encryption' => 'tls',

    // Alternative settings if port 587 doesn't work:
    // 'port' => 465,
    // 'encryption' => 'ssl',
];
