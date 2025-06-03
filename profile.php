<?php
   session_start();

   error_reporting(E_ALL);
   ini_set('display_errors', 1);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>profile</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/lms.css">
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

   <section class="user-profile">

      <h1 class="heading">your profile</h1>
      <div class="info">
         <div class="user">
            <img src="<?php 
               if(!empty($user_data['profile_picture']) && file_exists('uploads/profile_pictures/'.$user_data['profile_picture'])){
                  echo 'uploads/profile_pictures/'.$user_data['profile_picture'];
               } else {
                  echo 'images/default-avatar.png';
               }
            ?>" alt="Profile Picture">

            <h3 class="name"><?php echo htmlspecialchars($_SESSION["name"]); ?></h3>
            <p class="role"><?php echo htmlspecialchars($_SESSION["profile_type"]); ?></p>
            <a href="update.php" class="inline-btn">update profile</a>
         </div>
      
         <div class="box-container">
      
            <div class="box">
               <div class="flex">
                  <i class="fas fa-bookmark"></i>
                  <div>
                     <span>4</span>
                     <p>saved playlist</p>
                  </div>
               </div>
               <a href="#" class="inline-btn">view playlists</a>
            </div>
      
            <div class="box">
               <div class="flex">
                  <i class="fas fa-heart"></i>
                  <div>
                     <span>33</span>
                     <p>videos liked</p>
                  </div>
               </div>
               <a href="#" class="inline-btn">view liked</a>
            </div>
      
            <div class="box">
               <div class="flex">
                  <i class="fas fa-comment"></i>
                  <div>
                     <span>12</span>
                     <p>videos comments</p>
                  </div>
               </div>
               <a href="#" class="inline-btn">view comments</a>
            </div>
      
         </div>
      </div>

   </section>

   <footer class="footer">
      &copy; copyright @ 2025 by <span>EduSync</span> 
   </footer>
   <script src="js/script.js"></script>

</body>
</html>