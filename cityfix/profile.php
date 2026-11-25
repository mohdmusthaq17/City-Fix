<?php
session_start();
include "db.php";

// Check user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$sql = "SELECT name, email, points FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get complaint statistics
$sql = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(status = 'Pending'), 0) AS pending,
    COALESCE(SUM(status = 'In Progress'), 0) AS progress,
    COALESCE(SUM(status = 'Resolved'), 0) AS resolved
    FROM complaints
    WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - NagrikX</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #0f172a;
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
        }

        nav a:hover {
            color: #38bdf8;
        }

        .container {
            max-width: 950px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .profile-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .avatar {
            width: 85px;
            height: 85px;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .profile-heading h1 {
            margin-bottom: 8px;
        }

        .details {
            background: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .detail-row {
            padding: 13px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row strong {
            display: inline-block;
            width: 130px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .stat-card {
            padding: 20px 10px;
            border-radius: 10px;
            text-align: center;
            background: #eff6ff;
        }

        .stat-card h2 {
            color: #1d4ed8;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .stat-card p {
            font-size: 14px;
            color: #475569;
        }

        .actions {
            text-align: center;
            margin-top: 25px;
        }

        .actions a {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 20px;
            margin: 5px;
            border-radius: 7px;
            text-decoration: none;
        }

        .actions a:hover {
            background: #1d4ed8;
        }

        @media (max-width: 650px) {
            header {
                flex-direction: column;
            }

            nav a {
                margin: 0 7px;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .detail-row strong {
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>NagrikX</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="my_complaints.php">My Complaints</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <div class="profile-card">

        <div class="profile-heading">
            <div class="avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>

            <h1><?php echo htmlspecialchars($user['name']); ?></h1>
            <p>NagrikX User Profile</p>
        </div>

        <h3>Personal Information</h3>

        <div class="details">
            <div class="detail-row">
                <strong>Full Name:</strong>
                <?php echo htmlspecialchars($user['name']); ?>
            </div>

            <div class="detail-row">
                <strong>Email:</strong>
                <?php echo htmlspecialchars($user['email']); ?>
            </div>

            <div class="detail-row">
                <strong>Account ID:</strong>
                <?php echo (int)$user_id; ?>
            </div>
        </div>

        <h3 style="margin-bottom: 15px;">My Points</h3>
        <p style="margin-bottom: 20px;">
            <strong><?php echo (int)$user['points']; ?> points</strong>
            earned from verified complaints.
            <a href="leaderboard.php">See the leaderboard</a>
        </p>

        <h3 style="margin-bottom: 15px;">My Complaint Statistics</h3>

        <div class="stats">

            <div class="stat-card">
                <h2><?php echo (int)$stats['total']; ?></h2>
                <p>Total Complaints</p>
            </div>

            <div class="stat-card">
                <h2><?php echo (int)$stats['pending']; ?></h2>
                <p>Pending</p>
            </div>

            <div class="stat-card">
                <h2><?php echo (int)$stats['progress']; ?></h2>
                <p>In Progress</p>
            </div>

            <div class="stat-card">
                <h2><?php echo (int)$stats['resolved']; ?></h2>
                <p>Resolved</p>
            </div>

        </div>

        <div class="actions">
            <a href="report.php">Submit Complaint</a>
            <a href="my_complaints.php">View My Complaints</a>
        </div>

    </div>
</div>

</body>
</html>