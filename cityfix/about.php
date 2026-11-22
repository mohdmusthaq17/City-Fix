<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us - CityFix</title>

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

        .hero {
            background: #123456;
            color: white;
            text-align: center;
            padding: 65px 20px;
        }

        .hero h1 {
            font-size: 38px;
            margin-bottom: 15px;
        }

        .hero p {
            font-size: 17px;
            line-height: 1.7;
        }

        .container {
            max-width: 1050px;
            margin: 40px auto;
            padding: 20px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .section h2 {
            color: #123456;
            margin-bottom: 15px;
        }

        .section p {
            line-height: 1.8;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .feature {
            background: #eff6ff;
            padding: 22px;
            border-radius: 10px;
            border-left: 5px solid #2563eb;
        }

        .feature h3 {
            color: #123456;
            margin-bottom: 10px;
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

            .hero h1 {
                font-size: 28px;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>CityFix</h2>

    <nav>
        <a href="index.php">Home</a>

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

<section class="hero">
    <h1>About CityFix</h1>
    <p>
        Making our cities cleaner, safer and better
        through digital civic complaint management.
    </p>
</section>

<main class="container">

    <section class="section">
        <h2>Who We Are</h2>
        <p>
            CityFix is a web-based civic complaint management
            system designed to help citizens report common
            public problems in their areas. It provides a
            simple platform where users can submit complaints
            and track their progress.
        </p>
    </section>

    <section class="section">
        <h2>Our Mission</h2>
        <p>
            Our mission is to make reporting civic problems
            easier and more transparent. We aim to connect
            citizens and complaint administrators through
            a simple digital platform.
        </p>
    </section>

    <section class="section">
        <h2>Our Vision</h2>
        <p>
            Our vision is to support cleaner, safer and
            better-maintained communities by encouraging
            citizens to participate in solving public
            problems.
        </p>
    </section>

    <section class="section">
        <h2>What Problems Can You Report?</h2>

        <div class="features">

            <div class="feature">
                <h3>Road Damage</h3>
                <p>Report damaged roads and potholes.</p>
            </div>

            <div class="feature">
                <h3>Garbage</h3>
                <p>Report garbage dumping and waste problems.</p>
            </div>

            <div class="feature">
                <h3>Streetlights</h3>
                <p>Report broken or non-working streetlights.</p>
            </div>

            <div class="feature">
                <h3>Water Leakage</h3>
                <p>Report water leakage and related issues.</p>
            </div>

        </div>
    </section>

    <section class="section">
        <h2>Why Choose CityFix?</h2>
        <p>
            CityFix allows users to submit complaints,
            upload supporting images, view their complaints
            and track updates. Administrators can manage
            complaints and update their status.
        </p>
    </section>

</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> CityFix. All Rights Reserved.</p>
</footer>

</body>
</html>