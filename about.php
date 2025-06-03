<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - EduSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/about.css">
    <style>
        :root {
            --main-color: #8e44ad;
            --red: #e74c3c;
            --orange: #f39c12;
            --light-color: #888;
            --light-bg: #eee;
            --black: #2c3e50;
            --white: #fff;
            --border: .1rem solid rgba(0,0,0,.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            background: #f9f9f9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero-section {
            background: linear-gradient(135deg, #8e44ad11 0%, #ffffff 100%);
            padding: 80px 0;
            text-align: center;
        }

        .hero-title {
            font-size: 3.5rem;
            color: var(--black);
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.8rem;
            color: var(--light-color);
            max-width: 800px;
            margin: 0 auto 40px;
        }

        .platform-features {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.8rem;
            color: var(--black);
            text-align: center;
            margin-bottom: 50px;
        }

        .user-types-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 60px;
        }

        .user-card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-10px);
        }

        .user-icon {
            width: 70px;
            height: 70px;
            background: var(--main-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .user-icon i {
            font-size: 2.5rem;
            color: var(--white);
        }

        .user-card h3 {
            font-size: 2rem;
            color: var(--black);
            text-align: center;
            margin-bottom: 20px;
        }

        .feature-list {
            list-style: none;
            margin-bottom: 20px;
        }

        .feature-list li {
            font-size: 1.4rem;
            color: var(--light-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .feature-list li i {
            color: var(--main-color);
            margin-right: 10px;
        }

        .stats-section {
            padding: 60px 0;
            background: var(--white);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }

        .chart-container {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .achievements-section {
            padding: 80px 0;
            text-align: center;
        }

        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .achievement-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .achievement-number {
            font-size: 3rem;
            color: var(--main-color);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .achievement-label {
            font-size: 1.4rem;
            color: var(--light-color);
        }

        @media (max-width: 992px) {
            .user-types-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .achievement-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .user-types-grid {
                grid-template-columns: 1fr;
            }

            .achievement-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</head>
<body>
    <!-- Header -->
    <header>
        <nav class="nav-container">
            <a href="#" class="logo">
                <i class="fas fa-graduation-cap"></i>
                EduSync
            </a>
            <div class="nav-links">
                <a href="edusync.php">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="#team">Team</a>
                <a href="loginTest.php">Login</a>
                <a href="registerTest.php">Register</a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div data-aos="fade-up">
            <h1>Welcome to EduSync</h1>
            <p>Empowering middle school students through personalized learning experiences and comprehensive academic support.</p>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission" id="about">
        <div class="mission-container">
            <div class="mission-image" data-aos="fade-right">
                <img src="https://picsum.photos/600/400?random=1" alt="Students learning">
            </div>
            <div class="mission-content" data-aos="fade-left">
                <h2>Our Mission</h2>
                <p>At EduSync, we understand that every student learns differently. Our mission is to provide personalized make-up classes and enrichment programs that help middle school students excel in their studies and discover their passion for learning.</p>
                <p>We focus on creating a supportive environment where students can catch up on missed lessons, strengthen their understanding of core subjects, and explore new academic interests.</p>
            </div>
        </div>
    </section>

    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">About EduSync Platform</h1>
            <p class="hero-subtitle">Discover our comprehensive learning management system designed for students, instructors, and administrators to create an efficient and engaging educational environment.</p>
        </div>
    </section>

    <section class="platform-features">
        <div class="container">
            <h2 class="section-title">Platform Features by User Role</h2>
            
            <div class="user-types-grid">
                <div class="user-card">
                    <div class="user-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Student Features</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Personal Dashboard</li>
                        <li><i class="fas fa-check"></i> Course Enrollment</li>
                        <li><i class="fas fa-check"></i> Assignment Submissions</li>
                        <li><i class="fas fa-check"></i> Progress Tracking</li>
                        <li><i class="fas fa-check"></i> Course Materials Access</li>
                        <li><i class="fas fa-check"></i> Grade Viewing</li>
                        <li><i class="fas fa-check"></i> Discussion Participation</li>
                        <li><i class="fas fa-check"></i> Profile Management</li>
                    </ul>
                </div>

                <div class="user-card">
                    <div class="user-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Instructor Features</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Course Management</li>
                        <li><i class="fas fa-check"></i> Content Creation</li>
                        <li><i class="fas fa-check"></i> Assignment Creation</li>
                        <li><i class="fas fa-check"></i> Grade Management</li>
                        <li><i class="fas fa-check"></i> Student Progress Tracking</li>
                        <li><i class="fas fa-check"></i> Discussion Moderation</li>
                        <li><i class="fas fa-check"></i> Course Analytics</li>
                        <li><i class="fas fa-check"></i> Resource Management</li>
                    </ul>
                </div>

                <div class="user-card">
                    <div class="user-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Admin Features</h3>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> User Management</li>
                        <li><i class="fas fa-check"></i> Course Oversight</li>
                        <li><i class="fas fa-check"></i> System Configuration</li>
                        <li><i class="fas fa-check"></i> Analytics Dashboard</li>
                        <li><i class="fas fa-check"></i> Role Management</li>
                        <li><i class="fas fa-check"></i> Content Moderation</li>
                        <li><i class="fas fa-check"></i> System Reports</li>
                        <li><i class="fas fa-check"></i> Security Controls</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <h2 class="section-title">Platform Statistics</h2>
            
            <div class="stats-grid">
                <div class="chart-container">
                    <canvas id="userActivityChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <canvas id="courseProgressChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    <section class="achievements-section">
        <div class="container">
            <h2 class="section-title">Platform Achievements</h2>
            
            <div class="achievement-grid">
                <div class="achievement-card">
                    <div class="achievement-number">10,000+</div>
                    <div class="achievement-label">Active Students</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-number">500+</div>
                    <div class="achievement-label">Expert Instructors</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-number">1,000+</div>
                    <div class="achievement-label">Online Courses</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-number">95%</div>
                    <div class="achievement-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="services-container">
            <div class="section-title" data-aos="fade-up">
                <h2>Our Services</h2>
                <p>We offer comprehensive academic support tailored to middle school students' needs</p>
            </div>
            <div class="services-grid">
                <div class="service-card" data-aos="fade-up">
                    <div class="service-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Make-up Classes</h3>
                    <p>Catch up on missed lessons with our personalized make-up classes in core subjects like Math, Science, and English.</p>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3>Homework Support</h3>
                    <p>Get expert help with homework assignments and develop better study habits.</p>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Enrichment Programs</h3>
                    <p>Explore new subjects and develop additional skills through our enrichment programs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-us">
        <div class="why-us-container">
            <div class="section-title" data-aos="fade-up">
                <h2>Why Choose EduSync?</h2>
                <p>We provide a unique learning experience tailored to middle school students</p>
            </div>
            <div class="features-grid">
                <div class="feature-card" data-aos="fade-up">
                    <i class="fas fa-user-graduate feature-icon"></i>
                    <h3>Expert Teachers</h3>
                    <p>Experienced educators specialized in middle school education</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-users feature-icon"></i>
                    <h3>Small Class Sizes</h3>
                    <p>Personalized attention with maximum 6 students per class</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <i class="fas fa-clock feature-icon"></i>
                    <h3>Flexible Schedule</h3>
                    <p>Classes available after school and on weekends</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <i class="fas fa-chart-line feature-icon"></i>
                    <h3>Progress Tracking</h3>
                    <p>Regular progress reports and parent updates</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team" id="team">
        <div class="team-container">
            <div class="section-title" data-aos="fade-up">
                <h2>Our Teaching Team</h2>
                <p>Meet our dedicated educators who make learning engaging and effective</p>
            </div>
            <div class="team-grid">
                <div class="team-card" data-aos="fade-up">
                    <div class="team-image">
                        <img src="https://picsum.photos/400/400?random=2" alt="Team Member">
                    </div>
                    <div class="team-info">
                        <h3>Sarah Johnson</h3>
                        <p>Mathematics Specialist</p>
                    </div>
                </div>
                <div class="team-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="team-image">
                        <img src="https://picsum.photos/400/400?random=3" alt="Team Member">
                    </div>
                    <div class="team-info">
                        <h3>Michael Chen</h3>
                        <p>Science Expert</p>
                    </div>
                </div>
                <div class="team-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="team-image">
                        <img src="https://picsum.photos/400/400?random=4" alt="Team Member">
                    </div>
                    <div class="team-info">
                        <h3>Emily Parker</h3>
                        <p>English Language Arts</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About EduSync</h3>
                <p>Empowering middle school students through innovative education and personalized learning experiences.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p><a href="#about">About Us</a></p>
                <p><a href="#services">Services</a></p>
                <p><a href="#team">Our Team</a></p>
                <p><a href="registerTest.php">Register</a></p>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-phone"></i> +1 234 567 890</p>
                <p><i class="fas fa-envelope"></i> info@edusync.com</p>
                <p><i class="fas fa-map-marker-alt"></i> 123 Education St, Learning City</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2023 EduSync. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // User Activity Chart
        const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
        new Chart(userActivityCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Student Activity',
                    data: [65, 72, 78, 85, 82, 90],
                    borderColor: '#8e44ad',
                    tension: 0.4
                }, {
                    label: 'Instructor Activity',
                    data: [45, 55, 60, 58, 65, 70],
                    borderColor: '#e74c3c',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'User Activity Trends'
                    }
                }
            }
        });

        // Course Progress Chart
        const courseProgressCtx = document.getElementById('courseProgressChart').getContext('2d');
        new Chart(courseProgressCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [45, 35, 20],
                    backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Course Completion Status'
                    }
                }
            }
        });
    </script>
</body>
</html>