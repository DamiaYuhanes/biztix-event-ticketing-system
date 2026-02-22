<?php
require_once "db.php";

/*
 |------------------------------------------------------------
 | Load featured events (latest active upcoming events)
 |------------------------------------------------------------
*/
$featuredSql = "SELECT e.*, c.category_name
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.category_id
                WHERE e.status = 'active'
                ORDER BY e.event_date ASC, e.event_time ASC
                LIMIT 3";

$featuredResult = mysqli_query($conn, $featuredSql);

if (!$featuredResult) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix | Event Ticketing</title>
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
        <h1>Discover and Book Amazing Events</h1>
        <p>
            BizTix is a mini event ticketing platform for seminars, workshops, and business events.
            Browse upcoming events and reserve your tickets online.
        </p>
        <a href="events.php" class="btn">Browse Events</a>
    </div>

    <div class="section">
        <h2>Featured Events</h2>
        <div class="grid">

            <?php if (mysqli_num_rows($featuredResult) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($featuredResult)): ?>
                    <div class="card">
                        <img
                            src="https://via.placeholder.com/400x200?text=<?php echo urlencode($row['title']); ?>"
                            alt="<?php echo htmlspecialchars($row['title']); ?>"
                        >
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?></p>
                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
                        <p><strong>Date:</strong> <?php echo date("d M Y", strtotime($row['event_date'])); ?></p>

                        <a href="event_details.php?id=<?php echo (int)$row['event_id']; ?>" class="btn">
                            View Details
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card">
                    <h3>No featured events yet</h3>
                    <p>Please add active events from the admin panel.</p>
                    <a href="events.php" class="btn">Browse Events</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="footer">
        © <?php echo date("Y"); ?> BizTix. Mini Event Ticketing Project.
    </div>

</body>
</html>