<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

class CategoryController extends Controller {
    private $category;
    private $product;

    public function __construct() {
        parent::__construct();
        $this->category = new Category();
        $this->product = new Product();
    }

    // Get all categories
    public function index() {
        try {
            $params = $this->getQueryParams();
            $format = isset($params['format']) ? $params['format'] : 'flat';

            if ($format === 'tree') {
                $categories = $this->category->getTree();
            } else {
                $categories = $this->category->getAllWithProductCounts();
            }

            $this->successResponse($categories);

        } catch (Exception $e) {
            $this->handleError('Get categories failed', $e);
            $this->errorResponse('Failed to fetch categories');
        }
    }

    // Get single category with products
    public function show($categoryId) {
        try {
            $category = $this->category->getWithProductCount($categoryId);
            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            // Get category path (breadcrumb)
            $category['path'] = $this->category->getCategoryPath($categoryId);

            // Get subcategories
            $category['subcategories'] = $this->category->getSubcategories($categoryId);

            // Get products with pagination
            $params = $this->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 12;
            
            // Sort options
            $sortBy = isset($params['sort']) ? $params['sort'] : 'created_at';
            $direction = isset($params['direction']) && strtolower($params['direction']) === 'asc' ? 'ASC' : 'DESC';
            $orderBy = "$sortBy $direction";

            $products = $this->category->getProducts($categoryId, $page, $perPage, $orderBy);
            $category['products'] = $products;

            $this->successResponse($category);

        } catch (Exception $e) {
            $this->handleError('Get category failed', $e);
            $this->errorResponse('Failed to fetch category details');
        }
    }

    // Create new category (admin only)
    public function create() {
        try {
            $this->requireRole('admin');
            
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['name'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Generate slug
            $data['slug'] = $this->category->generateSlug($data['name']);

            // Handle image upload
            if (isset($_FILES['image'])) {
                $imageName = $this->handleFileUpload($_FILES['image']);
                if ($imageName) {
                    $data['image_url'] = $imageName;
                }
            }

            // Create category
            $categoryId = $this->category->create($data);
            if (!$categoryId) {
                throw new Exception('Failed to create category');
            }

            $category = $this->category->getWithParent($categoryId);
            $this->successResponse($category, 'Category created successfully');

        } catch (Exception $e) {
            $this->handleError('Create category failed', $e);
            $this->errorResponse('Failed to create category');
        }
    }

    // Update category (admin only)
    public function update($categoryId) {
        try {
            $this->requireRole('admin');
            
            $data = $this->getPostData();
            
            // Check if category exists
            if (!$this->category->find($categoryId)) {
                return $this->errorResponse('Category not found', 404);
            }

            // Handle image upload
            if (isset($_FILES['image'])) {
                $imageName = $this->handleFileUpload($_FILES['image']);
                if ($imageName) {
                    $data['image_url'] = $imageName;
                }
            }

            // Update slug if name changed
            if (isset($data['name'])) {
                $data['slug'] = $this->category->generateSlug($data['name']);
            }

            // Prevent circular parent reference
            if (isset($data['parent_id']) && $data['parent_id'] == $categoryId) {
                return $this->errorResponse('Category cannot be its own parent');
            }

            // Update category
            if (!$this->category->update($categoryId, $data)) {
                throw new Exception('Failed to update category');
            }

            $category = $this->category->getWithParent($categoryId);
            $this->successResponse($category, 'Category updated successfully');

        } catch (Exception $e) {
            $this->handleError('Update category failed', $e);
            $this->errorResponse('Failed to update category');
        }
    }

    // Delete category (admin only)
    public function delete($categoryId) {
        try {
            $this->requireRole('admin');
            
            // Check if category has children
            if ($this->category->hasChildren($categoryId)) {
                return $this->errorResponse('Cannot delete category with subcategories');
            }

            $data = $this->getPostData();
            $moveToCategory = isset($data['move_to']) ? $data['move_to'] : null;

            // Delete category and optionally move products
            if (!$this->category->deleteWithProducts($categoryId, $moveToCategory)) {
                throw new Exception('Failed to delete category');
            }

            $this->successResponse(null, 'Category deleted successfully');

        } catch (Exception $e) {
            $this->handleError('Delete category failed', $e);
            $this->errorResponse('Failed to delete category');
        }
    }

    // Get featured categories
    public function featured() {
        try {
            $params = $this->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 4;

            $categories = $this->category->getFeatured($limit);
            
            // Add product count to each category
            foreach ($categories as &$category) {
                $stats = $this->category->getWithProductCount($category['id']);
                $category['product_count'] = $stats['product_count'];
            }

            $this->successResponse($categories);

        } catch (Exception $e) {
            $this->handleError('Get featured categories failed', $e);
            $this->errorResponse('Failed to fetch featured categories');
        }
    }

    // Get category breadcrumb
    public function breadcrumb($categoryId) {
        try {
            $path = $this->category->getCategoryPath($categoryId);
            if (empty($path)) {
                return $this->errorResponse('Category not found', 404);
            }

            $this->successResponse($path);

        } catch (Exception $e) {
            $this->handleError('Get category breadcrumb failed', $e);
            $this->errorResponse('Failed to fetch category breadcrumb');
        }
    }

    // Get root categories
    public function root() {
        try {
            $categories = $this->category->getRootCategories();
            
            // Add product count and subcategories to each category
            foreach ($categories as &$category) {
                $stats = $this->category->getWithProductCount($category['id']);
                $category['product_count'] = $stats['product_count'];
                $category['subcategories'] = $this->category->getSubcategories($category['id']);
            }

            $this->successResponse($categories);

        } catch (Exception $e) {
            $this->handleError('Get root categories failed', $e);
            $this->errorResponse('Failed to fetch root categories');
        }
    }
}
