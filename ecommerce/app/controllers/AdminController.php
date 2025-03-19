<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Category.php';

class AdminController extends Controller {
    private $order;
    private $product;
    private $review;
    private $category;

    public function __construct() {
        parent::__construct();
        $this->order = new Order();
        $this->product = new Product();
        $this->review = new Review();
        $this->category = new Category();
    }

    // Get dashboard statistics
    public function dashboard() {
        try {
            $this->requireRole('admin');

            // Get date range from query parameters
            $params = $this->getQueryParams();
            $startDate = isset($params['start_date']) ? $params['start_date'] : date('Y-m-d', strtotime('-30 days'));
            $endDate = isset($params['end_date']) ? $params['end_date'] : date('Y-m-d');

            // Get sales statistics
            $salesStats = $this->order->getSalesStats($startDate, $endDate);

            // Get popular products
            $popularProducts = $this->order->getPopularProducts(5);

            // Get recent orders
            $recentOrders = $this->order->getByDateRange($startDate, $endDate, 1, 5)['data'];

            // Get pending reviews count
            $pendingReviews = $this->review->count('status = ?', ['pending']);

            // Get low stock products
            $lowStockProducts = $this->product->where('stock_quantity <= ?', 10);

            // Get user statistics
            $userStats = $this->getUserStats($startDate, $endDate);

            $this->successResponse([
                'sales_stats' => $salesStats,
                'popular_products' => $popularProducts,
                'recent_orders' => $recentOrders,
                'pending_reviews' => $pendingReviews,
                'low_stock_products' => $lowStockProducts,
                'user_stats' => $userStats
            ]);

        } catch (Exception $e) {
            $this->handleError('Get dashboard stats failed', $e);
            $this->errorResponse('Failed to fetch dashboard statistics');
        }
    }

    // Get sales report
    public function salesReport() {
        try {
            $this->requireRole('admin');

            $params = $this->getQueryParams();
            $startDate = isset($params['start_date']) ? $params['start_date'] : date('Y-m-d', strtotime('-30 days'));
            $endDate = isset($params['end_date']) ? $params['end_date'] : date('Y-m-d');
            $groupBy = isset($params['group_by']) ? $params['group_by'] : 'day';

            $sql = "SELECT 
                    DATE_FORMAT(created_at, ?) as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_sales,
                    AVG(total_amount) as average_order_value
                    FROM orders
                    WHERE created_at BETWEEN ? AND ?
                    AND status != 'cancelled'
                    GROUP BY period
                    ORDER BY period";

            $format = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';
            $result = $this->db->query($sql, [$format, $startDate, $endDate])->fetchAll();

            $this->successResponse($result);

        } catch (Exception $e) {
            $this->handleError('Get sales report failed', $e);
            $this->errorResponse('Failed to generate sales report');
        }
    }

    // Get inventory report
    public function inventoryReport() {
        try {
            $this->requireRole('admin');

            $params = $this->getQueryParams();
            $categoryId = isset($params['category_id']) ? $params['category_id'] : null;
            $stockThreshold = isset($params['stock_threshold']) ? (int)$params['stock_threshold'] : 10;

            $where = ['stock_quantity <= ?'];
            $whereParams = [$stockThreshold];

            if ($categoryId) {
                $where[] = 'category_id = ?';
                $whereParams[] = $categoryId;
            }

            $products = $this->product->where(implode(' AND ', $where), $whereParams);

            // Add category names
            foreach ($products as &$product) {
                $category = $this->category->find($product['category_id']);
                $product['category_name'] = $category ? $category['name'] : null;
            }

            $this->successResponse($products);

        } catch (Exception $e) {
            $this->handleError('Get inventory report failed', $e);
            $this->errorResponse('Failed to generate inventory report');
        }
    }

    // Get user statistics
    private function getUserStats($startDate, $endDate) {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_users,
                    COUNT(DISTINCT o.user_id) as customers_with_orders
                    FROM users u
                    LEFT JOIN orders o ON u.id = o.user_id";

            return $this->db->query($sql, [$startDate, $endDate])->fetch();

        } catch (Exception $e) {
            $this->handleError('Get user stats failed', $e);
            return [
                'total_users' => 0,
                'new_users' => 0,
                'customers_with_orders' => 0
            ];
        }
    }

    // Export orders to CSV
    public function exportOrders() {
        try {
            $this->requireRole('admin');

            $params = $this->getQueryParams();
            $startDate = isset($params['start_date']) ? $params['start_date'] : date('Y-m-d', strtotime('-30 days'));
            $endDate = isset($params['end_date']) ? $params['end_date'] : date('Y-m-d');

            $sql = "SELECT o.*, u.email as user_email,
                    GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR '; ') as items
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE o.created_at BETWEEN ? AND ?
                    GROUP BY o.id
                    ORDER BY o.created_at DESC";

            $orders = $this->db->query($sql, [$startDate, $endDate])->fetchAll();

            // Generate CSV
            $filename = "orders_export_" . date('Y-m-d_His') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($output, [
                'Order ID',
                'Date',
                'Customer Email',
                'Status',
                'Total Amount',
                'Items',
                'Shipping Address',
                'Payment Method',
                'Payment Status'
            ]);

            // Add data
            foreach ($orders as $order) {
                fputcsv($output, [
                    $order['id'],
                    $order['created_at'],
                    $order['user_email'],
                    $order['status'],
                    $order['total_amount'],
                    $order['items'],
                    $order['shipping_address'],
                    $order['payment_method'],
                    $order['payment_status']
                ]);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            $this->handleError('Export orders failed', $e);
            $this->errorResponse('Failed to export orders');
        }
    }

    // Get system logs
    public function logs() {
        try {
            $this->requireRole('admin');

            $params = $this->getQueryParams();
            $type = isset($params['type']) ? $params['type'] : 'error';
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;

            $logFile = LOG_DIR . $type . '.log';
            if (!file_exists($logFile)) {
                return $this->successResponse([
                    'logs' => [],
                    'message' => 'No logs found'
                ]);
            }

            // Read last n lines from log file
            $logs = [];
            $file = new SplFileObject($logFile, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();

            $lines = new LimitIterator($file, max(0, $lastLine - $limit), $lastLine);
            foreach ($lines as $line) {
                if (trim($line)) {
                    $logs[] = $line;
                }
            }

            $this->successResponse([
                'logs' => array_reverse($logs),
                'total_lines' => $lastLine
            ]);

        } catch (Exception $e) {
            $this->handleError('Get logs failed', $e);
            $this->errorResponse('Failed to fetch system logs');
        }
    }

    // Clear system logs
    public function clearLogs() {
        try {
            $this->requireRole('admin');

            $params = $this->getPostData();
            $type = isset($params['type']) ? $params['type'] : 'error';

            $logFile = LOG_DIR . $type . '.log';
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
            }

            $this->successResponse(null, 'Logs cleared successfully');

        } catch (Exception $e) {
            $this->handleError('Clear logs failed', $e);
            $this->errorResponse('Failed to clear system logs');
        }
    }

    // Get system information
    public function systemInfo() {
        try {
            $this->requireRole('admin');

            $info = [
                'php_version' => phpversion(),
                'mysql_version' => $this->db->query('SELECT VERSION() as version')->fetch()['version'],
                'server_software' => $_SERVER['SERVER_SOFTWARE'],
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'disk_free_space' => disk_free_space('/'),
                'disk_total_space' => disk_total_space('/'),
                'server_load' => sys_getloadavg(),
                'database_size' => $this->getDatabaseSize(),
                'total_products' => $this->product->count(),
                'total_users' => $this->user->count(),
                'total_orders' => $this->order->count(),
                'php_extensions' => get_loaded_extensions()
            ];

            $this->successResponse($info);

        } catch (Exception $e) {
            $this->handleError('Get system info failed', $e);
            $this->errorResponse('Failed to fetch system information');
        }
    }

    // Get database size
    private function getDatabaseSize() {
        try {
            $sql = "SELECT 
                    table_schema as database_name,
                    SUM(data_length + index_length) as size
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                    GROUP BY table_schema";

            $result = $this->db->query($sql, [DB_NAME])->fetch();
            return $result['size'] ?? 0;

        } catch (Exception $e) {
            $this->handleError('Get database size failed', $e);
            return 0;
        }
    }
}
