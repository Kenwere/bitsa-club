<?php
require_once 'config/constants.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitsa Club - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #000000;
            --bg-secondary: #1a1a1a;
            --bg-card: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --accent-cyan: #00fff5;
            --accent-purple: #bf00ff;
            --accent-pink: #ff006e;
            --accent-primary: #2563eb;
            --accent-danger: #ef4444;
            --border-color: #404040;
            --card-bg: rgba(15, 15, 25, 0.7);
            --card-text: #ffffff;
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --accent-cyan: #00b8b0;
            --accent-purple: #8e00bf;
            --accent-pink: #d6004d;
            --accent-primary: #2563eb;
            --accent-danger: #ef4444;
            --border-color: #e2e8f0;
            --card-bg: rgba(255, 255, 255, 0.9);
            --card-text: #000000;
        }

        html {
            scroll-behavior: smooth;
            scroll-snap-type: y mandatory;
        }

        body {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
            overflow-x: hidden;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background 0.5s ease;
        }

        .section {
            height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            scroll-snap-align: start;
        }

        /* Matrix Rain Effect */
        .matrix-rain {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
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

        /* Floating Navigation Bar - FIXED POSITION */
        .floating-nav {
            position: fixed;
            top: 1.2rem; /* Reduced from 2rem to prevent overlap */
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            display: flex;
            gap: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        [data-theme="light"] .floating-nav {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .nav-btn {
            background: transparent;
            border: none;
            color: var(--text-primary);
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }

        .nav-btn:hover {
            background: rgba(0, 255, 245, 0.2);
            color: var(--accent-cyan);
            transform: translateY(-2px);
        }

        .nav-btn.active {
            background: rgba(0, 255, 245, 0.3);
            color: var(--accent-cyan);
        }

        /* Theme Toggle - FIXED POSITION */
        .theme-toggle {
            position: fixed;
            top: 5.2rem; /* Moved down to sit below navigation */
            right: 2rem;
            z-index: 1000;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: rgba(0, 255, 245, 0.2);
            border-color: var(--accent-cyan);
            transform: rotate(180deg) scale(1.1);
        }

        /* Welcome Section */
        .welcome-section {
            position: relative;
            overflow: hidden;
            background: var(--bg-primary);
        }

        /* Welcome Content */
        .welcome-content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 1200px;
            padding: 0 2rem;
        }

        .welcome-title {
            font-size: 5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            position: relative;
            animation: fadeInScale 1s ease;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .tagline {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 2rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            opacity: 0;
            animation: fadeInUp 1s ease 0.3s forwards;
        }

        .tagline span {
            display: inline-block;
            color: var(--accent-cyan);
            position: relative;
        }

        .tagline span:nth-child(2) {
            color: var(--accent-purple);
        }

        .tagline span:nth-child(3) {
            color: var(--accent-pink);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mission-statement {
            font-size: 1.2rem;
            line-height: 1.6;
            max-width: 700px;
            margin: 0 auto 3rem;
            opacity: 0;
            animation: fadeInUp 1s ease 0.6s forwards;
            color: var(--text-primary);
            font-weight: 300;
        }

        /* Rotating Text Container */
        .rotating-text-container {
            height: 60px;
            margin-bottom: 2rem;
            position: relative;
            opacity: 0;
            animation: fadeInUp 1s ease 0.8s forwards;
        }

        .rotating-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-cyan);
            position: absolute;
            width: 100%;
            text-align: center;
            opacity: 0;
            transform: rotateX(90deg);
            animation: textSpin 12s infinite;
        }

        .rotating-text:nth-child(1) {
            animation-delay: 0s;
        }

        .rotating-text:nth-child(2) {
            animation-delay: 4s;
        }

        .rotating-text:nth-child(3) {
            animation-delay: 8s;
        }

        @keyframes textSpin {
            0% {
                opacity: 0;
                transform: rotateX(90deg);
            }
            5% {
                opacity: 1;
                transform: rotateX(0deg);
            }
            25% {
                opacity: 1;
                transform: rotateX(0deg);
            }
            30% {
                opacity: 0;
                transform: rotateX(-90deg);
            }
            100% {
                opacity: 0;
                transform: rotateX(-90deg);
            }
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            opacity: 0;
            animation: fadeInUp 1s ease 0.9s forwards;
        }

        .cta-btn {
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .cta-primary {
            background: var(--accent-cyan);
            color: #000;
        }

        .cta-secondary {
            background: transparent;
            color: var(--text-primary);
            border-color: var(--text-primary);
        }

        .cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 255, 245, 0.3);
        }

        .cta-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 3rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            animation: fadeIn 1s ease 1s forwards, float 3s ease-in-out infinite;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @keyframes float {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-10px); }
        }

        .scroll-text {
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.7;
        }

        .scroll-mouse {
            width: 24px;
            height: 40px;
            border: 2px solid var(--accent-cyan);
            border-radius: 12px;
            position: relative;
        }

        .scroll-wheel {
            width: 4px;
            height: 8px;
            background: var(--accent-cyan);
            border-radius: 2px;
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            animation: scrollWheel 2s infinite;
        }

        @keyframes scrollWheel {
            0% { top: 8px; opacity: 1; }
            100% { top: 24px; opacity: 0; }
        }

        /* Updated Login Sections */
        .login-section {
            background: var(--bg-primary);
            position: relative;
        }

        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            animation: fadeInUp 0.8s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            z-index: 10;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .login-header p {
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
            outline: none;
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

        .btn-login {
            background: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--accent-danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .register-link a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
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

        /* Responsive - FIXED OVERLAP ISSUES */
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 3rem;
            }

            .tagline {
                font-size: 1rem;
                letter-spacing: 0.2em;
            }

            .mission-statement {
                font-size: 1rem;
            }

            .rotating-text {
                font-size: 2rem;
            }

            .rotating-text-container {
                height: 50px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .login-card {
                padding: 1.5rem;
            }

            /* Mobile spacing fixes */
            .floating-nav {
                top: 1rem;
                padding: 0.5rem 1rem;
                gap: 0.5rem;
            }

            .theme-toggle {
                top: 4.5rem; /* Adjusted for mobile */
                right: 1rem;
                width: 50px;
                height: 50px;
            }

            .nav-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }

        /* Extra small devices */
        @media (max-width: 480px) {
            .floating-nav {
                top: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
            
            .theme-toggle {
                top: 4rem;
                right: 0.8rem;
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Navigation Bar -->
    <nav class="floating-nav">
        <a href="auth/user-login.php" class="nav-btn">User Login</a>
        <a href="auth/admin-login.php" class="nav-btn">Admin Login</a>
    </nav>

    <!-- Theme Toggle -->
    <button class="theme-toggle" id="themeToggle">
        <i class="bi bi-moon-fill" id="themeIcon"></i>
    </button>

    <!-- Welcome Section -->
    <section class="section welcome-section">
        <!-- Matrix Rain -->
        <div class="matrix-rain" id="matrixRain"></div>

        <!-- Content -->
        <div class="welcome-content">
            <h1 class="welcome-title">BITSA CLUB</h1>
            
            <p class="tagline">
                <span>Code</span> · <span>Innovate</span> · <span>Transform</span>
            </p>

            <p class="mission-statement">
                A premier technology community dedicated to fostering innovation, collaboration, 
                and professional growth through cutting-edge projects and knowledge sharing.
            </p>

            <!-- Rotating Text -->
            <div class="rotating-text-container">
                <div class="rotating-text">Software Engineering</div>
                <div class="rotating-text">Networking</div>
                <div class="rotating-text">BBIT</div>
            </div>

            <div class="cta-buttons">
                <a href="auth/user-login.php" class="cta-btn cta-primary">Get Started</a>
                <a href="auth/admin-login.php" class="cta-btn cta-secondary">Admin Access</a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <span class="scroll-text">Explore</span>
            <div class="scroll-mouse">
                <div class="scroll-wheel"></div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }

        // Matrix Rain Effect with English characters
        function createMatrixRain(containerId) {
            const matrixRain = document.getElementById(containerId);
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

        // Create matrix rain
        createMatrixRain('matrixRain');

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
    </script>
</body>
</html>