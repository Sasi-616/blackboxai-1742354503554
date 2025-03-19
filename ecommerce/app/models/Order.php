<?php
require_once __DIR__ . '/Model.php';

class Order extends Model {
    protected $table = 'orders';
    
    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'shipping_address',
        'billing_address',
        'payment_status',
        'payment_method',
        'shipping_method',
        'tracking_number',
        'notes'
    ];

    // Create a new order
    public function createOrder($userId, $cartItems, $orderData) {
        try {
            $this->db->beginTransaction();

            // Calculate total amount
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Add shipping cost
            $shippingMethod = $orderData['shipping_method'];
            $shippingCost = SHIPPING_METHODS[$shippingMethod]['price'] ?? 0;
            $totalAmount += $shippingCost;

            // Add tax
            $tax = $totalAmount * TAX_RATE;
            $totalAmount += $tax;

            // Create order
            $orderData['user_id'] = $userId;
            $orderData['total_amount'] = $totalAmount;
            $orderData['status'] = 'pending';
            $orderData['payment_status'] = 'pending';

            $orderId = $this->create($orderData);
            if (!$orderId) {
                throw new Exception('Failed to create order');
            }

            // Add order items
            foreach ($cartItems as $item) {
                $success = $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);

                if (!$success) {
                    throw new Exception('Failed to add order items');
                }

                // Update product stock
                $product = new Product();
                if (!$product->decreaseStock($item['product_id'], $item['quantity'])) {
                    throw new Exception('Failed to update product stock');
                }
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->handleError('Order creation failed', $e);
            return false;
        }
    }

    // Get order with items
    public function getWithItems($orderId) {
        try {
            // Get order details
            $order = $this->find($orderId);
            if (!$order) {
                return false;
            }

            // Get order items
            $sql = "SELECT oi.*, p.name, p.image_url 
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?";

            $items = $this->db->query($sql, [$orderId])->fetchAll();
            $order['items'] = $items;

            return $order;
        } catch (Exception $e) {
            $this->handleError('Get order with items failed', $e);
            return false;
        }
    }

    // Update order status
    public function updateStatus($orderId, $status) {
        try {
            return $this->update($orderId, ['status' => $status]);
        } catch (Exception $e) {
            $this->handleError('Update order status failed', $e);
            return false;
        }
    }

    // Update payment status
    public function updatePaymentStatus($orderId, $status) {
        try {
            return $this->update($orderId, ['payment_status' => $status]);
        } catch (Exception $e) {
            $this->handleError('Update payment status failed', $e);
            return false;
        }
    }

    // Add tracking number
    public function addTrackingNumber($orderId, $trackingNumber) {
        try {
            return $this->update($orderId, [
                'tracking_number' => $trackingNumber,
                'status' => 'shipped'
            ]);
        } catch (Exception $e) {
            $this->handleError('Add tracking number failed', $e);
            return false;
        }
    }

    // Get orders by status
    public function getByStatus($status, $page = 1, $perPage = 10) {
        try {
            return $this->paginate(
                $page,
                $perPage,
                'status = ?',
                [$status]
            );
        } catch (Exception $e) {
            $this->handleError('Get orders by status failed', $e);
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    // Get orders by date range
    public function getByDateRange($startDate, $endDate, $page = 1, $perPage = 10) {
        try {
            return $this->paginate(
                $page,
                $perPage,
                'created_at BETWEEN ? AND ?',
                [$startDate, $endDate]
            );
        } catch (Exception $e) {
            $this->handleError('Get orders by date range failed', $e);
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    // Get sales statistics
    public function getSalesStats($startDate = null, $endDate = null) {
        try {
            $where = '';
            $params = [];

            if ($startDate && $endDate) {
                $where = 'WHERE created_at BETWEEN ? AND ?';
                $params = [$startDate, $endDate];
            }

            $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_sales,
                    AVG(total_amount) as average_order_value,
                    COUNT(DISTINCT user_id) as unique_customers
                    FROM {$this->table}
                    $where";

            return $this->db->query($sql, $params)->fetch();
        } catch (Exception $e) {
            $this->handleError('Get sales statistics failed', $e);
            return [
                'total_orders' => 0,
                'total_sales' => 0,
                'average_order_value' => 0,
                'unique_customers' => 0
            ];
        }
    }

    // Get popular products
    public function getPopularProducts($limit = 10) {
        try {
            $sql = "SELECT p.*, 
                    COUNT(oi.id) as order_count,
                    SUM(oi.quantity) as total_quantity
                    FROM products p
                    LEFT JOIN order_items oi ON p.id = oi.product_id
                    LEFT JOIN orders o ON oi.order_id = o.id
                    WHERE o.status != 'cancelled'
                    GROUP BY p.id
                    ORDER BY total_quantity DESC
                    LIMIT ?";

            return $this->db->query($sql, [$limit])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get popular products failed', $e);
            return [];
        }
    }

    // Cancel order
    public function cancelOrder($orderId) {
        try {
            $this->db->beginTransaction();

            // Get order items
            $items = $this->db->select(
                'order_items',
                '*',
                'order_id = ?',
                [$orderId]
            )->fetchAll();

            // Restore product stock
            $product = new Product();
            foreach ($items as $item) {
                if (!$product->updateStock(
                    $item['product_id'],
                    $product->find($item['product_id'])['stock_quantity'] + $item['quantity']
                )) {
                    throw new Exception('Failed to restore product stock');
                }
            }

            // Update order status
            if (!$this->update($orderId, [
                'status' => 'cancelled',
                'notes' => 'Order cancelled by user'
            ])) {
                throw new Exception('Failed to update order status');
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->handleError('Cancel order failed', $e);
            return false;
        }
    }

    // Generate order number
    public function generateOrderNumber() {
        return ORDER_PREFIX . date('Ymd') . str_pad($this->count() + 1, 4, '0', STR_PAD_LEFT);
    }
}
