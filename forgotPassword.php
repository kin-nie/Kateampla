<?php
session_start();
require("./conx/conx_login.php");

function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_email = sanitizeInput($_POST['email']);

    if (empty($user_email)) {
        $message = "Please enter your email address.";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_email = :email");
            $stmt->bindParam(':email', $user_email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['email'] = $user_email;
                header("Location: securityQuestion.php");
                exit();
            } else {
                $message = "Email address not found.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = "An error occurred. Please try again later.";
        }
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
    <title>Forgot Password</title>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Kateampla Karaoke</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="box form-box">
            <header>Forgot Password</header>
            <?php if (!empty($message)) : ?>
                <div class='alert alert-danger'>
                    <p><?php echo $message; ?></p>
                </div>
                <br>
            <?php endif; ?>
            <form action="" method="post">
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" autocomplete="off" required>
                </div>
                <div class="field">
                    <button type="submit" class="btn btn-dark">Submit</button>
                </div>
            </form>
            <div class="links">
                <a href="login.php">Back</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</body>

</html>