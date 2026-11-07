<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$error = '';
$success = '';
$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $vehicle_name = trim($_POST['vehicle_name'] ?? '');
    $registration_number = trim($_POST['registration_number'] ?? '');
    $passenger_capacity = (int) ($_POST['passenger_capacity'] ?? 0);

    $allowed_types = ['Bike', 'Motorcycle', 'Car'];

    if ($vehicle_type === '' || $vehicle_name === '' || $registration_number === '' || $passenger_capacity <= 0) {
        $error = 'All fields are required and capacity must be greater than zero.';
    } elseif (!in_array($vehicle_type, $allowed_types, true)) {
        $error = 'Invalid vehicle type selected.';
    } else {
        $stmt = mysqli_prepare($conn, 'INSERT INTO vehicles (user_id, vehicle_type, vehicle_name, registration_number, passenger_capacity) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'isssi', $user_id, $vehicle_type, $vehicle_name, $registration_number, $passenger_capacity);

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Vehicle added successfully.';
            $_POST = [];
        } else {
            $error = 'Unable to add vehicle. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}

$vehicles = [];
$list_stmt = mysqli_prepare($conn, 'SELECT id, vehicle_type, vehicle_name, registration_number, passenger_capacity, created_at FROM vehicles WHERE user_id = ? ORDER BY created_at DESC');
mysqli_stmt_bind_param($list_stmt, 'i', $user_id);
mysqli_stmt_execute($list_stmt);
$result = mysqli_stmt_get_result($list_stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $vehicles[] = $row;
}
mysqli_stmt_close($list_stmt);

$pageTitle = 'Add Vehicle - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Add Vehicle</h1>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <form method="post" class="grid">
        <div>
            <label for="vehicle_type">Vehicle Type</label>
            <select id="vehicle_type" name="vehicle_type" required>
                <option value="">Select</option>
                <?php foreach (['Bike', 'Motorcycle', 'Car'] as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo (($_POST['vehicle_type'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="vehicle_name">Vehicle Name / Model</label>
            <input id="vehicle_name" name="vehicle_name" value="<?php echo htmlspecialchars($_POST['vehicle_name'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="registration_number">Registration Number</label>
            <input id="registration_number" name="registration_number" value="<?php echo htmlspecialchars($_POST['registration_number'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="passenger_capacity">Passenger Capacity</label>
            <input id="passenger_capacity" type="number" min="1" name="passenger_capacity" value="<?php echo htmlspecialchars($_POST['passenger_capacity'] ?? ''); ?>" required>
        </div>
        <div style="grid-column: 1 / -1;"><button type="submit">Save Vehicle</button></div>
    </form>
</section>

<section class="card">
    <h2>My Vehicles</h2>
    <?php if (empty($vehicles)): ?>
        <p class="muted">No vehicles added yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <tr><th>Type</th><th>Model</th><th>Registration</th><th>Capacity</th><th>Added</th></tr>
                <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['vehicle_name']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                        <td><?php echo (int) $vehicle['passenger_capacity']; ?></td>
                        <td><?php echo htmlspecialchars($vehicle['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
