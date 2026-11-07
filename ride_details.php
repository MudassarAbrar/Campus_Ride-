<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$user_id = (int) $_SESSION['user_id'];
$ride_id = (int) ($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($ride_id <= 0) {
    header('Location: search_rides.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requested_seats = (int) ($_POST['requested_seats'] ?? 0);
    $pickup_point = trim($_POST['pickup_point'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($requested_seats <= 0 || $pickup_point === '') {
        $error = 'Requested seats and pickup point are required.';
    } else {
        $ride_check = mysqli_prepare($conn, 'SELECT rider_id, available_seats, status FROM rides WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($ride_check, 'i', $ride_id);
        mysqli_stmt_execute($ride_check);
        mysqli_stmt_bind_result($ride_check, $rider_id, $available_seats, $ride_status);
        $ride_exists = mysqli_stmt_fetch($ride_check);
        mysqli_stmt_close($ride_check);

        if (!$ride_exists || $ride_status !== 'active') {
            $error = 'Ride is not available.';
        } elseif ((int) $rider_id === $user_id) {
            $error = 'You cannot request a seat on your own ride.';
        } elseif ($requested_seats > (int) $available_seats) {
            $error = 'Requested seats exceed available seats.';
        } else {
            $dupe = mysqli_prepare($conn, 'SELECT id FROM ride_requests WHERE ride_id = ? AND passenger_id = ? AND status IN ("pending", "accepted") LIMIT 1');
            mysqli_stmt_bind_param($dupe, 'ii', $ride_id, $user_id);
            mysqli_stmt_execute($dupe);
            mysqli_stmt_store_result($dupe);

            if (mysqli_stmt_num_rows($dupe) > 0) {
                $error = 'You already have an active request for this ride.';
            }
            mysqli_stmt_close($dupe);

            if ($error === '') {
                $insert = mysqli_prepare($conn, 'INSERT INTO ride_requests (ride_id, passenger_id, requested_seats, pickup_point, message, status) VALUES (?, ?, ?, ?, ?, "pending")');
                mysqli_stmt_bind_param($insert, 'iiiss', $ride_id, $user_id, $requested_seats, $pickup_point, $message);

                if (mysqli_stmt_execute($insert)) {
                    $success = 'Seat request submitted successfully.';
                    $_POST = [];
                } else {
                    $error = 'Unable to submit request. Please try again.';
                }
                mysqli_stmt_close($insert);
            }
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT r.id, r.rider_id, r.pickup_area, r.destination, r.departure_date, r.departure_time, r.available_seats, r.total_seats, r.fare, r.description, r.status,
                                     u.name AS rider_name, u.phone AS rider_phone, v.vehicle_type, v.vehicle_name
                              FROM rides r
                              JOIN users u ON u.id = r.rider_id
                              JOIN vehicles v ON v.id = r.vehicle_id
                              WHERE r.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $ride_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ride = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$ride) {
    header('Location: search_rides.php');
    exit;
}

$pageTitle = 'Ride Details - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Ride Details</h1>
    <p><strong>Route:</strong> <?php echo htmlspecialchars($ride['pickup_area']); ?> → <?php echo htmlspecialchars($ride['destination']); ?></p>
    <p><strong>Departure:</strong> <?php echo htmlspecialchars($ride['departure_date']); ?> at <?php echo htmlspecialchars($ride['departure_time']); ?></p>
    <p><strong>Rider:</strong> <?php echo htmlspecialchars($ride['rider_name']); ?> (<?php echo htmlspecialchars($ride['rider_phone']); ?>)</p>
    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($ride['vehicle_type'] . ' - ' . $ride['vehicle_name']); ?></p>
    <p><strong>Seats:</strong> <?php echo (int) $ride['available_seats']; ?> / <?php echo (int) $ride['total_seats']; ?> available</p>
    <p><strong>Fare:</strong> <?php echo number_format((float) $ride['fare'], 2); ?></p>
    <?php if (!empty($ride['description'])): ?><p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($ride['description'])); ?></p><?php endif; ?>
</section>

<section class="card">
    <h2>Request a Seat</h2>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <?php if ((int) $ride['rider_id'] === $user_id): ?>
        <div class="alert alert-error">You cannot request your own ride.</div>
    <?php elseif ($ride['status'] !== 'active' || (int) $ride['available_seats'] <= 0): ?>
        <div class="alert alert-error">This ride is not currently accepting requests.</div>
    <?php else: ?>
        <form method="post" class="grid">
            <div>
                <label for="requested_seats">Requested Seats</label>
                <input id="requested_seats" type="number" min="1" max="<?php echo (int) $ride['available_seats']; ?>" name="requested_seats" value="<?php echo htmlspecialchars($_POST['requested_seats'] ?? '1'); ?>" required>
            </div>
            <div>
                <label for="pickup_point">Pickup Point</label>
                <input id="pickup_point" name="pickup_point" value="<?php echo htmlspecialchars($_POST['pickup_point'] ?? ''); ?>" required>
            </div>
            <div style="grid-column: 1 / -1;">
                <label for="message">Message (optional)</label>
                <textarea id="message" name="message"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <div style="grid-column: 1 / -1;"><button type="submit">Submit Request</button></div>
        </form>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
