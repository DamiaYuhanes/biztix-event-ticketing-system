<?php
require_once "db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid event ID.");
}

$event_id = (int) $_GET['id'];

/*
 |------------------------------------------------------------
 | Load event
 |------------------------------------------------------------
*/
$eventQuery = mysqli_query($conn, "SELECT * FROM events WHERE event_id = $event_id AND status = 'active' LIMIT 1");

if (!$eventQuery || mysqli_num_rows($eventQuery) === 0) {
    die("Event not found.");
}

$event = mysqli_fetch_assoc($eventQuery);

$message = "";
$error = "";

/*
 |------------------------------------------------------------
 | Helper: calculate remaining seats
 |------------------------------------------------------------
*/
function getRemainingSeats($conn, $event_id, $capacity)
{
    $bookedSql = "SELECT COALESCE(SUM(quantity), 0) AS total_booked
                  FROM bookings
                  WHERE event_id = $event_id
                  AND booking_status IN ('pending', 'confirmed')";

    $bookedResult = mysqli_query($conn, $bookedSql);

    $totalBooked = 0;
    if ($bookedResult) {
        $bookedRow = mysqli_fetch_assoc($bookedResult);
        $totalBooked = (int) ($bookedRow['total_booked'] ?? 0);
    }

    $remaining = (int)$capacity - $totalBooked;
    return max(0, $remaining);
}

/*
 |------------------------------------------------------------
 | Handle booking submit
 |------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($full_name === '' || $email === '' || $quantity < 1) {
        $error = "Please fill in all required fields correctly.";
    }

    // Basic email validation
    if ($error === "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    // Capacity check
    if ($error === "") {
        $remainingSeats = getRemainingSeats($conn, $event_id, (int)$event['capacity']);

        if ($remainingSeats <= 0) {
            $error = "Sorry, this event is fully booked.";
        } elseif ($quantity > $remainingSeats) {
            $error = "Not enough seats available. Remaining seats: " . $remainingSeats;
        }
    }

    if ($error === "") {
        // Escape values for SQL
        $full_name_esc = mysqli_real_escape_string($conn, $full_name);
        $email_esc = mysqli_real_escape_string($conn, $email);
        $phone_esc = mysqli_real_escape_string($conn, $phone);

        /*
         |--------------------------------------------------------
         | Find existing customer by email (avoid duplicates)
         |--------------------------------------------------------
        */
        $customer_id = 0;

        $emailCheckSql = "SELECT customer_id FROM customers WHERE email = '$email_esc' LIMIT 1";
        $emailCheckResult = mysqli_query($conn, $emailCheckSql);

        if ($emailCheckResult && mysqli_num_rows($emailCheckResult) === 1) {
            $existingCustomer = mysqli_fetch_assoc($emailCheckResult);
            $customer_id = (int) $existingCustomer['customer_id'];

            // Optional: keep latest name/phone
            mysqli_query(
                $conn,
                "UPDATE customers
                 SET full_name = '$full_name_esc', phone = '$phone_esc'
                 WHERE customer_id = $customer_id"
            );
        } else {
            $customerSql = "INSERT INTO customers (full_name, email, phone)
                            VALUES ('$full_name_esc', '$email_esc', '$phone_esc')";

            if (!mysqli_query($conn, $customerSql)) {
                $error = "Failed to save customer: " . mysqli_error($conn);
            } else {
                $customer_id = mysqli_insert_id($conn);
            }
        }

        /*
         |--------------------------------------------------------
         | Create booking
         |--------------------------------------------------------
        */
        if ($error === "") {
            $ticket_price = (float) $event['ticket_price'];
            $total_amount = $quantity * $ticket_price;

            // Safer-ish unique booking code generation
            do {
                $booking_code = "BZT" . date("Y") . rand(100000, 999999);

                $codeCheck = mysqli_query(
                    $conn,
                    "SELECT booking_id FROM bookings WHERE booking_code = '$booking_code' LIMIT 1"
                );
            } while ($codeCheck && mysqli_num_rows($codeCheck) > 0);

            $bookingSql = "INSERT INTO bookings
                           (customer_id, event_id, quantity, total_amount, booking_status, payment_status, booking_code)
                           VALUES
                           ($customer_id, $event_id, $quantity, $total_amount, 'pending', 'unpaid', '$booking_code')";

            if (mysqli_query($conn, $bookingSql)) {
                $message = "Booking submitted successfully! Your booking code is: " . $booking_code;

                // Optional: clear form feel by resetting variables
                $full_name = "";
                $email = "";
                $phone = "";
                $quantity = 1;
            } else {
                $error = "Failed to save booking: " . mysqli_error($conn);
            }
        }
    }
}

/*
 |------------------------------------------------------------
 | Always show current remaining seats (updated after booking)
 |------------------------------------------------------------
*/
$remainingPreview = getRemainingSeats($conn, $event_id, (int)$event['capacity']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix | Book Event</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="navbar">
        <div><strong>BizTix</strong></div>
        <div>
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
        </div>
    </div>

    <div class="hero">
        <h1>Book Event</h1>

        <p><strong><?php echo htmlspecialchars($event['title']); ?></strong></p>
        <p>Venue: <?php echo htmlspecialchars($event['venue']); ?></p>
        <p>Date: <?php echo date("d M Y", strtotime($event['event_date'])); ?></p>
        <p>Time: <?php echo date("h:i A", strtotime($event['event_time'])); ?></p>
        <p>Price per ticket: RM <?php echo number_format((float)$event['ticket_price'], 2); ?></p>
        <p>Capacity: <?php echo (int)$event['capacity']; ?> seats</p>
        <p><strong>Remaining seats: <?php echo (int)$remainingPreview; ?></strong></p>

        <?php if ($message): ?>
            <p style="color: green; font-weight: bold; margin-top: 15px;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p style="color: red; font-weight: bold; margin-top: 15px;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <?php if ($remainingPreview > 0): ?>
            <form method="POST" style="margin-top:20px;">
                <p>
                    <label>Full Name *</label><br>
                    <input
                        type="text"
                        name="full_name"
                        required
                        value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Email *</label><br>
                    <input
                        type="email"
                        name="email"
                        required
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Phone</label><br>
                    <input
                        type="text"
                        name="phone"
                        value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <p>
                    <label>Ticket Quantity *</label><br>
                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        max="<?php echo (int)$remainingPreview; ?>"
                        value="<?php echo htmlspecialchars((string)($quantity ?? 1)); ?>"
                        required
                        style="width:100%; padding:10px; margin-top:6px;"
                    >
                </p>
                <br>

                <button type="submit" class="btn" style="border:none; cursor:pointer;">
                    Submit Booking
                </button>
            </form>
        <?php else: ?>
            <p style="margin-top: 16px; color: #b91c1c; font-weight: bold;">
                This event is fully booked.
            </p>
        <?php endif; ?>

        <br>
        <a href="event_details.php?id=<?php echo (int)$event['event_id']; ?>" class="btn" style="background:#6b7280;">
            Back to Event Details
        </a>
    </div>

    <div class="footer">
        © <?php echo date("Y"); ?> BizTix. Mini Event Ticketing Project.
    </div>

</body>
</html>