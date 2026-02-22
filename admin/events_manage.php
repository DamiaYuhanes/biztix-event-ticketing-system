<?php
require_once "auth.php";
require_once "../db.php";

$message = "";
$error = "";

/*
 |------------------------------------------------------------
 | Handle Delete Event
 |------------------------------------------------------------
*/
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];

    // Optional safety check: prevent deleting events with bookings
    $bookingCheck = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings WHERE event_id = $delete_id");
    $bookingCount = 0;
    if ($bookingCheck) {
        $bookingCount = (int) mysqli_fetch_assoc($bookingCheck)['total'];
    }

    if ($bookingCount > 0) {
        $error = "Cannot delete event because it already has bookings. You can change status to closed/cancelled instead.";
    } else {
        if (mysqli_query($conn, "DELETE FROM events WHERE event_id = $delete_id LIMIT 1")) {
            $message = "Event deleted successfully.";
        } else {
            $error = "Failed to delete event: " . mysqli_error($conn);
        }
    }
}

/*
 |------------------------------------------------------------
 | Prepare Edit Mode
 |------------------------------------------------------------
*/
$editMode = false;
$editEvent = [
    'event_id' => '',
    'category_id' => '',
    'title' => '',
    'description' => '',
    'venue' => '',
    'event_date' => '',
    'event_time' => '',
    'ticket_price' => '0.00',
    'capacity' => '100',
    'status' => 'active'
];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];

    $editResult = mysqli_query($conn, "SELECT * FROM events WHERE event_id = $edit_id LIMIT 1");
    if ($editResult && mysqli_num_rows($editResult) === 1) {
        $editMode = true;
        $editEvent = mysqli_fetch_assoc($editResult);
    } else {
        $error = "Event not found for editing.";
    }
}

/*
 |------------------------------------------------------------
 | Handle Add / Update Event
 |------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_event'])) {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $event_time = trim($_POST['event_time'] ?? '');
    $ticket_price = (float) ($_POST['ticket_price'] ?? 0);
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');

    $allowedStatuses = ['active', 'closed', 'cancelled'];

    if ($title === '' || $venue === '' || $event_date === '' || $event_time === '') {
        $error = "Please fill all required event fields.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid event status.";
    } elseif ($capacity < 1) {
        $error = "Capacity must be at least 1.";
    } elseif ($ticket_price < 0) {
        $error = "Ticket price cannot be negative.";
    } else {
        $title_esc = mysqli_real_escape_string($conn, $title);
        $description_esc = mysqli_real_escape_string($conn, $description);
        $venue_esc = mysqli_real_escape_string($conn, $venue);
        $event_date_esc = mysqli_real_escape_string($conn, $event_date);
        $event_time_esc = mysqli_real_escape_string($conn, $event_time);
        $status_esc = mysqli_real_escape_string($conn, $status);

        // category_id can be NULL
        $category_sql_value = ($category_id > 0) ? $category_id : "NULL";

        if ($event_id > 0) {
            // Update existing event
            $sqlUpdate = "UPDATE events SET
                            category_id = $category_sql_value,
                            title = '$title_esc',
                            description = '$description_esc',
                            venue = '$venue_esc',
                            event_date = '$event_date_esc',
                            event_time = '$event_time_esc',
                            ticket_price = $ticket_price,
                            capacity = $capacity,
                            status = '$status_esc'
                          WHERE event_id = $event_id
                          LIMIT 1";

            if (mysqli_query($conn, $sqlUpdate)) {
                $message = "Event updated successfully.";
                $editMode = false;

                // Reset form after update
                $editEvent = [
                    'event_id' => '',
                    'category_id' => '',
                    'title' => '',
                    'description' => '',
                    'venue' => '',
                    'event_date' => '',
                    'event_time' => '',
                    'ticket_price' => '0.00',
                    'capacity' => '100',
                    'status' => 'active'
                ];
            } else {
                $error = "Failed to update event: " . mysqli_error($conn);
            }
        } else {
            // Insert new event
            $sqlInsert = "INSERT INTO events
                (category_id, title, description, venue, event_date, event_time, ticket_price, capacity, status)
                VALUES
                ($category_sql_value, '$title_esc', '$description_esc', '$venue_esc', '$event_date_esc', '$event_time_esc', $ticket_price, $capacity, '$status_esc')";

            if (mysqli_query($conn, $sqlInsert)) {
                $message = "Event added successfully.";

                // Reset form after insert
                $editEvent = [
                    'event_id' => '',
                    'category_id' => '',
                    'title' => '',
                    'description' => '',
                    'venue' => '',
                    'event_date' => '',
                    'event_time' => '',
                    'ticket_price' => '0.00',
                    'capacity' => '100',
                    'status' => 'active'
                ];
            } else {
                $error = "Failed to add event: " . mysqli_error($conn);
            }
        }
    }

    // Keep edit mode values if validation fails
    if ($error !== "") {
        $editMode = ($event_id > 0);
        $editEvent = [
            'event_id' => $event_id,
            'category_id' => $category_id,
            'title' => $title,
            'description' => $description,
            'venue' => $venue,
            'event_date' => $event_date,
            'event_time' => $event_time,
            'ticket_price' => $ticket_price,
            'capacity' => $capacity,
            'status' => $status
        ];
    }
}

/*
 |------------------------------------------------------------
 | Load categories + events list
 |------------------------------------------------------------
*/
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

$events = mysqli_query(
    $conn,
    "SELECT e.*, c.category_name
     FROM events e
     LEFT JOIN categories c ON e.category_id = c.category_id
     ORDER BY e.event_date ASC, e.event_time ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix Manage Events</title>
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
        <h2><?php echo $editMode ? 'Edit Event' : 'Add New Event'; ?></h2>

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

        <div class="card" style="margin-bottom:20px;">
            <form method="POST">
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars((string)$editEvent['event_id']); ?>">

                <p>
                    <label>Title *</label><br>
                    <input
                        type="text"
                        name="title"
                        required
                        value="<?php echo htmlspecialchars($editEvent['title']); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Category</label><br>
                    <select name="category_id" style="width:100%; padding:10px; margin-top:6px;">
                        <option value="0">Select category</option>
                        <?php if ($categories): ?>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option
                                    value="<?php echo (int)$cat['category_id']; ?>"
                                    <?php echo ((int)$editEvent['category_id'] === (int)$cat['category_id']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </p>
                <br>

                <p>
                    <label>Description</label><br>
                    <textarea name="description" rows="4" style="width:100%; padding:10px; margin-top:6px;"><?php echo htmlspecialchars($editEvent['description']); ?></textarea>
                </p>
                <br>

                <p>
                    <label>Venue *</label><br>
                    <input
                        type="text"
                        name="venue"
                        required
                        value="<?php echo htmlspecialchars($editEvent['venue']); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Event Date *</label><br>
                    <input
                        type="date"
                        name="event_date"
                        required
                        value="<?php echo htmlspecialchars($editEvent['event_date']); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Event Time *</label><br>
                    <input
                        type="time"
                        name="event_time"
                        required
                        value="<?php echo htmlspecialchars(substr((string)$editEvent['event_time'], 0, 5)); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Ticket Price (RM)</label><br>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="ticket_price"
                        value="<?php echo htmlspecialchars((string)$editEvent['ticket_price']); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Capacity *</label><br>
                    <input
                        type="number"
                        min="1"
                        name="capacity"
                        required
                        value="<?php echo htmlspecialchars((string)$editEvent['capacity']); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Status *</label><br>
                    <select name="status" style="width:100%; padding:10px; margin-top:6px;">
                        <option value="active" <?php echo $editEvent['status'] === 'active' ? 'selected' : ''; ?>>active</option>
                        <option value="closed" <?php echo $editEvent['status'] === 'closed' ? 'selected' : ''; ?>>closed</option>
                        <option value="cancelled" <?php echo $editEvent['status'] === 'cancelled' ? 'selected' : ''; ?>>cancelled</option>
                    </select>
                </p>
                <br>

                <button type="submit" name="save_event" class="btn" style="border:none; cursor:pointer;">
                    <?php echo $editMode ? 'Update Event' : 'Add Event'; ?>
                </button>

                <?php if ($editMode): ?>
                    <a href="events_manage.php" class="btn" style="background:#6b7280;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>All Events</h2>
        <div class="card" style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="padding:10px; text-align:left;">Title</th>
                        <th style="padding:10px; text-align:left;">Category</th>
                        <th style="padding:10px; text-align:left;">Venue</th>
                        <th style="padding:10px; text-align:left;">Date</th>
                        <th style="padding:10px; text-align:left;">Time</th>
                        <th style="padding:10px; text-align:left;">Price</th>
                        <th style="padding:10px; text-align:left;">Capacity</th>
                        <th style="padding:10px; text-align:left;">Status</th>
                        <th style="padding:10px; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events && mysqli_num_rows($events) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($events)): ?>
                            <tr style="border-top:1px solid #eee;">
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['venue']); ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['event_date']); ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars(substr((string)$row['event_time'], 0, 5)); ?></td>
                                <td style="padding:10px;">RM <?php echo number_format((float)$row['ticket_price'], 2); ?></td>
                                <td style="padding:10px;"><?php echo (int)$row['capacity']; ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($row['status']); ?></td>
                                <td style="padding:10px; white-space:nowrap;">
                                    <a href="events_manage.php?edit=<?php echo (int)$row['event_id']; ?>" class="btn" style="padding:8px 12px;">
                                        Edit
                                    </a>
                                    <a href="events_manage.php?delete=<?php echo (int)$row['event_id']; ?>"
                                       class="btn"
                                       style="padding:8px 12px; background:#dc2626;"
                                       onclick="return confirm('Delete this event? This cannot be undone if no bookings exist.');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="padding:12px;">No events found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>