<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Social Media - Navbar</title>
    <link rel="stylesheet" href="navbar.css">
</head>

<body>
    <!-- Navbar Section -->
    <nav class="navbar">
        <div class="logo">
            <img src="logo.png" alt="Social Media Logo">
        </div>
        <?php
        // Start session
        session_start();

        // Include the database connector
        require_once 'db_connect.php';

        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            $db = new Database();
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("SELECT username, full_name, profile_picture FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
        ?>

        <!-- Navigation Links -->
        <ul class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="index.php">Home</a></li>
                <li><a href="friends.php">Friends</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="groups.php">Groups</a></li>
            <?php else: ?>
                <li><a href="login.php">Log In</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            <?php endif; ?>
        </ul>

        <!-- Profile Section -->
        <div class="profile">
            <?php if (isset($_SESSION['user_id']) && $user): ?>
                <img src="<?php echo $user['profile_picture'] ? $user['profile_picture'] : 'default-profile-pic.jpg'; ?>" alt="Profile Picture">
                <a href="profile.php"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></a>
                <a href="logout.php" class="logout">Logout</a>
            <?php endif; ?>
        </div>
    </nav>

    <script src="navbar.js"></script>
</body>

</html>