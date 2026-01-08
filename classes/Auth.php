<?php
/**
 * Barron Production Management System
 * Authentication Class
 */

class Auth {
    private $conn;
    private $database;
    
    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
    }
    
    /**
     * User login
     */
    public function login($username, $password) {
        try {
            // Check login attempts
            $this->checkLoginAttempts($username);
            
            // Get user details from users table with roles
            $query = "SELECT u.*, r.role_code, r.role_name
                      FROM users u
                      LEFT JOIN user_roles ur ON u.id = ur.user_id
                      LEFT JOIN roles r ON ur.role_id = r.id
                      WHERE (u.username = :username OR u.email = :email) 
                      AND u.status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':username' => $username, ':email' => $username]);
            
            if ($stmt->rowCount() == 0) {
                $this->recordFailedAttempt($username);
                throw new Exception('Invalid username or password');
            }
            
            $user = $stmt->fetch();
            
            // Debug logging
            error_log("Login attempt - User found: " . $user['username']);
            error_log("Password hash from DB: " . $user['password']);
            error_log("Verifying password...");
            
            // Verify password
            $passwordMatch = password_verify($password, $user['password']);
            error_log("Password match result: " . ($passwordMatch ? 'true' : 'false'));
            
            if (!$passwordMatch) {
                $this->recordFailedAttempt($username);
                throw new Exception('Invalid username or password');
            }
            
            // Clear failed attempts
            $this->clearFailedAttempts($username);
            
            // Get user permissions
            $permissions = $this->getUserPermissions($user['id']);
            
            // Create session
            $this->createSession($user, $permissions);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Log activity
            logActivity('insert', 'sessions', session_id(), null, ['user_id' => $user['id']]);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'role' => $user['role_name'],
                    'department' => $user['department_name']
                ]
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * User logout
     */
    public function logout() {
        try {
            if (isset($_SESSION['user_id'])) {
                // Delete session from database
                $query = "DELETE FROM sessions WHERE id = :session_id";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([':session_id' => session_id()]);
                
                // Log activity
                logActivity('delete', 'sessions', session_id(), ['user_id' => $_SESSION['user_id']], null);
            }
            
            // Clear session
            $_SESSION = [];
            session_destroy();
            
            return ['success' => true, 'message' => 'Logged out successfully'];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $timeout = $this->getSessionTimeout();
            if (time() - $_SESSION['last_activity'] > $timeout) {
                $this->logout();
                return false;
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get session timeout based on role
     */
    private function getSessionTimeout() {
        if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['OPERATOR', 'APPLIQUE_CUTTER', 'PACKER'])) {
            return OPERATOR_SESSION_LIFETIME;
        }
        return SESSION_LIFETIME;
    }
    
    /**
     * Create user session
     */
    private function createSession($user, $permissions) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['employee_number'] = $user['employee_number'];
        $_SESSION['user_role'] = $user['role_code'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['department_name'] = $user['department_name'];
        $_SESSION['user_permissions'] = $permissions;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
        
        // Store session in database
        $this->saveSessionToDatabase();
    }
    
    /**
     * Save session to database
     */
    private function saveSessionToDatabase() {
        try {
            $session_data = json_encode([
                'username' => $_SESSION['username'],
                'role' => $_SESSION['user_role'],
                'department_id' => $_SESSION['department_id']
            ]);
            
            $expires_at = date('Y-m-d H:i:s', time() + $this->getSessionTimeout());
            
            $query = "INSERT INTO sessions (id, employee_id, session_data, ip_address, user_agent, expires_at)
                      VALUES (:session_id, :employee_id, :session_data, :ip_address, :user_agent, :expires_at)
                      ON DUPLICATE KEY UPDATE 
                      session_data = :session_data,
                      last_activity = CURRENT_TIMESTAMP,
                      expires_at = :expires_at";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':session_id' => session_id(),
                ':employee_id' => $_SESSION['user_id'],
                ':session_data' => $session_data,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':expires_at' => $expires_at
            ]);
            
        } catch (Exception $e) {
            error_log("Session save failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get user permissions
     */
    private function getUserPermissions($user_id) {
        $query = "SELECT DISTINCT p.permission_code
                  FROM users u
                  JOIN user_roles ur ON u.id = ur.user_id
                  JOIN role_permissions rp ON ur.role_id = rp.role_id
                  JOIN permissions p ON rp.permission_id = p.id
                  WHERE u.id = :user_id AND p.is_active = 1 AND rp.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        $permissions = [];
        while ($row = $stmt->fetch()) {
            $permissions[] = $row['permission_code'];
        }
        
        return $permissions;
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id) {
        $query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
    }
    
    /**
     * Check login attempts
     */
    private function checkLoginAttempts($username) {
        $cache_key = 'login_attempts_' . md5($username);
        
        // In production, use Redis for this
        // For now, we'll use session-based tracking
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if (isset($_SESSION['login_attempts'][$username])) {
            $attempts = $_SESSION['login_attempts'][$username];
            
            if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
                $lockout_remaining = LOGIN_LOCKOUT_TIME - (time() - $attempts['last_attempt']);
                
                if ($lockout_remaining > 0) {
                    throw new Exception('Too many failed login attempts. Please try again in ' . ceil($lockout_remaining / 60) . ' minutes.');
                } else {
                    // Reset after lockout period
                    unset($_SESSION['login_attempts'][$username]);
                }
            }
        }
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($username) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if (!isset($_SESSION['login_attempts'][$username])) {
            $_SESSION['login_attempts'][$username] = [
                'count' => 0,
                'last_attempt' => time()
            ];
        }
        
        $_SESSION['login_attempts'][$username]['count']++;
        $_SESSION['login_attempts'][$username]['last_attempt'] = time();
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts($username) {
        if (isset($_SESSION['login_attempts'][$username])) {
            unset($_SESSION['login_attempts'][$username]);
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($user_id, $old_password, $new_password) {
        try {
            // Validate new password
            if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
                throw new Exception('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long');
            }
            
            // Get current password
            $query = "SELECT password FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':user_id' => $user_id]);
            
            $user = $stmt->fetch();
            
            // Verify old password
            if (!password_verify($old_password, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':password' => $new_hash,
                ':user_id' => $user_id
            ]);
            
            logActivity('update', 'users', $user_id, null, ['password_changed' => true]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Reset password (admin function)
     */
    public function resetPassword($user_id) {
        try {
            // Generate random password
            $new_password = generateRandomPassword(8);
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':password' => $new_hash,
                ':user_id' => $user_id
            ]);
            
            logActivity('update', 'users', $user_id, null, ['password_reset' => true]);
            
            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'new_password' => $new_password
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}
