<?php
require_once 'includes/auth.php';
require_once 'includes/utilities.php';

// Get Auth instance and validate session
$auth = Auth::getInstance();
$auth->validateSession();

// Verify admin access
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Get database connection
$conn = $auth->getConnection();

// Fetch users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$results_per_page = 10;
$offset = ($page - 1) * $results_per_page;

$stmt = $conn->prepare("SELECT * FROM tbl_users ORDER BY name LIMIT ? OFFSET ?");
$stmt->execute([$results_per_page, $offset]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total users for pagination
$total_stmt = $conn->query("SELECT COUNT(*) FROM tbl_users");
$total_users = $total_stmt->fetchColumn();
$total_pages = ceil($total_users / $results_per_page);

include 'includes/header.php';
?>

<main class="pt-5 mt-5">
    <div class="container py-4">
        <!-- User Management Section -->
        <?php include 'includes/manage_accounts/user_table.php'; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</main>

<!-- Include Modal Components -->
<?php 
include 'includes/manage_accounts/add_user_modal.php';
include 'includes/manage_accounts/edit_user_modal.php';
include 'includes/footer.php'; 
?>