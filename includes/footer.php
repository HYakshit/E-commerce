        </main>
        <footer class="bg-dark text-white py-5 mt-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4 mb-md-0">
                        <h5 class="mb-3">ShopNow</h5>
                        <p class="text-muted">Your one-stop shop for all your needs. High-quality products at affordable
                            prices.</p>
                    </div>
                    <div class="col-md-4 mb-4 mb-md-0">
                        <h5 class="mb-3">Quick Links</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="/" class="text-decoration-none text-muted">Home</a></li>
                            <li class="mb-2"><a href="/products.php"
                                    class="text-decoration-none text-muted">Products</a></li>
                            <li class="mb-2"><a href="/cart.php" class="text-decoration-none text-muted">Cart</a></li>
                            <?php if (isLoggedIn()): ?>
                            <li class="mb-2"><a href="/account.php" class="text-decoration-none text-muted">My
                                    Account</a></li>
                            <?php else: ?>
                            <li class="mb-2"><a href="/login.php" class="text-decoration-none text-muted">Login</a></li>
                            <li class="mb-2"><a href="/register.php"
                                    class="text-decoration-none text-muted">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5 class="mb-3">Contact Us</h5>
                        <p class="text-muted mb-2">Email: dummy-email@shopnow.com</p>
                        <p class="text-muted mb-2">Phone: +91 (123) 456-7890</p>
                        <div class="mt-3">
                            <a href="#" class="text-decoration-none me-3 text-muted"><i
                                    class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-decoration-none me-3 text-muted"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-decoration-none me-3 text-muted"><i
                                    class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <hr class="my-4 bg-secondary">
                <div class="text-center text-muted">
                    <p class="mb-0">&copy; <?php echo date("Y"); ?> ShopNow. All rights reserved.</p>
                </div>
            </div>
        </footer>
        <!-- owl -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Owl requires jQuery -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
        </script>
        <!-- Firebase Auth JS -->
        <script src="/js/firebase-auth.js"></script>
        <!-- Main JS -->
        <script src="/js/main.js"></script>
        <script src="/js/owl-carousal.js"></script>
        <?php if (isset($page_script)): ?>
        <script src="<?php echo $page_script; ?>"></script>
        <?php endif; ?>
        </body>

        </html>