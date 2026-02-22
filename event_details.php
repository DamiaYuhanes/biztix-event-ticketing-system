<?php
require_once "db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid event ID.");
}

$event_id = (int) $_GET['id'];

$sql = "SELECT e.*, c.category_name
        FROM events e
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE e.event_id = $event_id
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Event not found.");
}

$event = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix | <?php echo htmlspecialchars($event['title']); ?></title>
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

    <div class="hero">
        <h1><?php echo htmlspecialchars($event['title']); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>

        <p><strong>Category:</strong> <?php echo htmlspecialchars($event['category_name'] ?? 'General'); ?></p>
        <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
        <p><strong>Date:</strong> <?php echo date("d M Y", strtotime($event['event_date'])); ?></p>
        <p><strong>Time:</strong> <?php echo date("h:i A", strtotime($event['event_time'])); ?></p>
        <p><strong>Ticket Price:</strong> RM <?php echo number_format($event['ticket_price'], 2); ?></p>
        <p><strong>Capacity:</strong> <?php echo (int)$event['capacity']; ?> seats</p>

        <br>
        <a href="book_event.php?id=<?php echo $event['event_id']; ?>" class="btn">Book Now</a>
        <a href="events.php" class="btn" style="background:#6b7280;">Back to Events</a>
    </div>

    <div class="footer">
        © <?php echo date("Y"); ?> BizTix. Mini Event Ticketing Project.
    </div>

</body>
</html>