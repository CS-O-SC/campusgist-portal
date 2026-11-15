<?php
require_once __DIR__ . '/includes/db_connect.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register') {
        $mode = 'register';
        $account_type = ($_POST['account_type'] ?? 'student') === 'staff' ? 'staff' : 'student';
        $full_name = trim($_POST['full_name'] ?? '');
        $matric_no = $account_type === 'student' ? strtoupper(trim($_POST['matric_no'] ?? '')) : null;
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$full_name || ($account_type === 'student' && !$matric_no) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $error = "Please complete all required fields (password must be at least 6 characters).";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, account_type, matric_no, email, password_hash, role) VALUES (?, ?, ?, ?, ?, 'student')");
            $stmt->bind_param("sssss", $full_name, $account_type, $matric_no, $email, $hash);

            if ($stmt->execute()) {
                $success = ucfirst($account_type) . " account created! You can now sign in below.";
                $mode = 'login';
            } else {
                $error = "An account with that email or matric number already exists.";
            }
            $stmt->close();
        }
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, full_name, account_type, matric_no, email, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['account_type'] = $user['account_type'];
                $_SESSION['matric_no'] = $user['matric_no'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header("Location: index.php");
                exit;
            }
        }
        $error = "Invalid email address or password.";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — CampusGist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center p-3" style="min-height: 100vh;">

<div class="cg-card shadow-sm" style="max-width: 430px; width: 100%; border-radius: 22px; padding: 2.25rem;">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center gap-2 mb-1">
            <div class="brand-logo" style="background: var(--cg-primary);">C</div>
            <span class="fs-4 fw-bold">Campusgist</span>
        </div>
        <p class="text-muted small mb-0">
            <?= $mode === 'register' ? 'Create your campus portal account' : 'Sign in to continue' ?>
        </p>
    </div>

    <?php if (isset($_GET['auth']) && $_GET['auth'] === 'required' && !$error && !$success): ?>
        <div class="alert alert-primary py-2 small text-center">
            Please sign in or create an account to access CampusGist.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2 small"><?= e($success) ?></div>
    <?php endif; ?>

    <!-- Mode Switcher -->
    <div class="btn-group w-100 mb-3" role="group">
        <a href="login.php?mode=login" class="btn btn-sm <?= $mode === 'login' ? 'btn-cg-primary' : 'btn-outline-secondary' ?>">Sign In</a>
        <a href="login.php?mode=register" class="btn btn-sm <?= $mode === 'register' ? 'btn-cg-primary' : 'btn-outline-secondary' ?>">Create Account</a>
    </div>

    <?php if ($mode === 'register'): ?>
        <form method="POST" action="login.php?mode=register" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="register">

            <div class="mb-3">
                <label class="form-label small fw-semibold">I am registering as</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="account_type" id="typeStudent" value="student" checked onchange="toggleAccountType()">
                        <label class="btn btn-outline-primary btn-sm w-100 py-2 fw-semibold" for="typeStudent">Student</label>
                    </div>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="account_type" id="typeStaff" value="staff" onchange="toggleAccountType()">
                        <label class="btn btn-outline-primary btn-sm w-100 py-2 fw-semibold" for="typeStaff">University Staff</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="e.g. John Doe" required>
            </div>

            <div class="mb-3" id="matricFieldGroup">
                <label class="form-label small fw-semibold">Matric Number</label>
                <input type="text" name="matric_no" id="matricInput" class="form-control" placeholder="e.g. 123456789" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" id="emailLabel">University Email</label>
                <input type="email" name="email" id="emailInput" class="form-control" placeholder="studentID@nileuniversity.edu.ng" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Password (min 6 chars)</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-cg-primary w-100 py-2">Create Account</button>
        </form>
    <?php else: ?>
        <form method="POST" action="login.php" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <input type="email" name="email" class="form-control py-2" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control py-2" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-cg-primary w-100 py-2">Continue with email</button>
        </form>
    <?php endif; ?>

    <div class="text-center mt-4 text-muted" style="font-size: 0.7rem; letter-spacing: 0.06em;">
        NILE UNIVERSITY &bull; CAMPUSGIST PORTAL
    </div>
</div>

<script>
function toggleAccountType() {
    const isStaff = document.getElementById('typeStaff').checked;
    const matricGroup = document.getElementById('matricFieldGroup');
    const matricInput = document.getElementById('matricInput');
    const emailLabel = document.getElementById('emailLabel');
    const emailInput = document.getElementById('emailInput');

    if (isStaff) {
        matricGroup.style.display = 'none';
        matricInput.required = false;
        matricInput.value = '';
        emailLabel.textContent = 'Official Staff Email';
        emailInput.placeholder = 'firstname.lastname@nileuniversity.edu.ng';
    } else {
        matricGroup.style.display = 'block';
        matricInput.required = true;
        emailLabel.textContent = 'University Email';
        emailInput.placeholder = 'studentID@nileuniversity.edu.ng';
    }
}
</script>
<script src="js/app.js"></script>
</body>
</html>