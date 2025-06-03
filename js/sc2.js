// DOM Elements
const toggleBtn = document.getElementById('toggle-btn');
const body = document.body;
const profile = document.querySelector('.header .flex .profile');
const search = document.querySelector('.header .flex .search-form');
const sideBar = document.querySelector('.side-bar');
const userBtn = document.querySelector('#user-btn');
const searchBtn = document.querySelector('#search-btn');
const menuBtn = document.querySelector('#menu-btn');
const closeBtn = document.querySelector('#close-btn');
const loginForm = document.querySelector('.login-form');
const registerForm = document.querySelector('.register-form');

// Theme Management
let darkMode = localStorage.getItem('dark-mode');

const enableDarkMode = () => {
    toggleBtn.classList.replace('fa-sun', 'fa-moon');
    body.classList.add('dark');
    localStorage.setItem('dark-mode', 'enabled');
    
    // Additional theme-specific adjustments
    document.querySelectorAll('.card').forEach(card => {
        card.style.boxShadow = '3px 3px 6px #1a1a1a, -3px -3px 6px #2c2c2c';
    });
    
    // Update text colors for dark mode
    document.querySelectorAll('.text-content').forEach(text => {
        text.style.color = '#ffffff';
    });
};

const disableDarkMode = () => {
    toggleBtn.classList.replace('fa-moon', 'fa-sun');
    body.classList.remove('dark');
    localStorage.setItem('dark-mode', 'disabled');
    
    // Reset to light mode styles
    document.querySelectorAll('.card').forEach(card => {
        card.style.boxShadow = '3px 3px 6px #bebebe, -3px -3px 6px #ffffff';
    });
    
    // Update text colors for light mode
    document.querySelectorAll('.text-content').forEach(text => {
        text.style.color = '#333333';
    });
};

// Initialize theme
if (darkMode === 'enabled') enableDarkMode();

// Event Listeners
toggleBtn.addEventListener('click', () => {
    darkMode = localStorage.getItem('dark-mode');
    if (darkMode === 'disabled') {
        enableDarkMode();
    } else {
        disableDarkMode();
    }
});

// Profile Toggle
userBtn.addEventListener('click', () => {
    profile.classList.toggle('active');
    search.classList.remove('active');
    
    // Add animation
    if (profile.classList.contains('active')) {
        profile.style.animation = 'slideDown 0.3s ease forwards';
    } else {
        profile.style.animation = 'slideUp 0.3s ease forwards';
    }
});

// Search Toggle
searchBtn.addEventListener('click', () => {
    search.classList.toggle('active');
    profile.classList.remove('active');
    
    // Focus search input when opened
    if (search.classList.contains('active')) {
        search.querySelector('input').focus();
        search.style.animation = 'fadeIn 0.3s ease forwards';
    } else {
        search.style.animation = 'fadeOut 0.3s ease forwards';
    }
});

// Sidebar Controls
menuBtn.addEventListener('click', () => {
    sideBar.classList.toggle('active');
    body.classList.toggle('active');
    
    // Add slide animation
    if (sideBar.classList.contains('active')) {
        sideBar.style.animation = 'slideRight 0.3s ease forwards';
    } else {
        sideBar.style.animation = 'slideLeft 0.3s ease forwards';
    }
});

closeBtn.addEventListener('click', () => {
    sideBar.classList.remove('active');
    body.classList.remove('active');
    sideBar.style.animation = 'slideLeft 0.3s ease forwards';
});

// Window Scroll Handler
window.addEventListener('scroll', () => {
    profile.classList.remove('active');
    search.classList.remove('active');

    if (window.innerWidth < 1200) {
        sideBar.classList.remove('active');
        body.classList.remove('active');
    }
});

// Form Handlers with Enhanced Error Handling and Validation
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        try {
            const formData = new FormData(loginForm);
            formData.append('action', 'login');

            // Add loading state
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Logging in...';

            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Show success message with animation
                showNotification('success', data.message);
                setTimeout(() => window.location.href = 'home.html', 1500);
            } else {
                showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'An error occurred. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Login';
        }
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        try {
            const formData = new FormData(registerForm);
            formData.append('action', 'register');

            // Add loading state
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Creating Account...';

            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', data.message);
                setTimeout(() => window.location.href = 'login.html', 1500);
            } else {
                showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'An error occurred. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Register';
        }
    });
}

// Utility Functions
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);

    // Add animation
    notification.style.animation = 'slideIn 0.5s ease forwards';

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.5s ease forwards';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// Add necessary CSS animations
const styleSheet = document.createElement('style');
styleSheet.textContent = `
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    @keyframes slideUp {
        from { transform: translateY(0); opacity: 1; }
        to { transform: translateY(-20px); opacity: 0; }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    @keyframes slideRight {
        from { transform: translateX(-100%); }
        to { transform: translateX(0); }
    }

    @keyframes slideLeft {
        from { transform: translateX(0); }
        to { transform: translateX(-100%); }
    }

    @keyframes slideIn {
        from { transform: translateY(-100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    @keyframes slideOut {
        from { transform: translateY(0); opacity: 1; }
        to { transform: translateY(-100%); opacity: 0; }
    }

    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 5px;
        color: white;
        z-index: 1000;
    }

    .notification.success {
        background-color: #4CAF50;
    }

    .notification.error {
        background-color: #f44336;
    }
`;

document.head.appendChild(styleSheet);