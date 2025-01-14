<!-- Document Section -->
<div class="row mb-4">
    <label class="col-sm-3 col-form-label fw-medium">Document:</label>
    <div class="col-sm-9">
        <?php if (!empty($paperwork['document_path']) && file_exists('uploads/' . $paperwork['document_path'])): ?>
            <div class="d-flex align-items-center gap-2">
                <a href="uploads/<?php echo htmlspecialchars($paperwork['document_path']); ?>" 
                   class="btn btn-primary" 
                   target="_blank">
                    <i class="fas fa-download me-2"></i>View Document
                </a>
                <span class="text-muted small">
                    <?php 
                    $file_path = 'uploads/' . $paperwork['document_path'];
                    $file_size = filesize($file_path);
                    echo '(' . round($file_size / 1024, 2) . ' KB)';
                    ?>
                </span>
            </div>

            <?php if (pathinfo($paperwork['document_path'], PATHINFO_EXTENSION) === 'pdf'): ?>
                <div class="mt-3">
                    <div class="ratio ratio-16x9" style="max-height: 600px;">
                        <iframe 
                            src="uploads/<?php echo htmlspecialchars($paperwork['document_path']); ?>" 
                            class="shadow-sm rounded"
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-warning mb-0 d-flex align-items-center">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                <span>No document has been uploaded for this paperwork.</span>
            </div>
        <?php endif; ?>
    </div>
</div>