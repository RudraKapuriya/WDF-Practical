<?php
require_once __DIR__ . '/data-store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_response('Registration', 'Submit the registration form to create an account.', 'Go to registration', '../registration.html', 405);
    exit;
}

$name = clean_input($_POST['name'] ?? null, 80);
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
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
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
    append_json_record('registration.json', [
        'name' => $name,
        'email' => $email,
        'mobile' => $mobile,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'course' => $course,
        'year' => $year,
        'gender' => $gender,
        'registered_at' => date(DATE_ATOM),
    ]);
} catch (Throwable $error) {
    render_response('Registration could not be saved', 'The server could not save your details. Please try again later.', 'Return to registration', '../registration.html', 500);
    exit;
}

render_response('Registration successful', 'Your registration details were saved successfully.', 'Return to registration', '../registration.html');
