<?php
$page_crumb = 'MANAGE';
require_once __DIR__ . '/includes/db_connect.php';

if (!isStaff()) {
    header("Location: admin.php");
    exit;
}

$feedback = '';
$feedback_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

<div class="mb-4">
    <div class="eyebrow-text">Publish Something Great</div>
    <h1 class="hero-heading mb-2">Create an <span>event.</span></h1>
    <p class="text-muted mb-0">Give your campus something to look forward to.</p>
</div>

<?php if ($feedback): ?>
    <div class="alert alert-<?= $feedback_type ?> alert-dismissible fade show">
        <?= e($feedback) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Card 1: Create Event Form -->
<div class="cg-card mb-4" style="max-width: 780px;">
    <form method="POST" action="manage.php" class="needs-validation" novalidate>
        <input type="hidden" name="create_event" value="1">

        <div class="mb-3">
            <label class="eyebrow-text d-block">Event Name</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Tech & Innovation Summit" required>
        </div>

        <div class="mb-3">
            <label class="eyebrow-text d-block">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="What should people know?" required></textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Venue</label>
                <input type="text" name="venue" class="form-control" placeholder="Main Auditorium" required>
            </div>
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Category</label>
                <select name="category" class="form-select">
                    <option value="Academic">Academic</option>
                    <option value="Tech & Innovation">Tech &amp; Innovation</option>
                    <option value="Sports">Sports</option>
                    <option value="Social & Cultural">Social &amp; Cultural</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Event Date</label>
                <input type="date" name="event_date" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="eyebrow-text d-block">Start Time</label>
                <input type="time" name="event_time" class="form-control" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="eyebrow-text d-block">Capacity</label>
            <input type="number" name="capacity" class="form-control" value="100" min="1" required>
        </div>

        <button type="submit" class="btn btn-cg-primary w-100 py-2">Publish event &#8599;</button>
    </form>
</div>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>