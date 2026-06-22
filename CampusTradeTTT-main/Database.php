<?php

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

loadEnvFile(__DIR__ . '/.env');

// Local XAMPP defaults are used if .env is missing or a value is blank.
$db_host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$db_user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$db_password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
$db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'campustrade';

// Make MySQLi throw exceptions so try/catch works.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli($db_host, $db_user, $db_password, $db_name);
    $db->set_charset('utf8mb4');
    return $db;
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed');
}
