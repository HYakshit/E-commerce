<?php
// Database connection configuration
// This file is designed to work with both PostgreSQL (on Replit) and MySQL (on XAMPP)

// Check if we're on Replit by looking for Replit's PostgreSQL environment variables
if (getenv('PGHOST')) {
    // We're on Replit - use PostgreSQL
    $host = getenv('PGHOST');
    $port = getenv('PGPORT'); 
    $dbname = getenv('PGDATABASE');
    $username = getenv('PGUSER');
    $password = getenv('PGPASSWORD');
    
    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
        $conn = new PDO($dsn);
        $db_type = 'postgresql';
    } catch(PDOException $e) {
        die("PostgreSQL connection failed: " . $e->getMessage());
    }
} else {
    // We're not on Replit - use MySQL (for XAMPP)
    $host = 'localhost';
    $dbname = 'shopdb';
    $username = 'root';
    $password = ''; // Default XAMPP MySQL password is empty
    
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password);
        $db_type = 'mysql';
    } catch(PDOException $e) {
        die("MySQL connection failed: " . $e->getMessage());
    }
}

// Common configuration for both database types
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Define a function to help with boolean values in different databases
function dbBool($value) {
    global $db_type;
    if ($db_type == 'postgresql') {
        return $value ? 'TRUE' : 'FALSE';
    } else {
        return $value ? '1' : '0';
    }
}

// Define a function to handle "RETURNING" syntax differences
function getLastInsertId($conn, $table, $id_column = 'id') {
    global $db_type;
    if ($db_type == 'postgresql') {
        // PostgreSQL can use RETURNING clause
        return $conn->lastInsertId();
    } else {
        // MySQL needs to use lastInsertId() function
        return $conn->lastInsertId();
    }
}
?>
