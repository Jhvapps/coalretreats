<?php
// Coal Retreats — enquiry form handler
// Receives POST from index.html enquiry form, sends to info@coalretreats.com
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Simple honeypot — bots tend to fill every field
if (!empty($_POST['_hp'] ?? '')) {
    // Pretend success so bot doesn't retry
    echo json_encode(['ok' => true]);
    exit;
}

$firstName     = trim($_POST['firstName']     ?? '');
$lastName      = trim($_POST['lastName']      ?? '');
$email         = trim($_POST['email']         ?? '');
$arrivalDate   = trim($_POST['arrivalDate']   ?? '');
$departureDate = trim($_POST['departureDate'] ?? '');
$groupSize     = trim($_POST['groupSize']     ?? '');
$tripType      = trim($_POST['tripType']      ?? '');
$message       = trim($_POST['message']       ?? '');

// Basic validation
if (strlen($firstName) < 1 || strlen($firstName) > 80) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid first name']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address']);
    exit;
}
if (strlen($message) > 4000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message too long']);
    exit;
}

$name    = trim($firstName . ' ' . $lastName);
$to      = 'info@coalretreats.com';
$subject = 'New website enquiry — ' . $name;

$bodyLines = [
    'New enquiry from coalretreats.com:',
    '',
    'Name:       ' . $name,
    'Email:      ' . $email,
    'Arrival:    ' . ($arrivalDate ?: '—'),
    'Departure:  ' . ($departureDate ?: '—'),
    'Group size: ' . ($groupSize ?: '—'),
    'Trip type:  ' . ($tripType ?: '—'),
    '',
    'Message:',
    $message ?: '(no message)',
    '',
    '-- ',
    'Sent from the Coal Retreats website enquiry form.',
    'Submitted: ' . gmdate('Y-m-d H:i:s') . ' UTC',
    'IP:        ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
];
$body = implode("\r\n", $bodyLines);

$safeFromName  = '=?UTF-8?B?' . base64_encode('Coal Retreats Website') . '?=';
$safeSubject   = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$replyToSafe   = preg_replace('/[\r\n]/', '', $email);

$headers   = [];
$headers[] = 'From: ' . $safeFromName . ' <bookings@coalretreats.com>';
$headers[] = 'Reply-To: ' . $replyToSafe;
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'Content-Transfer-Encoding: 8bit';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$sent = @mail($to, $safeSubject, $body, implode("\r\n", $headers));

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail send failed. Please email bookings@coalretreats.com directly.']);
    exit;
}

echo json_encode(['ok' => true]);
