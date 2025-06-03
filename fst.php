<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty of Science and Technology | University of Macau</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="css/fst.css">
</head>
<body data-theme="light">
    <!-- Header -->
    <header>
        <a href="fst.html"><img src="images/UM-with-FST-logo-1.png" alt=""></a>

        <nav>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#departments">Departments</a></li>
                <li><a href="#research">Research</a></li>
                <li><a href="#news">News</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Faculty of Science and Technology</h1>
            <p>Advancing Knowledge, Inspiring Innovation</p>
            <div class="unsw">
                <a href="umwi.php">UMFST Educational Project</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about">
        <div class="section-title">
            <h2>About FST</h2>
        </div>
        <div class="grid">
            <div class="card" data-aos="fade-up">
                <h3>Vision</h3>
                <p>To be a leading faculty in science and technology education and research.</p>
            </div>
            <div class="card" data-aos="fade-up" data-aos-delay="200">
                <h3>Mission</h3>
                <p>Providing quality education and conducting innovative research.</p>
            </div>
        </div>
    </section>

    <!-- Departments Section -->
    <section id="departments">
        <div class="section-title">
            <h2>Our Departments</h2>
        </div>
        <div class="grid">
            <div class="department-card card" data-aos="fade-up">
                <i class='bx bx-building'></i>
                <h3>Civil and Environmental Engineering</h3>
            </div>
            <div class="department-card card" data-aos="fade-up" data-aos-delay="200">
                <i class='bx bx-code-alt'></i>
                <h3>Computer and Information Science</h3>
            </div>
            <div class="department-card card" data-aos="fade-up" data-aos-delay="400">
                <i class='bx bx-chip'></i>
                <h3>Electrical and Computer Engineering</h3>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section id="news">
        <div class="section-title">
            <h2>Latest News</h2>
        </div>
        <div class="grid">
            <div class="news-card card" data-aos="fade-up">
                <img src="images/edusync.jpg">
                <h3>Latest Research Breakthrough</h3>
                <p>Our researchers made significant progress in AI technology.</p>
            </div>
            <div class="news-card card" data-aos="fade-up" data-aos-delay="200">
                <img src="images/london.png">
                <h3>Student Achievements</h3>
                <p>FST students win international competition.</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="section-title">
            <h2>Contact Us</h2>
        </div>
        <div class="contact-form card">
            <form>
                <input type="text" placeholder="Name">
                <input type="email" placeholder="Email">
                <textarea placeholder="Message" rows="5"></textarea>
                <button type="submit">Send Message</button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Faculty of Science and Technology | University of Macau</p>
    </footer>

    <div class="theme-toggle" id="theme-toggle">
        <i class='bx bx-moon'></i>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        
        AOS.init({
            duration: 1000,
            once: true
        });

        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;
        const icon = themeToggle.querySelector('i');

        themeToggle.addEventListener('click', () => {
            if (body.getAttribute('data-theme') === 'light') {
                body.setAttribute('data-theme', 'dark');
                icon.classList.replace('bx-moon', 'bx-sun');
            } else {
                body.setAttribute('data-theme', 'light');
                icon.classList.replace('bx-sun', 'bx-moon');
            }
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