<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: loginTest.php");
        exit;
    }

    require_once "php/config.php";

    // Get topic ID
    $topic_id = isset($_GET['id']) ? $_GET['id'] : 0;

    // Update view count
    $sql = "UPDATE topics SET views = views + 1 WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$topic_id]);

    // Get topic details with author info
    $sql = "SELECT t.*, u.name as author_name, u.profile_picture, u.profile_type 
            FROM topics t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$topic) {
        header("location: forum.php");
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

    // Get replies with user info
    $sql = "SELECT r.*, u.name as author_name, u.profile_picture, u.profile_type 
            FROM replies r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.topic_id = ? 
            ORDER BY r.is_solution DESC, r.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$topic_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle new reply
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reply'])) {
        $content = trim($_POST['content']);
        
        if(!empty($content)) {
            $sql = "INSERT INTO replies (topic_id, user_id, content) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$topic_id, $_SESSION['id'], $content])) {
                header("location: view_topic.php?id=" . $topic_id);
                exit;
            }
        }
    }

    // Handle marking solution
    if(isset($_POST['mark_solution']) && $_SESSION['id'] == $topic['user_id']) {
        $reply_id = $_POST['reply_id'];

        $sql = "UPDATE replies SET is_solution = 0 WHERE topic_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$topic_id]);

        $sql = "UPDATE replies SET is_solution = 1 WHERE id = ? AND topic_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reply_id, $topic_id]);
        
        header("location: view_topic.php?id=" . $topic_id);
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - EduSync Forum</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <style>
        .topic-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .topic-header {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-link {
            color: var(--main-color);
            font-size: 1.6rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }

        .topic-title {
            font-size: 2.4rem;
            color: var(--black);
            margin-bottom: 15px;
        }

        .topic-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            color: var(--light-color);
            font-size: 1.4rem;
        }

        .post-container {
            background: var(--white);
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .post-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-info {
            flex: 1;
        }

        .author-name {
            font-size: 1.6rem;
            color: var(--black);
            margin-bottom: 5px;
        }

        .author-role {
            font-size: 1.3rem;
            color: var(--main-color);
        }

        .post-content {
            padding: 20px;
            font-size: 1.6rem;
            color: var(--black);
            line-height: 1.6;
        }

        .post-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--light-color);
            font-size: 1.3rem;
        }

        .reply-form {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--black);
            font-size: 1.6rem;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.6rem;
            background: var(--light-bg);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background: var(--main-color);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1.6rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--black);
        }

        .solution-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .mark-solution-btn {
            background: none;
            border: none;
            color: var(--main-color);
            cursor: pointer;
            font-size: 1.4rem;
            padding: 5px 10px;
            transition: all 0.3s ease;
        }

        .mark-solution-btn:hover {
            color: #28a745;
        }

        .course-badge {
            background: var(--main-color);
            color: var(--white);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 1.2rem;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .topic-container {
                padding: 10px;
            }

            .topic-title {
                font-size: 2rem;
            }

            .post-header {
                flex-direction: column;
                text-align: center;
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

    <div class="topic-container">
        <a href="forum.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Forum
        </a>

        <div class="topic-header">
            <h1 class="topic-title">
                <?php if(!empty($topic['course_title'])): ?>
                    <span class="course-badge"><?php echo htmlspecialchars($topic['course_title']); ?></span>
                <?php endif; ?>
                <?php echo htmlspecialchars($topic['title']); ?>
            </h1>
            <div class="topic-meta">
                <span><i class="fas fa-eye"></i> <?php echo $topic['views']; ?> views</span>
                <span><i class="fas fa-comments"></i> <?php echo count($replies); ?> replies</span>
                <span><i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($topic['created_at'])); ?></span>
            </div>
        </div>

        <!-- Original Post -->
        <div class="post-container">
            <div class="post-header">
                <img src="<?php echo !empty($topic['profile_picture']) ? 
                    'uploads/profile_pictures/'.$topic['profile_picture'] : 
                    'images/default-avatar.png'; ?>" 
                    alt="Author" class="author-avatar">
                <div class="author-info">
                    <div class="author-name"><?php echo htmlspecialchars($topic['author_name']); ?></div>
                    <div class="author-role"><?php echo ucfirst($topic['profile_type']); ?></div>
                </div>
            </div>
            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
            </div>
            <div class="post-footer">
                <span>Posted on <?php echo date('M d, Y H:i', strtotime($topic['created_at'])); ?></span>
            </div>
        </div>

        <!-- Replies -->
        <?php foreach($replies as $reply): ?>
            <div class="post-container">
                <div class="post-header">
                    <img src="<?php echo !empty($reply['profile_picture']) ? 
                        'uploads/profile_pictures/'.$reply['profile_picture'] : 
                        'images/default-avatar.png'; ?>" 
                        alt="Author" class="author-avatar">
                    <div class="author-info">
                        <div class="author-name"><?php echo htmlspecialchars($reply['author_name']); ?></div>
                        <div class="author-role"><?php echo ucfirst($reply['profile_type']); ?></div>
                    </div>
                    <?php if($reply['is_solution']): ?>
                        <span class="solution-badge">
                            <i class="fas fa-check-circle"></i> Solution
                        </span>
                    <?php endif; ?>
                </div>
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                </div>
                <div class="post-footer">
                    <span>Posted on <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?></span>
                    <?php if($_SESSION['id'] == $topic['user_id'] && !$reply['is_solution']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                            <button type="submit" name="mark_solution" class="mark-solution-btn">
                                <i class="fas fa-check-circle"></i> Mark as Solution
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Reply Form -->
        <?php if(!$topic['is_locked']): ?>
            <div class="reply-form">
                <h3>Leave a Reply</h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="content">Your Reply</label>
                        <textarea id="content" name="content" class="form-control" required></textarea>
                    </div>
                    <button type="submit" name="submit_reply" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Submit Reply
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="post-container">
                <div class="post-content" style="text-align: center;">
                    <i class="fas fa-lock"></i> This topic is locked. New replies are not allowed.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> EduSync. All rights reserved.</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>