<?php
class Controller {
    protected $db;
    protected $user;

    public function __construct() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize database connection
        require_once __DIR__ . '/../db.php';
        $this->db = Database::getInstance();

        // Load user if logged in
        require_once __DIR__ . '/../models/User.php';
        $this->user = new User();
    }

    // Send JSON response
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // Send error response
    protected function errorResponse($message, $statusCode = 400) {
        return $this->jsonResponse([
            'error' => true,
            'message' => $message
        ], $statusCode);
    }

    // Send success response
    protected function successResponse($data = null, $message = 'Success') {
        return $this->jsonResponse([
            'error' => false,
            'message' => $message,
            'data' => $data
        ]);
    }

    // Validate required fields
    protected function validateRequired($data, $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    // Check if user is logged in
    protected function requireLogin() {
        if (!$this->user->isLoggedIn()) {
            $this->errorResponse('Authentication required', 401);
        }
    }

    // Check if user has specific role
    protected function requireRole($role) {
        $this->requireLogin();
        if (!$this->user->hasRole($role)) {
            $this->errorResponse('Permission denied', 403);
        }
    }

    // Sanitize input
    protected function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
        } else {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    // Validate email
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Get POST data
    protected function getPostData() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = $_POST;
        }
        return $this->sanitize($data);
    }

    // Get query parameters
    protected function getQueryParams() {
        return $this->sanitize($_GET);
    }

    // Handle file upload
    protected function handleFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = 5242880) {
        try {
            if (!isset($file['error']) || is_array($file['error'])) {
                throw new Exception('Invalid file parameters');
            }

            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('No file uploaded');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('File size exceeds limit');
                default:
                    throw new Exception('Unknown file upload error');
            }

            if ($file['size'] > $maxSize) {
                throw new Exception('File size exceeds limit');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes)) {
                throw new Exception('Invalid file type');
            }

            $fileName = uniqid() . '.' . $ext;
            $uploadPath = UPLOAD_DIR . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to move uploaded file');
            }

            return $fileName;

        } catch (Exception $e) {
            $this->handleError('File upload failed', $e);
            return false;
        }
    }

    // Generate pagination data
    protected function getPaginationData($page, $perPage, $total) {
        $lastPage = ceil($total / $perPage);
        $page = max(1, min($page, $lastPage));
        
        return [
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total' => (int)$total,
            'last_page' => $lastPage,
            'has_more_pages' => $page < $lastPage
        ];
    }

    // Log error
    protected function handleError($message, Exception $e) {
        error_log("$message: " . $e->getMessage());
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            throw $e;
        }
    }

    // Generate CSRF token
    protected function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verify CSRF token
    protected function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            $this->errorResponse('Invalid CSRF token', 403);
        }
    }

    // Send email
    protected function sendEmail($to, $subject, $body) {
        try {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: ' . ADMIN_EMAIL,
                'Reply-To: ' . ADMIN_EMAIL,
                'X-Mailer: PHP/' . phpversion()
            ];

            return mail($to, $subject, $body, implode("\r\n", $headers));
        } catch (Exception $e) {
            $this->handleError('Send email failed', $e);
            return false;
        }
    }
}
