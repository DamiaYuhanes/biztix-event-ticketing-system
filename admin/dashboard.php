<?php
require_once "auth.php";
require_once "../db.php";

$totalEvents = 0;
$totalBookings = 0;
$totalCustomers = 0;

$r1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM events");
if ($r1) {
    $totalEvents = mysqli_fetch_assoc($r1)['total'];
}

$r2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings");
if ($r2) {
    $totalBookings = mysqli_fetch_assoc($r2)['total'];
}

$r3 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers");
if ($r3) {
    $totalCustomers = mysqli_fetch_assoc($r3)['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF 8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="navbar">
        <div><strong>BizTix Admin</strong></div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="bookings.php">Bookings</a>
            <a href="events_manage.php">Manage Events</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="section">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h2>
        <p style="margin-bottom:16px; color:#555;">Here is your BizTix admin overview.</p>

        <div class="grid">
            <div class="card">
                <h3>Total Events</h3>
                <p style="font-size:28px; font-weight:bold; color:#2563eb;">
                    <?php echo (int)$totalEvents; ?>
                </p>
            </div>

            <div class="card">
                <h3>Total Bookings</h3>
                <p style="font-size:28px; font-weight:bold; color:#2563eb;">
                    <?php echo (int)$totalBookings; ?>
                </p>
            </div>

            <div class="card">
                <h3>Total Customers</h3>
                <p style="font-size:28px; font-weight:bold; color:#2563eb;">
                    <?php echo (int)$totalCustomers; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="footer">
        © <?php echo date("Y"); ?> BizTix Admin Dashboard
    </div>

</body>
</html>