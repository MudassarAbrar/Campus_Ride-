<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pageTitle)) {
    $pageTitle = 'CampusRide';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-row">
        <a class="brand" href="index.php">CampusRide</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <nav class="site-nav">
                <a href="index.php">Dashboard</a>
                <a href="search_rides.php">Search Rides</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="add_vehicle.php">Add Vehicle</a>
                <a href="my_rides.php">My Rides</a>
                <a href="my_requests.php">My Requests</a>
                <a href="logout.php">Logout</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="container">
