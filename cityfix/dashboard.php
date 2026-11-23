<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$sql = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(status = 'Pending'), 0) AS pending,
    COALESCE(SUM(status = 'In Progress'), 0) AS progress,
    COALESCE(SUM(status = 'Resolved'), 0) AS resolved
    FROM complaints
    WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database query failed.");
}

mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CityFix - User Dashboard</title>

    <link rel="stylesheet" href="./css/style.css?v=3">

    <style>

        body {
            background: #f1f5f9;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        header {
            background: #123456;
            color: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h2 {
            margin: 0;
            font-size: 26px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        nav a:hover {
            color: #38bdf8;
        }

        .dashboard {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            color: #123456;
            font-size: 32px;
        }

        .welcome p {
            color: #64748b;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
            border-left: 5px solid #2563eb;
        }

        .stat-card h3 {
            color: #64748b;
            font-size: 16px;
        }

        .stat-card h2 {
            color: #123456;
            font-size: 32px;
            margin-bottom: 0;
        }

        .pending {
            border-left-color: #f59e0b;
        }

        .progress {
            border-left-color: #3b82f6;
        }

        .resolved {
            border-left-color: #16a34a;
        }

        .actions {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-top: 30px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .actions h2 {
            color: #123456;
        }

        .action-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 13px 22px;
            margin: 10px 10px 0 0;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .action-btn:hover {
            background: #1d4ed8;
        }

        .secondary-btn {
            background: #16a34a;
        }

        .secondary-btn:hover {
            background: #15803d;
        }

        @media (max-width: 800px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            header {
                padding: 15px;
                flex-direction: column;
                gap: 12px;
            }

            nav a {
                margin: 0 8px;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .welcome h1 {
                font-size: 25px;
            }
        }

    </style>

</head>

<body>

<header>

    <h2>CityFix</h2>

    <nav>
        <a href="index.php">Home</a>
        <a href="profile.php">My Profile</a>
        <a href="logout.php">Logout</a>
		<a href="about.php">About Us</a>
		<a href="contact.php">Contact Us</a>
    </nav>

</header>

<main class="dashboard">

    <section class="welcome">

        <h1>Welcome to CityFix!</h1>

        <h2>
            Hello,
            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </h2>

        <p>
            Track your complaints and help make your city better.
        </p>

    </section>

    <?php
    $pstmt = mysqli_prepare($conn, "SELECT points FROM users WHERE id = ?");
    $puid = (int)$_SESSION['user_id'];
    mysqli_stmt_bind_param($pstmt, "i", $puid);
    mysqli_stmt_execute($pstmt);
    $prow = mysqli_fetch_assoc(mysqli_stmt_get_result($pstmt));
    mysqli_stmt_close($pstmt);
    $my_points = (int)($prow['points'] ?? 0);
    ?>
    <p style="text-align:center; font-size:1.1rem; margin:10px 0;">
        <strong>Your points: <?php echo $my_points; ?></strong>
        &nbsp;|&nbsp; <a href="leaderboard.php">Leaderboard</a>
    </p>

    <section class="stats-container">

        <div class="stat-card">
            <h3>Total Complaints</h3>
            <h2><?php echo (int)$stats['total']; ?></h2>
        </div>

        <div class="stat-card pending">
            <h3>Pending</h3>
            <h2><?php echo (int)$stats['pending']; ?></h2>
        </div>

        <div class="stat-card progress">
            <h3>In Progress</h3>
            <h2><?php echo (int)$stats['progress']; ?></h2>
        </div>

        <div class="stat-card resolved">
            <h3>Resolved</h3>
            <h2><?php echo (int)$stats['resolved']; ?></h2>
        </div>

    </section>

    <section class="actions">

        <h2>Manage Your Complaints</h2>

        <p>
            Report a new civic problem or check the status
            of your existing complaints.
        </p>

        <a href="report.php" class="action-btn">
            + Report a Problem
        </a>

        <a href="my_complaints.php" class="action-btn secondary-btn">
            My Complaints
        </a>

    </section>

</main>

</body>
</html>