<?php
require_once __DIR__ . '/data-store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_response('Contact form', 'Submit the contact form to send a message.', 'Return to contact', '../contact.html', 405);
    exit;
}

$name = clean_input($_POST['name'] ?? null, 80);
$email = strtolower(clean_input($_POST['email'] ?? null, 254));
$message = clean_input($_POST['message'] ?? null, 6000);

if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($message) < 1 || strlen($message) > 6000) {
    render_response('Message not sent', 'Enter a valid name and email address, and a message of up to 2,000 characters.', 'Return to contact', '../contact.html', 422);
    exit;
}

try {
    append_json_record('contact.json', [
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'submitted_at' => date(DATE_ATOM),
    ]);
} catch (Throwable $error) {
    render_response('Message could not be saved', 'The server could not save your message. Please try again later.', 'Return to contact', '../contact.html', 500);
    exit;
}

render_response('Message sent', 'Thank you. Your message has been received by student support.', 'Return to contact', '../contact.html');
