<?php

function studenthub_database()
{
    static $connection = null;
    if ($connection instanceof PDO) {
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

    $dsn = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4';
    try {
        $connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        throw new RuntimeException('Could not connect to the StudentHub database.', 0, $exception);
    }

    return $connection;
}
