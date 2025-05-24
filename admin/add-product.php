<?php
$page_title = "Add New Product";
$is_admin = true;
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get categories for select dropdown
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form
    $errors = [];
    
    // Required fields
    $required_fields = ['name', 'price', 'category_id', 'description'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // Validate price
    if (!empty($_POST['price']) && (!is_numeric($_POST['price']) || $_POST['price'] < 0)) {
        $errors[] = 'Price must be a valid number.';
    }
    
    // Validate stock
    if (!empty($_POST['stock_quantity']) && (!is_numeric($_POST['stock_quantity']) || $_POST['stock_quantity'] < 0)) {
        $errors[] = 'Stock quantity must be a valid number.';
    }
    
    // Process the form if no errors
    if (empty($errors)) {
        try {
            // Set default values
            $name = $_POST['name'];
            $sku = $_POST['sku'] ?? '';
            $price = (float)$_POST['price'];
            $description = $_POST['description'];
            $category_id = (int)$_POST['category_id'];
            $stock_quantity = isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 0;
            $featured = isset($_POST['featured']) ? 1 : 0;
            $in_stock = $stock_quantity > 0 ? 1 : 0;
            $specifications = $_POST['specifications'] ?? '';
            $brand = $_POST['brand'] ?? '';
            $tags = $_POST['tags'] ?? '';
            $image = $_POST['image'] ?? 'https://images.unsplash.com/photo-1525904097878-94fb15835963'; // Default image
            
            // Insert product
            $stmt = $conn->prepare("INSERT INTO products (name, sku, price, description, category_id, stock_quantity, featured, in_stock, specifications, brand, tags, image, created_at, updated_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $result = $stmt->execute([
                $name,
                $sku,
                $price,
                $description,
                $category_id,
                $stock_quantity,
                $featured,
                $in_stock,
                $specifications,
                $brand,
                $tags,
                $image
            ]);
            
            if ($result) {
                // Redirect to products page with success message
                $_SESSION['success_message'] = "Product added successfully.";
                header('Location: products.php');
                exit;
            } else {
                $errors[] = "Failed to add product.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="admin-container">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-logo">
            <img src="/assets/logo.svg" alt="ShopNow Admin">
            <span>ShopNow Admin</span>
        </div>

        <div class="admin-menu">
            <p class="admin-menu-category">Main</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/index.php" class="admin-menu-link">
                        <i class="fas fa-tachometer-alt admin-menu-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="/admin/orders.php" class="admin-menu-link">
                        <i class="fas fa-shopping-cart admin-menu-icon"></i>
                        <span>Orders</span>
                    </a>
                </li>
            </ul>

            <p class="admin-menu-category">Catalog</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/products.php" class="admin-menu-link active">
                        <i class="fas fa-box admin-menu-icon"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="/admin/categories.php" class="admin-menu-link">
                        <i class="fas fa-tags admin-menu-icon"></i>
                        <span>Categories</span>
                    </a>
                </li>
            </ul>

            <p class="admin-menu-category">Users</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/customers.php" class="admin-menu-link">
                        <i class="fas fa-users admin-menu-icon"></i>
                        <span>Customers</span>
                    </a>
                </li>
            </ul>

            <p class="admin-menu-category">Settings</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/settings.php" class="admin-menu-link">
                        <i class="fas fa-cog admin-menu-icon"></i>
                        <span>General Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Admin Content -->
    <div class="admin-content" id="admin-content">
        <div class="admin-top-bar">
            <button class="admin-toggle-sidebar" id="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <div class="admin-user">
                <img src="<?php echo isset($_SESSION['photo']) ? htmlspecialchars($_SESSION['photo']) : '/assets/user-icon.svg'; ?>"
                    alt="User" class="admin-user-avatar">
                <div class="admin-user-info">
                    <div class="admin-user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="admin-user-role">Administrator</div>
                </div>
            </div>
        </div>

        <div class="admin-page-header">
            <h1>Add New Product</h1>
            <a href="/admin/products.php" class="btn btn-outline">Back to Products</a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="admin-form">
            <form action="/admin/add-product.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="sku">SKU (Stock Keeping Unit)</label>
                        <input type="text" id="sku" name="sku" class="form-control"
                            value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="price">Price *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0"
                                value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0"
                            value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : '0'; ?>">
                    </div>

                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" class="form-control"
                            value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags (comma separated)</label>
                        <input type="text" id="tags" name="tags" class="form-control"
                            value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>">
                        <small class="form-text">Enter keywords separated by commas. Used for search and
                            filtering.</small>
                    </div>

                    <div class="form-group">
                        <label for="featured">Featured Product</label>
                        <div class="form-check">
                            <input type="checkbox" id="featured" name="featured" class="form-check-input"
                                <?php echo (isset($_POST['featured'])) ? 'checked' : ''; ?>>
                            <label for="featured" class="form-check-label">Display this product in featured
                                sections</label>
                        </div>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="image">Product Image URL</label>
                        <input type="text" id="image" name="image" class="form-control"
                            value="<?php echo isset($_POST['image']) ? htmlspecialchars($_POST['image']) : ''; ?>"
                            placeholder="https://example.com/image.jpg">
                        <small class="form-text">Enter a URL for the product image. Ideal dimensions: 800x800
                            pixels.</small>

                        <div class="image-preview-container">
                            <img id="image-preview" src="" alt="Product Image Preview" class="image-preview">
                        </div>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="description">Product Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="6"
                            required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="specifications">Product Specifications</label>
                        <textarea id="specifications" name="specifications" class="form-control"
                            rows="6"><?php echo isset($_POST['specifications']) ? htmlspecialchars($_POST['specifications']) : ''; ?></textarea>
                        <small class="form-text">Enter technical specifications, dimensions, materials, etc.</small>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-outline">Reset</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleBtn = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('admin-sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Image preview
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');

    // Function to update image preview
    function updateImagePreview() {
        const imageUrl = imageInput.value.trim();
        if (imageUrl) {
            imagePreview.src = imageUrl;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.src = '';
            imagePreview.style.display = 'none';
        }
    }

    // Update preview when input changes
    if (imageInput && imagePreview) {
        imageInput.addEventListener('input', updateImagePreview);
        // Initial preview
        updateImagePreview();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>