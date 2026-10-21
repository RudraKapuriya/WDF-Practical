<?php

function json_data_path($filename)
{
    $allowed = ['registration.json', 'contact.json'];
    if (!in_array($filename, $allowed, true)) {
        throw new RuntimeException('Invalid data file.');
    }

    return __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $filename;
}

function read_json_records($filename)
{
    $handle = fopen(json_data_path($filename), 'c+');
    if ($handle === false || !flock($handle, LOCK_SH)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        throw new RuntimeException('Could not read the data file.');
    }

    try {
        rewind($handle);
        $contents = stream_get_contents($handle);
        if ($contents === false || trim($contents) === '') {
            return [];
        }

        $records = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($records)) {
            throw new RuntimeException('The data file contains invalid JSON.');
        }

        return $records;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function append_json_record($filename, $record)
{
    $handle = fopen(json_data_path($filename), 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        throw new RuntimeException('Could not save the data file.');
    }

    try {
        rewind($handle);
        $contents = stream_get_contents($handle);
        if ($contents === false) {
            throw new RuntimeException('Could not read the data file.');
        }
        $records = trim($contents) === '' ? [] : json_decode($contents, true);
        if (!is_array($records) || (trim($contents) !== '' && json_last_error() !== JSON_ERROR_NONE)) {
            throw new RuntimeException('The data file contains invalid JSON.');
        }

        $records[] = $record;
        $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || !ftruncate($handle, 0) || !rewind($handle)) {
            throw new RuntimeException('Could not write the data file.');
        }
        $written = fwrite($handle, $json . PHP_EOL);
        if ($written === false || $written !== strlen($json . PHP_EOL) || !fflush($handle)) {
            throw new RuntimeException('Could not write the data file.');
        }

        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function clean_input($value, $maximumLength)
{
    if (!is_string($value)) {
        return '';
    }

    $value = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/', '', trim($value));
    return strlen($value) <= $maximumLength ? $value : '';
}

function html_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render_response($title, $message, $linkText, $linkTarget, $statusCode = 200)
{
    http_response_code($statusCode);
    $safeTitle = html_escape($title);
    $safeMessage = html_escape($message);
    $safeLinkText = html_escape($linkText);
    $safeLinkTarget = html_escape($linkTarget);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $safeTitle . ' | Vidya Setu</title><style>body{font:16px/1.6 Arial,sans-serif;background:#f3f6f8;color:#18232d;margin:0;padding:8vh 20px}.panel{max-width:560px;margin:auto;background:#fff;border-top:5px solid #167c80;padding:28px;box-shadow:0 8px 30px #162b3718}h1{margin-top:0;font-size:26px}a{color:#12666a;font-weight:700}</style></head><body><main class="panel"><h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p><a href="' . $safeLinkTarget . '">' . $safeLinkText . '</a></main></body></html>';
}

function start_student_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();

    if (isset($_SESSION['user'], $_SESSION['last_activity'])
        && time() - (int) $_SESSION['last_activity'] > 900) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['session_expired'] = true;
    }

    if (isset($_SESSION['user'])) {
        $_SESSION['last_activity'] = time();
    }
}

function student_csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function valid_student_csrf_token($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_student_role($role)
{
    start_student_session();

    if (!isset($_SESSION['user']['id'], $_SESSION['user']['role'])) {
        header('Location: ../login.php' . (!empty($_SESSION['session_expired']) ? '?expired=1' : ''));
        exit;
    }

    if (!in_array($_SESSION['user']['role'], ['student', 'admin'], true)) {
        $_SESSION = [];
        session_destroy();
        header('Location: ../login.php', true, 303);
        exit;
    }

    if ($_SESSION['user']['role'] !== $role) {
        $dashboard = $_SESSION['user']['role'] === 'admin'
            ? 'admin-dashboard.php'
            : 'student-dashboard.php';
        header('Location: ' . $dashboard, true, 303);
        exit;
    }
}
