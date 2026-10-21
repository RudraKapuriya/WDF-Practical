<?php
require_once __DIR__ . '/data-store.php';
require_student_role('admin');
require_once __DIR__ . '/db.php';

$courses = ['BCA', 'BBA', 'BSc IT'];
$roles = ['student', 'admin'];

function student_management_error($message, $statusCode = 422)
{
    render_response('Student management', $message, 'Return to student management', 'students.php', $statusCode);
    exit;
}

function validate_managed_student()
{
    $student = [
        'name' => clean_input($_POST['name'] ?? null, 80),
        'username' => strtolower(clean_input($_POST['username'] ?? null, 30)),
        'email' => strtolower(clean_input($_POST['email'] ?? null, 254)),
        'mobile' => clean_input($_POST['mobile'] ?? null, 10),
        'course' => clean_input($_POST['course'] ?? null, 20),
        'year' => clean_input($_POST['year'] ?? null, 1),
        'gender' => clean_input($_POST['gender'] ?? null, 30),
        'role' => clean_input($_POST['role'] ?? null, 10),
    ];

    if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,79}$/", $student['name'])
        || !preg_match('/^[A-Za-z0-9_]{3,30}$/', $student['username'])
        || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $student['email'])
        || !preg_match('/^[6-9][0-9]{9}$/', $student['mobile'])
        || !in_array($student['course'], ['BCA', 'BBA', 'BSc IT'], true)
        || !in_array($student['year'], ['1', '2', '3', '4'], true)
        || !in_array($student['gender'], ['female', 'male', 'other', 'prefer-not-to-say'], true)
        || !in_array($student['role'], ['student', 'admin'], true)) {
        student_management_error('Check each field and enter valid student details.');
    }

    return $student;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_student_csrf_token($_POST['csrf_token'] ?? null)) {
        student_management_error('The request could not be verified. Refresh the page and try again.', 403);
    }

    $action = clean_input($_POST['action'] ?? null, 10);
    try {
        $connection = studenthub_database();

        if ($action === 'delete') {
            $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($studentId === false || $studentId === null) {
                student_management_error('Select a valid student record.');
            }
            if ((int) $studentId === (int) $_SESSION['user']['id']) {
                student_management_error('You cannot delete your own administrator account.', 409);
            }

            $delete = $connection->prepare('DELETE FROM students WHERE student_id = ?');
            $delete->bind_param('i', $studentId);
            $delete->execute();
            $deleted = $delete->affected_rows;
            $delete->close();
            if ($deleted !== 1) {
                student_management_error('Student record was not found.', 404);
            }
            header('Location: students.php?status=deleted', true, 303);
            exit;
        }

        if (!in_array($action, ['create', 'update'], true)) {
            student_management_error('Choose a valid student management action.');
        }

        $student = validate_managed_student();
        if ($action === 'create') {
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if (strlen($password) < 8
                || !preg_match('/[A-Z]/', $password)
                || !preg_match('/[a-z]/', $password)
                || !preg_match('/[0-9]/', $password)
                || !preg_match('/[^A-Za-z0-9]/', $password)) {
                student_management_error('New student passwords must be at least 8 characters and include uppercase, lowercase, number, and symbol.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $year = (int) $student['year'];
            $insert = $connection->prepare(
                'INSERT INTO students (full_name, username, email, mobile, password_hash, course, year_of_study, gender, role)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->bind_param(
                'ssssssiss',
                $student['name'],
                $student['username'],
                $student['email'],
                $student['mobile'],
                $passwordHash,
                $student['course'],
                $year,
                $student['gender'],
                $student['role']
            );
            $insert->execute();
            $insert->close();
            header('Location: students.php?status=created', true, 303);
            exit;
        }

        $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($studentId === false || $studentId === null) {
            student_management_error('Select a valid student record.');
        }
        if ((int) $studentId === (int) $_SESSION['user']['id'] && $student['role'] !== 'admin') {
            student_management_error('You cannot remove your own administrator role.', 409);
        }

        $year = (int) $student['year'];
        $update = $connection->prepare(
            'UPDATE students
             SET full_name = ?, username = ?, email = ?, mobile = ?, course = ?,
                 year_of_study = ?, gender = ?, role = ?
             WHERE student_id = ?'
        );
        $update->bind_param(
            'sssssissi',
            $student['name'],
            $student['username'],
            $student['email'],
            $student['mobile'],
            $student['course'],
            $year,
            $student['gender'],
            $student['role'],
            $studentId
        );
        $update->execute();
        $updated = $update->affected_rows;
        $update->close();
        if ($updated === 0) {
            $check = $connection->prepare('SELECT student_id FROM students WHERE student_id = ?');
            $check->bind_param('i', $studentId);
            $check->execute();
            $check->store_result();
            $exists = $check->num_rows === 1;
            $check->close();
            if (!$exists) {
                student_management_error('Student record was not found.', 404);
            }
        }
        header('Location: students.php?status=updated', true, 303);
        exit;
    } catch (mysqli_sql_exception $error) {
        if ((int) $error->getCode() === 1062) {
            student_management_error('That username or email address is already assigned to another account.', 409);
        }
        error_log('Student management database error: ' . $error->getMessage());
        student_management_error('The student record could not be saved. Please try again later.', 500);
    } catch (Throwable $error) {
        error_log('Student management error: ' . $error->getMessage());
        student_management_error('The student record could not be saved. Please try again later.', 500);
    }
}

$statusMessages = [
    'created' => 'Student record added successfully.',
    'updated' => 'Student record updated successfully.',
    'deleted' => 'Student record deleted successfully.',
];
$status = clean_input($_GET['status'] ?? null, 20);
$statusMessage = $statusMessages[$status] ?? '';
$filters = [
    'search' => clean_input($_GET['search'] ?? null, 100),
    'course' => clean_input($_GET['course'] ?? null, 20),
    'year' => clean_input($_GET['year'] ?? null, 1),
    'role' => clean_input($_GET['role'] ?? null, 10),
];
if (!in_array($filters['course'], $courses, true)) {
    $filters['course'] = '';
}
if (!in_array($filters['year'], ['1', '2', '3', '4'], true)) {
    $filters['year'] = '';
}
if (!in_array($filters['role'], $roles, true)) {
    $filters['role'] = '';
}

$editingStudent = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
try {
    $connection = studenthub_database();
    if ($editId !== false && $editId !== null) {
        $editQuery = $connection->prepare(
            'SELECT student_id, full_name, username, email, mobile, course, year_of_study, gender, role
             FROM students WHERE student_id = ?'
        );
        $editQuery->bind_param('i', $editId);
        $editQuery->execute();
        $editingStudent = $editQuery->get_result()->fetch_assoc();
        $editQuery->close();
        if (!$editingStudent) {
            student_management_error('Student record was not found.', 404);
        }
    }

    $searchLike = '%' . $filters['search'] . '%';
    $listing = $connection->prepare(
        "SELECT student_id, full_name, username, email, mobile, course, year_of_study, gender, role
         FROM students
         WHERE (? = '' OR full_name LIKE ? OR email LIKE ? OR course LIKE ?)
           AND (? = '' OR course = ?)
           AND (? = '' OR CAST(year_of_study AS CHAR) = ?)
           AND (? = '' OR role = ?)
         ORDER BY student_id DESC"
    );
    $listing->bind_param(
        'ssssssssss',
        $filters['search'],
        $searchLike,
        $searchLike,
        $searchLike,
        $filters['course'],
        $filters['course'],
        $filters['year'],
        $filters['year'],
        $filters['role'],
        $filters['role']
    );
    $listing->execute();
    $students = $listing->get_result()->fetch_all(MYSQLI_ASSOC);
    $listing->close();
} catch (Throwable $error) {
    error_log('Student management query error: ' . $error->getMessage());
    student_management_error('Student records could not be loaded. Check the database setup and try again.', 500);
}

$csrfToken = student_csrf_token();
$formStudent = $editingStudent ?: [
    'student_id' => '',
    'full_name' => '',
    'username' => '',
    'email' => '',
    'mobile' => '',
    'course' => '',
    'year_of_study' => '',
    'gender' => '',
    'role' => 'student',
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Management | Vidya Setu</title>
    <link rel="stylesheet" href="../css/students.css" />
  </head>
  <body>
    <main class="management">
      <header class="management-header">
        <div>
          <h1>Student Management</h1>
          <p class="muted">Signed in as <?php echo html_escape($_SESSION['user']['name']); ?> (administrator)</p>
        </div>
        <div class="management-actions">
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

      <section class="management-card">
        <h2><?php echo $editingStudent ? 'Edit student record' : 'Add student record'; ?></h2>
        <form method="post" action="students.php">
          <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
          <input type="hidden" name="action" value="<?php echo $editingStudent ? 'update' : 'create'; ?>" />
          <?php if ($editingStudent): ?>
            <input type="hidden" name="student_id" value="<?php echo (int) $formStudent['student_id']; ?>" />
          <?php endif; ?>
          <div class="management-grid">
            <div>
              <label for="name">Full name</label>
              <input id="name" name="name" minlength="2" maxlength="80" pattern="[A-Za-z][A-Za-z .'-]{1,79}" required value="<?php echo html_escape($formStudent['full_name']); ?>" />
            </div>
            <div>
              <label for="username">Username</label>
              <input id="username" name="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" required value="<?php echo html_escape($formStudent['username']); ?>" />
            </div>
            <div>
              <label for="email">Email</label>
              <input id="email" name="email" type="email" maxlength="254" required value="<?php echo html_escape($formStudent['email']); ?>" />
            </div>
            <div>
              <label for="mobile">Mobile number</label>
              <input id="mobile" name="mobile" inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" required value="<?php echo html_escape($formStudent['mobile']); ?>" />
            </div>
            <div>
              <label for="course">Course</label>
              <select id="course" name="course" required>
                <option value="">Select course</option>
                <?php foreach ($courses as $course): ?>
                  <option value="<?php echo html_escape($course); ?>" <?php echo $formStudent['course'] === $course ? 'selected' : ''; ?>><?php echo html_escape($course); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="year">Year of study</label>
              <select id="year" name="year" required>
                <option value="">Select year</option>
                <?php for ($year = 1; $year <= 4; $year++): ?>
                  <option value="<?php echo $year; ?>" <?php echo (string) $formStudent['year_of_study'] === (string) $year ? 'selected' : ''; ?>>Year <?php echo $year; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div>
              <label for="gender">Gender</label>
              <select id="gender" name="gender" required>
                <option value="">Select option</option>
                <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other', 'prefer-not-to-say' => 'Prefer not to say'] as $genderValue => $genderLabel): ?>
                  <option value="<?php echo html_escape($genderValue); ?>" <?php echo $formStudent['gender'] === $genderValue ? 'selected' : ''; ?>><?php echo html_escape($genderLabel); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="role">Account role</label>
              <select id="role" name="role" required>
                <option value="student" <?php echo $formStudent['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                <option value="admin" <?php echo $formStudent['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
              </select>
            </div>
            <?php if (!$editingStudent): ?>
              <div>
                <label for="password">Temporary password</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required />
              </div>
            <?php endif; ?>
          </div>
          <button type="submit"><?php echo $editingStudent ? 'Save changes' : 'Add student'; ?></button>
          <?php if ($editingStudent): ?>
            <a class="button-link button-secondary" href="students.php">Cancel edit</a>
          <?php endif; ?>
          <?php if (!$editingStudent): ?>
            <p class="muted">Password must be 8+ characters and contain uppercase, lowercase, number, and symbol.</p>
          <?php endif; ?>
        </form>
      </section>

      <section class="management-card">
        <h2>Student records (<?php echo count($students); ?>)</h2>
        <form class="management-toolbar" method="get" action="students.php">
          <div class="filter-search">
            <label for="search">Search name, email, or course</label>
            <input id="search" name="search" type="search" maxlength="100" value="<?php echo html_escape($filters['search']); ?>" placeholder="Type a name, email, or course" />
          </div>
          <div>
            <label for="filter-course">Course</label>
            <select id="filter-course" name="course">
              <option value="">All courses</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo html_escape($course); ?>" <?php echo $filters['course'] === $course ? 'selected' : ''; ?>><?php echo html_escape($course); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="filter-year">Year</label>
            <select id="filter-year" name="year">
              <option value="">All years</option>
              <?php for ($year = 1; $year <= 4; $year++): ?>
                <option value="<?php echo $year; ?>" <?php echo $filters['year'] === (string) $year ? 'selected' : ''; ?>>Year <?php echo $year; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label for="filter-role">Role</label>
            <select id="filter-role" name="role">
              <option value="">All roles</option>
              <option value="student" <?php echo $filters['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
              <option value="admin" <?php echo $filters['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
            </select>
          </div>
          <button type="submit">Search / filter</button>
          <a class="button-link button-secondary" href="students.php">Clear</a>
        </form>
        <?php if ($students): ?>
          <table>
            <thead>
              <tr><th>ID</th><th>Name / username</th><th>Email</th><th>Mobile</th><th>Course / year</th><th>Role</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><?php echo (int) $student['student_id']; ?></td>
                  <td><?php echo html_escape($student['full_name']); ?><br /><span class="muted">@<?php echo html_escape($student['username']); ?></span></td>
                  <td><?php echo html_escape($student['email']); ?></td>
                  <td><?php echo html_escape($student['mobile']); ?></td>
                  <td><?php echo html_escape($student['course']); ?><br /><span class="muted">Year <?php echo (int) $student['year_of_study']; ?></span></td>
                  <td><?php echo html_escape(ucfirst($student['role'])); ?></td>
                  <td class="actions">
                    <a class="button-link button-secondary" href="students.php?edit=<?php echo (int) $student['student_id']; ?>">Edit</a>
                    <?php if ((int) $student['student_id'] !== (int) $_SESSION['user']['id']): ?>
                      <form class="inline" method="post" action="students.php" onsubmit="return confirm('Delete this student record? This also removes their event registrations.');">
                        <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="student_id" value="<?php echo (int) $student['student_id']; ?>" />
                        <button class="button-danger" type="submit">Delete</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">No student records match these filters.</p>
        <?php endif; ?>
      </section>
    </main>
  </body>
</html>
