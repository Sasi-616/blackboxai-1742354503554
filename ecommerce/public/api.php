<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/config.php';

// Load all controllers
require_once __DIR__ . '/../app/controllers/ProductController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/CartController.php';
require_once __DIR__ . '/../app/controllers/OrderController.php';
require_once __DIR__ . '/../app/controllers/CategoryController.php';
require_once __DIR__ . '/../app/controllers/ReviewController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

// Parse the request URL
$request = parse_url($_SERVER['REQUEST_URI']);
$path = $request['path'];
$path = str_replace('/api/', '', $path);
$segments = explode('/', trim($path, '/'));

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Route the request
    switch ($segments[0]) {
        case 'products':
            $controller = new ProductController();
            if (count($segments) === 1) {
                switch ($method) {
                    case 'GET':
                        $controller->index();
                        break;
                    case 'POST':
                        $controller->create();
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            } else {
                $productId = $segments[1];
                switch ($method) {
                    case 'GET':
                        if (isset($segments[2])) {
                            switch ($segments[2]) {
                                case 'reviews':
                                    $controller->reviews($productId);
                                    break;
                                default:
                                    throw new Exception('Not found', 404);
                            }
                        } else {
                            $controller->show($productId);
                        }
                        break;
                    case 'PUT':
                        $controller->update($productId);
                        break;
                    case 'DELETE':
                        $controller->delete($productId);
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            }
            break;

        case 'categories':
            $controller = new CategoryController();
            if (count($segments) === 1) {
                switch ($method) {
                    case 'GET':
                        $controller->index();
                        break;
                    case 'POST':
                        $controller->create();
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            } else {
                $categoryId = $segments[1];
                switch ($method) {
                    case 'GET':
                        if (isset($segments[2])) {
                            switch ($segments[2]) {
                                case 'products':
                                    $controller->show($categoryId);
                                    break;
                                default:
                                    throw new Exception('Not found', 404);
                            }
                        } else {
                            $controller->show($categoryId);
                        }
                        break;
                    case 'PUT':
                        $controller->update($categoryId);
                        break;
                    case 'DELETE':
                        $controller->delete($categoryId);
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            }
            break;

        case 'users':
            $controller = new UserController();
            switch ($method) {
                case 'POST':
                    if (count($segments) === 1) {
                        $controller->register();
                    } else {
                        switch ($segments[1]) {
                            case 'login':
                                $controller->login();
                                break;
                            case 'logout':
                                $controller->logout();
                                break;
                            default:
                                throw new Exception('Not found', 404);
                        }
                    }
                    break;
                case 'GET':
                    if (isset($segments[1])) {
                        switch ($segments[1]) {
                            case 'profile':
                                $controller->profile();
                                break;
                            case 'orders':
                                $controller->orders();
                                break;
                            case 'reviews':
                                $controller->reviews();
                                break;
                            case 'wishlist':
                                $controller->wishlist();
                                break;
                            default:
                                throw new Exception('Not found', 404);
                        }
                    }
                    break;
                case 'PUT':
                    if (isset($segments[1]) && $segments[1] === 'profile') {
                        $controller->updateProfile();
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'cart':
            $controller = new CartController();
            switch ($method) {
                case 'GET':
                    $controller->index();
                    break;
                case 'POST':
                    if (count($segments) === 1) {
                        $controller->addItem();
                    } else {
                        switch ($segments[1]) {
                            case 'clear':
                                $controller->clear();
                                break;
                            case 'coupon':
                                $controller->applyCoupon();
                                break;
                            default:
                                throw new Exception('Not found', 404);
                        }
                    }
                    break;
                case 'PUT':
                    $controller->updateQuantity();
                    break;
                case 'DELETE':
                    if (isset($segments[1])) {
                        $controller->removeItem($segments[1]);
                    }
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;

        case 'orders':
            $controller = new OrderController();
            if (count($segments) === 1) {
                switch ($method) {
                    case 'GET':
                        $controller->index();
                        break;
                    case 'POST':
                        $controller->create();
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            } else {
                $orderId = $segments[1];
                switch ($method) {
                    case 'GET':
                        $controller->show($orderId);
                        break;
                    case 'PUT':
                        if (isset($segments[2])) {
                            switch ($segments[2]) {
                                case 'status':
                                    $controller->updateStatus($orderId);
                                    break;
                                case 'cancel':
                                    $controller->cancel($orderId);
                                    break;
                                default:
                                    throw new Exception('Not found', 404);
                            }
                        }
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            }
            break;

        case 'reviews':
            $controller = new ReviewController();
            if (count($segments) === 1) {
                switch ($method) {
                    case 'GET':
                        if (isset($_GET['product_id'])) {
                            $controller->index($_GET['product_id']);
                        } else {
                            throw new Exception('Product ID required', 400);
                        }
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            } else {
                switch ($method) {
                    case 'POST':
                        $controller->create($segments[1]);
                        break;
                    case 'PUT':
                        $controller->update($segments[1]);
                        break;
                    case 'DELETE':
                        $controller->delete($segments[1]);
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            }
            break;

        case 'admin':
            $controller = new AdminController();
            if (count($segments) === 1) {
                switch ($method) {
                    case 'GET':
                        $controller->dashboard();
                        break;
                    default:
                        throw new Exception('Method not allowed', 405);
                }
            } else {
                switch ($segments[1]) {
                    case 'sales':
                        $controller->salesReport();
                        break;
                    case 'inventory':
                        $controller->inventoryReport();
                        break;
                    case 'export':
                        if ($method === 'GET') {
                            $controller->exportOrders();
                        }
                        break;
                    case 'logs':
                        switch ($method) {
                            case 'GET':
                                $controller->logs();
                                break;
                            case 'DELETE':
                                $controller->clearLogs();
                                break;
                            default:
                                throw new Exception('Method not allowed', 405);
                        }
                        break;
                    case 'system':
                        $controller->systemInfo();
                        break;
                    default:
                        throw new Exception('Not found', 404);
                }
            }
            break;

        default:
            throw new Exception('Not found', 404);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
