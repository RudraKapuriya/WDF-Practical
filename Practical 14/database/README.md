# StudentHub MySQL setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin, choose **Import**, and import `database/schema.sql`. It creates the isolated `studenthub_practical14` database and the `students`, `events`, `registrations`, and `audit_logs` tables. The `students` table has unique email and username constraints; new accounts receive the `student` role by default.
3. For a local school practical, you can use XAMPP's default MySQL account: `root` with a blank password. If you have set a root password, use that instead. This is for a local development setup only; do not expose this configuration to other machines.
4. Configure these environment variables for Apache, then restart Apache:

   - `STUDENTHUB_DB_HOST` (usually `127.0.0.1`)
   - `STUDENTHUB_DB_NAME` (`studenthub_practical14`)
   - `STUDENTHUB_DB_USER` (`root` for the default local XAMPP setup)
   - `STUDENTHUB_DB_PASSWORD` (leave blank if the XAMPP root account has no password)

   On XAMPP, Apache environment variables can be set in `apache/conf/httpd.conf` using `SetEnv VARIABLE value`. For the default local setup, use `SetEnv STUDENTHUB_DB_USER root` and `SetEnv STUDENTHUB_DB_PASSWORD ""` to explicitly set an empty password. Keep any configured password in local server configuration, not in this project.

5. Visit `http://localhost/Practical%2014/php/db-test.php`. The page reports connection success or a generic setup error without exposing credentials or server exception details.

Registration validates all fields on the server, checks for existing email addresses and usernames, hashes passwords with `password_hash()`, and inserts account details using MySQLi prepared statements. Database unique constraints prevent duplicates even if two registrations arrive at once.

Login accepts either username or email and verifies the stored hash with `password_verify()`. Successful logins rotate the session ID and send the user to the dashboard for their database role. Dashboards require the matching authenticated role; idle sessions expire after 15 minutes. Logout is a CSRF-protected POST that clears and destroys the session.

Practical 14 uses its own PHP session cookie name so its login sessions are separate from the previous practical projects.

To grant an account administrator access, register it as a student first, then run this in phpMyAdmin after verifying the username:

```sql
USE studenthub_practical14;
UPDATE students SET role = 'admin' WHERE username = 'your_admin_username';
```

`php/db.php` creates a MySQLi connection using `utf8mb4`. Keep all SQL containing user-provided values in prepared statements. For production deployment, serve the site over HTTPS so the session cookie's `Secure` flag is enabled.

Administrators can manage accounts at `http://localhost/Practical%2014/php/students.php`. The page supports adding, viewing, editing, deleting, searching by name/email/course, and filtering by course, year, or role. All record changes use MySQLi prepared statements, require the admin session and CSRF token, and deletion requires browser confirmation. Deleting a student also deletes their related event registrations through the schema's foreign key.

Administrators can manage events at `http://localhost/Practical%2014/php/events.php` or use the Fetch-powered interface at `http://localhost/Practical%2014/php/events-manager.php`. Both interfaces support creating, viewing, editing, and deleting events with prepared MySQLi statements. A poster is required when creating an event; supported image types are JPEG, PNG, and WebP with a maximum size of 5 MB. Replacing a poster during edit is optional. Set PHP's `upload_max_filesize` to at least `5M` and `post_max_size` above `5M` so PHP accepts files within the application's limit. Uploaded files use randomized names in `uploads/events`, and the directory blocks script execution. Event deletion also removes related registrations and the poster file.

The Fetch interface uses `php/events-api.php`: `GET` lists events or fetches one event, and `POST` with an `action` of `create`, `update`, or `delete` performs changes. Responses are JSON with `success`, `message`, and `data` fields. Mutations require the admin session and CSRF token.

The admin dashboard at `http://localhost/Practical%2014/php/admin-dashboard.php` displays live user, student, event, and registration counts, recent registrations, and recent audit activity. It and its navigation are restricted to administrator sessions; the student dashboard has student-specific navigation. Administrator sign-ins/outs and student/event create, update, and delete operations are recorded in `audit_logs`. Import the full schema when setting up this practical so the audit table exists.
