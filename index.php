<?php
$page_title = "Home";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Get featured products
$featured_condition = dbBool(true); // This function handles boolean values for different databases
$stmt = $conn->prepare("SELECT * FROM products WHERE featured = $featured_condition ORDER BY id DESC LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Get latest products
$stmt = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
$stmt->execute();
$latest_products = $stmt->fetchAll();

// Get product categories
$stmt = $conn->prepare("SELECT * FROM categories LIMIT 6");
$stmt->execute();
$categories = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="position-relative">
    <div class="bg-dark position-absolute top-0 start-0 w-100 h-100 opacity-50"></div>
    <div class="container position-relative py-5">
        <div class="row min-vh-50 align-items-center text-center text-white py-5" style="min-height: 500px;">
            <div class="col-lg-8 mx-auto">
                <h1 class="display-4 fw-bold mb-4">Shop the Latest Trends</h1>
                <p class="lead mb-4">Discover our curated collection of premium products at unbeatable prices</p>
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                    <a href="products.php" class="btn btn-primary btn-lg px-4 me-sm-3">Shop Now</a>
                    <a href="categories.php" class="btn btn-outline-light btn-lg px-4">Explore Categories</a>
                </div>
            </div>
        </div>
    </div>
    <img src="https://images.unsplash.com/photo-1483181957632-8bda974cbc91" alt="Shop Hero"
        class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" style="z-index: -1;">
</section>

<!-- Featured Products Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Featured Products</h2>
            <div class="border-bottom border-3 border-primary mx-auto" style="width: 50px;"></div>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($featured_products as $product): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <small class="text-muted">
                                <?php
                                    $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
                                    $cat_stmt->execute([$product['category_id']]);
                                    $category = $cat_stmt->fetch();
                                    echo htmlspecialchars($category['name'] ?? 'Uncategorized');
                                    ?>
                            </small>
                            <span class="badge bg-primary"><?php echo formatPrice($product['price']); ?></span>
                        </div>
                        <h5 class="card-title mb-3"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <div class="mt-auto pt-3 d-grid gap-2">
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add_to_cart">
                                <button type="submit" class="btn btn-primary w-100">Add to Cart</button>
                            </form>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>"
                                class="btn btn-outline-secondary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="products.php" class="btn btn-outline-primary">View All Products</a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Shop by Category</h2>
            <div class="border-bottom border-3 border-primary mx-auto" style="width: 50px;"></div>
        </div>
        <div class=" owl-carousel">
            <?php foreach ($categories as $category): ?>
            <div class="">
                <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                        <img src="<?php echo !empty($category['image']) ? htmlspecialchars($category['image']) : 'https://images.unsplash.com/photo-1534452203293-494d7ddbf7e0'; ?>"
                            class="card-img" alt="<?php echo htmlspecialchars($category['name']); ?>"
                            style="height: 200px; object-fit: cover;">
                        <div
                            class="position-absolute top-0 left-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center">
                            <h4 class="text-white fw-bold"><?php echo htmlspecialchars($category['name']); ?></h4>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Latest Products Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Latest Arrivals</h2>
            <div class="border-bottom border-3 border-primary mx-auto" style="width: 50px;"></div>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($latest_products as $product): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="position-absolute end-0 top-0 p-2">
                        <span class="badge bg-danger">New</span>
                    </div>
                    <a href="product-details.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            style="height: 200px; object-fit: cover;"></a>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <small class="text-muted">
                                <?php
                                    $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
                                    $cat_stmt->execute([$product['category_id']]);
                                    $category = $cat_stmt->fetch();
                                    echo htmlspecialchars($category['name'] ?? 'Uncategorized');
                                    ?>
                            </small>
                            <span class="badge bg-primary"><?php echo formatPrice($product['price']); ?></span>
                        </div>
                        <h5 class="card-title mb-3"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <div class="mt-auto pt-3 d-grid gap-2">
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add_to_cart">
                                <button type="submit" class="btn btn-primary w-100">Add to Cart</button>
                            </form>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>"
                                class="btn btn-outline-secondary">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="products.php?sort=newest" class="btn btn-outline-primary">View All New Arrivals</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 text-center">
            <div class="col">
                <div class="card h-100 border-0 bg-transparent">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-truck fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Free Shipping</h5>
                        <p class="card-text text-muted">Free shipping on all orders over $50</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-0 bg-transparent">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-undo fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Easy Returns</h5>
                        <p class="card-text text-muted">30-day easy return policy</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-0 bg-transparent">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-lock fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Secure Payments</h5>
                        <p class="card-text text-muted">Your payment information is always safe</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 border-0 bg-transparent">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-headset fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">24/7 Support</h5>
                        <p class="card-text text-muted">Our support team is always available</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>