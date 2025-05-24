    <?php
    session_start();
require_once 'includes/db_connect.php';
    // Ensure user is logged in
    if (!isset($_SESSION['firebase_uid'])) {
        header("Location: login.php");
        exit();
    }

    // Fetch user info from the database
    $firebase_uid = $_SESSION['firebase_uid'];

    if ($db_type == 'postgresql') {
        $stmt = $conn->prepare("SELECT id, name, email, firebase_uid, is_admin FROM users WHERE firebase_uid = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, firebase_uid, is_admin FROM users WHERE firebase_uid = ?");
    }
    $stmt->execute([$firebase_uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['firebase_uid'] = $user['firebase_uid'];
        $_SESSION['is_admin'] = $user['is_admin'];
    } else {
        // Handle user not found (optional: redirect or show error)
        echo "User not found.";
        exit();
    }
    // Example: Display account information
    echo "<h1>Account Page</h1>";
    echo "<p>Name: " . htmlspecialchars($_SESSION['name']) . "</p>";
    echo "<p>Email: " . htmlspecialchars($_SESSION['email']) . "</p>";
    echo "<p>User ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>";
    echo "<p>Firebase UID: " . htmlspecialchars($_SESSION['firebase_uid']) . "</p>";
    echo "<p>Admin: " . ($_SESSION['is_admin'] ? 'Yes' : 'No') . "</p>";
    ?>