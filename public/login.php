<?php
/**
 * Login page
 */
require_once __DIR__ . '/../src/bootstrap.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        redirectWithError('login.php', 'Username and password are required');
    }
    
    // Attempt login
    if ($authService->login($username, $password)) {
        // Redirect to dashboard or previous page
        $redirect = $_SESSION['redirect_after_login'] ?? '/index.php';
        unset($_SESSION['redirect_after_login']);
        header("Location: $redirect");
        exit;
    } else {
        // Add a session variable to trigger browser console log
        $_SESSION['login_attempt'] = [
            'status' => 'failed',
            'username' => $username,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        redirectWithError('login.php', 'Invalid username or password');
    }
}

// Check if already logged in
if ($authService->isAuthenticated()) {
    header("Location: /dashboard/public/index.php");
    exit;
}

// Get flash messages
$messages = getFlashMessages();

// Prepare login attempt data for JavaScript logging
$loginAttempt = $_SESSION['login_attempt'] ?? null;
unset($_SESSION['login_attempt']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WooCommerce Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #111827;
            color: #e5e7eb;
        }
        .dark-card {
            background-color: #1f2937;
            border: 1px solid #374151;
        }
        .dark-input {
            background-color: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }
        .dark-input:focus {
            border-color: #60a5fa;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="dark-card rounded-lg shadow-lg p-8 w-full max-w-md">
            <h1 class="text-2xl font-bold mb-6 text-center">Dashboard Login</h1>
            
            <?php if (!empty($messages['error'])): ?>
                <div class="bg-red-800 text-white p-4 rounded-md mb-6">
                    <?= htmlspecialchars($messages['error']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($messages['success'])): ?>
                <div class="bg-green-800 text-white p-4 rounded-md mb-6">
                    <?= htmlspecialchars($messages['success']) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" id="loginForm">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="dark-input w-full rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           required>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="dark-input w-full rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           required>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Login
                </button>
            </form>
        </div>
    </div>

    <script>
        // Log login-related information to browser console
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($loginAttempt): ?>
                console.group('Login Attempt');
                console.log('Status: <?= $loginAttempt['status'] ?>');
                console.log('Username: <?= $loginAttempt['username'] ?>');
                console.log('Timestamp: <?= $loginAttempt['timestamp'] ?>');
                console.groupEnd();
            <?php endif; ?>

            // Optional: Add client-side validation logging
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;

                console.group('Login Form Submission');
                console.log('Username entered: ' + username);
                console.log('Password length: ' + password.length);
                console.groupEnd();
            });
        });
    </script>
</body>
</html>