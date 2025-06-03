<?php
require_once '../includes/header.php';
// include('../includes/navbar.php');
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}
?>
<head>
    <link rel="stylesheet" href="/../css/admin.css">
</head>
<div class="admin-container">
    <!-- Admin Sidebar -->
    <?php  require_once './includes/sidebar.php'; ?>

    <div class="row admin-content" id="admin-content">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Customers List</h4>
                </div>
                <div class="card-body">
                    <?php
                    $query = "SELECT * FROM users WHERE is_admin = 0 ORDER BY id DESC";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $customers = $stmt->fetchAll();

                    if (count($customers) > 0) {
                        ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registration Date</th>
                                    <!-- <th>Status</th> -->
                                    <th>Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($customers as $row) {
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']); ?></td>
                                        <td><?= htmlspecialchars($row['name']); ?></td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td><?= htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                        <td><?= date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                        <!-- <td>
                                            <?php
                                            // if ($row['status'] == '1') {
                                            //     echo '<span class="badge bg-success">Active</span>';
                                            // } else {
                                            //     echo '<span class="badge bg-danger">Inactive</span>';
                                            // }
                                            ?>
                                        </td> -->
                                        <td><?= htmlspecialchars($row['address']); ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                    } else {
                        echo "No Customers Found";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>