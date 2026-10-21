<?php
require_once __DIR__ . '/data-store.php';
require_student_role('admin');
$csrfToken = student_csrf_token();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?php echo html_escape($csrfToken); ?>" />
    <title>Async Event Management | Vidya Setu</title>
    <link rel="stylesheet" href="../css/events.css" />
    <link rel="stylesheet" href="../css/index.css" />
  </head>
  <body>
    <main class="event-management" data-event-manager data-api="events-api.php">
      <header class="event-management-header">
        <div>
          <h1>Event Management</h1>
          <p class="muted">Manage events asynchronously with the PHP JSON API.</p>
        </div>
        <div class="event-actions">
          <a class="button-link button-secondary" href="admin-dashboard.php">Admin dashboard</a>
          <form class="inline" action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
            <button type="submit">Log out</button>
          </form>
        </div>
      </header>

      <p class="message" data-status role="status" aria-live="polite">Loading events...</p>

      <section class="event-card">
        <h2 data-form-title>Add event</h2>
        <form data-event-form enctype="multipart/form-data">
          <input type="hidden" name="id" />
          <div class="event-grid">
            <div>
              <label for="event-title">Event title</label>
              <input id="event-title" name="title" maxlength="160" required />
            </div>
            <div>
              <label for="event-starts-at">Date and time</label>
              <input id="event-starts-at" name="starts_at" type="datetime-local" required />
            </div>
            <div>
              <label for="event-venue">Venue</label>
              <input id="event-venue" name="venue" maxlength="160" required />
            </div>
            <div>
              <label for="event-capacity">Capacity (optional)</label>
              <input id="event-capacity" name="capacity" type="number" min="1" max="2147483647" />
            </div>
            <div class="wide">
              <label for="event-description">Description</label>
              <textarea id="event-description" name="description" maxlength="4000" required></textarea>
            </div>
            <div class="wide">
              <label for="event-poster">Poster (JPEG, PNG, WebP; max 5 MB)</label>
              <input id="event-poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp" required />
              <p class="muted" data-poster-note>Poster is required for a new event. When editing, leave this empty to keep the current poster.</p>
              <img class="poster-preview" data-poster-preview alt="" hidden />
            </div>
          </div>
          <button type="submit" data-save-button>Add event</button>
          <button class="button-secondary" type="button" data-cancel-edit hidden>Cancel edit</button>
        </form>
      </section>

      <section class="event-card">
        <h2>Events <span data-count>(0)</span></h2>
        <p class="muted" data-list-status>Events have not loaded yet.</p>
        <div data-event-list></div>
      </section>
    </main>
    <script src="../js/events-manager.js" defer></script>
  </body>
</html>
