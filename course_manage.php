<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: loginTest.php");
        exit;
    }

    // Allow both instructors and admins to access this page
    $is_instructor = ($_SESSION["profile_type"] === "instructor");
    $is_admin = ($_SESSION["profile_type"] === "admin");
    
    if(!$is_instructor && !$is_admin) {
        header("location: loginTest.php");
        exit;
    }

    require_once "php/config.php";

    // Check if course ID is provided
    if(!isset($_GET['id'])) {
        header("location: " . ($is_admin ? "manage_courses.php" : "instructor_WS.php"));
        exit;
    }

    $course_id = $_GET['id'];

    // Fetch user data including profile picture
    $sql = "SELECT * FROM users WHERE id = :id";
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
        if($stmt->execute()){
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!isset($_SESSION['profile_picture']) && !empty($user_data['profile_picture'])){
                $_SESSION['profile_picture'] = $user_data['profile_picture'];
            }
        }
    }

    // Verify course exists and permissions
    if($is_instructor) {
        // For instructors, verify they own the course
        $sql = "SELECT * FROM courses WHERE id = :course_id AND instructor_id = :instructor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
        $stmt->bindParam(":instructor_id", $_SESSION["id"], PDO::PARAM_INT);
    } else {
        // For admins, just verify course exists
        $sql = "SELECT c.*, u.name as instructor_name 
                FROM courses c 
                JOIN users u ON c.instructor_id = u.id 
                WHERE c.id = :course_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();

    if($stmt->rowCount() == 0) {
        header("location: " . ($is_admin ? "manage_courses.php" : "instructor_WS.php"));
        exit;
    }

    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle content creation
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST["add_content"])) {
            $title = trim($_POST["title"]);
            $content_type = $_POST["content_type"];
            $content = trim($_POST["content"]);
            
            // Handle file upload if exists
            $file_path = null;
            if(isset($_FILES["content_file"]) && $_FILES["content_file"]["error"] == 0) {
                $target_dir = "uploads/course_content/";
                if(!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES["content_file"]["name"], PATHINFO_EXTENSION));
                $new_file_name = uniqid() . "." . $file_extension;
                $target_file = $target_dir . $new_file_name;
                
                if(move_uploaded_file($_FILES["content_file"]["tmp_name"], $target_file)) {
                    $file_path = $target_file;
                }
            }
            
            $sql = "INSERT INTO course_content (course_id, title, content_type, content, file_path, created_by) 
                    VALUES (:course_id, :title, :content_type, :content, :file_path, :created_by)";
            
            if($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
                $stmt->bindParam(":title", $title, PDO::PARAM_STR);
                $stmt->bindParam(":content_type", $content_type, PDO::PARAM_STR);
                $stmt->bindParam(":content", $content, PDO::PARAM_STR);
                $stmt->bindParam(":file_path", $file_path, PDO::PARAM_STR);
                $stmt->bindParam(":created_by", $_SESSION['id'], PDO::PARAM_INT);
                
                if($stmt->execute()) {
                    $success_msg = "Content added successfully!";
                } else {
                    $error_msg = "Something went wrong. Please try again.";
                }
            }
        }
        
    // Handle content editing
    if(isset($_POST["edit_content"])) {
        $content_id = $_POST["content_id"];
        $title = trim($_POST["title"]);
        $content_type = $_POST["content_type"];
        $content = trim($_POST["content"]);
        
        // Get current file path
        $sql = "SELECT file_path FROM course_content WHERE id = :content_id AND course_id = :course_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":content_id", $content_id, PDO::PARAM_INT);
        $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $current_content = $stmt->fetch(PDO::FETCH_ASSOC);
        $file_path = $current_content['file_path'];
        
        // Handle file upload if exists
        if(isset($_FILES["content_file"]) && $_FILES["content_file"]["error"] == 0) {
            $target_dir = "uploads/course_content/";
            if(!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["content_file"]["name"], PATHINFO_EXTENSION));
            $new_file_name = uniqid() . "." . $file_extension;
            $target_file = $target_dir . $new_file_name;
            
            if(move_uploaded_file($_FILES["content_file"]["tmp_name"], $target_file)) {
                // Delete old file if exists
                if(!empty($file_path) && file_exists($file_path)) {
                    unlink($file_path);
                }
                $file_path = $target_file;
            }
        }
        
        $sql = "UPDATE course_content 
                SET title = :title, 
                    content_type = :content_type, 
                    content = :content, 
                    file_path = :file_path 
                WHERE id = :content_id AND course_id = :course_id";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":title", $title, PDO::PARAM_STR);
            $stmt->bindParam(":content_type", $content_type, PDO::PARAM_STR);
            $stmt->bindParam(":content", $content, PDO::PARAM_STR);
            $stmt->bindParam(":file_path", $file_path, PDO::PARAM_STR);
            $stmt->bindParam(":content_id", $content_id, PDO::PARAM_INT);
            $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $success_msg = "Content updated successfully!";
            } else {
                $error_msg = "Something went wrong. Please try again.";
            }
        }
    }



    // Handle content deletion
    if(isset($_POST["delete_content"])) {
            $content_id = $_POST["content_id"];
            
            // Get file path before deleting
            $sql = "SELECT file_path FROM course_content WHERE id = :content_id AND course_id = :course_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":content_id", $content_id, PDO::PARAM_INT);
            $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $content_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete content
            $sql = "DELETE FROM course_content WHERE id = :content_id AND course_id = :course_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":content_id", $content_id, PDO::PARAM_INT);
            $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                // Delete file if exists
                if(!empty($content_data['file_path']) && file_exists($content_data['file_path'])) {
                    unlink($content_data['file_path']);
                }
                $success_msg = "Content deleted successfully!";
            } else {
                $error_msg = "Error deleting content. Please try again.";
            }
        }
    }

    // Get course content
    $sql = "SELECT cc.*, u.name as creator_name 
            FROM course_content cc
            JOIN users u ON cc.created_by = u.id
            WHERE cc.course_id = :course_id 
            ORDER BY cc.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get enrolled students count
    $sql = "SELECT COUNT(*) as student_count FROM enrollments WHERE course_id = :course_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $enrollment_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_count = $enrollment_data['student_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course - <?php echo htmlspecialchars($course['course_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <style>
        .course-manage-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .section-title {
            font-size: 2.5rem;
            color: var(--black);
            margin-bottom: 2.5rem;
            border-bottom: var(--border);
            padding-bottom: 1.5rem;
            text-transform: capitalize;
        }

        .course-info {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .course-info h2 {
            color: var(--black);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .course-info p {
            color: var(--light-color);
            font-size: 1.6rem;
            line-height: 1.6;
        }

        .content-form-section {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--black);
            font-size: 1.6rem;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.6rem;
            background: var(--light-bg);
            color: var(--black);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .content-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .content-item {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .content-item:hover {
            transform: translateY(-5px);
        }

        .content-item h3 {
            color: var(--black);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .content-item p {
            color: var(--light-color);
            font-size: 1.4rem;
            margin-bottom: 8px;
        }

        .content-type-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .content-type-lesson {
            background: var(--main-color);
            color: var(--white);
        }

        .content-type-exercise {
            background: var(--orange);
            color: var(--white);
        }

        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--main-color);
            font-size: 1.4rem;
            text-decoration: none;
            margin-top: 10px;
        }

        .attachment-link:hover {
            text-decoration: underline;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.6rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.6rem;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-button {
            display: inline-block;
            padding: 10px 20px;
            background: var(--light-bg);
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            color: var(--black);
        }

        .selected-file-name {
            margin-left: 10px;
            font-size: 1.4rem;
            color: var(--light-color);
        }

        <style>
        .course-manage-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--main-color);
            font-size: 1.6rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--black);
            transform: translateX(-5px);
        }

        .section-title {
            font-size: 2.2rem;
            color: var(--black);
            margin-bottom: 20px;
            border-bottom: var(--border);
            padding-bottom: 10px;
        }

        .course-info {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .course-info-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .course-stats {
            display: flex;
            gap: 15px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--light-bg);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 1.4rem;
            color: var(--black);
        }

        .instructor-info {
            color: var(--light-color);
            font-size: 1.5rem;
            margin: 5px 0 10px;
        }

        .course-description {
            color: var(--light-color);
            font-size: 1.6rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .course-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--light-color);
            font-size: 1.4rem;
        }

        .content-form-section {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--black);
            font-size: 1.5rem;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.5rem;
            background: var(--light-bg);
            color: var(--black);
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--main-color);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .content-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .content-item {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .content-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .content-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.4rem;
            color: var(--light-color);
            transition: all 0.3s ease;
            padding: 5px;
        }

        .edit-btn:hover {
            color: var(--main-color);
        }

        .delete-btn:hover {
            color: var(--red);
        }

        .content-item h3 {
            color: var(--black);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .content-item p {
            color: var(--light-color);
            font-size: 1.4rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .content-type-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .content-type-lesson {
            background: var(--main-color);
            color: var(--white);
        }

        .content-type-exercise {
            background: var(--orange);
            color: var(--white);
        }

        .content-type-assignment {
            background: #6c5ce7;
            color: var(--white);
        }

        .content-type-quiz {
            background: #e17055;
            color: var(--white);
        }

        .content-type-resource {
            background: #00b894;
            color: var(--white);
        }

        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--main-color);
            font-size: 1.4rem;
            text-decoration: none;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .attachment-link:hover {
            color: var(--black);
            text-decoration: underline;
        }

        .content-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            color: var(--light-color);
            font-size: 1.3rem;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-input-button {
            display: inline-block;
            padding: 10px 20px;
            background: var(--light-bg);
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            color: var(--black);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .file-input-button:hover {
            background: var(--border);
        }

        .selected-file-name {
            margin-left: 10px;
            font-size: 1.4rem;
            color: var(--light-color);
        }

        .btn {
            background: var(--main-color);
            color: var(--white);
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: var(--black);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--light-color);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 2rem;
            color: var(--black);
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 1.4rem;
            color: var(--light-color);
            margin-bottom: 20px;
        }

        /* Delete confirmation modal */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .confirm-content {
            background: var(--white);
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .confirm-content h3 {
            margin-bottom: 20px;
            color: var(--black);
            font-size: 1.8rem;
        }

        .confirm-content p {
            color: var(--light-color);
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .content-title-display {
            font-weight: bold;
            color: var(--black);
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .confirm-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .confirm-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .confirm-btn-cancel {
            background: var(--light-bg);
            color: var(--black);
        }

        .confirm-btn-delete {
            background: var(--red);
            color: var(--white);
        }

        .confirm-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }


        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h2 {
            font-size: 2rem;
            color: var(--black);
        }

        .close-btn {
            font-size: 2.4rem;
            cursor: pointer;
            color: var(--light-color);
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: var(--red);
        }

        .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .btn-danger {
            background: var(--red);
            color: var(--white);
        }

        .btn-primary {
            background: var(--main-color);
            color: var(--white);
        }

        .note {
            font-size: 1.2rem;
            color: var(--light-color);
            margin-top: 5px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .content-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.4rem;
            color: var(--light-color);
            transition: all 0.3s ease;
            padding: 5px;
        }

        .edit-btn:hover {
            color: var(--main-color);
        }

        .delete-btn:hover {
            color: var(--red);
        }

        .content-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            color: var(--light-color);
            font-size: 1.3rem;
        }
    </style>
</head>
<body>

    <header class="header">
        <section class="flex">

            <a href="dashboard.php" class="logo">EduSync</a>

            <form action="search.html" method="post" class="search-form">
                <input type="text" name="search_box" required placeholder="search courses..." maxlength="100">
                <button type="submit" class="fas fa-search"></button>
            </form>

            <div class="icons">
                <div id="menu-btn" class="fas fa-bars"></div>
                <div id="search-btn" class="fas fa-search"></div>
                <div id="user-btn" class="fas fa-user"></div>
                <div id="toggle-btn" class="fas fa-sun"></div>
            </div>

            <div class="profile">
                <img src="<?php 
                    if(!empty($user_data['profile_picture']) && file_exists('uploads/profile_pictures/'.$user_data['profile_picture'])){
                    echo 'uploads/profile_pictures/'.$user_data['profile_picture'];
                    } else {
                    echo 'images/pic-1.jpg'; 
                    }
                ?>" alt="Profile Picture">
                <h3 class="name"><?php echo htmlspecialchars($_SESSION["name"]); ?></h3>
                <p class="role"><?php echo htmlspecialchars($_SESSION["profile_type"]); ?></p>
                <a href="profile.php" class="btn">view profile</a>
                <div class="flex-btn">
                    <a href="logout.php" class="option-btn">Logout</a>
                </div>
            </div>
        </section>
    </header>   

    <div class="side-bar">
        <div id="close-btn">
            <i class="fas fa-times"></i>
        </div>
        <div class="profile">
            <img src="<?php 
                    if(!empty($user_data['profile_picture']) && file_exists('uploads/profile_pictures/'.$user_data['profile_picture'])){
                    echo 'uploads/profile_pictures/'.$user_data['profile_picture'];
                    } else {
                    echo 'images/pic-1.jpg';
                    }
                ?>" alt="Profile Picture">
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <span><?php echo ucfirst($_SESSION['profile_type']); ?></span>
        </div>
        <nav class="navbar">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="instructor_WS.php"><i class="fas fa-chalkboard-teacher"></i><span>Workspace</span></a>
            <a href="courses.php"><i class="fas fa-graduation-cap"></i><span>Courses</span></a>
            <a href="update.php"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="course-manage-container">
            <?php if(isset($success_msg)): ?>
                <div class="success-message"><?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if(isset($error_msg)): ?>
                <div class="error-message"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="course-info">
                <h2><?php echo htmlspecialchars($course['course_name']); ?> (<?php echo htmlspecialchars($course['course_code']); ?>)</h2>
                <p><?php echo htmlspecialchars($course['description']); ?></p>
            </div>

            <div class="content-form-section">
                <h2 class="section-title">Add New Content</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Content Type</label>
                        <select name="content_type" class="form-control" required>
                            <option value="lesson">Lesson</option>
                            <option value="exercise">Exercise</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Attachment (optional)</label>
                        <div class="file-input-wrapper">
                            <button type="button" class="file-input-button">
                                <i class="fas fa-upload"></i> Choose File
                            </button>
                            <input type="file" name="content_file" id="content_file">
                        </div>
                        <span class="selected-file-name" id="file-name"></span>
                    </div>
                    
                    <button type="submit" name="add_content" class="btn">
                        <i class="fas fa-plus"></i> Add Content
                    </button>
                </form>
            </div>

            <section class="course-section">
                <h2 class="section-title">Course Content</h2>
                <div class="content-list">
                    <?php foreach($contents as $content): ?>
                        <div class="content-item">
                            <div class="content-header">
                                <span class="content-type-badge content-type-<?php echo $content['content_type']; ?>">
                                    <?php echo ucfirst($content['content_type']); ?>
                                </span>
                                <div class="content-actions">
                                    <button class="action-btn edit-btn" title="Edit content" 
                                            onclick="editContent(<?php echo $content['id']; ?>, '<?php echo addslashes($content['title']); ?>', '<?php echo $content['content_type']; ?>', '<?php echo addslashes($content['content']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="Delete content"
                                            onclick="confirmDelete(<?php echo $content['id']; ?>, '<?php echo addslashes($content['title']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars(substr($content['content'], 0, 150)) . (strlen($content['content']) > 150 ? '...' : '')); ?></p>
                            <?php if($content['file_path']): ?>
                                <a href="<?php echo htmlspecialchars($content['file_path']); ?>" 
                                class="attachment-link" target="_blank">
                                    <i class="fas fa-paperclip"></i> View Attachment
                                </a>
                            <?php endif; ?>
                            <div class="content-footer">
                                <p class="content-creator">
                                    <i class="fas fa-user"></i> 
                                    Added by: <?php echo htmlspecialchars($content['creator_name']); ?>
                                </p>
                                <p class="content-date">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date('M d, Y H:i', strtotime($content['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
    </div>

    <!-- Edit Content Modal -->
    <div id="editContentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Content</h2>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editContentForm">
                <input type="hidden" name="edit_content" value="1">
                <input type="hidden" name="content_id" id="editContentId">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="editTitle" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Content Type</label>
                    <select name="content_type" id="editContentType" class="form-control" required>
                        <option value="lesson">Lesson</option>
                        <option value="exercise">Exercise</option>
                        <option value="assignment">Assignment</option>
                        <option value="quiz">Quiz</option>
                        <option value="resource">Resource</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" id="editContentText" class="form-control" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>New Attachment (optional)</label>
                    <div class="file-input-wrapper">
                        <button type="button" class="file-input-button">
                            <i class="fas fa-upload"></i> Choose File
                        </button>
                        <input type="file" name="content_file" id="edit_content_file">
                    </div>
                    <span class="selected-file-name" id="edit-file-name"></span>
                    <p class="note">Leave empty to keep the current attachment.</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="confirmDelete(document.getElementById('editContentId').value, document.getElementById('editTitle').value)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="confirm-modal">
        <div class="confirm-content">
            <h3>Delete Content</h3>
            <p>Are you sure you want to delete this content? This action cannot be undone.</p>
            <p class="content-title-display"></p>
            <div class="confirm-actions">
                <button class="confirm-btn confirm-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="delete_content" value="1">
                    <input type="hidden" name="content_id" id="deleteContentId">
                    <button type="submit" class="confirm-btn confirm-btn-delete">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('content_file').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
        
        function confirmDelete(contentId, contentTitle) {
            document.getElementById('deleteContentId').value = contentId;
            document.querySelector('.content-title-display').textContent = `"${contentTitle}"`;
            document.getElementById('deleteConfirmModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('confirm-modal')) {
                event.target.style.display = 'none';
            }
        }

        document.getElementById('content_file').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
        
        document.getElementById('edit_content_file').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('edit-file-name').textContent = fileName;
        });
        
        function editContent(contentId, title, contentType, contentText) {
            document.getElementById('editContentId').value = contentId;
            document.getElementById('editTitle').value = title;
            document.getElementById('editContentType').value = contentType;
            document.getElementById('editContentText').value = contentText;
            document.getElementById('edit-file-name').textContent = 'No file chosen';
            document.getElementById('editContentModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editContentModal').style.display = 'none';
        }
        
        function confirmDelete(contentId, contentTitle) {
            document.getElementById('deleteContentId').value = contentId;
            document.querySelector('.content-title-display').textContent = `"${contentTitle}"`;
            document.getElementById('deleteConfirmModal').style.display = 'flex';
            
            document.getElementById('editContentModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }
     
        window.onclick = function(event) {
            if (event.target.classList.contains('confirm-modal') || event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        document.getElementById('content_file').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
    <script src="js/script.js"></script>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> EduSync. All rights reserved.</p>
    </footer>

</body>
</html>