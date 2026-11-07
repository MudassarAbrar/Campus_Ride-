<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$user_id = (int) $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);

    if ($request_id <= 0) {
        $error = 'Invalid request selected.';
    } else {
        $stmt_cancel = mysqli_prepare($conn, 'UPDATE ride_requests SET status = "cancelled" WHERE id = ? AND passenger_id = ? AND status = "pending"');
        mysqli_stmt_bind_param($stmt_cancel, 'ii', $request_id, $user_id);
        mysqli_stmt_execute($stmt_cancel);

        if (mysqli_stmt_affected_rows($stmt_cancel) > 0) {
            $success = 'Request cancelled successfully.';
        } else {
            $error = 'Only pending requests can be cancelled.';
        }
        mysqli_stmt_close($stmt_cancel);
    }
}

$stmt = mysqli_prepare($conn, "SELECT rr.id, rr.requested_seats, rr.pickup_point, rr.status, rr.created_at,
                                     r.pickup_area, r.destination, r.departure_date, r.departure_time,
                                     u.name AS rider_name
                              FROM ride_requests rr
                              JOIN rides r ON r.id = rr.ride_id
                              JOIN users u ON u.id = r.rider_id
                              WHERE rr.passenger_id = ?
                              ORDER BY rr.created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$pageTitle = 'My Requests - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>My Seat Requests</h1>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <?php if (empty($requests)): ?>
        <p class="muted">You have not requested any rides yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <tr>
                    <th>Route</th>
                    <th>Rider</th>
                    <th>Date/Time</th>
                    <th>Seats</th>
                    <th>Pickup Point</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['pickup_area']); ?> → <?php echo htmlspecialchars($request['destination']); ?></td>
                        <td><?php echo htmlspecialchars($request['rider_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['departure_date']); ?> <?php echo htmlspecialchars($request['departure_time']); ?></td>
                        <td><?php echo (int) $request['requested_seats']; ?></td>
                        <td><?php echo htmlspecialchars($request['pickup_point']); ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars($request['status']); ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span></td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                                <form method="post">
                                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                    <button type="submit" class="btn-danger">Cancel</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
