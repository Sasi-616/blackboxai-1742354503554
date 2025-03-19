<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VellankiFoods - Fresh & Authentic Indian Groceries</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/" class="text-2xl font-bold text-green-600">
                    VellankiFoods
                </a>

                <!-- Search Bar -->
                <div class="flex-1 max-w-xl mx-8">
                    <div class="relative">
                        <input type="text" 
                               placeholder="Search for products..." 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <button class="absolute right-3 top-2.5 text-gray-400">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Account & Cart -->
                <div class="flex items-center space-x-6">
                    <a href="/account" class="flex items-center text-gray-700 hover:text-green-600">
                        <i class="fas fa-user mr-2"></i>
                        <span>Account</span>
                    </a>
                    <a href="/cart" class="flex items-center text-gray-700 hover:text-green-600">
                        <div class="relative">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                        </div>
                        <span>Cart</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-green-50 to-green-100 py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <div class="w-1/2">
                    <h1 class="text-5xl font-bold text-gray-800 mb-6">
                        Fresh & Authentic<br>Indian Groceries
                    </h1>
                    <p class="text-xl text-gray-600 mb-8">
                        Discover our wide range of authentic Indian groceries, spices, and ingredients.
                    </p>
                    <a href="/shop" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition duration-300">
                        Shop Now
                    </a>
                </div>
                <div class="w-1/2 p-4">
                    <div class="hero-image shadow-lg rounded-lg"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Popular Categories</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Category Card -->
                <a href="/category/spices" class="group">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 transform hover:-translate-y-1 hover:shadow-xl">
                        <div class="category-image"></div>
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-800 group-hover:text-green-600">Spices</h3>
                            <p class="text-gray-600">Authentic Indian spices</p>
                        </div>
                    </div>
                </a>
                <a href="/category/lentils" class="group">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 transform hover:-translate-y-1 hover:shadow-xl">
                        <div class="category-image"></div>
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-800 group-hover:text-green-600">Lentils</h3>
                            <p class="text-gray-600">Premium quality dals</p>
                        </div>
                    </div>
                </a>
                <a href="/category/rice" class="group">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 transform hover:-translate-y-1 hover:shadow-xl">
                        <div class="category-image"></div>
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-800 group-hover:text-green-600">Rice</h3>
                            <p class="text-gray-600">Finest basmati and more</p>
                        </div>
                    </div>
                </a>
                <a href="/category/snacks" class="group">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 transform hover:-translate-y-1 hover:shadow-xl">
                        <div class="category-image"></div>
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-800 group-hover:text-green-600">Snacks</h3>
                            <p class="text-gray-600">Authentic Indian snacks</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="bg-white py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Featured Products</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Product Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="product-image"></div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800">Basmati Rice</h3>
                        <p class="text-gray-600 mb-2">Premium long-grain rice</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-green-600">$12.99</span>
                            <button 
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 add-to-cart-btn"
                                data-product-id="1"
                                data-product-name="Basmati Rice"
                                data-product-price="12.99">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="product-image"></div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800">Toor Dal</h3>
                        <p class="text-gray-600 mb-2">Premium yellow lentils</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-green-600">$8.99</span>
                            <button 
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 add-to-cart-btn"
                                data-product-id="2"
                                data-product-name="Toor Dal"
                                data-product-price="8.99">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="product-image"></div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800">Garam Masala</h3>
                        <p class="text-gray-600 mb-2">Aromatic spice blend</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-green-600">$5.99</span>
                            <button 
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 add-to-cart-btn"
                                data-product-id="3"
                                data-product-name="Garam Masala"
                                data-product-price="5.99">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="product-image"></div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800">Masala Dosa Mix</h3>
                        <p class="text-gray-600 mb-2">Instant dosa batter</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-green-600">$6.99</span>
                            <button 
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 add-to-cart-btn"
                                data-product-id="4"
                                data-product-name="Masala Dosa Mix"
                                data-product-price="6.99">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature -->
                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Free Shipping</h3>
                    <p class="text-gray-600">On orders over $50</p>
                </div>
                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Quality Guarantee</h3>
                    <p class="text-gray-600">100% authentic products</p>
                </div>
                <div class="text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">24/7 Support</h3>
                    <p class="text-gray-600">Always here to help</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-xl font-semibold mb-4">VellankiFoods</h4>
                    <p class="text-gray-400">Your trusted source for authentic Indian groceries.</p>
                </div>
                <div>
                    <h4 class="text-xl font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="/about" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="/contact" class="text-gray-400 hover:text-white">Contact</a></li>
                        <li><a href="/shipping" class="text-gray-400 hover:text-white">Shipping Info</a></li>
                        <li><a href="/faq" class="text-gray-400 hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xl font-semibold mb-4">Contact Us</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-phone mr-2"></i> (555) 123-4567</li>
                        <li><i class="fas fa-envelope mr-2"></i> support@vellankifoods.com</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> 123 Main St, City, State</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xl font-semibold mb-4">Newsletter</h4>
                    <p class="text-gray-400 mb-4">Subscribe to get updates on new products and special offers.</p>
                    <form class="flex">
                        <input type="email" placeholder="Your email" class="px-4 py-2 rounded-l-lg w-full">
                        <button class="bg-green-600 text-white px-4 py-2 rounded-r-lg hover:bg-green-700">
                            Subscribe
                        </button>
                    </form>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 VellankiFoods. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Main JavaScript -->
    <script src="/js/main.js"></script>
</body>
</html>
