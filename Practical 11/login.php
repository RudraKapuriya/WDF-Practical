<?php
require_once __DIR__ . '/php/data-store.php';

start_student_session();
$csrfToken = student_csrf_token();
$message = !empty($_GET['expired']) || !empty($_SESSION['session_expired'])
    ? 'Your session expired after 15 minutes of inactivity. Please sign in again.'
    : '';
unset($_SESSION['session_expired']);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | Vidya Setu Student Hub</title>
    <link rel="stylesheet" href="css/login.css" />
    <link rel="stylesheet" href="css/weather.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />
  </head>
  <body class="auth-page">
    <div class="auth-wrap">
      <div class="auth-brand">
        <a class="logo" href="index.html" style="justify-content: center"
          ><span class="logo-mark">VS</span
          ><span><strong>Vidya Setu</strong><small>Student Hub</small></span></a
        >
        <h1>Secure Login</h1>
        <p>Sign in to access your academic information.</p>
      </div>
      <form class="form-card" action="php/auth-login.php" method="post">
        <h2>Login to your account</h2>
        <?php if ($message !== ''): ?>
          <p role="status"><?php echo html_escape($message); ?></p>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>" />
        <label for="login-identifier">Username or email</label>
        <input
          id="login-identifier"
          name="identifier"
          type="text"
          autocomplete="username"
          maxlength="254"
          required
        />
        <label for="password">Password</label>
        <input
          id="password"
          name="password"
          type="password"
          autocomplete="current-password"
          required
        />
        <button class="btn btn-primary" style="width: 100%" type="submit">Login</button>
        <div class="auth-links" style="margin-top: 16px">
          <a href="forget-password.html">Forgot password?</a>
          <a href="registration.html">Create account</a>
        </div>
      </form>
    </div>
    <script src="js/login.js"></script>
    <script src="js/weather.js"></script>
  </body>
</html>
