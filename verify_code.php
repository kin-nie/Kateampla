<?php
require("./conx/conx_customer.php");;

if (isset($_POST['verify'])) {
    $verification_code = $_POST['verification_code'];

    $stmt = $conx_customer->prepare("SELECT * FROM users WHERE user_verification = :verification_code AND user_status = 'inactivated'");
    $stmt->bindParam(':verification_code', $verification_code);
    $stmt->execute();

    $message = "";
    $button = "";

    if ($stmt->rowCount() == 1) {
        $update_stmt = $conx_customer->prepare("UPDATE users SET user_status = 'activated' WHERE user_verification = :verification_code");
        $update_stmt->bindParam(':verification_code', $verification_code);
        if ($update_stmt->execute()) {
            $message = "Account verified successfully!";
            $button = "<a href='login.php'><button class='btn btn-dark'>Login Now</button></a>";
        } else {
            $message = "Failed to activate account. Please try again.";
            $button = "<a href='javascript:self.history.back()'><button class='btn btn-dark'>Go Back</button></a>";
        }
    } else {
        $message = "Invalid verification code or account already activated.";
        $button = "<a href='javascript:self.history.back()'><button class='btn btn-dark'>Go Back</button></a>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="style/style.css">
    <title>Verify Account</title>
</head>

<body>
    <div class="container">
        <div class="box form-box">
            <header>Verification Status</header>
            <div class="message">
                <p><?php echo $message; ?></p>
            </div>
            <?php echo $button; ?>
        </div>
    </div>
</body>

</html>