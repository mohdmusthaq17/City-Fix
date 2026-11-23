<?php
session_start();
include "db.php";

// Admin login protection
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: login.php");
    exit();
}

$message = "";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Update complaint status
if (isset($_POST['update_status'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid request.");
    }

    $id = (int)$_POST['complaint_id'];
    $status = $_POST['status'];

    $allowed = ["Pending", "In Progress", "Resolved"];

    if ($id > 0 && in_array($status, $allowed, true)) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE complaints SET status = ? WHERE id = ?"
        );

        if (!$stmt) {
            die("Database query failed.");
        }

        mysqli_stmt_bind_param($stmt, "si", $status, $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: admin.php?updated=1");
            exit();
        }

        $message = "Could not update status.";
        mysqli_stmt_close($stmt);

    } else {
        $message = "Invalid complaint or status.";
    }
}

// Verify a genuine complaint and reward the citizen (once per complaint)
define("POINTS_PER_VERIFIED", 10);
if (isset($_POST['verify_complaint'])) {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid request.");
    }
    $id = (int)$_POST['complaint_id'];
    if ($id > 0) {
        $points = POINTS_PER_VERIFIED;
        mysqli_begin_transaction($conn);
        $stmt = mysqli_prepare($conn, "UPDATE complaints SET verified = 1 WHERE id = ? AND verified = 0");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $changed = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        if ($changed === 1) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users SET points = points + ? WHERE id = (SELECT user_id FROM complaints WHERE id = ?)"
            );
            mysqli_stmt_bind_param($stmt, "ii", $points, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        mysqli_commit($conn);
        header("Location: admin.php?verified=1");
        exit();
    }
}
if (isset($_GET['verified'])) {
    $message = "Complaint verified. The citizen earned " . POINTS_PER_VERIFIED . " points!";
}
if (isset($_GET['updated'])) {
    $message = "Complaint status updated successfully!";
}

// Search and filters
$search = trim($_GET['search'] ?? "");
$status_filter = $_GET['status'] ?? "";
$category_filter = $_GET['category'] ?? "";

$sql = "SELECT complaints.*, users.name, users.email
        FROM complaints
        JOIN users ON complaints.user_id = users.id
        WHERE 1=1";

if ($search !== "") {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (users.name LIKE '%$search_safe%'
              OR users.email LIKE '%$search_safe%')";
}

if (in_array($status_filter, ["Pending", "In Progress", "Resolved"], true)) {
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND complaints.status = '$status_safe'";
}

if (in_array($category_filter, ["Road Damage", "Garbage", "Streetlights", "Water Leakage"], true)) {
    $category_safe = mysqli_real_escape_string($conn, $category_filter);
    $sql .= " AND complaints.category = '$category_safe'";
}

$sql .= " ORDER BY complaints.created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database error: " . htmlspecialchars(mysqli_error($conn)));
}

// Dashboard statistics
$stats_sql = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(status = 'Pending'), 0) AS pending,
    COALESCE(SUM(status = 'In Progress'), 0) AS progress,
    COALESCE(SUM(status = 'Resolved'), 0) AS resolved
    FROM complaints";

$stats_result = mysqli_query($conn, $stats_sql);

if (!$stats_result) {
    die("Could not load statistics.");
}

$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityFix Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .admin-container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h2 {
            color: #2563eb;
            margin-bottom: 8px;
        }

        .filter-form {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .filter-form input,
        .filter-form select {
            padding: 10px;
            margin: 5px;
        }

        .complaint-card {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .complaint-card img {
            max-width: 250px;
            display: block;
            margin: 10px 0;
        }

        .success-message {
            color: green;
            margin: 15px 0;
        }

        @media (max-width: 700px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-form input,
            .filter-form select {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>CityFix Admin</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="admin-container">

    <h2>Admin Dashboard</h2>

    <?php if ($message !== ""): ?>
        <p class="success-message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <div class="stats">

        <div class="stat-card">
            <h2><?php echo $stats['total']; ?></h2>
            <p>Total Complaints</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['pending']; ?></h2>
            <p>Pending</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['progress']; ?></h2>
            <p>In Progress</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $stats['resolved']; ?></h2>
            <p>Resolved</p>
        </div>

    </div>

    <h2>Manage Complaints</h2>

    <form method="GET" action="admin.php" class="filter-form">

        <input
            type="text"
            name="search"
            placeholder="Search by citizen name or email"
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <select name="status">
            <option value="">All Status</option>
            <option value="Pending" <?php if ($status_filter === "Pending") echo "selected"; ?>>
                Pending
            </option>
            <option value="In Progress" <?php if ($status_filter === "In Progress") echo "selected"; ?>>
                In Progress
            </option>
            <option value="Resolved" <?php if ($status_filter === "Resolved") echo "selected"; ?>>
                Resolved
            </option>
        </select>

        <select name="category">
            <option value="">All Categories</option>
            <option value="Road Damage" <?php if ($category_filter === "Road Damage") echo "selected"; ?>>
                Road Damage
            </option>
            <option value="Garbage" <?php if ($category_filter === "Garbage") echo "selected"; ?>>
                Garbage
            </option>
            <option value="Streetlights" <?php if ($category_filter === "Streetlights") echo "selected"; ?>>
                Streetlights
            </option>
            <option value="Water Leakage" <?php if ($category_filter === "Water Leakage") echo "selected"; ?>>
                Water Leakage
            </option>
        </select>

        <button type="submit">Search</button>
        <a href="admin.php">Reset</a>

    </form>
	<?php if (mysqli_num_rows($result) > 0): ?>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

            <div class="complaint-card">

                <h3>
                    Complaint #<?php echo (int)$row['id']; ?>
                </h3>

                <p>
                    <strong>Citizen:</strong>
                    <?php echo htmlspecialchars($row['name']); ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($row['email']); ?>
                </p>

                <p>
                    <strong>Category:</strong>
                    <?php echo htmlspecialchars($row['category']); ?><?php if (!empty($row['auto_category'])) echo ' (detected by AI)'; ?>
                </p>

                <p>
                    <strong>Description:</strong>
                    <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                </p>

                <p>
                    <strong>Location:</strong>
                    <?php echo htmlspecialchars($row['location']); ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?php echo htmlspecialchars($row['created_at']); ?>
                </p>

                <p>
                    <strong>Current Status:</strong>
                    <?php echo htmlspecialchars($row['status']); ?>
                </p>

                <p>
                    <strong>Verified:</strong>
                    <?php echo !empty($row['verified']) ? "Yes - citizen rewarded" : "Not yet"; ?>
                </p>

                <?php if (empty($row['verified'])): ?>
                    <form method="POST" action="admin.php" style="margin-bottom:10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="complaint_id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" name="verify_complaint">Verify genuine report (+<?php echo POINTS_PER_VERIFIED; ?> points)</button>
                    </form>
                <?php endif; ?>

                <?php if (!empty($row['image'])): ?>
                    <p><strong>Complaint Image:</strong></p>

                    <img
                        src="uploads/<?php echo rawurlencode(basename($row['image'])); ?>"
                        alt="Complaint Image"
                    >
                <?php endif; ?>

                <form method="POST" action="admin.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                    >

                    <input
                        type="hidden"
                        name="complaint_id"
                        value="<?php echo (int)$row['id']; ?>"
                    >

                    <label>Update Status:</label>

                    <select name="status" required>
                        <option value="Pending"
                            <?php if ($row['status'] === "Pending") echo "selected"; ?>>
                            Pending
                        </option>

                        <option value="In Progress"
                            <?php if ($row['status'] === "In Progress") echo "selected"; ?>>
                            In Progress
                        </option>

                        <option value="Resolved"
                            <?php if ($row['status'] === "Resolved") echo "selected"; ?>>
                            Resolved
                        </option>
                    </select>

                    <button type="submit" name="update_status">
                        Update Status
                    </button>

                </form>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>No complaints found.</p>

    <?php endif; ?>

</div>

</body>
</html>
	