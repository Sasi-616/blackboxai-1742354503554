# E-commerce Website

A fully functional e-commerce website built with PHP, MySQL, and modern frontend technologies.

## Features

- Responsive design using Tailwind CSS
- User authentication and profile management
- Product catalog with categories and search
- Shopping cart functionality
- Order processing and management
- Product reviews and ratings
- Admin dashboard with sales analytics
- Secure payment processing
- Email notifications
- And more...

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

## Installation

1. Clone or download the repository to your web server directory.

2. Create a MySQL database and import the schema:
```bash
mysql -u your_username -p your_database_name < sql/database.sql
```

3. Configure the application:
   - Update the database credentials in `app/config.php`
   - Configure email settings for notifications
   - Set up payment gateway credentials if needed

4. Set up the required directories:
```bash
mkdir -p public/assets/uploads
mkdir -p logs
chmod 777 public/assets/uploads logs
```

5. Start the development server:
```bash
php -S localhost:8000 server.php
```

## Directory Structure

```
ecommerce/
├── app/
│   ├── config.php
│   ├── db.php
│   ├── controllers/
│   └── models/
├── public/
│   ├── index.php
│   ├── api.php
│   ├── assets/
│   ├── css/
│   └── js/
├── sql/
│   └── database.sql
├── logs/
├── server.php
└── README.md
```

## API Endpoints

### Products
- `GET /api/products` - Get all products
- `GET /api/products/{id}` - Get product details
- `POST /api/products` - Create new product (admin)
- `PUT /api/products/{id}` - Update product (admin)
- `DELETE /api/products/{id}` - Delete product (admin)

### Categories
- `GET /api/categories` - Get all categories
- `GET /api/categories/{id}` - Get category details
- `POST /api/categories` - Create new category (admin)
- `PUT /api/categories/{id}` - Update category (admin)
- `DELETE /api/categories/{id}` - Delete category (admin)

### Users
- `POST /api/users` - Register new user
- `POST /api/users/login` - User login
- `POST /api/users/logout` - User logout
- `GET /api/users/profile` - Get user profile
- `PUT /api/users/profile` - Update user profile

### Cart
- `GET /api/cart` - Get cart contents
- `POST /api/cart` - Add item to cart
- `PUT /api/cart` - Update cart item quantity
- `DELETE /api/cart/{product_id}` - Remove item from cart
- `POST /api/cart/clear` - Clear cart

### Orders
- `GET /api/orders` - Get all orders (admin)
- `POST /api/orders` - Create new order
- `GET /api/orders/{id}` - Get order details
- `PUT /api/orders/{id}/status` - Update order status (admin)

### Reviews
- `GET /api/reviews?product_id={id}` - Get product reviews
- `POST /api/reviews/{product_id}` - Create new review
- `PUT /api/reviews/{id}` - Update review
- `DELETE /api/reviews/{id}` - Delete review

### Admin
- `GET /api/admin` - Get dashboard statistics
- `GET /api/admin/sales` - Get sales report
- `GET /api/admin/inventory` - Get inventory report
- `GET /api/admin/export` - Export orders to CSV
- `GET /api/admin/logs` - Get system logs
- `GET /api/admin/system` - Get system information

## Security Features

1. Authentication and Authorization
   - Secure password hashing
   - Role-based access control
   - Session management
   - CSRF protection

2. Data Security
   - Prepared statements for SQL queries
   - Input validation and sanitization
   - XSS protection
   - File upload validation

3. API Security
   - CORS configuration
   - Rate limiting
   - Request validation
   - Error handling

## Frontend Features

1. Responsive Design
   - Mobile-first approach using Tailwind CSS
   - Optimized for all screen sizes
   - Modern and clean interface

2. User Experience
   - Dynamic search functionality
   - Real-time cart updates
   - Form validation
   - Loading states and animations
   - Toast notifications

3. Performance
   - Optimized images
   - Minified assets
   - Caching implementation
   - Lazy loading

## Admin Features

1. Dashboard
   - Sales analytics
   - Order management
   - Inventory tracking
   - User management

2. Content Management
   - Product management
   - Category management
   - Review moderation
   - Banner management

3. Reports
   - Sales reports
   - Inventory reports
   - User activity
   - System logs

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please email support@vellankifoods.com or create an issue in the repository.
