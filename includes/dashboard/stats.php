<?php
// Get stats based on user role
$stats = [];

switch($_SESSION['user_type']) {
    case 'admin':
        // Admin stats
        $stats = [
            [
                'title' => 'Total Paperworks',
                'value' => count($rows),
                'icon' => 'file-alt',
                'color' => 'primary'
            ],
            [
                'title' => 'Active Users',
                'value' => $conn->query("SELECT COUNT(*) FROM tbl_users WHERE active = 1")->fetchColumn(),
                'icon' => 'users',
                'color' => 'info'
            ],
            [
                'title' => 'Awaiting Review',
                'value' => count(array_filter($rows, fn($row) => $row['current_stage'] == 'submitted')),
                'icon' => 'hourglass-half',
                'color' => 'warning'
            ],
            [
                'title' => 'Approved Today',
                'value' => count(array_filter($rows, function($row) {
                    return $row['status'] == 1 && 
                           isset($row['dean_approval_date']) && 
                           date('Y-m-d', strtotime($row['dean_approval_date'])) == date('Y-m-d');
                })),
                'icon' => 'check-double',
                'color' => 'success'
            ]
        ];
        break;

    case 'hod':
        // HOD stats
        $stats = [
            [
                'title' => 'Department Papers',
                'value' => count($rows),
                'icon' => 'file-alt',
                'color' => 'primary'
            ],
            [
                'title' => 'Pending Review',
                'value' => count(array_filter($rows, fn($row) => $row['current_stage'] == 'hod_review')),
                'icon' => 'clock',
                'color' => 'warning'
            ],
            [
                'title' => 'Approved',
                'value' => count(array_filter($rows, fn($row) => $row['hod_approval'] == 1)),
                'icon' => 'check-circle',
                'color' => 'success'
            ],
            [
                'title' => 'Returned',
                'value' => count(array_filter($rows, fn($row) => $row['hod_approval'] === 0)),
                'icon' => 'undo',
                'color' => 'danger'
            ]
        ];
        break;

    case 'dean':
        // Dean stats
        $stats = [
            [
                'title' => 'Total Papers',
                'value' => count($rows),
                'icon' => 'file-alt',
                'color' => 'primary'
            ],
            [
                'title' => 'Pending Review',
                'value' => count(array_filter($rows, fn($row) => $row['current_stage'] == 'dean_review')),
                'icon' => 'clock',
                'color' => 'warning'
            ],
            [
                'title' => 'Approved',
                'value' => count(array_filter($rows, fn($row) => $row['dean_approval'] == 1)),
                'icon' => 'check-circle',
                'color' => 'success'
            ],
            [
                'title' => 'Returned',
                'value' => count(array_filter($rows, fn($row) => $row['dean_approval'] === 0)),
                'icon' => 'undo',
                'color' => 'danger'
            ]
        ];
        break;

    default:
        // Regular user stats
        $stats = [
            [
                'title' => 'Total Paperworks',
                'value' => count($rows),
                'icon' => 'file-alt',
                'color' => 'primary'
            ],
            [
                'title' => 'Pending Approval',
                'value' => count(array_filter($rows, fn($row) => $row['status'] != 1)),
                'icon' => 'clock',
                'color' => 'warning'
            ],
            [
                'title' => 'Approved',
                'value' => count(array_filter($rows, fn($row) => $row['status'] == 1)),
                'icon' => 'check-circle',
                'color' => 'success'
            ],
            [
                'title' => 'Last Submission',
                'value' => !empty($rows) ? date('d M Y', strtotime($rows[0]['submission_time'])) : 'N/A',
                'icon' => 'calendar',
                'color' => 'info',
                'isDate' => true
            ]
        ];
}
?>

<!-- Stats Cards -->
<div class="container mb-4">
    <div class="row g-4">
        <?php foreach ($stats as $stat): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <h6 class="text-muted mb-2"><?php echo $stat['title']; ?></h6>
                        <div class="d-flex align-items-center">
                            <?php if (isset($stat['isDate'])): ?>
                                <small class="text-muted"><?php echo $stat['value']; ?></small>
                            <?php else: ?>
                                <h3 class="mb-0"><?php echo $stat['value']; ?></h3>
                            <?php endif; ?>
                            <i class="fas fa-<?php echo $stat['icon']; ?> text-<?php echo $stat['color']; ?> ms-auto"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>