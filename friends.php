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

// Handle friend request actions
if(isset($_POST['accept_request'])) {
    $friendship_id = intval($_POST['friendship_id']);
    $conn->query("UPDATE friend SET status = 'accepted' WHERE friendship_id = $friendship_id");
    header("Location: friends.php");
    exit();
} elseif(isset($_POST['reject_request'])) {
    $friendship_id = intval($_POST['friendship_id']);
    $conn->query("DELETE FROM friend WHERE friendship_id = $friendship_id");
    header("Location: friends.php");
    exit();
} elseif(isset($_POST['send_request'])) {
    $user_id = intval($_POST['user_id']);
    
    // Check if a friendship already exists
    $check_query = "SELECT * FROM friend WHERE 
                    (user_id1 = $current_user_id AND user_id2 = $user_id) OR 
                    (user_id1 = $user_id AND user_id2 = $current_user_id)";
    $check_result = $conn->query($check_query);
    
    if($check_result->num_rows == 0) {
        // Create new friendship request
        $conn->query("INSERT INTO friend (user_id1, user_id2, status) VALUES ($current_user_id, $user_id, 'pending')");
    }
    
    header("Location: friends.php");
    exit();
} elseif(isset($_POST['cancel_request'])) {
    $friendship_id = intval($_POST['friendship_id']);
    $conn->query("DELETE FROM friend WHERE friendship_id = $friendship_id");
    header("Location: friends.php");
    exit();
} elseif(isset($_POST['remove_friend'])) {
    $friendship_id = intval($_POST['friendship_id']);
    $conn->query("DELETE FROM friend WHERE friendship_id = $friendship_id");
    header("Location: friends.php");
    exit();
}

// Fetch friends list (accepted friendships)
$friends_query = "
    SELECT f.*, f.friendship_id,
           CASE 
               WHEN f.user_id1 = $current_user_id THEN u2.user_id
               ELSE u1.user_id
           END as friend_id,
           CASE 
               WHEN f.user_id1 = $current_user_id THEN u2.username
               ELSE u1.username
           END as friend_username,
           CASE 
               WHEN f.user_id1 = $current_user_id THEN u2.full_name
               ELSE u1.full_name
           END as friend_name,
           CASE 
               WHEN f.user_id1 = $current_user_id THEN u2.profile_picture
               ELSE u1.profile_picture
           END as friend_picture
    FROM friend f
    JOIN users u1 ON f.user_id1 = u1.user_id
    JOIN users u2 ON f.user_id2 = u2.user_id
    WHERE (f.user_id1 = $current_user_id OR f.user_id2 = $current_user_id)
    AND f.status = 'accepted'
    ORDER BY f.created_at DESC
";

$friends_result = $conn->query($friends_query);

// Fetch pending friend requests sent to current user
$pending_requests_query = "
    SELECT f.*, f.friendship_id, 
           u1.user_id as requester_id,
           u1.username as requester_username,
           u1.full_name as requester_name,
           u1.profile_picture as requester_picture
    FROM friend f
    JOIN users u1 ON f.user_id1 = u1.user_id
    WHERE f.user_id2 = $current_user_id AND f.status = 'pending'
    ORDER BY f.created_at DESC
";

$pending_requests_result = $conn->query($pending_requests_query);

// Fetch sent friend requests by current user
$sent_requests_query = "
    SELECT f.*, f.friendship_id,
           u2.user_id as recipient_id,
           u2.username as recipient_username,
           u2.full_name as recipient_name,
           u2.profile_picture as recipient_picture
    FROM friend f
    JOIN users u2 ON f.user_id2 = u2.user_id
    WHERE f.user_id1 = $current_user_id AND f.status = 'pending'
    ORDER BY f.created_at DESC
";

$sent_requests_result = $conn->query($sent_requests_query);

// Fetch potential friends (users who are not friends and no pending requests)
$potential_friends_query = "
    SELECT u.user_id, u.username, u.full_name, u.profile_picture
    FROM users u
    WHERE u.user_id != $current_user_id
    AND NOT EXISTS (
        SELECT 1 FROM friend f 
        WHERE (f.user_id1 = $current_user_id AND f.user_id2 = u.user_id)
           OR (f.user_id1 = u.user_id AND f.user_id2 = $current_user_id)
    )
    LIMIT 5
";

$potential_friends_result = $conn->query($potential_friends_query);

// Current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get current date and time for display
$current_date = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends | MySocial</title>
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
        
        /* Friends page styles */
        .friends-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }
        
        .friends-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .friends-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            background: var(--light-bg);
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-color);
            color: white;
            font-size: 12px;
            border-radius: 50%;
            width: 22px;
            height: 22px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Friends list */
        .friends-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .friend-card {
            background: var(--light-bg);
            border-radius: 10px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .friend-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }
        
        .friend-header {
            background: var(--primary-color);
            height: 60px;
            position: relative;
        }
        
        .friend-avatar {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 32px;
            border: 4px solid var(--card-bg);
        }
        
        .friend-body {
            padding: 40px 20px 20px;
            text-align: center;
        }
        
        .friend-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .friend-username {
            color: var(--dark-gray);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .friend-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        
        .btn-secondary {
            background: var(--light-gray);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background: var(--medium-gray);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #2b9348;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c1121f;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-block {
            width: 100%;
        }
        
        /* Friend requests */
        .friend-request {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .friend-request:last-child {
            border-bottom: none;
        }
        
        .request-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .request-info {
            flex: 1;
        }
        
        .request-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .request-date {
            font-size: 13px;
            color: var(--dark-gray);
            margin-bottom: 10px;
        }
        
        .request-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Suggestions */
        .suggestion {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .suggestion:last-child {
            border-bottom: none;
        }
        
        .suggestion-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin-right: 12px;
        }
        
        .suggestion-info {
            flex: 1;
        }
        
        .suggestion-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .suggestion-action {
            margin-left: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
        }
        
        .empty-state i {
            font-size: 60px;
            color: var(--medium-gray);
            margin-bottom: 15px;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark-gray);
        }
        
        .empty-state p {
            color: var(--dark-gray);
            margin-bottom: 15px;
        }
        
        /* Mobile responsiveness */
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
            
            .friends-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .friends-list {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }
        
        @media (max-width: 576px) {
            .search-bar {
                display: none;
            }
            
            .friends-list {
                grid-template-columns: 1fr;
            }
            
            .friend-request {
                flex-direction: column;
            }
            
            .request-avatar {
                margin-bottom: 10px;
            }
            
            .request-actions {
                margin-top: 10px;
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
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="friend-search" placeholder="Search friends...">
        </div>
        <div class="user-menu">
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
        <h1>Friends</h1>
        <p>Connect with friends and manage your social network.</p>
        
        <div class="friends-container">
            <div class="friends-main">
                <!-- My Friends -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-user-friends"></i> My Friends
                            <?php if($friends_result && $friends_result->num_rows > 0): ?>
                                <span class="card-badge"><?= $friends_result->num_rows ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($friends_result && $friends_result->num_rows > 0): ?>
                            <div class="friends-list">
                                <?php while($friend = $friends_result->fetch_assoc()): ?>
                                    <?php 
                                    // Get first letter of friend's name for avatar
                                    $friend_avatar = substr($friend['friend_name'] ?? $friend['friend_username'], 0, 1);
                                    ?>
                                    <div class="friend-card">
                                        <div class="friend-header">
                                            <div class="friend-avatar">
                                                <?= $friend_avatar ?>
                                            </div>
                                        </div>
                                        <div class="friend-body">
                                            <div class="friend-name"><?= htmlspecialchars($friend['friend_name'] ?? '') ?></div>
                                            <div class="friend-username">@<?= htmlspecialchars($friend['friend_username']) ?></div>
                                            <div class="friend-actions">
                                                <a href="messages.php?user=<?= $friend['friend_id'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-envelope"></i> Message
                                                </a>
                                                <form method="POST" action="" style="display:inline;">
                                                    <input type="hidden" name="friendship_id" value="<?= $friend['friendship_id'] ?>">
                                                    <button type="submit" name="remove_friend" class="btn btn-secondary btn-sm">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-friends"></i>
                                <h3>No Friends Yet</h3>
                                <p>Start connecting with others to build your social network!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Friend Requests -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-user-plus"></i> Friend Requests
                            <?php if($pending_requests_result && $pending_requests_result->num_rows > 0): ?>
                                <span class="card-badge"><?= $pending_requests_result->num_rows ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($pending_requests_result && $pending_requests_result->num_rows > 0): ?>
                            <?php while($request = $pending_requests_result->fetch_assoc()): ?>
                                <?php 
                                // Get first letter of requester's name for avatar
                                $requester_avatar = substr($request['requester_name'] ?? $request['requester_username'], 0, 1);
                                
                                // Format request date
                                $request_date = new DateTime($request['created_at']);
                                $now = new DateTime();
                                $interval = $request_date->diff($now);
                                
                                if($interval->d > 0) {
                                    $time_ago = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                } elseif($interval->h > 0) {
                                    $time_ago = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                } elseif($interval->i > 0) {
                                    $time_ago = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                } else {
                                    $time_ago = 'Just now';
                                }
                                ?>
                                <div class="friend-request">
                                    <div class="request-avatar">
                                        <?= $requester_avatar ?>
                                    </div>
                                    <div class="request-info">
                                        <div class="request-name"><?= htmlspecialchars($request['requester_name'] ?? $request['requester_username']) ?></div>
                                        <div class="request-date"><i class="far fa-clock"></i> <?= $time_ago ?></div>
                                        <div class="request-actions">
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="friendship_id" value="<?= $request['friendship_id'] ?>">
                                                <button type="submit" name="accept_request" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                            </form>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="friendship_id" value="<?= $request['friendship_id'] ?>">
                                                <button type="submit" name="reject_request" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-plus"></i>
                                <h3>No Friend Requests</h3>
                                <p>You don't have any pending friend requests at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="friends-sidebar">
                <!-- Sent Requests -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-paper-plane"></i> Sent Requests
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($sent_requests_result && $sent_requests_result->num_rows > 0): ?>
                            <?php while($sent = $sent_requests_result->fetch_assoc()): ?>
                                <?php 
                                // Get first letter of recipient's name for avatar
                                $recipient_avatar = substr($sent['recipient_name'] ?? $sent['recipient_username'], 0, 1);
                                ?>
                                <div class="suggestion">
                                    <div class="suggestion-avatar">
                                        <?= $recipient_avatar ?>
                                    </div>
                                    <div class="suggestion-info">
                                        <div class="suggestion-name"><?= htmlspecialchars($sent['recipient_name'] ?? $sent['recipient_username']) ?></div>
                                    </div>
                                    <div class="suggestion-action">
                                        <form method="POST" action="">
                                            <input type="hidden" name="friendship_id" value="<?= $sent['friendship_id'] ?>">
                                            <button type="submit" name="cancel_request" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>You haven't sent any friend requests.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- People You May Know -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-user-plus"></i> People You May Know
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($potential_friends_result && $potential_friends_result->num_rows > 0): ?>
                            <?php while($potential = $potential_friends_result->fetch_assoc()): ?>
                                <?php 
                                // Get first letter of potential friend's name for avatar
                                $potential_avatar = substr($potential['full_name'] ?? $potential['username'], 0, 1);
                                ?>
                                <div class="suggestion">
                                    <div class="suggestion-avatar">
                                        <?= $potential_avatar ?>
                                    </div>
                                    <div class="suggestion-info">
                                        <div class="suggestion-name"><?= htmlspecialchars($potential['full_name'] ?? $potential['username']) ?></div>
                                    </div>
                                    <div class="suggestion-action">
                                        <form method="POST" action="">
                                            <input type="hidden" name="user_id" value="<?= $potential['user_id'] ?>">
                                            <button type="submit" name="send_request" class="btn btn-primary btn-sm">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No suggestions available at this time.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for sidebar toggle on mobile and search functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle for mobile
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
            
            // Friend search functionality
            const searchInput = document.getElementById('friend-search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const friendCards = document.querySelectorAll('.friend-card');
                    
                    friendCards.forEach(card => {
                        const friendName = card.querySelector('.friend-name').textContent.toLowerCase();
                        const friendUsername = card.querySelector('.friend-username').textContent.toLowerCase();
                        
                        if (friendName.includes(searchTerm) || friendUsername.includes(searchTerm)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
            
            // Highlight current user's avatar
            const avatarElement = document.querySelector('.avatar');
            if (avatarElement) {
                const firstLetter = '<?= strtoupper(substr($current_username, 0, 1)); ?>';
                avatarElement.textContent = firstLetter;
            }
        });
    </script>
</body>
</html>