<?php
require_once "auth.php";
require_once "../db.php";

$message = "";
$error = "";

/*
 |------------------------------------------------------------
 | Handle status update
 |------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_booking'])) {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $booking_status = trim($_POST['booking_status'] ?? '');
    $payment_status = trim($_POST['payment_status'] ?? '');

    $allowedBooking = ['pending', 'confirmed', 'cancelled'];
    $allowedPayment = ['unpaid', 'paid'];

    if ($booking_id <= 0) {
        $error = "Invalid booking ID.";
    } elseif (!in_array($booking_status, $allowedBooking, true)) {
        $error = "Invalid booking status.";
    } elseif (!in_array($payment_status, $allowedPayment, true)) {
        $error = "Invalid payment status.";
    } else {
        $booking_status_esc = mysqli_real_escape_string($conn, $booking_status);
        $payment_status_esc = mysqli_real_escape_string($conn, $payment_status);

        $updateSql = "UPDATE bookings
                      SET booking_status = '$booking_status_esc',
                          payment_status = '$payment_status_esc'
                      WHERE booking_id = $booking_id
                      LIMIT 1";

        if (mysqli_query($conn, $updateSql)) {
            $message = "Booking updated successfully.";
        } else {
            $error = "Failed to update booking: " . mysqli_error($conn);
        }
    }
}

/*
 |------------------------------------------------------------
 | Search filter
 |------------------------------------------------------------
*/
$search = trim($_GET['search'] ?? '');
$searchSql = "";

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $searchSql = " AND (
        cu.full_name LIKE '%$safeSearch%' OR
        cu.email LIKE '%$safeSearch%' OR
        e.title LIKE '%$safeSearch%' OR
        b.booking_code LIKE '%$safeSearch%'
    )";
}

/*
 |------------------------------------------------------------
 | Query bookings
 |------------------------------------------------------------
*/
$sql = "SELECT b.*, cu.full_name, cu.email, cu.phone, e.title AS event_title
        FROM bookings b
        JOIN customers cu ON b.customer_id = cu.customer_id
        JOIN events e ON b.event_id = e.event_id
        WHERE 1=1 $searchSql
        ORDER BY b.created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix Admin Bookings</title>
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
        <h2>Bookings</h2>

        <?php if ($message): ?>
            <p style="color:green; font-weight:bold; margin:10px 0;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p style="color:red; font-weight:bold; margin:10px 0;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form method="GET" style="margin: 12px 0 18px 0;">
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search by name, email, event, booking code"
                style="width:70%; padding:10px;"
            >
            <button type="submit" class="btn" style="border:none; cursor:pointer;">Search</button>
            <a href="bookings.php" class="btn" style="background:#6b7280;">Reset</a>
        </form>

        <div class="card" style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="padding:10px; text-align:left;">Booking Code</th>
                        <th style="padding:10px; text-align:left;">Customer</th>
                        <th style="padding:10px; text-align:left;">Email</th>
                        <th style="padding:10px; text-align:left;">Event</th>
                        <th style="padding:10px; text-align:left;">Qty</th>
                        <th style="padding:10px; text-align:left;">Total</th>
                        <th style="padding:10px; text-align:left;">Booking Status</th>
                        <th style="padding:10px; text-align:left;">Payment</th>
                        <th style="padding:10px; text-align:left;">Created</th>
                        <th style="padding:10px; text-align:left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr style="border-top:1px solid #eee; vertical-align:top;">
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['booking_code']); ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['event_title']); ?></td>
                                <td style="padding:10px;"><?php echo (int)$row['quantity']; ?></td>
                                <td style="padding:10px;">RM <?php echo number_format((float)$row['total_amount'], 2); ?></td>

                                <td style="padding:10px;">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$row['booking_id']; ?>">
                                        <select name="booking_status" style="padding:6px; width:130px;">
                                            <option value="pending" <?php echo $row['booking_status'] === 'pending' ? 'selected' : ''; ?>>pending</option>
                                            <option value="confirmed" <?php echo $row['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>confirmed</option>
                                            <option value="cancelled" <?php echo $row['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>cancelled</option>
                                        </select>
                                </td>

                                <td style="padding:10px;">
                                        <select name="payment_status" style="padding:6px; width:110px;">
                                            <option value="unpaid" <?php echo $row['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>unpaid</option>
                                            <option value="paid" <?php echo $row['payment_status'] === 'paid' ? 'selected' : ''; ?>>paid</option>
                                        </select>
                                </td>

                                <td style="padding:10px;"><?php echo htmlspecialchars($row['created_at']); ?></td>

                                <td style="padding:10px;">
                                        <button type="submit" name="update_booking" class="btn" style="border:none; cursor:pointer; padding:8px 12px;">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="padding:12px;">No bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>