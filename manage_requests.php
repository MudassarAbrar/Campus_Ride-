<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$user_id = (int) $_SESSION['user_id'];
$error = '';
$success = '';
$ride_filter = (int) ($_GET['ride_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($request_id <= 0 || !in_array($action, ['accept', 'reject'], true)) {
        $error = 'Invalid request action.';
    } else {
        mysqli_begin_transaction($conn);

        try {
            $lock = mysqli_prepare($conn, 'SELECT rr.id, rr.requested_seats, rr.status, rr.ride_id, r.available_seats FROM ride_requests rr JOIN rides r ON r.id = rr.ride_id WHERE rr.id = ? AND r.rider_id = ? FOR UPDATE');
            mysqli_stmt_bind_param($lock, 'ii', $request_id, $user_id);
            mysqli_stmt_execute($lock);
            $locked = mysqli_stmt_get_result($lock);
            $request = mysqli_fetch_assoc($locked);
            mysqli_stmt_close($lock);

            if (!$request) {
                throw new Exception('Request not found.');
            }

            if ($request['status'] !== 'pending') {
                throw new Exception('Only pending requests can be updated.');
            }

            if ($action === 'reject') {
                $update = mysqli_prepare($conn, 'UPDATE ride_requests SET status = "rejected" WHERE id = ?');
                mysqli_stmt_bind_param($update, 'i', $request_id);
                mysqli_stmt_execute($update);
                mysqli_stmt_close($update);
                $success = 'Request rejected.';
            } else {
                $requested_seats = (int) $request['requested_seats'];
                $available_seats = (int) $request['available_seats'];

                if ($requested_seats > $available_seats) {
                    throw new Exception('Insufficient seats available.');
                }

                $accept = mysqli_prepare($conn, 'UPDATE ride_requests SET status = "accepted" WHERE id = ?');
                mysqli_stmt_bind_param($accept, 'i', $request_id);
                mysqli_stmt_execute($accept);
                mysqli_stmt_close($accept);

                $new_seats = $available_seats - $requested_seats;
                $ride_update = mysqli_prepare($conn, 'UPDATE rides SET available_seats = ? WHERE id = ?');
                mysqli_stmt_bind_param($ride_update, 'ii', $new_seats, $request['ride_id']);
                mysqli_stmt_execute($ride_update);
                mysqli_stmt_close($ride_update);

                $success = 'Request accepted and seats updated.';
            }

            mysqli_commit($conn);
        } catch (Throwable $th) {
            mysqli_rollback($conn);
            $error = $th->getMessage();
        }
    }
}

$query = "SELECT rr.id, rr.requested_seats, rr.pickup_point, rr.message, rr.status, rr.created_at,
                 p.name AS passenger_name, p.phone AS passenger_phone,
                 r.id AS ride_id, r.pickup_area, r.destination, r.departure_date, r.departure_time, r.available_seats
          FROM ride_requests rr
          JOIN rides r ON r.id = rr.ride_id
          JOIN users p ON p.id = rr.passenger_id
          WHERE r.rider_id = ?";

$types = 'i';
$params = [$user_id];
if ($ride_filter > 0) {
    $query .= ' AND r.id = ?';
    $types .= 'i';
    $params[] = $ride_filter;
}
$query .= ' ORDER BY rr.created_at DESC';

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$pageTitle = 'Manage Requests - CampusRide';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Incoming Ride Requests</h1>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <?php if (empty($requests)): ?>
        <p class="muted">No requests received yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <tr>
                    <th>Passenger</th>
                    <th>Route</th>
                    <th>Date/Time</th>
                    <th>Seats</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['passenger_name']); ?><br><span class="muted"><?php echo htmlspecialchars($request['passenger_phone']); ?></span></td>
                        <td><?php echo htmlspecialchars($request['pickup_area']); ?> → <?php echo htmlspecialchars($request['destination']); ?><br><span class="muted">Pickup: <?php echo htmlspecialchars($request['pickup_point']); ?></span></td>
                        <td><?php echo htmlspecialchars($request['departure_date']); ?> <?php echo htmlspecialchars($request['departure_time']); ?></td>
                        <td><?php echo (int) $request['requested_seats']; ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars($request['status']); ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span></td>
                        <td><?php echo nl2br(htmlspecialchars($request['message'] ?: '-')); ?></td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                                <form method="post" class="actions" style="min-width: 180px;">
                                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                    <button type="submit" name="action" value="accept">Accept</button>
                                    <button type="submit" name="action" value="reject" class="btn-danger">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
