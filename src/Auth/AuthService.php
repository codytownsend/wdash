<?php
namespace App\Auth;

class AuthService {
    private array $config;
    private string $logFile;
    
    /**
     * Constructor
     * 
     * @param array $config Application configuration
     */
    public function __construct(array $config) {
        $this->config = $config;
        
        // Set up a log file in your project directory
        $this->logFile = __DIR__ . '/../../logs/auth.log';
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            $this->startSecureSession();
        }
    }
    
    /**
     * Custom logging method
     */
    private function log(string $message): void {
        $logMessage = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Start a secure session
     */
    private function startSecureSession(): void {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Use secure cookies in production
        if ($this->config['app']['env'] === 'production') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
    }
    
    /**
     * Check if the user is authenticated
     * 
     * @return bool Whether the user is authenticated
     */
    public function isAuthenticated(): bool {
        return isset($_SESSION['user_id']) && 
               $_SESSION['user_id'] === 1 && 
               isset($_SESSION['user_role']) && 
               $_SESSION['user_role'] === 'admin';
    }

    public function verifyPasswordHash(string $password, string $storedHash): void {
        $this->log("Password Verification Debug:");
        $this->log("Input Password: $password");
        $this->log("Stored Hash: $storedHash");
        $this->log("Direct Verify Result: " . (password_verify($password, $storedHash) ? 'Success' : 'Failed'));
        $this->log("New Hash for this password: " . password_hash($password, PASSWORD_BCRYPT));
    }

    /**
     * Require authentication or redirect to login
     * 
     * @param string $redirect URL to redirect to after login
     */
    public function requireAuth(string $redirect = ''): void {
        if (!$this->isAuthenticated()) {
            // Store the requested URL for redirection after login
            $_SESSION['redirect_after_login'] = $redirect ?: $_SERVER['REQUEST_URI'];
            
            // Redirect to login page
            header('Location: /login.php');
            exit;
        }
    }
    
    /**
     * Authenticate a user
     * 
     * @param string $username Username
     * @param string $password Password
     * @return bool Whether authentication was successful
     */
    public function login(string $username, string $password): bool {
        // Detailed debug logging
        $this->log("Login Attempt Debugging:");
        $this->log("Provided Username: " . $username);
        $this->log("Provided Password Length: " . strlen($password));

        $this->verifyPasswordHash($password, $this->config['auth']['admin_password_hash']);
        
        // Get username from config
        $expectedUsername = $this->config['auth']['admin_username'] ?? null;
        
        $this->log("Expected Username: " . ($expectedUsername ?? 'Not Set'));
        
        // Validate username
        if ($username !== $expectedUsername) {
            $this->log("Username Mismatch");
            return false;
        }
        
        // Get stored password hash
        $storedHash = $this->config['auth']['admin_password_hash'] ?? null;
        
        $this->log("Stored Hash: " . ($storedHash ?? 'Not Set'));
        
        // Verify password
        $passwordVerified = password_verify($password, $storedHash);
        $this->log("Password Verification Result: " . ($passwordVerified ? 'Success' : 'Failed'));
        
        // Authenticate if password is correct
        if ($passwordVerified) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['login_time'] = time();
            
            $this->log("Login Successful for user: " . $username);
            return true;
        }
        
        $this->log("Login Failed for user: " . $username);
        return false;
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data or null if not authenticated
     */
    public function getCurrentUser(): ?array {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }

    /**
     * Log out the current user
     */
    public function logout(): void {
        // Log logout attempt
        error_log("User Logout: " . ($_SESSION['username'] ?? 'Unknown User'));
        
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Utility method to generate a password hash
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}