<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, name, password FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id, $name, $password_hash);

        if (mysqli_stmt_fetch($stmt) && password_verify($password, $password_hash)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $name;
            mysqli_stmt_close($stmt);
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid email or password.';
        mysqli_stmt_close($stmt);
    }
}

$pageTitle = 'Login - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Login</h1>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <button type="button" class="btn-secondary" data-toggle-target="password">Show/Hide</button>

        <div style="margin-top: 1rem;">
            <button type="submit">Login</button>
        </div>
    </form>
    <p>Don't have an account? <a href="signup.php">Register here</a>.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
