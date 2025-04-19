<?php
// Current user information (for reference)
$current_date = "2025-04-17 09:01:30"; // Current UTC time
$current_user = "Agabaofficial"; // Current user
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sign Up - MySocial</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #e63946;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --light-gray: #e9ecef;
            --medium-gray: #ced4da;
            --dark-gray: #6c757d;
            --card-bg: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        header {
            background: var(--card-bg);
            box-shadow: var(--shadow);
            padding: 1rem 2rem;
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .nav-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: var(--transition);
        }
        
        .nav-links a:hover {
            background: var(--light-bg);
        }
        
        /* Signup Section */
        .signup-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .signup-container {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .message {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background-color: rgba(56, 176, 0, 0.1);
            border-left: 4px solid var(--success-color);
            color: #2b7900;
        }
        
        .error {
            background-color: rgba(230, 57, 70, 0.1);
            border-left: 4px solid var(--danger-color);
            color: #c1121f;
        }
        
        form {
            display: flex;
            flex-direction: column;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            padding-left: 40px;
            border: 1px solid var(--medium-gray);
            border-radius: 5px;
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-group .icon {
            position: absolute;
            left: 15px;
            top: 41px;
            color: var(--dark-gray);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 41px;
            color: var(--dark-gray);
            cursor: pointer;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .form-check input {
            margin-right: 10px;
        }
        
        button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        button:hover {
            background: var(--secondary-color);
        }
        
        .login-prompt {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 14px;
        }
        
        .login-prompt a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-prompt a:hover {
            text-decoration: underline;
        }
        
        /* Password strength indicator */
        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 5px;
            background-color: var(--light-gray);
            position: relative;
            overflow: hidden;
        }
        
        .password-strength .strength-bar {
            height: 100%;
            border-radius: 5px;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: var(--dark-gray);
        }
        
        /* Footer Styles */
        footer {
            background: var(--card-bg);
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
            color: var(--dark-gray);
            font-size: 14px;
        }
        
        /* Responsive styles */
        @media (max-width: 576px) {
            .signup-container {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-globe-americas"></i>
                <span>MySocial</span>
            </div>
            <div class="nav-links">
                <a href="login.php">Log In</a>
            </div>
        </nav>
    </header>

    <!-- Sign Up Form Section -->
    <section class="signup-section">
        <div class="signup-container">
            <h1>Create Your Account</h1>
            <?php
            // Include the database connector
            require_once 'db_connect.php';

            // Initialize variables for feedback
            $message = '';
            $messageClass = '';

            // Process form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $fullName = trim($_POST['fullName']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirmPassword'];
                $terms = isset($_POST['terms']) ? $_POST['terms'] : '';

                // Basic validation
                if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
                    $message = "All fields are required.";
                    $messageClass = "error";
                } elseif ($password !== $confirmPassword) {
                    $message = "Passwords do not match.";
                    $messageClass = "error";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Invalid email format.";
                    $messageClass = "error";
                } elseif (empty($terms)) {
                    $message = "You must agree to the Terms of Service.";
                    $messageClass = "error";
                } elseif (strlen($password) < 8) {
                    $message = "Password must be at least 8 characters long.";
                    $messageClass = "error";
                } else {
                    // Generate a unique username (e.g., from email)
                    $username = strtolower(str_replace(['@', '.'], '', $email));

                    // Hash the password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    try {
                        // Check if username or email already exists
                        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                        $checkStmt->bind_param("ss", $username, $email);
                        $checkStmt->execute();
                        $checkStmt->bind_result($count);
                        $checkStmt->fetch();
                        $checkStmt->close();

                        if ($count > 0) {
                            $message = "Username or email already taken.";
                            $messageClass = "error";
                        } else {
                            // Insert new user
                            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name) 
                                                   VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("ssss", $username, $email, $password_hash, $fullName);
                            $stmt->execute();
                            
                            if ($stmt->affected_rows > 0) {
                                $message = "Account created successfully! You can now log in.";
                                $messageClass = "success";
                                
                                // Clear form data on success
                                $fullName = $email = '';
                            } else {
                                $message = "Error creating account. Please try again.";
                                $messageClass = "error";
                            }
                            $stmt->close();
                        }
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                        $messageClass = "error";
                    }
                }
            }
            ?>

            <!-- Display feedback message -->
            <?php if (!empty($message)): ?>
                <div class="message <?= $messageClass ?>">
                    <?php if ($messageClass === "success"): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle"></i>
                    <?php endif; ?>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form id="signupForm" action="signup.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <i class="fas fa-user icon"></i>
                    <input type="text" id="fullName" name="fullName" placeholder="Enter your full name" value="<?= isset($fullName) ? htmlspecialchars($fullName) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText">Password strength</div>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                    <i class="fas fa-eye password-toggle" id="confirmToggle"></i>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="#" style="color: var(--primary-color);">Terms of Service</a> and <a href="#" style="color: var(--primary-color);">Privacy Policy</a></label>
                </div>
                
                <button type="submit">Create Account</button>
            </form>
            
            <div class="login-prompt">
                <p>Already have an account? <a href="login.php">Log In</a></p>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <p>© 2025 MySocial. All Rights Reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const passwordToggle = document.getElementById('passwordToggle');
            const confirmToggle = document.getElementById('confirmToggle');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirmPassword');
            
            passwordToggle.addEventListener('click', function() {
                togglePasswordVisibility(passwordInput, passwordToggle);
            });
            
            confirmToggle.addEventListener('click', function() {
                togglePasswordVisibility(confirmInput, confirmToggle);
            });
            
            function togglePasswordVisibility(input, icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
            
            // Password strength meter
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedback = 'Password strength';
                
                if (password.length > 0) {
                    // Check length
                    if (password.length >= 8) strength += 25;
                    
                    // Check for lowercase and uppercase letters
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
                    
                    // Check for numbers
                    if (/\d/.test(password)) strength += 25;
                    
                    // Check for special characters
                    if (/[^a-zA-Z0-9]/.test(password)) strength += 25;
                    
                    // Set color based on strength
                    let strengthColor = '';
                    if (strength <= 25) {
                        strengthColor = '#e63946'; // Weak
                        feedback = 'Weak password';
                    } else if (strength <= 50) {
                        strengthColor = '#ffaa00'; // Medium
                        feedback = 'Fair password';
                    } else if (strength <= 75) {
                        strengthColor = '#38b000'; // Strong
                        feedback = 'Good password';
                    } else {
                        strengthColor = '#38b000'; // Very strong
                        feedback = 'Strong password';
                    }
                    
                    strengthBar.style.width = strength + '%';
                    strengthBar.style.backgroundColor = strengthColor;
                    strengthText.textContent = feedback;
                    strengthText.style.color = strengthColor;
                } else {
                    strengthBar.style.width = '0%';
                    strengthText.textContent = 'Password strength';
                    strengthText.style.color = 'var(--dark-gray)';
                }
            });
            
            // Form validation
            const form = document.getElementById('signupForm');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Basic validation
                const fullName = document.getElementById('fullName').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = passwordInput.value;
                const confirmPassword = confirmInput.value;
                const terms = document.getElementById('terms').checked;
                
                if (fullName === '' || email === '' || password === '' || confirmPassword === '') {
                    isValid = false;
                }
                
                if (password !== confirmPassword) {
                    isValid = false;
                }
                
                if (!terms) {
                    isValid = false;
                }
                
                if (!isValid) {
                    // Form will still submit because server-side validation is in place
                    // This is just to enhance UX
                }
            });
        });
    </script>
</body>

</html>