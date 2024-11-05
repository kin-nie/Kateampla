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
            <header>Verify Your Account</header>
            <form action="verify_code.php" method="post">
                <div class="field input">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" name="verification_code" id="verification_code" placeholder="Enter your verification code" required>
                </div>
                <div class="field">
                    <input type="submit" class="btn btn-dark" name="verify" value="Verify">
                </div>
            </form>
        </div>
    </div>
</body>

</html>