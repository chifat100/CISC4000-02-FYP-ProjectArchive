<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
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

   // Get all courses with instructor and enrollment info
   $sql = "SELECT c.*, u.name as instructor_name, u.profile_picture,
         (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'active') as enrolled_students,
         (SELECT COUNT(*) FROM course_content WHERE course_id = c.id) as content_count
         FROM courses c 
         JOIN users u ON c.instructor_id = u.id 
         ORDER BY c.created_at DESC";
   $stmt = $pdo->prepare($sql);
   $stmt->execute();
   $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Courses - EduSync</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/lms.css">
   <style>
      .courses-container {
         max-width: 1200px;
         margin: 20px auto;
         padding: 20px;
      }

      .filter-bar {
         background: var(--white);
         padding: 20px;
         border-radius: 10px;
         margin-bottom: 30px;
         display: flex;
         gap: 15px;
         align-items: center;
         flex-wrap: wrap;
         box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .search-box {
         flex: 1;
         min-width: 200px;
         position: relative;
      }

      .search-box input {
         width: 100%;
         padding: 12px 20px;
         padding-left: 45px;
         border: 1px solid var(--border);
         border-radius: 30px;
         font-size: 1.6rem;
         background: var(--light-bg);
         color: var(--black);
      }

      .search-box i {
         position: absolute;
         left: 15px;
         top: 50%;
         transform: translateY(-50%);
         color: var(--light-color);
         font-size: 1.8rem;
      }

      .filter-btn {
         padding: 10px 20px;
         border: none;
         border-radius: 30px;
         background: var(--light-bg);
         color: var(--black);
         font-size: 1.4rem;
         cursor: pointer;
         transition: all 0.3s ease;
      }

      .filter-btn:hover,
      .filter-btn.active {
         background: var(--main-color);
         color: var(--white);
      }

      .courses-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
         gap: 25px;
      }

      .course-card {
         background: var(--white);
         border-radius: 10px;
         overflow: hidden;
         box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
         transition: transform 0.3s ease;
      }

      .course-card:hover {
         transform: translateY(-5px);
      }

      .course-image {
         position: relative;
         height: 200px;
         overflow: hidden;
      }

      .course-image img {
         width: 100%;
         height: 100%;
         object-fit: cover;
      }

      .course-overlay {
         position: absolute;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background: rgba(0, 0, 0, 0.5);
         display: flex;
         justify-content: center;
         align-items: center;
         opacity: 0;
         transition: opacity 0.3s ease;
      }

      .course-card:hover .course-overlay {
         opacity: 1;
      }

      .view-course-btn {
         background: var(--main-color);
         color: var(--white);
         padding: 10px 20px;
         border-radius: 30px;
         font-size: 1.4rem;
         text-decoration: none;
         transition: all 0.3s ease;
      }

      .view-course-btn:hover {
         background: var(--white);
         color: var(--main-color);
      }

      .course-content {
         padding: 20px;
      }

      .course-instructor {
         display: flex;
         align-items: center;
         gap: 10px;
         margin-bottom: 15px;
      }

      .instructor-avatar {
         width: 40px;
         height: 40px;
         border-radius: 50%;
         object-fit: cover;
      }

      .instructor-name {
         font-size: 1.4rem;
         color: var(--black);
      }

      .course-title {
         font-size: 1.8rem;
         color: var(--black);
         margin-bottom: 10px;
         line-height: 1.4;
      }

      .course-meta {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding-top: 15px;
         border-top: 1px solid var(--border);
         font-size: 1.3rem;
         color: var(--light-color);
      }

      .meta-item {
         display: flex;
         align-items: center;
         gap: 5px;
      }

      @media (max-width: 768px) {
         .courses-container {
            padding: 10px;
         }

         .filter-bar {
            flex-direction: column;
         }

         .search-box {
            width: 100%;
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
      <a href="student_WS.php"><i class="fa-solid fa-person-digging"></i><span>workspace</span></a>
      <a href="courses.php"><i class="fas fa-graduation-cap"></i><span>courses</span></a>
      <a href="forum.php"><i class="fas fa-headset"></i><span>discussion</span></a>
      <a href="blog.php"><i class="fas fa-headset"></i><span>Blog</span></a>
      </nav>
   </div>

   <div class="courses-container">
      <h1 class="heading">Our Courses</h1>

      <div class="filter-bar">
         <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search courses...">
         </div>
         <button class="filter-btn active" data-category="all">All</button>
         <button class="filter-btn" data-category="programming">Programming</button>
         <button class="filter-btn" data-category="design">Design</button>
         <button class="filter-btn" data-category="database">Database</button>
      </div>

      <div class="courses-grid">
         <?php foreach($courses as $course): ?>
         <div class="course-card">
            <div class="course-image">
               <img src="https://picsum.photos/800/600?random=<?php echo $course['id']; ?>" alt="Course Image">
               <div class="course-overlay">
                  <a href="view_course.php?id=<?php echo $course['id']; ?>" class="view-course-btn">
                     <i class="fas fa-play-circle"></i> View Course
                  </a>
               </div>
            </div>
            <div class="course-content">
               <div class="course-instructor">
                  <img src="<?php echo !empty($course['profile_picture']) ? 
                            'uploads/profile_pictures/'.$course['profile_picture'] : 
                            'images/default-avatar.png'; ?>" alt="Instructor" class="instructor-avatar">
                  <span class="instructor-name">
                     <?php echo htmlspecialchars($course['instructor_name']); ?>
                  </span>
               </div>

               <h3 class="course-title">
                  <?php echo htmlspecialchars($course['course_name']); ?>
               </h3>

               <div class="course-meta">
                  <span class="meta-item">
                     <i class="fas fa-calendar"></i>
                     <?php echo date('M d, Y', strtotime($course['created_at'])); ?>
                  </span>
                  <span class="meta-item">
                     <i class="fas fa-video"></i>
                     <?php echo $course['content_count']; ?> videos
                  </span>
                  <span class="meta-item">
                     <i class="fas fa-users"></i>
                     <?php echo $course['enrolled_students']; ?> students
                  </span>
               </div>
            </div>
         </div>
         <?php endforeach; ?>
      </div>
   </div>


   <script>
      // Search functionality
      document.getElementById('searchInput').addEventListener('input', function (e) {
         const searchTerm = e.target.value.toLowerCase();
         const courses = document.querySelectorAll('.course-card');

         courses.forEach(course => {
            const title = course.querySelector('.course-title').textContent.toLowerCase();
            const instructor = course.querySelector('.instructor-name').textContent.toLowerCase();

            if (title.includes(searchTerm) || instructor.includes(searchTerm)) {
               course.style.display = '';
            } else {
               course.style.display = 'none';
            }
         });
      });

      // Filter functionality
      const filterButtons = document.querySelectorAll('.filter-btn');
      filterButtons.forEach(button => {
         button.addEventListener('click', function () {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            const category = this.dataset.category;
            filterCourses(category);
         });
      });

      function filterCourses(category) {
         const courses = document.querySelectorAll('.course-card');
         courses.forEach(course => {
            if (category === 'all') {
               course.style.display = '';
            } else {
               course.style.display = '';
            }
         });
      }
      
   </script>

   <script src="js/script.js"></script>
</body>

</html>