<?php
include 'db_connect.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Use the logged-in user information from session
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'User';

// Handle unlike action
if(isset($_POST['unlike'])) {
    $like_id = intval($_POST['unlike_id']);
    $delete_query = "DELETE FROM `like` WHERE like_id = $like_id AND user_id = $current_user_id";
    
    if($conn->query($delete_query)) {
        // Success message or redirect
        header("Location: likes.php?unliked=success");
        exit();
    }
}

// Fetch user's likes
$user_likes_query = "
    SELECT l.*, 
           p.content as post_content, p.post_id, p.visibility as post_visibility,
           c.content as comment_content, c.comment_id,
           u.username as content_creator_username, u.full_name as content_creator_name,
           CASE 
               WHEN p.post_id IS NOT NULL THEN 'post'
               ELSE 'comment'
           END as content_type,
           l.created_at as like_date
    FROM `like` l
    LEFT JOIN post p ON l.post_id = p.post_id
    LEFT JOIN comment c ON l.comment_id = c.comment_id
    LEFT JOIN users u ON 
        (p.post_id IS NOT NULL AND p.user_id = u.user_id) OR 
        (c.comment_id IS NOT NULL AND c.user_id = u.user_id)
    WHERE l.user_id = $current_user_id
    ORDER BY l.created_at DESC
";

$user_likes_result = $conn->query($user_likes_query);

// Get total likes count
$total_likes = $user_likes_result ? $user_likes_result->num_rows : 0;

// Current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Current date and time
$current_date = date('Y-m-d H:i:s'); // 2025-04-18 16:48:51 format
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Likes | MySocial</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #1DA1F2; /* Twitter Blue */
            --primary-hover: #1a91da;
            --secondary-color: #14171A;
            --accent-color: #657786;
            --heart-color: #E0245E; /* Twitter Heart */
            --success-color: #17BF63;
            --warning-color: #FFAD1F;
            --text-color: #14171A;
            --text-secondary: #657786;
            --bg-color: #F5F8FA;
            --light-bg: #E1E8ED;
            --card-bg: #FFFFFF;
            --dark-card-bg: #15202B;
            --border-color: #E1E8ED;
            --border-radius: 15px;
            --sidebar-width: 240px;
            --header-height: 60px;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        /* Dark mode variables */
        .dark-mode {
            --primary-color: #1DA1F2;
            --primary-hover: #1a91da;
            --secondary-color: #E1E8ED;
            --accent-color: #AAB8C2;
            --heart-color: #E0245E;
            --text-color: #FFFFFF;
            --text-secondary: #AAB8C2;
            --bg-color: #15202B;
            --light-bg: #192734;
            --card-bg: #192734;
            --border-color: #38444D;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
            overflow-y: auto;
            border-right: 1px solid var(--border-color);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            background: var(--card-bg);
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
        }
        
        .sidebar-logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-logo i {
            font-size: 28px;
            margin-right: 10px;
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
            border-radius: 30px;
            margin: 5px 10px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(29, 161, 242, 0.1);
            color: var(--primary-color);
        }
        
        .nav-link.active {
            font-weight: bold;
        }
        
        .nav-link i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        .nav-link .badge {
            margin-left: auto;
            background: var(--primary-color);
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
            transition: var(--transition);
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
            transition: var(--transition);
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-color);
        }
        
        .search-bar {
            display: flex;
            align-items: center;
            background: var(--light-bg);
            border-radius: 30px;
            padding: 5px 15px;
            width: 300px;
            transition: var(--transition);
        }
        
        .search-bar:focus-within {
            box-shadow: 0 0 0 2px var(--primary-color);
            background: var(--card-bg);
        }
        
        .search-bar input {
            border: none;
            background: transparent;
            padding: 8px;
            width: 100%;
            outline: none;
            color: var(--text-color);
        }
        
        .search-bar i {
            color: var(--accent-color);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .theme-toggle {
            margin-right: 20px;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .theme-toggle:hover {
            background: var(--light-bg);
        }
        
        .user-menu .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .user-menu .avatar:hover {
            opacity: 0.9;
        }
        
        .notification-icon {
            margin-right: 20px;
            position: relative;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .notification-icon:hover {
            background: var(--light-bg);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--heart-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header-title h1 {
            font-size: 28px;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .header-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            font-size: 15px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: rgba(29, 161, 242, 0.1);
        }
        
        /* Likes Grid */
        .likes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        /* Like Card */
        .like-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .like-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .like-header {
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .like-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(29, 161, 242, 0.1);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .like-date {
            color: var(--text-secondary);
            font-size: 13px;
        }
        
        .like-content {
            padding: 20px;
            color: var(--text-color);
            font-size: 16px;
            line-height: 1.5;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            position: relative;
        }
        
        .content-fade {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, rgba(255,255,255,0), var(--card-bg));
            pointer-events: none;
        }
        
        .dark-mode .content-fade {
            background: linear-gradient(to bottom, rgba(25,39,52,0), var(--card-bg));
        }
        
        .like-creator {
            display: flex;
            align-items: center;
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }
        
        .creator-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin-right: 12px;
        }
        
        .creator-info {
            flex-grow: 1;
        }
        
        .creator-name {
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 2px;
        }
        
        .creator-username {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .like-actions {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
        }
        
        .like-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 30px;
            cursor: pointer;
            transition: var(--transition);
            background: transparent;
            border: none;
            color: var(--text-color);
            font-size: 14px;
        }
        
        .like-btn:hover {
            background: var(--light-bg);
        }
        
        .like-btn.unlike-btn {
            color: var(--heart-color);
        }
        
        .like-btn.unlike-btn:hover {
            background: rgba(224, 36, 94, 0.1);
        }
        
        .like-count {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--heart-color);
            color: white;
            border-radius: 30px;
            padding: 4px 12px;
            font-size: 13px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin: 20px auto;
            max-width: 500px;
        }
        
        .empty-state i {
            font-size: 70px;
            color: var(--accent-color);
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .empty-state h2 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--text-color);
        }
        
        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        /* Stats Card */
        .stats-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            padding: 10px;
            border-right: 1px solid var(--border-color);
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .likes-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-nav {
                left: 0;
            }
            
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                cursor: pointer;
                border-radius: 50%;
                transition: var(--transition);
            }
            
            .menu-toggle:hover {
                background: var(--light-bg);
            }
            
            .likes-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .search-bar {
                display: none;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
            }
            
            .stats-card {
                flex-wrap: wrap;
            }
            
            .stat-item {
                flex-basis: 50%;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                padding: 15px 0;
            }
            
            .stat-item:nth-child(3), .stat-item:nth-child(4) {
                border-bottom: none;
            }
        }
        
        @media (max-width: 576px) {
            .stat-item {
                flex-basis: 100%;
            }
            
            .stat-item:nth-child(3) {
                border-bottom: 1px solid var(--border-color);
            }
            
            .page-title {
                display: none;
            }
        }
        
        /* Animations */
        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.3); }
            28% { transform: scale(1); }
            42% { transform: scale(1.3); }
            70% { transform: scale(1); }
        }
        
        .heartbeat {
            animation: heartBeat 1s ease-in-out;
        }
        
        /* Utils */
        .backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .backdrop.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Backdrop for mobile sidebar -->
    <div class="backdrop"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fab fa-twitter"></i>
                <span>MySocial</span>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
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
                    <?php if($total_likes > 0): ?>
                        <span class="badge"><?= $total_likes ?></span>
                    <?php endif; ?>
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
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
    </div>

    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="left-section">
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="page-title">My Likes</div>
        </div>
        
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="search-likes" placeholder="Search in likes...">
        </div>
        
        <div class="user-menu">
            <div class="theme-toggle" id="theme-toggle">
                <i class="fas fa-moon"></i>
            </div>
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">2</span>
            </div>
            <div class="avatar">
                <?= strtoupper(substr($current_username, 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="header-title">
                <h1>My Likes</h1>
                <div class="header-subtitle">
                    <span>See all the content you've liked across MySocial</span>
                </div>
            </div>
            <div class="header-actions">
                <a href="posts.php" class="btn btn-outline">
                    <i class="fas fa-newspaper"></i> Browse Posts
                </a>
                <?php if($total_likes > 0): ?>
                    <button id="filter-likes" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter Likes
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if($total_likes > 0): ?>
            <!-- Stats Card -->
            <div class="stats-card">
                <div class="stat-item">
                    <div class="stat-value"><?= $total_likes ?></div>
                    <div class="stat-label">Total Likes</div>
                </div>
                
                <?php 
                // Count likes by type
                $post_likes = 0;
                $comment_likes = 0;
                
                // Reset pointer to beginning of result set
                if($user_likes_result) {
                    $user_likes_result->data_seek(0);
                    
                    while($like = $user_likes_result->fetch_assoc()) {
                        if($like['content_type'] == 'post') {
                            $post_likes++;
                        } else {
                            $comment_likes++;
                        }
                    }
                    
                    // Reset again
                    $user_likes_result->data_seek(0);
                }
                ?>
                
                <div class="stat-item">
                    <div class="stat-value"><?= $post_likes ?></div>
                    <div class="stat-label">Post Likes</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?= $comment_likes ?></div>
                    <div class="stat-label">Comment Likes</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value">
                        <?php 
                        // Get today's date
                        $today = new DateTime();
                        $today->setTime(0, 0, 0);
                        
                        // Count today's likes
                        $today_likes = 0;
                        
                        if($user_likes_result) {
                            $user_likes_result->data_seek(0);
                            
                            while($like = $user_likes_result->fetch_assoc()) {
                                $like_date = new DateTime($like['like_date']);
                                if($like_date >= $today) {
                                    $today_likes++;
                                }
                            }
                            
                            // Reset again
                            $user_likes_result->data_seek(0);
                        }
                        
                        echo $today_likes;
                        ?>
                    </div>
                    <div class="stat-label">Today's Likes</div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Likes Grid -->
        <div class="likes-grid">
            <?php if($user_likes_result && $user_likes_result->num_rows > 0): ?>
                <?php while($like = $user_likes_result->fetch_assoc()): ?>
                    <?php 
                    // Format the like date for display
                    $like_date = new DateTime($like['like_date']);
                    $now = new DateTime();
                    $interval = $like_date->diff($now);
                    
                    if($interval->y > 0) {
                        $time_ago = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                    } elseif($interval->m > 0) {
                        $time_ago = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                    } elseif($interval->d > 0) {
                        $time_ago = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                    } elseif($interval->h > 0) {
                        $time_ago = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                    } elseif($interval->i > 0) {
                        $time_ago = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                    } else {
                        $time_ago = 'Just now';
                    }
                    
                    // Get content and link based on type
                    $content = '';
                    $link = '';
                    
                    if($like['content_type'] == 'post') {
                        $content = $like['post_content'];
                        $link = "posts.php?post=" . $like['post_id'];
                    } else {
                        $content = $like['comment_content'];
                        $link = "comments.php?comment=" . $like['comment_id'];
                    }
                    
                    // Get first letter of content creator's name for avatar
                    $creator_avatar = substr($like['content_creator_name'] ?? $like['content_creator_username'], 0, 1);
                    ?>
                    <div class="like-card" data-type="<?= $like['content_type'] ?>">
                        <div class="like-count">
                            <i class="fas fa-heart"></i> <?= $like['content_type'] == 'post' ? 'Post' : 'Comment' ?>
                        </div>
                        
                        <div class="like-header">
                            <div class="like-type">
                                <i class="fas <?= $like['content_type'] == 'post' ? 'fa-newspaper' : 'fa-comment' ?>"></i>
                                <?= ucfirst($like['content_type']) ?>
                            </div>
                            <div class="like-date">
                                <i class="far fa-clock"></i> <?= $time_ago ?>
                            </div>
                        </div>
                        
                        <div class="like-content">
                            <?= htmlspecialchars($content) ?>
                            <div class="content-fade"></div>
                        </div>
                        
                        <div class="like-creator">
                            <div class="creator-avatar">
                                <?= strtoupper($creator_avatar) ?>
                            </div>
                            <div class="creator-info">
                                <div class="creator-name"><?= htmlspecialchars($like['content_creator_name'] ?? '') ?></div>
                                <div class="creator-username">@<?= htmlspecialchars($like['content_creator_username']) ?></div>
                            </div>
                        </div>
                        
                        <div class="like-actions">
                            <a href="<?= $link ?>" class="like-btn view-btn">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>
                            
                            <form method="POST" action="" class="unlike-form">
                                <input type="hidden" name="unlike_id" value="<?= $like['like_id'] ?>">
                                <button type="submit" name="unlike" class="like-btn unlike-btn">
                                    <i class="fas fa-heart-broken"></i> Unlike
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-heart"></i>
                    <h2>No likes yet</h2>
                    <p>
                        You haven't liked any posts or comments yet. 
                        Explore content on MySocial and start liking what you enjoy!
                    </p>
                    <a href="posts.php" class="btn btn-primary">
                        <i class="fas fa-newspaper"></i> Explore Posts
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle for mobile
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.querySelector('.backdrop');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    backdrop.classList.toggle('active');
                    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
                });
            }
            
            // Close sidebar when clicking on backdrop
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    backdrop.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
            
            // Dark mode toggle
            const themeToggle = document.getElementById('theme-toggle');
            const body = document.body;
            
            // Check for saved theme preference
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                body.classList.add('dark-mode');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            // Toggle theme
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    body.classList.toggle('dark-mode');
                    
                    if (body.classList.contains('dark-mode')) {
                        localStorage.setItem('theme', 'dark');
                        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    } else {
                        localStorage.setItem('theme', 'light');
                        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    }
                });
            }
            
            // Search functionality
            const searchInput = document.getElementById('search-likes');
            const likeCards = document.querySelectorAll('.like-card');
            
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    likeCards.forEach(card => {
                        const content = card.querySelector('.like-content').textContent.toLowerCase();
                        const creator = card.querySelector('.creator-name').textContent.toLowerCase();
                        
                        if (content.includes(searchTerm) || creator.includes(searchTerm)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
            
            // Filter likes button
            const filterButton = document.getElementById('filter-likes');
            
            if (filterButton) {
                filterButton.addEventListener('click', function() {
                    const currentFilter = this.getAttribute('data-filter') || 'all';
                    let newFilter;
                    
                    if (currentFilter === 'all') {
                        newFilter = 'post';
                        this.innerHTML = '<i class="fas fa-newspaper"></i> Posts Only';
                    } else if (currentFilter === 'post') {
                        newFilter = 'comment';
                        this.innerHTML = '<i class="fas fa-comments"></i> Comments Only';
                    } else {
                        newFilter = 'all';
                        this.innerHTML = '<i class="fas fa-filter"></i> Filter Likes';
                    }
                    
                    this.setAttribute('data-filter', newFilter);
                    
                    likeCards.forEach(card => {
                        if (newFilter === 'all' || card.getAttribute('data-type') === newFilter) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
            
            // Confirmation for unlike action
            const unlikeForms = document.querySelectorAll('.unlike-form');
            
            unlikeForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to unlike this?')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Heart animation on hover
            const unlikeButtons = document.querySelectorAll('.unlike-btn');
            
            unlikeButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    const icon = this.querySelector('i');
                    icon.classList.add('heartbeat');
                });
                
                button.addEventListener('mouseleave', function() {
                    const icon = this.querySelector('i');
                    icon.classList.remove('heartbeat');
                });
            });
        });
    </script>
</body>
</html>