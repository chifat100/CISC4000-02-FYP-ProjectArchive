<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: loginTest.php");
        exit;
    }

    require_once "php/config.php";

    // Check if course ID is provided
    if(!isset($_GET['id'])) {
        header("location: student_WS.php");
        exit;
    }

    // Fetch user data including profile picture
    $sql = "SELECT * FROM users WHERE id = :id";
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
        if($stmt->execute()){
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            // Update session with profile picture if not set
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


    $course_id = $_GET['id'];

    // Check if student is enrolled in this course
    $sql = "SELECT * FROM enrollments 
            WHERE student_id = :student_id 
            AND course_id = :course_id 
            AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":student_id", $_SESSION["id"], PDO::PARAM_INT);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() == 0) {
        header("location: workspace.php");
        exit;
    }

    // Get course details
    $sql = "SELECT c.*, u.name as instructor_name 
            FROM courses c 
            JOIN users u ON c.instructor_id = u.id 
            WHERE c.id = :course_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get course content
    $sql = "SELECT * FROM course_content 
            WHERE course_id = :course_id 
            ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle exercise submission
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_exercise'])) {
        $content_id = $_POST['content_id'];
        $answer = $_POST['answer'];
        
        $sql = "INSERT INTO student_exercises (student_id, content_id, answer) 
                VALUES (:student_id, :content_id, :answer)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":student_id", $_SESSION["id"], PDO::PARAM_INT);
        $stmt->bindParam(":content_id", $content_id, PDO::PARAM_INT);
        $stmt->bindParam(":answer", $answer, PDO::PARAM_STR);
        $stmt->execute();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_name']); ?> - EduSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <style>
        .course-content-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .course-header {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .course-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--main-color), var(--main-color-light));
        }

        .course-header h1 {
            font-size: 2.8rem;
            color: var(--black);
            margin-bottom: 15px;
        }

        .course-instructor {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.6rem;
            color: var(--light-color);
        }

        .course-instructor i {
            color: var(--main-color);
        }

        .course-progress {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .progress-bar {
            height: 8px;
            background: var(--light-bg);
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--main-color);
            width: 60%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .content-list {
            display: grid;
            gap: 20px;
        }

        .content-item {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .content-item:hover {
            transform: translateY(-5px);
        }

        .content-item h3 {
            font-size: 2rem;
            color: var(--black);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-item h3 i {
            color: var(--main-color);
            font-size: 1.8rem;
        }

        .lesson-content {
            font-size: 1.6rem;
            line-height: 1.8;
            color: var(--light-color);
        }

        .lesson-content p {
            margin-bottom: 15px;
        }

        .attachment {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .attachment a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: var(--light-bg);
            color: var(--black);
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .attachment a:hover {
            background: var(--main-color);
            color: var(--white);
        }

        .exercise-content {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .exercise-content form {
            margin-top: 20px;
        }

        .exercise-content textarea {
            width: 100%;
            height: 150px;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1.6rem;
            margin-bottom: 15px;
            resize: vertical;
        }

        .exercise-content button {
            background: var(--main-color);
            color: var(--white);
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.6rem;
            transition: all 0.3s ease;
        }

        .exercise-content button:hover {
            background: var(--black);
            transform: translateY(-2px);
        }

        .content-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: var(--light-bg);
            color: var(--black);
            border-radius: 5px;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: var(--main-color);
            color: var(--white);
        }

        .course-resources {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .resource-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .resource-item {
            padding: 15px;
            background: var(--light-bg);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .resource-item i {
            font-size: 2rem;
            color: var(--main-color);
        }

        @media (max-width: 768px) {
            .course-content-container {
                padding: 10px;
            }

            .course-header {
                padding: 20px;
            }

            .course-header h1 {
                font-size: 2.2rem;
            }

            .content-item {
                padding: 20px;
            }
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

    <div class="course-content-container">
        <!-- Course Header -->
        <div class="course-header">
            <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
            <div class="course-instructor">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></span>
            </div>
        </div>

        <!-- Course Progress -->
        <div class="course-progress">
            <h3>Course Progress</h3>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <span>60% Complete</span>
        </div>

        <!-- Content List -->
        <div class="content-list">
            <?php foreach($contents as $content): ?>
                <div class="content-item">
                    <h3>
                        <i class="fas <?php echo $content['content_type'] == 'lesson' ? 'fa-book-open' : 'fa-pencil-alt'; ?>"></i>
                        <?php echo htmlspecialchars($content['title']); ?>
                    </h3>
                    
                    <?php if($content['content_type'] == 'lesson'): ?>
                        <div class="lesson-content">
                            <?php echo $content['content']; ?>
                            <?php if($content['file_path']): ?>
                                <div class="attachment">
                                    <a href="<?php echo htmlspecialchars($content['file_path']); ?>" target="_blank">
                                        <i class="fas fa-file-download"></i> Download Materials
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="exercise-content">
                            <?php echo $content['content']; ?>
                            <form method="POST">
                                <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                                <textarea name="answer" placeholder="Type your answer here..." required></textarea>
                                <button type="submit" name="submit_exercise">
                                    <i class="fas fa-paper-plane"></i> Submit Answer
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Content Navigation -->
                    <div class="content-nav">
                        <a href="#" class="nav-btn">
                            <i class="fas fa-arrow-left"></i> Previous
                        </a>
                        <a href="#" class="nav-btn">
                            Next <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Course Resources -->
        <div class="course-resources">
            <h3>Course Resources</h3>
            <div class="resource-list">
                <div class="resource-item">
                    <i class="fas fa-file-pdf"></i>
                    <span>Course Syllabus</span>
                </div>
                <div class="resource-item">
                    <i class="fas fa-book"></i>
                    <span>Reference Materials</span>
                </div>
                <div class="resource-item">
                    <i class="fas fa-video"></i>
                    <span>Video Lectures</span>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>