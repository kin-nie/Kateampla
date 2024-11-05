<?php
session_start();

// Redirect if the user is not logged in or not a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: login.php");
    exit();
}

// Retrieve user ID from session
$user_id = $_SESSION['user_id'];

// Load customer connection
require("./conx/conx_customer.php");
$conn = $conx_customer;

// Fetch user information
$query = "SELECT user_fname, user_lname, user_email, user_connum, user_pass, user_pic, user_question FROM users WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Check if the user is found
if ($stmt->rowCount() === 0) {
    header("Location: customerPage.php");
    exit();
}

// Get user details
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header("Location: customerPage.php");
    exit();
}

// Extract user data
$res_Uname = $user['user_fname'];
$res_Lname = $user['user_lname'];
$res_Email = $user['user_email'];
$res_Connum = $user['user_connum'];
$current_password = $user['user_pass'];
$user_pic_blob = $user['user_pic'];
$hashed_user_question = $user['user_question'];

// Encode the blob data to base64 for displaying in HTML
$user_pic_base64 = base64_encode($user_pic_blob);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form input values
    $user_fname = sanitizeInput($_POST['user_fname']);
    $user_lname = sanitizeInput($_POST['user_lname']);
    $old_pass = sanitizeInput($_POST['old_pass']);
    $user_pass = sanitizeInput($_POST['user_pass']);
    $confirm_pass = sanitizeInput($_POST['confirm_pass']);
    $user_question = sanitizeInput($_POST['user_question']);

    // Initialize flag for changes made
    $changes_made = false;

    // Initialize an array to track the changes
    $actions = [];

    // Initialize an array to store error messages
    $errors = [];

    // Verify the user question
    if (!password_verify($user_question, $hashed_user_question)) {
        $errors[] = "The secret question answer is incorrect.";
    }

    // Proceed only if the user question is correct
    if (empty($errors)) {
        // Get the uploaded file information
        $file_tmp_name = $_FILES['user_pic']['tmp_name'];
        $file_size = $_FILES['user_pic']['size'];

        // Check if a file is uploaded
        if (!empty($file_tmp_name)) {
            if ($file_size > 1600000) {
                $errors[] = 'Image size is too large';
            } else {
                $image_data = file_get_contents($file_tmp_name); // Read image file as binary data
                $update_image = $conn->prepare("UPDATE `users` SET user_pic = ? WHERE user_id = ?");
                $update_image->bindParam(1, $image_data, PDO::PARAM_LOB); // Bind image data as a BLOB
                $update_image->bindParam(2, $user_id, PDO::PARAM_INT);
                if ($update_image->execute()) {
                    $actions[] = 'updated-pic';
                    $changes_made = true;
                } else {
                    $errors[] = 'Failed to update image';
                }
            }
        }

        // Check if first name has changed
        if ($user_fname !== $res_Uname) {
            $changes_made = true;
            $actions[] = 'updated - first name';
        }

        // Check if last name has changed
        if ($user_lname !== $res_Lname) {
            $changes_made = true;
            $actions[] = 'updated - last name';
        }

        // Check if password has changed
        if (!empty($user_pass)) {
            // Check if the old password is correct
            if (!password_verify($old_pass, $current_password)) {
                $errors[] = "The old password is incorrect.";
            } elseif ($user_pass !== $confirm_pass) {
                $errors[] = "The new password and confirm password do not match.";
            } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?=.*[a-zA-Z]).{8,}$/', $user_pass)) {
                $errors[] = "The password must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, one digit, and one special character.";
            } else {
                // Hash the new password
                $hashed_pass = password_hash($user_pass, PASSWORD_BCRYPT);
                $current_password = $hashed_pass;
                $changes_made = true;
                $actions[] = 'updated-pass';
            }
        } else {
            // Keep the current password if no new password is provided
            $hashed_pass = $current_password;
        }

        // If there are no errors and changes were made, update user profile
        if (empty($errors) && $changes_made) {
            $update_query = "UPDATE users SET user_fname = :user_fname, user_lname = :user_lname, user_pass = :user_pass, user_upddate = CURRENT_TIMESTAMP WHERE user_id = :user_id";
            $stmt = $conn->prepare($update_query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_fname', $user_fname, PDO::PARAM_STR);
            $stmt->bindParam(':user_lname', $user_lname, PDO::PARAM_STR);
            $stmt->bindParam(':user_pass', $hashed_pass, PDO::PARAM_STR);
            if ($stmt->execute()) {
                // Insert a new record into the audit_trail table
                $action = implode(', ', $actions);
                $insert_audit_query = "INSERT INTO audit_trail (user_id, action) VALUES (:user_id, :action)";
                $insert_audit_stmt = $conn->prepare($insert_audit_query);
                $insert_audit_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $insert_audit_stmt->bindParam(':action', $action, PDO::PARAM_STR);
                $insert_audit_stmt->execute();

                // Redirect to the customer profile page
                header("Location: profCustomer.php");
                exit();
            } else {
                $errors[] = 'Failed to update profile';
            }
        }

        // Display error messages
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "<div class='message'><p>$error</p></div>";
            }
        } else {
            // Display a message if no changes were made
            echo "<div class='message'><p>No changes were made to your profile.</p></div>";
        }
    } else {
        // Display error messages for user question verification
        foreach ($errors as $error) {
            echo "<div class='message'><p>$error</p></div>";
        }
    }
}

// Function to sanitize input
function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Update Profile</title>
</head>

<body>
    <div class="container">
        <div class="box form-box">
            <header>Update Profile</header>
            <img src="data:image/jpeg;base64,<?= $user_pic_base64; ?>" alt="Profile Image" class="circular-profile-pic">

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"
                enctype="multipart/form-data">
                <div class="field input">
                    <input type="text" name="user_fname" id="user_fname"
                        value="<?php echo htmlspecialchars($res_Uname); ?>" autocomplete="off"
                        placeholder="Enter your first name" required>
                </div>

                <div class="field input">
                    <input type="text" name="user_lname" id="user_lname"
                        value="<?php echo htmlspecialchars($res_Lname); ?>" autocomplete="off"
                        placeholder="Enter your last name" required>
                </div>

                <div class="field input">
                    <input type="email" name="user_email" id="user_email"
                        value="<?php echo htmlspecialchars($res_Email); ?>" autocomplete="off"
                        placeholder="Enter your email address" disabled required>
                </div>

                <div class="field input">
                    <input type="password" name="old_pass" id="old_pass" autocomplete="off"
                        placeholder="Enter current password">
                </div>

                <div class="field input">
                    <input type="password" name="user_pass" id="user_pass" autocomplete="off"
                        placeholder="Enter new password (leave blank to keep current)"
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?=.*[a-zA-Z]).{8,}"
                        title="Must contain at least one number, one uppercase letter, one lowercase letter, one special character, and at least 8 or more characters">
                </div>

                <div class="field input">
                    <input type="password" name="confirm_pass" id="confirm_pass" autocomplete="off"
                        placeholder="Confirm new password"
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?=.*[a-zA-Z]).{8,}"
                        title="Must match the new password">
                </div>

                <div class="field input">
                    <input type="password" name="user_question" id="user_question" autocomplete="off"
                        placeholder="Enter your SeedPhrase to proceed updating" required>
                </div>

                <div class="field input">
                    <input type="text" name="user_connum" id="user_connum"
                        value="<?php echo htmlspecialchars($res_Connum); ?>" autocomplete="off"
                        placeholder="Enter your contact number" disabled required>
                </div>

                <label for="user_pic">Choose Profile Picture</label>
                <input type="hidden" name="old_image" value="<?= base64_encode($user_pic_blob); ?>">
                <input type="file" name="user_pic" id="user_pic" accept="image/jpg, image/png, image/jpeg"
                    onchange="previewImage(event)">

                <div class="field input">
                    <img id="preview" src="#" alt="Preview" style="display: none; max-width: 200px; max-height: 200px;">
                </div>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Update Profile">
                    <a href="customerPage.php"><i class="fa-solid fa-chevron-left"></i> Back</a>
                </div>

                <!-- "Back to Profile" button always shown -->
                <script>
                function previewImage(event) {
                    const preview = document.getElementById('preview');
                    if (event.target.files.length > 0) {
                        const file = event.target.files[0];
                        const reader = new FileReader();
                        reader.onload = function() {
                            preview.src = reader.result;
                            preview.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.src = '#';
                        preview.style.display = 'none';
                    }
                }

                function sanitizeInput(input) {
                    return input.replace(/[<>]/g, '');
                }
                </script>
            </form>
        </div>
    </div>
</body>

</html>