<?php
session_start();

// Redirect if the user is not logged in or not a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: login.php");
    exit();
}

// Load customer connection
require("./conx/conx_customer.php");

// Retrieve user ID from session
$user_id = $_SESSION['user_id'];

// Prepare and execute query
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $conx_customer->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Fetch user data
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if a result was found
if (!$user) {
    header("Location: index.php");
    exit();
}

// User data
$res_Uname = $user['user_fname'];
$res_Lname = $user['user_lname'];
$res_Email = $user['user_email'];
$res_Connum = $user['user_connum'];
$res_Age = $user['user_age'];
$user_pic_blob = $user['user_pic'];

// Encode the blob data to base64 for displaying in HTML
$user_pic_base64 = base64_encode($user_pic_blob);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Updated Profile</title>
</head>

<body>
    <div class="container">
        <div class="box form-box">
            <header>User Profile</header>
            <img src="data:image/jpeg;base64,<?= $user_pic_base64; ?>" alt="Profile Image" class="circular-profile-pic">
            <p>First Name: <strong><?php echo $res_Uname; ?></strong></p>
            <p>Last Name: <strong><?php echo $res_Lname; ?></strong></p>
            <p>Email: <strong><?php echo $res_Email; ?></strong></p>
            <p>Contact Number: <strong><?php echo $res_Connum; ?></strong></p>
            <p>Age: <strong><?php echo $res_Age; ?></strong></p>

            <!-- Add a button to go back to the profile page -->
            <a href="customerPage.php"><button class="btn">Back to Reservations</button></a>
        </div>
    </div>

</body>

</html>