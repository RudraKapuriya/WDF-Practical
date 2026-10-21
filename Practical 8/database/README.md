# StudentHub MySQL setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin, choose **Import**, and import `database/schema.sql`. It creates the `studenthub` database and the normalized `students`, `events`, and `registrations` tables.
   If you imported the previous schema already, run this once in the `studenthub` database using phpMyAdmin's **SQL** tab to allow duplicate student emails: `ALTER TABLE students DROP INDEX uq_students_email;`
3. For a local school practical, you can use XAMPP's default MySQL account: `root` with a blank password. If you have set a root password, use that instead. This is for a local development setup only; do not expose this configuration to other machines.
4. Configure these environment variables for Apache, then restart Apache:

   - `STUDENTHUB_DB_HOST` (usually `127.0.0.1`)
   - `STUDENTHUB_DB_NAME` (`studenthub`)
   - `STUDENTHUB_DB_USER` (`root` for the default local XAMPP setup)
   - `STUDENTHUB_DB_PASSWORD` (leave blank if the XAMPP root account has no password)

   On XAMPP, Apache environment variables can be set in `apache/conf/httpd.conf` using `SetEnv VARIABLE value`. For the default local setup, use `SetEnv STUDENTHUB_DB_USER root` and `SetEnv STUDENTHUB_DB_PASSWORD ""` to explicitly set an empty password. Keep any configured password in local server configuration, not in this project.

5. Visit `http://localhost/Practical%208/php/db-test.php`. The page reports connection success or a generic setup error without exposing credentials or server exception details.

`php/db.php` creates a PDO MySQL connection with `utf8mb4`, exception mode, associative fetches, and native prepared statements. Use prepared statements for all SQL queries that include user-provided values.
