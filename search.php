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
$where_params = ['%' . $search_query . '%', '%' . $search_query . '%', '%' . $search_query . '%'];
$where_clauses = ['(name LIKE ? OR description LIKE ? OR tags LIKE ?)'];

// Category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_clauses[] = "category_id = ?";
    $where_params[] = $_GET['category'];
}

// Price range filter
if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $where_clauses[] = "price >= ?";
    $where_params[] = $_GET['min_price'];
}

if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $where_clauses[] = "price <= ?";
    $where_params[] = $_GET['max_price'];
}

// Build the WHERE clause
$where_sql = ' WHERE ' . implode(' AND ', $where_clauses);

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$order_by = "";
$order_params = [];

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
        $order_params[] = '%' . $search_query . '%';
        $order_params[] = '%' . $search_query . '%';
        $order_params[] = '%' . $search_query . '%';
        break;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total products count for pagination
$count_sql = "SELECT COUNT(*) FROM products" . $where_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($where_params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "SELECT * FROM products" . $where_sql . $order_by . " LIMIT " . $per_page . " OFFSET " . $offset;
$stmt = $conn->prepare($sql);
$stmt->execute(array_merge($where_params, $order_params));
$products = $stmt->fetchAll();

// Current URL without pagination for pagination links
$current_url = strtok($_SERVER["REQUEST_URI"], '?');
$query_string = $_GET;
unset($query_string['page']);
$base_url = $current_url . '?' . http_build_query($query_string) . (empty($query_string) ? '' : '&');

$sort_options = [
    'relevance' => 'Relevance',
    'newest' => 'Newest',
    'price_low' => 'Price: Low to High',
    'price_high' => 'Price: High to Low'
];

require_once 'includes/header.php';
?>

<section class="search-results">
    <div class="container">
        <div class="search-header">
            <h1>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>
            <p><?php echo $total_products; ?> products found</p>
        </div>
        
        <div class="catalog-layout">
            <?php
            echo renderCatalogSidebar([
                'title' => 'Refine Search',
                'form_action' => 'search.php',
                'categories' => $categories,
                'current_category' => isset($_GET['category']) ? $_GET['category'] : '',
                'current_min_price' => isset($_GET['min_price']) ? $_GET['min_price'] : '',
                'current_max_price' => isset($_GET['max_price']) ? $_GET['max_price'] : '',
                'current_sort' => $sort,
                'sort_options' => $sort_options,
                'hidden_fields' => ['q' => $search_query],
                'clear_url' => 'search.php?q=' . urlencode($search_query)
            ]);
            ?>
            
            <div class="catalog-main">
                <?php if (count($products) > 0): ?>
                <div class="catalog-main-header">
                    <div>
                        <h2>Matching Products</h2>
                        <p class="catalog-results-count"><?php echo $total_products; ?> items found for "<?php echo htmlspecialchars($search_query); ?>"</p>
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
                            <?php if (isProductAvailableForPurchase($product)): ?>
                            <form action="cart.php" method="POST" class="add-to-cart">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add_to_cart">
                                <button type="submit" class="btn btn-primary btn-sm btn-block">Add to Cart</button>
                            </form>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-block" disabled>Out of Stock</button>
                            <?php endif; ?>
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

<?php require_once 'includes/footer.php'; ?>
