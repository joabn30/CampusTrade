<?php
// -------- LOCAL (fallback to each developer’s own XAMPP) --------
$db_host  = '127.0.0.1';
$db_user  = 'root';
$db_password = '';
$db_name   = 'campustrade';

// Make MySQLi throw exceptions so try/catch works
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    //Connect to database
    $db = new mysqli($db_host, $db_user, $db_password, $db_name);
    $db->set_charset('utf8mb4');
    return $db;

} catch (mysqli_sql_exception $e) {

    // error_log('DB connect failed. Shared: '.$e1->getMessage().;
    http_response_code(500);
    exit('Database connection failed');
    
}
