<?php
include 'db_connect.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'User';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle post creation
if (isset($_POST['action']) && $_POST['action'] === 'create_post') {
    $content = $conn->real_escape_string($_POST['content']);
    $visibility = $conn->real_escape_string($_POST['visibility']);

    $insert_query = "INSERT INTO post (user_id, content, visibility) VALUES ($current_user_id, '$content', '$visibility')";
    if ($conn->query($insert_query)) {
        echo json_encode(["status" => "success", "message" => "Post created successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create post: " . $conn->error]);
    }
    exit();
}

// Handle like action
if (isset($_POST['action']) && $_POST['action'] === 'like_post') {
    $post_id = intval($_POST['post_id']);

    // Check if user already liked this post
    $check_like = $conn->query("SELECT * FROM `like` WHERE user_id = $current_user_id AND post_id = $post_id");

    if ($check_like->num_rows > 0) {
        // Unlike the post
        $conn->query("DELETE FROM `like` WHERE user_id = $current_user_id AND post_id = $post_id");
        echo json_encode(["status" => "success", "liked" => false]);
    } else {
        // Like the post
        $conn->query("INSERT INTO `like` (user_id, post_id) VALUES ($current_user_id, $post_id)");
        echo json_encode(["status" => "success", "liked" => true]);
    }
    exit();
}

// Handle comment submission
if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $post_id = intval($_POST['post_id']);
    $content = $conn->real_escape_string($_POST['comment_content']);

    $insert_comment = "INSERT INTO comment (post_id, user_id, content) VALUES ($post_id, $current_user_id, '$content')";
    if ($conn->query($insert_comment)) {
        // Get the newly created comment with user info
        $comment_id = $conn->insert_id;
        $comment_query = "
            SELECT c.*, u.username, u.full_name, u.profile_picture
            FROM comment c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.comment_id = $comment_id
        ";
        $comment_result = $conn->query($comment_query);
        $comment = $comment_result->fetch_assoc();
        
        echo json_encode([
            "status" => "success", 
            "message" => "Comment added successfully.",
            "comment" => $comment
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add comment: " . $conn->error]);
    }
    exit();
}

// Fetch comments for a post
if (isset($_GET['action']) && $_GET['action'] === 'fetch_comments') {
    $post_id = intval($_GET['post_id']);
    
    $comments_query = "
        SELECT c.*, u.username, u.full_name, u.profile_picture
        FROM comment c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.post_id = $post_id
        ORDER BY c.created_at ASC
    ";
    
    $comments_result = $conn->query($comments_query);
    $comments = [];
    while ($comment = $comments_result->fetch_assoc()) {
        $comments[] = $comment;
    }
    
    echo json_encode($comments);
    exit();
}

// Fetch posts dynamically via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'fetch_posts') {
    $posts_query = "
        SELECT p.*, u.username, u.full_name, u.profile_picture,
        (SELECT COUNT(*) FROM `like` WHERE post_id = p.post_id) as like_count,
        (SELECT COUNT(*) FROM comment WHERE post_id = p.post_id) as comment_count,
        (SELECT COUNT(*) FROM `like` WHERE post_id = p.post_id AND user_id = $current_user_id) as user_liked
        FROM post p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.visibility = 'public' 
           OR p.user_id = $current_user_id
           OR (p.visibility = 'friends' AND EXISTS (
              SELECT 1 FROM friend 
              WHERE (user_id1 = $current_user_id AND user_id2 = p.user_id AND status = 'accepted') 
                 OR (user_id2 = $current_user_id AND user_id1 = p.user_id AND status = 'accepted')
           ))
        ORDER BY p.created_at DESC
    ";

    $posts_result = $conn->query($posts_query);
    $posts = [];
    while ($post = $posts_result->fetch_assoc()) {
        // Format date for better readability
        $post_date = new DateTime($post['created_at']);
        $now = new DateTime();
        $interval = $post_date->diff($now);
        
        if ($interval->y > 0) {
            $post['time_ago'] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
        } elseif ($interval->m > 0) {
            $post['time_ago'] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d > 0) {
            $post['time_ago'] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
        } elseif ($interval->h > 0) {
            $post['time_ago'] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        } elseif ($interval->i > 0) {
            $post['time_ago'] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        } else {
            $post['time_ago'] = 'just now';
        }
        
        $posts[] = $post;
    }

    echo json_encode($posts);
    exit();
}

// Search posts (new endpoint)
if (isset($_GET['action']) && $_GET['action'] === 'search_posts') {
    $search_term = $conn->real_escape_string($_GET['term']);
    
    $search_query = "
        SELECT p.*, u.username, u.full_name, u.profile_picture,
        (SELECT COUNT(*) FROM `like` WHERE post_id = p.post_id) as like_count,
        (SELECT COUNT(*) FROM comment WHERE post_id = p.post_id) as comment_count,
        (SELECT COUNT(*) FROM `like` WHERE post_id = p.post_id AND user_id = $current_user_id) as user_liked
        FROM post p
        JOIN users u ON p.user_id = u.user_id
        WHERE (p.content LIKE '%$search_term%' OR u.username LIKE '%$search_term%' OR u.full_name LIKE '%$search_term%')
        AND (p.visibility = 'public' 
           OR p.user_id = $current_user_id
           OR (p.visibility = 'friends' AND EXISTS (
              SELECT 1 FROM friend 
              WHERE (user_id1 = $current_user_id AND user_id2 = p.user_id AND status = 'accepted') 
                 OR (user_id2 = $current_user_id AND user_id1 = p.user_id AND status = 'accepted')
           )))
        ORDER BY p.created_at DESC
    ";

    $search_result = $conn->query($search_query);
    $posts = [];
    while ($post = $search_result->fetch_assoc()) {
        // Format date for better readability
        $post_date = new DateTime($post['created_at']);
        $now = new DateTime();
        $interval = $post_date->diff($now);
        
        if ($interval->y > 0) {
            $post['time_ago'] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
        } elseif ($interval->m > 0) {
            $post['time_ago'] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d > 0) {
            $post['time_ago'] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
        } elseif ($interval->h > 0) {
            $post['time_ago'] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        } elseif ($interval->i > 0) {
            $post['time_ago'] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        } else {
            $post['time_ago'] = 'just now';
        }
        
        $posts[] = $post;
    }

    echo json_encode($posts);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts | MySocial</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reset styling */
        :root {
            --primary-color: #1DA1F2; /* Twitter blue */
            --primary-light: #71C9F8;
            --primary-dark: #1A91DA;
            --accent-color: #E1226B;
            --text-color: #14171A;
            --text-secondary: #657786;
            --background-color: #F5F8FA;
            --card-bg: #fff;
            --border-color: #E1E8ED;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            --top-nav-height: 60px;
            --shadow: 0px 1px 3px rgba(0, 0, 0, 0.08);
            --radius: 12px;
            --btn-radius: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: var(--background-color);
            color: var(--text-color);
            -webkit-font-smoothing: antialiased;
            transition: padding-left 0.3s ease;
            padding-left: var(--sidebar-width);
            padding-top: var(--top-nav-height);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-logo {
            font-size: 22px;
            font-weight: bold;
            color: var(--primary-color);
            text-align: center;
        }

        .sidebar-nav {
            list-style-type: none;
            padding: 20px 0;
        }

        .nav-item {
            margin: 8px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 30px;
            margin: 0 12px;
            font-weight: 500;
        }

        .nav-link i {
            font-size: 18px;
            width: 24px;
            margin-right: 15px;
            text-align: center;
        }

        .nav-link:hover {
            background-color: rgba(29, 161, 242, 0.1);
            color: var(--primary-color);
        }

        .nav-link.active {
            background-color: rgba(29, 161, 242, 0.1);
            color: var(--primary-color);
            font-weight: bold;
        }

        /* Top Navigation Styles */
        .top-nav {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--top-nav-height);
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 999;
            transition: left 0.3s ease;
        }

        .menu-toggle {
            display: none;
            font-size: 20px;
            color: var(--text-color);
            cursor: pointer;
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            position: relative;
            margin: 0 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 40px 10px 40px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            background-color: #EFF3F4;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .search-bar input:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-light);
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
        }

        .notification-icon i {
            font-size: 18px;
            color: var(--text-color);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent-color);
            color: white;
            font-size: 10px;
            font-weight: bold;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
        }

        /* Main Content Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            color: var(--text-color);
        }

        .header .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .header .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .post-form {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .post-input-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            flex-shrink: 0;
        }

        .post-input-wrapper {
            flex-grow: 1;
        }

        .post-form textarea {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: var(--radius);
            resize: none;
            margin-bottom: 10px;
            font-size: 16px;
            font-family: inherit;
            outline: none;
            background: transparent;
        }

        .post-form textarea:focus {
            outline: none;
        }

        .post-form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .post-form select {
            padding: 8px 12px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            font-size: 14px;
            background: transparent;
        }

        .post-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--btn-radius);
            padding: 8px 20px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .post-submit:hover {
            background: var(--primary-dark);
        }
        
        .post-submit:disabled {
            background: var(--primary-light);
            cursor: not-allowed;
        }

        .post-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }

        .post-header {
            display: flex;
            margin-bottom: 12px;
        }

        .post-user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .post-user-info {
            flex-grow: 1;
        }

        .post-user-name {
            font-weight: bold;
            color: var(--text-color);
        }

        .post-user-handle, .post-time {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .post-content {
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.5;
        }

        .post-actions {
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }

        .action-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-secondary);
            font-size: 14px;
            padding: 5px;
            border-radius: 20px;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: rgba(29, 161, 242, 0.1);
            color: var(--primary-color);
        }

        .action-btn.liked {
            color: #E0245E;
        }

        .action-btn.liked:hover {
            background: rgba(224, 36, 94, 0.1);
        }

        .action-btn i {
            font-size: 16px;
        }

        .comments-section {
            background: var(--background-color);
            border-radius: var(--radius);
            margin-top: 10px;
            padding: 10px;
            display: none;
        }

        .comment-form {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }

        .comment-input-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .comment-input {
            width: 100%;
            padding: 8px 40px 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 14px;
            outline: none;
        }

        .comment-submit {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
        }

        .comment-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .comment-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .comment-item:last-child {
            border-bottom: none;
        }

        .comment-user-info {
            flex-grow: 1;
        }

        .comment-user-name {
            font-weight: bold;
            font-size: 14px;
        }

        .comment-content {
            font-size: 14px;
        }

        .comment-time {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .empty-message {
            text-align: center;
            color: var(--text-secondary);
            padding: 20px;
            font-size: 16px;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
        }

        .loading i {
            font-size: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Layout */
        /* Small tablets and large phones */
        @media (max-width: 992px) {
            body {
                padding-left: var(--sidebar-collapsed-width);
            }
            
            .sidebar {
                width: var(--sidebar-collapsed-width);
            }
            
            .sidebar-logo {
                display: none;
            }
            
            .nav-link span {
                display: none;
            }
            
            .nav-link {
                justify-content: center;
                padding: 15px 0;
            }
            
            .nav-link i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .top-nav {
                left: var(--sidebar-collapsed-width);
            }
        }

        /* Mobile Phones */
        @media (max-width: 768px) {
            body {
                padding-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
                width: var(--sidebar-width);
            }
            
            .sidebar.active .sidebar-logo {
                display: block;
            }
            
            .sidebar.active .nav-link span {
                display: inline;
            }
            
            .sidebar.active .nav-link {
                justify-content: flex-start;
                padding: 12px 20px;
            }
            
            .sidebar.active .nav-link i {
                margin-right: 15px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .top-nav {
                left: 0;
            }
            
            .container {
                padding: 10px;
            }
            
            .search-bar {
                max-width: none;
                margin: 0 10px;
            }
            
            .post-card, .post-form {
                padding: 12px;
            }
            
            .post-user-avatar, .post-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .comment-avatar {
                width: 28px;
                height: 28px;
            }
            
            .action-btn span {
                display: none;
            }
        }

        /* Extra small devices */
        @media (max-width: 480px) {
            .top-nav {
                padding: 0 10px;
            }
            
            .search-bar {
                max-width: 150px;
            }
            
            .post-actions {
                justify-content: space-around;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --text-color: #E7E9EA;
                --text-secondary: #71767B;
                --background-color: #000000;
                --card-bg: #16181C;
                --border-color: #2F3336;
            }
            
            .search-bar input {
                background-color: #202327;
                color: var(--text-color);
            }
            
            .search-bar input:focus {
                background-color: #000000;
            }
            
            .top-nav {
                background-color: rgba(0, 0, 0, 0.9);
            }
        }

        /* Theme Toggle */
        body.dark-mode {
            --text-color: #E7E9EA;
            --text-secondary: #71767B;
            --background-color: #000000;
            --card-bg: #16181C;
            --border-color: #2F3336;
        }
        
        body.dark-mode .search-bar input {
            background-color: #202327;
            color: var(--text-color);
        }
        
        body.dark-mode .search-bar input:focus {
            background-color: #000000;
        }
        
        body.dark-mode .top-nav {
            background-color: rgba(0, 0, 0, 0.9);
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
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="posts.php" class="nav-link <?= $current_page == 'posts.php' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i> <span>Posts</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="comments.php" class="nav-link <?= $current_page == 'comments.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> <span>Comments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="likes.php" class="nav-link <?= $current_page == 'likes.php' ? 'active' : '' ?>">
                    <i class="fas fa-heart"></i> <span>Likes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="friends.php" class="nav-link <?= $current_page == 'friends.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-friends"></i> <span>Friends</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="groups.php" class="nav-link <?= $current_page == 'groups.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> <span>Groups</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="messages.php" class="nav-link <?= $current_page == 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> <span>Messages</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> <span>Settings</span>
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
            <input type="text" id="search-input" placeholder="Search posts...">
        </div>
        <div class="user-menu">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">2</span>
            </div>
            <div class="avatar">
                <?php echo strtoupper(substr($current_username, 0, 1)); ?>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Home</h1>
        </div>

        <!-- Post creation form -->
        <div class="post-form">
            <div class="post-input-container">
                <div class="post-avatar"><?php echo strtoupper(substr($current_username, 0, 1)); ?></div>
                <div class="post-input-wrapper">
                    <textarea id="post-content" placeholder="What's happening?" rows="3"></textarea>
                </div>
            </div>
            <div class="post-form-footer">
                <select id="post-visibility">
                    <option value="public">Everyone</option>
                    <option value="friends">Friends</option>
                    <option value="private">Only me</option>
                </select>
                <button id="post-submit" class="post-submit" disabled>Tweet</button>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="loading" class="loading">
            <i class="fas fa-circle-notch"></i>
            <p>Loading tweets...</p>
        </div>

        <!-- Posts feed -->
        <div id="posts-feed"></div>
    </div>

    <script>
        $(document).ready(function () {
            // Sidebar toggle for mobile
            $('.menu-toggle').click(function() {
                $('.sidebar').toggleClass('active');
                $('body').toggleClass('sidebar-open');
            });

            // Close sidebar when clicking outside on mobile
            $(document).click(function(event) {
                if ($('.sidebar').hasClass('active') && 
                    !$(event.target).closest('.sidebar').length && 
                    !$(event.target).closest('.menu-toggle').length) {
                    $('.sidebar').removeClass('active');
                    $('body').removeClass('sidebar-open');
                }
            });

            // Search functionality
            let searchTimeout;
            $('#search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                const searchTerm = $(this).val().trim();
                
                if (searchTerm.length > 2) {
                    searchTimeout = setTimeout(function() {
                        $('#loading').show();
                        $('#posts-feed').hide();
                        
                        $.get('posts.php', { action: 'search_posts', term: searchTerm }, function(data) {
                            $('#loading').hide();
                            $('#posts-feed').show();
                            
                            renderPosts(JSON.parse(data));
                        }).fail(function() {
                            $('#loading').hide();
                            $('#posts-feed').html('<div class="empty-message">Failed to search posts. Please try again.</div>');
                        });
                    }, 500);
                } else if (searchTerm.length === 0) {
                    fetchPosts();
                }
            });

            // Check post content length to enable/disable tweet button
            $('#post-content').on('keyup', function() {
                const content = $(this).val().trim();
                $('#post-submit').prop('disabled', content.length === 0);
            });

            // Render posts to the DOM
            function renderPosts(posts) {
                let postsHtml = '';

                if (posts.length === 0) {
                    postsHtml = '<div class="empty-message">No posts to show. Be the first to tweet!</div>';
                } else {
                    posts.forEach(post => {
                        const avatarText = post.full_name ? post.full_name.charAt(0).toUpperCase() : post.username.charAt(0).toUpperCase();
                        const avatarImg = post.profile_picture ? 
                            `<img src="${post.profile_picture}" alt="${post.full_name}" class="post-user-avatar">` : 
                            `<div class="post-user-avatar">${avatarText}</div>`;
                        
                        postsHtml += `
                            <div class="post-card" data-post-id="${post.post_id}">
                                <div class="post-header">
                                    ${avatarImg}
                                    <div class="post-user-info">
                                        <div class="post-user-name">${post.full_name || post.username}</div>
                                        <div class="post-user-handle">@${post.username}</div>
                                        <div class="post-time">${post.time_ago}</div>
                                    </div>
                                </div>
                                <div class="post-content">${post.content}</div>
                                <div class="post-actions">
                                    <button class="action-btn comment-btn" data-post-id="${post.post_id}">
                                        <i class="far fa-comment"></i>
                                        <span class="comment-count">${post.comment_count}</span>
                                    </button>
                                    <button class="action-btn retweet-btn">
                                        <i class="fas fa-retweet"></i>
                                        <span>0</span>
                                    </button>
                                    <button class="action-btn like-btn ${post.user_liked ? 'liked' : ''}" data-post-id="${post.post_id}">
                                        <i class="${post.user_liked ? 'fas' : 'far'} fa-heart"></i>
                                        <span class="like-count">${post.like_count}</span>
                                    </button>
                                    <button class="action-btn share-btn">
                                        <i class="far fa-share-square"></i>
                                    </button>
                                </div>
                                <div class="comments-section" id="comments-section-${post.post_id}">
                                    <div class="comment-form">
                                        <div class="comment-avatar">${avatarText}</div>
                                        <div class="comment-input-wrapper">
                                            <input type="text" class="comment-input" placeholder="Tweet your reply" data-post-id="${post.post_id}">
                                            <button class="comment-submit" data-post-id="${post.post_id}">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="comment-list" id="comment-list-${post.post_id}">
                                        <div class="loading-comments">
                                            <i class="fas fa-circle-notch"></i> Loading comments...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }

                $('#posts-feed').html(postsHtml);
            }

            // Fetch posts and render them
            function fetchPosts() {
                $('#loading').show();
                $('#posts-feed').hide();
                
                $.get('posts.php', { action: 'fetch_posts' }, function (data) {
                    $('#loading').hide();
                    $('#posts-feed').show();
                    
                    renderPosts(JSON.parse(data));
                }).fail(function() {
                    $('#loading').hide();
                    $('#posts-feed').html('<div class="empty-message">Failed to load posts. Please try again later.</div>');
                });
            }

            // Create post
            $('#post-submit').click(function () {
                const content = $('#post-content').val().trim();
                if (!content) return;
                
                const visibility = $('#post-visibility').val();
                $(this).prop('disabled', true).text('Posting...');

                $.post('posts.php', { 
                    action: 'create_post', 
                    content: content, 
                    visibility: visibility 
                }, function (response) {
                    let res = JSON.parse(response);
                    if (res.status === 'success') {
                        $('#post-content').val('');
                        $('#post-submit').text('Tweet').prop('disabled', true);
                        fetchPosts();
                    } else {
                        alert(res.message);
                        $('#post-submit').text('Tweet').prop('disabled', false);
                    }
                }).fail(function() {
                    alert('Failed to create post. Please try again.');
                    $('#post-submit').text('Tweet').prop('disabled', false);
                });
            });

            // Like post
            $(document).on('click', '.like-btn', function () {
                const $btn = $(this);
                const postId = $btn.data('post-id');
                const $icon = $btn.find('i');
                const $count = $btn.find('.like-count');
                const currentCount = parseInt($count.text());
                
                // Optimistic UI update
                if ($btn.hasClass('liked')) {
                    $btn.removeClass('liked');
                    $icon.removeClass('fas').addClass('far');
                    $count.text(Math.max(0, currentCount - 1));
                } else {
                    $btn.addClass('liked');
                    $icon.removeClass('far').addClass('fas');
                    $count.text(currentCount + 1);
                }

                $.post('posts.php', { 
                    action: 'like_post', 
                    post_id: postId 
                }, function (response) {
                    let res = JSON.parse(response);
                    if (res.status !== 'success') {
                        // Revert UI if there was an error
                        if ($btn.hasClass('liked')) {
                            $btn.removeClass('liked');
                            $icon.removeClass('fas').addClass('far');
                            $count.text(Math.max(0, currentCount));
                        } else {
                            $btn.addClass('liked');
                            $icon.removeClass('far').addClass('fas');
                            $count.text(currentCount);
                        }
                        alert('Failed to update like status');
                    }
                }).fail(function() {
                    // Revert UI on network failure
                    if ($btn.hasClass('liked')) {
                        $btn.removeClass('liked');
                        $icon.removeClass('fas').addClass('far');
                        $count.text(Math.max(0, currentCount));
                    } else {
                        $btn.addClass('liked');
                        $icon.removeClass('far').addClass('fas');
                        $count.text(currentCount);
                    }
                    alert('Network error. Please try again.');
                });
            });

            // Toggle comments section
            $(document).on('click', '.comment-btn', function () {
                const postId = $(this).data('post-id');
                const commentsSection = $(`#comments-section-${postId}`);
                
                if (commentsSection.is(':visible')) {
                    commentsSection.slideUp(200);
                } else {
                    commentsSection.slideDown(200);
                    fetchComments(postId);
                }
            });

            // Fetch comments for a post
            function fetchComments(postId) {
                const commentList = $(`#comment-list-${postId}`);
                commentList.html('<div class="loading-comments"><i class="fas fa-circle-notch"></i> Loading comments...</div>');
                
                $.get('posts.php', { 
                    action: 'fetch_comments', 
                    post_id: postId 
                }, function (data) {
                    let comments = JSON.parse(data);
                    let commentsHtml = '';
                    
                    if (comments.length === 0) {
                        commentsHtml = '<div class="empty-message">No comments yet. Be the first to reply!</div>';
                    } else {
                        comments.forEach(comment => {
                            const avatarText = comment.full_name ? comment.full_name.charAt(0).toUpperCase() : comment.username.charAt(0).toUpperCase();
                            const avatarImg = comment.profile_picture ? 
                                `<img src="${comment.profile_picture}" alt="${comment.full_name}" class="comment-avatar">` : 
                                `<div class="comment-avatar">${avatarText}</div>`;
                            
                            // Format the comment date
                            const commentDate = new Date(comment.created_at);
                            const formattedDate = commentDate.toLocaleString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            commentsHtml += `
                                <div class="comment-item">
                                    ${avatarImg}
                                    <div class="comment-user-info">
                                        <div class="comment-user-name">${comment.full_name || comment.username}</div>
                                        <div class="comment-content">${comment.content}</div>
                                        <div class="comment-time">${formattedDate}</div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    commentList.html(commentsHtml);
                }).fail(function() {
                    commentList.html('<div class="empty-message">Failed to load comments. Please try again.</div>');
                });
            }

            // Submit comment
            $(document).on('click', '.comment-submit', function() {
                const postId = $(this).data('post-id');
                const inputElement = $(this).parent().find('.comment-input');
                const content = inputElement.val().trim();
                
                if (!content) return;
                
                $(this).prop('disabled', true);
                inputElement.prop('disabled', true);
                
                $.post('posts.php', {
                    action: 'add_comment',
                    post_id: postId,
                    comment_content: content
                }, function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        inputElement.val('');
                        
                        // Update comment count in the UI
                        const countElement = $(`.comment-btn[data-post-id="${postId}"] .comment-count`);
                        const currentCount = parseInt(countElement.text());
                        countElement.text(currentCount + 1);
                        
                        // Refresh comments
                        fetchComments(postId);
                    } else {
                        alert(res.message);
                    }
                    
                    inputElement.prop('disabled', false);
                    $('.comment-submit').prop('disabled', false);
                }).fail(function() {
                    alert('Failed to add comment. Please try again.');
                    inputElement.prop('disabled', false);
                    $('.comment-submit').prop('disabled', false);
                });
            });
            
            // Submit comment on Enter key press
            $(document).on('keypress', '.comment-input', function(e) {
                if (e.which === 13) { // Enter key
                    const postId = $(this).data('post-id');
                    $(`.comment-submit[data-post-id="${postId}"]`).click();
                    return false;
                }
            });

            // Dark mode toggle
            function toggleDarkMode() {
                $('body').toggleClass('dark-mode');
                localStorage.setItem('darkMode', $('body').hasClass('dark-mode'));
            }
            
            // Check user preference
            if (localStorage.getItem('darkMode') === 'true') {
                $('body').addClass('dark-mode');
            }

            // Detect system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (e.matches && !localStorage.getItem('darkMode')) {
                    $('body').addClass('dark-mode');
                } else if (!e.matches && !localStorage.getItem('darkMode')) {
                    $('body').removeClass('dark-mode');
                }
            });

            // Resize handler for responsive layout
            function handleResize() {
                if (window.innerWidth <= 768) {
                    $('body').addClass('mobile-view');
                } else {
                    $('body').removeClass('mobile-view sidebar-open');
                    $('.sidebar').removeClass('active');
                }
            }
            
            // Initial resize check
            handleResize();
            
            // Add resize event listener
            $(window).resize(function() {
                handleResize();
            });

            // Load posts initially
            fetchPosts();
            
            // Pull to refresh functionality (simplified version)
            let touchStartY = 0;
            let touchEndY = 0;
            
            $(document).on('touchstart', function(e) {
                touchStartY = e.originalEvent.touches[0].clientY;
            });
            
            $(document).on('touchend', function(e) {
                touchEndY = e.originalEvent.changedTouches[0].clientY;
                if (touchEndY - touchStartY > 100 && window.scrollY <= 0) {
                    // User pulled down at the top of the page
                    fetchPosts();
                }
            });
        });
    </script>
</body>
</html>