<?php
// Calculate total pages
$total_records = count($rows);
$total_pages = ceil($total_records / $results_per_page);
$current_page = isset($_GET['pageno']) ? (int)$_GET['pageno'] : 1;

// Only show pagination if there's more than one page
if ($total_pages > 1):
?>

<div class="container mb-4">
    <nav aria-label="Page navigation" class="d-flex justify-content-center">
        <ul class="pagination">
            <!-- Previous page link -->
            <?php if ($current_page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?pageno=<?php echo $current_page - 1; ?>" aria-label="Previous">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>

            <!-- Page numbers -->
            <?php
            // Calculate range of pages to show
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            // Show first page if not in range
            if ($start_page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?pageno=1">1</a></li>';
                if ($start_page > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Show page numbers
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                    <a class="page-link" href="?pageno=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor;

            // Show last page if not in range
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?pageno=' . $total_pages . '">' . $total_pages . '</a></li>';
            }
            ?>

            <!-- Next page link -->
            <?php if ($current_page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?pageno=<?php echo $current_page + 1; ?>" aria-label="Next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php endif; ?>