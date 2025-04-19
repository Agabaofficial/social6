<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Social Media - Connect with the World</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #6366F1;
            --primary-dark: #4338CA;
            --secondary: #EC4899;
            --accent: #8B5CF6;
            --dark: #1E293B;
            --light: #F8FAFC;
            --gray: #64748B;
            --gray-light: #E2E8F0;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            background-color: var(--light);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Utilities */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            outline: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }
        
        .text-gradient {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline;
        }
        
        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1rem 0;
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
        }
        
        .logo-text {
            font-weight: 700;
            font-size: 1.5rem;
            margin-left: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .login-btn {
            padding: 10px 24px;
            background: var(--primary);
            color: white;
            border-radius: 50px;
        }
        
        .login-btn:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            padding-top: 80px;
            margin-bottom: 60px;
            overflow: hidden;
            position: relative;
        }
        
        .hero::before {
            content: "";
            position: absolute;
            top: -300px;
            right: -300px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(99, 102, 241, 0.15), rgba(236, 72, 153, 0.15));
            z-index: -1;
        }
        
        .hero::after {
            content: "";
            position: absolute;
            bottom: -200px;
            left: -200px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.15));
            z-index: -1;
        }
        
        .hero-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 2rem;
        }
        
        .hero-heading {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        
        .hero-description {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 2.5rem;
            max-width: 550px;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        
        .hero-stats {
            display: flex;
            gap: 2rem;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .hero-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        
        .hero-image img {
            width: 90%;
            max-width: 600px;
            z-index: 1;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
            100% {
                transform: translateY(0);
            }
        }
        
        .hero-blob {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 450px;
            height: 450px;
            background: linear-gradient(45deg, var(--primary-light), var(--accent));
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            opacity: 0.2;
            animation: morph 8s ease-in-out infinite;
        }
        
        @keyframes morph {
            0% {
                border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            }
            25% {
                border-radius: 50% 50% 70% 30% / 50% 50% 30% 70%;
            }
            50% {
                border-radius: 70% 30% 30% 70% / 70% 50% 50% 30%;
            }
            75% {
                border-radius: 30% 70% 50% 50% / 30% 50% 70% 50%;
            }
            100% {
                border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            }
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background-color: white;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.03);
        }
        
        .section-heading {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.05);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .feature-description {
            color: var(--gray);
            font-size: 1rem;
        }
        
        /* Testimonials Section */
        .testimonials {
            padding: 80px 0;
            background-color: var(--light);
            position: relative;
            overflow: hidden;
        }
        
        .testimonials::before {
            content: "";
            position: absolute;
            top: -200px;
            left: -200px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));
            z-index: 0;
        }
        
        .testimonials-slider {
            display: flex;
            gap: 30px;
            margin-top: 50px;
            overflow-x: auto;
            scrollbar-width: none;
            padding: 20px 0;
            scroll-snap-type: x mandatory;
        }
        
        .testimonials-slider::-webkit-scrollbar {
            display: none;
        }
        
        .testimonial-card {
            flex: 0 0 auto;
            width: 350px;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
            scroll-snap-align: start;
        }
        
        .testimonial-text {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .author-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .author-info p {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .quote-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            color: var(--primary-light);
            opacity: 0.1;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><rect fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1" x="400" y="400" width="300" height="300" rx="100" transform="rotate(45 400 400)"/><rect fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1" x="400" y="400" width="500" height="500" rx="200" transform="rotate(45 400 400)"/><rect fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1" x="400" y="400" width="700" height="700" rx="300" transform="rotate(45 400 400)"/></svg>');
            background-size: cover;
            background-position: center;
            opacity: 0.4;
            z-index: 0;
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .cta-description {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 2.5rem auto;
            opacity: 0.9;
        }
        
        .btn-light {
            background: white;
            color: var(--primary);
        }
        
        .btn-light:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 30px 0;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .footer-column h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 1.5rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .copyright {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 20px;
        }
        
        .footer-bottom-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .footer-bottom-links a:hover {
            color: white;
        }
        
        /* Notification popup */
        .notification-popup {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 300px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        .notification-popup.show {
            transform: translateX(0);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .notification-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .notification-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--gray);
        }
        
        .notification-message {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }
        
        .notification-action {
            text-align: right;
        }
        
        /* Cookie banner */
        .cookie-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
            padding: 1rem;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transform: translateY(100%);
            transition: transform 0.5s ease;
        }
        
        .cookie-banner.show {
            transform: translateY(0);
        }
        
        .cookie-text {
            flex: 1;
            font-size: 0.9rem;
            color: var(--gray);
            margin-right: 1rem;
        }
        
        .cookie-buttons {
            display: flex;
            gap: 10px;
        }
        
        /* Login Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            position: relative;
            transform: translateY(50px);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        .modal-overlay.show .modal {
            transform: translateY(0);
        }
        
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .modal-subtitle {
            color: var(--gray);
            font-size: 1rem;
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .social-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--gray-light);
            background: white;
            color: var(--dark);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            background: var(--gray-light);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--gray-light);
        }
        
        .divider::before {
            margin-right: 1rem;
        }
        
        .divider::after {
            margin-left: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-options a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .form-button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .form-button:hover {
            background: var(--primary-dark);
        }
        
        .modal-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .modal-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Animation for intrusive elements */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero {
                flex-direction: column;
                margin-bottom: 0;
            }
            
            .hero-image {
                margin-top: 3rem;
                order: -1;
            }
            
            .hero-heading {
                font-size: 2.5rem;
                text-align: center;
            }
            
            .hero-description {
                text-align: center;
                margin: 0 auto 2.5rem auto;
            }
            
            .cta-buttons {
                justify-content: center;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                flex-direction: column;
                padding: 1rem;
                background: white;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
                gap: 0;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .nav-links a {
                padding: 1rem;
                display: block;
                border-bottom: 1px solid var(--gray-light);
            }
            
            .nav-toggle {
                display: block;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .testimonial-card {
                width: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-heading {
                font-size: 2rem;
            }
            
            .feature-card {
                text-align: center;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .footer-bottom-links {
                justify-content: center;
            }
            
            .cookie-banner {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .cookie-text {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .notification-popup {
                bottom: 20px;
                right: 20px;
                width: calc(100% - 40px);
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header>
        <nav class="container">
            <div class="logo">
                <img src="https://www.apa.org/images/social-media-internet-topic-landing-page-tile_tcm7-311810_w1024_n.jpg" alt="Social Media Logo">
                <span class="logo-text">EchoBridge</span>
            </div>
            <button class="nav-toggle" id="navToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="#features">Features</a>
                <a href="#testimonials">Testimonials</a>
                <a href="#about">About Us</a>
                <a href="signup.php">Sign Up</a>
                <a href="login.php" class="login-btn">Log In</a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero container">
        <div class="hero-content">
            <h1 class="hero-heading">Connect with <span class="text-gradient">Friends</span> and the World Around You</h1>
            <p class="hero-description">Join the largest social platform where millions connect, share moments, and discover what's happening in real-time. Your journey to meaningful connections starts here.</p>
            <div class="cta-buttons">
                <a href="signup.php" class="btn btn-primary pulse">Create Account</a>
                <a href="login.php" class="btn btn-secondary">Log In</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number">10M+</span>
                    <span class="stat-label">Active Users</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">150+</span>
                    <span class="stat-label">Countries</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">5M+</span>
                    <span class="stat-label">Daily Posts</span>
                </div>
            </div>
        </div>
        <div class="hero-image">
            <div class="hero-blob"></div>
            <img src="https://www.apa.org/images/social-media-internet-topic-landing-page-tile_tcm7-311810_w1024_n.jpg" alt="Social Connections">
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-heading">
                <h2 class="section-title">Why People <span class="text-gradient">Love</span> EchoBridge</h2>
                <p class="section-subtitle">Experience a world of connections, content, and community with our innovative features that bring people together.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Meaningful Connections</h3>
                    <p class="feature-description">Connect with friends, family, and like-minded individuals around the globe. Build your network and foster relationships that matter.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Privacy First</h3>
                    <p class="feature-description">We prioritize your privacy with advanced security features and customizable privacy settings to keep your data safe and secure.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-photo-video"></i>
                    </div>
                    <h3 class="feature-title">Rich Media Sharing</h3>
                    <p class="feature-description">Share photos, videos, and stories with our high-quality media tools. Express yourself and capture moments like never before.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="feature-title">Real-time Messaging</h3>
                    <p class="feature-description">Stay in touch with instant messaging, group chats, and video calls. Communication has never been this seamless and engaging.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-compass"></i>
                    </div>
                    <h3 class="feature-title">Discover Content</h3>
                    <p class="feature-description">Explore trending topics, personalized content feeds, and discover communities that match your interests and passions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="feature-title">Event Planning</h3>
                    <p class="feature-description">Create and join events, send invitations, and coordinate activities with friends and communities in just a few clicks.</p>
                </div>
            </div>
        </div>
    </section>

   <!-- Testimonials Section -->
<section class="testimonials" id="testimonials">
    <div class="container">
        <div class="section-heading">
            <h2 class="section-title">What Our <span class="text-gradient">Users</span> Say</h2>
            <p class="section-subtitle">Hear from real people across Uganda who have transformed their social experience with EchoBridge.</p>
        </div>
        <div class="testimonials-slider">
            <div class="testimonial-card">
                <i class="fas fa-quote-right quote-icon"></i>
                <p class="testimonial-text">"EchoBridge has completely transformed how I stay connected with friends and family across Kampala and Jinja. The interface is intuitive, and I love how easy it is to share moments from our community events."</p>
                <div class="testimonial-author">
                    <img src="https://images.unsplash.com/photo-1531123897727-8f129e1688ce?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=987&q=80" alt="Nakato Esther" class="author-avatar">
                    <div class="author-info">
                        <h4>Nakato Esther</h4>
                        <p>Photographer, Kampala</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-right quote-icon"></i>
                <p class="testimonial-text">"As a tour guide in Queen Elizabeth National Park, EchoBridge helps me share Uganda's beauty with the world and connect travelers with authentic local experiences. The groups feature is amazing!"</p>
                <div class="testimonial-author">
                    <img src="https://images.unsplash.com/photo-1506277886164-e25aa3f4ef7f?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=735&q=80" alt="Okello Moses" class="author-avatar">
                    <div class="author-info">
                        <h4>Okello Moses</h4>
                        <p>Tour Guide, Fort Portal</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-right quote-icon"></i>
                <p class="testimonial-text">"The privacy features are what won me over. As a teacher in Mbale, I can safely connect with students and parents while maintaining professional boundaries. The platform genuinely cares about user security."</p>
                <div class="testimonial-author">
                    <img src="https://images.unsplash.com/photo-1589156191108-c762ff4b96ab?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=686&q=80" alt="Namukwaya Grace" class="author-avatar">
                    <div class="author-info">
                        <h4>Namukwaya Grace</h4>
                        <p>Secondary School Teacher, Mbale</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-right quote-icon"></i>
                <p class="testimonial-text">"I've organized several community development events in Gulu through EchoBridge, and it's been incredible to see how the platform brings Ugandan youth together both online and in our local communities."</p>
                <div class="testimonial-author">
                    <img src="https://images.unsplash.com/photo-1539605268671-1f3aeae376a6?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=670&q=80" alt="Mugisha Daniel" class="author-avatar">
                    <div class="author-info">
                        <h4>Mugisha Daniel</h4>
                        <p>Community Organizer, Gulu</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-right quote-icon"></i>
                <p class="testimonial-text">"As a small business owner selling crafts at Kampala's markets, EchoBridge has helped me reach customers across Uganda and even internationally. It's transformed my business completely!"</p>
                <div class="testimonial-author">
                    <img src="https://images.unsplash.com/photo-1517805686688-47dd930554b2?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=687&q=80" alt="Achieng Sarah" class="author-avatar">
                    <div class="author-info">
                        <h4>Achieng Sarah</h4>
                        <p>Craft Business Owner, Kampala</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-right quote-icon"></i>
                <p class="testimonial-text">"The way EchoBridge connects our music studio with fans across East Africa is incredible. We've been able to organize successful shows in Entebbe and Kampala thanks to the platform's reach!"</p>
                <div class="testimonial-author">
                    <img src="https://images.unsplash.com/photo-1620932934088-fbdb2920e484?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=687&q=80" alt="Kiwanuka Brian" class="author-avatar">
                    <div class="author-info">
                        <h4>Kiwanuka Brian</h4>
                        <p>Music Producer, Entebbe</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container cta-content">
            <h2 class="cta-title">Ready to Connect with the World?</h2>
            <p class="cta-description">Join millions of users and start sharing your stories, experiences, and connect with friends and communities worldwide.</p>
            <div class="cta-buttons">
                <a href="signup.php" class="btn btn-light pulse">Get Started Now</a>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>EchoBridge</h3>
                    <p style="color: rgba(255, 255, 255, 0.7); margin-bottom: 1.5rem;">Connecting people worldwide through shared experiences and meaningful conversations.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Company</h3>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Community Guidelines</a></li>
                        <li><a href="#">Safety Center</a></li>
                        <li><a href="#">Developers</a></li>
                        <li><a href="#">Status</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Legal</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Intellectual Property</a></li>
                        <li><a href="#">Law Enforcement</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="copyright">&copy; 2025 EchoBridge. All Rights Reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Cookies</a>
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Notification Popup -->
    <div class="notification-popup" id="notificationPopup">
        <div class="notification-header">
            <div class="notification-title">
                <i class="fas fa-bell" style="color: var(--primary);"></i> 
                New Features!
            </div>
            <button class="notification-close" id="closeNotification">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="notification-message">
            We've just launched new messaging features! Connect with friends faster than ever before.
        </div>
        <div class="notification-action">
            <button class="btn btn-primary" style="padding: 8px 15px; font-size: 0.9rem;">Learn More</button>
        </div>
    </div>

    <!-- Cookie Banner -->
    <div class="cookie-banner" id="cookieBanner">
        <div class="cookie-text">
            We use cookies to enhance your experience, analyze site traffic, and for marketing purposes. By continuing to browse our site, you agree to our <a href="#" style="color: var(--primary);">Cookie Policy</a>.
        </div>
        <div class="cookie-buttons">
            <button class="btn btn-secondary" style="padding: 8px 15px; font-size: 0.9rem;">Customize</button>
            <button class="btn btn-primary" id="acceptCookies" style="padding: 8px 15px; font-size: 0.9rem;">Accept All</button>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal">
            <button class="modal-close" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header">
                <h2 class="modal-title">Welcome Back!</h2>
                <p class="modal-subtitle">Log in to your EchoBridge account</p>
            </div>
            <div class="social-login">
                <button class="social-btn">
                    <i class="fab fa-google"></i> Google
                </button>
                <button class="social-btn">
                    <i class="fab fa-facebook-f"></i> Facebook
                </button>
            </div>
            <div class="divider">or continue with email</div>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>
                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#">Forgot password?</a>
                </div>
                <button type="submit" class="form-button">Log In</button>
            </form>
            <div class="modal-footer">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation Toggle for Mobile
            const navToggle = document.getElementById('navToggle');
            const navLinks = document.getElementById('navLinks');
            
            if (navToggle) {
                navToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
            
            // Notification Popup
            const notificationPopup = document.getElementById('notificationPopup');
            const closeNotification = document.getElementById('closeNotification');
            
            // Show notification after 5 seconds
            setTimeout(function() {
                notificationPopup.classList.add('show');
            }, 5000);
            
            if (closeNotification) {
                closeNotification.addEventListener('click', function() {
                    notificationPopup.classList.remove('show');
                });
            }
            
            // Cookie Banner
            const cookieBanner = document.getElementById('cookieBanner');
            const acceptCookies = document.getElementById('acceptCookies');
            
            // Check if user has already accepted cookies
            if (!localStorage.getItem('cookiesAccepted')) {
                // Show cookie banner after 2 seconds
                setTimeout(function() {
                    cookieBanner.classList.add('show');
                }, 2000);
            }
            
            if (acceptCookies) {
                acceptCookies.addEventListener('click', function() {
                    localStorage.setItem('cookiesAccepted', 'true');
                    cookieBanner.classList.remove('show');
                });
            }
            
            // Login Modal
            const loginBtn = document.querySelector('.login-btn');
            const loginModal = document.getElementById('loginModal');
            const closeModal = document.getElementById('closeModal');
            
            if (loginBtn) {
                loginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    loginModal.classList.add('show');
                });
            }
            
            if (closeModal) {
                closeModal.addEventListener('click', function() {
                    loginModal.classList.remove('show');
                });
            }
            
            // Close modal when clicking outside
            loginModal.addEventListener('click', function(e) {
                if (e.target === loginModal) {
                    loginModal.classList.remove('show');
                }
            });
            
            // Add smooth scrolling to all links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Auto-scroll testimonials
            const testimonialsSlider = document.querySelector('.testimonials-slider');
            let scrollAmount = 0;
            const scrollSpeed = 1;
            const scrollPause = 3000;
            let isPaused = false;
            let scrollInterval;
            
            function startScrolling() {
                scrollInterval = setInterval(function() {
                    if (!isPaused) {
                        scrollAmount += scrollSpeed;
                        if (scrollAmount >= testimonialsSlider.scrollWidth / 2) {
                            scrollAmount = 0;
                        }
                        testimonialsSlider.scrollLeft = scrollAmount;
                        
                        // Pause scrolling when reaching a testimonial card
                        if (scrollAmount % 380 < 5 && scrollAmount > 0) {
                            isPaused = true;
                            setTimeout(function() {
                                isPaused = false;
                            }, scrollPause);
                        }
                    }
                }, 20);
            }
            
            if (testimonialsSlider) {
                startScrolling();
                
                // Pause scrolling when hovering over testimonials
                testimonialsSlider.addEventListener('mouseenter', function() {
                    clearInterval(scrollInterval);
                });
                
                testimonialsSlider.addEventListener('mouseleave', function() {
                    startScrolling();
                });
            }
        });
        
        // Current date for copyright
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.copyright').innerHTML = 
                '&copy; ' + (new Date().getFullYear()) + ' EchoBridge. All Rights Reserved. <span style="color:rgba(255,255,255,0.5);">Last updated: April 17, 2025</span>';
        });
    </script>
</body>

</html>