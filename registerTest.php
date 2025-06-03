<?php

    require_once "php/config.php";
    
    $username = $password = $confirm_password = $email = $profile_type = "";
    $username_err = $password_err = $confirm_password_err = $email_err = $profile_type_err = "";
    
    if($_SERVER["REQUEST_METHOD"] == "POST"){
    
        // Validate username
        if(empty(trim($_POST["username"]))){
            $username_err = "Please enter a username.";
        } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
            $username_err = "Username can only contain letters, numbers, and underscores.";
        } else{
            // Prepare a select statement
            $sql = "SELECT id FROM users WHERE name = :username";
            
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                $param_username = trim($_POST["username"]);
                
                if($stmt->execute()){
                    if($stmt->rowCount() == 1){
                        $username_err = "This username is already taken.";
                    } else{
                        $username = trim($_POST["username"]);
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                unset($stmt);
            }
        }

        // Validate email
        if(empty(trim($_POST["email"]))){
            $email_err = "Please enter an email.";
        } else{
            if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)){
                $email_err = "Please enter a valid email address.";
            } else {
                $sql = "SELECT id FROM users WHERE email = :email";
                
                if($stmt = $pdo->prepare($sql)){
                    $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
                    $param_email = trim($_POST["email"]);
                    
                    if($stmt->execute()){
                        if($stmt->rowCount() == 1){
                            $email_err = "This email is already registered.";
                        } else{
                            $email = trim($_POST["email"]);
                        }
                    }
                    unset($stmt);
                }
            }
        }
        
        // Validate profile type
        if(empty(trim($_POST["profile_type"]))){
            $profile_type_err = "Please select a profile type.";
        } else {
            $profile_type = trim($_POST["profile_type"]);
        }
        
        // Validate password
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter a password.";     
        } elseif(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }
        
        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = "Please confirm password.";     
        } else{
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($password_err) && ($password != $confirm_password)){
                $confirm_password_err = "Password did not match.";
            }
        }

        if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($profile_type_err)){
        
            // Generate verification code
            $verification_code = bin2hex(random_bytes(32));
        
            $sql = "INSERT INTO users (name, email, password, profile_type, verification_code) VALUES (:username, :email, :password, :profile_type, :verification_code)";
        
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
                $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
                $stmt->bindParam(":profile_type", $param_profile_type, PDO::PARAM_STR);
                $stmt->bindParam(":verification_code", $verification_code, PDO::PARAM_STR);
            
                $param_username = $username;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_profile_type = $profile_type;
            
                if($stmt->execute()){
                    // Send verification email
                    $to = $email;
                    $subject = "Email Verification - EduSync";
                    $verification_link = "http://localhost/testbed03/website/verify.php?code=" . $verification_code . "&email=" . urlencode($email);
                
                    $message = "
                    <html>
                    <head>
                        <title>Email Verification</title>
                    </head>
                    <body>
                        <h2>Welcome to EduSync!</h2>
                        <p>Thank you for registering. Please click the link below to verify your email address:</p>
                        <p><a href='$verification_link'>Verify Email Address</a></p>
                        <p>If you didn't create an account, you can ignore this email.</p>
                        <br>
                        <p>Best regards,</p>
                        <p>EduSync Team</p>
                    </body>
                    </html>
                    ";

                    // Headers for HTML email
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= 'From: EduSync <noreply@edusync.com>' . "\r\n";
                
                    if(mail($to, $subject, $message, $headers)){
                        // Redirect to a success page
                        header("location: verificationSent.php");
                        exit();
                    } else {
                        $email_err = "Failed to send verification email. Please try again.";
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                unset($stmt);
            }
        }

        if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($profile_type_err)){
            
            $sql = "INSERT INTO users (name, email, password, profile_type) VALUES (:username, :email, :password, :profile_type)";
            
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
                $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
                $stmt->bindParam(":profile_type", $param_profile_type, PDO::PARAM_STR);
                
                $param_username = $username;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_profile_type = $profile_type;
                
                if($stmt->execute()){
                    header("location: loginTest.php");
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                unset($stmt);
            }
        }
        unset($pdo);
    }
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css">
</head>

<body>
    <div class="register-container">

        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>EduSync
            </div>
        </div>

        <h2>Create Account</h2>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="error-message"><?php echo $username_err; ?></span>
            </div>  

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <span class="error-message"><?php echo $email_err; ?></span>
            </div>

            <div class="form-group">
                <label>Profile Type</label>
                <select name="profile_type" class="form-control <?php echo (!empty($profile_type_err)) ? 'is-invalid' : ''; ?>">
                    <option value="" style="color: rgba(255, 255, 255, 0.7);">Select Profile Type</option>
                    <option value="student" <?php echo ($profile_type == "student") ? 'selected' : ''; ?>>Student</option>
                    <option value="instructor" <?php echo ($profile_type == "instructor") ? 'selected' : ''; ?>>Instructor</option>
                    <option value="admin" <?php echo ($profile_type == "admin") ? 'selected' : ''; ?>>Admin</option>
                </select>
                <span class="error-message"><?php echo $profile_type_err; ?></span>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                <div class="password-strength"></div>
                <span class="error-message"><?php echo $password_err; ?></span>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                <span class="error-message"><?php echo $confirm_password_err; ?></span>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-register">Create Account</button>
            </div>
            
            <p class="login-link">Already have an account? <a href="loginTest.php">Login here</a></p>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About EduSync</h3>
                <p>Empowering learners worldwide through innovative education and technology.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p><a href="#home">Home</a></p>
                <p><a href="#features">Features</a></p>
                <p><a href="#programs">Programs</a></p>
                <p><a href="#about">About</a></p>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-phone"></i> +1 234 567 890</p>
                <p><i class="fas fa-envelope"></i> info@edusync.com</p>
                <p><i class="fas fa-map-marker-alt"></i> 123 Education St, Learning City</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 EduSync. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select');
            const passwordInput = document.querySelector('input[name="password"]');
            const strengthIndicator = document.querySelector('.password-strength');


            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-5px)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                const patterns = [
                    /[a-z]+/, // lowercase
                    /[A-Z]+/, // uppercase
                    /[0-9]+/, // numbers
                    /[$@#&!]+/, // special characters
                    /.{8,}/ // minimum 8 characters
                ];

                patterns.forEach(pattern => {
                    if(pattern.test(password)) strength += 1;
                });

                const strengthColors = ['transparent', '#ff4444', '#ffbb33', '#00C851', '#007E33'];
                const strengthWidth = ['0%', '25%', '50%', '75%', '100%'];

                strengthIndicator.style.width = strengthWidth[strength];
                strengthIndicator.style.background = strengthColors[strength];
            });

            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                inputs.forEach(input => {
                    if(!input.value) {
                        input.parentElement.style.animation = 'shake 0.5s';
                        setTimeout(() => {
                            input.parentElement.style.animation = '';
                        }, 500);
                    }
                });
            });
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
        `;

        document.head.appendChild(style);
    </script>

</body>
</html>