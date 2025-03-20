<?php
// Show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting test...<br>";

// Try direct include
if (file_exists(__DIR__ . '/../src/Auth/AuthService.php')) {
    echo "File exists at expected location<br>";
    include_once __DIR__ . '/../src/Auth/AuthService.php';
    echo "File included directly<br>";
    
    if (class_exists('App\\Auth\\AuthService')) {
        echo "Class found with direct include<br>";
    } else {
        echo "Class NOT found even with direct include<br>";
    }
} else {
    echo "File DOES NOT exist at expected location!<br>";
}

// Try autoloader
echo "Testing autoloader...<br>";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Autoloader file exists<br>";
    include_once __DIR__ . '/../vendor/autoload.php';
    echo "Autoloader included<br>";
    
    if (class_exists('App\\Auth\\AuthService')) {
        echo "Class found via autoloader<br>";
    } else {
        echo "Class NOT found via autoloader<br>";
    }
} else {
    echo "Autoloader file DOES NOT exist!<br>";
}

echo "Test complete.";
