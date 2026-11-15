<?php
$page_crumb = 'ANNOUNCEMENTS';
require_once __DIR__ . '/includes/header.php';

$ann_res = $conn->query("SELECT * FROM announcements ORDER BY posted_at DESC");
?>

<div class="mb-4">
    <div class="eyebrow-text">Campus Bulletin</div>
    <h1 class="hero-heading mb-2">What's the <span>word?</span></h1>
    <p class="text-muted mb-0">Updates, notices and things you should know.</p>
</div>

<?php if ($ann_res && $ann_res->num_rows > 0): ?>
    <div class="d-flex flex-column gap-3">
        <?php while ($ann = $ann_res->fetch_assoc()): ?>
            <div class="cg-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <?php if ($ann['priority'] === 'Urgent'): ?>
                        <span class="badge bg-danger-subtle text-danger">URGENT NOTICE</span>
                    <?php else: ?>
                        <span class="badge bg-primary-subtle text-primary">CAMPUS UPDATE</span>
                    <?php endif; ?>
                    <span class="small text-muted"><?= date('M j, Y • g:i A', strtotime($ann['posted_at'])) ?></span>
                </div>
                <h5 class="fw-bold mb-2"><?= e($ann['headline']) ?></h5>
                <p class="text-muted mb-0"><?= nl2br(e($ann['message_body'])) ?></p>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="empty-state-box">
        No announcements have been posted yet.
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>