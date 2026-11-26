<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "######";
$username = "######";
$password = "#######";
$database = "######";

// Create database connection
$conn = mysqli_connect(
    $servername,
    $username,
    $password,
    $database
);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
