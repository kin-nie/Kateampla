<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="style/style.css">
    <title>Register</title>
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
            <?php
            require("./conx/conx_customer.php");
            require 'PHPMailer/src/Exception.php';
            require 'PHPMailer/src/PHPMailer.php';
            require 'PHPMailer/src/SMTP.php';

            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;

            if (isset($_POST['submit'])) {
                $user_fname = filter_var($_POST['user_fname'], FILTER_SANITIZE_STRING);
                $user_lname = filter_var($_POST['user_lname'], FILTER_SANITIZE_STRING);
                $user_age = (int)$_POST['user_age'];
                $user_email = filter_var($_POST['user_email'], FILTER_SANITIZE_EMAIL);
                $user_pass = $_POST['user_pass'];
                $user_pass_confirm = $_POST['user_pass_confirm'];
                $user_connum = $_POST['user_connum'];
                $user_question = $_POST['user_question'];

                if (strpos($user_question, ' ') !== false) {
                    echo "<div class='message'><p>The unique word must not contain spaces.</p></div>";
                    echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                    exit;
                }

                $hashed_question = password_hash($user_question, PASSWORD_BCRYPT);

                if ($user_pass !== $user_pass_confirm) {
                    echo "<div class='message'><p>Passwords do not match. Please try again.</p></div>";
                    echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                    exit;
                }

                if ($user_age < 18) {
                    echo "<div class='message'><p>You must be at least 18 years old to register.</p></div>";
                    echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                } else {
                    $stmt_email = $conx_customer->prepare("SELECT * FROM users WHERE user_email = :email");
                    $stmt_email->bindParam(':email', $user_email);
                    $stmt_email->execute();

                    if ($stmt_email->rowCount() > 0) {
                        echo "<div class='message'><p>Email is already in use. Please try another one.</p></div>";
                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                    } else {
                        $stmt_connum = $conx_customer->prepare("SELECT * FROM users WHERE user_connum = :connum");
                        $stmt_connum->bindParam(':connum', $user_connum);
                        $stmt_connum->execute();

                        if ($stmt_connum->rowCount() > 0) {
                            echo "<div class='message'><p>Contact number is already in use. Please try another one.</p></div>";
                            echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                        } else {
                            $hashed_password = password_hash($user_pass, PASSWORD_BCRYPT);
                            $user_type = 2;
                            $current_datetime = date("Y-m-d H:i:s");

                            if (isset($_FILES['user_pic']) && $_FILES['user_pic']['error'] === UPLOAD_ERR_OK) {
                                $file_tmp_name = $_FILES['user_pic']['tmp_name'];
                                $file_size = $_FILES['user_pic']['size'];
                                $max_allowed_size = 16 * 1024 * 1024;

                                if ($file_size > $max_allowed_size) {
                                    echo "<div class='message'><p>Image size exceeds the maximum allowed size (16MB).</p></div>";
                                    echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                                    exit;
                                }

                                $user_pic_data = file_get_contents($file_tmp_name);

                                $verification_code = rand(100000, 999999);  // Generate a 6-digit verification code
                                $insert_stmt = $conx_customer->prepare("INSERT INTO users (user_fname, user_lname, user_age, user_email, user_pass, user_connum, user_question, user_type, user_regdate, user_pic, user_status, user_verification) VALUES (:fname, :lname, :age, :email, :pass, :connum, :question, :type, :regdate, :user_pic, 'inactivated', :verification_code)");
                                $insert_stmt->bindParam(':fname', $user_fname);
                                $insert_stmt->bindParam(':lname', $user_lname);
                                $insert_stmt->bindParam(':age', $user_age);
                                $insert_stmt->bindParam(':email', $user_email);
                                $insert_stmt->bindParam(':pass', $hashed_password);
                                $insert_stmt->bindParam(':connum', $user_connum);
                                $insert_stmt->bindParam(':question', $hashed_question);
                                $insert_stmt->bindParam(':type', $user_type);
                                $insert_stmt->bindParam(':regdate', $current_datetime);
                                $insert_stmt->bindParam(':user_pic', $user_pic_data, PDO::PARAM_LOB);
                                $insert_stmt->bindParam(':verification_code', $verification_code);

                                try {
                                    if ($insert_stmt->execute()) {
                                        $mail = new PHPMailer(true);
                                        try {
                                            $mail->isSMTP();
                                            $mail->Host = 'smtp.gmail.com';  // Replace with your SMTP server
                                            $mail->SMTPAuth = true;
                                            $mail->Username = 'jolocatubag323@gmail.com';  // Replace with your SMTP username
                                            $mail->Password = 'mkzs jtsf zsqy kcxh';  // Replace with your SMTP password
                                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                            $mail->Port = 587;

                                            $mail->setFrom('no-reply@example.com', 'Kateampla Karaoke');
                                            $mail->addAddress($user_email, $user_fname . ' ' . $user_lname);

                                            $mail->isHTML(true);
                                            $mail->Subject = 'Email Verification';
                                            $mail->Body    = "Your verification code is: $verification_code";

                                            $mail->send();
                                            echo "<div class='message'><p>Registration successful! A verification email has been sent to your email address.</p></div>";
                                            echo "<a href='verify.php'><button class='btn'>Verify Now</button></a>";
                                        } catch (Exception $e) {
                                            echo "<div class='message'><p>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p></div>";
                                            echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                                        }
                                    } else {
                                        echo "<div class='message'><p>An error occurred during registration. Please try again.</p></div>";
                                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<div class='message'><p>Error: " . $e->getMessage() . "</p></div>";
                                    echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                                }
                            } else {
                                echo "<div class='message'><p>No image selected or an error occurred while uploading the image.</p></div>";
                                echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                            }
                        }
                    }
                }
            } else {
            ?>
                <!-- Display the registration form -->
                <header>Sign Up</header>
                <form id="registerForm" action="" method="post" enctype="multipart/form-data">
                    <div class="field input">
                        <label for="user_fname">First Name</label>
                        <input type="text" name="user_fname" id="user_fname"
                            pattern="^[A-Za-z.,/]+(?: [A-Za-z.,/]+)*[A-Za-z.,/]+$" placeholder="Juan Dela"
                            title="Alphabets, spaces, and .,/ only. Should not end with a space." required>
                    </div>

                    <div class="field input">
                        <label for="user_lname">Last Name</label>
                        <input type="text" name="user_lname" id="user_lname"
                            pattern="^[A-Za-z.,/]+(?: [A-Za-z.,/]+)*[A-Za-z.,/]+$" placeholder="Cruz Jr."
                            title="Alphabets, spaces, and .,/ only. Should not end with a space." required>
                    </div>

                    <div class="field input">
                        <label for="user_age">Age</label>
                        <input type="number" name="user_age" id="user_age" min="18" placeholder="Legal Age (18 Above)"
                            required>
                    </div>

                    <div class="field input">
                        <label for="user_email">Email (must be unique)</label>
                        <input type="email" name="user_email" id="user_email" placeholder="Juan@gmail.com" required>
                    </div>

                    <div class="field input">
                        <label for="user_pass">Password</label>
                        <div class="input-group">
                            <input type="password" name="user_pass" id="user_pass" class="form-control"
                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?=.*[a-zA-Z]).{8,}"
                                title="Must contain at least one number, one uppercase letter, one lowercase letter, one special character, and at least 8 or more characters"
                                placeholder="Secret#1" required>
                            <button type="button" class="btn btn-outline-secondary" id="togglePassword"><i
                                    class="bi bi-eye-slash"></i> <span>Show</span></button>
                        </div>
                    </div>

                    <div class="field input">
                        <label for="user_pass_confirm">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="user_pass_confirm" id="user_pass_confirm" class="form-control"
                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?=.*[a-zA-Z]).{8,}"
                                title="Must contain at least one number, one uppercase letter, one lowercase letter, one special character, and at least 8 or more characters"
                                placeholder="Confirm your password" required>
                            <button type="button" class="btn btn-outline-secondary" id="togglePasswordConfirm"><i
                                    class="bi bi-eye-slash"></i> <span>Show</span></button>
                        </div>
                    </div>

                    <div class="field input">
                        <label for="user_connum">Contact Number (must be unique)</label>
                        <input type="text" name="user_connum" id="user_connum" pattern="09[0-9]{9}"
                            title="Must be a 9-digit number starting with 09" placeholder="09*********" required>
                    </div>

                    <label for="user_pic">Choose Profile Picture</label>
                    <input type="file" name="user_pic" id="user_pic" required accept="image/jpg, image/png, image/jpeg"
                        onchange="previewImage(event)">

                    <div class="field input">
                        <img id="preview" src="#" alt="Preview" style="display: none; max-width: 200px; max-height: 200px;">
                    </div>

                    <!-- Hidden input to store the unique word -->
                    <input type="hidden" name="user_question" id="user_question">

                    <div class="field">
                        <input type="submit" class="btn btn-dark" name="submit" value="Register">
                    </div>

                    <div class="links">
                        Already a member? <a href="login.php">Sign In</a>
                    </div>
                </form>

            <?php } ?>

            <script>
                document.getElementById('registerForm').addEventListener('submit', function(event) {
                    // Prompt the user for a unique word
                    let userWord = null;
                    while (userWord === null || !/^[^\s]+$/.test(userWord)) {
                        userWord = prompt(
                            "SEEDPHRASE: Enter a single word (max 50 characters). If you forget your password, this will be needed to reset it. Failure to input it properly will result in account lock."
                        );
                        if (userWord === null) {
                            // If the user clicks cancel, stop form submission
                            event.preventDefault();
                            alert("You must answer the prompt to register.");
                            return;
                        } else if (!/^[^\s]+$/.test(userWord)) {
                            // If the word is invalid, show an alert
                            alert("The unique word must be a single word without spaces.");
                        }
                    }
                    // Set the value of the hidden input to the entered word
                    document.getElementById('user_question').value = userWord;
                });

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

                function togglePasswordVisibility(inputId, buttonId) {
                    const passwordInput = document.getElementById(inputId);
                    const toggleButton = document.getElementById(buttonId);
                    toggleButton.addEventListener('click', function() {
                        const type = passwordInput.type === 'password' ? 'text' : 'password';
                        passwordInput.type = type;
                        this.innerHTML = type === 'password' ? '<i class="bi bi-eye-slash"></i> <span>Show</span>' :
                            '<i class="bi bi-eye"></i> <span>Hide</span>';
                    });
                }

                togglePasswordVisibility('user_pass', 'togglePassword');
                togglePasswordVisibility('user_pass_confirm', 'togglePasswordConfirm');
            </script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
                integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
                crossorigin="anonymous">
            </script>
        </div>
    </div>
</body>

</html>