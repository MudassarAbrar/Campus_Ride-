<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $home_area = trim($_POST['home_area'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $confirm_password === '' || $phone === '' || $university === '' || $home_area === '' || $student_id === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? OR student_id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $email, $student_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Email or student ID already exists.';
        }
        mysqli_stmt_close($stmt);

        if ($error === '') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = mysqli_prepare($conn, 'INSERT INTO users (name, email, password, phone, university, home_area, student_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($insert, 'sssssss', $name, $email, $hashed_password, $phone, $university, $home_area, $student_id);

            if (mysqli_stmt_execute($insert)) {
                $_SESSION['flash_success'] = 'Registration successful. Please login.';
                mysqli_stmt_close($insert);
                header('Location: login.php');
                exit;
            }

            $error = 'Registration failed. Please try again.';
            mysqli_stmt_close($insert);
        }
    }
}

$pageTitle = 'Sign Up - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Create Account</h1>
    <p class="muted">Register as a student to offer or request rides.</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" class="grid">
        <div>
            <label for="name">Full Name</label>
            <input id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="student_id">Student ID</label>
            <input id="student_id" name="student_id" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="university">University</label>
            <input id="university" name="university" value="<?php echo htmlspecialchars($_POST['university'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="home_area">Home Area</label>
            <input id="home_area" name="home_area" value="<?php echo htmlspecialchars($_POST['home_area'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
            <button type="button" class="btn-secondary" data-toggle-target="password">Show/Hide</button>
        </div>
        <div>
            <label for="confirm_password">Confirm Password</label>
            <input id="confirm_password" type="password" name="confirm_password" required>
            <button type="button" class="btn-secondary" data-toggle-target="confirm_password">Show/Hide</button>
        </div>
        <div style="grid-column: 1 / -1;">
            <button type="submit">Register</button>
        </div>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
