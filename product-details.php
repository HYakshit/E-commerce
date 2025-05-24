<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Get product ID from query string
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no product ID provided, redirect to products page
if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// Fetch product details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// If product not found, redirect to products page
if (!$product) {
    header('Location: products.php');
    exit;
}

// Fetch related products
$stmt = $conn->prepare("SELECT * FROM products 
                        WHERE category_id = ? AND id != ? 
                        ORDER BY RAND() 
                        LIMIT 4");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll();

$page_title = $product['name'];
$page_script = "/js/cart.js";
require_once 'includes/header.php';
?>

<section class="product-detail">
    <div class="container">
        <div class="product-grid">
            <!-- Product Images -->
            <div class="product-images">
                <div class="product-main-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <?php if (!empty($product['gallery'])): ?>
                <div class="product-thumbnails">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="active">
                    <?php 
                    $gallery = json_decode($product['gallery'], true);
                    if (is_array($gallery)) {
                        foreach ($gallery as $img): 
                    ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php 
                        endforeach; 
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Content -->
            <div class="product-content">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-meta">
                    <span class="badge badge-primary"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                    <?php if ($product['in_stock']): ?>
                    <span class="badge badge-success">In Stock</span>
                    <?php else: ?>
                    <span class="badge badge-danger">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['rating'])): ?>
                <div class="product-rating">
                    <div class="rating-stars">
                        <?php 
                        $rating = $product['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <span class="rating-count">(<?php echo $product['rating_count'] ?? 0; ?> reviews)</span>
                </div>
                <?php endif; ?>
                
                <div class="product-price-detail">
                    <?php echo formatPrice($product['price']); ?>
                </div>
                
                <div class="product-description">
                    <?php echo $product['description']; ?>
                </div>
                
                <?php if ($product['in_stock']): ?>
                <form id="add-to-cart-form" action="cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="action" value="add_to_cart">
                    
                    <div class="product-actions">
                        <div class="quantity-input">
                            <button type="button" class="quantity-btn">-</button>
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            <button type="button" class="quantity-btn">+</button>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg add-to-cart-btn">Add to Cart</button>
                        <button type="button" class="wishlist-btn" data-tooltip="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="out-of-stock-message">
                    <p>This product is currently out of stock. Please check back later.</p>
                    <button type="button" class="btn btn-outline">Notify Me When Available</button>
                </div>
                <?php endif; ?>
                
                <div class="product-meta-info">
                    <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></p>
                    <?php if (!empty($product['brand'])): ?>
                    <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Product Details Tabs -->
        <div class="product-details-tabs">
            <div class="tabs-nav">
                <div class="tab-link active" data-tab="description">Description</div>
                <div class="tab-link" data-tab="specifications">Specifications</div>
                <div class="tab-link" data-tab="reviews">Reviews</div>
            </div>
            
            <div id="description" class="tab-content active">
                <?php echo $product['description']; ?>
            </div>
            
            <div id="specifications" class="tab-content">
                <?php if (!empty($product['specifications'])): ?>
                    <?php echo $product['specifications']; ?>
                <?php else: ?>
                    <p>No specifications available for this product.</p>
                <?php endif; ?>
            </div>
            
            <div id="reviews" class="tab-content">
                <?php if (!empty($product['reviews'])): ?>
                    <?php echo $product['reviews']; ?>
                <?php else: ?>
                    <p>No reviews yet. Be the first to review this product!</p>
                    <button class="btn btn-outline">Write a Review</button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (count($related_products) > 0): ?>
        <div class="related-products">
            <div class="section-title">
                <h2>Related Products</h2>
            </div>
            <div class="products-grid">
                <?php foreach ($related_products as $rel_product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($rel_product['image']); ?>" alt="<?php echo htmlspecialchars($rel_product['name']); ?>" class="product-img">
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($rel_product['name']); ?></h3>
                        <div class="product-price"><?php echo formatPrice($rel_product['price']); ?></div>
                    </div>
                    <div class="product-footer">
                        <form action="cart.php" method="POST" class="add-to-cart">
                            <input type="hidden" name="product_id" value="<?php echo $rel_product['id']; ?>">
                            <input type="hidden" name="action" value="add_to_cart">
                            <button type="submit" class="btn btn-primary btn-sm btn-block">Add to Cart</button>
                        </form>
                        <a href="product-details.php?id=<?php echo $rel_product['id']; ?>" class="view-details">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to current tab
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
