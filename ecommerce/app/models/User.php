<?php
require_once __DIR__ . '/Model.php';

class User extends Model {
    protected $table = 'users';
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'role'
    ];

    // Register a new user
    public function register($data) {
        try {
            // Validate email uniqueness
            if ($this->firstWhere('email', $data['email'])) {
                throw new Exception('Email already exists');
            }

            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Set default role if not specified
            if (!isset($data['role'])) {
                $data['role'] = 'customer';
            }

            return $this->create($data);
        } catch (Exception $e) {
            $this->handleError('User registration failed', $e);
            return false;
        }
    }

    // Authenticate user
    public function authenticate($email, $password) {
        try {
            $user = $this->firstWhere('email', $email);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }

            // Remove password from user data
            unset($user['password']);
            
            // Set session
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            
            return $user;
        } catch (Exception $e) {
            $this->handleError('Authentication failed', $e);
            return false;
        }
    }

    // Logout user
    public function logout() {
        unset($_SESSION['user']);
        unset($_SESSION['user_id']);
        session_destroy();
        return true;
    }

    // Get current user
    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }

    // Check if user has role
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }

    // Update password
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->find($userId);
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            return $this->update($userId, [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
        } catch (Exception $e) {
            $this->handleError('Password update failed', $e);
            return false;
        }
    }

    // Reset password
    public function resetPassword($email) {
        try {
            $user = $this->firstWhere('email', $email);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store reset token
            $this->db->insert('password_resets', [
                'email' => $email,
                'token' => $token,
                'expires_at' => $expires
            ]);

            // Send reset email
            $resetLink = SITE_URL . "/reset-password.php?token=" . $token;
            $this->sendPasswordResetEmail($email, $resetLink);

            return true;
        } catch (Exception $e) {
            $this->handleError('Password reset failed', $e);
            return false;
        }
    }

    // Confirm password reset
    public function confirmPasswordReset($token, $newPassword) {
        try {
            // Get reset request
            $reset = $this->db->select(
                'password_resets',
                '*',
                'token = ? AND expires_at > NOW() AND used = 0',
                [$token]
            )->fetch();

            if (!$reset) {
                throw new Exception('Invalid or expired reset token');
            }

            // Update password
            $user = $this->firstWhere('email', $reset['email']);
            if (!$user) {
                throw new Exception('User not found');
            }

            $success = $this->update($user['id'], [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);

            if ($success) {
                // Mark token as used
                $this->db->update(
                    'password_resets',
                    ['used' => 1],
                    'token = ?',
                    [$token]
                );
            }

            return $success;
        } catch (Exception $e) {
            $this->handleError('Password reset confirmation failed', $e);
            return false;
        }
    }

    // Get user orders
    public function getOrders($userId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT o.*, COUNT(oi.id) as items_count 
                    FROM orders o 
                    LEFT JOIN order_items oi ON o.id = oi.order_id 
                    WHERE o.user_id = ? 
                    GROUP BY o.id 
                    ORDER BY o.created_at DESC 
                    LIMIT ?, ?";

            return $this->db->query($sql, [$userId, $offset, $perPage])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get user orders failed', $e);
            return [];
        }
    }

    // Get user reviews
    public function getReviews($userId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT r.*, p.name as product_name, p.image_url 
                    FROM reviews r 
                    LEFT JOIN products p ON r.product_id = p.id 
                    WHERE r.user_id = ? 
                    ORDER BY r.created_at DESC 
                    LIMIT ?, ?";

            return $this->db->query($sql, [$userId, $offset, $perPage])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get user reviews failed', $e);
            return [];
        }
    }

    // Get user wishlist
    public function getWishlist($userId) {
        try {
            $sql = "SELECT p.* 
                    FROM wishlist w 
                    LEFT JOIN products p ON w.product_id = p.id 
                    WHERE w.user_id = ? 
                    ORDER BY w.created_at DESC";

            return $this->db->query($sql, [$userId])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get wishlist failed', $e);
            return [];
        }
    }

    // Add to wishlist
    public function addToWishlist($userId, $productId) {
        try {
            return $this->db->insert('wishlist', [
                'user_id' => $userId,
                'product_id' => $productId
            ]) > 0;
        } catch (Exception $e) {
            $this->handleError('Add to wishlist failed', $e);
            return false;
        }
    }

    // Remove from wishlist
    public function removeFromWishlist($userId, $productId) {
        try {
            return $this->db->delete(
                'wishlist',
                'user_id = ? AND product_id = ?',
                [$userId, $productId]
            ) > 0;
        } catch (Exception $e) {
            $this->handleError('Remove from wishlist failed', $e);
            return false;
        }
    }

    // Send password reset email
    private function sendPasswordResetEmail($email, $resetLink) {
        // Implementation depends on your email service configuration
        // This is a placeholder for the actual email sending logic
        $to = $email;
        $subject = "Password Reset Request";
        $message = "Click the following link to reset your password: $resetLink";
        $headers = "From: " . ADMIN_EMAIL;

        mail($to, $subject, $message, $headers);
    }
}
