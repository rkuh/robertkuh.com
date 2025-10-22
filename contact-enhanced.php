<?php
// Enhanced contact form handler with detailed logging and PHPMailer support
// This version provides better debugging and reliability

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user

// Logging function
function logDebug($message, $data = null) {
    $logdir = __DIR__.'/_form_log';
    if (!is_dir($logdir)) @mkdir($logdir, 0755, true);
    @file_put_contents($logdir.'/.htaccess', "Require all denied\n");

    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] $message";
    if ($data !== null) {
        $logLine .= " | " . json_encode($data);
    }
    $logLine .= "\n";
    @file_put_contents($logdir.'/debug.log', $logLine, FILE_APPEND);
}

logDebug("=== NEW REQUEST ===");
logDebug("REQUEST_METHOD", $_SERVER['REQUEST_METHOD']);
logDebug("CONTENT_TYPE", $_SERVER['CONTENT_TYPE'] ?? 'not set');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("ERROR: Method not allowed");
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
    exit;
}

function clean($v){ return trim(filter_var($v, FILTER_UNSAFE_RAW)); }

// Detect body type: JSON vs form
$ctype = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input');
$data = [];

if (stripos($ctype, 'application/json') !== false) {
    $data = json_decode($raw, true) ?: [];
    logDebug("Parsed JSON data", array_keys($data));
} else {
    $data = $_POST ?: [];
    logDebug("Parsed FORM data", array_keys($data));
}

// Map field names from either style
$name = clean($data['full_name'] ?? $data['fullName'] ?? $data['name'] ?? '');
$email = clean($data['email'] ?? '');
$company = clean($data['company'] ?? '');
$phone   = clean($data['phone'] ?? '');
$service = clean($data['service'] ?? $data['serviceInterest'] ?? '');
$message = clean($data['message'] ?? '');
$honeypot = clean($data['website'] ?? '');

logDebug("Extracted fields", [
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'phone' => $phone,
    'service' => $service,
    'honeypot' => $honeypot ? 'SPAM' : 'OK'
]);

// Basic validation
if ($honeypot !== '') {
    logDebug("SPAM: Honeypot triggered");
    echo json_encode(['ok'=>true]);
    exit;
}

if ($name === '' || $email === '' || $message === '') {
    logDebug("ERROR: Missing required fields");
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Missing required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logDebug("ERROR: Invalid email format", $email);
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Invalid email address']);
    exit;
}

// Configuration
$TO_EMAIL   = 'rob@robertkuh.com';
$SITE_NAME  = 'RobertKuh.com';
$FROM_EMAIL = 'no-reply@robertkuh.com';

// Build email content
$subject = "New Contact • $name";
$body  = "New contact form submission from $SITE_NAME\n\n";
$body .= "Name: $name\nEmail: $email\n";
if ($company) $body .= "Company: $company\n";
if ($phone)   $body .= "Phone: $phone\n";
if ($service) $body .= "Service: $service\n";
$body .= "\nMessage:\n$message\n\n";
$body .= "----\nSubmitted: ".date('Y-m-d H:i:s T')."\n";
$body .= "IP: ".($_SERVER['REMOTE_ADDR'] ?? 'unknown')."\n";
$body .= "UA: ".($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')."\n";

logDebug("Email prepared", ['to' => $TO_EMAIL, 'subject' => $subject]);

// Check if PHPMailer is available
$phpmailerPath = __DIR__.'/PHPMailer/src/PHPMailer.php';
$usePHPMailer = file_exists($phpmailerPath);

logDebug("PHPMailer available", $usePHPMailer ? 'YES' : 'NO');

$sent = false;
$errorMsg = '';

// Try PHPMailer with SMTP if configured
if ($usePHPMailer) {
    require $phpmailerPath;
    require __DIR__.'/PHPMailer/src/SMTP.php';
    require __DIR__.'/PHPMailer/src/Exception.php';

    $configFile = __DIR__.'/smtp-config.php';
    if (file_exists($configFile)) {
        logDebug("Loading SMTP config");
        $smtpConfig = require $configFile;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpConfig['username'];
            $mail->Password = $smtpConfig['password'];
            $mail->SMTPSecure = $smtpConfig['encryption'] ?? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpConfig['port'];

            // Email content
            $mail->setFrom($FROM_EMAIL, $SITE_NAME);
            $mail->addAddress($TO_EMAIL);
            $mail->addReplyTo($email, $name);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $sent = $mail->send();
            logDebug("PHPMailer SMTP send", $sent ? 'SUCCESS' : 'FAILED');
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            logDebug("PHPMailer ERROR", $errorMsg);
        }
    } else {
        logDebug("SMTP config not found, will try basic mail()");
    }
}

// Fallback to basic mail() if PHPMailer didn't work
if (!$sent) {
    logDebug("Attempting basic mail() function");

    // Check if mail function exists
    if (!function_exists('mail')) {
        logDebug("ERROR: mail() function not available");
        $errorMsg = "Mail function not available on server";
    } else {
        $headers = [];
        $headers[] = "From: $SITE_NAME <$FROM_EMAIL>";
        $headers[] = "Reply-To: $name <$email>";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers_str = implode("\r\n", $headers);

        $sent = @mail($TO_EMAIL, $subject, $body, $headers_str);

        if ($sent) {
            logDebug("Basic mail() send SUCCESS");
        } else {
            logDebug("Basic mail() send FAILED");
            $errorMsg = "mail() function returned false";

            // Try to get more error info
            $error = error_get_last();
            if ($error) {
                logDebug("Last PHP error", $error);
            }
        }
    }
}

// Log the submission
$logdir = __DIR__.'/_form_log';
$logline = date('c')." | ".($sent?'SENT':'FAILED')." | $name <$email> | $service | ".str_replace(["\r","\n"],' ', $message)."\n";
@file_put_contents($logdir.'/contact.log', $logline, FILE_APPEND);

// Response
if ($sent) {
    logDebug("=== SUCCESS ===");
    echo json_encode(['ok'=>true,'message'=>'Thanks — your message has been sent.']);
} else {
    logDebug("=== FINAL FAILURE ===", $errorMsg);
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Unable to send email. Please contact rob@robertkuh.com directly.', 'debug' => $errorMsg]);
}
