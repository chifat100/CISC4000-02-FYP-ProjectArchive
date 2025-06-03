<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["profile_type"] !== "admin"){
        header("location: loginTest.php");
        exit;
    }

    require_once "php/config.php";

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

    // Function to get enrollment trends
    function getEnrollmentTrends($pdo) {
        $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as enrollment_count
                FROM enrollments
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get course popularity
    function getCoursePopularity($pdo) {
        $sql = "SELECT 
                c.course_name,
                COUNT(e.id) as enrollment_count
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                GROUP BY c.id
                ORDER BY enrollment_count DESC
                LIMIT 5";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get user registration trends
    function getUserRegistrationTrends($pdo) {
        $sql = "SELECT 
                profile_type,
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as user_count
                FROM users
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY profile_type, DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Function to get course completion rates
    function getCourseCompletionRates($pdo) {
        try {
            $sql = "SELECT 
                    c.course_name,
                    COUNT(e.id) as total_enrollments,
                    SUM(CASE WHEN e.completion_status = 'completed' THEN 1 ELSE 0 END) as completed_count
                    FROM courses c
                    LEFT JOIN enrollments e ON c.id = e.course_id
                    GROUP BY c.id
                    HAVING total_enrollments > 0
                    ORDER BY (completed_count/total_enrollments) DESC
                    LIMIT 5";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            return [];
        }
    }

    // Function to get instructor performance
    function getInstructorPerformance($pdo) {
        $sql = "SELECT 
                u.name as instructor_name,
                COUNT(DISTINCT c.id) as courses_count,
                COUNT(DISTINCT e.id) as total_students,
                AVG(COALESCE(c.rating, 0)) as avg_rating
                FROM users u
                LEFT JOIN courses c ON u.id = c.instructor_id
                LEFT JOIN enrollments e ON c.id = e.course_id
                WHERE u.profile_type = 'instructor'
                GROUP BY u.id
                ORDER BY avg_rating DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function calculateTotalRevenue($pdo) {
        try {
            $sql = "SELECT COUNT(*) * 100 as total_revenue FROM enrollments WHERE status = 'active'";
            $stmt = $pdo->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    function calculateActiveUsers($pdo) {
        try {
            $sql = "SELECT COUNT(*) as active_users FROM users WHERE status = 'active'";
            $stmt = $pdo->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC)['active_users'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    function calculateCompletionRate($pdo) {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_enrollments,
                    SUM(CASE WHEN completion_status = 'completed' THEN 1 ELSE 0 END) as completed_count
                    FROM enrollments";
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['total_enrollments'] > 0) {
                return round(($result['completed_count'] / $result['total_enrollments']) * 100);
            }
            return 0;
        } catch (PDOException $e) {
            return 0;
        }
    }


    // Get report data
    $enrollment_trends = getEnrollmentTrends($pdo);
    $course_popularity = getCoursePopularity($pdo);
    $user_registration_trends = getUserRegistrationTrends($pdo);
    $course_completion_rates = getCourseCompletionRates($pdo);
    $instructor_performance = getInstructorPerformance($pdo);

    // Calculate summary statistics
    $total_revenue = calculateTotalRevenue($pdo);
    $active_users = calculateActiveUsers($pdo);
    $course_completion_rate = calculateCompletionRate($pdo);

    try {
        $enrollment_trends = getEnrollmentTrends($pdo);
        $course_popularity = getCoursePopularity($pdo);
        $user_registration_trends = getUserRegistrationTrends($pdo);
        $course_completion_rates = getCourseCompletionRates($pdo);
        $instructor_performance = getInstructorPerformance($pdo);
    } catch (PDOException $e) {
        $enrollment_trends = [];
        $course_popularity = [];
        $user_registration_trends = [];
        $course_completion_rates = [];
        $instructor_performance = [];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - EduSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .summary-stats {
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
        }

        .stat-card .icon {
            font-size: 3rem;
            color: var(--main-color);
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 2.4rem;
            color: var(--black);
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 1.4rem;
            color: var(--light-color);
        }

        .chart-section {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.8rem;
            color: var(--black);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--light-bg);
            font-weight: 500;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--light-bg);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar .fill {
            height: 100%;
            background: var(--main-color);
            border-radius: 4px;
        }

        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-select {
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.4rem;
        }

        .export-btn {
            background: var(--main-color);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.4rem;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: var(--light-color);
            font-size: 1.4rem;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .rating i {
            font-size: 1.4rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1.4rem;
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
            <img src="<?php echo !empty($_SESSION['profile_picture']) ? 
                'uploads/profile_pictures/'.$_SESSION['profile_picture'] : 
                'images/default-avatar.png'; ?>" alt="">
            <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
            <span>Administrator</span>
        </div>
        <nav class="navbar">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="admin_WS.php"><i class="fas fa-tools"></i><span>Admin Panel</span></a>
            <a href="manage_users.php"><i class="fas fa-users"></i><span>Manage Users</span></a>
            <a href="manage_courses.php"><i class="fas fa-graduation-cap"></i><span>Manage Courses</span></a>
            <a href="system_settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="reports-container">
        <h1 class="heading">Reports & Analytics</h1>

        <div class="filters">
            <select class="filter-select" id="timeRange">
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 3 months</option>
                <option value="180" selected>Last 6 months</option>
            </select>
            <a href="#" class="export-btn" onclick="exportReport()">
                <i class="fas fa-download"></i> Export Report
            </a>
        </div>

        <div class="summary-stats">
            <div class="stat-card">
                <i class="fas fa-dollar-sign icon"></i>
                <div class="number">$<?php echo number_format($total_revenue); ?></div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users icon"></i>
                <div class="number"><?php echo number_format($active_users); ?></div>
                <div class="label">Active Users</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-graduation-cap icon"></i>
                <div class="number"><?php echo number_format($course_completion_rate); ?>%</div>
                <div class="label">Completion Rate</div>
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-header">
                <h2 class="chart-title">Enrollment Trends</h2>
            </div>
            <div class="chart-container">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-header">
                <h2 class="chart-title">Popular Courses</h2>
            </div>
            <?php if (!empty($course_popularity)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Enrollments</th>
                            <th>Popularity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($course_popularity as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo $course['enrollment_count']; ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="fill" style="width: <?php 
                                            $max_count = max(array_column($course_popularity, 'enrollment_count'));
                                            echo $max_count > 0 ? ($course['enrollment_count'] / $max_count) * 100 : 0;
                                        ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No course data available</p>
            <?php endif; ?>
        </div>

        <div class="chart-section">
            <div class="chart-header">
                <h2 class="chart-title">Instructor Performance</h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Instructor</th>
                        <th>Courses</th>
                        <th>Students</th>
                        <th>Avg. Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($instructor_performance as $instructor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($instructor['instructor_name']); ?></td>
                            <td><?php echo $instructor['courses_count']; ?></td>
                            <td><?php echo $instructor['total_students']; ?></td>
                            <td>
                                <div class="rating">
                                    <?php echo number_format($instructor['avg_rating'], 1); ?>
                                    <i class="fas fa-star" style="color: #FFD700;"></i>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Prepare data for enrollment chart
        const enrollmentData = {
            labels: <?php echo json_encode(array_column($enrollment_trends, 'month')); ?>,
            datasets: [{
                label: 'Enrollments',
                data: <?php echo json_encode(array_column($enrollment_trends, 'enrollment_count')); ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        // Create enrollment chart
        const enrollmentChart = new Chart(
            document.getElementById('enrollmentChart'),
            {
                type: 'line',
                data: enrollmentData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            }
        );

        // Prepare data for enrollment chart
        const enrollmentData = {
            labels: <?php echo !empty($enrollment_trends) ? json_encode(array_column($enrollment_trends, 'month')) : '[]'; ?>,
            datasets: [{
                label: 'Enrollments',
                data: <?php echo !empty($enrollment_trends) ? json_encode(array_column($enrollment_trends, 'enrollment_count')) : '[]'; ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        // Create enrollment chart only if container exists
        const chartContainer = document.getElementById('enrollmentChart');
        if (chartContainer) {
            new Chart(chartContainer, {
                type: 'line',
                data: enrollmentData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function exportReport() {
            alert('Generating report...');
        }


        document.getElementById('timeRange').addEventListener('change', function(e) {
            alert('Updating reports for ' + e.target.value + ' days...');
        });
    </script>
    <script src="js/script.js"></script>

</body>
</html>