<?php
$page_title = "Edit Product";
$is_admin = true;
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Get categories for select dropdown
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Fetch product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// Check if product exists
if (!$product) {
    header('Location: products.php');
    exit;
}

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
            // Set values
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
            $image = $_POST['image'] ?? $product['image']; // Use existing image if not provided
            
            // Update product
            $stmt = $conn->prepare("UPDATE products SET 
                                    name = ?, 
                                    sku = ?, 
                                    price = ?, 
                                    description = ?, 
                                    category_id = ?, 
                                    stock_quantity = ?, 
                                    featured = ?, 
                                    in_stock = ?, 
                                    specifications = ?, 
                                    brand = ?, 
                                    tags = ?, 
                                    image = ?, 
                                    updated_at = NOW()
                                    WHERE id = ?");
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
                $image,
                $product_id
            ]);
            
            if ($result) {
                // Redirect to products page with success message
                $_SESSION['success_message'] = "Product updated successfully.";
                header('Location: products.php');
                exit;
            } else {
                $errors[] = "Failed to update product.";
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
    <?php  require_once './includes/sidebar.php'; ?>

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
            <h1>Edit Product: <?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="admin-header-actions">
                <a href="/admin/products.php" class="btn btn-outline">Back to Products</a>
                <a href="/product-details.php?id=<?php echo $product_id; ?>" class="btn btn-outline"
                    target="_blank">View Product</a>
            </div>
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
            <form action="/admin/edit-product.php?id=<?php echo $product_id; ?>" method="POST"
                enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="sku">SKU (Stock Keeping Unit)</label>
                        <input type="text" id="sku" name="sku" class="form-control"
                            value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="price">Price *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0"
                                value="<?php echo htmlspecialchars($product['price']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0"
                            value="<?php echo htmlspecialchars($product['stock_quantity']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" class="form-control"
                            value="<?php echo htmlspecialchars($product['brand'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags (comma separated)</label>
                        <input type="text" id="tags" name="tags" class="form-control"
                            value="<?php echo htmlspecialchars($product['tags'] ?? ''); ?>">
                        <small class="form-text">Enter keywords separated by commas. Used for search and
                            filtering.</small>
                    </div>

                    <div class="form-group">
                        <label for="featured">Featured Product</label>
                        <div class="form-check">
                            <input type="checkbox" id="featured" name="featured" class="form-check-input"
                                <?php echo ($product['featured'] == 1) ? 'checked' : ''; ?>>
                            <label for="featured" class="form-check-label">Display this product in featured
                                sections</label>
                        </div>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="image">Product Image URL</label>
                        <input type="text" id="image" name="image" class="form-control"
                            value="<?php echo htmlspecialchars($product['image']); ?>"
                            placeholder="https://example.com/image.jpg">
                        <small class="form-text">Enter a URL for the product image. Ideal dimensions: 800x800
                            pixels.</small>

                        <div class="image-preview-container">
                            <img id="image-preview" src="<?php echo htmlspecialchars($product['image']); ?>"
                                alt="Product Image Preview" class="image-preview" style="display: block;">
                        </div>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="description">Product Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="6"
                            required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="form-group form-grid-full">
                        <label for="specifications">Product Specifications</label>
                        <textarea id="specifications" name="specifications" class="form-control"
                            rows="6"><?php echo htmlspecialchars($product['specifications'] ?? ''); ?></textarea>
                        <small class="form-text">Enter technical specifications, dimensions, materials, etc.</small>
                    </div>

                    <div class="form-actions">
                        <a href="/admin/products.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Product</button>
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
        // Initial preview already set from PHP
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>