<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../controllers/AuthController.php';
    $authController = new AuthController();
    $authController->registerAdmin();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Bitsa Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/emailjs-com@3.2.0/dist/email.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a1a;
            --bg-card: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --accent-primary: #2563eb;
            --accent-danger: #ef4444;
            --accent-cyan: #00fff5;
            --border-color: #404040;
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --accent-primary: #2563eb;
            --accent-danger: #ef4444;
            --accent-cyan: #00b8b0;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        /* Matrix Rain Effect */
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
            pointer-events: none;
        }

        .matrix-column {
            position: absolute;
            top: -100%;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: var(--accent-cyan);
            animation: matrixFall linear infinite;
        }

        @keyframes matrixFall {
            0% { top: -100%; }
            100% { top: 110%; }
        }

        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: var(--accent-primary);
            color: white;
        }

        .back-button {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .back-button:hover {
            background: var(--accent-primary);
            color: white;
        }

        .register-container {
            max-width: 440px;
            width: 100%;
            margin: 2rem 0;
            position: relative;
            z-index: 10;
        }

        .register-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .register-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-control {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .form-control:focus {
            background: var(--bg-secondary);
            border-color: var(--accent-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
        }

        .btn-register {
            background: var(--accent-danger);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-register:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-register:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
        }

        .btn-verify {
            background: var(--accent-cyan);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-verify:hover {
            background: #00e6dc;
            transform: translateY(-1px);
        }

        .btn-verify:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
        }

        .verification-section {
            background: rgba(0, 255, 245, 0.05);
            border: 1px solid var(--accent-cyan);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .verification-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #22c55e;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .login-link a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border-left: 4px solid var(--accent-danger);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #86efac;
            border-left: 4px solid #22c55e;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: #93c5fd;
            border-left: 4px solid var(--accent-primary);
        }

        .countdown {
            font-size: 0.8rem;
            color: var(--accent-cyan);
            font-weight: 600;
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 576px) {
            .register-container {
                margin: 1rem 0;
            }
            
            .register-card {
                padding: 1.5rem;
            }
            
            .theme-toggle,
            .back-button {
                top: 0.5rem;
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Matrix Rain Background -->
    <div class="matrix-rain" id="matrixRain"></div>

    <!-- Back Button -->
    <a href="../index.php" class="back-button">
        <i class="bi bi-arrow-left"></i>
    </a>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle">
        <i class="bi bi-moon" id="themeIcon"></i>
    </button>

    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h2><i class="bi bi-shield-check"></i> Admin Registration</h2>
                <p>Create your administrator account</p>
            </div>

            <!-- Success Message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; unset($_SESSION['errors']); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registrationForm">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="verification_code" id="verificationCode">
                
                <?php
                $old_input = $_SESSION['old_input'] ?? [];
                unset($_SESSION['old_input']);
                ?>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo $old_input['name'] ?? ''; ?>" 
                           placeholder="Enter your full name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="d-flex gap-2">
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $old_input['email'] ?? ''; ?>" 
                               placeholder="Enter your email" required>
                        <button type="button" class="btn-verify" id="sendCodeBtn" onclick="sendVerificationCode()">
                            Send Code
                        </button>
                    </div>
                    <div class="countdown mt-1" id="countdown" style="display: none;"></div>
                </div>

                <!-- Verification Code Input -->
                <div class="verification-section" id="verificationSection" style="display: none;">
                    <div class="mb-3">
                        <label for="verification_code_input" class="form-label">Verification Code</label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="verification_code_input" 
                                   placeholder="Enter 6-digit code" maxlength="6">
                            <button type="button" class="btn-verify" onclick="verifyCode()">
                                Verify
                            </button>
                        </div>
                        <small class="text-muted">Check your email for the verification code</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo $old_input['username'] ?? ''; ?>" 
                           placeholder="Choose a username" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <div class="password-input-group">
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required>
                        <button type="button" class="password-toggle" id="togglePasswordConfirmation">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="admin_key" class="form-label">Admin Registration Key</label>
                    <input type="password" class="form-control" id="admin_key" name="admin_key" 
                           placeholder="Enter admin registration key" required>
                    <small class="text-muted">Contact system administrator to get the registration key</small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms" style="font-size: 0.875rem;">
                        I agree to the admin terms and conditions
                    </label>
                </div>

                <button type="submit" class="btn-register" id="submitBtn" disabled>Create Admin Account</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="admin-login.php">Login here</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize EmailJS with your credentials
        (function() {
            // Replace with your actual EmailJS public key
            emailjs.init("kBkG6pOzC0cI4zDFL");
        })();

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        const currentTheme = localStorage.getItem('theme') || 'dark';
        html.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        themeToggle.addEventListener('click', () => {
            const theme = html.getAttribute('data-theme');
            const newTheme = theme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-sun';
            } else {
                themeIcon.className = 'bi bi-moon';
            }
        }

        // Matrix Rain Effect
        function createMatrixRain() {
            const matrixRain = document.getElementById('matrixRain');
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$#@%&*';
            
            for (let i = 0; i < 20; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = `${Math.random() * 100}%`;
                column.style.animationDuration = `${8 + Math.random() * 4}s`;
                column.style.animationDelay = `${Math.random() * 5}s`;
                
                let text = '';
                for (let j = 0; j < 25; j++) {
                    text += chars[Math.floor(Math.random() * chars.length)] + '<br>';
                }
                column.innerHTML = text;
                matrixRain.appendChild(column);
            }
        }

        createMatrixRain();

        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
        const password = document.getElementById('password');
        const passwordConfirmation = document.getElementById('password_confirmation');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });

        togglePasswordConfirmation.addEventListener('click', function () {
            const type = passwordConfirmation.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirmation.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });

        // Email Verification
        let verificationCode = '';
        let isVerified = false;

        function generateVerificationCode() {
            return Math.floor(100000 + Math.random() * 900000).toString();
        }

        function startCountdown(seconds) {
            const countdownElement = document.getElementById('countdown');
            const sendCodeBtn = document.getElementById('sendCodeBtn');
            
            sendCodeBtn.disabled = true;
            countdownElement.style.display = 'block';
            
            let timeLeft = seconds;
            
            const countdownInterval = setInterval(() => {
                countdownElement.textContent = `Resend in ${timeLeft}s`;
                timeLeft--;
                
                if (timeLeft < 0) {
                    clearInterval(countdownInterval);
                    countdownElement.style.display = 'none';
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = 'Resend Code';
                }
            }, 1000);
        }

        async function sendVerificationCode() {
            const email = document.getElementById('email').value;
            const sendCodeBtn = document.getElementById('sendCodeBtn');
            
            if (!email) {
                alert('Please enter your email address first');
                return;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }

            sendCodeBtn.disabled = true;
            sendCodeBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Sending...';

            // Generate verification code
            verificationCode = generateVerificationCode();
            document.getElementById('verificationCode').value = verificationCode;

            try {
                // Send email using EmailJS
                const response = await emailjs.send('service_0wpy66l', 'template_hw7kr58', {
                    to_email: email,
                    verification_code: verificationCode,
                    user_name: document.getElementById('name').value || 'Admin',
                    site_name: 'Bitsa Club',
                    account_type: 'Administrator'
                });

                if (response.status === 200) {
                    // Show verification section
                    document.getElementById('verificationSection').style.display = 'block';
                    sendCodeBtn.textContent = 'Code Sent!';
                    startCountdown(60); // 60 seconds countdown
                    
                    alert('Verification code sent to your email!');
                } else {
                    throw new Error('Failed to send email');
                }
            } catch (error) {
                console.error('Error sending verification code:', error);
                alert('Failed to send verification code. Please try again.');
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = 'Send Code';
            }
        }

        function verifyCode() {
            const enteredCode = document.getElementById('verification_code_input').value;
            const verificationSection = document.getElementById('verificationSection');
            const submitBtn = document.getElementById('submitBtn');

            if (!enteredCode) {
                alert('Please enter the verification code');
                return;
            }

            if (enteredCode === verificationCode) {
                isVerified = true;
                verificationSection.classList.add('verification-success');
                submitBtn.disabled = false;
                
                // Update verification input to show success
                const codeInput = document.getElementById('verification_code_input');
                codeInput.disabled = true;
                codeInput.style.background = 'rgba(34, 197, 94, 0.1)';
                codeInput.style.borderColor = '#22c55e';
                
                alert('Email verified successfully! You can now complete your registration.');
            } else {
                alert('Invalid verification code. Please try again.');
            }
        }

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (!isVerified) {
                e.preventDefault();
                alert('Please verify your email address first!');
                return;
            }

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            const terms = document.getElementById('terms').checked;
            const adminKey = document.getElementById('admin_key').value;
            
            if (!terms) {
                e.preventDefault();
                alert('You must agree to the terms and conditions!');
                return;
            }
            
            if (!adminKey) {
                e.preventDefault();
                alert('Please enter the admin registration key!');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
        });

        // Real-time password strength check
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            
            if (!strengthIndicator) {
                // Create strength indicator if it doesn't exist
                const strengthDiv = document.createElement('div');
                strengthDiv.id = 'passwordStrength';
                strengthDiv.className = 'mt-1 small';
                this.parentNode.appendChild(strengthDiv);
            }
            
            const strengthText = getPasswordStrength(password);
            strengthIndicator.innerHTML = strengthText;
            strengthIndicator.style.color = getStrengthColor(password);
        });

        function getPasswordStrength(password) {
            if (password.length === 0) return '';
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const strengthLevels = [
                'Very Weak',
                'Weak',
                'Fair',
                'Good',
                'Strong',
                'Very Strong'
            ];
            
            return `Password Strength: ${strengthLevels[strength]}`;
        }

        function getStrengthColor(password) {
            if (password.length === 0) return 'var(--text-secondary)';
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const colors = [
                '#ef4444', // Very Weak - red
                '#f97316', // Weak - orange
                '#eab308', // Fair - yellow
                '#84cc16', // Good - lime
                '#22c55e', // Strong - green
                '#16a34a'  // Very Strong - dark green
            ];
            
            return colors[strength];
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>