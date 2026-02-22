<?php
require_once "db.php";

$sql = "SELECT e.*, c.category_name
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE e.status = 'active'
        ORDER BY e.event_date ASC, e.event_time ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix | Events</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="navbar">
        <div><strong>BizTix</strong></div>
        <div>
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
            <a href="admin/login.php">Admin</a>
        </div>
    </div>

    <div class="section">
        <h2>Upcoming Events</h2>

        <div class="grid">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="card">
                    <img src="https://via.placeholder.com/400x200?text=<?php echo urlencode($row['title']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?></p>
                    <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
                    <p><strong>Date:</strong> <?php echo date("d M Y", strtotime($row['event_date'])); ?></p>
                    <p><strong>Time:</strong> <?php echo date("h:i A", strtotime($row['event_time'])); ?></p>
                    <p><strong>Price:</strong> RM <?php echo number_format($row['ticket_price'], 2); ?></p>
                    <a href="event_details.php?id=<?php echo $row['event_id']; ?>" class="btn">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="footer">
        © <?php echo date("Y"); ?> BizTix. Mini Event Ticketing Project.
    </div>

</body>
</html>