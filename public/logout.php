<?php
/**
 * Logout page
 */
require_once __DIR__ . '/../src/bootstrap.php';

// Log out the user
$authService->logout();

// Redirect to login page with success message
redirectWithSuccess('/login.php', 'You have been successfully logged out');