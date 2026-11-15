<?php
$page_crumb = 'SCHEDULE';
require_once __DIR__ . '/includes/db_connect.php';
requireAuth();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reg_id'])) {
    $reg_id = (int)$_POST['cancel_reg_id'];
    $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $reg_id);
    $stmt->execute();
    $stmt->close();
    header("Location: schedule.php?cancelled=1");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$lookup_matric = strtoupper(trim($_GET['matric'] ?? ($_SESSION['matric_no'] ?? '')));
$registrations = [];

if ($lookup_matric) {
    $stmt = $conn->prepare("SELECT r.id AS reg_id, r.registered_at, e.* 
                            FROM registrations r 
                            JOIN events e ON r.event_id = e.id 
                            WHERE r.matric_no = ? 
                            ORDER BY e.event_date ASC, e.event_time ASC");
    $stmt->bind_param("s", $lookup_matric);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $registrations[] = $row;
    }
    $stmt->close();
}
?>

<div class="mb-4">
    <div class="eyebrow-text">Your Personal Lineup</div>
    <h1 class="hero-heading mb-2">My <span>schedule.</span></h1>
    <p class="text-muted mb-0">Everything you've signed up for, all in one place.</p>
</div>

<?php if (isset($_GET['cancelled'])): ?>
    <div class="alert alert-info py-2 small">Registration cancelled and removed from your schedule.</div>
<?php endif; ?>

<?php if (!isLoggedIn()): ?>
    <div class="cg-card mb-4" style="max-width: 500px;">
        <form method="GET" action="schedule.php" class="d-flex gap-2">
            <input type="text" name="matric" class="form-control" placeholder="Enter Matric No. (e.g. 252130103)" value="<?= e($lookup_matric) ?>" required>
            <button type="submit" class="btn btn-cg-primary text-nowrap">View Lineup</button>
        </form>
    </div>
<?php endif; ?>

<?php if (count($registrations) > 0): ?>
    <div class="row g-3">
        <?php foreach ($registrations as $item): ?>
            <div class="col-md-6">
                <div class="cg-card d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary mb-2"><?= e($item['category']) ?></span>
                        <h5 class="fw-bold mb-1"><?= e($item['title']) ?></h5>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-calendar3"></i> <?= date('M j, Y', strtotime($item['event_date'])) ?> &bull; 
                            <i class="bi bi-clock"></i> <?= date('g:i A', strtotime($item['event_time'])) ?> &bull; 
                            <i class="bi bi-geo-alt"></i> <?= e($item['venue']) ?>
                        </div>
                    </div>
                    <form method="POST" action="schedule.php">
                        <input type="hidden" name="cancel_reg_id" value="<?= $item['reg_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state-box">
        Your schedule is looking open. <a href="events.php" class="text-primary fw-semibold text-decoration-none">Explore events</a> and reserve your spot.
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>