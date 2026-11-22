<?php

session_start();
include "db.php";

// Check admin login
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: login.php");
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";

// Delete contact message
if (isset($_POST['delete_message'])) {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die("Invalid request.");
    }

    $message_id = (int)($_POST['message_id'] ?? 0);

    if ($message_id > 0) {
        $sql = "DELETE FROM contact_messages WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $message_id);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Message deleted successfully.";
            } else {
                $message = "Unable to delete message.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Get all contact messages
$sql = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Unable to load contact messages.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Messages - CityFix Admin</title>

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
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        h1 {
            color: #123456;
            margin-bottom: 20px;
        }

        .message-card {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .message-card h3 {
            color: #123456;
            margin-bottom: 12px;
        }

        .info {
            color: #64748b;
            margin-bottom: 10px;
            overflow-wrap: anywhere;
        }

        .message-text {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .delete-btn:hover {
            background: #b91c1c;
        }

        .notice {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 7px;
        }

        .empty {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }

        @media (max-width: 600px) {
            header {
                flex-direction: column;
            }

            nav a {
                margin: 0 6px;
            }
        }

    </style>

</head>

<body>

<header>

    <h2>CityFix Admin</h2>

    <nav>
        <a href="admin.php">Dashboard</a>
        <a href="admin_messages.php">Messages</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <h1>Contact Messages</h1>

    <?php if ($message !== ""): ?>
        <div class="notice">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($result) > 0): ?>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

            <div class="message-card">

                <h3>
                    <?php echo htmlspecialchars($row['subject']); ?>
                </h3>

                <p class="info">
                    <strong>From:</strong>
                    <?php echo htmlspecialchars($row['name']); ?>
                </p>

                <p class="info">
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($row['email']); ?>
                </p>

                <p class="info">
                    <strong>Date:</strong>
                    <?php echo htmlspecialchars($row['created_at']); ?>
                </p>

                <div class="message-text">
                    <?php echo htmlspecialchars($row['message']); ?>
                </div>

                <form method="POST"
                      onsubmit="return confirm('Are you sure you want to delete this message?');">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <input type="hidden"
                           name="message_id"
                           value="<?php echo (int)$row['id']; ?>">

                    <button type="submit"
                            name="delete_message"
                            class="delete-btn">
                        Delete Message
                    </button>

                </form>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No Contact Messages Found</h3>
            <p>Messages submitted by visitors will appear here.</p>
        </div>

    <?php endif; ?>

</main>

</body>
</html>