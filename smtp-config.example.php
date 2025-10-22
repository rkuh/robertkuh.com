<?php
// SMTP Configuration Template
// Copy this file to smtp-config.php and fill in your SMTP credentials

return [
    // SMTP server hostname
    // Examples:
    //   Gmail: smtp.gmail.com
    //   Office365: smtp.office365.com
    //   Yahoo: smtp.mail.yahoo.com
    //   GoDaddy: relay-hosting.secureserver.net
    //   Custom: mail.yoursite.com
    'host' => 'smtp.example.com',

    // SMTP username (usually your email address)
    'username' => 'your-email@example.com',

    // SMTP password or app-specific password
    'password' => 'your-password-here',

    // SMTP port
    // Common ports:
    //   587 - TLS/STARTTLS (recommended)
    //   465 - SSL
    //   25  - Plain (not recommended)
    'port' => 587,

    // Encryption type
    // Options: 'tls', 'ssl', or null for none
    // 587 usually uses 'tls'
    // 465 usually uses 'ssl'
    'encryption' => 'tls',
];
