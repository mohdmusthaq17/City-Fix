<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$user_id = $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Citizen answers: is the problem really fixed?
if (isset($_POST['answer'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request.");
    }
    $cid = (int)($_POST['complaint_id'] ?? 0);
    $answer = $_POST['answer'];
    $note = substr(trim($_POST['note'] ?? ''), 0, 255);
    if ($note === '') { $note = 'No reason given'; }

    if ($cid > 0 && $answer === 'yes') {
        $upd = mysqli_prepare($conn, "UPDATE complaints SET citizen_confirmation = 'confirmed'
            WHERE id = ? AND user_id = ? AND status = 'Resolved' AND citizen_confirmation = 'none'");
        mysqli_stmt_bind_param($upd, "ii", $cid, $user_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        header("Location: my_complaints.php?confirmed=1");
        exit();
    }
    if ($cid > 0 && $answer === 'no') {
        $upd = mysqli_prepare($conn, "UPDATE complaints SET status = 'In Progress',
            citizen_confirmation = 'disputed', citizen_note = ?, reopen_count = reopen_count + 1
            WHERE id = ? AND user_id = ? AND status = 'Resolved'
            AND citizen_confirmation = 'none' AND reopen_count < 2");
        mysqli_stmt_bind_param($upd, "sii", $note, $cid, $user_id);
        mysqli_stmt_execute($upd);
        $done = mysqli_stmt_affected_rows($upd);
        mysqli_stmt_close($upd);
        header("Location: my_complaints.php?" . ($done === 1 ? "reopened=1" : "limit=1"));
        exit();
    }
}

// Messages
$flash = "";
if (isset($_GET['success'])) {
    $flash = "Your complaint has been submitted successfully!";
    $ok = ["Road Damage", "Garbage", "Streetlights", "Water Leakage"];
    if (isset($_GET['detected']) && in_array($_GET['detected'], $ok, true)) {
        $flash .= " AI detected the category: " . $_GET['detected'] . ".";
    }
}
if (isset($_GET['confirmed'])) { $flash = "Thank you! You confirmed the problem is fixed."; }
if (isset($_GET['reopened'])) { $flash = "Thanks. Your complaint was sent back to the admin as not fixed."; }
if (isset($_GET['limit'])) { $flash = "This complaint cannot be reopened again. Please contact the admin."; }

$sql = "SELECT * FROM complaints
        WHERE user_id = ?
        ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database query failed.");
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Complaints - CityFix</title>

    <link rel="stylesheet" href="./css/style.css?v=4">

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
            margin-left: 18px;
            font-weight: bold;
        }

        nav a:hover {
            color: #38bdf8;
        }

        .page-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 35px;
        }

        .page-title h1 {
            color: #123456;
            font-size: 32px;
        }

        .page-title p {
            color: #64748b;
        }

        .complaint-card {
            background: white;
            padding: 25px;
            margin: 22px auto;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
            border-left: 5px solid #2563eb;
        }

        .complaint-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
        }

        .complaint-top h2 {
            color: #123456;
            margin: 0;
        }

        .complaint-id {
            color: #64748b;
            font-size: 14px;
        }

        .complaint-info p {
            color: #475569;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            padding: 7px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .in-progress {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .resolved {
            background: #dcfce7;
            color: #166534;
        }

        .unknown {
            background: #e2e8f0;
            color: #334155;
        }

        .complaint-image {
            margin-top: 15px;
        }

        .complaint-image img {
            width: 100%;
            max-width: 350px;
            max-height: 250px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
        }

        .empty-state {
            background: white;
            padding: 40px 20px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .empty-state h2 {
            color: #123456;
        }

        .action-btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 13px 22px;
            margin-top: 15px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .action-btn:hover {
            background: #1d4ed8;
        }

        @media (max-width: 600px) {

            header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            nav {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }

            nav a {
                margin: 0;
                font-size: 14px;
            }

            .complaint-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-title h1 {
                font-size: 26px;
            }

        }
        
                .flash { background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px; }
        .confirm-box { background:#fffbeb; border:1px solid #fcd34d; padding:15px; border-radius:10px; margin-top:15px; }
        .confirm-box textarea { width:100%; box-sizing:border-box; padding:10px; margin:8px 0; border:1px solid #cbd5e1; border-radius:6px; }
        .btn-yes, .btn-no { border:none; color:white; padding:10px 18px; border-radius:6px; font-weight:bold; cursor:pointer; margin-right:8px; }
        .btn-yes { background:#16a34a; }
        .btn-no { background:#dc2626; }
        .feedback-ok { color:#166534; font-weight:bold; margin-top:12px; }
        .feedback-bad { color:#b91c1c; font-weight:bold; margin-top:12px; }

    </style>

</head>

<body>

<header>

    <h2>CityFix</h2>

    <nav>
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="report.php">Report Problem</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="page-container">

    <section class="page-title">

        <h1>My Complaints</h1>

        <p>
            Track the progress of all your reported problems.
        </p>

    </section>
    
        <?php if ($flash !== "") { ?>
        <div class="flash"><?php echo htmlspecialchars($flash); ?></div>
    <?php } ?>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <?php while ($row = mysqli_fetch_assoc($result)) { ?>

            <?php

            $status = $row['status'] ?? '';

            $status_class = 'unknown';

            if ($status === 'Pending') {
                $status_class = 'pending';
            } elseif ($status === 'In Progress') {
                $status_class = 'in-progress';
            } elseif ($status === 'Resolved') {
                $status_class = 'resolved';
            }

            ?>

            <div class="complaint-card">

                <div class="complaint-top">

                    <h2>
                        <?php echo htmlspecialchars($row['category']); ?>
                    </h2>

                    <span class="complaint-id">
                        Complaint ID: #<?php echo (int)$row['id']; ?>
                    </span>

                </div>

                <div class="complaint-info">

                    <p>
                        <strong>Category:</strong><br>
                        <?php echo htmlspecialchars($row['category']); ?>
                        <?php if (!empty($row['auto_category'])): ?>
                            <em>(detected by AI from your photo)</em>
                        <?php endif; ?>
                    </p>

                    <p>
                        <strong>Description:</strong><br>
                        <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                    </p>

                    <p>
                        <strong>Location:</strong><br>
                        <?php echo htmlspecialchars($row['location']); ?>
                                                <?php if (!empty($row['latitude'])) { ?>
                            <br><a href="https://www.openstreetmap.org/?mlat=<?php echo (float)$row['latitude']; ?>&mlon=<?php echo (float)$row['longitude']; ?>#map=17/<?php echo (float)$row['latitude']; ?>/<?php echo (float)$row['longitude']; ?>" target="_blank" rel="noopener">View pinned spot on map</a>
                        <?php } ?>
                    </p>

                    <p>
                        <strong>Status:</strong>

                        <span class="status <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status); ?>
                        </span>

                    </p>

                    <p>
                        <strong>Submitted Date:</strong><br>

                        <?php
                        echo !empty($row['created_at'])
                            ? htmlspecialchars(date("d M Y, h:i A", strtotime($row['created_at'])))
                            : "Not available";
                        ?>

                    </p>

                </div>
                
                                <?php
                $conf = $row['citizen_confirmation'] ?? 'none';
                $reopens = (int)($row['reopen_count'] ?? 0);
                ?>

                <?php if ($status === 'Resolved' && $conf === 'none') { ?>
                    <div class="confirm-box">
                        <p><strong>The admin marked this as resolved. Is the problem really fixed?</strong></p>
                        <form method="POST" action="my_complaints.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="complaint_id" value="<?php echo (int)$row['id']; ?>">
                            <textarea name="note" maxlength="255" placeholder="Optional: if it is NOT fixed, tell us what is still wrong"></textarea>
                            <button type="submit" name="answer" value="yes" class="btn-yes">Yes, it is fixed</button>
                            <?php if ($reopens < 2) { ?>
                                <button type="submit" name="answer" value="no" class="btn-no">No, still not fixed</button>
                            <?php } ?>
                        </form>
                    </div>
                <?php } elseif ($conf === 'confirmed') { ?>
                    <p class="feedback-ok">&#10004; You confirmed this problem was fixed.</p>
                <?php } elseif ($conf === 'disputed') { ?>
                    <p class="feedback-bad">&#9888; You reported it as not fixed
                        (<?php echo htmlspecialchars($row['citizen_note'] ?? ''); ?>). The admin has been asked to check again.</p>
                <?php } ?>

                <?php if (!empty($row['image'])) { ?>

                    <div class="complaint-image">

                        <strong>Complaint Photo:</strong><br>

                        <img
                            src="uploads/<?php echo rawurlencode(basename($row['image'])); ?>"
                            alt="Complaint photo"
                            loading="lazy"
                        >

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    <?php } else { ?>

        <div class="empty-state">

            <h2>No Complaints Found</h2>

            <p>
                You have not submitted any complaints yet.
            </p>

            <a href="report.php" class="action-btn">
                + Report a Problem
            </a>

        </div>

    <?php } ?>

</main>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>