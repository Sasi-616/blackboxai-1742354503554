<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

class OrderController extends Controller {
    private $order;
    private $cart;
    private $product;

    public function __construct() {
        parent::__construct();
        $this->order = new Order();
        $this->cart = new Cart();
        $this->product = new Product();
    }

    // Create new order
    public function create() {
        try {
            $this->requireLogin();
            
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['shipping_address', 'billing_address', 'payment_method', 'shipping_method'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Get user's cart
            $userId = $this->user->getCurrentUser()['id'];
            $cart = $this->cart->getCart($userId);
            
            if (!$cart) {
                return $this->errorResponse('Cart is empty');
            }

            // Get cart items
            $cartItems = $this->cart->getItems($cart['id']);
            if (empty($cartItems)) {
                return $this->errorResponse('Cart is empty');
            }

            // Validate cart items
            $validation = $this->cart->validateItems($cart['id']);
            if (!$validation['valid']) {
                return $this->errorResponse('Some items in your cart are no longer available', 400, [
                    'invalid_items' => $validation['invalid_items']
                ]);
            }

            // Process payment
            $paymentResult = $this->processPayment($data['payment_method'], $this->cart->getTotal($cart['id']));
            if (!$paymentResult['success']) {
                return $this->errorResponse('Payment failed: ' . $paymentResult['message']);
            }

            // Create order
            $orderData = [
                'user_id' => $userId,
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'],
                'payment_method' => $data['payment_method'],
                'payment_status' => $paymentResult['status'],
                'shipping_method' => $data['shipping_method']
            ];

            $orderId = $this->order->createOrder($userId, $cartItems, $orderData);
            if (!$orderId) {
                // Refund payment if order creation fails
                $this->refundPayment($paymentResult['transaction_id']);
                throw new Exception('Failed to create order');
            }

            // Clear cart after successful order
            $this->cart->clearCart($cart['id']);

            // Send order confirmation email
            $this->sendOrderConfirmationEmail($orderId);

            $order = $this->order->getWithItems($orderId);
            $this->successResponse($order, 'Order created successfully');

        } catch (Exception $e) {
            $this->handleError('Create order failed', $e);
            $this->errorResponse('Failed to create order');
        }
    }

    // Get order details
    public function show($orderId) {
        try {
            $this->requireLogin();
            
            $order = $this->order->getWithItems($orderId);
            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            // Check if user owns the order or is admin
            if ($order['user_id'] !== $this->user->getCurrentUser()['id'] && 
                !$this->user->hasRole('admin')) {
                return $this->errorResponse('Permission denied', 403);
            }

            $this->successResponse($order);

        } catch (Exception $e) {
            $this->handleError('Get order failed', $e);
            $this->errorResponse('Failed to fetch order details');
        }
    }

    // Get all orders (admin only)
    public function index() {
        try {
            $this->requireRole('admin');
            
            $params = $this->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 10;

            // Filter by status
            $status = isset($params['status']) ? $params['status'] : null;
            if ($status) {
                $orders = $this->order->getByStatus($status, $page, $perPage);
            } else {
                $orders = $this->order->paginate($page, $perPage);
            }

            $this->successResponse($orders);

        } catch (Exception $e) {
            $this->handleError('Get orders failed', $e);
            $this->errorResponse('Failed to fetch orders');
        }
    }

    // Update order status (admin only)
    public function updateStatus($orderId) {
        try {
            $this->requireRole('admin');
            
            $data = $this->getPostData();
            if (!isset($data['status'])) {
                return $this->errorResponse('Status is required');
            }

            if (!$this->order->updateStatus($orderId, $data['status'])) {
                throw new Exception('Failed to update order status');
            }

            // Send status update email
            $this->sendOrderStatusEmail($orderId, $data['status']);

            $order = $this->order->getWithItems($orderId);
            $this->successResponse($order, 'Order status updated');

        } catch (Exception $e) {
            $this->handleError('Update status failed', $e);
            $this->errorResponse('Failed to update order status');
        }
    }

    // Cancel order
    public function cancel($orderId) {
        try {
            $this->requireLogin();
            
            $order = $this->order->getWithItems($orderId);
            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            // Check if user owns the order
            if ($order['user_id'] !== $this->user->getCurrentUser()['id']) {
                return $this->errorResponse('Permission denied', 403);
            }

            // Check if order can be cancelled
            if (!in_array($order['status'], ['pending', 'processing'])) {
                return $this->errorResponse('Order cannot be cancelled');
            }

            if (!$this->order->cancelOrder($orderId)) {
                throw new Exception('Failed to cancel order');
            }

            // Process refund if payment was made
            if ($order['payment_status'] === 'paid') {
                $this->processRefund($order);
            }

            // Send cancellation email
            $this->sendOrderCancellationEmail($orderId);

            $this->successResponse(null, 'Order cancelled successfully');

        } catch (Exception $e) {
            $this->handleError('Cancel order failed', $e);
            $this->errorResponse('Failed to cancel order');
        }
    }

    // Get order statistics (admin only)
    public function statistics() {
        try {
            $this->requireRole('admin');
            
            $params = $this->getQueryParams();
            $startDate = isset($params['start_date']) ? $params['start_date'] : null;
            $endDate = isset($params['end_date']) ? $params['end_date'] : null;

            $stats = $this->order->getSalesStats($startDate, $endDate);
            $this->successResponse($stats);

        } catch (Exception $e) {
            $this->handleError('Get statistics failed', $e);
            $this->errorResponse('Failed to fetch statistics');
        }
    }

    // Process payment
    private function processPayment($paymentMethod, $amount) {
        // This is a placeholder for payment processing logic
        // In a real application, you would integrate with a payment gateway
        try {
            switch ($paymentMethod) {
                case 'stripe':
                    // Implement Stripe payment processing
                    break;
                case 'paypal':
                    // Implement PayPal payment processing
                    break;
                case 'razorpay':
                    // Implement Razorpay payment processing
                    break;
                default:
                    throw new Exception('Unsupported payment method');
            }

            // For demonstration, always return success
            return [
                'success' => true,
                'status' => 'paid',
                'transaction_id' => uniqid('trx_')
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Process refund
    private function processRefund($order) {
        // This is a placeholder for refund processing logic
        // In a real application, you would integrate with a payment gateway
        try {
            switch ($order['payment_method']) {
                case 'stripe':
                    // Implement Stripe refund processing
                    break;
                case 'paypal':
                    // Implement PayPal refund processing
                    break;
                case 'razorpay':
                    // Implement Razorpay refund processing
                    break;
                default:
                    throw new Exception('Unsupported payment method');
            }

            return true;
        } catch (Exception $e) {
            $this->handleError('Process refund failed', $e);
            return false;
        }
    }

    // Send order confirmation email
    private function sendOrderConfirmationEmail($orderId) {
        try {
            $order = $this->order->getWithItems($orderId);
            $user = $this->user->find($order['user_id']);

            $subject = "Order Confirmation - " . SITE_NAME;
            $body = "
                <h2>Thank you for your order!</h2>
                <p>Dear {$user['first_name']},</p>
                <p>Your order #{$orderId} has been received and is being processed.</p>
                <h3>Order Details:</h3>
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
            ";

            foreach ($order['items'] as $item) {
                $body .= "
                    <tr>
                        <td>{$item['name']}</td>
                        <td>{$item['quantity']}</td>
                        <td>\${$item['price']}</td>
                    </tr>
                ";
            }

            $body .= "
                </table>
                <p><strong>Total: \${$order['total_amount']}</strong></p>
                <p>We will notify you when your order ships.</p>
            ";

            $this->sendEmail($user['email'], $subject, $body);
        } catch (Exception $e) {
            $this->handleError('Send order confirmation email failed', $e);
        }
    }

    // Send order status update email
    private function sendOrderStatusEmail($orderId, $status) {
        try {
            $order = $this->order->getWithItems($orderId);
            $user = $this->user->find($order['user_id']);

            $subject = "Order Status Update - " . SITE_NAME;
            $body = "
                <h2>Order Status Update</h2>
                <p>Dear {$user['first_name']},</p>
                <p>Your order #{$orderId} has been updated to: <strong>$status</strong></p>
            ";

            if ($status === 'shipped') {
                $body .= "
                    <p>Tracking Number: {$order['tracking_number']}</p>
                    <p>You can track your order using this number.</p>
                ";
            }

            $this->sendEmail($user['email'], $subject, $body);
        } catch (Exception $e) {
            $this->handleError('Send status update email failed', $e);
        }
    }

    // Send order cancellation email
    private function sendOrderCancellationEmail($orderId) {
        try {
            $order = $this->order->getWithItems($orderId);
            $user = $this->user->find($order['user_id']);

            $subject = "Order Cancelled - " . SITE_NAME;
            $body = "
                <h2>Order Cancellation Confirmation</h2>
                <p>Dear {$user['first_name']},</p>
                <p>Your order #{$orderId} has been cancelled.</p>
                <p>If you paid for this order, a refund will be processed shortly.</p>
                <p>If you have any questions, please contact our customer support.</p>
            ";

            $this->sendEmail($user['email'], $subject, $body);
        } catch (Exception $e) {
            $this->handleError('Send cancellation email failed', $e);
        }
    }
}
