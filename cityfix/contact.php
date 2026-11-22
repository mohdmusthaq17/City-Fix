<?php

session_start();
include "db.php";

$message_status = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (
        $name === '' ||
        $email === '' ||
        $subject === '' ||
        $message === ''
    ) {
        $message_status = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_status = "Please enter a valid email address.";
    } else {

        $sql = "INSERT INTO contact_messages
                (name, email, subject, message)
                VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $name,
                $email,
                $subject,
                $message
            );

            if (mysqli_stmt_execute($stmt)) {
                $message_status = "Your message has been sent successfully!";
            } else {
                $message_status = "Unable to send your message. Please try again.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $message_status = "Something went wrong. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - CityFix</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #334155;
        }

        header {
            background: #123456;
            color: white;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            color: #38bdf8;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 18px;
            font-weight: bold;
        }

        nav a:hover {
            color: #38bdf8;
        }

        .container {
            max-width: 750px;
            margin: 40px auto;
            padding: 20px;
        }

        .contact-card {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        h1 {
            color: #123456;
            text-align: center;
            margin-bottom: 12px;
        }

        .intro {
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            background: #2563eb;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 7px;
            margin-top: 20px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 15px;
            text-align: center;
        }

        footer {
            background: #123456;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        @media (max-width: 600px) {
            header {
                flex-direction: column;
            }

            nav a {
                margin: 0 7px;
            }

            .contact-card {
                padding: 22px;
            }
        }

    </style>

</head>

<body>

<header>

    <h2>CityFix</h2>

    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About Us</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>

</header>

<div class="container">

    <div class="contact-card">

        <h1>Contact Us</h1>

        <p class="intro">
            Have a question or suggestion?
            Send us a message. We would love to hear from you.
        </p>

        <?php if ($message_status !== ""): ?>
            <div class="message">
                <?php echo htmlspecialchars($message_status); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <label>Your Name</label>
            <input
                type="text"
                name="name"
                maxlength="100"
                required
            >

            <label>Email Address</label>
            <input
                type="email"
                name="email"
                maxlength="150"
                required
            >

            <label>Subject</label>
            <input
                type="text"
                name="subject"
                maxlength="200"
                required
            >

            <label>Your Message</label>
            <textarea
                name="message"
                required
            ></textarea>

            <button type="submit">
                Send Message
            </button>

        </form>

    </div>

</div>

<footer>
    <p>&copy; <?php echo date("Y"); ?> CityFix. All Rights Reserved.</p>
</footer>

</body>
</html>