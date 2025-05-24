<?php 

session_start();

// if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
//     echo "<h2>No products found in your order.</h2>";
//     exit;
// }

$ordered_product = $_SESSION['cart'];
?>

<h1>Order Successful!</h1>
<p>Thank you for your purchase. Here are the details of your order:</p>
<ul>
    <?php
foreach ($ordered_product as $product) {
    echo "<li>{$product['name']} - Quantity: {$product['quantity']} - Price: {$product['price']}</li>";
}
?>
</ul>
<a href="index.php">Continue Shopping</a>
<?php
// Optionally clear the cart after successful order
// unset($_SESSION['cart']);
?>