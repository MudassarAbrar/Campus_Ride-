<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$error = '';
$success = '';
$user_id = (int) $_SESSION['user_id'];

$vehicles = [];
$stmt_vehicles = mysqli_prepare($conn, 'SELECT id, vehicle_type, vehicle_name, passenger_capacity FROM vehicles WHERE user_id = ? ORDER BY created_at DESC');
mysqli_stmt_bind_param($stmt_vehicles, 'i', $user_id);
mysqli_stmt_execute($stmt_vehicles);
$result_vehicles = mysqli_stmt_get_result($stmt_vehicles);
while ($row = mysqli_fetch_assoc($result_vehicles)) {
    $vehicles[] = $row;
}
mysqli_stmt_close($stmt_vehicles);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_area = trim($_POST['pickup_area'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $departure_date = trim($_POST['departure_date'] ?? '');
    $departure_time = trim($_POST['departure_time'] ?? '');
    $vehicle_id = (int) ($_POST['vehicle_id'] ?? 0);
    $total_seats = (int) ($_POST['total_seats'] ?? 0);
    $fare = $_POST['fare'] === '' ? 0 : (float) $_POST['fare'];
    $description = trim($_POST['description'] ?? '');

    if ($pickup_area === '' || $destination === '' || $departure_date === '' || $departure_time === '' || $vehicle_id <= 0 || $total_seats <= 0) {
        $error = 'Please complete all required fields.';
    } elseif ($departure_date < date('Y-m-d')) {
        $error = 'Departure date cannot be in the past.';
    } elseif ($fare < 0) {
        $error = 'Fare cannot be negative.';
    } else {
        $capacity_stmt = mysqli_prepare($conn, 'SELECT passenger_capacity FROM vehicles WHERE id = ? AND user_id = ? LIMIT 1');
        mysqli_stmt_bind_param($capacity_stmt, 'ii', $vehicle_id, $user_id);
        mysqli_stmt_execute($capacity_stmt);
        mysqli_stmt_bind_result($capacity_stmt, $capacity);
        $has_vehicle = mysqli_stmt_fetch($capacity_stmt);
        mysqli_stmt_close($capacity_stmt);

        if (!$has_vehicle) {
            $error = 'Invalid vehicle selected.';
        } elseif ($total_seats > (int) $capacity) {
            $error = 'Available seats cannot exceed vehicle capacity.';
        } else {
            $insert = mysqli_prepare($conn, 'INSERT INTO rides (rider_id, vehicle_id, pickup_area, destination, departure_date, departure_time, total_seats, available_seats, fare, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active")');
            mysqli_stmt_bind_param($insert, 'iissssiids', $user_id, $vehicle_id, $pickup_area, $destination, $departure_date, $departure_time, $total_seats, $total_seats, $fare, $description);

            if (mysqli_stmt_execute($insert)) {
                $success = 'Ride published successfully.';
                $_POST = [];
            } else {
                $error = 'Failed to publish ride. Please try again.';
            }
            mysqli_stmt_close($insert);
        }
    }
}

$pageTitle = 'Offer Ride - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Publish a Ride</h1>
    <?php if (empty($vehicles)): ?>
        <div class="alert alert-error">Please <a href="add_vehicle.php">add a vehicle</a> before publishing a ride.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <form method="post" class="grid">
        <div>
            <label for="pickup_area">Pickup Area</label>
            <input id="pickup_area" name="pickup_area" value="<?php echo htmlspecialchars($_POST['pickup_area'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="destination">Destination</label>
            <input id="destination" name="destination" value="<?php echo htmlspecialchars($_POST['destination'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="departure_date">Departure Date</label>
            <input id="departure_date" type="date" name="departure_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['departure_date'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="departure_time">Departure Time</label>
            <input id="departure_time" type="time" name="departure_time" value="<?php echo htmlspecialchars($_POST['departure_time'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="vehicle_id">Vehicle</label>
            <select id="vehicle_id" name="vehicle_id" required>
                <option value="">Select vehicle</option>
                <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?php echo (int) $vehicle['id']; ?>" <?php echo ((int) ($_POST['vehicle_id'] ?? 0) === (int) $vehicle['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vehicle['vehicle_type'] . ' - ' . $vehicle['vehicle_name'] . ' (Capacity: ' . $vehicle['passenger_capacity'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="total_seats">Available Seats</label>
            <input id="total_seats" type="number" min="1" name="total_seats" value="<?php echo htmlspecialchars($_POST['total_seats'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="fare">Fare per Passenger (optional)</label>
            <input id="fare" type="number" min="0" step="0.01" name="fare" value="<?php echo htmlspecialchars($_POST['fare'] ?? '0'); ?>">
        </div>
        <div>
            <label for="description">Additional Description (optional)</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <div style="grid-column: 1 / -1;"><button type="submit" <?php echo empty($vehicles) ? 'disabled' : ''; ?>>Publish Ride</button></div>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
