<?php
$page_title = "Search Results";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Redirect if no search query is provided
if (empty($search_query)) {
    header('Location: index.php');
    exit;
}

// Get categories for filter
$cat_stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();

// Initialize query parameters
$params = ['%' . $search_query . '%', '%' . $search_query . '%', '%' . $search_query . '%'];
$where_clauses = ['(name LIKE ? OR description LIKE ? OR tags LIKE ?)'];

// Category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_clauses[] = "category_id = ?";
    $params[] = $_GET['category'];
}

// Price range filter
if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $where_clauses[] = "price >= ?";
    $params[] = $_GET['min_price'];
}

if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $where_clauses[] = "price <= ?";
    $params[] = $_GET['max_price'];
}

// Build the WHERE clause
$where_sql = ' WHERE ' . implode(' AND ', $where_clauses);

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$order_by = "";

switch ($sort) {
    case 'price_low':
        $order_by = " ORDER BY price ASC";
        break;
    case 'price_high':
        $order_by = " ORDER BY price DESC";
        break;
    case 'newest':
        $order_by = " ORDER BY created_at DESC";
        break;
    case 'relevance':
    default:
        // For relevance, we might use a custom scoring (simplified here)
        $order_by = " ORDER BY (
            CASE
                WHEN name LIKE ? THEN 3
                WHEN description LIKE ? THEN 2
                WHEN tags LIKE ? THEN 1
                ELSE 0
            END
        ) DESC, name ASC";
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
        $params[] = '%' . $search_query . '%';
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

<section class="search-results">
    <div class="container">
        <div class="search-header">
            <h1>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>
            <p><?php echo $total_products; ?> products found</p>
        </div>
        
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-12 col-md-3">
                <div class="search-filters">
                    <h3 class="filters-title">Filters</h3>
                    <form action="search.php" method="GET">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                        
                        <div class="filter-group">
                            <label class="filter-label">Category</label>
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Price Range</label>
                            <div class="price-range">
                                <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                                <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                    </form>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-12 col-md-9">
                <?php if (count($products) > 0): ?>
                <div class="products-header">
                    <div class="products-sorting">
                        <label>Sort by:</label>
                        <select id="sort-products" class="form-control">
                            <option value="relevance" <?php echo $sort == 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                </div>
                
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-category">
                                <?php 
                                $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
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
                            </form>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-details">View Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php echo generatePaginationLinks($page, $total_pages, $base_url); ?>
                </div>
                
                <?php else: ?>
                <div class="no-results">
                    <p>No products found matching "<?php echo htmlspecialchars($search_query); ?>". Please try different search terms or browse our categories.</p>
                    <div class="no-results-actions">
                        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                        <a href="products.php" class="btn btn-outline">Browse All Products</a>
                    </div>
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
