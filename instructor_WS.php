<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: loginTest.php");
        exit;
    }


    // Check if user is an instructor
    if($_SESSION["profile_type"] !== "instructor"){
        header("location: dashboard.php");
        exit;
    }

    require_once "php/config.php";


    if(isset($_GET['success'])) {
        $success_messages = [
            'schedule_added' => 'Teaching schedule added successfully!',
            'resource_added' => 'Teaching resource added successfully!'
        ];
        $success_msg = $success_messages[$_GET['success']] ?? '';
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


    // Function to get instructor's courses
    function getInstructorCourses($pdo, $instructor_id) {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'active') as student_count 
                FROM courses c 
                WHERE c.instructor_id = :instructor_id 
                ORDER BY c.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":instructor_id", $instructor_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle course creation
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST["create_course"])) {
            $course_code = trim($_POST["course_code"]);
            $course_name = trim($_POST["course_name"]);
            $description = trim($_POST["description"]);
            
            $sql = "INSERT INTO courses (course_code, course_name, description, instructor_id) 
                    VALUES (:course_code, :course_name, :description, :instructor_id)";
            
            if($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(":course_code", $course_code, PDO::PARAM_STR);
                $stmt->bindParam(":course_name", $course_name, PDO::PARAM_STR);
                $stmt->bindParam(":description", $description, PDO::PARAM_STR);
                $stmt->bindParam(":instructor_id", $_SESSION["id"], PDO::PARAM_INT);
                
                if($stmt->execute()) {
                    $success_msg = "Course created successfully!";
                } else {
                    $error_msg = "Something went wrong. Please try again.";
                }
            }
        }
    }

    // Function to get course assignments
    function getCourseAssignments($pdo, $instructor_id) {
        $sql = "SELECT a.*, c.course_name
                FROM assignments a
                JOIN courses c ON a.course_id = c.id
                WHERE c.instructor_id = :instructor_id
                ORDER BY a.due_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":instructor_id", $instructor_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    // Handle assignment creation
    if(isset($_POST['create_assignment'])) {
        $course_id = $_POST['course_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $due_date = $_POST['due_date'];
        
        // Verify instructor owns the course
        $sql = "SELECT id FROM courses WHERE id = ? AND instructor_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$course_id, $_SESSION['id']]);
        
        if($stmt->rowCount() > 0) {
            $sql = "INSERT INTO assignments (course_id, title, description, due_date) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$course_id, $title, $description, $due_date])) {
                $success_msg = "Assignment created successfully!";
            } else {
                $error_msg = "Error creating assignment.";
            }
        }
    }


    $assignments = getCourseAssignments($pdo, $_SESSION["id"]);

    $instructor_courses = getInstructorCourses($pdo, $_SESSION["id"]);

    function getTeachingStats($pdo, $instructor_id) {
        $stats = array();
        
        try {
            // Get total students
            $sql = "SELECT COUNT(DISTINCT e.student_id) as total_students 
                    FROM enrollments e 
                    JOIN courses c ON e.course_id = c.id 
                    WHERE c.instructor_id = ? AND e.status = 'active'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$instructor_id]);
            $stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'] ?? 0;
            
            // Get average course rating
            $sql = "SELECT AVG(cr.rating) as avg_rating 
                    FROM course_ratings cr 
                    JOIN courses c ON cr.course_id = c.id 
                    WHERE c.instructor_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$instructor_id]);
            $stats['avg_rating'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0, 1);
            
            // Get total assignments
            $sql = "SELECT COUNT(*) as total_assignments 
                    FROM assignments a 
                    JOIN courses c ON a.course_id = c.id 
                    WHERE c.instructor_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$instructor_id]);
            $stats['total_assignments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_assignments'] ?? 0;
            
            return $stats;
        } catch(PDOException $e) {
            error_log("Error in getTeachingStats: " . $e->getMessage());
            return [
                'total_students' => 0,
                'avg_rating' => 0,
                'total_assignments' => 0
            ];
        }
    }

    // Function to get course analytics
    function getCourseAnalytics($pdo, $instructor_id) {
        $sql = "SELECT c.course_name,
                COUNT(DISTINCT e.student_id) as enrolled_students,
                COUNT(DISTINCT a.id) as total_assignments,
                c.rating as avg_grade
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
                LEFT JOIN assignments a ON c.id = a.course_id
                WHERE c.instructor_id = ?
                GROUP BY c.id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get recent student activities
    function getRecentActivities($pdo, $instructor_id) {
        try {
            $sql = "SELECT sa.type, sa.description, sa.created_at, 
                    u.name as student_name, c.course_name
                    FROM student_activities sa
                    JOIN users u ON sa.student_id = u.id
                    JOIN courses c ON sa.course_id = c.id
                    WHERE c.instructor_id = ?
                    ORDER BY sa.created_at DESC
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$instructor_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getRecentActivities: " . $e->getMessage());
            return [];
        }
    }

    // Function to get teaching schedule
    function getTeachingSchedule($pdo, $instructor_id) {
        try {
            $sql = "SELECT ts.*, c.course_name
                    FROM teaching_schedule ts
                    JOIN courses c ON ts.course_id = c.id
                    WHERE ts.instructor_id = ?
                    ORDER BY ts.schedule_date, ts.start_time";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$instructor_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getTeachingSchedule: " . $e->getMessage());
            return [];
        }
    }

    // Function to get teaching resources
    function getTeachingResources($pdo, $instructor_id) {
        $sql = "SELECT r.*, c.course_name
                FROM teaching_resources r
                JOIN courses c ON r.course_id = c.id
                WHERE c.instructor_id = ?
                ORDER BY r.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$instructor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    // Handle schedule creation
    if(isset($_POST['add_schedule'])) {
        try {
            $course_id = $_POST['schedule_course'];
            $topic = trim($_POST['topic']);
            $schedule_date = $_POST['schedule_date'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            
            $sql = "INSERT INTO teaching_schedule (instructor_id, course_id, topic, schedule_date, start_time, end_time) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['id'], $course_id, $topic, $schedule_date, $start_time, $end_time]);
            
            header("Location: instructor_WS.php?success=schedule_added");
            exit;
        } catch(PDOException $e) {
            error_log("Error adding schedule: " . $e->getMessage());
        }
    }

    // Handle resource upload
    if(isset($_POST['add_resource'])) {
        try {
            $course_id = $_POST['resource_course'];
            $title = trim($_POST['resource_title']);
            $description = trim($_POST['resource_description']);
            $type = $_POST['resource_type'];
            
            // Handle file upload
            $file_path = '';
            if(isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] == 0) {
                $upload_dir = 'uploads/resources/';
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = time() . '_' . $_FILES['resource_file']['name'];
                $file_path = $upload_dir . $file_name;
                
                move_uploaded_file($_FILES['resource_file']['tmp_name'], $file_path);
            }
            
            $sql = "INSERT INTO teaching_resources (instructor_id, course_id, title, description, type, file_path) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['id'], $course_id, $title, $description, $type, $file_path]);
            
            header("Location: instructor_WS.php?success=resource_added");
            exit;
        } catch(PDOException $e) {
            error_log("Error adding resource: " . $e->getMessage());
        }
    }

    // Get instructor's data
    $teaching_stats = getTeachingStats($pdo, $_SESSION["id"]);
    $course_analytics = getCourseAnalytics($pdo, $_SESSION["id"]);
    $recent_activities = getRecentActivities($pdo, $_SESSION["id"]);
    $teaching_schedule = getTeachingSchedule($pdo, $_SESSION["id"]);
    $teaching_resources = getTeachingResources($pdo, $_SESSION["id"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Workspace - EduSync</title>
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

        .create-course-form {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--black);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            background: var(--white);
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .course-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .course-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--main-color);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--light-bg);
            color: var(--black);
            border: 1px solid var(--border);
        }

        .student-count {
            color: var(--light-color);
            font-size: 0.9rem;
            margin-top: 10px;
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

        .course-badge {
            background: var(--main-color);
            color: var(--white);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
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

        .assignment-stats {
            margin-bottom: 15px;
            font-size: 1.4rem;
        }

        .stat-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .assignment-actions {
            display: flex;
            gap: 10px;
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
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
        }

        .create-assignment-form {
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--main-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--white);
        }

        .stat-info h3 {
            font-size: 1.4rem;
            color: var(--light-color);
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 2rem;
            color: var(--black);
            font-weight: bold;
        }

        .analytics-section {
            margin-top: 30px;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .analytics-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .analytics-stats {
            margin-top: 15px;
        }

        .stat-item {
            margin-bottom: 10px;
        }

        .stat-label {
            display: block;
            font-size: 1.2rem;
            color: var(--light-color);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.6rem;
            color: var(--black);
            font-weight: bold;
        }

        .schedule-section,
        .resources-section,
        .activities-section {
            margin-top: 30px;
        }

        .schedule-grid,
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .schedule-card,
        .resource-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .schedule-time {
            font-size: 1.6rem;
            color: var(--main-color);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .schedule-info h4 {
            font-size: 1.4rem;
            color: var(--black);
            margin-bottom: 5px;
        }

        .schedule-date {
            font-size: 1.2rem;
            color: var(--light-color);
            margin-top: 10px;
        }

        .activities-list {
            margin-top: 20px;
        }

        .activity-item {
            display: flex;
            align-items: start;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: var(--light-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--main-color);
        }

        .activity-time {
            font-size: 1.2rem;
            color: var(--light-color);
            display: block;
            margin-top: 5px;
        }

        .course-tag {
            display: inline-block;
            padding: 3px 8px;
            background: var(--main-color);
            color: var(--white);
            border-radius: 15px;
            font-size: 1.2rem;
            margin-top: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
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

        .modal h2 {
            font-size: 2rem;
            color: var(--black);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
        }

        .modal .form-group {
            margin-bottom: 20px;
        }

        .modal .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--black);
            font-size: 1.4rem;
        }

        .modal .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1.4rem;
            background: var(--light-bg);
        }

        .modal .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .modal .btn-secondary,
        .modal .btn-primary {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .modal .btn-secondary {
            background: var(--light-bg);
            color: var(--black);
        }

        .modal .btn-primary {
            background: var(--main-color);
            color: var(--white);
        }

        .modal .btn-secondary:hover,
        .modal .btn-primary:hover {
            transform: translateY(-2px);
        }

        .modal input[type="file"] {
            padding: 10px;
            background: var(--light-bg);
            border-radius: 8px;
            width: 100%;
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
                    echo 'images/default-avatar.png';
                }
            ?>" alt="Profile Picture">

            <h3 class="name"><?php echo htmlspecialchars($_SESSION["name"]); ?></h3>
            <p class="role"><?php echo htmlspecialchars($_SESSION["profile_type"]); ?></p>

            <a href="profile.php" class="btn">view profile</a>
        </div>

        <nav class="navbar">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>home</span></a>
            <a href="instructor_WS.php"><i class="fa-solid fa-person-digging"></i><span>workspace</span></a>
            <a href="courses.php"><i class="fas fa-graduation-cap"></i><span>courses</span></a>
            <a href="forum.php"><i class="fas fa-headset"></i><span>discussion</span></a>
            <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
        </nav>
    </div>

    <div class="workspace-container">
        <section class="course-section">
            <h2 class="section-title">Create New Course</h2>
            <div class="create-course-form">
                <form method="POST">
                    <div class="form-group">
                        <label>Course Code</label>
                        <input type="text" name="course_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" name="course_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="create_course" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Course
                    </button>
                </form>
            </div>
        </section>

        <section class="course-section">
            <h2 class="section-title">My Courses</h2>
            <div class="course-grid">
                <?php foreach($instructor_courses as $course): ?>
                    <div class="course-card">
                        <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                        <p class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></p>
                        <p class="student-count">
                            <i class="fas fa-users"></i> 
                            <?php echo $course['student_count']; ?> students enrolled
                        </p>
                        <div class="course-actions">
                            <a href="course_manage.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Manage
                            </a>
                            <a href="course_students.php?id=<?php echo $course['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-users"></i> Students
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="course-section">
            <h2 class="section-title">Assignments</h2>
            <div class="create-assignment-form">
                <button class="btn btn-primary" onclick="openAssignmentModal()">
                    <i class="fas fa-plus"></i> Create New Assignment
                </button>
            </div>

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
                        
                        <div class="assignment-actions">
                            <button class="btn btn-secondary" onclick="editAssignment(<?php echo $assignment['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </section>

        <!-- Assignment Modal -->
        <div id="assignmentModal" class="modal">
            <div class="modal-content">
                <h2>Create New Assignment</h2>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id" class="form-control" required>
                            <?php foreach($instructor_courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="datetime-local" name="due_date" class="form-control" required>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" onclick="closeAssignmentModal()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" name="create_assignment" class="btn btn-primary">Create Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Instructor Dashboard Section -->
    <section class="course-section">
        <h2 class="section-title">Teaching Dashboard</h2>
        
        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Students</h3>
                    <p class="stat-number"><?php echo $teaching_stats['total_students']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3>Average Rating</h3>
                    <p class="stat-number"><?php echo $teaching_stats['avg_rating']; ?>/5.0</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Assignments</h3>
                    <p class="stat-number"><?php echo $teaching_stats['total_assignments']; ?></p>
                </div>
            </div>
        </div>

        <!-- Course Analytics -->
        <div class="analytics-section">
            <h3>Course Performance Analytics</h3>
            <div class="analytics-grid">
                <?php foreach($course_analytics as $course): ?>
                    <div class="analytics-card">
                        <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <div class="analytics-stats">
                            <div class="stat-item">
                                <span class="stat-label">Enrolled Students</span>
                                <span class="stat-value"><?php echo $course['enrolled_students']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Assignments</span>
                                <span class="stat-value"><?php echo $course['total_assignments']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Course Rating</span>
                                <span class="stat-value"><?php echo number_format($course['avg_grade'], 1); ?>/5.0</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Teaching Schedule -->
        <div class="schedule-section">
            <h3>Teaching Schedule</h3>
            <button type="button" class="btn btn-primary" onclick="openScheduleModal()">
                <i class="fas fa-plus"></i> Add Schedule
            </button>
            <div class="schedule-grid">
                <?php foreach($teaching_schedule as $schedule): ?>
                    <div class="schedule-card">
                        <div class="schedule-time">
                            <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                            <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                        </div>
                        <div class="schedule-info">
                            <h4><?php echo htmlspecialchars($schedule['course_name']); ?></h4>
                            <p><?php echo htmlspecialchars($schedule['topic']); ?></p>
                        </div>
                        <div class="schedule-date">
                            <?php echo date('M d, Y', strtotime($schedule['schedule_date'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Teaching Resources -->
        <div class="resources-section">
            <h3>Teaching Resources</h3>
            <button type="button" class="btn btn-primary" onclick="openResourceModal()">
                <i class="fas fa-plus"></i> Add Resource
            </button>
            <div class="resources-grid">
                <?php foreach($teaching_resources as $resource): ?>
                    <div class="resource-card">
                        <div class="resource-icon">
                            <i class="fas <?php echo getResourceIcon($resource['type']); ?>"></i>
                        </div>
                        <div class="resource-info">
                            <h4><?php echo htmlspecialchars($resource['title']); ?></h4>
                            <p><?php echo htmlspecialchars($resource['description']); ?></p>
                            <span class="course-tag"><?php echo htmlspecialchars($resource['course_name']); ?></span>
                        </div>
                        <div class="resource-actions">
                            <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button class="btn btn-primary" onclick="editResource(<?php echo $resource['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="activities-section">
            <h3>Recent Student Activities</h3>
            <div class="activities-list">
                <?php foreach($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas <?php echo getActivityIcon($activity['type']); ?>"></i>
                        </div>
                        <div class="activity-info">
                            <p>
                                <strong><?php echo htmlspecialchars($activity['student_name']); ?></strong>
                                <?php echo htmlspecialchars($activity['description']); ?>
                                in <span class="course-tag"><?php echo htmlspecialchars($activity['course_name']); ?></span>
                            </p>
                            <span class="activity-time">
                                <?php echo timeAgo($activity['created_at']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <h2>Add Teaching Schedule</h2>
            <form method="POST" action="instructor_WS.php">
                <div class="form-group">
                    <label>Course</label>
                    <select name="schedule_course" class="form-control" required>
                        <?php foreach($instructor_courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Topic</label>
                    <input type="text" name="topic" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="schedule_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('scheduleModal')" class="btn-secondary">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="openScheduleModal()">
                        <i class="fas fa-plus"></i> Add Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resource Modal -->
    <div id="resourceModal" class="modal">
        <div class="modal-content">
            <h2>Add Teaching Resource</h2>
            <form method="POST" enctype="multipart/form-data" action="instructor_WS.php">
                <div class="form-group">
                    <label>Course</label>
                    <select name="resource_course" class="form-control" required>
                        <?php foreach($instructor_courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="resource_title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="resource_description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Resource Type</label>
                    <select name="resource_type" class="form-control" required>
                        <option value="document">Document</option>
                        <option value="presentation">Presentation</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Upload File</label>
                    <input type="file" name="resource_file" class="form-control" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('resourceModal')" class="btn-secondary">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="openResourceModal()">
                        <i class="fas fa-plus"></i> Add Resource
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function openAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'flex';
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'none';
        }

        function editAssignment(assignmentId) {
          
            alert('Edit assignment ' + assignmentId);
        }

        window.onclick = function(event) {
            const modal = document.getElementById('assignmentModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        window.onload = function() {
            <?php if(isset($success_msg)): ?>
                alert('<?php echo $success_msg; ?>');
            <?php endif; ?>
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editResource(resourceId) {
            alert('Edit resource ' + resourceId);
        }


        function getResourceIcon(type) {
            const icons = {
                'document': 'fa-file-alt',
                'presentation': 'fa-file-powerpoint',
                'video': 'fa-video',
                'audio': 'fa-volume-up',
                'other': 'fa-file'
            };
            return icons[type] || icons['other'];
        }

        function getActivityIcon(type) {
            const icons = {
                'submission': 'fa-paper-plane',
                'enrollment': 'fa-user-plus',
                'completion': 'fa-check-circle',
                'comment': 'fa-comment'
            };
            return icons[type] || 'fa-circle';
        }

        function timeAgo(datetime) {
            const now = new Date();
            const past = new Date(datetime);
            const diff = Math.floor((now - past) / 1000);
            
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            if (diff < 2592000) return Math.floor(diff / 86400) + ' days ago';
            return past.toLocaleDateString();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Get all modals
            const modals = {
                'scheduleModal': document.getElementById('scheduleModal'),
                'resourceModal': document.getElementById('resourceModal'),
                'assignmentModal': document.getElementById('assignmentModal')
            };

            window.openModal = function(modalId) {
                if (modals[modalId]) {
                    modals[modalId].style.display = 'flex';
                }
            };

            window.closeModal = function(modalId) {
                if (modals[modalId]) {
                    modals[modalId].style.display = 'none';
                }
            };

            window.onclick = function(event) {
                for (let modalId in modals) {
                    if (event.target === modals[modalId]) {
                        modals[modalId].style.display = 'none';
                    }
                }
            };

            <?php if(isset($_GET['success'])): ?>
                const messages = {
                    'schedule_added': 'Teaching schedule added successfully!',
                    'resource_added': 'Teaching resource added successfully!'
                };
                const message = messages['<?php echo $_GET['success']; ?>'];
                if (message) {
                    alert(message);
                }
            <?php endif; ?>
        });

        function openScheduleModal() {
            openModal('scheduleModal');
        }

        function openResourceModal() {
            openModal('resourceModal');
        }
    </script>
</body>
</html>