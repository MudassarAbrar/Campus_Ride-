<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Dashboard - CampusRide';
include __DIR__ . '/includes/header.php';

$userName = $_SESSION['name'] ?? 'Student';

$rides = [];
$sql = "SELECT r.id, r.pickup_area, r.destination, r.departure_date, r.departure_time, r.available_seats, r.fare,
               u.name AS rider_name, v.vehicle_type, v.vehicle_name
        FROM rides r
        JOIN users u ON u.id = r.rider_id
        JOIN vehicles v ON v.id = r.vehicle_id
        WHERE r.status = 'active' AND r.available_seats > 0 AND r.departure_date >= CURDATE()
        ORDER BY r.departure_date ASC, r.departure_time ASC
        LIMIT 6";

$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rides[] = $row;
    }
}
?>
<section class="card">
    <h1>Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
    <p class="muted">Search available rides or publish your own ride offer.</p>
    <div class="actions">
        <a class="btn" href="offer_ride.php">Offer a Ride</a>
        <a class="btn btn-secondary" href="search_rides.php">Search Rides</a>
    </div>
</section>

<section class="card">
    <h2>Quick Search</h2>
    <form method="get" action="search_rides.php" class="grid">
        <div>
            <label for="pickup_area">Pickup Area</label>
            <input id="pickup_area" name="pickup_area" placeholder="e.g. Gulshan">
        </div>
        <div>
            <label for="destination">Destination</label>
            <input id="destination" name="destination" placeholder="e.g. University Campus">
        </div>
        <div>
            <label for="departure_date">Date</label>
            <input id="departure_date" type="date" name="departure_date">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit">Find Rides</button>
        </div>
    </form>
</section>

<section class="card">
    <h2>Recent Available Rides</h2>
    <?php if (empty($rides)): ?>
        <p class="muted">No rides available right now.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($rides as $ride): ?>
                <article class="card">
                    <h3><?php echo htmlspecialchars($ride['pickup_area']); ?> → <?php echo htmlspecialchars($ride['destination']); ?></h3>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($ride['departure_date']); ?> <?php echo htmlspecialchars($ride['departure_time']); ?></p>
                    <p><strong>Rider:</strong> <?php echo htmlspecialchars($ride['rider_name']); ?></p>
                    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($ride['vehicle_type'] . ' - ' . $ride['vehicle_name']); ?></p>
                    <p><strong>Seats:</strong> <?php echo (int) $ride['available_seats']; ?> | <strong>Fare:</strong> <?php echo number_format((float) $ride['fare'], 2); ?></p>
                    <a class="btn" href="ride_details.php?id=<?php echo (int) $ride['id']; ?>">View / Request</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
