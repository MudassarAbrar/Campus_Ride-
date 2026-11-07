<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pickup_area = trim($_GET['pickup_area'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$departure_date = trim($_GET['departure_date'] ?? '');

$where = " WHERE r.status = 'active' AND r.available_seats > 0 AND r.departure_date >= CURDATE() ";
$types = '';
$params = [];

if ($pickup_area !== '') {
    $where .= ' AND r.pickup_area LIKE ? ';
    $types .= 's';
    $params[] = '%' . $pickup_area . '%';
}

if ($destination !== '') {
    $where .= ' AND r.destination LIKE ? ';
    $types .= 's';
    $params[] = '%' . $destination . '%';
}

if ($departure_date !== '') {
    $where .= ' AND r.departure_date = ? ';
    $types .= 's';
    $params[] = $departure_date;
}

$query = "SELECT r.id, r.pickup_area, r.destination, r.departure_date, r.departure_time, r.available_seats, r.fare,
                 u.name AS rider_name, v.vehicle_type, v.vehicle_name
          FROM rides r
          JOIN users u ON u.id = r.rider_id
          JOIN vehicles v ON v.id = r.vehicle_id
          {$where}
          ORDER BY r.departure_date ASC, r.departure_time ASC";

$stmt = mysqli_prepare($conn, $query);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rides = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$pageTitle = 'Search Rides - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Search Available Rides</h1>
    <form method="get" class="grid">
        <div>
            <label for="pickup_area">Pickup Area</label>
            <input id="pickup_area" name="pickup_area" value="<?php echo htmlspecialchars($pickup_area); ?>">
        </div>
        <div>
            <label for="destination">Destination</label>
            <input id="destination" name="destination" value="<?php echo htmlspecialchars($destination); ?>">
        </div>
        <div>
            <label for="departure_date">Departure Date</label>
            <input id="departure_date" type="date" name="departure_date" value="<?php echo htmlspecialchars($departure_date); ?>">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit">Search</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Results</h2>
    <?php if (empty($rides)): ?>
        <p class="muted">No rides found for the selected criteria.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($rides as $ride): ?>
                <article class="card">
                    <h3><?php echo htmlspecialchars($ride['pickup_area']); ?> → <?php echo htmlspecialchars($ride['destination']); ?></h3>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($ride['departure_date']); ?> <?php echo htmlspecialchars($ride['departure_time']); ?></p>
                    <p><strong>Rider:</strong> <?php echo htmlspecialchars($ride['rider_name']); ?></p>
                    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($ride['vehicle_type'] . ' - ' . $ride['vehicle_name']); ?></p>
                    <p><strong>Seats:</strong> <?php echo (int) $ride['available_seats']; ?> | <strong>Fare:</strong> <?php echo number_format((float) $ride['fare'], 2); ?></p>
                    <a class="btn" href="ride_details.php?id=<?php echo (int) $ride['id']; ?>">Request Seat</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
