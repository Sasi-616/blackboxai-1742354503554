<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Product.php';

class Cart extends Model {
    protected $table = 'cart';
    
    protected $fillable = [
        'user_id',
        'session_id'
    ];

    private $sessionId;
    private $product;

    public function __construct() {
        parent::__construct();
        $this->sessionId = session_id();
        $this->product = new Product();
    }

    // Get or create cart
    public function getCart($userId = null) {
        try {
            // Try to find existing cart
            $cart = $this->findCart($userId);
            
            if (!$cart) {
                // Create new cart
                $cartId = $this->create([
                    'user_id' => $userId,
                    'session_id' => $this->sessionId
                ]);
                
                $cart = $this->find($cartId);
            }

            return $cart;
        } catch (Exception $e) {
            $this->handleError('Get cart failed', $e);
            return false;
        }
    }

    // Find cart by user ID or session ID
    private function findCart($userId = null) {
        try {
            if ($userId) {
                return $this->firstWhere('user_id', $userId);
            }
            return $this->firstWhere('session_id', $this->sessionId);
        } catch (Exception $e) {
            $this->handleError('Find cart failed', $e);
            return false;
        }
    }

    // Get cart items
    public function getItems($cartId) {
        try {
            $sql = "SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity 
                    FROM cart_items ci 
                    LEFT JOIN products p ON ci.product_id = p.id 
                    WHERE ci.cart_id = ?";
            
            return $this->db->query($sql, [$cartId])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get cart items failed', $e);
            return [];
        }
    }

    // Add item to cart
    public function addItem($cartId, $productId, $quantity = 1) {
        try {
            // Check product existence and stock
            $product = $this->product->find($productId);
            if (!$product || $product['stock_quantity'] < $quantity) {
                throw new Exception('Product not available in requested quantity');
            }

            // Check if product already in cart
            $existingItem = $this->db->select(
                'cart_items',
                '*',
                'cart_id = ? AND product_id = ?',
                [$cartId, $productId]
            )->fetch();

            if ($existingItem) {
                // Update quantity if product already in cart
                $newQuantity = $existingItem['quantity'] + $quantity;
                if ($newQuantity > $product['stock_quantity']) {
                    throw new Exception('Requested quantity exceeds available stock');
                }

                return $this->db->update(
                    'cart_items',
                    ['quantity' => $newQuantity],
                    'id = ?',
                    [$existingItem['id']]
                );
            }

            // Add new item to cart
            return $this->db->insert('cart_items', [
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        } catch (Exception $e) {
            $this->handleError('Add to cart failed', $e);
            return false;
        }
    }

    // Update cart item quantity
    public function updateItemQuantity($cartId, $productId, $quantity) {
        try {
            // Check product stock
            $product = $this->product->find($productId);
            if (!$product || $product['stock_quantity'] < $quantity) {
                throw new Exception('Requested quantity not available');
            }

            return $this->db->update(
                'cart_items',
                ['quantity' => $quantity],
                'cart_id = ? AND product_id = ?',
                [$cartId, $productId]
            );
        } catch (Exception $e) {
            $this->handleError('Update cart item quantity failed', $e);
            return false;
        }
    }

    // Remove item from cart
    public function removeItem($cartId, $productId) {
        try {
            return $this->db->delete(
                'cart_items',
                'cart_id = ? AND product_id = ?',
                [$cartId, $productId]
            );
        } catch (Exception $e) {
            $this->handleError('Remove from cart failed', $e);
            return false;
        }
    }

    // Clear cart
    public function clearCart($cartId) {
        try {
            return $this->db->delete(
                'cart_items',
                'cart_id = ?',
                [$cartId]
            );
        } catch (Exception $e) {
            $this->handleError('Clear cart failed', $e);
            return false;
        }
    }

    // Get cart total
    public function getTotal($cartId) {
        try {
            $sql = "SELECT SUM(ci.quantity * p.price) as total 
                    FROM cart_items ci 
                    LEFT JOIN products p ON ci.product_id = p.id 
                    WHERE ci.cart_id = ?";
            
            $result = $this->db->query($sql, [$cartId])->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            $this->handleError('Get cart total failed', $e);
            return 0;
        }
    }

    // Get cart item count
    public function getItemCount($cartId) {
        try {
            $sql = "SELECT SUM(quantity) as count 
                    FROM cart_items 
                    WHERE cart_id = ?";
            
            $result = $this->db->query($sql, [$cartId])->fetch();
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            $this->handleError('Get cart item count failed', $e);
            return 0;
        }
    }

    // Merge carts (e.g., when user logs in)
    public function mergeCarts($sessionCartId, $userCartId) {
        try {
            $this->db->beginTransaction();

            // Get items from session cart
            $sessionItems = $this->getItems($sessionCartId);

            // Add items to user cart
            foreach ($sessionItems as $item) {
                $this->addItem($userCartId, $item['product_id'], $item['quantity']);
            }

            // Clear and delete session cart
            $this->clearCart($sessionCartId);
            $this->delete($sessionCartId);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->handleError('Merge carts failed', $e);
            return false;
        }
    }

    // Validate cart items (check stock availability)
    public function validateItems($cartId) {
        try {
            $items = $this->getItems($cartId);
            $invalidItems = [];

            foreach ($items as $item) {
                if ($item['quantity'] > $item['stock_quantity']) {
                    $invalidItems[] = [
                        'product_id' => $item['product_id'],
                        'name' => $item['name'],
                        'requested' => $item['quantity'],
                        'available' => $item['stock_quantity']
                    ];
                }
            }

            return [
                'valid' => empty($invalidItems),
                'invalid_items' => $invalidItems
            ];
        } catch (Exception $e) {
            $this->handleError('Validate cart items failed', $e);
            return [
                'valid' => false,
                'invalid_items' => []
            ];
        }
    }

    // Apply coupon to cart
    public function applyCoupon($cartId, $couponCode) {
        try {
            // Get coupon details
            $coupon = $this->db->select(
                'coupons',
                '*',
                'code = ? AND is_active = ? AND (usage_limit > used_count OR usage_limit IS NULL) 
                AND (start_date IS NULL OR start_date <= NOW()) 
                AND (end_date IS NULL OR end_date >= NOW())',
                [$couponCode, 1]
            )->fetch();

            if (!$coupon) {
                throw new Exception('Invalid or expired coupon');
            }

            $cartTotal = $this->getTotal($cartId);

            // Validate minimum spend
            if ($coupon['minimum_spend'] && $cartTotal < $coupon['minimum_spend']) {
                throw new Exception('Cart total does not meet minimum spend requirement');
            }

            // Calculate discount
            $discount = $coupon['discount_type'] === 'percentage' 
                ? $cartTotal * ($coupon['discount_value'] / 100)
                : $coupon['discount_value'];

            // Apply maximum spend limit if set
            if ($coupon['maximum_spend']) {
                $discount = min($discount, $coupon['maximum_spend']);
            }

            return [
                'valid' => true,
                'discount' => $discount,
                'coupon' => $coupon
            ];

        } catch (Exception $e) {
            $this->handleError('Apply coupon failed', $e);
            return [
                'valid' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
