# StudentHub MySQL setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin, choose **Import**, and import `database/schema.sql`. It creates the isolated `studenthub_practical10` database and the `students`, `events`, and `registrations` tables. The `students` table has unique email and username constraints; new accounts receive the `student` role by default.
3. For a local school practical, you can use XAMPP's default MySQL account: `root` with a blank password. If you have set a root password, use that instead. This is for a local development setup only; do not expose this configuration to other machines.
4. Configure these environment variables for Apache, then restart Apache:

   - `STUDENTHUB_DB_HOST` (usually `127.0.0.1`)
   - `STUDENTHUB_DB_NAME` (`studenthub_practical10`)
   - `STUDENTHUB_DB_USER` (`root` for the default local XAMPP setup)
   - `STUDENTHUB_DB_PASSWORD` (leave blank if the XAMPP root account has no password)

   On XAMPP, Apache environment variables can be set in `apache/conf/httpd.conf` using `SetEnv VARIABLE value`. For the default local setup, use `SetEnv STUDENTHUB_DB_USER root` and `SetEnv STUDENTHUB_DB_PASSWORD ""` to explicitly set an empty password. Keep any configured password in local server configuration, not in this project.

5. Visit `http://localhost/Practical%2010/php/db-test.php`. The page reports connection success or a generic setup error without exposing credentials or server exception details.

Registration validates all fields on the server, checks for existing email addresses and usernames, hashes passwords with `password_hash()`, and inserts account details using MySQLi prepared statements. Database unique constraints prevent duplicates even if two registrations arrive at once.

Login accepts either username or email and verifies the stored hash with `password_verify()`. Successful logins rotate the session ID and send the user to the dashboard for their database role. Dashboards require the matching authenticated role; idle sessions expire after 15 minutes. Logout is a CSRF-protected POST that clears and destroys the session.

To grant an account administrator access, register it as a student first, then run this in phpMyAdmin after verifying the username:

```sql
USE studenthub_practical10;
UPDATE students SET role = 'admin' WHERE username = 'your_admin_username';
```

`php/db.php` creates a MySQLi connection using `utf8mb4`. Keep all SQL containing user-provided values in prepared statements. For production deployment, serve the site over HTTPS so the session cookie's `Secure` flag is enabled.
