<?php
$page_crumb = 'DASHBOARD';
require_once __DIR__ . '/includes/db_connect.php';

if (!isStaff()) {
    header("Location: admin.php");
    exit;
}

// Handle Event Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event_id'])) {
    $del_id = (int)$_POST['delete_event_id'];
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?deleted=1");
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Fetch Summary Stats
$events_count = $conn->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'] ?? 0;
$regs_count = $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'] ?? 0;
$ann_count = $conn->query("SELECT COUNT(*) AS c FROM announcements")->fetch_assoc()['c'] ?? 0;

// Fetch Events with Registration Counts
$events_res = $conn->query("SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_total 
                            FROM events e ORDER BY e.event_date ASC");

// Optional Attendee Roster View
$view_event_id = (int)($_GET['attendees'] ?? 0);
$roster_title = '';
$roster = [];
if ($view_event_id > 0) {
    $ev_stmt = $conn->prepare("SELECT title FROM events WHERE id = ?");
    $ev_stmt->bind_param("i", $view_event_id);
    $ev_stmt->execute();
    $ev_row = $ev_stmt->get_result()->fetch_assoc();
    $roster_title = $ev_row['title'] ?? '';
    $ev_stmt->close();

    $r_stmt = $conn->prepare("SELECT * FROM registrations WHERE event_id = ? ORDER BY registered_at DESC");
    $r_stmt->bind_param("i", $view_event_id);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($row = $r_res->fetch_assoc()) {
        $roster[] = $row;
    }
    $r_stmt->close();
}
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow-text">Your Control Room</div>
        <h1 class="hero-heading mb-2">Organizer <span>dashboard.</span></h1>
        <p class="text-muted mb-0">Keep your campus community in the loop.</p>
    </div>
    <a href="manage.php" class="btn btn-cg-primary px-4 py-2">+ Create event</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-info py-2 small">Event removed from the campus calendar.</div>
<?php endif; ?>

<!-- 3 Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="cg-card">
            <div class="eyebrow-text mb-1">Your Events</div>
            <div class="display-5 fw-bold"><?= $events_count ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="cg-card">
            <div class="eyebrow-text mb-1">Registrations</div>
            <div class="display-5 fw-bold text-primary"><?= $regs_count ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="cg-card">
            <div class="eyebrow-text mb-1">Announcements</div>
            <div class="display-5 fw-bold"><?= $ann_count ?></div>
        </div>
    </div>
</div>

<!-- Attendee Roster Drawer (When Selected) -->
<?php if ($view_event_id > 0): ?>
    <div class="cg-card mb-4 border-primary">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="eyebrow-text text-primary mb-0">Registered Attendees</div>
                <h5 class="fw-bold mb-0"><?= e($roster_title) ?> (<?= count($roster) ?>)</h5>
            </div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Close Roster</a>
        </div>
        <?php if (count($roster) > 0): ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Matric No.</th>
                            <th>Email</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roster as $st): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($st['student_name']) ?></td>
                                <td><?= e($st['matric_no']) ?></td>
                                <td><?= e($st['email']) ?></td>
                                <td class="small text-muted"><?= date('M j, g:i A', strtotime($st['registered_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="small text-muted mb-0">No students have registered for this event yet.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Published Events Table -->
<h5 class="fw-bold mb-3">Your published events</h5>
<?php if ($events_res && $events_res->num_rows > 0): ?>
    <div class="cg-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Event</th>
                        <th>Date &amp; Time</th>
                        <th>Venue</th>
                        <th>Registrations</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($ev = $events_res->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold"><?= e($ev['title']) ?></div>
                                <span class="badge bg-primary-subtle text-primary"><?= e($ev['category']) ?></span>
                            </td>
                            <td class="small">
                                <?= date('M j, Y', strtotime($ev['event_date'])) ?><br>
                                <span class="text-muted"><?= date('g:i A', strtotime($ev['event_time'])) ?></span>
                            </td>
                            <td class="small"><?= e($ev['venue']) ?></td>
                            <td>
                                <span class="fw-bold"><?= $ev['reg_total'] ?></span> / <?= $ev['capacity'] ?>
                            </td>
                            <td class="text-end pe-3">
                                <a href="dashboard.php?attendees=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary">Roster</a>
                                <form method="POST" action="dashboard.php" class="d-inline" onsubmit="return confirm('Delete this event?');">
                                    <input type="hidden" name="delete_event_id" value="<?= $ev['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state-box">You haven't published an event yet.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>