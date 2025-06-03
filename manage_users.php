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

    $message = '';
    $message_type = '';

    if(isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $message_type = $_SESSION['message_type'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }


    // Handle user actions
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST['action'])) {
            switch($_POST['action']) {
                case 'delete':
                    if(isset($_POST['user_id'])) {
                        $sql = "DELETE FROM users WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$_POST['user_id']]);
                    }
                    break;
                    
                case 'update_user':
                    if(isset($_POST['user_id'])) {
                        $sql = "UPDATE users 
                            SET profile_type = ?, 
                                status = ?
                            WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $_POST['profile_type'],
                            $_POST['status'],
                            $_POST['user_id']
                        ]);
                    }
                    break;
            }
            header("Location: manage_users.php");
            exit;
        }

        if($stmt->execute()) {
            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating user.";
            $_SESSION['message_type'] = "error";
        }
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Search functionality
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $role_filter = isset($_GET['role']) ? $_GET['role'] : '';

    $sql = "SELECT *, COALESCE(status, 'active') as status FROM users WHERE 1=1";
    $count_sql = "SELECT COUNT(*) FROM users WHERE 1=1";
    

    if($search) {
        $search_condition = " AND (name LIKE :search OR email LIKE :search)";
        $sql .= $search_condition;
        $count_sql .= $search_condition;
    }

    if($role_filter) {
        $role_condition = " AND profile_type = :role";
        $sql .= $role_condition;
        $count_sql .= $role_condition;
    }

    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    // Get total count
    $count_stmt = $pdo->prepare($count_sql);
    if($search) {
        $search_param = "%$search%";
        $count_stmt->bindParam(':search', $search_param);
    }
    if($role_filter) {
        $count_stmt->bindParam(':role', $role_filter);
    }
    $count_stmt->execute();
    $total_users = $count_stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);

    // Get users
    $stmt = $pdo->prepare($sql);
    if($search) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }
    if($role_filter) {
        $stmt->bindParam(':role', $role_filter);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - EduSync</title>
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

        .filter-select {
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.4rem;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
        }

        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .users-table th {
            background: var(--light-bg);
            font-weight: 500;
            color: var(--black);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem;
            margin-right: 5px;
        }

        .edit-btn {
            background: var(--main-color);
            color: var(--white);
        }

        .delete-btn {
            background: var(--red);
            color: var(--white);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
        }

        .status-active {
            background: #28a745;
            color: white;
        }

        .status-inactive {
            background: #dc3545;
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .page-link {
            padding: 8px 15px;
            border: 1px solid var(--border);
            border-radius: 5px;
            color: var(--main-color);
            text-decoration: none;
        }

        .page-link.active {
            background: var(--main-color);
            color: var(--white);
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
        }

        .modal-content {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-btn {
            font-size: 2rem;
            cursor: pointer;
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
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.4rem;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 1.4rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .user-email {
            font-size: 1.2rem;
            color: var(--light-color);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-secondary {
            background: var(--light-color);
        }

        .btn-danger {
            background: var(--red);
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
                    echo 'images/pic-1.jpg'; // Create a default avatar image
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

    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="manage-container">
        <h1 class="heading">Manage Users</h1>

        <div class="filters">
            <form class="search-form" method="GET">
                <input type="text" name="search" class="search-input" 
                    placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                <select name="role" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                    <option value="instructor" <?php echo $role_filter === 'instructor' ? 'selected' : ''; ?>>Instructors</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                </select>
                <button type="submit" class="btn">Filter</button>
            </form>
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?php echo !empty($user['profile_picture']) ? 
                                    'uploads/profile_pictures/'.$user['profile_picture'] : 
                                    'images/default-avatar.png'; ?>" 
                                    class="user-avatar" alt="">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['profile_type']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <button onclick="editUser(<?php echo $user['id']; ?>, 
                                                    '<?php echo $user['profile_type']; ?>', 
                                                    '<?php echo $user['status']; ?>')" 
                                            class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="action-btn delete-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="profile_type" class="form-control" id="editRole">
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" id="editStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function editUser(userId, role, status) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editRole').value = role;
            document.getElementById('editStatus').value = status || 'active';
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteUser(userId) {
            if(confirm('Are you sure you want to delete this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <script src="js/script.js"></script>

</body>
</html>