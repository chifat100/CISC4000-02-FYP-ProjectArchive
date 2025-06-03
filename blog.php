<?php
session_start();
require_once "php/config.php";

// Fetch user data including profile picture
$sql = "SELECT * FROM users WHERE id = :id";
if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);
    if($stmt->execute()){
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Function to get all blog posts
function getBlogPosts($pdo, $limit = null) {
    $sql = "SELECT b.*, u.name as author_name, u.profile_picture,
            (SELECT COUNT(*) FROM blog_comments WHERE post_id = b.id) as comment_count,
            (SELECT COUNT(*) FROM blog_likes WHERE post_id = b.id) as like_count
            FROM blog_posts b
            JOIN users u ON b.author_id = u.id
            ORDER BY b.created_at DESC";
    
    if($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $pdo->prepare($sql);
    if($limit) {
        $stmt->execute([$limit]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle blog post creation
if(isset($_POST['create_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    
    // Handle image upload
    $image_path = '';
    if(isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $upload_dir = 'uploads/blog/';
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . $_FILES['post_image']['name'];
        $image_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['post_image']['tmp_name'], $image_path);
    }
    
    $sql = "INSERT INTO blog_posts (title, content, category, image_path, author_id) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $content, $category, $image_path, $_SESSION['id']]);
    
    header("Location: blog.php?success=post_created");
    exit;
}


$blog_posts = getBlogPosts($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - EduSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/lms.css">
    <style>

        .blog-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .blog-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .blog-header h1 {
            font-size: 3rem;
            color: var(--black);
            margin-bottom: 10px;
        }

        .blog-header p {
            color: var(--light-color);
            font-size: 1.6rem;
        }

        .create-post-btn {
            background: var(--main-color);
            color: var(--white);
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 1.6rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .create-post-btn:hover {
            background: var(--black);
            transform: translateY(-2px);
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .blog-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .blog-card:hover {
            transform: translateY(-5px);
        }

        .blog-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .blog-content {
            padding: 20px;
        }

        .blog-category {
            background: var(--light-bg);
            color: var(--main-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 1.2rem;
            display: inline-block;
            margin-bottom: 10px;
        }

        .blog-title {
            font-size: 2rem;
            color: var(--black);
            margin-bottom: 15px;
        }

        .blog-excerpt {
            color: var(--light-color);
            font-size: 1.4rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .blog-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .blog-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .author-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-name {
            font-size: 1.4rem;
            color: var(--black);
        }

        .blog-stats {
            display: flex;
            gap: 15px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--light-color);
            font-size: 1.3rem;
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
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--black);
            font-size: 1.4rem;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1.4rem;
        }

        .rich-editor {
            min-height: 200px;
            border: 1px solid var(--border);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .tox-tinymce {
            min-height: 400px !important;
            margin-bottom: 20px;
        }

        .modal-content {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            font-size: 2rem;
            color: var(--black);
        }

        .form-group {
            margin-bottom: 25px;
        }



        .blog-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-primary, .btn-secondary {
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

        .btn-secondary {
            background: var(--light-bg);
            color: var(--black);
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .blog-link {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
        }

        .blog-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .blog-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .blog-card:hover .blog-image {
            transform: scale(1.05);
        }

        .blog-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .blog-title {
            font-size: 2rem;
            color: var(--black);
            margin: 10px 0;
            line-height: 1.4;
            transition: color 0.3s ease;
        }

        .blog-card:hover .blog-title {
            color: var(--main-color);
        }

        .blog-excerpt {
            color: var(--light-color);
            font-size: 1.4rem;
            line-height: 1.6;
            margin-bottom: 20px;
            flex: 1;
        }

        .blog-meta {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .blog-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .author-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--main-color);
        }

        .author-name {
            font-size: 1.4rem;
            color: var(--black);
            font-weight: 500;
        }

        .blog-stats {
            display: flex;
            gap: 15px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--light-color);
            font-size: 1.3rem;
            transition: color 0.3s ease;
        }

        .stat-item:hover {
            color: var(--main-color);
        }

        .blog-category {
            background: var(--light-bg);
            color: var(--main-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 1.2rem;
            display: inline-block;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .blog-card:hover .blog-category {
            background: var(--main-color);
            color: var(--white);
        }

        @media (max-width: 768px) {
            .blog-grid {
                grid-template-columns: 1fr;
            }
            
            .blog-title {
                font-size: 1.8rem;
            }
            
            .blog-excerpt {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 768px) {
            .blog-grid {
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
            <a href="student_WS.php"><i class="fas fa-tools"></i><span>WorkingSpace</span></a>
            <a href="system_settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="blog-container">
        <div class="blog-header">
            <h1>EduSync Blog</h1>
            <p>Stay updated with the latest educational insights and resources</p>
        </div>

        <?php if($_SESSION['profile_type'] == 'instructor' || $_SESSION['profile_type'] == 'admin' || $_SESSION['profile_type'] == 'student'): ?>
            <button class="create-post-btn" onclick="openCreatePostModal()">
                <i class="fas fa-plus"></i> Create New Post
            </button>
        <?php endif; ?>

        <div class="blog-grid">
            <?php foreach($blog_posts as $post): ?>
                <div class="blog-card">
                    <?php if($post['image_path']): ?>
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="" class="blog-image">
                    <?php endif; ?>
                    
                    <div class="blog-content">
                        <span class="blog-category"><?php echo htmlspecialchars($post['category']); ?></span>
                        <h3 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="blog-excerpt">
                            <?php echo substr(strip_tags($post['content']), 0, 150) . '...'; ?>
                        </p>
                        
                        <div class="blog-meta">
                            <div class="blog-author">
                                <img src="<?php echo !empty($post['profile_picture']) ? 
                                    'uploads/profile_pictures/'.$post['profile_picture'] : 
                                    'images/default-avatar.png'; ?>" 
                                    alt="" class="author-image">
                                <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                            </div>
                            
                            <div class="blog-stats">
                                <span class="stat-item">
                                    <i class="fas fa-heart"></i>
                                    <?php echo $post['like_count']; ?>
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-comment"></i>
                                    <?php echo $post['comment_count']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="createPostModal" class="modal">
        <div class="modal-content">
            <h2>Create New Blog Post</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control" required>
                        <option value="Education">Education</option>
                        <option value="Technology">Technology</option>
                        <option value="Teaching Tips">Teaching Tips</option>
                        <option value="Student Life">Student Life</option>
                        <option value="Career Guidance">Career Guidance</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea id="editor" name="content"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Featured Image</label>
                    <input type="file" name="post_image" class="form-control" accept="image/*">
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" name="create_post" class="btn-primary">Publish Post</button>
                </div>
            </form>
        </div>
    </div>                    

    <div class="blog-card">
            
            <div class="blog-content">
                <div class="blog-grid">
                    <?php foreach($blog_posts as $post): ?>
                        <a href="blog_post.php?id=<?php echo $post['id']; ?>" class="blog-link">
                            <div class="blog-card">
                                <?php if($post['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Blog Post Image" class="blog-image">
                                <?php else: ?>
                                    <img src="images/default-blog-image.jpg" alt="Default Blog Image" class="blog-image">
                                <?php endif; ?>
                                
                                <div class="blog-content">
                                    <span class="blog-category"><?php echo htmlspecialchars($post['category']); ?></span>
                                    <h3 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <p class="blog-excerpt">
                                        <?php echo substr(strip_tags($post['content']), 0, 150) . '...'; ?>
                                    </p>
                                    
                                    <div class="blog-meta">
                                        <div class="blog-author">
                                            <img src="<?php echo !empty($post['profile_picture']) ? 
                                                'uploads/profile_pictures/'.$post['profile_picture'] : 
                                                'images/default-avatar.png'; ?>" 
                                                alt="Author" class="author-image">
                                            <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                        </div>
                                        
                                        <div class="blog-stats">
                                            <span class="stat-item">
                                                <i class="fas fa-heart"></i>
                                                <?php echo $post['like_count']; ?>
                                            </span>
                                            <span class="stat-item">
                                                <i class="fas fa-comment"></i>
                                                <?php echo $post['comment_count']; ?>
                                            </span>
                                            <span class="stat-item">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </a>
    </div>                    




    <script>
        function openCreatePostModal() {
            document.getElementById('createPostModal').style.display = 'flex';
            // Reinitialize TinyMCE when modal opens
            if (typeof tinymce !== 'undefined') {
                tinymce.execCommand('mceRemoveEditor', true, 'editor');
                tinymce.execCommand('mceAddEditor', true, 'editor');
            }
        }

        function closeModal() {
            document.getElementById('createPostModal').style.display = 'none';
            // Clean up TinyMCE instance when modal closes
            if (typeof tinymce !== 'undefined') {
                tinymce.execCommand('mceRemoveEditor', true, 'editor');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                // Clean up TinyMCE instance when modal closes
                if (typeof tinymce !== 'undefined') {
                    tinymce.execCommand('mceRemoveEditor', true, 'editor');
                }
            }
        }

        // Show success message if post was created
        <?php if(isset($_GET['success']) && $_GET['success'] == 'post_created'): ?>
            alert('Blog post created successfully!');
        <?php endif; ?>
    </script>

  
</body>
</html>