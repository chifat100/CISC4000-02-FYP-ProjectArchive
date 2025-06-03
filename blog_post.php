<?php
session_start();
require_once "php/config.php";

// Get post ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch post details
$sql = "SELECT b.*, u.name as author_name, u.profile_picture,
        (SELECT COUNT(*) FROM blog_comments WHERE post_id = b.id) as comment_count,
        (SELECT COUNT(*) FROM blog_likes WHERE post_id = b.id) as like_count
        FROM blog_posts b
        JOIN users u ON b.author_id = u.id
        WHERE b.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch comments
$sql = "SELECT c.*, u.name as commenter_name, u.profile_picture
        FROM blog_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle comment submission
if(isset($_POST['submit_comment'])) {
    $comment = trim($_POST['comment']);
    $sql = "INSERT INTO blog_comments (post_id, user_id, comment) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id, $_SESSION['id'], $comment]);
    header("Location: blog_post.php?id=" . $post_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - EduSync Blog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <style>
        .post-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }

        .post-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .post-title {
            font-size: 3.5rem;
            color: var(--black);
            margin-bottom: 20px;
        }

        .post-meta {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .post-content {
            font-size: 1.6rem;
            line-height: 1.8;
            color: var(--black);
            margin-bottom: 40px;
        }

        .comments-section {
            margin-top: 50px;
        }

        .comment-form {
            margin-bottom: 30px;
        }

        .comment-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .comment {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .comment-content {
            font-size: 1.4rem;
            color: var(--black);
        }

        .comment-time {
            font-size: 1.2rem;
            color: var(--light-color);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: var(--main-color);
            color: var(--white);
            border-radius: 5px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--black);
            transform: translateY(-2px);
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1.4rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--main-color);
            box-shadow: 0 0 0 2px rgba(var(--main-color-rgb), 0.1);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.4rem;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--main-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--black);
            transform: translateY(-2px);
        }

        .author-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .commenter-name {
            font-size: 1.4rem;
            font-weight: 500;
            color: var(--black);
        }

        .post-meta > span {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--light-color);
            font-size: 1.4rem;
        }

        .post-category {
            background: var(--light-bg);
            color: var(--main-color);
            padding: 5px 15px;
            border-radius: 20px;
        }

        .post-content {
            font-size: 1.6rem;
            line-height: 1.8;
            color: var(--black);
            margin-bottom: 40px;
        }

        .post-content p {
            margin-bottom: 20px;
        }

        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }

        .comments-section h2 {
            font-size: 2.4rem;
            color: var(--black);
            margin-bottom: 20px;
        }

        .comment {
            transition: transform 0.3s ease;
        }

        .comment:hover {
            transform: translateX(5px);
        }

        @media (max-width: 768px) {
            .post-container {
                padding: 15px;
            }

            .post-title {
                font-size: 2.8rem;
            }

            .post-meta {
                flex-direction: column;
                gap: 10px;
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
            <span><?php echo htmlspecialchars($_SESSION['profile_type']); ?></span>
        </div>
        <nav class="navbar">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
            <?php if($_SESSION['profile_type'] == 'admin'): ?>
                <a href="admin_WS.php"><i class="fas fa-tools"></i><span>Admin Panel</span></a>
                <a href="manage_users.php"><i class="fas fa-users"></i><span>Manage Users</span></a>
                <a href="manage_courses.php"><i class="fas fa-graduation-cap"></i><span>Manage Courses</span></a>
                <a href="system_settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
            <?php endif; ?>
            <a href="blog.php"><i class="fas fa-blog"></i><span>Blog</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="post-container">
        <a href="blog.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>

        <div class="post-header">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                <div class="post-author">
                    <img src="<?php echo !empty($post['profile_picture']) ? 
                        'uploads/profile_pictures/'.$post['profile_picture'] : 
                        'images/default-avatar.png'; ?>" 
                        alt="" class="author-image">
                    <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                </div>
                <span class="post-date">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                </span>
                <span class="post-category">
                    <i class="fas fa-tag"></i>
                    <?php echo htmlspecialchars($post['category']); ?>
                </span>
            </div>
        </div>

        <?php if($post['image_path']): ?>
            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="" class="post-image">
        <?php endif; ?>

        <div class="post-content">
            <?php echo $post['content']; ?>
        </div>

        <div class="comments-section">
            <h2>Comments (<?php echo count($comments); ?>)</h2>
            
            <div class="comment-form">
                <form method="POST">
                    <div class="form-group">
                        <textarea name="comment" class="form-control" rows="4" placeholder="Write a comment..." required></textarea>
                    </div>
                    <button type="submit" name="submit_comment" class="btn btn-primary">Post Comment</button>
                </form>
            </div>

            <div class="comment-list">
                <?php foreach($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <img src="<?php echo !empty($comment['profile_picture']) ? 
                                'uploads/profile_pictures/'.$comment['profile_picture'] : 
                                'images/default-avatar.png'; ?>" 
                                alt="" class="author-image">
                            <div>
                                <div class="commenter-name"><?php echo htmlspecialchars($comment['commenter_name']); ?></div>
                                <div class="comment-time"><?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="comment-content">
                            <?php echo htmlspecialchars($comment['comment']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>

        let sidebar = document.querySelector('.side-bar');
        document.querySelector('#menu-btn').onclick = () => {
            sidebar.classList.toggle('active');
        }

        document.querySelector('#close-btn').onclick = () => {
            sidebar.classList.remove('active');
        }


        let searchForm = document.querySelector('.search-form');
        document.querySelector('#search-btn').onclick = () => {
            searchForm.classList.toggle('active');
        }


        let profile = document.querySelector('.header .flex .profile');
        document.querySelector('#user-btn').onclick = () => {
            profile.classList.toggle('active');
        }

        let body = document.body;
        document.querySelector('#toggle-btn').onclick = () => {
            body.classList.toggle('dark');
            if(body.classList.contains('dark')) {
                document.querySelector('#toggle-btn').classList.replace('fa-sun', 'fa-moon');
            } else {
                document.querySelector('#toggle-btn').classList.replace('fa-moon', 'fa-sun');
            }
        }

        window.onclick = function(event) {
            if (!event.target.matches('.profile') && !event.target.matches('#user-btn')) {
                profile.classList.remove('active');
            }
            if (!event.target.matches('.search-form') && !event.target.matches('#search-btn')) {
                searchForm.classList.remove('active');
            }
        }
    </script>
</body>


</html>