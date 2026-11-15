<?php
$page_crumb = 'ADMIN-LOGIN';
require_once __DIR__ . '/includes/db_connect.php';
requireAuth();

$status_msg = '';

// Handle Role Changes (Only for Admins)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && $_SESSION['role'] === 'admin') {
    if (isset($_POST['update_user_role'])) {
        $target_id = (int)$_POST['target_user_id'];
        $new_role = $_POST['new_role'] ?? 'student';
        if (in_array($new_role, ['student', 'organizer', 'admin'])) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $target_id);
            $stmt->execute();
            $stmt->close();

            if ($target_id === (int)$_SESSION['user_id']) {
                $_SESSION['role'] = $new_role;
            }
            $status_msg = "User role updated to " . strtoupper($new_role) . ".";
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$users_res = null;
if (isLoggedIn() && $_SESSION['role'] === 'admin') {
    $users_res = $conn->query("SELECT id, full_name, matric_no, email, role, created_at FROM users ORDER BY id ASC");
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow-text">CampusGist Control Room</div>
        <h1 class="hero-heading mb-1">Admin <span>portal.</span></h1>
        <p class="text-muted mb-0">
            <?php if (!isLoggedIn()): ?>
                Sign in with an administrator account to access control tools.
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                Your administrator access is active.
            <?php else: ?>
                Your account currently holds <strong><?= strtoupper(e($_SESSION['role'])) ?></strong> privileges.
            <?php endif; ?>
        </p>
    </div>

    <?php if (isLoggedIn() && $_SESSION['role'] === 'admin'): ?>
        <span class="topbar-pill text-primary fw-bold">APPROVED ADMIN</span>
    <?php endif; ?>
</div>

<?php if ($status_msg): ?>
    <div class="alert alert-success py-2 small"><?= e($status_msg) ?></div>
<?php endif; ?>

<?php if (!isLoggedIn()): ?>
    <div class="cg-card text-center py-5 mx-auto" style="max-width: 560px;">
        <h4 class="fw-bold mb-2">Authentication Required</h4>
        <p class="text-muted small mb-4">Please sign in with an authorized campus administrator account to manage roles and workspace permissions.</p>
        <a href="login.php" class="btn btn-cg-primary px-4">Sign in to Continue &#8599;</a>
    </div>

<?php elseif ($_SESSION['role'] === 'admin'): ?>
    <!-- Confirmed Admin Card (Matching Admin Portal Screenshot) -->
    <div class="cg-card mx-auto mb-4 p-4 p-md-5" style="max-width: 640px;">
        <h4 class="fw-bold mb-2">Administrator access confirmed</h4>
        <p class="text-muted mb-4">
            You can use the organizer workspace and administrator tools. You can also switch your role back to standard student access at any time.
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <a href="dashboard.php" class="btn btn-cg-primary px-4">Open workspace &#8599;</a>
            <form method="POST" action="admin.php" class="d-inline">
                <input type="hidden" name="update_user_role" value="1">
                <input type="hidden" name="target_user_id" value="<?= $_SESSION['user_id'] ?>">
                <input type="hidden" name="new_role" value="student">
                <button type="submit" class="btn btn-outline-secondary">Turn off admin access</button>
            </form>
        </div>
    </div>

    <!-- Role-Based Access Control (RBAC) User Table -->
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
                            <td class="small"><?= e($u['matric_no']) ?></td>
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
    <div class="cg-card text-center py-5 mx-auto" style="max-width: 560px;">
        <h4 class="fw-bold mb-2">Restricted Area</h4>
        <p class="text-muted small mb-0">Administrator privileges are required to modify campus permissions. Contact an existing administrator to elevate your account.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>