<?php
session_start();
require_once "../db.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = "Please enter email and password.";
    } else {
        $email_esc = mysqli_real_escape_string($conn, $email);

        $sql = "SELECT * FROM admins WHERE email = '$email_esc' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);

            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BizTix Admin Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="navbar">
        <div><strong>BizTix Admin</strong></div>
        <div>
            <a href="../index.php">Public Site</a>
        </div>
    </div>

    <div class="hero" style="max-width:500px;">
        <h1>Admin Login</h1>
        <p>Login to manage events and bookings.</p>

        <?php if ($error): ?>
            <p style="color:red; font-weight:bold; margin-top:12px;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" style="margin-top:20px;">
            <p>
                <label>Email</label><br>
                <input type="email" name="email" required style="width:100%; padding:10px; margin-top:6px;">
            </p>
            <br>
            <p>
                <label>Password</label><br>
                <input type="password" name="password" required style="width:100%; padding:10px; margin-top:6px;">
            </p>
            <br>
            <button type="submit" class="btn" style="border:none; cursor:pointer;">Login</button>
        </form>
    </div>

</body>
</html>