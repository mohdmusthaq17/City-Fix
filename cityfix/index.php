<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NagrikX | Smart City Complaint Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f8fc;
            color: #172b4d;
        }

        /* Navigation */

        header {
            background: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 27px;
            font-weight: 800;
            color: #1769aa;
            text-decoration: none;
        }

        .logo span {
            color: #16a085;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        nav a {
            text-decoration: none;
            color: #344563;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
        }

        nav a:hover {
            color: #1769aa;
        }

        nav .register-btn {
            background: #1769aa;
            color: white;
            padding: 11px 20px;
            border-radius: 7px;
        }

        nav .register-btn:hover {
            background: #10558c;
            color: white;
        }

        /* Hero Section */

        .hero {
            min-height: 540px;
            padding: 100px 8%;
            display: flex;
            align-items: center;
            background: linear-gradient(120deg, #0d3157, #1769aa);
            color: white;
        }

        .hero-content {
            max-width: 700px;
        }

        .hero-tag {
            display: inline-block;
            background: rgba(255,255,255,0.13);
            padding: 9px 16px;
            border-radius: 30px;
            font-size: 13px;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }

        .hero h1 {
            font-size: 52px;
            line-height: 1.2;
            font-weight: 800;
            margin-bottom: 22px;
        }

        .hero h1 span {
            color: #6ee7c8;
        }

        .hero p {
            font-size: 17px;
            line-height: 1.8;
            color: #dceafa;
            max-width: 570px;
            margin-bottom: 32px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 14px 25px;
            border-radius: 7px;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-primary {
            background: #16a085;
            color: white;
        }

        .btn-primary:hover {
            background: #11816c;
            transform: translateY(-2px);
        }

        .btn-outline {
            border: 1px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: white;
            color: #1769aa;
        }

        /* Features */

        .features {
            padding: 80px 7%;
            text-align: center;
        }

        .section-tag {
            color: #16a085;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .features h2,
        .how-it-works h2 {
            font-size: 32px;
            margin: 12px 0;
            color: #172b4d;
        }

        .section-description {
            color: #68778d;
            margin-bottom: 45px;
            line-height: 1.7;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            text-align: left;
        }

        .card {
            background: white;
            padding: 28px 23px;
            border-radius: 12px;
            border: 1px solid #e8edf4;
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-7px);
            box-shadow: 0 12px 30px rgba(23,105,170,0.10);
            border-color: #b8d9f5;
        }

        .card-icon {
            width: 55px;
            height: 55px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #eaf4ff;
            border-radius: 12px;
            font-size: 27px;
            margin-bottom: 20px;
        }

        .card h3 {
            font-size: 17px;
            margin-bottom: 12px;
        }

        .card p {
            color: #68778d;
            font-size: 14px;
            line-height: 1.7;
        }

        /* How it works */

        .how-it-works {
            background: white;
            padding: 75px 7%;
            text-align: center;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 45px;
        }

        .step {
            padding: 25px;
        }

        .step-number {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e5f4f0;
            color: #16a085;
            font-size: 22px;
            font-weight: 800;
        }

        .step h3 {
            margin-bottom: 12px;
        }

        .step p {
            color: #68778d;
            font-size: 14px;
            line-height: 1.7;
        }

        /* Call to action */

        .cta {
            margin: 70px 7%;
            padding: 55px 25px;
            text-align: center;
            background: linear-gradient(120deg, #1769aa, #0d3157);
            color: white;
            border-radius: 15px;
        }

        .cta h2 {
            font-size: 30px;
            margin-bottom: 15px;
        }

        .cta p {
            color: #dceafa;
            margin-bottom: 25px;
            line-height: 1.7;
        }

        /* Footer */

        footer {
            background: #10243e;
            color: white;
            padding: 35px 7%;
            text-align: center;
        }

        footer h2 {
            margin-bottom: 12px;
            color: #6ee7c8;
        }

        footer p {
            color: #b9c8da;
            font-size: 13px;
            line-height: 1.8;
        }

        .footer-links {
            margin: 18px 0;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
        }

        .footer-links a:hover {
            color: #6ee7c8;
        }

        /* Mobile Responsive */

        @media (max-width: 900px) {
            header {
                flex-direction: column;
                gap: 18px;
            }

            nav {
                justify-content: center;
                flex-wrap: wrap;
                gap: 15px;
            }

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .hero {
                padding: 75px 7%;
                min-height: 450px;
            }

            .hero h1 {
                font-size: 37px;
            }

            .hero p {
                font-size: 15px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .steps {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .features h2,
            .how-it-works h2 {
                font-size: 26px;
            }

            .cta h2 {
                font-size: 25px;
            }

            nav a {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<!-- Navigation -->
<header>

    <a href="index.php" class="logo">Nagrik<span>X.</span></a>

    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact Us</a>
        <a href="login.php">Login</a>
        <a href="register.php" class="register-btn">Get Started</a>
    </nav>

</header>

<!-- Hero Section -->
<section class="hero">

    <div class="hero-content">

        <div class="hero-tag">
            YOUR CITY, YOUR RESPONSIBILITY
        </div>

        <h1>
            Make Your City<br>
            <span>Better Together.</span>
        </h1>

        <p>
            Help build a cleaner, safer and smarter city.
            Report civic problems, track complaints and
            become a part of positive change in your community.
        </p>

        <div class="hero-buttons">

            <a href="report.php" class="btn btn-primary">
                Report a Problem →
            </a>

            <a href="about.php" class="btn btn-outline">
                Learn More
            </a>

        </div>

    </div>

</section>

<!-- Complaint Categories -->
<section class="features">

    <div class="section-tag">OUR SERVICES</div>

    <h2>What Can You Report?</h2>

    <p class="section-description">
        Help us identify and solve common problems in your city.
    </p>

    <div class="cards">

        <div class="card">
            <div class="card-icon">🛣️</div>
            <h3>Road Damage</h3>
            <p>
                Report potholes, damaged roads and
                unsafe road conditions.
            </p>
        </div>

        <div class="card">
            <div class="card-icon">🗑️</div>
            <h3>Garbage Problems</h3>
            <p>
                Report garbage dumping, overflowing bins
                and unclean public areas.
            </p>
        </div>

        <div class="card">
            <div class="card-icon">💡</div>
            <h3>Streetlights</h3>
            <p>
                Report broken streetlights and
                dark public spaces.
            </p>
        </div>

        <div class="card">
            <div class="card-icon">💧</div>
            <h3>Water Leakage</h3>
            <p>
                Report leaking water pipes and
                water wastage problems.
            </p>
        </div>

    </div>

</section>

<!-- How it Works -->
<section class="how-it-works">

    <div class="section-tag">SIMPLE PROCESS</div>

    <h2>How NagrikX Works</h2>

    <p class="section-description">
        Three simple steps to make a difference.
    </p>

    <div class="steps">

        <div class="step">

            <div class="step-number">1</div>

            <h3>Create an Account</h3>

            <p>
                Register or log in to your NagrikX account
                to access the complaint portal.
            </p>

        </div>

        <div class="step">

            <div class="step-number">2</div>

            <h3>Report a Problem</h3>

            <p>
                Select a complaint category, describe
                the issue and upload an image if needed.
            </p>

        </div>

        <div class="step">

            <div class="step-number">3</div>

            <h3>Track Your Complaint</h3>

            <p>
                Check your dashboard to follow the
                progress of your submitted complaint.
            </p>

        </div>

    </div>

</section>

<!-- Call to Action -->
<section class="cta">

    <h2>Your City Needs Your Voice!</h2>

    <p>
        Don't ignore civic problems. Report them today
        and help make your community better.
    </p>

    <a href="register.php" class="btn btn-primary">
        Join NagrikX Today →
    </a>

</section>

<!-- Footer -->
<footer>

    <h2>NagrikX.</h2>

    <p>
        Building Better Cities Through Citizen Participation.
    </p>

    <div class="footer-links">
        <a href="index.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact Us</a>
        <a href="login.php">Login</a>
    </div>

    <p>
        © 2026 NagrikX. All Rights Reserved.
    </p>

</footer>

</body>
</html>
