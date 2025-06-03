<?php
    session_start();

    // Check if already logged in
    if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
        header("location: dashboard.php");
        exit;
    }

    require_once "php/config.php";

    function updateLastLogin($pdo, $user_id) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }

    // Initialize variables
    $email = $password = "";
    $email_err = $password_err = "";

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        // Validate email
        if(empty(trim($_POST["email"]))){
            $email_err = "Please enter email.";
        } else{
            $email = trim($_POST["email"]);
        }
        
        // Validate password
        if(empty(trim($_POST["password"]))){
            $password_err = "Please enter your password.";
        } else{
            $password = trim($_POST["password"]);
        }
        
        // Check input errors before processing
        if(empty($email_err) && empty($password_err)){
            $sql = "SELECT id, name, email, password, profile_type, profile_picture FROM users WHERE email = :email";
            
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
                $param_email = trim($_POST["email"]);
                
                if($stmt->execute()){
                    if($stmt->rowCount() == 1){
                        if($row = $stmt->fetch()){
                            $id = $row["id"];
                            $name = $row["name"];
                            $email = $row["email"];
                            $hashed_password = $row["password"];
                            $profile_type = $row["profile_type"];
                            $profile_picture = $row["profile_picture"];
                            
                            if(password_verify($password, $hashed_password)){

                                session_start();
                                
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["name"] = $name;
                                $_SESSION["email"] = $email;
                                $_SESSION["profile_type"] = $profile_type;
                                $_SESSION["profile_picture"] = $profile_picture;
                                
                                updateLastLogin($pdo, $id);
                                
                                switch($profile_type) {
                                    case 'admin':
                                        header("location: admin_WS.php");
                                        break;
                                    case 'instructor':
                                        header("location: instructor_WS.php");
                                        break;
                                    case 'student':
                                        header("location: student_WS.php");
                                        break;
                                    default:
                                        header("location: dashboard.php");
                                }
                                exit;
                            } else{
                                $password_err = "The password you entered was not valid.";
                            }
                        }
                    } else{
                        $email_err = "No account found with that email.";
                    }
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                unset($stmt);
            }
        }
        
        unset($pdo);
    }

    $logout_msg = "";
    if(isset($_GET["logout"]) && $_GET["logout"] == "success") {
        $logout_msg = "You have been successfully logged out.";
    }

    if(!empty($logout_msg)){
        echo '<div class="success-message">' . $logout_msg . '</div>';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

    <?php
        if(!empty($logout_msg)){
            echo '<div class="success-message-wrapper">
                    <div class="success-message">
                        <span>' . $logout_msg . '</span>
                    </div>
                    </div>';
        }   
    ?>

    <div class="login-container">

        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>EduSync
            </div>
        </div>

        <h2>Login</h2>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <i class="fas fa-envelope"></i>
                <?php if(!empty($email_err)): ?>
                    <span class="error-message"><?php echo $email_err; ?></span>
                <?php endif; ?>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password">
                <i class="fas fa-lock"></i>
                <?php if(!empty($password_err)): ?>
                    <span class="error-message"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-login" id="loginButton">Login</button>
            </div>
            <p class="signup-link">Don't have an account? <a href="registerTest.php">Sign up now</a></p>
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
                <p><a href="edusync.php">Home</a></p>
                <p><a href="edusync.php">Features</a></p>
                <p><a href="edusync.php">Programs</a></p>
                <p><a href="about.php">About</a></p>
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

    <script src="js/sc1.js"> </script>

</body>
</html>