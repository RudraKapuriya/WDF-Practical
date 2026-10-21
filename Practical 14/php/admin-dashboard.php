<?php
require_once __DIR__ . '/data-store.php';
require_student_role('admin');
require_once __DIR__ . '/db.php';

$user = $_SESSION['user'];
$csrfToken = student_csrf_token();
$dashboardError = '';
$stats = [
    'users' => 0,
    'students' => 0,
    'admins' => 0,
    'events' => 0,
    'registrations' => 0,
];
$latestRegistrations = [];
$latestAuditLogs = [];

try {
    $connection = studenthub_database();
    $result = $connection->query(
        "SELECT COUNT(*) AS total_users,
                COALESCE(SUM(role = 'student'), 0) AS student_count,
                COALESCE(SUM(role = 'admin'), 0) AS admin_count
         FROM students"
    );
    $counts = $result->fetch_assoc();
    $result->free();
    $stats['users'] = (int) $counts['total_users'];
    $stats['students'] = (int) $counts['student_count'];
    $stats['admins'] = (int) $counts['admin_count'];

    $result = $connection->query('SELECT COUNT(*) AS total_events FROM events');
    $stats['events'] = (int) $result->fetch_assoc()['total_events'];
    $result->free();

    $result = $connection->query('SELECT COUNT(*) AS total_registrations FROM registrations');
    $stats['registrations'] = (int) $result->fetch_assoc()['total_registrations'];
    $result->free();

    $result = $connection->query(
        "SELECT s.full_name, s.email, e.title AS event_title, r.status, r.registered_at
         FROM registrations r
         INNER JOIN students s ON s.student_id = r.student_id
         INNER JOIN events e ON e.event_id = r.event_id
         ORDER BY r.registered_at DESC
         LIMIT 10"
    );
    $latestRegistrations = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();

    $result = $connection->query(
        "SELECT al.action, al.entity_type, al.entity_id, al.details, al.created_at,
                s.full_name AS actor_name
         FROM audit_logs al
         LEFT JOIN students s ON s.student_id = al.actor_id
         ORDER BY al.created_at DESC, al.audit_id DESC
         LIMIT 10"
    );
    $latestAuditLogs = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} catch (Throwable $error) {
    error_log('Admin dashboard query error: ' . $error->getMessage());
    $dashboardError = 'Dashboard data could not be loaded. Check the database setup and try again.';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard | Vidya Setu</title>
    <link rel="stylesheet" href="../css/index.css" />
    <link rel="stylesheet" href="../css/admin-dashboard.css" />
  </head>
  <body>
    <header class="header">
      <div class="container header-inner">
        <a class="logo" href="../index.html"><span class="logo-mark">VS</span><span><strong>Vidya Setu</strong><small>Administrator</small></span></a>
        <nav class="nav" aria-label="Administrator navigation">
          <a class="active" href="admin-dashboard.php">Dashboard</a>
          <a href="students.php">Manage students</a>
          <a href="events.php">Manage events</a>
          <a href="events-manager.php">Events (Fetch API)</a>
          <a href="../index.html">Student Hub home</a>
        </nav>
        <form action="logout.php" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
          <button type="submit">Log out</button>
        </form>
      </div>
    </header>

    <main class="admin-dashboard container">
      <header class="dashboard-heading">
        <div>
          <h1>Admin Dashboard</h1>
          <p>Welcome, <?php echo html_escape($user['name']); ?>. These figures are loaded from the live database.</p>
        </div>
        <span class="role-badge">Administrator</span>
      </header>

      <?php if ($dashboardError !== ''): ?>
        <p class="dashboard-message error" role="alert"><?php echo html_escape($dashboardError); ?></p>
      <?php endif; ?>

      <section class="stat-grid" aria-label="Portal statistics">
        <article class="stat-card"><span>Total users</span><strong><?php echo $stats['users']; ?></strong><small><?php echo $stats['admins']; ?> administrator(s)</small></article>
        <article class="stat-card"><span>Students</span><strong><?php echo $stats['students']; ?></strong><small>Student accounts</small></article>
        <article class="stat-card"><span>Events</span><strong><?php echo $stats['events']; ?></strong><small>Events in the portal</small></article>
        <article class="stat-card"><span>Registrations</span><strong><?php echo $stats['registrations']; ?></strong><small>Event registrations</small></article>
      </section>

      <section class="dashboard-card">
        <div class="section-heading">
          <div><h2>Latest registrations</h2><p>Most recently registered students and events.</p></div>
          <a href="events.php">Manage events</a>
        </div>
        <?php if ($latestRegistrations): ?>
          <div class="table-scroll">
            <table>
              <thead><tr><th>Student</th><th>Email</th><th>Event</th><th>Status</th><th>Registered</th></tr></thead>
              <tbody>
                <?php foreach ($latestRegistrations as $registration): ?>
                  <tr>
                    <td><?php echo html_escape($registration['full_name']); ?></td>
                    <td><?php echo html_escape($registration['email']); ?></td>
                    <td><?php echo html_escape($registration['event_title']); ?></td>
                    <td><?php echo html_escape(ucfirst($registration['status'])); ?></td>
                    <td><?php echo html_escape($registration['registered_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="empty-state">There are no event registrations yet.</p>
        <?php endif; ?>
      </section>

      <section class="dashboard-card">
        <div class="section-heading">
          <div><h2>Recent audit activity</h2><p>Important administrator actions recorded by the portal.</p></div>
          <a href="students.php">Manage students</a>
        </div>
        <?php if ($latestAuditLogs): ?>
          <div class="table-scroll">
            <table>
              <thead><tr><th>Administrator</th><th>Action</th><th>Record</th><th>Details</th><th>Time</th></tr></thead>
              <tbody>
                <?php foreach ($latestAuditLogs as $audit): ?>
                  <tr>
                    <td><?php echo html_escape($audit['actor_name'] ?: 'Account removed'); ?></td>
                    <td><?php echo html_escape(str_replace('_', ' ', ucfirst($audit['action']))); ?></td>
                    <td><?php echo html_escape(ucfirst($audit['entity_type'])); ?><?php echo $audit['entity_id'] === null ? '' : ' #' . (int) $audit['entity_id']; ?></td>
                    <td><?php echo html_escape($audit['details'] ?? ''); ?></td>
                    <td><?php echo html_escape($audit['created_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="empty-state">No audit activity has been recorded yet.</p>
        <?php endif; ?>
      </section>
    </main>
  </body>
</html>
