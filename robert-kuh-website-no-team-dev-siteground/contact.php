<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
if (empty($data['fullName']) || empty($data['email']) || empty($data['message'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Please fill in all required fields (Name, Email, and Message).'
    ]);
    exit;
}

// Sanitize input data
$fullName = filter_var(trim($data['fullName']), FILTER_SANITIZE_STRING);
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$company = filter_var(trim($data['company'] ?? ''), FILTER_SANITIZE_STRING);
$phone = filter_var(trim($data['phone'] ?? ''), FILTER_SANITIZE_STRING);
$serviceInterest = filter_var(trim($data['serviceInterest'] ?? ''), FILTER_SANITIZE_STRING);
$message = filter_var(trim($data['message']), FILTER_SANITIZE_STRING);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Please enter a valid email address.'
    ]);
    exit;
}

// Your email address
$to = 'rob@robertkuh.com';

// Email subject
$subject = 'New Contact Form Submission - Robert Kuh Consulting';

// Email body
$emailBody = "New contact form submission from robertkuh.com\n\n";
$emailBody .= "Name: " . $fullName . "\n";
$emailBody .= "Email: " . $email . "\n";

if (!empty($company)) {
    $emailBody .= "Company: " . $company . "\n";
}

if (!empty($phone)) {
    $emailBody .= "Phone: " . $phone . "\n";
}

if (!empty($serviceInterest)) {
    $emailBody .= "Service Interest: " . $serviceInterest . "\n";
}

$emailBody .= "\nMessage:\n" . $message . "\n\n";
$emailBody .= "---\n";
$emailBody .= "Submitted: " . date('Y-m-d H:i:s T') . "\n";
$emailBody .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";

// Email headers
$headers = array(
    'From: rob@robertkuh.com',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8'
);

// Send email
$mailSent = mail($to, $subject, $emailBody, implode("\r\n", $headers));

if ($mailSent) {
    // Log successful submission (optional)
    $logEntry = date('Y-m-d H:i:s') . " - Contact form submission from: " . $email . " (" . $fullName . ")\n";
    file_put_contents('contact_submissions.log', $logEntry, FILE_APPEND | LOCK_EX);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your message! I will get back to you within 24 hours.'
    ]);
} else {
    // Log failed submission
    $errorLog = date('Y-m-d H:i:s') . " - Failed to send email for: " . $email . "\n";
    file_put_contents('contact_errors.log', $errorLog, FILE_APPEND | LOCK_EX);
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'There was an error sending your message. Please try again or contact us directly at rob@robertkuh.com.'
    ]);
}
?>