// Main JavaScript for VellankiFoods

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initializeSearch();
    initializeCart();
    initializeProductActions();
    initializeNewsletter();
});

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('input[placeholder="Search for products..."]');
    if (!searchInput) return;

    let searchTimeout;
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = e.target.value.trim();
            if (searchTerm) {
                showSearchSuggestions(searchTerm);
            }
        }, 500);
    });
}

// Cart functionality
function initializeCart() {
    // Initialize cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };
    updateCartDisplay(cart);

    // Add click event listeners to all "Add to Cart" buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productData = {
                id: this.dataset.productId,
                name: this.dataset.productName,
                price: parseFloat(this.dataset.productPrice),
                quantity: 1
            };
            
            addToCart(productData);
            animateAddToCart(this);
        });
    });

    // Add cart icon click handler
    const cartIcon = document.querySelector('a[href="/cart"]');
    if (cartIcon) {
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            showCart();
        });
    }
}

function addToCart(product) {
    let cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };
    
    // Check if product already exists in cart
    const existingItem = cart.items.find(item => item.id === product.id);
    
    if (existingItem) {
        existingItem.quantity += product.quantity;
    } else {
        cart.items.push(product);
    }
    
    // Update cart total
    cart.total = cart.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    
    // Save to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update display
    updateCartDisplay(cart);
    
    // Show success message
    showToast(`Added ${product.name} to cart`, 'success');
}

function updateCartDisplay(cart) {
    // Update cart count
    const cartCount = document.querySelector('.fa-shopping-cart + span');
    if (cartCount) {
        const totalItems = cart.items.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = totalItems;
        
        // Add animation class if items were added
        if (totalItems > 0) {
            cartCount.classList.add('animate-bounce');
            setTimeout(() => cartCount.classList.remove('animate-bounce'), 1000);
        }
    }
}

function showCart() {
    const cart = JSON.parse(localStorage.getItem('cart')) || { items: [], total: 0 };
    
    if (cart.items.length === 0) {
        showToast('Your cart is empty', 'info');
        return;
    }

    const itemsList = cart.items.map(item => 
        `${item.name} (${item.quantity}x) - $${(item.price * item.quantity).toFixed(2)}`
    ).join('\n');

    showToast(`Cart Total: $${cart.total.toFixed(2)}`, 'info');
}

// Product actions
function initializeProductActions() {
    // Add hover effects to product cards
    document.querySelectorAll('.bg-white').forEach(card => {
        card.classList.add('hover-scale');
    });
}

// Newsletter subscription
function initializeNewsletter() {
    const form = document.querySelector('footer form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const emailInput = this.querySelector('input[type="email"]');
        if (emailInput && emailInput.value) {
            showToast('Thank you for subscribing!', 'success');
            emailInput.value = '';
        }
    });
}

// Toast notifications
function showToast(message, type = 'info') {
    // Remove existing toasts
    document.querySelectorAll('.toast').forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast toast-${type} fade-in`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add to cart animation
function animateAddToCart(button) {
    button.classList.add('loading');
    button.disabled = true;
    
    setTimeout(() => {
        button.classList.remove('loading');
        button.disabled = false;
    }, 500);
}

// Search suggestions
function showSearchSuggestions(term) {
    const suggestions = [
        'Basmati Rice',
        'Toor Dal',
        'Garam Masala',
        'Turmeric Powder',
        'Cumin Seeds'
    ].filter(item => item.toLowerCase().includes(term.toLowerCase()));

    if (suggestions.length > 0) {
        showToast(`Try searching for: ${suggestions[0]}`, 'info');
    }
}

// Category navigation
document.querySelectorAll('a[href^="/category/"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const category = this.getAttribute('href').split('/').pop();
        showToast(`Browsing ${category} category`, 'info');
    });
});
