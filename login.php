<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Log In - Social Media</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <!-- Header Section -->
    <header>
        <nav>
            <div class="logo">
                <img src="logo.png" alt="Social Media Logo">
            </div>
            <div class="nav-links">
                <a href="signup.php">Sign Up</a>
            </div>
        </nav>
    </header>

    <!-- Login Form Section -->
    <section class="login-section">
        <div class="login-container">
            <h1>Log In</h1>
            <?php
            // Start session
            session_start();

            // Include the database connector
            require_once 'db_connect.php';

            // Initialize variables for feedback
            $message = '';

            // Process form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = trim($_POST['email']);
                $password = $_POST['password'];

                // Basic validation
                if (empty($email) || empty($password)) {
                    $message = "Both email and password are required.";
                } else {
                    // Connect to database
                    $db = new Database();
                    $pdo = $db->getConnection();

                    try {
                        // Check if user exists by email
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                        $stmt->execute([':email' => $email]);
                        $user = $stmt->fetch();

                        if ($user && password_verify($password, $user['password_hash'])) {
                            // Successful login
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['username'] = $user['username'];
                            $message = "Login successful! Redirecting...";
                            // Redirect to a dashboard or home page (e.g., after 2 seconds)
                            header("Refresh: 2; url=dashboard.php");
                        } else {
                            $message = "Invalid email or password.";
                        }
                    } catch (PDOException $e) {
                        $message = "Error: " . $e->getMessage();
                    }
                }
            }
            ?>

            <!-- Display feedback message -->
            <?php if (!empty($message)): ?>
                <p style="color: <?php echo strpos($message, 'successful') !== false ? 'green' : 'red'; ?>;">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="text" id="email" name="email" placeholder="Email" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <button type="submit">Log In</button>
                <div class="forgot-password">
                    <a href="#">Forgotten password?</a>
                </div>
            </form>
            <div class="signup-prompt">
                <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <p>© 2025 Social Media, All Rights Reserved.</p>
    </footer>

    <script src="login.js"></script>
</body>

</html>