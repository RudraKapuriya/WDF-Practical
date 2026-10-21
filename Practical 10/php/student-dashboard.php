<?php
require_once __DIR__ . '/data-store.php';
require_student_role('student');
$user = $_SESSION['user'];
$csrfToken = student_csrf_token();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Dashboard | Vidya Setu</title>
    <link rel="stylesheet" href="../css/index.css" />
  </head>
  <body>
    <main>
      <h1>Student Dashboard</h1>
      <p>Welcome, <?php echo html_escape($user['name']); ?>.</p>
      <p>You are signed in as a student.</p>
      <a href="../index.html">Student Hub home</a>
      <form action="logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
        <button type="submit">Log out</button>
      </form>
    </main>
  </body>
</html>
