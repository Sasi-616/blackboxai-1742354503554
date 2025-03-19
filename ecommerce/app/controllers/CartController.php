<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

class CartController extends Controller {
    private $cart;
    private $product;

    public function __construct() {
        parent::__construct();
        $this->cart = new Cart();
        $this->product = new Product();
    }

    // Get cart contents
    public function index() {
        try {
            $userId = $this->user->isLoggedIn() ? $this->user->getCurrentUser()['id'] : null;
            $cart = $this->cart->getCart($userId);
            
            if (!$cart) {
                return $this->successResponse([
                    'items' => [],
                    'total' => 0,
                    'item_count' => 0
                ]);
            }

            $items = $this->cart->getItems($cart['id']);
            $total = $this->cart->getTotal($cart['id']);
            $itemCount = $this->cart->getItemCount($cart['id']);

            $this->successResponse([
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount
            ]);

        } catch (Exception $e) {
            $this->handleError('Get cart failed', $e);
            $this->errorResponse('Failed to fetch cart');
        }
    }

    // Add item to cart
    public function addItem() {
        try {
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['product_id', 'quantity'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Validate quantity
            if ($data['quantity'] < 1) {
                return $this->errorResponse('Quantity must be at least 1');
            }

            // Check product existence and stock
            $product = $this->product->find($data['product_id']);
            if (!$product) {
                return $this->errorResponse('Product not found');
            }

            if (!$product['is_active']) {
                return $this->errorResponse('Product is not available');
            }

            if ($product['stock_quantity'] < $data['quantity']) {
                return $this->errorResponse('Not enough stock available');
            }

            // Get or create cart
            $userId = $this->user->isLoggedIn() ? $this->user->getCurrentUser()['id'] : null;
            $cart = $this->cart->getCart($userId);

            // Add item to cart
            if (!$this->cart->addItem($cart['id'], $data['product_id'], $data['quantity'])) {
                throw new Exception('Failed to add item to cart');
            }

            // Get updated cart data
            $items = $this->cart->getItems($cart['id']);
            $total = $this->cart->getTotal($cart['id']);
            $itemCount = $this->cart->getItemCount($cart['id']);

            $this->successResponse([
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount
            ], 'Item added to cart');

        } catch (Exception $e) {
            $this->handleError('Add to cart failed', $e);
            $this->errorResponse('Failed to add item to cart');
        }
    }

    // Update cart item quantity
    public function updateQuantity() {
        try {
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['product_id', 'quantity'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Validate quantity
            if ($data['quantity'] < 0) {
                return $this->errorResponse('Invalid quantity');
            }

            // Get cart
            $userId = $this->user->isLoggedIn() ? $this->user->getCurrentUser()['id'] : null;
            $cart = $this->cart->getCart($userId);

            if ($data['quantity'] === 0) {
                // Remove item if quantity is 0
                if (!$this->cart->removeItem($cart['id'], $data['product_id'])) {
                    throw new Exception('Failed to remove item from cart');
                }
            } else {
                // Check product stock
                $product = $this->product->find($data['product_id']);
                if (!$product || $product['stock_quantity'] < $data['quantity']) {
                    return $this->errorResponse('Not enough stock available');
                }

                // Update quantity
                if (!$this->cart->updateItemQuantity($cart['id'], $data['product_id'], $data['quantity'])) {
                    throw new Exception('Failed to update quantity');
                }
            }

            // Get updated cart data
            $items = $this->cart->getItems($cart['id']);
            $total = $this->cart->getTotal($cart['id']);
            $itemCount = $this->cart->getItemCount($cart['id']);

            $this->successResponse([
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount
            ], 'Cart updated');

        } catch (Exception $e) {
            $this->handleError('Update quantity failed', $e);
            $this->errorResponse('Failed to update quantity');
        }
    }

    // Remove item from cart
    public function removeItem($productId) {
        try {
            // Get cart
            $userId = $this->user->isLoggedIn() ? $this->user->getCurrentUser()['id'] : null;
            $cart = $this->cart->getCart($userId);

            if (!$this->cart->removeItem($cart['id'], $productId)) {
                throw new Exception('Failed to remove item from cart');
            }

            // Get updated cart data
            $items = $this->cart->getItems($cart['id']);
            $total = $this->cart->getTotal($cart['id']);
            $itemCount = $this->cart->getItemCount($cart['id']);

            $this->successResponse([
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount
            ], 'Item removed from cart');

        } catch (Exception $e) {
            $this->handleError('Remove from cart failed', $e);
            $this->errorResponse('Failed to remove item from cart');
        }
    }

    // Clear cart
    public function clear() {
        try {
            // Get cart
            $userId = $this->user->isLoggedIn() ? $this->user->getCurrentUser()['id'] : null;
            $cart = $this->cart->getCart($userId);

            if (!$this->cart->clearCart($cart['id'])) {
                throw new Exception('Failed to clear cart');
            }

            $this->successResponse([
                'items' => [],
                'total' => 0,
                'item_count' => 0
            ], 'Cart cleared');

        } catch (Exception $e) {
            $this->handleError('Clear cart failed', $e);
            $this->errorResponse('Failed to clear cart');
        }
    }

    // Apply coupon to cart
    public function applyCoupon() {
        try {
            $data = $this->getPostData();
            
            if (!isset($data['code'])) {
                return $this->errorResponse('Coupon code is required');
            }

            // Get cart
            $userId = $this->user->isLoggedIn() ? $this->user->getCurrentUser()['id'] : null;
            $cart = $this->cart->getCart($userId);

            $result = $this->cart->applyCoupon($cart['id'], $data['code']);
            
            if (!$result['valid']) {
                return $this->errorResponse($result['message']);
            }

            // Get updated cart data with discount
            $items = $this->cart->getItems($cart['id']);
            $total = $this->cart->getTotal($cart['id']);
            $itemCount = $this->cart->getItemCount($cart['id']);

            $this->successResponse([
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount,
                'discount' => $result['discount'],
                'final_total' => $total - $result['discount']
            ], 'Coupon applied successfully');

        } catch (Exception $e) {
            $this->handleError('Apply coupon failed', $e);
            $this->errorResponse('Failed to apply coupon');
        }
    }

    // Validate cart before checkout
    public function validate() {
        try {
            // Get cart
            $userId = $this->user->isLoggedIn() ? $this->user->getCurrentUser()['id'] : null;
            $cart = $this->cart->getCart($userId);

            $validation = $this->cart->validateItems($cart['id']);
            
            if (!$validation['valid']) {
                return $this->errorResponse('Some items in your cart are no longer available', 400, [
                    'invalid_items' => $validation['invalid_items']
                ]);
            }

            $this->successResponse(null, 'Cart is valid');

        } catch (Exception $e) {
            $this->handleError('Validate cart failed', $e);
            $this->errorResponse('Failed to validate cart');
        }
    }

    // Merge guest cart with user cart after login
    public function mergeCarts($sessionCartId) {
        try {
            $this->requireLogin();
            
            $userId = $this->user->getCurrentUser()['id'];
            $userCart = $this->cart->getCart($userId);

            if (!$this->cart->mergeCarts($sessionCartId, $userCart['id'])) {
                throw new Exception('Failed to merge carts');
            }

            // Get updated cart data
            $items = $this->cart->getItems($userCart['id']);
            $total = $this->cart->getTotal($userCart['id']);
            $itemCount = $this->cart->getItemCount($userCart['id']);

            $this->successResponse([
                'items' => $items,
                'total' => $total,
                'item_count' => $itemCount
            ], 'Carts merged successfully');

        } catch (Exception $e) {
            $this->handleError('Merge carts failed', $e);
            $this->errorResponse('Failed to merge carts');
        }
    }
}
