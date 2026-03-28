<?php
$page_title = "Our Products";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Get categories for filter
$cat_stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();

// Initialize query parameters
$params = [];
$where_clauses = [];

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

$sort_options = [
    'newest' => 'Newest',
    'oldest' => 'Oldest',
    'price_low' => 'Price: Low to High',
    'price_high' => 'Price: High to Low'
];

require_once 'includes/header.php';
?>

<section class="products-page">
    <div class="container">
        <div class="catalog-layout">
            <?php
            $hidden_fields = [];
            foreach ($_GET as $key => $value) {
                if (!in_array($key, ['category', 'min_price', 'max_price', 'sort', 'page'])) {
                    $hidden_fields[$key] = $value;
                }
            }

            echo renderCatalogSidebar([
                'title' => 'Filter Products',
                'form_action' => 'products.php',
                'categories' => $categories,
                'current_category' => isset($_GET['category']) ? $_GET['category'] : '',
                'current_min_price' => isset($_GET['min_price']) ? $_GET['min_price'] : '',
                'current_max_price' => isset($_GET['max_price']) ? $_GET['max_price'] : '',
                'current_sort' => $sort,
                'sort_options' => $sort_options,
                'hidden_fields' => $hidden_fields,
                'clear_url' => 'products.php'
            ]);
            ?>

            <div class="catalog-main">
                <div class="catalog-main-header">
                    <div>
                        <h2><?php echo $page_title; ?></h2>
                        <p class="catalog-results-count"><?php echo $total_products; ?> products available</p>
                    </div>
                </div>

                <?php if (count($products) > 0): ?>
                    <div class="products-grid">
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
                                    <?php if (isProductAvailableForPurchase($product)): ?>
                                    <form action="cart.php" method="POST" class="add-to-cart">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <button type="submit" class="btn btn-primary btn-sm btn-block">Add to Cart</button>
                                    </form>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-block" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                    <div class="text-center mt-1"> <a
                                            href="product-details.php?id=<?php echo $product['id']; ?>"
                                            class="btn btn-outline-secondary">View
                                            details</a></div>
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
                        <p>No products found matching your criteria. Please try different filters.</p>
                        <a href="products.php" class="btn btn-outline">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
