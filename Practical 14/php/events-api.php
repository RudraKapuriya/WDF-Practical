<?php
require_once __DIR__ . '/data-store.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function events_api_response($statusCode, $success, $message, $data = null)
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function events_api_input()
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw === false ? '' : $raw, true);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            events_api_response(400, false, 'Request body must contain valid JSON.', null);
        }
        return $decoded;
    }

    return $_POST;
}

function events_api_id($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    return $id === false || $id === null ? null : (int) $id;
}

function events_api_validate_event($input)
{
    $title = clean_input($input['title'] ?? null, 160);
    $description = clean_input($input['description'] ?? null, 4000);
    $startsAt = clean_input($input['starts_at'] ?? null, 16);
    $venue = clean_input($input['venue'] ?? null, 160);
    $capacityInput = clean_input($input['capacity'] ?? null, 10);

    $date = DateTime::createFromFormat('!Y-m-d\TH:i', $startsAt);
    $dateErrors = DateTime::getLastErrors();
    $validDate = $date instanceof DateTime
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $date->format('Y-m-d\TH:i') === $startsAt;
    $capacity = null;
    if ($capacityInput !== '') {
        $capacity = filter_var($capacityInput, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647],
        ]);
    }

    if ($title === '' || $description === '' || $venue === '' || !$validDate
        || ($capacityInput !== '' && ($capacity === false || $capacity === null))) {
        events_api_response(
            422,
            false,
            'Enter a title, description, valid date and time, venue, and a positive whole-number capacity or leave capacity blank.',
            null
        );
    }

    return [
        'title' => $title,
        'description' => $description,
        'starts_at' => $date->format('Y-m-d H:i:s'),
        'venue' => $venue,
        'capacity' => $capacity,
    ];
}

function events_api_poster_path($storedPath)
{
    if (!is_string($storedPath)
        || !preg_match('#^uploads/events/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $storedPath)) {
        return null;
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
}

function events_api_remove_poster($storedPath)
{
    $path = events_api_poster_path($storedPath);
    if ($path !== null && is_file($path) && !unlink($path)) {
        error_log('Could not remove event poster: ' . $path);
    }
}

function events_api_upload_poster()
{
    $file = $_FILES['poster'] ?? null;
    if ($file === null) {
        return null;
    }
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        events_api_response(422, false, 'Choose a valid poster image file.', null);
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'Poster is larger than the server upload limit. Choose an image up to 5 MB.'
            : 'Poster upload failed. Choose the file again.';
        events_api_response(422, false, $message, null);
    }
    if (!isset($file['tmp_name'], $file['size'])
        || !is_uploaded_file($file['tmp_name'])
        || $file['size'] < 1
        || $file['size'] > 5242880) {
        events_api_response(422, false, 'Poster must be a non-empty image no larger than 5 MB.', null);
    }

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mimeType]) || @getimagesize($file['tmp_name']) === false) {
        events_api_response(422, false, 'Poster must be a valid JPEG, PNG, or WebP image.', null);
    }

    $relativePath = 'uploads/events/' . bin2hex(random_bytes(16))
        . '.' . $extensions[$mimeType];
    $destination = events_api_poster_path($relativePath);
    if ($destination === null || !move_uploaded_file($file['tmp_name'], $destination)) {
        error_log('Could not store uploaded event poster in uploads/events.');
        events_api_response(500, false, 'Poster could not be saved. Check the uploads/events folder permissions.', null);
    }

    return $relativePath;
}

function events_api_event_row($row)
{
    return [
        'id' => (int) $row['event_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'starts_at' => $row['starts_at'],
        'venue' => $row['venue'],
        'capacity' => $row['capacity'] === null ? null : (int) $row['capacity'],
        'poster_url' => $row['poster_path'] ? '../' . $row['poster_path'] : null,
    ];
}

start_student_session();
if (!isset($_SESSION['user']['id'], $_SESSION['user']['role'])) {
    events_api_response(401, false, !empty($_SESSION['session_expired'])
        ? 'Your session expired. Sign in again to continue.'
        : 'Sign in with an administrator account to manage events.', null);
}
if ($_SESSION['user']['role'] !== 'admin') {
    events_api_response(403, false, 'Administrator access is required to manage events.', null);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $eventId = null;
    if (isset($_GET['id'])) {
        $eventId = events_api_id($_GET['id']);
        if ($eventId === null) {
            events_api_response(422, false, 'Event ID must be a positive integer.', null);
        }
    }

    try {
        $connection = studenthub_database();
        if ($eventId !== null) {
            $statement = $connection->prepare(
                'SELECT event_id, title, description, starts_at, venue, capacity, poster_path
                 FROM events WHERE event_id = ?'
            );
            $statement->bind_param('i', $eventId);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$row) {
                events_api_response(404, false, 'Event was not found.', null);
            }
            events_api_response(200, true, 'Event loaded.', ['event' => events_api_event_row($row)]);
        }

        $result = $connection->query(
            'SELECT event_id, title, description, starts_at, venue, capacity, poster_path
             FROM events ORDER BY starts_at DESC, event_id DESC'
        );
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = events_api_event_row($row);
        }
        $result->free();
        events_api_response(200, true, 'Events loaded.', ['events' => $events]);
    } catch (Throwable $error) {
        error_log('Events API list error: ' . $error->getMessage());
        events_api_response(500, false, 'Events could not be loaded. Please try again later.', null);
    }
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    events_api_response(405, false, 'Use GET to read events and POST to create, update, or delete events.', null);
}

$input = events_api_input();
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? null);
if (!valid_student_csrf_token($csrfToken)) {
    events_api_response(403, false, 'Request verification failed. Refresh the page and try again.', null);
}

$action = clean_input($input['action'] ?? null, 10);
if (!in_array($action, ['create', 'update', 'delete'], true)) {
    events_api_response(422, false, 'Action must be create, update, or delete.', null);
}

if ($action === 'delete') {
    $eventId = events_api_id($input['id'] ?? null);
    if ($eventId === null) {
        events_api_response(422, false, 'Event ID must be a positive integer.', null);
    }

    $connection = null;
    try {
        $connection = studenthub_database();
        $connection->begin_transaction();
        $posterQuery = $connection->prepare('SELECT poster_path FROM events WHERE event_id = ?');
        $posterQuery->bind_param('i', $eventId);
        $posterQuery->execute();
        $posterQuery->bind_result($oldPoster);
        $found = $posterQuery->fetch();
        $posterQuery->close();
        if (!$found) {
            $connection->rollback();
            events_api_response(404, false, 'Event was not found.', null);
        }

        $registrations = $connection->prepare('DELETE FROM registrations WHERE event_id = ?');
        $registrations->bind_param('i', $eventId);
        $registrations->execute();
        $registrations->close();

        $delete = $connection->prepare('DELETE FROM events WHERE event_id = ?');
        $delete->bind_param('i', $eventId);
        $delete->execute();
        $deleted = $delete->affected_rows === 1;
        $delete->close();
        if (!$deleted) {
            $connection->rollback();
            events_api_response(409, false, 'Event could not be deleted.', null);
        }
        record_audit_log($connection, (int) $_SESSION['user']['id'], 'event_delete', 'event', $eventId, 'Event and registrations deleted');
        $connection->commit();
        events_api_remove_poster($oldPoster);
        events_api_response(200, true, 'Event deleted successfully.', ['id' => $eventId]);
    } catch (Throwable $error) {
        if ($connection instanceof mysqli) {
            $connection->rollback();
        }
        error_log('Events API delete error: ' . $error->getMessage());
        events_api_response(500, false, 'Event could not be deleted. Please try again later.', null);
    }
}

$event = events_api_validate_event($input);
$eventId = null;
if ($action === 'update') {
    $eventId = events_api_id($input['id'] ?? null);
    if ($eventId === null) {
        events_api_response(422, false, 'Event ID must be a positive integer.', null);
    }
}
$newPoster = events_api_upload_poster();
if ($action === 'create' && $newPoster === null) {
    events_api_response(422, false, 'Upload a JPEG, PNG, or WebP poster no larger than 5 MB to create an event.', null);
}

try {
    $connection = studenthub_database();
    if ($action === 'create') {
        $connection->begin_transaction();
        $insert = $connection->prepare(
            'INSERT INTO events (title, description, starts_at, venue, capacity, poster_path)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insert->bind_param(
            'ssssis',
            $event['title'],
            $event['description'],
            $event['starts_at'],
            $event['venue'],
            $event['capacity'],
            $newPoster
        );
        $insert->execute();
        $eventId = $insert->insert_id;
        $insert->close();
        $posterPath = $newPoster;
        record_audit_log($connection, (int) $_SESSION['user']['id'], 'event_create', 'event', (int) $eventId, 'Event created');
        $connection->commit();
    } else {
        $connection->begin_transaction();
        $posterQuery = $connection->prepare('SELECT poster_path FROM events WHERE event_id = ?');
        $posterQuery->bind_param('i', $eventId);
        $posterQuery->execute();
        $posterQuery->bind_result($oldPoster);
        $found = $posterQuery->fetch();
        $posterQuery->close();
        if (!$found) {
            $connection->rollback();
            if ($newPoster !== null) {
                events_api_remove_poster($newPoster);
            }
            events_api_response(404, false, 'Event was not found.', null);
        }

        $posterPath = $newPoster !== null ? $newPoster : $oldPoster;
        $update = $connection->prepare(
            'UPDATE events
             SET title = ?, description = ?, starts_at = ?, venue = ?, capacity = ?, poster_path = ?
             WHERE event_id = ?'
        );
        $update->bind_param(
            'ssssisi',
            $event['title'],
            $event['description'],
            $event['starts_at'],
            $event['venue'],
            $event['capacity'],
            $posterPath,
            $eventId
        );
        $update->execute();
        $update->close();
        record_audit_log($connection, (int) $_SESSION['user']['id'], 'event_update', 'event', $eventId, 'Event updated');
        $connection->commit();
        if ($newPoster !== null) {
            events_api_remove_poster($oldPoster);
        }
    }

    $savedEvent = [
        'id' => (int) $eventId,
        'title' => $event['title'],
        'description' => $event['description'],
        'starts_at' => $event['starts_at'],
        'venue' => $event['venue'],
        'capacity' => $event['capacity'],
        'poster_url' => $posterPath ? '../' . $posterPath : null,
    ];
    events_api_response(
        $action === 'create' ? 201 : 200,
        true,
        $action === 'create' ? 'Event added successfully.' : 'Event updated successfully.',
        ['event' => $savedEvent]
    );
} catch (Throwable $error) {
    if (isset($connection) && $connection instanceof mysqli) {
        $connection->rollback();
    }
    if ($newPoster !== null) {
        events_api_remove_poster($newPoster);
    }
    error_log('Events API save error: ' . $error->getMessage());
    events_api_response(500, false, 'Event could not be saved. Please try again later.', null);
}
