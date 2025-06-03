<?php
    session_start();

    require_once "php/config.php";

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    function debug_to_console($data) {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);

        echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
    }

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: loginTest.php");
        exit;
    }



    if($_SESSION["profile_type"] === "instructor") {
        header("location: instructor_WS.php");
        exit;
    } elseif($_SESSION["profile_type"] === "admin") {
        header("location: admin_WS.php");
        exit;
    }

        // Fetch user data
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

    $upload_dir = 'uploads/profile_pictures/';
    if(!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    function getProfileImage($user_data) {
        $default_image = 'images/default-avatar.png';
        
        if(!empty($user_data['profile_picture'])) {
            $image_path = 'uploads/profile_pictures/'.$user_data['profile_picture'];
            if(file_exists($image_path)) {
                return $image_path;
            }
        }
        return $default_image;
    }


    // Function to get enrolled courses
    function getEnrolledCourses($pdo, $student_id) {
        $sql = "SELECT c.*, u.name as instructor_name 
                FROM courses c 
                JOIN enrollments e ON c.id = e.course_id 
                JOIN users u ON c.instructor_id = u.id 
                WHERE e.student_id = :student_id AND e.status = 'active'";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get available courses
    function getAvailableCourses($pdo, $student_id) {
        $sql = "SELECT c.*, u.name as instructor_name 
                FROM courses c 
                JOIN users u ON c.instructor_id = u.id 
                WHERE c.id NOT IN (
                    SELECT course_id FROM enrollments 
                    WHERE student_id = :student_id AND status = 'active'
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle course enrollment/dropping
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST["enroll"]) && isset($_POST["course_id"])) {
            $sql = "INSERT INTO enrollments (student_id, course_id) VALUES (:student_id, :course_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":student_id", $_SESSION["id"], PDO::PARAM_INT);
            $stmt->bindParam(":course_id", $_POST["course_id"], PDO::PARAM_INT);
            $stmt->execute();
        } elseif(isset($_POST["drop"]) && isset($_POST["course_id"])) {
            $sql = "UPDATE enrollments SET status = 'dropped' 
                    WHERE student_id = :student_id AND course_id = :course_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":student_id", $_SESSION["id"], PDO::PARAM_INT);
            $stmt->bindParam(":course_id", $_POST["course_id"], PDO::PARAM_INT);
            $stmt->execute();
        }
        header("Location: student_WS.php");
        exit;
    }

    // Function to get assignments for enrolled courses
    function getAssignments($pdo, $student_id) {
        $sql = "SELECT a.*, c.course_name
                FROM assignments a
                JOIN courses c ON a.course_id = c.id
                JOIN enrollments e ON c.id = e.course_id
                WHERE e.student_id = :student_id AND e.status = 'active'
                ORDER BY a.due_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getLearningProgress($pdo, $student_id) {
        try {
            $sql = "SELECT 
                    c.course_name, 
                    COUNT(DISTINCT a.id) as total_assignments,
                    0 as completed_assignments,
                    0 as average_grade
                    FROM courses c
                    JOIN enrollments e ON c.id = e.course_id
                    LEFT JOIN assignments a ON c.id = a.course_id
                    WHERE e.student_id = ? AND e.status = 'active'
                    GROUP BY c.id, c.course_name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getLearningProgress: " . $e->getMessage());
            return [];
        }
    }

    // Handle assignment submission
    if(isset($_POST['submit_assignment'])) {
        $assignment_id = $_POST['assignment_id'];
        $submission_text = trim($_POST['submission_text']);
        
        // Handle file upload
        $file_path = '';
        if(isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
            $upload_dir = 'uploads/assignments/';
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . $_FILES['submission_file']['name'];
            $file_path = $upload_dir . $file_name;
            
            move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path);
        }
        
        // Check if submission already exists
        $sql = "SELECT id FROM assignment_submissions 
                WHERE assignment_id = ? AND student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$assignment_id, $_SESSION['id']]);
        
        if($stmt->rowCount() > 0) {
            // Update existing submission
            $sql = "UPDATE assignment_submissions 
                    SET submission_text = ?, file_path = ?, submission_date = NOW()
                    WHERE assignment_id = ? AND student_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$submission_text, $file_path, $assignment_id, $_SESSION['id']]);
        } else {
            // Create new submission
            $sql = "INSERT INTO assignment_submissions 
                    (assignment_id, student_id, submission_text, file_path) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$assignment_id, $_SESSION['id'], $submission_text, $file_path]);
        }
        
        header("Location: student_WS.php?submitted=1");
        exit;
    }



    // Function to get student's study goals
    function getStudentGoals($pdo, $student_id) {
        $sql = "SELECT * FROM study_goals WHERE student_id = ? ORDER BY target_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get student's notes
    function getStudentNotes($pdo, $student_id) {
        $sql = "SELECT n.*, c.course_name 
                FROM study_notes n 
                LEFT JOIN courses c ON n.course_id = c.id 
                WHERE n.student_id = ? 
                ORDER BY n.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle study goals
    if(isset($_POST['add_goal'])) {
        try {
            $title = trim($_POST['goal_title']);
            $description = trim($_POST['goal_description']);
            $target_date = $_POST['target_date'];
            
            $sql = "INSERT INTO study_goals (student_id, title, description, target_date) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$_SESSION['id'], $title, $description, $target_date]);
            
            if($result) {
                header("Location: student_WS.php?success=goal_added");
                exit;
            }
        } catch(PDOException $e) {
            error_log("Error adding goal: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }


    // Function to complete a goal
    function completeGoal($pdo, $goal_id, $student_id) {
        $sql = "UPDATE study_goals SET completed = 1 
                WHERE id = ? AND student_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$goal_id, $student_id]);
    }

    // Function to get learning preferences
    function getLearningPreferences($pdo, $student_id) {
        $sql = "SELECT * FROM learning_preferences WHERE student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prefs) {
            $sql = "INSERT INTO learning_preferences (student_id) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
            
            return [
                'theme' => 'light',
                'study_reminder' => 0,
                'notification_preference' => 'email'
            ];
        }
        
        return $prefs;
    }

    // Function to update learning preferences
    function updateLearningPreferences($pdo, $student_id, $preferences) {
        $sql = "UPDATE learning_preferences 
                SET theme = ?, study_reminder = ?, notification_preference = ? 
                WHERE student_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $preferences['theme'],
            $preferences['study_reminder'],
            $preferences['notification_preference'],
            $student_id
        ]);
    }


    // Handle study notes
    if(isset($_POST['add_note'])) {
        try {
            $title = trim($_POST['note_title']);
            $content = trim($_POST['note_content']);
            $course_id = $_POST['course_id'];
            
            $sql = "INSERT INTO study_notes (student_id, course_id, title, content) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$_SESSION['id'], $course_id, $title, $content]);
            
            if($result) {
                header("Location: student_WS.php?success=note_added");
                exit;
            }
        } catch(PDOException $e) {
            error_log("Error adding note: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }

    // Handle study schedule
    if(isset($_POST['add_schedule'])) {
        try {
            $course_id = $_POST['schedule_course'];
            $study_date = $_POST['study_date'];
            $start_time = $_POST['start_time'];
            $duration = $_POST['duration'];
            
            $sql = "INSERT INTO study_schedule (student_id, course_id, study_date, start_time, duration) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$_SESSION['id'], $course_id, $study_date, $start_time, $duration]);
            
            if($result) {
                header("Location: student_WS.php?success=schedule_added");
                exit;
            }
        } catch(PDOException $e) {
            error_log("Error adding schedule: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }





    // Handle completing goals
    if(isset($_POST['complete_goal'])) {
        $goal_id = $_POST['goal_id'];
        if(completeGoal($pdo, $goal_id, $_SESSION['id'])) {
            header("Location: student_WS.php?success=goal_completed");
            exit;
        }
    }

    // Handle learning preferences update
    if(isset($_POST['update_preferences'])) {
        $preferences = [
            'theme' => $_POST['theme'],
            'study_reminder' => isset($_POST['study_reminder']) ? 1 : 0,
            'notification_preference' => $_POST['notification_preference']
        ];
        
        if(updateLearningPreferences($pdo, $_SESSION['id'], $preferences)) {
            header("Location: student_WS.php?success=preferences_updated");
            exit;
        }
    }


    // Get personalized data
    $preferences = getLearningPreferences($pdo, $_SESSION['id']);
    $goals = getStudentGoals($pdo, $_SESSION['id']);
    $notes = getStudentNotes($pdo, $_SESSION['id']);
    $learning_progress = getLearningProgress($pdo, $_SESSION['id']);
    
    // Get student's personalized data
    $goals = getStudentGoals($pdo, $_SESSION['id']);
    $notes = getStudentNotes($pdo, $_SESSION['id']);
    $learning_progress = getLearningProgress($pdo, $_SESSION['id']);

    


    // Get assignments
    $assignments = getAssignments($pdo, $_SESSION["id"]);

    // Get enrolled and available courses
    $enrolled_courses = getEnrolledCourses($pdo, $_SESSION["id"]);
    $available_courses = getAvailableCourses($pdo, $_SESSION["id"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Workspace - EduSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <style>
        .workspace-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .course-section {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .course-card {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .course-card:hover {
            transform: translateY(-5px);
        }

        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--main-color), var(--main-color-light));
        }

        .course-card h3 {
            color: var(--black);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .course-info {
            color: var(--light-color);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .course-actions {
            display: flex;
            gap: 10px;
        }

        .btn-enroll, .btn-drop, .btn-view {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-enroll {
            background: var(--main-color);
            color: var(--white);
        }

        .btn-drop {
            background: var(--red);
            color: var(--white);
        }

        .btn-view {
            background: var(--light-bg);
            color: var(--black);
            border: 1px solid var(--border);
        }

        .btn-enroll:hover, .btn-drop:hover, .btn-view:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        .section-title {
            color: var(--black);
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: var(--light-color);
            font-style: italic;
        }

        @keyframes highlight {
            0% { background-color: var(--yellow); }
            100% { background-color: var(--light-bg); }
        }

        .new-course {
            animation: highlight 2s ease-out;
        }

        .progress-bar {
            height: 5px;
            background: var(--light-bg);
            border-radius: 5px;
            margin: 10px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--main-color);
            width: 0%;
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .course-grid {
                grid-template-columns: 1fr;
            }

            .workspace-container {
                padding: 10px;
            }

            .course-card {
                margin-bottom: 15px;
            }
        }

        .assignment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .assignment-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .assignment-header h3 {
            font-size: 1.8rem;
            color: var(--black);
        }

        .assignment-info {
            margin-bottom: 15px;
            font-size: 1.4rem;
            color: var(--light-color);
        }

        .due-date {
            margin-top: 10px;
            color: var(--main-color);
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
        }

        .status-badge.submitted {
            background: #28a745;
            color: white;
        }

        .status-badge.overdue {
            background: #dc3545;
            color: white;
        }

        .status-badge.pending {
            background: #ffc107;
            color: black;
        }

        .grade-badge {
            background: var(--main-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            margin-left: 10px;
        }

        .feedback-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .feedback-section h4 {
            color: var(--black);
            margin-bottom: 5px;
        }

        .submit-btn {
            background: var(--main-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            width: 100%;
        }   

        .submit-btn:hover {
            background: var(--black);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .dashboard-card {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dashboard-card h3 {
            font-size: 1.8rem;
            color: var(--black);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-card h3 i {
            color: var(--main-color);
        }

        .add-btn {
            background: var(--main-color);
            color: var(--white);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }

        .goals-list, .notes-list, .schedule-calendar {
            max-height: 400px;
            overflow-y: auto;
        }

        .goal-item, .note-item, .schedule-item {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .goal-item h4, .note-item h4 {
            font-size: 1.6rem;
            color: var(--black);
            margin-bottom: 10px;
        }

        .goal-date, .note-date {
            font-size: 1.2rem;
            color: var(--light-color);
            margin-top: 10px;
        }

        .status-complete {
            color: #28a745;
            font-size: 1.4rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-complete {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .course-tag {
            background: var(--main-color);
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 1.2rem;
        }

        .schedule-time {
            font-size: 1.6rem;
            color: var(--main-color);
            font-weight: bold;
        }

        .schedule-course {
            font-size: 1.4rem;
            color: var(--black);
            margin: 5px 0;
        }

        .preferences-form {
            display: grid;
            gap: 15px;
        }

        .btn-save {
            background: var(--main-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
        }

        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--black);
            font-size: 1.4rem;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.4rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-secondary, .btn-primary {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
        }

        .btn-secondary {
            background: var(--light-bg);
            color: var(--black);
        }

        .btn-primary {
            background: var(--main-color);
            color: var(--white);
        }

        .progress-charts {
            margin-top: 20px;
        }

        .course-progress {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .course-progress h4 {
            color: var(--black);
            font-size: 1.6rem;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 8px;
            background: rgba(0,0,0,0.1);
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--main-color), var(--main-color-light));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 1.4rem;
            color: var(--light-color);
        }

        .completion-stat, .grade-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .completion-stat i, .grade-stat i {
            color: var(--main-color);
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: var(--light-color);
            font-style: italic;
            background: var(--light-bg);
            border-radius: 10px;
        }

        .course-progress {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .course-progress:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        @keyframes progressFill {
            from { width: 0; }
            to { width: var(--progress-width); }
        }

        .progress-fill {
            animation: progressFill 1s ease-out forwards;
        }

    </style>
</head>
<body>

    <header class="header">
        <section class="flex">
            <a href="dashboard.php" class="logo">EduSync</a>
            <div class="icons">
                <div id="menu-btn" class="fas fa-bars"></div>
                <div id="user-btn" class="fas fa-user"></div>
                <div id="toggle-btn" class="fas fa-sun"></div>
            </div>
            <div class="profile">
                <img src="<?php echo !empty($_SESSION['profile_picture']) ? 'uploads/profile_pictures/'.$_SESSION['profile_picture'] : 'images/default-avatar.png'; ?>" alt="">
                <h3><?php echo htmlspecialchars($_SESSION["name"]); ?></h3>
                <p><?php echo htmlspecialchars($_SESSION["profile_type"]); ?></p>
                <a href="profile.php" class="btn">view profile</a>
                <a href="logout.php" class="option-btn">logout</a>
            </div>
        </section>
    </header>

    <div class="side-bar">
        <div id="close-btn">
            <i class="fas fa-times"></i>
        </div>
        <div class="profile">
            <img src="<?php echo !empty($_SESSION['profile_picture']) ? 'uploads/profile_pictures/'.$_SESSION['profile_picture'] : 'images/default-avatar.png'; ?>" alt="">
            <h3><?php echo htmlspecialchars($_SESSION["name"]); ?></h3>
            <p><?php echo htmlspecialchars($_SESSION["profile_type"]); ?></p>
            <a href="profile.php" class="btn">view profile</a>
        </div>
        <nav class="navbar">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>home</span></a>
            <a href="student_WS.php"><i class="fa-solid fa-person-digging"></i><span>workspace</span></a>
            <a href="courses.php"><i class="fas fa-graduation-cap"></i><span>courses</span></a>
            <a href="forum.php"><i class="fas fa-headset"></i><span>discussion</span></a>
            <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
        </nav>
    </div>

    <div class="workspace-container">
        <!-- Enrolled Courses Section -->
        <section class="course-section">
            <h2 class="section-title">My Enrolled Courses</h2>
            <?php if(empty($enrolled_courses)): ?>
                <div class="empty-message">
                    <p>You haven't enrolled in any courses yet.</p>
                </div>
            <?php else: ?>
                <div class="course-grid">
                    <?php foreach($enrolled_courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                            <div class="course-info">
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                <p><i class="fas fa-code"></i> <?php echo htmlspecialchars($course['course_code']); ?></p>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 60%;"></div>
                            </div>
                            <div class="course-actions">
                                <a href="course_view.php?id=<?php echo $course['id']; ?>" class="btn-view">
                                    <i class="fas fa-book-reader"></i> View Course
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" name="drop" class="btn-drop">
                                        <i class="fas fa-user-minus"></i> Drop
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Assignments Section -->
        <section class="course-section">
            <h2 class="section-title">My Assignments</h2>
            <?php if(empty($assignments)): ?>
                <div class="empty-message">
                    <p>No assignments available.</p>
                </div>
            <?php else: ?>
                <div class="assignment-grid">
                    <?php foreach($assignments as $assignment): ?>
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                <span class="course-badge"><?php echo htmlspecialchars($assignment['course_name']); ?></span>
                            </div>
                            
                            <div class="assignment-info">
                                <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                <div class="due-date">
                                    <i class="fas fa-clock"></i>
                                    Due: <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?>
                                </div>
                            </div>
                            
                            <div class="assignment-status">
                                <?php
                                $now = new DateTime();
                                $due_date = new DateTime($assignment['due_date']);
                                $status = ($now > $due_date) ? 'overdue' : 'pending';
                                $status_class = ($status === 'overdue') ? 'overdue' : 'pending';
                                $status_icon = ($status === 'overdue') ? 'exclamation-circle' : 'hourglass-half';
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <i class="fas fa-<?php echo $status_icon; ?>"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </div>
                            
                            <button class="submit-btn" onclick="viewAssignment(<?php echo $assignment['id']; ?>)">
                                View Assignment
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Available Courses Section -->
        <section class="course-section">
            <h2 class="section-title">Available Courses</h2>
            <?php if(empty($available_courses)): ?>
                <div class="empty-message">
                    <p>No available courses at the moment.</p>
                </div>
            <?php else: ?>
                <div class="course-grid">
                    <?php foreach($available_courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                            <div class="course-info">
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                <p><i class="fas fa-code"></i> <?php echo htmlspecialchars($course['course_code']); ?></p>
                            </div>
                            <div class="course-actions">
                                <form method="POST">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" name="enroll" class="btn-enroll">
                                        <i class="fas fa-user-plus"></i> Enroll
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Personal Learning Space Section -->
    <section class="course-section personal-space">
        <h2 class="section-title">My Learning Space</h2>
        
        <!-- Progress Overview -->
        <div class="dashboard-card">
            <h3><i class="fas fa-chart-line"></i> Learning Progress</h3>
            <div class="progress-charts">
                <?php if(empty($learning_progress)): ?>
                    <p class="empty-message">No course progress available yet.</p>
                <?php else: ?>
                    <?php foreach($learning_progress as $progress): ?>
                        <div class="course-progress">
                            <h4><?php echo htmlspecialchars($progress['course_name']); ?></h4>
                            <div class="progress-stats">
                                <span class="stat-item">
                                    <i class="fas fa-book"></i>
                                    Total Assignments: <?php echo $progress['total_assignments']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

            <!-- Study Goals -->
            <div class="dashboard-card">
                <h3><i class="fas fa-bullseye"></i> Study Goals</h3>
                <button class="add-btn" onclick="openModal('goalModal')">
                    <i class="fas fa-plus"></i> Add Goal
                </button>
                <div class="goals-list">
                    <?php foreach($goals as $goal): ?>
                        <div class="goal-item">
                            <h4><?php echo htmlspecialchars($goal['title']); ?></h4>
                            <p><?php echo htmlspecialchars($goal['description']); ?></p>
                            <div class="goal-date">
                                Target: <?php echo date('M d, Y', strtotime($goal['target_date'])); ?>
                            </div>
                            <div class="goal-status">
                                <?php if($goal['completed']): ?>
                                    <span class="status-complete"><i class="fas fa-check"></i> Completed</span>
                                <?php else: ?>
                                    <button class="btn-complete" onclick="completeGoal(<?php echo $goal['id']; ?>)">
                                        Mark Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Study Notes -->
            <div class="dashboard-card">
                <h3><i class="fas fa-sticky-note"></i> Study Notes</h3>
                <button class="add-btn" onclick="openModal('noteModal')">
                    <i class="fas fa-plus"></i> Add Note
                </button>
                <div class="notes-list">
                    <?php foreach($notes as $note): ?>
                        <div class="note-item">
                            <div class="note-header">
                                <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                                <span class="course-tag">
                                    <?php echo htmlspecialchars($note['course_name']); ?>
                                </span>
                            </div>
                            <div class="note-content">
                                <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                            </div>
                            <div class="note-date">
                                <?php echo date('M d, Y', strtotime($note['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Learning Preferences -->
            <div class="dashboard-card">
                <h3><i class="fas fa-cog"></i> Learning Preferences</h3>
                <form method="POST" class="preferences-form">
                    <div class="form-group">
                        <label>Theme</label>
                        <select name="theme" class="form-control">
                            <option value="light" <?php echo $preferences['theme'] == 'light' ? 'selected' : ''; ?>>Light</option>
                            <option value="dark" <?php echo $preferences['theme'] == 'dark' ? 'selected' : ''; ?>>Dark</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="study_reminder" 
                                <?php echo $preferences['study_reminder'] ? 'checked' : ''; ?>>
                            Enable Study Reminders
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Notification Preference</label>
                        <select name="notification_preference" class="form-control">
                            <option value="email" <?php echo $preferences['notification_preference'] == 'email' ? 'selected' : ''; ?>>Email</option>
                            <option value="push" <?php echo $preferences['notification_preference'] == 'push' ? 'selected' : ''; ?>>Push Notifications</option>
                            <option value="both" <?php echo $preferences['notification_preference'] == 'both' ? 'selected' : ''; ?>>Both</option>
                        </select>
                    </div>
                    <button type="submit" name="update_preferences" class="btn-save">
                        Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Goal Modal -->
    <div id="goalModal" class="modal">
        <div class="modal-content">
            <h2>Add Study Goal</h2>
            <form method="POST" action="student_WS.php">
                <div class="form-group">
                    <label>Goal Title</label>
                    <input type="text" name="goal_title" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="goal_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Target Date</label>
                    <input type="date" name="target_date" required class="form-control">
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('goalModal')" class="btn-secondary">Cancel</button>
                    <button type="submit" name="add_goal" class="btn-primary">Add Goal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Note Modal -->
    <div id="noteModal" class="modal">
        <div class="modal-content">
            <h2>Add Study Note</h2>
            <form method="POST" action="student_WS.php">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="note_title" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control">
                        <?php foreach($enrolled_courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="note_content" class="form-control" rows="5"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('noteModal')" class="btn-secondary">Cancel</button>
                    <button type="submit" name="add_note" class="btn-primary">Save Note</button>
                </div>
            </form>
        </div>
    </div>


    <footer class="footer">
        &copy; copyright @ 2025 by <span>EduSync</span> 
    </footer>

    <script src="js/script.js"></script>
    <script>

        document.querySelector('select[name="theme"]').addEventListener('change', function(e) {
            document.body.className = e.target.value;
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme: e.target.value })
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('success')) {
                const message = {
                    'goal_added': 'Study goal added successfully!',
                    'note_added': 'Study note added successfully!',
                    'schedule_added': 'Study schedule added successfully!'
                }[urlParams.get('success')];
                
                if(message) {
                    alert(message);
                }
            }
        });

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) {
                modal.style.display = 'flex';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) {
                modal.style.display = 'none';
            }
        }

        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        function completeGoal(goalId) {
            if(confirm('Mark this goal as complete?')) {
                fetch('complete_goal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ goal_id: goalId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error completing goal');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error completing goal');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.modal form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('student_WS.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if(data.includes('success')) {
                            location.reload();
                        } else {
                            console.error('Error:', data);
                            alert('Error saving data. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error saving data. Please try again.');
                    });
                });
            });
        });

        function completeGoal(goalId) {
            if(confirm('Mark this goal as complete?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'student_WS.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'complete_goal';
                input.value = goalId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewAssignment(assignmentId) {
            window.location.href = `view_assignment.php?id=${assignmentId}`;
        }

        document.querySelector('select[name="theme"]').addEventListener('change', function(e) {
            document.body.className = e.target.value;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('success')) {
                const messages = {
                    'goal_added': 'Study goal added successfully!',
                    'goal_completed': 'Goal marked as complete!',
                    'note_added': 'Study note added successfully!',
                    'preferences_updated': 'Learning preferences updated successfully!'
                };
                
                const message = messages[urlParams.get('success')];
                if(message) {
                    alert(message);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });

    </script>

</body>
</html>