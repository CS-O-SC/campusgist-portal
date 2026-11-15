<?php
$page_crumb = 'MANAGE';
require_once __DIR__ . '/includes/db_connect.php';
requireAuth();

if (!isStaff()) {
    header("Location: admin.php");
    exit;
}

$feedback = '';
$feedback_type = 'success';

// Check if editing an existing event
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_event = null;
if ($edit_id > 0) {
    $ed_stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $ed_stmt->bind_param("i", $edit_id);
    $ed_stmt->execute();
    $edit_event = $ed_stmt->get_result()->fetch_assoc();
    $ed_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // UPDATE EXISTING EVENT
    if (isset($_POST['update_event'])) {
        $ev_id = (int)($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $category = $_POST['category'] ?? 'Academic';
        $event_date = $_POST['event_date'] ?? '';
        $event_time = $_POST['event_time'] ?? '';
        $capacity = max(1, (int)($_POST['capacity'] ?? 100));

        if ($ev_id > 0 && $title && $description && $venue && $event_date && $event_time) {
            $stmt = $conn->prepare("UPDATE events SET title = ?, category = ?, description = ?, event_date = ?, event_time = ?, venue = ?, capacity = ? WHERE id = ?");
            $stmt->bind_param("ssssssii", $title, $category, $description, $event_date, $event_time, $venue, $capacity, $ev_id);
            if ($stmt->execute()) {
                header("Location: dashboard.php?updated=1");
                exit;
            }
            $stmt->close();
        } else {
            $feedback = "Please fill in all required fields to update the event.";
            $feedback_type = "danger";
        }
    }

    // CREATE NEW EVENT
    if (isset($_POST['create_event'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $category = $_POST['category'] ?? 'Academic';
        $event_date = $_POST['event_date'] ?? '';
        $event_time = $_POST['event_time'] ?? '';
        $capacity = max(1, (int)($_POST['capacity'] ?? 100));

        if ($title && $description && $venue && $event_date && $event_time) {
            $stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, event_time, venue, capacity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $title, $category, $description, $event_date, $event_time, $venue, $capacity);
            if ($stmt->execute()) {
                $feedback = "Event '" . e($title) . "' published to the Campus Calendar!";
                $feedback_type = "success";
            } else {
                $feedback = "Error publishing event: " . $conn->error;
                $feedback_type = "danger";
            }
            $stmt->close();
        } else {
            $feedback = "Please fill in all required event fields.";
            $feedback_type = "danger";
        }
    }

    // POST ANNOUNCEMENT
    if (isset($_POST['post_announcement'])) {
        $headline = trim($_POST['headline'] ?? '');
        $message_body = trim($_POST['message_body'] ?? '');
        $priority = $_POST['priority'] ?? 'Normal';

        if ($headline && $message_body) {
            $stmt = $conn->prepare("INSERT INTO announcements (headline, message_body, priority) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $headline, $message_body, $priority);
            if ($stmt->execute()) {
                $feedback = "Announcement posted to the Campus Bulletin!";
                $feedback_type = "success";
            }
            $stmt->close();
        } else {
            $feedback = "Please enter both a headline and message body.";
            $feedback_type = "danger";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4" style="max-width: 780px;">
    <div>
        <div class="eyebrow-text"><?= $edit_event ? 'Update Event Details' : 'Publish Something Great' ?></div>
        <h1 class="hero-heading mb-2"><?= $edit_event ? 'Edit <span>event.</span>' : 'Create an <span>event.</span>' ?></h1>
        <p class="text-muted mb-0">
            <?= $edit_event ? 'Modify schedule, venue, or capacity details below.' : 'Give your campus something to look forward to.' ?>
        </p>
    </div>
    <?php if ($edit_event): ?>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Cancel Edit</a>
    <?php endif; ?>
</div>

<?php if ($feedback): ?>
    <div class="alert alert-<?= $feedback_type ?> alert-dismissible fade show" style="max-width: 780px;">
        <?= e($feedback) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Card 1: Create or Edit Event Form -->
<div class="cg-card mb-4" style="max-width: 780px;">
    <form method="POST" action="manage.php<?= $edit_event ? '?edit=' . $edit_event['id'] : '' ?>" class="needs-validation" novalidate>
        <?php if ($edit_event): ?>
            <input type="hidden" name="update_event" value="1">
            <input type="hidden" name="event_id" value="<?= $edit_event['id'] ?>">
        <?php else: ?>
            <input type="hidden" name="create_event" value="1">
        <?php endif; ?>

        <div class="mb-3">
            <label class="eyebrow-text d-block">Event Name</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Tech & Innovation Summit" value="<?= e($edit_event['title'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="eyebrow-text d-block">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="What should people know?" required><?= e($edit_event['description'] ?? '') ?></textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Venue</label>
                <input type="text" name="venue" class="form-control" placeholder="Main Auditorium" value="<?= e($edit_event['venue'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Category</label>
                <?php $sel_cat = $edit_event['category'] ?? 'Academic'; ?>
                <select name="category" class="form-select">
                    <option value="Academic" <?= $sel_cat === 'Academic' ? 'selected' : '' ?>>Academic</option>
                    <option value="Tech & Innovation" <?= $sel_cat === 'Tech & Innovation' ? 'selected' : '' ?>>Tech &amp; Innovation</option>
                    <option value="Sports" <?= $sel_cat === 'Sports' ? 'selected' : '' ?>>Sports</option>
                    <option value="Social & Cultural" <?= $sel_cat === 'Social & Cultural' ? 'selected' : '' ?>>Social &amp; Cultural</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Event Date</label>
                <input type="date" name="event_date" class="form-control" value="<?= e($edit_event['event_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Start Time</label>
                <input type="time" name="event_time" class="form-control" value="<?= e($edit_event['event_time'] ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="eyebrow-text d-block">Capacity</label>
            <input type="number" name="capacity" class="form-control" value="<?= e($edit_event['capacity'] ?? 100) ?>" min="1" required>
        </div>

        <button type="submit" class="btn btn-cg-primary w-100 py-2">
            <?= $edit_event ? 'Save Changes &#8599;' : 'Publish event &#8599;' ?>
        </button>
    </form>
</div>

<?php if (!$edit_event): ?>
<!-- Card 2: Post Campus Announcement Form -->
<div class="cg-card" style="max-width: 780px;">
    <h5 class="fw-bold mb-3">Post a campus announcement</h5>
    <form method="POST" action="manage.php" class="needs-validation" novalidate>
        <input type="hidden" name="post_announcement" value="1">

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label class="eyebrow-text d-block">Headline</label>
                <input type="text" name="headline" class="form-control" placeholder="Announcement title" required>
            </div>
            <div class="col-md-4">
                <label class="eyebrow-text d-block">Priority</label>
                <select name="priority" class="form-select">
                    <option value="Normal">Normal</option>
                    <option value="Urgent">Urgent</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="eyebrow-text d-block">Message</label>
            <textarea name="message_body" class="form-control" rows="3" placeholder="Share an update with everyone" required></textarea>
        </div>

        <button type="submit" class="btn btn-outline-dark w-100 py-2 fw-semibold">Post announcement &#8599;</button>
    </form>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>