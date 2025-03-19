<?php
require_once __DIR__ . '/Model.php';

class Review extends Model {
    protected $table = 'reviews';
    
    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'status'
    ];

    // Create a new review
    public function createReview($data) {
        try {
            // Validate rating
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                throw new Exception('Rating must be between 1 and 5');
            }

            // Check if user has already reviewed this product
            $existingReview = $this->firstWhere(
                'product_id = ? AND user_id = ?',
                [$data['product_id'], $data['user_id']]
            );

            if ($existingReview) {
                throw new Exception('You have already reviewed this product');
            }

            // Set initial status
            $data['status'] = 'pending';

            return $this->create($data);
        } catch (Exception $e) {
            $this->handleError('Create review failed', $e);
            return false;
        }
    }

    // Get reviews for a product
    public function getProductReviews($productId, $page = 1, $perPage = 5, $status = 'approved') {
        try {
            $sql = "SELECT r.*, u.first_name, u.last_name 
                    FROM {$this->table} r 
                    LEFT JOIN users u ON r.user_id = u.id 
                    WHERE r.product_id = ? AND r.status = ? 
                    ORDER BY r.created_at DESC";

            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ?, ?";

            return [
                'reviews' => $this->db->query($sql, [$productId, $status, $offset, $perPage])->fetchAll(),
                'total' => $this->count('product_id = ? AND status = ?', [$productId, $status])
            ];
        } catch (Exception $e) {
            $this->handleError('Get product reviews failed', $e);
            return [
                'reviews' => [],
                'total' => 0
            ];
        }
    }

    // Get review statistics for a product
    public function getProductStats($productId) {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
                    FROM {$this->table}
                    WHERE product_id = ? AND status = ?";

            $stats = $this->db->query($sql, [$productId, 'approved'])->fetch();
            
            // Calculate percentages
            $total = (int)$stats['total_reviews'];
            if ($total > 0) {
                $stats['five_star_percent'] = round(($stats['five_star'] / $total) * 100);
                $stats['four_star_percent'] = round(($stats['four_star'] / $total) * 100);
                $stats['three_star_percent'] = round(($stats['three_star'] / $total) * 100);
                $stats['two_star_percent'] = round(($stats['two_star'] / $total) * 100);
                $stats['one_star_percent'] = round(($stats['one_star'] / $total) * 100);
            }

            $stats['average_rating'] = round($stats['average_rating'], 1);

            return $stats;
        } catch (Exception $e) {
            $this->handleError('Get product review stats failed', $e);
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0,
                'five_star_percent' => 0,
                'four_star_percent' => 0,
                'three_star_percent' => 0,
                'two_star_percent' => 0,
                'one_star_percent' => 0
            ];
        }
    }

    // Update review status
    public function updateStatus($reviewId, $status) {
        try {
            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                throw new Exception('Invalid status');
            }

            return $this->update($reviewId, ['status' => $status]);
        } catch (Exception $e) {
            $this->handleError('Update review status failed', $e);
            return false;
        }
    }

    // Get pending reviews
    public function getPendingReviews($page = 1, $perPage = 10) {
        try {
            $sql = "SELECT r.*, p.name as product_name, u.first_name, u.last_name 
                    FROM {$this->table} r 
                    LEFT JOIN products p ON r.product_id = p.id 
                    LEFT JOIN users u ON r.user_id = u.id 
                    WHERE r.status = 'pending' 
                    ORDER BY r.created_at DESC";

            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ?, ?";

            return [
                'reviews' => $this->db->query($sql, [$offset, $perPage])->fetchAll(),
                'total' => $this->count('status = ?', ['pending'])
            ];
        } catch (Exception $e) {
            $this->handleError('Get pending reviews failed', $e);
            return [
                'reviews' => [],
                'total' => 0
            ];
        }
    }

    // Get user's reviews
    public function getUserReviews($userId, $page = 1, $perPage = 10) {
        try {
            $sql = "SELECT r.*, p.name as product_name, p.image_url 
                    FROM {$this->table} r 
                    LEFT JOIN products p ON r.product_id = p.id 
                    WHERE r.user_id = ? 
                    ORDER BY r.created_at DESC";

            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ?, ?";

            return [
                'reviews' => $this->db->query($sql, [$userId, $offset, $perPage])->fetchAll(),
                'total' => $this->count('user_id = ?', [$userId])
            ];
        } catch (Exception $e) {
            $this->handleError('Get user reviews failed', $e);
            return [
                'reviews' => [],
                'total' => 0
            ];
        }
    }

    // Check if user can review product
    public function canReview($userId, $productId) {
        try {
            // Check if user has purchased the product
            $sql = "SELECT COUNT(*) as count 
                    FROM orders o 
                    JOIN order_items oi ON o.id = oi.order_id 
                    WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'";

            $result = $this->db->query($sql, [$userId, $productId])->fetch();
            
            if ($result['count'] == 0) {
                return [
                    'can_review' => false,
                    'message' => 'You must purchase this product before reviewing it'
                ];
            }

            // Check if user has already reviewed
            $existingReview = $this->firstWhere(
                'product_id = ? AND user_id = ?',
                [$productId, $userId]
            );

            if ($existingReview) {
                return [
                    'can_review' => false,
                    'message' => 'You have already reviewed this product'
                ];
            }

            return [
                'can_review' => true,
                'message' => 'You can review this product'
            ];

        } catch (Exception $e) {
            $this->handleError('Check review permission failed', $e);
            return [
                'can_review' => false,
                'message' => 'Error checking review permission'
            ];
        }
    }

    // Get top-rated products
    public function getTopRatedProducts($limit = 10) {
        try {
            $sql = "SELECT p.*, 
                    COUNT(r.id) as review_count,
                    AVG(r.rating) as average_rating 
                    FROM products p 
                    LEFT JOIN {$this->table} r ON p.id = r.product_id 
                    WHERE r.status = 'approved' 
                    GROUP BY p.id 
                    HAVING review_count >= 5 
                    ORDER BY average_rating DESC, review_count DESC 
                    LIMIT ?";

            return $this->db->query($sql, [$limit])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get top rated products failed', $e);
            return [];
        }
    }

    // Get recent reviews
    public function getRecentReviews($limit = 5) {
        try {
            $sql = "SELECT r.*, p.name as product_name, p.image_url, 
                    u.first_name, u.last_name 
                    FROM {$this->table} r 
                    LEFT JOIN products p ON r.product_id = p.id 
                    LEFT JOIN users u ON r.user_id = u.id 
                    WHERE r.status = 'approved' 
                    ORDER BY r.created_at DESC 
                    LIMIT ?";

            return $this->db->query($sql, [$limit])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get recent reviews failed', $e);
            return [];
        }
    }
}
