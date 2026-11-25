<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Top 10 citizens by points (only first names are shown, for privacy)
$result = mysqli_query(
    $conn,
    "SELECT name, points FROM users
     WHERE role = 'citizen' AND points > 0
     ORDER BY points DESC, id ASC
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - NagrikX</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .board { max-width: 600px; margin: 30px auto; padding: 25px; background: #fff;
                 border-radius: 12px; box-shadow: 0 3px 15px rgba(0,0,0,0.1); }
        .board table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .board th, .board td { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .board td.pts { text-align: right; font-weight: bold; }
        .note { color: #4b5563; font-size: 0.95rem; }
    </style>
</head>
<body>
<header>
    <h2>NagrikX</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">My Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="board">
    <h2>Top Citizens</h2>
    <p class="note">
        You earn 10 points each time an admin verifies that your report is genuine.
    </p>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <table>
            <tr><th>Rank</th><th>Citizen</th><th style="text-align:right;">Points</th></tr>
            <?php $rank = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars(explode(" ", trim($row['name']))[0]); ?></td>
                    <td class="pts"><?php echo (int)$row['points']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No points yet. Report a genuine problem to be the first!</p>
    <?php endif; ?>
</div>
</body>
</html>
