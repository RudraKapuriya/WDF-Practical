<?php

function studenthub_database()
{
    static $connection = null;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    $host = getenv('STUDENTHUB_DB_HOST');
    $database = getenv('STUDENTHUB_DB_NAME');
    $username = getenv('STUDENTHUB_DB_USER');
    $password = getenv('STUDENTHUB_DB_PASSWORD');

    if ($host === false || $database === false || $username === false || $password === false
        || $host === '' || $database === '' || $username === '') {
        throw new RuntimeException('Database connection settings are missing.');
    }

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = new mysqli($host, $username, $password, $database);
        $connection->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $exception) {
        throw new RuntimeException('Could not connect to the StudentHub database.', 0, $exception);
    }

    return $connection;
}
