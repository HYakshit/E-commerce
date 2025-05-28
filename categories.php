<?php
$page_title = "Explore Categories";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Get categories 
$cat_stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();

// Category filter
$params = [];
$where_clauses = [];
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_clauses[] = "category_id = ?";
    $params[] = $_GET['category'];
}

// Build the WHERE clause
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = "";

switch ($sort) {
    case 'price_low':
        $order_by = " ORDER BY price ASC";
        break;
    case 'price_high':
        $order_by = " ORDER BY price DESC";
        break;
    case 'oldest':
        $order_by = " ORDER BY created_at ASC";
        break;
    case 'newest':
    default:
        $order_by = " ORDER BY created_at DESC";
        break;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total products count for pagination
$count_sql = "SELECT COUNT(*) FROM products" . $where_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "SELECT * FROM products" . $where_sql . $order_by . " LIMIT " . $per_page . " OFFSET " . $offset;
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Current URL without pagination for pagination links
$current_url = strtok($_SERVER["REQUEST_URI"], '?');
$query_string = $_GET;
unset($query_string['page']);
$base_url = $current_url . '?' . http_build_query($query_string) . (empty($query_string) ? '' : '&');

require_once 'includes/header.php';
?>

<section class="products-page">
    <div class="container">
        <div class="row">

            <!-- Products Grid -->
            <div class="col-12 col-md-9">
                <div class="products-header">
                    <h2><?php echo $page_title; ?></h2>
                    <div class="list-group">
                        <a href="categories.php"
                            class="list-group-item bg-warning list-group-item-action <?php echo empty($_GET['category']) ? 'active' : ''; ?>">
                            Show All Categories
                        </a>
                        <?php foreach ($categories as $category): ?>
                        <a href="categories.php?category=<?php echo $category['id']; ?>"
                            class="list-group-item list-group-item-action d-flex  align-items-center gap-3 <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'active' : ''; ?>">
                            <img src="<?php echo !empty($category['image']) ? htmlspecialchars($category['image']) : 'https://images.unsplash.com/photo-1534452203293-494d7ddbf7e0'; ?>"
                                style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="products-sorting">
                        <label>Sort by:</label>
                        <select id="sort-products" class="form-control">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low
                                to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price:
                                High to Low</option>
                        </select>
                    </div>
                </div>

                <?php if (count($products) > 0): ?>
                <div class="products-grid mt-2">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-details">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img"> </a>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-category">
                                <?php
                                        $cat_stmt = $conn
                                            ->prepare("SELECT name FROM categories WHERE id = ?");
                                        $cat_stmt->execute([$product['category_id']]);
                                        $category = $cat_stmt->fetch();
                                        echo htmlspecialchars($category['name'] ?? 'Uncategorized');
                                        ?>
                            </div>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                        </div>
                        <div class="product-footer">
                            <form action="cart.php" method="POST" class="add-to-cart">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add_to_cart">
                                <button type="submit" class="btn btn-primary btn-sm btn-block">Add to Cart</button>
                                <div class="text-center mt-1"> <a
                                        href="product-details.php?id=<?php echo $product['id']; ?>"
                                        class="btn btn-outline-secondary">View
                                        details</a></div>
                            </form>

                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php echo generatePaginationLinks($page, $total_pages, $base_url); ?>
                </div>

                <?php else: ?>
                <div class="no-products">
                    <p>No products found in this category.</p>
                    <a href="categories.php" class="btn btn-outline">Show All</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sort change
    const sortSelect = document.getElementById('sort-products');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', this.value);
            window.location.href = currentUrl.toString();
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>