<?php
require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');
try {
    studenthub_database();
    http_response_code(200);
    $title = 'Database connection successful';
    $message = 'PHP connected to the StudentHub MySQL database.';
} catch (Throwable $exception) {
    http_response_code(500);
    $title = 'Database connection failed';
    $message = 'Check that MySQL is running, the schema is imported, and the database environment settings are configured.';
}

$safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $safeTitle . ' | StudentHub</title><style>body{font:16px/1.6 Arial,sans-serif;background:#f3f6f8;color:#18232d;margin:0;padding:8vh 20px}.panel{max-width:560px;margin:auto;background:#fff;border-top:5px solid #167c80;padding:28px;box-shadow:0 8px 30px #162b3718}h1{margin-top:0;font-size:26px}</style></head><body><main class="panel"><h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p></main></body></html>';
