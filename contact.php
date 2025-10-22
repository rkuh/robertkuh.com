<?php
// /public_html/contact.php — robust handler that accepts JSON or form posts,
// and supports multiple field name variants used on your site.

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
} else {
  $data = $_POST ?: [];
}

// Map field names from either style
$name = clean($data['full_name'] ?? $data['fullName'] ?? $data['name'] ?? '');
$email = clean($data['email'] ?? '');
$company = clean($data['company'] ?? '');
$phone   = clean($data['phone'] ?? '');
$service = clean($data['service'] ?? $data['serviceInterest'] ?? '');
$message = clean($data['message'] ?? '');
$honeypot = clean($data['website'] ?? '');

// Basic validation
if ($honeypot !== '') { echo json_encode(['ok'=>true]); exit; }
if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'error'=>'Missing or invalid fields']);
  exit;
}

// Build email
$TO_EMAIL   = 'rob@robertkuh.com';
$SITE_NAME  = 'RobertKuh.com';
$FROM_EMAIL = 'no-reply@robertkuh.com'; // must be your domain for DMARC

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

// Headers
$headers = [];
$headers[] = "From: $SITE_NAME <$FROM_EMAIL>";
$headers[] = "Reply-To: $name <$email>";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=UTF-8";
$headers_str = implode("\r\n", $headers);

// Send
$sent = @mail($TO_EMAIL, $subject, $body, $headers_str);

// Private log
$logdir = __DIR__.'/_form_log';
if (!is_dir($logdir)) @mkdir($logdir, 0755, true);
@file_put_contents($logdir.'/.htaccess', "Require all denied\n");
$logline = date('c')." | ".($sent?'SENT':'FAILED')." | $name <$email> | $service | ".str_replace(["\r","\n"],' ', $message)."\n";
@file_put_contents($logdir.'/contact.log', $logline, FILE_APPEND);

// Response
if ($sent) {
  echo json_encode(['ok'=>true,'message'=>'Thanks — your message has been sent.']);
} else {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Mailer failed']);
}
