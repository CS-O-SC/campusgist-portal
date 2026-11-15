<?php
$page_crumb = 'DASHBOARD';
require_once __DIR__ . '/includes/db_connect.php';
requireAuth();

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

// Handle Organizer Manual Check-In Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_checkin_id'])) {
    $reg_id = (int)$_POST['toggle_checkin_id'];
    $return_ev = (int)$_POST['return_event_id'];
    $stmt = $conn->prepare("UPDATE registrations 
                            SET checked_in = IF(checked_in = 1, 0, 1), 
                                checked_in_at = IF(checked_in = 1, NULL, NOW()) 
                            WHERE id = ?");
    $stmt->bind_param("i", $reg_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?attendees=" . $return_ev);
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Summary Stats
$events_count = $conn->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'] ?? 0;
$regs_count = $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'] ?? 0;
$checked_in_total = $conn->query("SELECT COUNT(*) AS c FROM registrations WHERE checked_in = 1")->fetch_assoc()['c'] ?? 0;

// Events with Registration & Check-in Counts
$events_res = $conn->query("SELECT e.*, 
                            (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_total,
                            (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.checked_in = 1) AS checkin_total
                            FROM events e ORDER BY e.event_date ASC");

// Attendance Roster View
$view_event_id = (int)($_GET['attendees'] ?? 0);
$roster_title = '';
$roster = [];
$roster_present = 0;
if ($view_event_id > 0) {
    $ev_stmt = $conn->prepare("SELECT title FROM events WHERE id = ?");
    $ev_stmt->bind_param("i", $view_event_id);
    $ev_stmt->execute();
    $ev_row = $ev_stmt->get_result()->fetch_assoc();
    $roster_title = $ev_row['title'] ?? '';
    $ev_stmt->close();

    $r_stmt = $conn->prepare("SELECT * FROM registrations WHERE event_id = ? ORDER BY checked_in DESC, registered_at DESC");
    $r_stmt->bind_param("i", $view_event_id);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($row = $r_res->fetch_assoc()) {
        if ($row['checked_in']) $roster_present++;
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

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success py-2 small">Event updated successfully!</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-info py-2 small">Event removed from the campus calendar.</div>
<?php endif; ?>

<!-- 3 Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="cg-card">
            <div class="eyebrow-text mb-1">Published Events</div>
            <div class="display-5 fw-bold"><?= $events_count ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="cg-card">
            <div class="eyebrow-text mb-1">Total Registrations</div>
            <div class="display-5 fw-bold text-primary"><?= $regs_count ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="cg-card">
            <div class="eyebrow-text mb-1">Checked-In Attendees</div>
            <div class="display-5 fw-bold text-success"><?= $checked_in_total ?></div>
        </div>
    </div>
</div>

<!-- Attendance List Drawer -->
<?php if ($view_event_id > 0): ?>
    <div class="cg-card mb-4 border-primary">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <div class="eyebrow-text text-primary mb-0">Live Attendance List</div>
                <h5 class="fw-bold mb-1"><?= e($roster_title) ?></h5>
                <div class="small text-muted">
                    <span class="badge bg-success-subtle text-success me-1"><?= $roster_present ?> Checked In</span>
                    <span class="badge bg-secondary-subtle text-dark"><?= count($roster) - $roster_present ?> Pending</span>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Close Attendance List</a>
        </div>

        <?php if (count($roster) > 0): ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Attendee Name</th>
                            <th>ID / Role</th>
                            <th>Email</th>
                            <th>Attendance Status</th>
                            <th class="text-end">Organizer Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roster as $st): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($st['student_name']) ?></td>
                                <td>
                                    <?= $st['matric_no'] ? e($st['matric_no']) : '<span class="badge bg-info-subtle text-dark border">STAFF</span>' ?>
                                </td>
                                <td class="small"><?= e($st['email']) ?></td>
                                <td>
                                    <?php if ($st['checked_in']): ?>
                                        <span class="badge bg-success">Checked In &#10003;</span>
                                        <span class="text-muted" style="font-size: 0.7rem;">
                                            <?= date('g:i A', strtotime($st['checked_in_at'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning">Not Checked In</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="dashboard.php" class="d-inline">
                                        <input type="hidden" name="toggle_checkin_id" value="<?= $st['id'] ?>">
                                        <input type="hidden" name="return_event_id" value="<?= $view_event_id ?>">
                                        <button type="submit" class="btn btn-sm <?= $st['checked_in'] ? 'btn-outline-secondary' : 'btn-success' ?>">
                                            <?= $st['checked_in'] ? 'Undo' : 'Mark Present' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="small text-muted mb-0">No attendees have registered for this event yet.</p>
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
                        <th>Capacity / Checked In</th>
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
                            <td class="small">
                                <strong><?= $ev['reg_total'] ?></strong> / <?= $ev['capacity'] ?> spots<br>
                                <span class="text-success"><?= $ev['checkin_total'] ?> checked in</span>
                            </td>
                            <td class="text-end pe-3 text-nowrap">
                                <a href="dashboard.php?attendees=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary">Attendance</a>
                                <a href="manage.php?edit=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-dark">Edit</a>
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