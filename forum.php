<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
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


    $sql = "SELECT t.*, u.name as author_name, u.profile_picture, 
            (SELECT COUNT(*) FROM replies WHERE topic_id = t.id) as reply_count
            FROM topics t 
            JOIN users u ON t.user_id = u.id 
            ORDER BY t.is_pinned DESC, t.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_topic'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $course_title = trim($_POST['course_title']);
        
        if(!empty($title) && !empty($content)) {
            $sql = "INSERT INTO topics (user_id, title, content, course_title) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$_SESSION['id'], $title, $content, $course_title])) {
                header("location: forum.php");
                exit;
            }
        }
    }


    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_topic'])) {
        if($_SESSION['profile_type'] === 'admin') {
            $topic_id = $_POST['topic_id'];

            $pdo->beginTransaction();
            
            try {
            
                $sql = "DELETE FROM replies WHERE topic_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$topic_id]);
                
           
                $sql = "DELETE FROM topics WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$topic_id]);
                
          
                $pdo->commit();
                
              
                $_SESSION['message'] = "Topic deleted successfully";
                $_SESSION['message_type'] = "success";
                
            } catch (Exception $e) {
          
                $pdo->rollBack();
                $_SESSION['message'] = "Error deleting topic";
                $_SESSION['message_type'] = "error";
            }
            
            header("location: forum.php");
            exit;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Forum - EduSync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/forum.css">

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
            <span><?php echo ucfirst($_SESSION['profile_type']); ?></span>
        </div>
        <nav class="navbar">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="forum.php"><i class="fas fa-comments"></i><span>Forum</span></a>
            <a href="courses.php"><i class="fas fa-graduation-cap"></i><span>Courses</span></a>
            <a href="update.php"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="forum-container">
        <div class="forum-header">
            <h1 class="heading">Discussion Forum</h1>
            <button class="new-topic-btn" onclick="openNewTopicModal()">
                <i class="fas fa-plus"></i> New Topic
            </button>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="topics-list">
            <?php foreach($topics as $topic): ?>
                <div class="topic-item">
                    <div class="topic-author">
                        <img src="<?php echo !empty($topic['profile_picture']) ? 
                            'uploads/profile_pictures/'.$topic['profile_picture'] : 
                            'images/default-avatar.png'; ?>" 
                            alt="Author" class="topic-author-avatar">
                        <div class="author-name"><?php echo htmlspecialchars($topic['author_name']); ?></div>
                    </div>
                    
                    <div class="topic-content">
                        <div class="topic-header">
                            <?php if($topic['is_pinned']): ?>
                                <span class="pinned-badge">
                                    <i class="fas fa-thumbtack"></i> Pinned
                                </span>
                            <?php endif; ?>
                            
                            <a href="view_topic.php?id=<?php echo $topic['id']; ?>" class="topic-title">
                                <?php echo htmlspecialchars($topic['title']); ?>
                            </a>
                            
                            <?php if(!empty($topic['course_title'])): ?>
                                <span class="course-badge">
                                    <?php echo htmlspecialchars($topic['course_title']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if($_SESSION['profile_type'] === 'admin'): ?>
                                <div class="admin-controls">
                                    <button class="pin-btn" onclick="togglePin(<?php echo $topic['id']; ?>)" 
                                            title="<?php echo $topic['is_pinned'] ? 'Unpin topic' : 'Pin topic'; ?>">
                                        <i class="fas fa-thumbtack"></i>
                                    </button>
                                    <button class="delete-btn" onclick="confirmDelete(<?php echo $topic['id']; ?>, '<?php echo addslashes($topic['title']); ?>')" 
                                            title="Delete topic">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="topic-meta">
                            Posted on <?php echo date('M d, Y', strtotime($topic['created_at'])); ?>
                        </div>
                        
                        <div class="topic-stats">
                            <div class="stat-item">
                                <i class="fas fa-eye"></i>
                                <?php echo $topic['views']; ?> views
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-comments"></i>
                                <?php echo $topic['reply_count']; ?> replies
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- New Topic Modal -->
    <div id="newTopicModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Create New Topic</h2>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Course Title (Optional)</label>
                    <input type="text" name="course_title" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" class="form-control" required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeNewTopicModal()">Cancel</button>
                    <button type="submit" name="create_topic" class="btn btn-primary">Create Topic</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="confirm-modal">
        <div class="confirm-content">
            <h3>Delete Topic</h3>
            <p>Are you sure you want to delete this topic? This action cannot be undone.</p>
            <p class="topic-title-display"></p>
            <div class="confirm-actions">
                <button class="confirm-btn confirm-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="delete_topic" value="1">
                    <input type="hidden" name="topic_id" id="deleteTopicId">
                    <button type="submit" class="confirm-btn confirm-btn-delete">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openNewTopicModal() {
            document.getElementById('newTopicModal').style.display = 'flex';
        }

        function closeNewTopicModal() {
            document.getElementById('newTopicModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('newTopicModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function confirmDelete(topicId, topicTitle) {
            document.getElementById('deleteTopicId').value = topicId;
            document.querySelector('.topic-title-display').textContent = `"${topicTitle}"`;
            document.getElementById('deleteConfirmModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal') || event.target.classList.contains('confirm-modal')) {
                event.target.style.display = 'none';
            }
        }

        function togglePin(topicId) {

            alert('Pin/unpin functionality to be implemented');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
    <script src="js/script.js"></script>
    
</body>
</html>