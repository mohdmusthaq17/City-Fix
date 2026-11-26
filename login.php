<?php

session_start();
include "db.php";

$message = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, name, email, password, role
            FROM users WHERE email = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {

        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on user role
            if ($user['role'] == 'admin') {

                header("Location: admin.php");
                exit();

            } else {

                header("Location: dashboard.php");
                exit();

            }

        } else {
            $message = "Incorrect email or password.";
        }

    } else {
        $message = "Incorrect email or password.";
    }

    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>NagrikX Login</title>
    <link rel="stylesheet" href="./css/style.css?v=2">
</head>

<body>

<header>
    <h2>NagrikX</h2>

    <nav>
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<section class="hero">

    <h1>Welcome Back!</h1>

    <form method="POST">

        <input type="email"
               name="email"
               placeholder="Enter your email"
               required>

        <br><br>

        <input type="password"
               name="password"
               placeholder="Enter your password"
               required>

        <br><br>

        <button type="submit" name="login" class="btn">
            Login
        </button>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

    </form>

    <p>Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</section>

</body>
</html>