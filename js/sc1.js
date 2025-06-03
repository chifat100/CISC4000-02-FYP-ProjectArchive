        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const inputs = document.querySelectorAll('input');

            // Enhanced form animations
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-5px)';
                    this.parentElement.querySelector('label').style.transform = 'translateY(-3px)';
                });

                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.style.transform = 'translateY(0)';
                        this.parentElement.querySelector('label').style.transform = 'translateY(0)';
                    }
                });
            });

            // Form submission handling
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Basic validation
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                // Email validation
                const emailInput = document.querySelector('input[type="email"]');
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailInput.value.trim())) {
                    isValid = false;
                    emailInput.classList.add('is-invalid');
                }

                if (isValid) {
                    loginButton.classList.add('loading');
                    loginButton.disabled = true;
                } else {
                    e.preventDefault();
                }
            });

            // Remove invalid class on input
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                    }
                });
            });
        });

        