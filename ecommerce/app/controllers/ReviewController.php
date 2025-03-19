<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/User.php';

class ReviewController extends Controller {
    private $review;
    private $product;

    public function __construct() {
        parent::__construct();
        $this->review = new Review();
        $this->product = new Product();
    }

    // Get reviews for a product
    public function index($productId) {
        try {
            $params = $this->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 5;

            // Check if product exists
            if (!$this->product->find($productId)) {
                return $this->errorResponse('Product not found', 404);
            }

            // Get reviews with pagination
            $result = $this->review->getProductReviews($productId, $page, $perPage);

            // Get review statistics
            $stats = $this->review->getProductStats($productId);

            $this->successResponse([
                'reviews' => $result['reviews'],
                'stats' => $stats,
                'pagination' => $this->getPaginationData($page, $perPage, $result['total'])
            ]);

        } catch (Exception $e) {
            $this->handleError('Get reviews failed', $e);
            $this->errorResponse('Failed to fetch reviews');
        }
    }

    // Create new review
    public function create($productId) {
        try {
            $this->requireLogin();
            
            $data = $this->getPostData();
            
            // Validate required fields
            $required = ['rating', 'comment'];
            $missing = $this->validateRequired($data, $required);
            if (!empty($missing)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            // Validate rating
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                return $this->errorResponse('Rating must be between 1 and 5');
            }

            // Check if product exists
            if (!$this->product->find($productId)) {
                return $this->errorResponse('Product not found', 404);
            }

            // Check if user can review
            $userId = $this->user->getCurrentUser()['id'];
            $canReview = $this->review->canReview($userId, $productId);
            
            if (!$canReview['can_review']) {
                return $this->errorResponse($canReview['message']);
            }

            // Create review
            $reviewData = [
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $data['rating'],
                'comment' => $data['comment']
            ];

            if (!$this->review->createReview($reviewData)) {
                throw new Exception('Failed to create review');
            }

            $this->successResponse(null, 'Review submitted successfully');

        } catch (Exception $e) {
            $this->handleError('Create review failed', $e);
            $this->errorResponse('Failed to submit review');
        }
    }

    // Update review
    public function update($reviewId) {
        try {
            $this->requireLogin();
            
            $data = $this->getPostData();
            
            // Get review
            $review = $this->review->find($reviewId);
            if (!$review) {
                return $this->errorResponse('Review not found', 404);
            }

            // Check if user owns the review
            if ($review['user_id'] !== $this->user->getCurrentUser()['id']) {
                return $this->errorResponse('Permission denied', 403);
            }

            // Validate rating if provided
            if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
                return $this->errorResponse('Rating must be between 1 and 5');
            }

            // Update review
            if (!$this->review->update($reviewId, $data)) {
                throw new Exception('Failed to update review');
            }

            $this->successResponse(null, 'Review updated successfully');

        } catch (Exception $e) {
            $this->handleError('Update review failed', $e);
            $this->errorResponse('Failed to update review');
        }
    }

    // Delete review
    public function delete($reviewId) {
        try {
            $this->requireLogin();
            
            // Get review
            $review = $this->review->find($reviewId);
            if (!$review) {
                return $this->errorResponse('Review not found', 404);
            }

            // Check if user owns the review or is admin
            if ($review['user_id'] !== $this->user->getCurrentUser()['id'] && 
                !$this->user->hasRole('admin')) {
                return $this->errorResponse('Permission denied', 403);
            }

            if (!$this->review->delete($reviewId)) {
                throw new Exception('Failed to delete review');
            }

            $this->successResponse(null, 'Review deleted successfully');

        } catch (Exception $e) {
            $this->handleError('Delete review failed', $e);
            $this->errorResponse('Failed to delete review');
        }
    }

    // Get pending reviews (admin only)
    public function pending() {
        try {
            $this->requireRole('admin');
            
            $params = $this->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 10;

            $result = $this->review->getPendingReviews($page, $perPage);

            $this->successResponse([
                'reviews' => $result['reviews'],
                'pagination' => $this->getPaginationData($page, $perPage, $result['total'])
            ]);

        } catch (Exception $e) {
            $this->handleError('Get pending reviews failed', $e);
            $this->errorResponse('Failed to fetch pending reviews');
        }
    }

    // Approve review (admin only)
    public function approve($reviewId) {
        try {
            $this->requireRole('admin');
            
            if (!$this->review->updateStatus($reviewId, 'approved')) {
                throw new Exception('Failed to approve review');
            }

            $this->successResponse(null, 'Review approved successfully');

        } catch (Exception $e) {
            $this->handleError('Approve review failed', $e);
            $this->errorResponse('Failed to approve review');
        }
    }

    // Reject review (admin only)
    public function reject($reviewId) {
        try {
            $this->requireRole('admin');
            
            $data = $this->getPostData();
            $reason = isset($data['reason']) ? $data['reason'] : '';

            if (!$this->review->updateStatus($reviewId, 'rejected')) {
                throw new Exception('Failed to reject review');
            }

            // If reason provided, notify user
            if ($reason) {
                $review = $this->review->find($reviewId);
                $user = $this->user->find($review['user_id']);
                $this->sendReviewRejectionEmail($user['email'], $reason);
            }

            $this->successResponse(null, 'Review rejected successfully');

        } catch (Exception $e) {
            $this->handleError('Reject review failed', $e);
            $this->errorResponse('Failed to reject review');
        }
    }

    // Get user's reviews
    public function userReviews() {
        try {
            $this->requireLogin();
            
            $params = $this->getQueryParams();
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $perPage = isset($params['per_page']) ? (int)$params['per_page'] : 10;

            $userId = $this->user->getCurrentUser()['id'];
            $result = $this->review->getUserReviews($userId, $page, $perPage);

            $this->successResponse([
                'reviews' => $result['reviews'],
                'pagination' => $this->getPaginationData($page, $perPage, $result['total'])
            ]);

        } catch (Exception $e) {
            $this->handleError('Get user reviews failed', $e);
            $this->errorResponse('Failed to fetch user reviews');
        }
    }

    // Get top-rated products
    public function topRated() {
        try {
            $params = $this->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;

            $products = $this->review->getTopRatedProducts($limit);
            $this->successResponse($products);

        } catch (Exception $e) {
            $this->handleError('Get top rated products failed', $e);
            $this->errorResponse('Failed to fetch top rated products');
        }
    }

    // Get recent reviews
    public function recent() {
        try {
            $params = $this->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 5;

            $reviews = $this->review->getRecentReviews($limit);
            $this->successResponse($reviews);

        } catch (Exception $e) {
            $this->handleError('Get recent reviews failed', $e);
            $this->errorResponse('Failed to fetch recent reviews');
        }
    }

    // Send review rejection email
    private function sendReviewRejectionEmail($email, $reason) {
        $subject = "Review Status Update - " . SITE_NAME;
        $body = "
            <h2>Review Status Update</h2>
            <p>Your review has been rejected for the following reason:</p>
            <p>$reason</p>
            <p>If you have any questions, please contact our customer support.</p>
        ";

        $this->sendEmail($email, $subject, $body);
    }
}
