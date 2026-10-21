<?php
require_once __DIR__ . '/data-store.php';
require_student_role('admin');
require_once __DIR__ . '/db.php';

const EVENT_POSTER_MAX_BYTES = 5242880;
const EVENT_POSTER_DIRECTORY = 'uploads/events';

function event_management_error($message, $statusCode = 422)
{
    render_response('Event management', $message, 'Return to event management', 'events.php', $statusCode);
    exit;
}

function event_poster_path($storedPath)
{
    if (!is_string($storedPath)
        || !preg_match('#^uploads/events/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $storedPath)) {
        return null;
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
}

function remove_event_poster($storedPath)
{
    $path = event_poster_path($storedPath);
    if ($path !== null && is_file($path) && !unlink($path)) {
        error_log('Could not remove event poster: ' . $path);
    }
}

function receive_event_poster($file)
{
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        event_management_error('Choose a valid poster image file.');
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'The poster exceeds the server upload limit. Choose an image up to 5 MB.'
            : 'The poster upload failed. Please choose the file again.';
        event_management_error($message);
    }
    if (!isset($file['tmp_name'], $file['size'])
        || !is_uploaded_file($file['tmp_name'])
        || $file['size'] < 1
        || $file['size'] > EVENT_POSTER_MAX_BYTES) {
        event_management_error('Choose a non-empty poster image no larger than 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mimeType]) || @getimagesize($file['tmp_name']) === false) {
        event_management_error('Poster must be a valid JPEG, PNG, or WebP image.');
    }

    $relativePath = EVENT_POSTER_DIRECTORY . '/' . bin2hex(random_bytes(16))
        . '.' . $extensions[$mimeType];
    $destination = event_poster_path($relativePath);
    if ($destination === null || !move_uploaded_file($file['tmp_name'], $destination)) {
        error_log('Could not store uploaded event poster in ' . EVENT_POSTER_DIRECTORY);
        event_management_error('The poster could not be saved. Check that the uploads/events folder is writable.', 500);
    }

    return $relativePath;
}

function validate_event_input()
{
    $event = [
        'title' => clean_input($_POST['title'] ?? null, 160),
        'description' => clean_input($_POST['description'] ?? null, 4000),
        'starts_at' => clean_input($_POST['starts_at'] ?? null, 16),
        'venue' => clean_input($_POST['venue'] ?? null, 160),
        'capacity' => clean_input($_POST['capacity'] ?? null, 10),
    ];

    $date = DateTime::createFromFormat('!Y-m-d\TH:i', $event['starts_at']);
    $dateErrors = DateTime::getLastErrors();
    $validDate = $date instanceof DateTime
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $date->format('Y-m-d\TH:i') === $event['starts_at'];
    $capacity = null;
    if ($event['capacity'] !== '') {
        $capacity = filter_var($event['capacity'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647],
        ]);
    }

    if ($event['title'] === '' || strlen($event['title']) > 160
        || $event['description'] === '' || strlen($event['description']) > 4000
        || !$validDate
        || $event['venue'] === '' || strlen($event['venue']) > 160
        || ($event['capacity'] !== '' && ($capacity === false || $capacity === null))) {
        event_management_error('Enter a title, description, valid start date and time, venue, and a positive whole-number capacity (or leave capacity blank).');
    }

    return [
        'title' => $event['title'],
        'description' => $event['description'],
        'starts_at' => $date->format('Y-m-d H:i:s'),
        'venue' => $event['venue'],
        'capacity' => $capacity,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_student_csrf_token($_POST['csrf_token'] ?? null)) {
        event_management_error('The request could not be verified. Refresh the page and try again.', 403);
    }

    $action = clean_input($_POST['action'] ?? null, 10);
    if ($action === 'delete') {
        $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($eventId === false || $eventId === null) {
            event_management_error('Select a valid event to delete.');
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
                event_management_error('Event was not found.', 404);
            }

            $removeRegistrations = $connection->prepare('DELETE FROM registrations WHERE event_id = ?');
            $removeRegistrations->bind_param('i', $eventId);
            $removeRegistrations->execute();
            $removeRegistrations->close();

            $delete = $connection->prepare('DELETE FROM events WHERE event_id = ?');
            $delete->bind_param('i', $eventId);
            $delete->execute();
            $deleted = $delete->affected_rows === 1;
            $delete->close();
            if (!$deleted) {
                $connection->rollback();
                event_management_error('Event could not be deleted.', 409);
            }
            $connection->commit();
            remove_event_poster($oldPoster);
            header('Location: events.php?status=deleted', true, 303);
            exit;
        } catch (Throwable $error) {
            if ($connection instanceof mysqli) {
                $connection->rollback();
            }
            error_log('Event deletion database error: ' . $error->getMessage());
            event_management_error('The event could not be deleted. Please try again later.', 500);
        }
    }

    if (!in_array($action, ['create', 'update'], true)) {
        event_management_error('Choose a valid event management action.');
    }

    $event = validate_event_input();
    $eventId = null;
    if ($action === 'update') {
        $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($eventId === false || $eventId === null) {
            event_management_error('Select a valid event to update.');
        }
    }

    $newPoster = receive_event_poster($_FILES['poster'] ?? null);
    if ($action === 'create' && $newPoster === null) {
        event_management_error('Upload a JPEG, PNG, or WebP event poster (maximum 5 MB) to create the event.');
    }

    try {
        $connection = studenthub_database();
        $oldPoster = null;
        if ($action === 'create') {
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
            $insert->close();
            header('Location: events.php?status=created', true, 303);
            exit;
        }

        $posterQuery = $connection->prepare('SELECT poster_path FROM events WHERE event_id = ?');
        $posterQuery->bind_param('i', $eventId);
        $posterQuery->execute();
        $posterQuery->bind_result($oldPoster);
        $found = $posterQuery->fetch();
        $posterQuery->close();
        if (!$found) {
            if ($newPoster !== null) {
                remove_event_poster($newPoster);
            }
            event_management_error('Event was not found.', 404);
        }
        $posterPath = $newPoster !== null ? $newPoster : $oldPoster;
        $update = $connection->prepare(
            'UPDATE events
             SET title = ?, description = ?, starts_at = ?, venue = ?, capacity = ?, poster_path = ?
             WHERE event_id = ?'
        );
        $update->bind_param(
            'ssssis' . 'i',
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
        if ($newPoster !== null) {
            remove_event_poster($oldPoster);
        }
        header('Location: events.php?status=updated', true, 303);
        exit;
    } catch (mysqli_sql_exception $error) {
        if ($newPoster !== null) {
            remove_event_poster($newPoster);
        }
        error_log('Event save database error: ' . $error->getMessage());
        event_management_error('The event could not be saved. Please check the database setup and try again.', 500);
    } catch (Throwable $error) {
        if ($newPoster !== null) {
            remove_event_poster($newPoster);
        }
        error_log('Event management error: ' . $error->getMessage());
        event_management_error('The event could not be saved. Please try again later.', 500);
    }
}

$statusMessages = [
    'created' => 'Event added successfully.',
    'updated' => 'Event updated successfully.',
    'deleted' => 'Event deleted successfully.',
];
$status = clean_input($_GET['status'] ?? null, 20);
$statusMessage = $statusMessages[$status] ?? '';
$editingEvent = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

try {
    $connection = studenthub_database();
    if ($editId !== false && $editId !== null) {
        $editQuery = $connection->prepare(
            'SELECT event_id, title, description, starts_at, venue, capacity, poster_path
             FROM events WHERE event_id = ?'
        );
        $editQuery->bind_param('i', $editId);
        $editQuery->execute();
        $editingEvent = $editQuery->get_result()->fetch_assoc();
        $editQuery->close();
        if (!$editingEvent) {
            event_management_error('Event was not found.', 404);
        }
    }
    $result = $connection->query(
        'SELECT event_id, title, description, starts_at, venue, capacity, poster_path
         FROM events ORDER BY starts_at DESC, event_id DESC'
    );
    $events = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} catch (Throwable $error) {
    error_log('Event listing database error: ' . $error->getMessage());
    event_management_error('Event records could not be loaded. Check the database setup and try again.', 500);
}

$csrfToken = student_csrf_token();
$formEvent = $editingEvent ?: [
    'event_id' => '',
    'title' => '',
    'description' => '',
    'starts_at' => '',
    'venue' => '',
    'capacity' => '',
    'poster_path' => null,
];
if ($editingEvent) {
    $startTime = strtotime($editingEvent['starts_at']);
    $formEvent['starts_at_input'] = $startTime === false ? '' : date('Y-m-d\TH:i', $startTime);
} else {
    $formEvent['starts_at_input'] = '';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Management | Vidya Setu</title>
    <link rel="stylesheet" href="../css/events.css" />
  </head>
  <body>
    <main class="event-management">
      <header class="event-management-header">
        <div>
          <h1>Event Management</h1>
          <p class="muted">Add and manage Student Hub events.</p>
        </div>
        <div class="event-actions">
          <a class="button-link button-secondary" href="admin-dashboard.php">Admin dashboard</a>
          <a class="button-link button-secondary" href="../index.html">Student Hub home</a>
          <form class="inline" action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
            <button type="submit">Log out</button>
          </form>
        </div>
      </header>

      <?php if ($statusMessage !== ''): ?>
        <p class="message" role="status"><?php echo html_escape($statusMessage); ?></p>
      <?php endif; ?>

      <section class="event-card">
        <h2><?php echo $editingEvent ? 'Edit event' : 'Add event'; ?></h2>
        <form method="post" action="events.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
          <input type="hidden" name="action" value="<?php echo $editingEvent ? 'update' : 'create'; ?>" />
          <?php if ($editingEvent): ?>
            <input type="hidden" name="event_id" value="<?php echo (int) $formEvent['event_id']; ?>" />
          <?php endif; ?>
          <div class="event-grid">
            <div>
              <label for="title">Event title</label>
              <input id="title" name="title" maxlength="160" required value="<?php echo html_escape($formEvent['title']); ?>" />
            </div>
            <div>
              <label for="starts-at">Date and time</label>
              <input id="starts-at" name="starts_at" type="datetime-local" required value="<?php echo html_escape($formEvent['starts_at_input']); ?>" />
            </div>
            <div>
              <label for="venue">Venue</label>
              <input id="venue" name="venue" maxlength="160" required value="<?php echo html_escape($formEvent['venue']); ?>" />
            </div>
            <div>
              <label for="capacity">Capacity (optional)</label>
              <input id="capacity" name="capacity" type="number" min="1" max="4294967295" value="<?php echo html_escape($formEvent['capacity']); ?>" />
            </div>
            <div class="wide">
              <label for="description">Description</label>
              <textarea id="description" name="description" maxlength="4000" required><?php echo html_escape($formEvent['description']); ?></textarea>
            </div>
            <div class="wide">
              <label for="poster">Poster image (JPEG, PNG, or WebP; max 5 MB)<?php echo $editingEvent ? ' — optional when replacing' : ''; ?></label>
              <input id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp" <?php echo $editingEvent ? '' : 'required'; ?> />
              <?php if ($editingEvent && $formEvent['poster_path']): ?>
                <p class="muted">Current poster:</p>
                <img class="poster-preview" src="../<?php echo html_escape($formEvent['poster_path']); ?>" alt="Current poster for <?php echo html_escape($formEvent['title']); ?>" />
              <?php endif; ?>
            </div>
          </div>
          <p class="muted">Poster must be a valid image file; the server verifies its actual content and enforces the 5 MB limit.</p>
          <button type="submit"><?php echo $editingEvent ? 'Save event changes' : 'Add event'; ?></button>
          <?php if ($editingEvent): ?>
            <a class="button-link button-secondary" href="events.php">Cancel edit</a>
          <?php endif; ?>
        </form>
      </section>

      <section class="event-card">
        <h2>Events (<?php echo count($events); ?>)</h2>
        <?php if ($events): ?>
          <table>
            <thead>
              <tr><th>Poster</th><th>Event</th><th>Date / venue</th><th>Capacity</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($events as $event): ?>
                <tr>
                  <td>
                    <?php if ($event['poster_path']): ?>
                      <img class="poster-thumb" src="../<?php echo html_escape($event['poster_path']); ?>" alt="Poster for <?php echo html_escape($event['title']); ?>" />
                    <?php else: ?>
                      <span class="muted">No poster</span>
                    <?php endif; ?>
                  </td>
                  <td><strong><?php echo html_escape($event['title']); ?></strong><br /><?php echo nl2br(html_escape($event['description'])); ?></td>
                  <td><?php echo html_escape(date('M j, Y g:i A', strtotime($event['starts_at']))); ?><br /><span class="muted"><?php echo html_escape($event['venue']); ?></span></td>
                  <td><?php echo $event['capacity'] === null ? 'Unlimited' : (int) $event['capacity']; ?></td>
                  <td class="actions">
                    <a class="button-link button-secondary" href="events.php?edit=<?php echo (int) $event['event_id']; ?>">Edit</a>
                    <form class="inline" method="post" action="events.php" onsubmit="return confirm('Delete this event and its registrations?');">
                      <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="event_id" value="<?php echo (int) $event['event_id']; ?>" />
                      <button class="button-danger" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">No events have been added yet.</p>
        <?php endif; ?>
      </section>
    </main>
  </body>
</html>
