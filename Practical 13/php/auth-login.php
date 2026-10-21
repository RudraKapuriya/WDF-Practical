<?php
require_once __DIR__ . '/data-store.php';
require_once __DIR__ . '/db.php';

start_student_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_response('Login', 'Submit the login form to continue.', 'Go to login', '../login.php', 405);
    exit;
}

if (!valid_student_csrf_token($_POST['csrf_token'] ?? null)) {
    render_response('Login expired', 'The login form expired. Please try again.', 'Go to login', '../login.php', 403);
    exit;
}

$identifier = is_string($_POST['identifier'] ?? null) ? trim($_POST['identifier']) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
if ($identifier === '' || strlen($identifier) > 254 || $password === '') {
    render_response('Login failed', 'The username/email or password is incorrect.', 'Try again', '../login.php', 401);
    exit;
}

try {
    $connection = studenthub_database();
    $statement = $connection->prepare(
        'SELECT student_id, full_name, username, password_hash, role
         FROM students
         WHERE username = ? OR email = ?
         LIMIT 1'
    );
    $statement->bind_param('ss', $identifier, $identifier);
    $statement->execute();
    $statement->bind_result($studentId, $fullName, $username, $passwordHash, $role);
    $found = $statement->fetch();
    $statement->close();
} catch (Throwable $error) {
    error_log('Student login database error: ' . $error->getMessage());
    render_response('Login unavailable', 'The server could not verify your account. Please try again later.', 'Return to login', '../login.php', 500);
    exit;
}

if (!$found || !password_verify($password, $passwordHash)
    || !in_array($role, ['student', 'admin'], true)) {
    render_response('Login failed', 'The username/email or password is incorrect.', 'Try again', '../login.php', 401);
    exit;
}

if (!session_regenerate_id(true)) {
    error_log('Student login error: could not rotate the session ID.');
    render_response('Login unavailable', 'The server could not start a secure session. Please try again later.', 'Return to login', '../login.php', 500);
    exit;
}
$_SESSION['user'] = [
    'id' => (int) $studentId,
    'name' => $fullName,
    'username' => $username,
    'role' => $role,
];
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
unset($_SESSION['session_expired']);

$dashboard = $role === 'admin' ? 'admin-dashboard.php' : 'student-dashboard.php';
header('Location: ' . $dashboard, true, 303);
exit;
