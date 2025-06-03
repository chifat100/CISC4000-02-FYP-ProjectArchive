<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["profile_type"] !== "admin"){
        header("location: loginTest.php");
        exit;
    }

    require_once "php/config.php";

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

    function getSystemStats($pdo) {
        $stats = [];
        
        // Total users count
        $sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN profile_type = 'student' THEN 1 ELSE 0 END) as total_students,
                SUM(CASE WHEN profile_type = 'instructor' THEN 1 ELSE 0 END) as total_instructors
                FROM users";
        $stmt = $pdo->query($sql);
        $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total courses and active enrollments
        $sql = "SELECT COUNT(*) as total_courses FROM courses";
        $stmt = $pdo->query($sql);
        $stats['courses'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Active enrollments
        $sql = "SELECT COUNT(*) as total_enrollments FROM enrollments WHERE status = 'active'";
        $stmt = $pdo->query($sql);
        $stats['enrollments'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Recent activities
        try {
            $sql = "SELECT 
                    'New User' as activity_type,
                    name as subject_name,
                    profile_type as subject_role,
                    created_at as activity_date
                    FROM users 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    
                    UNION ALL
                    
                    SELECT 
                    'New Course' as activity_type,
                    c.course_name as subject_name,
                    u.name as subject_role,
                    c.created_at as activity_date
                    FROM courses c
                    JOIN users u ON c.instructor_id = u.id
                    WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    
                    UNION ALL
                    
                    SELECT 
                    'New Enrollment' as activity_type,
                    CONCAT(c.course_name, ' course') as subject_name,
                    u.name as subject_role,
                    NOW() as activity_date
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.id
                    JOIN users u ON e.student_id = u.id
                    WHERE e.status = 'active'
                    ORDER BY activity_date DESC
                    LIMIT 10";
            
            $stmt = $pdo->query($sql);
            $stats['recent_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $stats['recent_activities'] = [];
        }
        
        // Get system usage stats
        try {
            $sql = "SELECT 
                    COUNT(DISTINCT student_id) as active_students,
                    COUNT(DISTINCT course_id) as active_courses
                    FROM enrollments 
                    WHERE status = 'active'";
            $stmt = $pdo->query($sql);
            $stats['usage'] = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $stats['usage'] = [
                'active_students' => 0,
                'active_courses' => 0
            ];
        }
        
        return $stats;
    }


    function logSystemActivity($pdo, $type, $message) {
        try {
            $sql = "INSERT INTO system_logs (log_type, log_message) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$type, $message]);
            return true;
        } catch (PDOException $e) {
            error_log("Error logging system activity: " . $e->getMessage());
            return false;
        }
    }

    // Get system stats
    $system_stats = getSystemStats($pdo);

    // Get recent user registrations
    $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
    $stmt = $pdo->query($sql);
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent courses
    $sql = "SELECT c.*, u.name as instructor_name 
            FROM courses c 
            JOIN users u ON c.instructor_id = u.id 
            ORDER BY c.created_at DESC LIMIT 5";
    $stmt = $pdo->query($sql);
    $recent_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);




    // Function to get system performance metrics
   function getSystemMetrics($pdo) {
        $metrics = [];
        
        try {
            if (function_exists('sys_getloadavg')) {
                $metrics['system_load'] = sys_getloadavg()[0];
            } else {
                $metrics['system_load'] = random_int(1, 100) / 100;

            }
        } catch (Exception $e) {
            $metrics['system_load'] = 0;
        }
        
        try {
            $sql = "SELECT table_schema AS 'Database',
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size_MB'
                    FROM information_schema.TABLES
                    WHERE table_schema = DATABASE()
                    GROUP BY table_schema";
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $metrics['db_size'] = $result ? $result['Size_MB'] : 0;
        } catch (PDOException $e) {
            $metrics['db_size'] = 0;
        }
        
        try {
            $sql = "SELECT COUNT(DISTINCT id) as active_sessions 
                    FROM users 
                    WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $metrics['active_sessions'] = $result ? $result['active_sessions'] : 0;
        } catch (PDOException $e) {
            $metrics['active_sessions'] = 0;
        }

        $metrics['memory_usage'] = memory_get_usage(true) / 1024 / 1024; // in MB
        $metrics['php_version'] = PHP_VERSION;
        
        return $metrics;
    }

    // Function to get course analytics
    function getCourseAnalytics($pdo) {
        try {
            $sql = "SELECT 
                    c.course_name,
                    COUNT(DISTINCT e.student_id) as enrollment_count,
                    COUNT(DISTINCT a.id) as assignment_count,
                    c.rating as avg_grade
                    FROM courses c
                    LEFT JOIN enrollments e ON c.id = e.course_id
                    LEFT JOIN assignments a ON c.id = a.course_id
                    GROUP BY c.id
                    ORDER BY enrollment_count DESC
                    LIMIT 5";
                    
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Course analytics error: " . $e->getMessage());
            return [];
        }
    }

    // Function to get system alerts
    function getSystemAlerts($pdo) {
        $alerts = [];
        
        $sql = "SELECT COUNT(*) as count FROM users 
                WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $pdo->query($sql);
        $inactive_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if($inactive_users > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "$inactive_users users inactive for more than 30 days"
            ];
        }
        
        $sql = "SELECT COUNT(*) as count FROM courses c 
                LEFT JOIN assignments a ON c.id = a.course_id 
                WHERE a.id IS NULL";
        $stmt = $pdo->query($sql);
        $empty_courses = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if($empty_courses > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "$empty_courses courses have no assignments"
            ];
        }
        
        return $alerts;
    }

    // Get additional system data
    $system_metrics = getSystemMetrics($pdo);
    $course_analytics = getCourseAnalytics($pdo);
    $system_alerts = getSystemAlerts($pdo);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Workspace - EduSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            color: var(--main-color);
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2.4rem;
            color: var(--black);
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 1.4rem;
            color: var(--light-color);
        }

        .admin-section {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 2rem;
            color: var(--black);
        }

        .view-all {
            color: var(--main-color);
            font-size: 1.4rem;
        }

        .recent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .user-card, .course-card {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info, .course-info {
            flex: 1;
        }

        .user-name, .course-name {
            font-size: 1.6rem;
            color: var(--black);
            margin-bottom: 5px;
        }

        .user-role, .course-instructor {
            font-size: 1.3rem;
            color: var(--light-color);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-btn {
            background: var(--main-color);
            color: var(--white);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.6rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .action-btn:hover {
            background: var(--black);
            transform: translateY(-3px);
        }

        .activity-list {
            list-style: none;
            padding: 0;
        }

        .activity-item {
            padding: 15px;
            border-bottom: var(--border);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: var(--light-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: var(--main-color);
        }

        .activity-details {
            flex: 1;
        }

        .activity-text {
            font-size: 1.4rem;
            color: var(--black);
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 1.2rem;
            color: var(--light-color);
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
        }

        .activity-item {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            transition: background-color 0.3s ease;
        }

        .activity-item:hover {
            background-color: var(--light-bg);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: var(--main-color);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .activity-text {
            font-size: 1.4rem;
            color: var(--black);
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 1.2rem;
            color: var(--light-color);
        }

        .activity-icon.new-user {
            background: #4CAF50;
        }

        .activity-icon.new-course {
            background: #2196F3;
        }

        .activity-icon.new-enrollment {
            background: #FF9800;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .metric-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .metric-icon {
            width: 50px;
            height: 50px;
            background: var(--main-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--white);
        }

        .metric-info {
            flex: 1;
        }

        .metric-label {
            font-size: 1.4rem;
            color: var(--light-color);
            margin-bottom: 5px;
        }

        .metric-value {
            font-size: 2rem;
            color: var(--black);
            font-weight: bold;
        }

        .engagement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .engagement-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .engagement-number {
            font-size: 3rem;
            color: var(--main-color);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .engagement-label {
            font-size: 1.6rem;
            color: var(--black);
            margin-bottom: 20px;
        }

        .engagement-chart {
            height: 200px;
        }

        .course-analytics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .analytics-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .analytics-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 15px 0;
        }

        .stat {
            text-align: center;
        }

        .stat-label {
            font-size: 1.2rem;
            color: var(--light-color);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.8rem;
            color: var(--black);
            font-weight: bold;
        }

        .alerts-container {
            margin-top: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
        }

        .alert-info {
            background: #cce5ff;
            color: #004085;
        }

        .alert-dismiss {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }

        .alert-dismiss:hover {
            opacity: 1;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .tool-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .tool-card i {
            font-size: 3rem;
            color: var(--main-color);
            margin-bottom: 15px;
        }

        .tool-card h3 {
            font-size: 1.8rem;
            color: var(--black);
            margin-bottom: 15px;
        }

        .tool-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tool-btn {
            background: var(--light-bg);
            color: var(--black);
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tool-btn:hover {
            background: var(--main-color);
            color: var(--white);
        }

        @media (max-width: 768px) {
            .metrics-grid,
            .engagement-grid,
            .course-analytics,
            .tools-grid {
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
                    echo 'images/pic-1.jpg'; 
                    }
                ?>" alt="Profile Picture">
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <span>Administrator</span>
        </div>
        <nav class="navbar">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="admin_WS.php"><i class="fas fa-tools"></i><span>Admin Panel</span></a>
            <a href="manage_users.php"><i class="fas fa-users"></i><span>Manage Users</span></a>
            <a href="manage_courses.php"><i class="fas fa-graduation-cap"></i><span>Manage Courses</span></a>
            <a href="system_settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
        </nav>
    </div>

    <div class="admin-container">
        <h1 class="heading">Admin Workspace</h1>

        <div class="quick-actions">
            <a href="manage_users.php" class="action-btn">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="manage_courses.php" class="action-btn">
                <i class="fas fa-graduation-cap"></i> Manage Courses
            </a>
            <a href="system_settings.php" class="action-btn">
                <i class="fas fa-cog"></i> System Settings
            </a>
            <a href="reports.php" class="action-btn">
                <i class="fas fa-chart-bar"></i> View Reports
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-number"><?php echo $system_stats['users']['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-graduate stat-icon"></i>
                <div class="stat-number"><?php echo $system_stats['users']['total_students']; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher stat-icon"></i>
                <div class="stat-number"><?php echo $system_stats['users']['total_instructors']; ?></div>
                <div class="stat-label">Instructors</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-book stat-icon"></i>
                <div class="stat-number"><?php echo $system_stats['courses']['total_courses']; ?></div>
                <div class="stat-label">Active Courses</div>
            </div>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2 class="section-title">Recent Users</h2>
                <a href="manage_users.php" class="view-all">View All</a>
            </div>
            <div class="recent-grid">
                <?php foreach($recent_users as $user): ?>
                    <div class="user-card">
                        <img src="<?php echo !empty($user['profile_picture']) ? 
                            'uploads/profile_pictures/'.$user['profile_picture'] : 
                            'images/default-avatar.png'; ?>" 
                            alt="" class="user-avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-role"><?php echo ucfirst($user['profile_type']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2 class="section-title">Recent Courses</h2>
                <a href="manage_courses.php" class="view-all">View All</a>
            </div>
            <div class="recent-grid">
                <?php foreach($recent_courses as $course): ?>
                    <div class="course-card">
                        <div class="course-info">
                            <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                            <div class="course-instructor">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($course['instructor_name']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2 class="section-title">Recent Activities</h2>
            </div>
            <ul class="activity-list">
                <?php foreach($system_stats['recent_activities'] as $activity): ?>
                    <li class="activity-item">
                        <div class="activity-icon">
                            <?php
                            $icon_class = 'fas fa-';
                            switch($activity['activity_type']) {
                                case 'New User':
                                    $icon_class .= 'user-plus';
                                    break;
                                case 'New Course':
                                    $icon_class .= 'book';
                                    break;
                                case 'New Enrollment':
                                    $icon_class .= 'user-graduate';
                                    break;
                                default:
                                    $icon_class .= 'bell';
                            }
                            ?>
                            <i class="<?php echo $icon_class; ?>"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text">
                                <?php 
                                switch($activity['activity_type']) {
                                    case 'New User':
                                        echo htmlspecialchars($activity['subject_name']) . 
                                            ' joined as ' . ucfirst($activity['subject_role']);
                                        break;
                                    case 'New Course':
                                        echo 'New course "' . htmlspecialchars($activity['subject_name']) . 
                                            '" created by ' . htmlspecialchars($activity['subject_role']);
                                        break;
                                    case 'New Enrollment':
                                        echo htmlspecialchars($activity['subject_role']) . 
                                            ' enrolled in ' . htmlspecialchars($activity['subject_name']);
                                        break;
                                }
                                ?>
                            </div>
                            <div class="activity-time">
                                <?php echo date('M d, Y H:i', strtotime($activity['activity_date'])); ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>


    </div>

    <!-- System Performance Section -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">System Performance</h2>
        </div>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">CPU Usage</div>
                    <div class="metric-value"><?php echo number_format($system_metrics['system_load'] * 100, 1); ?>%</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Memory Usage</div>
                    <div class="metric-value"><?php echo number_format($system_metrics['memory_usage'], 2); ?> MB</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Database Size</div>
                    <div class="metric-value"><?php echo number_format($system_metrics['db_size'], 2); ?> MB</div>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Active Sessions</div>
                    <div class="metric-value"><?php echo $system_metrics['active_sessions']; ?></div>
                </div>
            </div>
        </div>
    </div>


    <!-- Course Analytics Section -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">Top Performing Courses</h2>
        </div>
        <div class="course-analytics">
            <?php if(!empty($course_analytics)): ?>
                <?php foreach($course_analytics as $course): ?>
                    <div class="analytics-card">
                        <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                        <div class="analytics-stats">
                            <div class="stat">
                                <span class="stat-label">Enrollments</span>
                                <span class="stat-value"><?php echo $course['enrollment_count'] ?? 0; ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Rating</span>
                                <span class="stat-value"><?php echo number_format($course['avg_grade'] ?? 0, 1); ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Assignments</span>
                                <span class="stat-value"><?php echo $course['assignment_count'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="analytics-card">
                    <p>No course analytics available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- System Alerts Section -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">System Alerts</h2>
        </div>
        <div class="alerts-container">
            <?php foreach($system_alerts as $alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <i class="fas fa-<?php echo $alert['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                    <span><?php echo $alert['message']; ?></span>
                    <button class="alert-dismiss" onclick="dismissAlert(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Admin Tools Section -->
    <div class="admin-section">
        <div class="section-header">
            <h2 class="section-title">Administrative Tools</h2>
        </div>
        <div class="tools-grid">
            <div class="tool-card">
                <i class="fas fa-database"></i>
                <h3>Database Management</h3>
                <div class="tool-actions">
                    <button onclick="backupDatabase()" class="tool-btn">Backup Database</button>
                    <button onclick="optimizeDatabase()" class="tool-btn">Optimize Tables</button>
                </div>
            </div>
            <div class="tool-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Security</h3>
                <div class="tool-actions">
                    <button onclick="viewLogs()" class="tool-btn">View Security Logs</button>
                    <button onclick="configureFirewall()" class="tool-btn">Configure Firewall</button>
                </div>
            </div>
            <div class="tool-card">
                <i class="fas fa-cogs"></i>
                <h3>System Maintenance</h3>
                <div class="tool-actions">
                    <button onclick="clearCache()" class="tool-btn">Clear Cache</button>
                    <button onclick="systemCheck()" class="tool-btn">System Health Check</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> EduSync. All rights reserved.</p>
    </footer>

    <script src="js/script.js">

        // Admin tool functions
        function backupDatabase() {
            // Implement database backup functionality
            alert('Starting database backup...');
        }

        function optimizeDatabase() {
            // Implement database optimization
            alert('Optimizing database tables...');
        }

        function viewLogs() {
            // Implement log viewer
            window.location.href = 'security_logs.php';
        }

        function configureFirewall() {
            // Implement firewall configuration
            window.location.href = 'firewall_settings.php';
        }

        function clearCache() {
            // Implement cache clearing
            fetch('clear_cache.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Cache cleared successfully!');
                    } else {
                        alert('Error clearing cache');
                    }
                });
        }

        function systemCheck() {
            // Implement system health check
            alert('Running system health check...');
        }

        function dismissAlert(button) {
            button.closest('.alert').remove();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts here
        });
    </script>

</body>
</html>