<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="/assets/logo.svg" alt="ShopNow Logo" height="30" class="d-inline-block align-text-top">
            <span>ShopNow</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/index.php') ? 'active' : ''; ?>"
                        href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($_SERVER['PHP_SELF'] == '/products.php') ? 'active' : ''; ?>"
                        href="/products.php">Products</a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/index.php">Admin</a>
                </li>
                <?php endif; ?>
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
                        <span class="nav-link">Hii <?php echo $_SESSION['name']; ?></span>

                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/account.php">My Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/index.php">Admin</a>
                    </li>
                </ul>
                <a href="/cart.php" class="position-relative me-3">
                    <i class="fas fa-shopping-cart fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo getCartItemCount(); ?>
                    </span>
                </a>
                <!-- <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i>
                            <span><?php echo $_SESSION['name']; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/account.php">My Account</a></li>
                            <li><a class="dropdown-item" href="/orders.php">My Orders</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#" id="logout-btn">Logout</a></li>
                        </ul>
                    </div> -->
                <?php else: ?>
                <div class="ms-2">
                    <a href="/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="/register.php" class="btn btn-primary">Register</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>