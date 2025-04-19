<?php
// Start session at the very beginning
session_start();

include 'db_connect.php';

if (!isset($conn)) {
    die("Database connection is not established. Please check db_connect.php.");
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user = null;

// If user is logged in, get their information
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'User';
    
    // You can fetch more user details here if needed
    // For example:
    /*
    $stmt = $conn->prepare("SELECT profile_pic, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $current_user = $result->fetch_assoc();
    }
    */
} else {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy the session
    session_destroy();
    // Redirect to login page
    header("Location: login.php");
    exit();
}

function fetchCount($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        echo "Query failed: " . $conn->error . "<br>";
        return 0;
    }
    return $result->fetch_assoc()['count'];
}

// Modify queries to show only data relevant to the logged-in user
// Note: Adjust these queries based on your database schema
$postCount = fetchCount($conn, "SELECT COUNT(*) as count FROM post WHERE is_public = 1");
$commentCount = fetchCount($conn, "SELECT COUNT(*) as count FROM comment");
$likeCount = fetchCount($conn, "SELECT COUNT(*) as count FROM `like`");
$friendCount = fetchCount($conn, "SELECT COUNT(*) as count FROM friend WHERE status = 'accepted'");
$groupCount = fetchCount($conn, "SELECT COUNT(*) as count FROM `group` WHERE is_public = 1");
$messageCount = fetchCount($conn, "SELECT COUNT(*) as count FROM message WHERE is_read = 0");

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Social Dashboard</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-width: 240px;
            --header-height: 60px;
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
            position: relative;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--card-bg);
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: var(--shadow);
            z-index: 10;
            transition: var(--transition);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            background: var(--primary-color);
            color: white;
        }
        
        .sidebar-logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            list-style: none;
            transition: var(--transition);
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--light-bg);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .nav-link .badge {
            margin-left: auto;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            padding-top: calc(var(--header-height) + 20px);
        }
        
        /* Top Navigation */
        .top-nav {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: var(--shadow);
            z-index: 5;
        }
        
        .search-bar {
            display: flex;
            align-items: center;
            background: var(--light-bg);
            border-radius: 20px;
            padding: 5px 15px;
            width: 300px;
        }
        
        .search-bar input {
            border: none;
            background: transparent;
            padding: 8px;
            width: 100%;
            outline: none;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-menu .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
        }
        
        .dropdown-content a {
            color: var(--text-color);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .user-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .notification-icon {
            margin-right: 20px;
            position: relative;
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e63946;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .stat-title {
            color: var(--text-color);
            font-size: 18px;
            font-weight: 600;
        }
        
        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: var(--light-bg);
            border-radius: 12px;
            color: var(--primary-color);
            font-size: 20px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-card a {
            display: inline-block;
            margin-top: auto;
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card a:hover {
            background: var(--secondary-color);
        }
        
        /* Welcome banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .welcome-banner h2 {
            margin-bottom: 10px;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content, .top-nav {
                margin-left: 0;
                left: 0;
            }
            
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">MySocial</div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="posts.php" class="nav-link <?= $current_page == 'posts.php' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i> Posts
                </a>
            </li>
            <li class="nav-item">
                <a href="comments.php" class="nav-link <?= $current_page == 'comments.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Comments
                </a>
            </li>
            <li class="nav-item">
                <a href="likes.php" class="nav-link <?= $current_page == 'likes.php' ? 'active' : '' ?>">
                    <i class="fas fa-heart"></i> Likes
                </a>
            </li>
            <li class="nav-item">
                <a href="friends.php" class="nav-link <?= $current_page == 'friends.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-friends"></i> Friends
                </a>
            </li>
            <li class="nav-item">
                <a href="groups.php" class="nav-link <?= $current_page == 'groups.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Groups
                </a>
            </li>
            <li class="nav-item">
                <a href="messages.php" class="nav-link <?= $current_page == 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Messages
                    <?php if($messageCount > 0): ?>
                    <span class="badge"><?= $messageCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?logout=1" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search...">
        </div>
        <div class="user-menu">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <?php if($messageCount > 0): ?>
                <span class="notification-badge"><?= $messageCount ?></span>
                <?php endif; ?>
            </div>
            <div class="user-dropdown">
                <div class="avatar">
                    <?= substr($username, 0, 1) ?>
                </div>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h2>Welcome back, <?= htmlspecialchars($username) ?>!</h2>
            <p>Here's your social network activity at a glance. Stay connected with friends and discover new content.</p>
        </div>
        
        <h1>Dashboard Overview</h1>
        <p>Check out your latest activity statistics below.</p>
        
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Public Posts</div>
                    <div class="stat-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $postCount ?></div>
                <a href="posts.php">View Posts</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Comments</div>
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $commentCount ?></div>
                <a href="comments.php">View Comments</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Likes</div>
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $likeCount ?></div>
                <a href="likes.php">View Likes</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Friends</div>
                    <div class="stat-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $friendCount ?></div>
                <a href="friends.php">My Friends</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Public Groups</div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $groupCount ?></div>
                <a href="groups.php">Join Groups</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Unread Messages</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $messageCount ?></div>
                <a href="messages.php">Check Messages</a>
            </div>
        </div>
    </div>

    <!-- JavaScript for sidebar toggle on mobile and dropdown menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 992 && 
                    !sidebar.contains(event.target) && 
                    !menuToggle.contains(event.target) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>