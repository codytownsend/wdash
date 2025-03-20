<?php
require_once __DIR__ . '/../vendor/autoload.php';

echo "Autoloader included successfully.\n";

try {
    $authService = new \App\Auth\AuthService([]);
    echo "AuthService class instantiated successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
