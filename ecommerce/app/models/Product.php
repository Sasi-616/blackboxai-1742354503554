<?php
require_once __DIR__ . '/Model.php';

class Product extends Model {
    protected $table = 'products';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'regular_price',
        'sale_price',
        'sku',
        'stock_quantity',
        'category_id',
        'image_url',
        'is_featured',
        'is_active'
    ];

    // Get featured products
    public function getFeatured($limit = 8) {
        try {
            return $this->db->select(
                $this->table,
                '*',
                'is_featured = ? AND is_active = ? AND stock_quantity > ?',
                [1, 1, 0],
                'created_at DESC',
                $limit
            )->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get featured products failed', $e);
            return [];
        }
    }

    // Search products
    public function search($term, $page = 1, $perPage = 12) {
        try {
            $term = "%$term%";
            return $this->paginate(
                $page,
                $perPage,
                'name LIKE ? OR description LIKE ? AND is_active = ?',
                [$term, $term, 1]
            );
        } catch (Exception $e) {
            $this->handleError('Product search failed', $e);
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    // Get products by category
    public function getByCategory($categoryId, $page = 1, $perPage = 12) {
        try {
            return $this->paginate(
                $page,
                $perPage,
                'category_id = ? AND is_active = ?',
                [$categoryId, 1]
            );
        } catch (Exception $e) {
            $this->handleError('Get products by category failed', $e);
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    // Get products on sale
    public function getOnSale($limit = 8) {
        try {
            return $this->db->select(
                $this->table,
                '*',
                'sale_price IS NOT NULL AND sale_price > 0 AND is_active = ? AND stock_quantity > ?',
                [1, 0],
                'sale_price ASC',
                $limit
            )->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get sale products failed', $e);
            return [];
        }
    }

    // Check if product is in stock
    public function isInStock($productId, $quantity = 1) {
        try {
            $product = $this->find($productId);
            return $product && $product['stock_quantity'] >= $quantity;
        } catch (Exception $e) {
            $this->handleError('Stock check failed', $e);
            return false;
        }
    }

    // Update stock quantity
    public function updateStock($productId, $quantity) {
        try {
            return $this->db->update(
                $this->table,
                ['stock_quantity' => $quantity],
                'id = ?',
                [$productId]
            ) > 0;
        } catch (Exception $e) {
            $this->handleError('Stock update failed', $e);
            return false;
        }
    }

    // Decrease stock quantity
    public function decreaseStock($productId, $quantity = 1) {
        try {
            $product = $this->find($productId);
            if (!$product || $product['stock_quantity'] < $quantity) {
                return false;
            }

            return $this->updateStock($productId, $product['stock_quantity'] - $quantity);
        } catch (Exception $e) {
            $this->handleError('Decrease stock failed', $e);
            return false;
        }
    }

    // Get related products
    public function getRelated($productId, $limit = 4) {
        try {
            $product = $this->find($productId);
            if (!$product) {
                return [];
            }

            return $this->db->select(
                $this->table,
                '*',
                'category_id = ? AND id != ? AND is_active = ? AND stock_quantity > ?',
                [$product['category_id'], $productId, 1, 0],
                'RAND()',
                $limit
            )->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get related products failed', $e);
            return [];
        }
    }

    // Generate unique slug
    public function generateSlug($name) {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $originalSlug = $slug;
            $counter = 1;

            while ($this->firstWhere('slug', $slug)) {
                $slug = $originalSlug . '-' . $counter++;
            }

            return $slug;
        } catch (Exception $e) {
            $this->handleError('Slug generation failed', $e);
            return false;
        }
    }

    // Get product with category
    public function getWithCategory($productId) {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM {$this->table} p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.id = ?";
            
            return $this->db->query($sql, [$productId])->fetch();
        } catch (Exception $e) {
            $this->handleError('Get product with category failed', $e);
            return false;
        }
    }

    // Get product images
    public function getImages($productId) {
        try {
            return $this->db->select(
                'product_images',
                '*',
                'product_id = ?',
                [$productId],
                'is_primary DESC'
            )->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get product images failed', $e);
            return [];
        }
    }

    // Get product reviews
    public function getReviews($productId, $page = 1, $perPage = 5) {
        try {
            $sql = "SELECT r.*, u.first_name, u.last_name 
                    FROM reviews r 
                    LEFT JOIN users u ON r.user_id = u.id 
                    WHERE r.product_id = ? AND r.status = 'approved'";
            
            $offset = ($page - 1) * $perPage;
            $sql .= " ORDER BY r.created_at DESC LIMIT $offset, $perPage";

            return $this->db->query($sql, [$productId])->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get product reviews failed', $e);
            return [];
        }
    }

    // Get average rating
    public function getAverageRating($productId) {
        try {
            $result = $this->db->select(
                'reviews',
                'AVG(rating) as avg_rating',
                'product_id = ? AND status = ?',
                [$productId, 'approved']
            )->fetch();

            return round($result['avg_rating'] ?? 0, 1);
        } catch (Exception $e) {
            $this->handleError('Get average rating failed', $e);
            return 0;
        }
    }
}
