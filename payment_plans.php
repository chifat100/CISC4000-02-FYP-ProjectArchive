
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
            --primary-color: #4A90E2;
            --secondary-color: #2C3E50;
            --accent-color: #E74C3C;
            --text-color: #333333;
            --light-gray: #F5F6FA;
            --white: #FFFFFF;
        }

        .pricing-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .pricing-header h1 {
            font-size: 3.5rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .pricing-header p {
            font-size: 1.8rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }

        .pricing-card {
            background: var(--white);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .pricing-card.featured {
            border: 3px solid var(--primary-color);
        }

        .featured-label {
            position: absolute;
            top: 20px;
            right: -35px;
            background: var(--primary-color);
            color: var(--white);
            padding: 8px 40px;
            font-size: 1.4rem;
            transform: rotate(45deg);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .plan-name {
            font-size: 2.8rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .plan-description {
            font-size: 1.6rem;
            color: #666;
            margin-bottom: 30px;
        }

        .plan-price {
            font-size: 4.2rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .plan-price span {
            font-size: 1.8rem;
            color: #666;
            font-weight: normal;
        }

        .feature-list {
            list-style: none;
            margin: 30px 0;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.6rem;
            color: #555;
            margin-bottom: 20px;
            padding: 10px 0;
        }

        .feature-list i {
            color: var(--primary-color);
            font-size: 1.8rem;
        }

        .select-plan-btn {
            display: inline-block;
            width: 100%;
            padding: 18px;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .select-plan-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .select-plan-btn.outlined {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .select-plan-btn.outlined:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .guarantee-section {
            text-align: center;
            margin-top: 80px;
            padding: 60px;
            background: var(--light-gray);
            border-radius: 20px;
        }

        .guarantee-section i {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 30px;
        }

        .guarantee-section h2 {
            font-size: 2.8rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .guarantee-section p {
            font-size: 1.8rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin: 60px 0;
            text-align: center;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 3.2rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.6rem;
            color: #666;
        }

        @media (max-width: 968px) {
            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .pricing-card.featured {
                transform: scale(1);
            }

            .stats-section {
                grid-template-columns: 1fr;
                gap: 20px;
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
                <a href="edusync.php">Home</a>
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

    <div class="pricing-container">
        <div class="pricing-header">
            <h1>Choose Your Learning Plan</h1>
            <p>Transform your educational journey with our flexible pricing plans designed to meet your learning needs</p>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
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

        <div class="pricing-grid">
            <!-- Personal Plan -->
            <div class="pricing-card featured">
                <span class="featured-label">Most Popular</span>
                <h2 class="plan-name">Personal Plan</h2>
                <p class="plan-description">Perfect for individual learners</p>
                <div class="plan-price">
                    $13.99<span>/month</span>
                </div>
                <p class="plan-duration">Cancel anytime • Monthly billing</p>
                <a href="checkout.php?plan=personal" class="select-plan-btn">
                    Start 7-day free trial
                </a>
            </div>

            <!-- Single Course Purchase -->
            <div class="pricing-card">
                <h2 class="plan-name">Single Course</h2>
                <p class="plan-description">Buy individual courses</p>
                <div class="plan-price">
                    $19.99<span>-$199.99</span>
                </div>
                <p class="plan-duration">One-time payment • Lifetime access</p>
                <a href="courses.php" class="select-plan-btn outlined">
                    Browse Courses
                </a>
            </div>
        </div>

        <div class="guarantee-section">
            <i class="fas fa-shield-alt"></i>
            <h2>30-Day Money-Back Guarantee</h2>
            <p>If you're not completely satisfied with our platform within the first 30 days, we'll provide a full refund. Your success and satisfaction are our top priorities.</p>
        </div>
    </div>
    
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