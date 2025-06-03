<?php
    session_start();

    // Check if user is logged in
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: loginTest.php");
        exit;
    }

    require_once "php/config.php";

    $new_name = $new_email = $current_password = $new_password = $confirm_password = "";
    $new_name_err = $new_email_err = $current_password_err = $new_password_err = $confirm_password_err = "";
    $success_msg = $error_msg = "";

    // Processing form data when form is submitted
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        
        if(isset($_POST["update_profile"])){
            // Validate new name
            if(empty(trim($_POST["new_name"]))){
                $new_name_err = "Please enter your new name.";
            } elseif(!preg_match('/^[a-zA-Z0-9_\s]+$/', trim($_POST["new_name"]))){
                $new_name_err = "Name can only contain letters, numbers, spaces and underscores.";
            } else{
                $new_name = trim($_POST["new_name"]);
            }

            // Validate new email
            if(empty(trim($_POST["new_email"]))){
                $new_email_err = "Please enter your new email.";
            } else{
                // Check if email is valid
                if(!filter_var($_POST["new_email"], FILTER_VALIDATE_EMAIL)){
                    $new_email_err = "Please enter a valid email address.";
                } else {
                    // Check if email is already taken
                    $sql = "SELECT id FROM users WHERE email = :email AND id != :id";
                    if($stmt = $pdo->prepare($sql)){
                        $stmt->bindParam(":email", $_POST["new_email"], PDO::PARAM_STR);
                        $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
                        if($stmt->execute()){
                            if($stmt->rowCount() > 0){
                                $new_email_err = "This email is already taken.";
                            } else{
                                $new_email = trim($_POST["new_email"]);
                            }
                        }
                    }
                    unset($stmt);
                }
            }

            // Check if there are no errors before updating
            if(empty($new_name_err) && empty($new_email_err)){
                $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
                if($stmt = $pdo->prepare($sql)){
                    $stmt->bindParam(":name", $new_name, PDO::PARAM_STR);
                    $stmt->bindParam(":email", $new_email, PDO::PARAM_STR);
                    $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
                    if($stmt->execute()){
                        // Update session variables
                        $_SESSION["name"] = $new_name;
                        $_SESSION["email"] = $new_email;
                        $success_msg = "Profile updated successfully!";
                    } else{
                        $error_msg = "Something went wrong. Please try again later.";
                    }
                }
                unset($stmt);
            }
        }
        
        // Process password update
        if(isset($_POST["update_password"])){
            // Validate current password
            if(empty(trim($_POST["current_password"]))){
                $current_password_err = "Please enter your current password.";
            } else{
                // Verify current password
                $sql = "SELECT password FROM users WHERE id = :id";
                if($stmt = $pdo->prepare($sql)){
                    $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
                    if($stmt->execute()){
                        if($row = $stmt->fetch()){
                            if(!password_verify($_POST["current_password"], $row["password"])){
                                $current_password_err = "Current password is incorrect.";
                            }
                        }
                    }
                }
            }

            // Validate new password
            if(empty(trim($_POST["new_password"]))){
                $new_password_err = "Please enter the new password.";     
            } else{
                    $temp_password = trim($_POST["new_password"]);
                    if(strlen($temp_password) < 6){
                        $new_password_err = "Password must have at least 6 characters.";
                    } else{
                        $new_password = $temp_password;
                    }
            }

            // Validate confirm password
            if(empty(trim($_POST["confirm_password"]))){
                $confirm_password_err = "Please confirm the password.";
            } else{
                    $temp_confirm = trim($_POST["confirm_password"]);
                    $confirm_password = $temp_confirm;
                    if(empty($new_password_err) && ($new_password != $confirm_password)){
                        $confirm_password_err = "Password did not match.";
                    }
            }
            
            // Check if there are no errors before updating the password
            if(empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)){
                $sql = "UPDATE users SET password = :password WHERE id = :id";
                if($stmt = $pdo->prepare($sql)){
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
                    $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
                    if($stmt->execute()){
                        $success_msg = "Password updated successfully!";
                    } else{
                        $error_msg = "Something went wrong. Please try again later.";
                    }
                }
                unset($stmt);
            }
        }

        // Process profile picture update
        if(isset($_FILES["profile_picture"])){
            $target_dir = "uploads/profile_pictures/";
            if(!file_exists($target_dir)){
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
            $new_file_name = $_SESSION["id"] . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_file_name;
            
            $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
            if($check !== false) {
                if($file_extension == "jpg" || $file_extension == "png" || $file_extension == "jpeg" || $file_extension == "gif") {
                    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                        $sql = "UPDATE users SET profile_picture = :picture WHERE id = :id";
                        if($stmt = $pdo->prepare($sql)){
                            $stmt->bindParam(":picture", $new_file_name, PDO::PARAM_STR);
                            $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
                            if($stmt->execute()){
                                $success_msg = "Profile picture updated successfully!";
                            } else {
                                $error_msg = "Error updating database with new profile picture.";
                            }
                        }
                    } else {
                        $error_msg = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $error_msg = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                $error_msg = "File is not an image.";
            }
        }
    }

    // Get current user data
    $sql = "SELECT * FROM users WHERE id = :id";
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - EduSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(45deg, #3498db, #2ecc71);
            padding: 40px 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .update-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 30px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        h2 {
            color: #fff;
            margin-bottom: 30px;
            font-size: 1.8em;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 8px;
            color: #2ecc71;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            background: #2ecc71;
            color: #fff;
            transform: translateY(-2px);
        }

        .error-message {
            color: #ff4444;
            font-size: 0.85em;
            margin-top: 5px;
            background: rgba(255, 68, 68, 0.1);
            padding: 8px;
            border-radius: 4px;
        }

        .success-message {
            color: #00C851;
            font-size: 0.85em;
            margin-top: 5px;
            background: rgba(0, 200, 81, 0.1);
            padding: 8px;
            border-radius: 4px;
            text-align: center;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .update-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="student_WS.php"><i class="fas fa-laptop-code"></i> Workspace</a>
            <a href="courses.php"><i class="fas fa-book"></i> Courses</a>
            <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
            <a href="forum.php"><i class="fas fa-envelope"></i> discussion</a>
        </div>

        <?php if(!empty($success_msg)): ?>
            <div class="success-message"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="error-message"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="update-container">
            <div class="profile-header">
                <div class="profile-picture">
                    <img src="<?php echo !empty($user_data['profile_picture']) ? 'uploads/profile_pictures/'.$user_data['profile_picture'] : 'https://via.placeholder.com/150'; ?>" alt="Profile Picture">
                </div>
                <h2>Update Profile</h2>
            </div>

            <!-- Profile Picture Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Update Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn">Update Picture</button>
            </form>

            <!-- Profile Information Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2>Personal Information</h2>
                <div class="form-group">
                    <label>Update Name</label>
                    <input type="text" name="new_name" class="form-control" value="<?php echo $user_data['name']; ?>">
                    <span class="error-message"><?php echo $new_name_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Update Email</label>
                    <input type="email" name="new_email" class="form-control" value="<?php echo $user_data['email']; ?>">
                    <span class="error-message"><?php echo $new_email_err; ?></span>
                </div>

                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </form>

            <!-- Password Update Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2>Change Password</h2>
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                    <span class="error-message"><?php echo $current_password_err; ?></span>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control">
                    <span class="error-message"><?php echo $new_password_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                    <span class="error-message"><?php echo $confirm_password_err; ?></span>
                </div>

                <button type="submit" name="update_password" class="btn">Update Password</button>
            </form>
        </div>
    </div>

    <footer style="text-align: center; color: #fff; padding: 20px;">
        &copy; copyright @ 2025 by <span>EduSync</span> 
    </footer>
</body>
</html>