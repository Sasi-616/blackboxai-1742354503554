<?php
require_once __DIR__ . '/Model.php';

class Category extends Model {
    protected $table = 'categories';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'parent_id'
    ];

    // Get category with parent
    public function getWithParent($categoryId) {
        try {
            $sql = "SELECT c.*, p.name as parent_name 
                    FROM {$this->table} c 
                    LEFT JOIN {$this->table} p ON c.parent_id = p.id 
                    WHERE c.id = ?";
            
            return $this->db->query($sql, [$categoryId])->fetch();
        } catch (Exception $e) {
            $this->handleError('Get category with parent failed', $e);
            return false;
        }
    }

    // Get all categories with their parents
    public function getAllWithParents() {
        try {
            $sql = "SELECT c.*, p.name as parent_name 
                    FROM {$this->table} c 
                    LEFT JOIN {$this->table} p ON c.parent_id = p.id 
                    ORDER BY c.parent_id, c.name";
            
            return $this->db->query($sql)->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get all categories with parents failed', $e);
            return [];
        }
    }

    // Get category tree
    public function getTree() {
        try {
            $categories = $this->getAllWithParents();
            return $this->buildTree($categories);
        } catch (Exception $e) {
            $this->handleError('Get category tree failed', $e);
            return [];
        }
    }

    // Build category tree from flat array
    private function buildTree(array $categories, $parentId = null) {
        $branch = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }
        
        return $branch;
    }

    // Get subcategories
    public function getSubcategories($parentId) {
        try {
            return $this->where('parent_id', $parentId);
        } catch (Exception $e) {
            $this->handleError('Get subcategories failed', $e);
            return [];
        }
    }

    // Get root categories (categories without parent)
    public function getRootCategories() {
        try {
            return $this->where('parent_id', null);
        } catch (Exception $e) {
            $this->handleError('Get root categories failed', $e);
            return [];
        }
    }

    // Get category path (breadcrumb)
    public function getCategoryPath($categoryId) {
        try {
            $path = [];
            $current = $this->find($categoryId);
            
            while ($current) {
                array_unshift($path, $current);
                $current = $current['parent_id'] ? $this->find($current['parent_id']) : null;
            }
            
            return $path;
        } catch (Exception $e) {
            $this->handleError('Get category path failed', $e);
            return [];
        }
    }

    // Get featured categories
    public function getFeatured($limit = 4) {
        try {
            return $this->db->select(
                $this->table,
                '*',
                'image_url IS NOT NULL',
                [],
                'RAND()',
                $limit
            )->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get featured categories failed', $e);
            return [];
        }
    }

    // Get category with product count
    public function getWithProductCount($categoryId) {
        try {
            $sql = "SELECT c.*, COUNT(p.id) as product_count 
                    FROM {$this->table} c 
                    LEFT JOIN products p ON c.id = p.category_id 
                    WHERE c.id = ? 
                    GROUP BY c.id";
            
            return $this->db->query($sql, [$categoryId])->fetch();
        } catch (Exception $e) {
            $this->handleError('Get category with product count failed', $e);
            return false;
        }
    }

    // Get all categories with product counts
    public function getAllWithProductCounts() {
        try {
            $sql = "SELECT c.*, COUNT(p.id) as product_count 
                    FROM {$this->table} c 
                    LEFT JOIN products p ON c.id = p.category_id 
                    GROUP BY c.id 
                    ORDER BY c.name";
            
            return $this->db->query($sql)->fetchAll();
        } catch (Exception $e) {
            $this->handleError('Get all categories with product counts failed', $e);
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

    // Check if category has children
    public function hasChildren($categoryId) {
        try {
            return $this->count('parent_id = ?', [$categoryId]) > 0;
        } catch (Exception $e) {
            $this->handleError('Check for children failed', $e);
            return false;
        }
    }

    // Get category products
    public function getProducts($categoryId, $page = 1, $perPage = 12, $orderBy = 'created_at DESC') {
        try {
            $product = new Product();
            return $product->paginate(
                $page,
                $perPage,
                'category_id = ? AND is_active = ?',
                [$categoryId, 1],
                $orderBy
            );
        } catch (Exception $e) {
            $this->handleError('Get category products failed', $e);
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    // Delete category and optionally move products to another category
    public function deleteWithProducts($categoryId, $moveToCategory = null) {
        try {
            $this->db->beginTransaction();

            if ($moveToCategory) {
                // Move products to another category
                $this->db->update(
                    'products',
                    ['category_id' => $moveToCategory],
                    'category_id = ?',
                    [$categoryId]
                );
            }

            // Delete the category
            if (!$this->delete($categoryId)) {
                throw new Exception('Failed to delete category');
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->handleError('Delete category with products failed', $e);
            return false;
        }
    }
}
