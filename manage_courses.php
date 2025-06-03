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

    // Handle course actions
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST['action'])) {
            switch($_POST['action']) {
                case 'delete':
                    if(isset($_POST['course_id'])) {
                        $sql = "DELETE FROM courses WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$_POST['course_id']]);
                    }
                    break;
                    
                case 'update_status':
                    if(isset($_POST['course_id']) && isset($_POST['status'])) {
                        $sql = "UPDATE courses SET status = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$_POST['status'], $_POST['course_id']]);
                    }
                    break;
                    
                case 'create_course':
                    $sql = "INSERT INTO courses (course_code, course_name, description, instructor_id) 
                            VALUES (?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['course_code'],
                        $_POST['course_name'],
                        $_POST['description'],
                        $_POST['instructor_id']
                    ]);
                    break;
            }
            header("Location: manage_courses.php");
            exit;
        }
    }

    // Get instructors for course creation
    $sql = "SELECT id, name FROM users WHERE profile_type = 'instructor'";
    $stmt = $pdo->query($sql);
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Base query
    $sql = "SELECT c.*, u.name as instructor_name 
            FROM courses c 
            JOIN users u ON c.instructor_id = u.id 
            WHERE 1=1";
    $count_sql = "SELECT COUNT(*) FROM courses c JOIN users u ON c.instructor_id = u.id WHERE 1=1";

    // Add search condition
    if($search) {
        $search_condition = " AND (c.course_name LIKE :search OR c.course_code LIKE :search)";
        $sql .= $search_condition;
        $count_sql .= $search_condition;
    }

    // Add pagination
    $sql .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";

    // Get total count
    $count_stmt = $pdo->prepare($count_sql);
    if($search) {
        $search_param = "%$search%";
        $count_stmt->bindParam(':search', $search_param);
    }
    $count_stmt->execute();
    $total_courses = $count_stmt->fetchColumn();
    $total_pages = ceil($total_courses / $limit);

    // Get courses
    $stmt = $pdo->prepare($sql);
    if($search) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function createCourse($pdo, $data) {
        try {
            $sql = "INSERT INTO courses (
                        course_code, 
                        course_name, 
                        description, 
                        instructor_id,
                        category,
                        duration,
                        level,
                        prerequisites,
                        status,
                        created_at
                    ) VALUES (
                        :code, 
                        :name, 
                        :description, 
                        :instructor_id,
                        :category,
                        :duration,
                        :level,
                        :prerequisites,
                        'active',
                        NOW()
                    )";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':code' => $data['course_code'],
                ':name' => $data['course_name'],
                ':description' => $data['description'],
                ':instructor_id' => $data['instructor_id'],
                ':category' => $data['category'] ?? null,
                ':duration' => $data['duration'] ?? null,
                ':level' => $data['level'] ?? 'beginner',
                ':prerequisites' => $data['prerequisites'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - EduSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">

    <style>
        .manage-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .filters {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .search-form {
            flex: 1;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.4rem;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .course-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }

        .course-info {
            flex: 1;
        }

        .course-code {
            color: var(--main-color);
            font-size: 1.4rem;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .course-name {
            font-size: 1.8rem;
            color: var(--black);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .course-instructor {
            color: var(--light-color);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .course-description {
            color: var(--light-color);
            font-size: 1.4rem;
            line-height: 1.6;
            margin-top: 15px;
        }

        .course-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: var(--main-color);
            color: var(--white);
        }

        .delete-btn {
            background: var(--red);
            color: var(--white);
        }

        .action-btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }

        .create-course-btn {
            background: var(--main-color);
            color: var(--white);
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.6rem;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .create-course-btn:hover {
            background: var(--black);
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--black);
            font-size: 1.4rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.4rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--main-color);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--main-color);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--light-bg);
            color: var(--black);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .status-active {
            background: #28a745;
            color: white;
        }

        .status-inactive {
            background: #dc3545;
            color: white;
        }

        .course-meta {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--light-color);
            font-size: 1.3rem;
        }

        .form-control.error {
            border-color: var(--red);
        }

        .error-message {
            color: var(--red);
            font-size: 1.2rem;
            margin-top: 5px;
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

    <div class="manage-container">
        <h1 class="heading">Manage Courses</h1>

        <button onclick="openCreateModal()" class="create-course-btn">
            <i class="fas fa-plus"></i> Create New Course
        </button>

        <div class="filters">
            <form class="search-form" method="GET">
                <input type="text" name="search" class="search-input" 
                    placeholder="Search by course name or code" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">Search</button>
            </form>
        </div>

        <div class="courses-grid">
            <?php foreach($courses as $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-info">
                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                            <h3 class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                            <div class="course-instructor">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($course['instructor_name']); ?>
                            </div>
                        </div>
                        <div class="course-actions">
                            <a href="course_manage.php?id=<?php echo $course['id']; ?>" class="action-btn view-btn" title="Manage Content">
                                <i class="fas fa-book"></i>
                            </a>
                            <button onclick="editCourse(<?php echo $course['id']; ?>)" class="action-btn edit-btn" title="Edit Course">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo addslashes($course['course_name']); ?>')" class="action-btn delete-btn" title="Delete Course">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p class="course-description">
                        <?php echo htmlspecialchars($course['description']); ?>
                    </p>
                    
                    <!-- Add course meta information -->
                    <div class="course-meta">
                        <?php if(!empty($course['category'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($course['duration'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i> <?php echo htmlspecialchars($course['duration']); ?> weeks
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($course['level'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-layer-group"></i> <?php echo ucfirst(htmlspecialchars($course['level'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i> Created: <?php echo date('M d, Y', strtotime($course['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>


    <!-- Create Course Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Course</h2>
                <span class="close-btn" onclick="closeModal('createModal')">&times;</span>
            </div>
            <form method="POST" id="createCourseForm" onsubmit="return validateForm()">
                <input type="hidden" name="action" value="create_course">
                
                <div class="form-group">
                    <label>Course Code*</label>
                    <input type="text" name="course_code" class="form-control" required
                        placeholder="Enter course code (e.g., CS101)">
                </div>
                
                <div class="form-group">
                    <label>Course Name*</label>
                    <input type="text" name="course_name" class="form-control" required
                        placeholder="Enter course name">
                </div>
                
                <div class="form-group">
                    <label>Description*</label>
                    <textarea name="description" class="form-control" rows="4" required
                            placeholder="Enter course description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="">Select Category</option>
                        <option value="programming">Programming</option>
                        <option value="design">Design</option>
                        <option value="business">Business</option>
                        <option value="marketing">Marketing</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Instructor*</label>
                    <select name="instructor_id" class="form-control" required>
                        <option value="">Select Instructor</option>
                        <?php foreach($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>">
                                <?php echo htmlspecialchars($instructor['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Duration (weeks)</label>
                    <input type="number" name="duration" class="form-control" min="1" max="52"
                        placeholder="Enter course duration">
                </div>

                <div class="form-group">
                    <label>Level</label>
                    <select name="level" class="form-control">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Prerequisites</label>
                    <textarea name="prerequisites" class="form-control" rows="2"
                            placeholder="Enter course prerequisites (if any)"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Course</h2>
                <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="course_id" id="editCourseId">
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>
                        
    <script>
        function validateForm() {
            const form = document.getElementById('createCourseForm');
            const courseCode = form.course_code.value.trim();
            const courseName = form.course_name.value.trim();
            const description = form.description.value.trim();
            const instructorId = form.instructor_id.value;

            // Remove any existing error messages
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));

            let isValid = true;

            // Course Code validation
            if (courseCode.length < 3 || courseCode.length > 10) {
                showError('course_code', 'Course code must be between 3 and 10 characters');
                isValid = false;
            }

            // Course Name validation
            if (courseName.length < 5) {
                showError('course_name', 'Course name must be at least 5 characters long');
                isValid = false;
            }

            // Description validation
            if (description.length < 20) {
                showError('description', 'Description must be at least 20 characters long');
                isValid = false;
            }

            // Instructor validation
            if (!instructorId) {
                showError('instructor_id', 'Please select an instructor');
                isValid = false;
            }

            return isValid;
        }

        function showError(fieldName, message) {
            const field = document.querySelector(`[name="${fieldName}"]`);
            field.classList.add('error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }

        function openCreateModal() {
            document.getElementById('createModal').style.display = 'flex';
            document.getElementById('createCourseForm').reset();
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
        }

        function editCourse(courseId, courseName, courseCode, description, status) {
            document.getElementById('editCourseId').value = courseId;
            document.getElementById('editCourseName').value = courseName;
            document.getElementById('editCourseCode').value = courseCode;
            document.getElementById('editDescription').value = description;
            document.getElementById('editStatus').value = status;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function deleteCourse(courseId, courseName) {
            if(confirm(`Are you sure you want to delete the course "${courseName}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="course_id" value="${courseId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('show', function() {
                this.style.opacity = '0';
                setTimeout(() => this.style.opacity = '1', 10);
            });
        });
    </script>
    <script src="js/script.js"></script>

</body>
</html>