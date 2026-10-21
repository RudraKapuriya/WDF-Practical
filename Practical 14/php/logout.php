<?php
require_once __DIR__ . '/data-store.php';
require_once __DIR__ . '/db.php';

start_student_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_response('Logout', 'Use the logout button to end your session.', 'Return to login', '../login.php', 405);
    exit;
}

if (!valid_student_csrf_token($_POST['csrf_token'] ?? null)) {
    render_response('Logout failed', 'The logout request could not be verified. Please try again.', 'Return to login', '../login.php', 403);
    exit;
}

$auditFailure = false;
if (($_SESSION['user']['role'] ?? null) === 'admin') {
    try {
        record_audit_log(
            studenthub_database(),
            (int) $_SESSION['user']['id'],
            'admin_logout',
            'session',
            null,
            'Administrator signed out'
        );
    } catch (Throwable $error) {
        error_log('Administrator logout audit error: ' . $error->getMessage());
        $auditFailure = true;
    }
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

if ($auditFailure) {
    render_response(
        'Logged out with an audit warning',
        'Your session was ended, but the administrator logout could not be recorded. Please notify the system administrator.',
        'Return to login',
        '../login.php',
        500
    );
    exit;
}

header('Location: ../login.php', true, 303);
exit;
