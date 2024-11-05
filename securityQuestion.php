<?php
session_start();
require("./conx/conx_login.php");

if (!function_exists('resetFailCounter')) {
    function resetFailCounter($conn, $user_id)
    {
        try {
            $stmt = $conn->prepare("UPDATE users SET fail_counter = 0 WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }
}

function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

$message = '';
$showForm = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_email = $_SESSION['email'];
    $user_question_input = sanitizeInput($_POST['user_question']);

    try {
        $stmt = $conn->prepare("SELECT user_id, user_question FROM users WHERE user_email = :email");
        $stmt->bindParam(':email', $user_email, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $row['user_id'];
            $user_question = $row['user_question'];

            if (password_verify($user_question_input, $user_question)) {
                $update_status_stmt = $conn->prepare("UPDATE users SET user_status = 'pending' WHERE user_id = :user_id");
                $update_status_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $update_status_stmt->execute();

                if (function_exists('resetFailCounter')) {
                    resetFailCounter($conn, $user_id);
                } else {
                    error_log('Function resetFailCounter does not exist.');
                }

                $message = "Your password will be reset to '@Default1'. Please try logging in later while the manager updates your account.";
            } else {
                $update_status_stmt = $conn->prepare("UPDATE users SET user_status = 'compromised' WHERE user_id = :user_id");
                $update_status_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $update_status_stmt->execute();

                $message = "To recover a compromised account, contact the manager directly through your email or contact number provided upon registration.";
            }
            $showForm = false;
        } else {
            $message = "Invalid request.";
            $showForm = false;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message = "An error occurred. Please try again later.";
        $showForm = false;
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
    <title>Security Question</title>
</head>

<body>
    <div class="container">
        <div class="box form-box">
            <header>Security Question</header>
            <?php if (!empty($message)) : ?>
                <div class='alert alert-info'>
                    <p><?php echo $message; ?></p>
                </div>
                <br>
            <?php endif; ?>
            <?php if ($showForm) : ?>
                <form action="" method="post">
                    <div class="field input">
                        <label for="user_question">Please Enter your SeedPhrase:</label>
                        <input type="text" name="user_question" id="user_question" required>
                    </div>
                    <div class="field">
                        <button type="submit" class="btn btn-dark">Submit</button>
                    </div>
                </form>
            <?php else : ?>
                <form action="login.php" method="get">
                    <div class="field">
                        <button type="submit" class="btn btn-dark">Back</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

</body>

</html>