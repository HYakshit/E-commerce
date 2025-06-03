<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
    <?php if (!isAdmin()): ?>
        <a class="navbar-brand" href="/">
            <img src="/assets/logo.svg" alt="ShopNow Logo" height="30" class="d-inline-block align-text-top">
            <span>ShopNow</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <?php endif; ?>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <?php if (isAdmin()): ?>
                <ul class="navbar-nav me-auto ms-lg-5 mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/add-product.php">Add products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/products.php">View products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/orders.php">View orders</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">Hi <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-btn" href="#" id="nav-logout-btn">Logout</a>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'active' : ''; ?>"
                            href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/products.php') ? 'active' : ''; ?>"
                            href="/products.php">Products</a>
                    </li>
                </ul>

                <form class="d-flex me-3" action="/search.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Search..." aria-label="Search"
                            required>
                        <button class="btn btn-primary " type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

                <div class="d-flex align-items-center">
                    <?php if (isLoggedIn()): ?>
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <span class="nav-link">Hi <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/account.php">My Account</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/orders.php">My Orders</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link logout-btn" href="#" id="nav-logout-btn">Logout</a>
                            </li>
                        </ul>
                        <a href="/cart.php" class="position-relative me-3">
                            <i class="fas fa-shopping-cart fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo getCartItemCount(); ?>
                            </span>
                        </a>
                    <?php else: ?>
                        <div class="ms-2">
                            <a href="/login.php" class="btn btn-outline-primary me-2">Login</a>
                            <a href="/register.php" class="btn btn-primary">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>