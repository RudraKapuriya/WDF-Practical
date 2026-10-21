<?php
require_once __DIR__ . '/data-store.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_response('Registration', 'Submit the registration form to create an account.', 'Go to registration', '../registration.html', 405);
    exit;
}

$name = clean_input($_POST['name'] ?? null, 80);
$username = strtolower(clean_input($_POST['username'] ?? null, 30));
$email = strtolower(clean_input($_POST['email'] ?? null, 254));
$mobile = clean_input($_POST['mobile'] ?? null, 10);
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$confirmPassword = is_string($_POST['confirmPassword'] ?? null) ? $_POST['confirmPassword'] : '';
$course = clean_input($_POST['course'] ?? null, 20);
$year = clean_input($_POST['year'] ?? null, 1);
$gender = clean_input($_POST['gender'] ?? null, 30);
$validCourses = ['BCA', 'BBA', 'BSc IT'];
$validGenders = ['female', 'male', 'other', 'prefer-not-to-say'];

if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,79}$/", $name)
    || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)
    || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $email)
    || !preg_match('/^[6-9][0-9]{9}$/', $mobile)
    || strlen($password) < 8
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[0-9]/', $password)
    || !preg_match('/[^A-Za-z0-9]/', $password)
    || $password !== $confirmPassword
    || !in_array($course, $validCourses, true)
    || !in_array($year, ['1', '2', '3', '4'], true)
    || !in_array($gender, $validGenders, true)
    || ($_POST['terms'] ?? '') !== 'on') {
    render_response('Registration not submitted', 'Check each field and try again. Passwords must match and meet the displayed requirements.', 'Return to registration', '../registration.html', 422);
    exit;
}

try {
    $connection = studenthub_database();
    $duplicateCheck = $connection->prepare(
        'SELECT student_id FROM students WHERE email = ? OR username = ? LIMIT 1'
    );
    $duplicateCheck->bind_param('ss', $email, $username);
    $duplicateCheck->execute();
    $duplicateCheck->store_result();
    $isDuplicate = $duplicateCheck->num_rows > 0;
    $duplicateCheck->close();

    if ($isDuplicate) {
        render_response(
            'Registration not submitted',
            'That email address or username is already registered. Please use different details.',
            'Return to registration',
            '../registration.html',
            409
        );
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $yearOfStudy = (int) $year;
    $insert = $connection->prepare(
        'INSERT INTO students (full_name, username, email, mobile, password_hash, course, year_of_study, gender)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insert->bind_param(
        'ssssssis',
        $name,
        $username,
        $email,
        $mobile,
        $passwordHash,
        $course,
        $yearOfStudy,
        $gender
    );
    $insert->execute();
    $insert->close();
} catch (mysqli_sql_exception $error) {
    if ((int) $error->getCode() === 1062) {
        render_response(
            'Registration not submitted',
            'That email address or username is already registered. Please use different details.',
            'Return to registration',
            '../registration.html',
            409
        );
        exit;
    }
    error_log('Student registration database error: ' . $error->getMessage());
    render_response('Registration could not be saved', 'The server could not save your details. Please try again later.', 'Return to registration', '../registration.html', 500);
    exit;
} catch (Throwable $error) {
    error_log('Student registration error: ' . $error->getMessage());
    render_response('Registration could not be saved', 'The server could not save your details. Please try again later.', 'Return to registration', '../registration.html', 500);
    exit;
}

render_response('Registration successful', 'Your account was created successfully and your details were saved securely.', 'Return to registration', '../registration.html');
