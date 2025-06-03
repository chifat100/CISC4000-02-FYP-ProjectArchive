<?php
    require_once "php/config.php";

    if(isset($_GET['code']) && isset($_GET['email'])) {
        $verification_code = $_GET['code'];
        $email = $_GET['email'];

        $sql = "SELECT * FROM users WHERE email = :email AND verification_code = :code AND verified = 0";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":code", $verification_code, PDO::PARAM_STR);
            
            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    // Update user as verified
                    $update_sql = "UPDATE users SET verified = 1 WHERE email = :email";
                    if($update_stmt = $pdo->prepare($update_sql)) {
                        $update_stmt->bindParam(":email", $email, PDO::PARAM_STR);
                        if($update_stmt->execute()) {
                            $success_msg = "Your email has been verified successfully. You can now login.";
                        } else {
                            $error_msg = "Something went wrong. Please try again later.";
                        }
                    }
                } else {
                    $error_msg = "Invalid verification code or email already verified.";
                }
            } else {
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
        unset($pdo);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - EduSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/verify.css">
</head>
<body>
    <div class="verify-container">
        <?php if(isset($success_msg)): ?>
            <div class="message success">
                <?php echo $success_msg; ?>
            </div>
            <a href="loginTest.php" class="btn-login">Proceed to Login</a>
        <?php elseif(isset($error_msg)): ?>
            <div class="message error">
                <?php echo $error_msg; ?>
            </div>
            <a href="registerTest.php" class="btn-login">Back to Register</a>
        <?php endif; ?>
    </div>
</body>
</html>