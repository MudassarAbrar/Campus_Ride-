<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$user_id = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT r.id, r.pickup_area, r.destination, r.departure_date, r.departure_time, r.available_seats, r.total_seats, r.fare, r.status,
                                     v.vehicle_type, v.vehicle_name,
                                     SUM(CASE WHEN rr.status = 'pending' THEN 1 ELSE 0 END) AS pending_requests,
                                     COUNT(rr.id) AS total_requests
                              FROM rides r
                              JOIN vehicles v ON v.id = r.vehicle_id
                              LEFT JOIN ride_requests rr ON rr.ride_id = r.id
                              WHERE r.rider_id = ?
                              GROUP BY r.id
                              ORDER BY r.departure_date DESC, r.departure_time DESC");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rides = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$pageTitle = 'My Rides - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>My Published Rides</h1>
    <?php if (empty($rides)): ?>
        <p class="muted">You have not published any rides yet. <a href="offer_ride.php">Publish a ride</a>.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <tr>
                    <th>Route</th>
                    <th>Date/Time</th>
                    <th>Vehicle</th>
                    <th>Seats</th>
                    <th>Fare</th>
                    <th>Status</th>
                    <th>Requests</th>
                </tr>
                <?php foreach ($rides as $ride): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ride['pickup_area']); ?> → <?php echo htmlspecialchars($ride['destination']); ?></td>
                        <td><?php echo htmlspecialchars($ride['departure_date']); ?> <?php echo htmlspecialchars($ride['departure_time']); ?></td>
                        <td><?php echo htmlspecialchars($ride['vehicle_type'] . ' - ' . $ride['vehicle_name']); ?></td>
                        <td><?php echo (int) $ride['available_seats']; ?>/<?php echo (int) $ride['total_seats']; ?></td>
                        <td><?php echo number_format((float) $ride['fare'], 2); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($ride['status'])); ?></td>
                        <td>
                            <a href="manage_requests.php?ride_id=<?php echo (int) $ride['id']; ?>">Pending: <?php echo (int) $ride['pending_requests']; ?> (Total: <?php echo (int) $ride['total_requests']; ?>)</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
