<?php
$page_crumb = 'SCHEDULE';
require_once __DIR__ . '/includes/db_connect.php';
requireAuth();

$user_email = $_SESSION['email'];

// Handle Attendee Self Check-In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin_reg_id'])) {
    $reg_id = (int)$_POST['checkin_reg_id'];
    $stmt = $conn->prepare("UPDATE registrations SET checked_in = 1, checked_in_at = NOW() WHERE id = ? AND email = ?");
    $stmt->bind_param("is", $reg_id, $user_email);
    $stmt->execute();
    $stmt->close();
    header("Location: schedule.php?checked_in=1");
    exit;
}

// Handle Cancellation (Only if not yet checked in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reg_id'])) {
    $reg_id = (int)$_POST['cancel_reg_id'];
    $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ? AND email = ? AND checked_in = 0");
    $stmt->bind_param("is", $reg_id, $user_email);
    $stmt->execute();
    $stmt->close();
    header("Location: schedule.php?cancelled=1");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$registrations = [];
$stmt = $conn->prepare("SELECT r.id AS reg_id, r.registered_at, r.checked_in, r.checked_in_at, e.* 
                        FROM registrations r 
                        JOIN events e ON r.event_id = e.id 
                        WHERE r.email = ? 
                        ORDER BY e.event_date ASC, e.event_time ASC");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $registrations[] = $row;
}
$stmt->close();
?>

<div class="mb-4">
    <div class="eyebrow-text">Your Personal Lineup</div>
    <h1 class="hero-heading mb-2">My <span>schedule.</span></h1>
    <p class="text-muted mb-0">Everything you've signed up for, all in one place. Check in when you arrive at the venue.</p>
</div>

<?php if (isset($_GET['checked_in'])): ?>
    <div class="alert alert-success py-2 small">Checked in successfully! Your attendance has been recorded for the organizer.</div>
<?php endif; ?>
<?php if (isset($_GET['cancelled'])): ?>
    <div class="alert alert-info py-2 small">Registration cancelled and removed from your schedule.</div>
<?php endif; ?>

<?php if (count($registrations) > 0): ?>
    <div class="row g-3">
        <?php foreach ($registrations as $item): ?>
            <div class="col-md-6">
                <div class="cg-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary-subtle text-primary"><?= e($item['category']) ?></span>
                            <?php if ($item['checked_in']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Checked In</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">Registered</span>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?= e($item['title']) ?></h5>
                        <div class="small text-muted mb-3">
                            <div><i class="bi bi-calendar3 me-1"></i> <?= date('M j, Y', strtotime($item['event_date'])) ?> at <?= date('g:i A', strtotime($item['event_time'])) ?></div>
                            <div><i class="bi bi-geo-alt me-1"></i> <?= e($item['venue']) ?></div>
                        </div>
                    </div>

                    <div class="border-top pt-3 d-flex justify-content-between align-items-center gap-2">
                        <?php if ($item['checked_in']): ?>
                            <span class="small text-success fw-semibold">
                                Checked in at <?= date('g:i A', strtotime($item['checked_in_at'])) ?>
                            </span>
                        <?php else: ?>
                            <form method="POST" action="schedule.php" class="d-inline">
                                <input type="hidden" name="checkin_reg_id" value="<?= $item['reg_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-cg-primary">
                                    <i class="bi bi-check2-circle me-1"></i> Check In Now
                                </button>
                            </form>
                            <form method="POST" action="schedule.php" class="d-inline" onsubmit="return confirm('Cancel your spot for this event?');">
                                <input type="hidden" name="cancel_reg_id" value="<?= $item['reg_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </div>
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