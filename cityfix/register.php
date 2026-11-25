<?php

include "db.php";

$message = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($name == "" || $email == "" ||
        $password == "" || $confirm_password == "") {

        $message = "Please fill all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Enter a valid email address.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM Users WHERE email = ?"
        );

        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "Email already registered.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO Users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $name,
                $email,
                $hashed_password
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Registration successful!";
            } else {
                $message = "Registration failed.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>NagrikX Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header>
    <h2>NagrikX</h2>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<section class="hero">

    <h1>Create Your Account</h1>

    <form method="POST">

        <input type="text"
               name="name"
               placeholder="Enter your name"
               required>

        <br><br>

        <input type="email"
               name="email"
               placeholder="Enter your email"
               required>

        <br><br>

        <input type="password"
               name="password"
               placeholder="Enter password"
               required>

        <br><br>

        <input type="password"
               name="confirm_password"
               placeholder="Confirm password"
               required>

        <br><br>

        <button type="submit" name="register" class="btn">
            Register
        </button>

    </form>

    <p><?php echo htmlspecialchars($message); ?></p>

    <p>Already have an account?
        <a href="login.php">Login here</a>
    </p>

</section>

</body>
</html>