<?php
$page_crumb = 'ADMIN-PORTAL';
require_once __DIR__ . '/includes/db_connect.php';
requireAuth();

$status_msg = '';
$status_type = 'success';
$current_user_id = (int)$_SESSION['user_id'];
$current_role = $_SESSION['role'];

// Sync session role with database in case an admin just approved their request
$sync_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$sync_stmt->bind_param("i", $current_user_id);
$sync_stmt->execute();
if ($sync_row = $sync_stmt->get_result()->fetch_assoc()) {
    $_SESSION['role'] = $sync_row['role'];
    $current_role = $sync_row['role'];
}
$sync_stmt->close();

// A. Handle Self-Service Role Request Submission (Students & Organizers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_role_request']) && $current_role !== 'admin') {
    $req_role = $_POST['requested_role'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (!in_array($req_role, ['organizer', 'admin'])) {
        $status_msg = "Please select a valid role.";
        $status_type = "danger";
    } elseif ($req_role === $current_role) {
        $status_msg = "You already hold " . strtoupper($current_role) . " privileges.";
        $status_type = "warning";
    } else {
        // Check if user already has a pending request
        $chk = $conn->prepare("SELECT id FROM role_requests WHERE user_id = ? AND status = 'pending'");
        $chk->bind_param("i", $current_user_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $status_msg = "You already have a pending access request awaiting administrator review.";
            $status_type = "warning";
        } else {
            $ins = $conn->prepare("INSERT INTO role_requests (user_id, requested_role, reason) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $current_user_id, $req_role, $reason);
            if ($ins->execute()) {
                $status_msg = "Your request for " . strtoupper($req_role) . " access has been submitted for approval.";
                $status_type = "success";
            }
            $ins->close();
        }
        $chk->close();
    }
}

// B. Handle Admin Actions (Approve/Deny Requests & Direct Role Updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_role === 'admin') {
    if (isset($_POST['review_request'])) {
        $req_id = (int)$_POST['request_id'];
        $decision = $_POST['decision'] ?? '';

        if (in_array($decision, ['approved', 'denied'])) {
            $fetch = $conn->prepare("SELECT user_id, requested_role FROM role_requests WHERE id = ? AND status = 'pending'");
            $fetch->bind_param("i", $req_id);
            $fetch->execute();
            if ($req_data = $fetch->get_result()->fetch_assoc()) {
                $upd_req = $conn->prepare("UPDATE role_requests SET status = ? WHERE id = ?");
                $upd_req->bind_param("si", $decision, $req_id);
                $upd_req->execute();
                $upd_req->close();

                if ($decision === 'approved') {
                    $upd_user = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $upd_user->bind_param("si", $req_data['requested_role'], $req_data['user_id']);
                    $upd_user->execute();
                    $upd_user->close();
                    $status_msg = "Request approved! User elevated to " . strtoupper($req_data['requested_role']) . ".";
                } else {
                    $status_msg = "Access request denied.";
                    $status_type = "info";
                }
            }
            $fetch->close();
        }
    }

    if (isset($_POST['update_user_role'])) {
        $target_id = (int)$_POST['target_user_id'];
        $new_role = $_POST['new_role'] ?? 'student';
        if (in_array($new_role, ['student', 'organizer', 'admin'])) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $target_id);
            $stmt->execute();
            $stmt->close();

            if ($target_id === $current_user_id) {
                $_SESSION['role'] = $new_role;
                $current_role = $new_role;
            }
            $status_msg = "User role updated to " . strtoupper($new_role) . ".";
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Fetch data depending on current role
$pending_requests = null;
$users_res = null;
$my_requests = [];

if ($current_role === 'admin') {
    $pending_requests = $conn->query("SELECT rr.*, u.full_name, u.matric_no, u.email 
                                      FROM role_requests rr 
                                      JOIN users u ON rr.user_id = u.id 
                                      WHERE rr.status = 'pending' 
                                      ORDER BY rr.created_at ASC");
    $users_res = $conn->query("SELECT id, full_name, matric_no, email, role FROM users ORDER BY id ASC");
} else {
    $my_stmt = $conn->prepare("SELECT * FROM role_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $my_stmt->bind_param("i", $current_user_id);
    $my_stmt->execute();
    $res = $my_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $my_requests[] = $row;
    }
    $my_stmt->close();
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow-text">CampusGist Control Room</div>
        <h1 class="hero-heading mb-1">Admin <span>portal.</span></h1>
        <p class="text-muted mb-0">
            <?php if ($current_role === 'admin'): ?>
                Your administrator access is active.
            <?php else: ?>
                Signed in as <strong><?= strtoupper(e($current_role)) ?></strong>. Request elevated workspace or admin privileges below.
            <?php endif; ?>
        </p>
    </div>

    <span class="topbar-pill text-primary fw-bold"><?= strtoupper(e($current_role)) ?> ACCESS</span>
</div>

<?php if ($status_msg): ?>
    <div class="alert alert-<?= $status_type ?> py-2 small"><?= e($status_msg) ?></div>
<?php endif; ?>

<?php if ($current_role === 'admin'): ?>
    <!-- Confirmed Admin Banner -->
    <div class="cg-card mx-auto mb-4 p-4" style="max-width: 820px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-1">Administrator access confirmed</h5>
                <p class="text-muted small mb-0">Review incoming role requests or manage existing campus accounts.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-sm btn-cg-primary px-3">Open workspace &#8599;</a>
                <form method="POST" action="admin.php" class="d-inline">
                    <input type="hidden" name="update_user_role" value="1">
                    <input type="hidden" name="target_user_id" value="<?= $current_user_id ?>">
                    <input type="hidden" name="new_role" value="student">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Turn off admin access</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Pending Role Requests Queue -->
    <div class="cg-card mx-auto mb-4" style="max-width: 820px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="eyebrow-text text-primary mb-0">Approval Queue</div>
                <h5 class="fw-bold mb-0">Pending Role Requests (<?= $pending_requests ? $pending_requests->num_rows : 0 ?>)</h5>
            </div>
        </div>

        <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Applicant</th>
                            <th>Requested Role</th>
                            <th>Reason</th>
                            <th class="text-end">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($req = $pending_requests->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($req['full_name']) ?></div>
                                    <div class="small text-muted"><?= e($req['matric_no']) ?> &bull; <?= e($req['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-dark border border-warning">
                                        <?= strtoupper(e($req['requested_role'])) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= e($req['reason'] ?: 'No reason provided') ?></td>
                                <td class="text-end text-nowrap">
                                    <form method="POST" action="admin.php" class="d-inline">
                                        <input type="hidden" name="review_request" value="1">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <button type="submit" name="decision" value="approved" class="btn btn-sm btn-success">Approve</button>
                                        <button type="submit" name="decision" value="denied" class="btn btn-sm btn-outline-danger">Deny</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state-box py-3 text-center">No pending role requests at the moment.</div>
        <?php endif; ?>
    </div>

    <!-- All Registered Accounts Table -->
    <div class="cg-card mx-auto" style="max-width: 820px;">
        <div class="eyebrow-text mb-1">Role-Based Access Control (RBAC)</div>
        <h5 class="fw-bold mb-3">Registered Campus Accounts</h5>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Matric No.</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th class="text-end">Assign Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users_res->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                            <td class="small"><?= $u['matric_no'] ? e($u['matric_no']) : '<span class="badge bg-info-subtle text-dark border">STAFF</span>' ?></td>
                            <td class="small"><?= e($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-primary' : ($u['role'] === 'organizer' ? 'bg-info text-dark' : 'bg-secondary') ?>">
                                    <?= strtoupper(e($u['role'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="admin.php" class="d-inline-flex gap-1">
                                    <input type="hidden" name="update_user_role" value="1">
                                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                    <select name="new_role" class="form-select form-select-sm" style="width: auto;">
                                        <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="organizer" <?= $u['role'] === 'organizer' ? 'selected' : '' ?>>Organizer</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- Self-Service Role Request Form (Students & Organizers) -->
    <div class="row g-4 mx-auto" style="max-width: 820px;">
        <div class="col-md-6">
            <div class="cg-card h-100">
                <div class="eyebrow-text text-primary mb-1">Self-Service Access</div>
                <h5 class="fw-bold mb-2">Request Elevated Role</h5>
                <p class="small text-muted mb-3">
                    Need to publish campus events or manage portal users? Submit a role request for administrator approval.
                </p>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="submit_role_request" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Requested Role</label>
                        <select name="requested_role" class="form-select" required>
                            <?php if ($current_role === 'student'): ?>
                                <option value="organizer">Organizer (Create &amp; manage events)</option>
                            <?php endif; ?>
                            <option value="admin">Administrator (Full portal &amp; user control)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason / Department (Optional)</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. NACOS Event Coordinator">
                    </div>
                    <button type="submit" class="btn btn-cg-primary w-100">Submit Access Request &#8599;</button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="cg-card h-100">
                <div class="eyebrow-text mb-1">Request History</div>
                <h5 class="fw-bold mb-3">Your Access Requests</h5>
                <?php if (count($my_requests) > 0): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($my_requests as $mr): ?>
                            <div class="p-3 rounded border bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small"><?= strtoupper(e($mr['requested_role'])) ?> ACCESS</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        <?= date('M j, Y • g:i A', strtotime($mr['created_at'])) ?>
                                    </div>
                                </div>
                                <?php if ($mr['status'] === 'approved'): ?>
                                    <span class="badge bg-success">APPROVED</span>
                                <?php elseif ($mr['status'] === 'denied'): ?>
                                    <span class="badge bg-danger">DENIED</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">PENDING</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-box py-4 text-center">You haven't submitted any role requests yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>