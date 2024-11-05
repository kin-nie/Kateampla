<?php
session_start();
require("./conx/conx_login.php");

function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

function logFailedAttempt($conn, $user_id)
{
    $stmt = $conn->prepare("INSERT INTO audit_trail (user_id, action) VALUES (:user_id, 'Login-Failed')");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $fail_stmt = $conn->prepare("SELECT fail_counter FROM fail_logs WHERE user_id = :user_id");
    $fail_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $fail_stmt->execute();

    if ($fail_stmt->rowCount() > 0) {
        $fail_log = $fail_stmt->fetch(PDO::FETCH_ASSOC);
        $new_counter = $fail_log['fail_counter'] + 1;
        $update_stmt = $conn->prepare("UPDATE fail_logs SET fail_counter = :fail_counter WHERE user_id = :user_id");
        $update_stmt->bindParam(':fail_counter', $new_counter, PDO::PARAM_INT);
        $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $update_stmt->execute();
    } else {
        $insert_fail_stmt = $conn->prepare("INSERT INTO fail_logs (user_id, fail_counter) VALUES (:user_id, 1)");
        $insert_fail_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $insert_fail_stmt->execute();
    }
}

function resetFailCounter($conn, $user_id)
{
    $reset_stmt = $conn->prepare("UPDATE fail_logs SET fail_counter = 0 WHERE user_id = :user_id");
    $reset_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $reset_stmt->execute();
}

function checkFailCounter($conn, $user_id)
{
    $fail_stmt = $conn->prepare("SELECT fail_counter FROM fail_logs WHERE user_id = :user_id");
    $fail_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $fail_stmt->execute();
    return $fail_stmt->fetch(PDO::FETCH_ASSOC)['fail_counter'];
}

$error_message = '';
$attempts_left = 5; // Default number of attempts left

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_email = sanitizeInput($_POST['email']);
    $user_password = sanitizeInput($_POST['password']);

    if (empty($user_email) || empty($user_password)) {
        $error_message = "Please fill in all fields.";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, user_pass, user_type, user_status, user_question FROM users WHERE user_email = :email");
            $stmt->bindParam(':email', $user_email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_id = $row['user_id'];
                $hashed_pass = $row['user_pass'];
                $user_status = $row['user_status'];
                $user_question = $row['user_question'];

                $attempts_left = 5 - checkFailCounter($conn, $user_id); // Calculate attempts left

                if ($user_status == 'pending') {
                    $error_message = "Wait for account activation and input the password '@Default1'.";
                } elseif ($user_status == 'compromised') {
                    $error_message = "Account locked out. Contact the manager directly.";
                } else {
                    if (password_verify($user_password, $hashed_pass)) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_type'] = $row['user_type'];

                        $action = "Log-In";
                        $insert_audit_query = "INSERT INTO audit_trail (user_id, action) VALUES (:user_id, :action)";
                        $insert_audit_stmt = $conn->prepare($insert_audit_query);
                        $insert_audit_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $insert_audit_stmt->bindParam(':action', $action, PDO::PARAM_STR);
                        $insert_audit_stmt->execute();

                        resetFailCounter($conn, $user_id);

                        if ($row['user_type'] == 1) {
                            header("Location: adminPage.php");
                        } elseif ($row['user_type'] == 2) {
                            header("Location: customerPage.php");
                        } else {
                            $error_message = "Invalid user type.";
                        }
                        exit();
                    } else {
                        logFailedAttempt($conn, $user_id);
                        $attempts_left = 5 - checkFailCounter($conn, $user_id); // Update attempts left

                        if (checkFailCounter($conn, $user_id) >= 5) {
                            $_SESSION['email'] = $user_email;
                            $_SESSION['password'] = $user_password;
                            header("Location: securityQuestion.php");
                            exit();
                        } else {
                            $error_message = "Incorrect email or password. You have $attempts_left attempts left.";
                        }
                    }
                }
            } else {
                $error_message = "Incorrect email or password.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error_message = "An error occurred. Please try again later.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="style/style.css">
    <title>Login</title>
    <style>
    .password-container {
        display: flex;
        align-items: center;
    }

    .password-container input {
        flex: 1;
    }

    .password-container button {
        margin-left: 10px;
    }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Kateampla Karaoke</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
            <header>Login</header>
            <?php if (!empty($error_message)) : ?>
            <div class='alert alert-danger'>
                <p><?php echo $error_message; ?></p>
            </div>
            <br>
            <?php endif; ?>
            <form action="" method="post">
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" autocomplete="off" required>
                </div>
                <div class="field input password-container">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password"
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?=.*[a-zA-Z]).{8,}"
                        title="Must contain at least one number, one uppercase letter, one lowercase letter, one special character, and at least 8 or more characters"
                        autocomplete="off" required>
                    <button type="button" id="togglePassword" class="btn btn-outline-secondary">Show</button>
                </div>
                <div class="field">
                    <input type="submit" class="btn btn-dark" name="submit" value="Login">
                </div>
                <div class="links">
                    Don't have an account? <a href="regPage.php">Sign Up Now</a><br>
                    <a href="forgotPassword.php">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        var passwordField = document.getElementById('password');
        var passwordFieldType = passwordField.getAttribute('type');
        if (passwordFieldType === 'password') {
            passwordField.setAttribute('type', 'text');
            this.textContent = 'Hide';
        } else {
            passwordField.setAttribute('type', 'password');
            this.textContent = 'Show';
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</body>

</html>