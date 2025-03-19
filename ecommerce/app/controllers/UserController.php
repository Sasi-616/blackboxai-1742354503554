<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Review.php';

class UserController extends Controller {
    private $order;
    private $review;

    public function __construct() {
        parent::__construct();
        $this->order = new Order();
        $this->review = new Review();
    }

    // User registration
    public function register() {
        try {
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'password', 'confirm_password'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Validate email
            if (!$this->validateEmail($data['email'])) {
                return $this->errorResponse('Invalid email address');
            }

            // Validate password
            if ($data['password'] !== $data['confirm_password']) {
                return $this->errorResponse('Passwords do not match');
            }

            if (strlen($data['password']) < 8) {
                return $this->errorResponse('Password must be at least 8 characters long');
            }

            // Remove confirm_password from data
            unset($data['confirm_password']);

            // Register user
            $user = $this->user->register($data);
            if (!$user) {
                throw new Exception('Registration failed');
            }

            // Send welcome email
            $this->sendWelcomeEmail($user['email'], $user['first_name']);

            $this->successResponse(null, 'Registration successful');

        } catch (Exception $e) {
            $this->handleError('Registration failed', $e);
            $this->errorResponse('Registration failed. Please try again.');
        }
    }

    // User login
    public function login() {
        try {
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['email', 'password'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Authenticate user
            $user = $this->user->authenticate($data['email'], $data['password']);
            if (!$user) {
                return $this->errorResponse('Invalid email or password');
            }

            $this->successResponse([
                'user' => $user,
                'token' => $this->generateCsrfToken()
            ]);

        } catch (Exception $e) {
            $this->handleError('Login failed', $e);
            $this->errorResponse('Login failed. Please try again.');
        }
    }

    // User logout
    public function logout() {
        try {
            $this->user->logout();
            $this->successResponse(null, 'Logout successful');
        } catch (Exception $e) {
            $this->handleError('Logout failed', $e);
            $this->errorResponse('Logout failed');
        }
    }

    // Get user profile
    public function profile() {
        try {
            $this->requireLogin();
            
            $userId = $this->user->getCurrentUser()['id'];
            $user = $this->user->find($userId);
            
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Remove sensitive information
            unset($user['password']);

            $this->successResponse($user);

        } catch (Exception $e) {
            $this->handleError('Get profile failed', $e);
            $this->errorResponse('Failed to fetch profile');
        }
    }

    // Update user profile
    public function updateProfile() {
        try {
            $this->requireLogin();
            
            $data = $this->getPostData();
            $userId = $this->user->getCurrentUser()['id'];

            // Validate email if being updated
            if (isset($data['email']) && !$this->validateEmail($data['email'])) {
                return $this->errorResponse('Invalid email address');
            }

            // Remove sensitive fields
            unset($data['password']);
            unset($data['role']);

            if (!$this->user->update($userId, $data)) {
                throw new Exception('Failed to update profile');
            }

            $user = $this->user->find($userId);
            unset($user['password']);

            $this->successResponse($user, 'Profile updated successfully');

        } catch (Exception $e) {
            $this->handleError('Update profile failed', $e);
            $this->errorResponse('Failed to update profile');
        }
    }

    // Change password
    public function changePassword() {
        try {
            $this->requireLogin();
            
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['current_password', 'new_password', 'confirm_password'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Validate new password
            if ($data['new_password'] !== $data['confirm_password']) {
                return $this->errorResponse('New passwords do not match');
            }

            if (strlen($data['new_password']) < 8) {
                return $this->errorResponse('New password must be at least 8 characters long');
            }

            $userId = $this->user->getCurrentUser()['id'];
            if (!$this->user->updatePassword($userId, $data['current_password'], $data['new_password'])) {
                return $this->errorResponse('Current password is incorrect');
            }

            $this->successResponse(null, 'Password changed successfully');

        } catch (Exception $e) {
            $this->handleError('Change password failed', $e);
            $this->errorResponse('Failed to change password');
        }
    }

    // Request password reset
    public function requestPasswordReset() {
        try {
            $data = $this->getPostData();
            
            if (!isset($data['email']) || !$this->validateEmail($data['email'])) {
                return $this->errorResponse('Invalid email address');
            }

            if (!$this->user->resetPassword($data['email'])) {
                throw new Exception('Failed to initiate password reset');
            }

            $this->successResponse(null, 'Password reset instructions sent to your email');

        } catch (Exception $e) {
            $this->handleError('Password reset request failed', $e);
            $this->errorResponse('Failed to process password reset request');
        }
    }

    // Reset password
    public function resetPassword() {
        try {
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['token', 'new_password', 'confirm_password'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Validate new password
            if ($data['new_password'] !== $data['confirm_password']) {
                return $this->errorResponse('Passwords do not match');
            }

            if (strlen($data['new_password']) < 8) {
                return $this->errorResponse('Password must be at least 8 characters long');
            }

            if (!$this->user->confirmPasswordReset($data['token'], $data['new_password'])) {
                return $this->errorResponse('Invalid or expired reset token');
            }

            $this->successResponse(null, 'Password reset successful');

        } catch (Exception $e) {
            $this->handleError('Password reset failed', $e);
            $this->errorResponse('Failed to reset password');
        }
    }

    // Get user orders
    public function orders() {
        try {
            $this->requireLogin();
            
            $params = $this->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            
            $userId = $this->user->getCurrentUser()['id'];
            $orders = $this->user->getOrders($userId, $page);

            $this->successResponse($orders);

        } catch (Exception $e) {
            $this->handleError('Get orders failed', $e);
            $this->errorResponse('Failed to fetch orders');
        }
    }

    // Get user reviews
    public function reviews() {
        try {
            $this->requireLogin();
            
            $params = $this->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            
            $userId = $this->user->getCurrentUser()['id'];
            $reviews = $this->user->getReviews($userId, $page);

            $this->successResponse($reviews);

        } catch (Exception $e) {
            $this->handleError('Get reviews failed', $e);
            $this->errorResponse('Failed to fetch reviews');
        }
    }

    // Get user wishlist
    public function wishlist() {
        try {
            $this->requireLogin();
            
            $userId = $this->user->getCurrentUser()['id'];
            $wishlist = $this->user->getWishlist($userId);

            $this->successResponse($wishlist);

        } catch (Exception $e) {
            $this->handleError('Get wishlist failed', $e);
            $this->errorResponse('Failed to fetch wishlist');
        }
    }

    // Add to wishlist
    public function addToWishlist($productId) {
        try {
            $this->requireLogin();
            
            $userId = $this->user->getCurrentUser()['id'];
            if (!$this->user->addToWishlist($userId, $productId)) {
                throw new Exception('Failed to add to wishlist');
            }

            $this->successResponse(null, 'Added to wishlist');

        } catch (Exception $e) {
            $this->handleError('Add to wishlist failed', $e);
            $this->errorResponse('Failed to add to wishlist');
        }
    }

    // Remove from wishlist
    public function removeFromWishlist($productId) {
        try {
            $this->requireLogin();
            
            $userId = $this->user->getCurrentUser()['id'];
            if (!$this->user->removeFromWishlist($userId, $productId)) {
                throw new Exception('Failed to remove from wishlist');
            }

            $this->successResponse(null, 'Removed from wishlist');

        } catch (Exception $e) {
            $this->handleError('Remove from wishlist failed', $e);
            $this->errorResponse('Failed to remove from wishlist');
        }
    }

    // Send welcome email
    private function sendWelcomeEmail($email, $firstName) {
        $subject = "Welcome to " . SITE_NAME;
        $body = "
            <h2>Welcome to " . SITE_NAME . ", $firstName!</h2>
            <p>Thank you for registering with us. We're excited to have you as a member of our community.</p>
            <p>You can now:</p>
            <ul>
                <li>Browse our extensive collection of products</li>
                <li>Create and manage your wishlist</li>
                <li>Track your orders</li>
                <li>Leave reviews for products you've purchased</li>
            </ul>
            <p>If you have any questions, feel free to contact us at " . ADMIN_EMAIL . "</p>
        ";

        $this->sendEmail($email, $subject, $body);
    }
}
