
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduSync - Empowering Education</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/edusync.css">

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

        .intro-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #8e44ad11 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 15s infinite;
        }

        .shape-1 { top: 10%; left: 5%; }
        .shape-2 { top: 40%; right: 10%; animation-delay: 2s; }
        .shape-3 { bottom: 20%; left: 15%; animation-delay: 4s; }
        .shape-4 { bottom: 30%; right: 20%; animation-delay: 6s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(0) rotate(0deg); }
            75% { transform: translateY(20px) rotate(-5deg); }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .intro-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            align-items: center;
        }

        .intro-content h1 {
            font-size: 3.5rem;
            color: var(--black);
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .intro-content p {
            font-size: 1.6rem;
            color: var(--light-color);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .user-types {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .user-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .user-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .user-icon {
            width: 80px;
            height: 80px;
            background: var(--main-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .user-icon i {
            font-size: 3rem;
            color: var(--white);
        }

        .user-card h3 {
            font-size: 2rem;
            color: var(--black);
            margin-bottom: 15px;
        }

        .user-card p {
            font-size: 1.4rem;
            color: var(--light-color);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .join-btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--main-color);
            color: var(--white);
            border-radius: 25px;
            font-size: 1.4rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .join-btn:hover {
            background: var(--black);
            transform: translateY(-2px);
        }

        .stats-section {
            background: var(--white);
            padding: 40px 0;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            color: var(--main-color);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.4rem;
            color: var(--light-color);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .feature-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--main-color);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.8rem;
            color: var(--black);
            margin-bottom: 15px;
        }

        .feature-card p {
            font-size: 1.4rem;
            color: var(--light-color);
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .intro-grid {
                grid-template-columns: 1fr;
            }

            .user-types {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <a href="#home">Home</a>
                <a href="#features">Features</a>
                <a href="#programs">Programs</a>
                <a href="about.php">About</a>
                <a href="#contact">Contact</a>
                <a href="payment_plans.php">Join Our Plan</a>
            </div>
            <div class="auth-buttons">
                <a href="loginTest.php" class="btn btn-login">Login</a>
                <a href="registerTest.php" class="btn btn-register">Register</a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text" data-aos="fade-right">
                <h1>Transform Your Future with EduSync</h1>
                <p>Join our innovative learning platform and unlock your potential through personalized education,
                    expert mentorship, and cutting-edge resources.</p>
                <a href="registerTest.php" class="btn btn-register">Get Started Today</a>
            </div>
            <div class="hero-image" data-aos="fade-left">
                <img src="https://picsum.photos/600/400?random=1" alt="Education">
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-title" data-aos="fade-up">
            <h2>Why Choose EduSync?</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up">
                <i class="fas fa-laptop-code feature-icon"></i>
                <h3>Interactive Learning</h3>
                <p>Engage with our interactive platform designed to make learning enjoyable and effective.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-users feature-icon"></i>
                <h3>Expert Instructors</h3>
                <p>Learn from industry professionals with years of experience in their respective fields.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-clock feature-icon"></i>
                <h3>Flexible Schedule</h3>
                <p>Study at your own pace with our flexible learning programs and schedules.</p>
            </div>
        </div>
    </section>


    <section class="intro-section">
        <div class="floating-shapes">
            <div class="shape shape-1">🎓</div>
            <div class="shape shape-2">📚</div>
            <div class="shape shape-3">💡</div>
            <div class="shape shape-4">✏️</div>
        </div>

        <div class="container">
            <div class="intro-grid">
                <div class="intro-content">
                    <h1>Join EduSync: Where Learning Meets Innovation</h1>
                    <p>Transform your educational journey with EduSync's cutting-edge learning platform. Whether you're a student seeking knowledge or an instructor ready to share expertise, we provide the tools and community to help you succeed.</p>
                    
                    <div class="user-types">
                        <div class="user-card">
                            <div class="user-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3>For Students</h3>
                            <p>Access quality education, interact with expert instructors, and learn at your own pace. Enhance your skills with our interactive courses and personalized learning paths.</p>
                            <a href="register.php?type=student" class="join-btn">Join as Student</a>
                        </div>
                        
                        <div class="user-card">
                            <div class="user-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h3>For Instructors</h3>
                            <p>Share your knowledge, build your teaching portfolio, and connect with eager learners worldwide. Enjoy our robust teaching tools and supportive community.</p>
                            <a href="register.php?type=instructor" class="join-btn">Join as Instructor</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">10,000+</div>
                    <div class="stat-label">Active Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Expert Instructors</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">1,000+</div>
                    <div class="stat-label">Online Courses</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">95%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Interactive Learning</h3>
                    <p>Engage with interactive content, live sessions, and hands-on projects designed to enhance your learning experience.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Flexible Schedule</h3>
                    <p>Learn at your own pace with 24/7 access to course materials and recorded sessions.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Certified Courses</h3>
                    <p>Earn recognized certificates upon course completion to boost your professional credentials.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Programs Section -->
    <section class="programs" id="programs">
        <div class="section-title" data-aos="fade-up">
            <h2>Our Programs</h2>
        </div>
        <div class="programs-grid">
            <div class="program-card" data-aos="fade-up">
                <div class="program-image">
                    <img src="https://picsum.photos/600/400?random=2" alt="Program 1">
                </div>
                <div class="program-content">
                    <h3>Web Development</h3>
                    <p>Master the art of web development with our comprehensive program.</p>
                    <a href="registerTest.php" class="btn btn-register">Enroll Now</a>
                </div>
            </div>
            <div class="program-card" data-aos="fade-up" data-aos-delay="100">
                <div class="program-image">
                    <img src="https://picsum.photos/600/400?random=3" alt="Program 2">
                </div>
                <div class="program-content">
                    <h3>Data Science</h3>
                    <p>Dive into the world of data analysis and machine learning.</p>
                    <a href="registerTest.php" class="btn btn-register">Enroll Now</a>
                </div>
            </div>
            <div class="program-card" data-aos="fade-up" data-aos-delay="200">
                <div class="program-image">
                    <img src="https://picsum.photos/600/400?random=4" alt="Program 3">
                </div>
                <div class="program-content">
                    <h3>Digital Marketing</h3>
                    <p>Learn to create and execute effective digital marketing strategies.</p>
                    <a href="registerTest.php" class="btn btn-register">Enroll Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About EduSync</h3>
                <p>Empowering learners worldwide through innovative education and technology.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p><a href="#home">Home</a></p>
                <p><a href="#features">Features</a></p>
                <p><a href="#programs">Programs</a></p>
                <p><a href="#about">About</a></p>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: info@edusync.com</p>
                <p>Phone: +1 234 567 890</p>
                <p>Address: 123 Education St, Learning City</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2023 EduSync. All rights reserved.</p>
        </div>
    </footer>

    <div class="dark-mode-toggle">
        <i class="fas fa-moon"></i>
    </div>


    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>

        AOS.init({
            duration: 1000,
            once: true
        });

        const darkModeToggle = document.querySelector('.dark-mode-toggle');
        const body = document.body;

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const icon = darkModeToggle.querySelector('i');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
    
</body>

</html>