<?php
require_once __DIR__ . '/data-store.php';

start_student_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_response('Logout', 'Use the logout button to end your session.', 'Return to login', '../login.php', 405);
    exit;
}

if (!valid_student_csrf_token($_POST['csrf_token'] ?? null)) {
    render_response('Logout failed', 'The logout request could not be verified. Please try again.', 'Return to login', '../login.php', 403);
    exit;
}

$_SESSION = [];
$cookie = session_get_cookie_params();
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

header('Location: ../login.php', true, 303);
exit;
