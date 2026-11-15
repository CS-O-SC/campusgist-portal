<?php
$page_crumb = 'EVENTS';
require_once __DIR__ . '/includes/db_connect.php';
requireAuth();

$msg = '';
$msg_type = 'success';
$is_staff_account = ($_SESSION['account_type'] ?? 'student') === 'staff';

// Handle Event Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $student_name = trim($_POST['student_name'] ?? '');
    $matric_no = $is_staff_account ? null : strtoupper(trim($_POST['matric_no'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($event_id <= 0 || !$student_name || (!$is_staff_account && !$matric_no) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please provide all required registration details.";
        $msg_type = "danger";
    } else {
        // Server-side Capacity Lockout Check
        $cap_stmt = $conn->prepare("SELECT capacity, (SELECT COUNT(*) FROM registrations WHERE event_id = ?) AS reg_count FROM events WHERE id = ?");
        $cap_stmt->bind_param("ii", $event_id, $event_id);
        $cap_stmt->execute();
        $cap_data = $cap_stmt->get_result()->fetch_assoc();
        $cap_stmt->close();

        if (!$cap_data) {
            $msg = "Selected event no longer exists.";
            $msg_type = "danger";
        } elseif ((int)$cap_data['reg_count'] >= (int)$cap_data['capacity']) {
            $msg = "Registration closed: This event has reached full capacity.";
            $msg_type = "danger";
        } else {
            $stmt = $conn->prepare("INSERT INTO registrations (event_id, student_name, matric_no, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $event_id, $student_name, $matric_no, $email);
            if ($stmt->execute()) {
                $msg = "Registration confirmed! This event has been added to your personal schedule.";
                $msg_type = "success";
            } else {
                $msg = "You are already registered for this event (" . e($email) . ").";
                $msg_type = "warning";
            }
            $stmt->close();
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$events_sql = "SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered_count 
               FROM events e ORDER BY e.event_date ASC, e.event_time ASC";
$events_res = $conn->query($events_sql);
$total_events = $events_res ? $events_res->num_rows : 0;
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow-text">The Campus Calendar</div>
        <h1 class="hero-heading mb-2">Find your <span>next thing.</span></h1>
        <p class="text-muted mb-0">Discover what's happening around campus. Your people are already there.</p>
    </div>
    <span class="topbar-pill fw-semibold text-primary">&bull; <?= $total_events ?> EVENTS</span>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
        <?= e($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Search & Category Filter Bar -->
<div class="cg-card mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="eventSearchInput" class="form-control border-start-0" placeholder="Search events by title or venue...">
            </div>
        </div>
        <div class="col-md-7 d-flex gap-2 flex-wrap justify-content-md-end">
            <button type="button" class="btn btn-sm btn-cg-primary active category-filter-btn" data-category="ALL">All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Academic">Academic</button>
            <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Tech & Innovation">Tech &amp; Innovation</button>
            <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Sports">Sports</button>
            <button type="button" class="btn btn-sm btn-outline-secondary category-filter-btn" data-category="Social & Cultural">Social &amp; Cultural</button>
        </div>
    </div>
</div>

<?php if ($total_events > 0): ?>
    <div class="row g-4">
        <?php while ($ev = $events_res->fetch_assoc()): 
            $capacity = (int)$ev['capacity'];
            $registered = (int)$ev['registered_count'];
            $spots_left = max(0, $capacity - $registered);
            $is_full = ($spots_left <= 0);
        ?>
            <div class="col-md-6 col-lg-4 event-item-card"
                 data-title="<?= strtolower(e($ev['title'])) ?>"
                 data-venue="<?= strtolower(e($ev['venue'])) ?>"
                 data-category="<?= e($ev['category']) ?>">
                <div class="cg-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary-subtle text-primary"><?= e($ev['category']) ?></span>
                            <?php if ($is_full): ?>
                                <span class="badge bg-danger-subtle text-danger">0 spots left &bull; FULL</span>
                            <?php else: ?>
                                <span class="small text-muted fw-semibold"><?= $spots_left ?> / <?= $capacity ?> spots left</span>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold mb-2"><?= e($ev['title']) ?></h5>
                        <p class="small text-muted mb-3"><?= e($ev['description']) ?></p>
                    </div>

                    <div>
                        <div class="small text-muted mb-3">
                            <div><i class="bi bi-calendar-event me-1"></i> <?= date('D, M j, Y', strtotime($ev['event_date'])) ?> at <?= date('g:i A', strtotime($ev['event_time'])) ?></div>
                            <div><i class="bi bi-geo-alt me-1"></i> <?= e($ev['venue']) ?></div>
                        </div>
                        <?php if ($is_full): ?>
                            <button type="button" class="btn btn-secondary w-100" disabled>Event Full</button>
                        <?php else: ?>
                            <button type="button"
                                    class="btn btn-cg-primary w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#registerModal"
                                    onclick="document.getElementById('modalEventId').value='<?= $ev['id'] ?>'; document.getElementById('modalEventTitle').textContent='<?= e(addslashes($ev['title'])) ?>';">
                                Register for Event
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="empty-state-box">No events yet. Check back soon.</div>
<?php endif; ?>

<!-- Registration Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <form method="POST" action="events.php" class="needs-validation" novalidate>
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="eyebrow-text mb-0">Event Registration</div>
                        <h5 class="modal-title fw-bold" id="modalEventTitle">Register</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="register_event" value="1">
                    <input type="hidden" name="event_id" id="modalEventId">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name</label>
                        <input type="text" name="student_name" class="form-control" value="<?= e($_SESSION['full_name'] ?? '') ?>" required>
                    </div>

                    <?php if (!$is_staff_account): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Matriculation Number</label>
                            <input type="text" name="matric_no" class="form-control" value="<?= e($_SESSION['matric_no'] ?? '') ?>" placeholder="e.g. 123456789" required>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <span class="badge bg-info-subtle text-dark border">Registering with University Staff Account</span>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">University Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($_SESSION['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cg-primary">Confirm Spot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>