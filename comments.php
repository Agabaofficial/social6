<?php
include 'db_connect.php';
session_start();

// Assume user is logged in as Agabaofficial (user_id 1 based on database)
$current_user_id = 1;
$current_username = "Agabaofficial";

// Handle like action for comment
if(isset($_POST['like_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    
    // Check if user already liked this comment
    $check_like = $conn->query("SELECT * FROM `like` WHERE user_id = $current_user_id AND comment_id = $comment_id");
    
    if($check_like->num_rows > 0) {
        // Unlike the comment
        $conn->query("DELETE FROM `like` WHERE user_id = $current_user_id AND comment_id = $comment_id");
    } else {
        // Like the comment
        $conn->query("INSERT INTO `like` (user_id, comment_id) VALUES ($current_user_id, $comment_id)");
    }
    
    header("Location: comments.php");
    exit();
}

// Fetch comments with post details and user info
$comments_query = "
    SELECT c.*, p.content as post_content, p.post_id,
           u.username as commenter_username, u.full_name as commenter_name,
           pu.username as post_username, pu.full_name as post_author_name,
           (SELECT COUNT(*) FROM `like` WHERE comment_id = c.comment_id) as like_count
    FROM comment c
    JOIN post p ON c.post_id = p.post_id
    JOIN users u ON c.user_id = u.user_id
    JOIN users pu ON p.user_id = pu.user_id
    ORDER BY c.created_at DESC
";

$comments_result = $conn->query($comments_query);

// Current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments | MySocial</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #38b000;
            --warning-color: #ffaa00;
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
        
        /* Comments list */
        .comment-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .comment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .comment-user {
            display: flex;
            align-items: center;
        }
        
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .comment-info h4 {
            margin: 0;
            font-size: 16px;
        }
        
        .comment-date {
            font-size: 14px;
            color: var(--dark-gray);
        }
        
        .comment-content {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 15px;
        }
        
        .comment-post-info {
            background: var(--light-gray);
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .comment-post-info a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .comment-post-info a:hover {
            text-decoration: underline;
        }
        
        .comment-actions {
            display: flex;
            gap: 15px;
        }
        
        .comment-action {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--dark-gray);
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .comment-action:hover {
            color: var(--primary-color);
        }
        
        .comment-action.liked {
            color: #e63946;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .empty-state i {
            font-size: 60px;
            color: var(--medium-gray);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--dark-gray);
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
        }
        
        @media (max-width: 576px) {
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
            <input type="text" placeholder="Search comments...">
        </div>
        <div class="user-menu">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">2</span>
            </div>
            <div class="avatar">
                A
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Comments</h1>
        <p>Browse all comments from posts across the platform.</p>
        
        <!-- Comments list -->
        <div class="comments-list">
            <?php if($comments_result && $comments_result->num_rows > 0): ?>
                <?php while($comment = $comments_result->fetch_assoc()): ?>
                    <?php 
                    // Check if current user has liked this comment
                    $liked_query = $conn->query("SELECT * FROM `like` WHERE user_id = $current_user_id AND comment_id = {$comment['comment_id']}");
                    $has_liked = $liked_query && $liked_query->num_rows > 0;
                    
                    // Format the comment date for display
                    $comment_date = new DateTime($comment['created_at']);
                    $now = new DateTime();
                    $interval = $comment_date->diff($now);
                    
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
                    
                    // Get first letter of username for avatar
                    $avatar_letter = substr($comment['commenter_name'] ?? $comment['commenter_username'], 0, 1);
                    ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <div class="comment-user">
                                <div class="comment-avatar">
                                    <?= $avatar_letter ?>
                                </div>
                                <div class="comment-info">
                                    <h4><?= htmlspecialchars($comment['commenter_name'] ?? $comment['commenter_username']) ?></h4>
                                    <div class="comment-date"><?= $time_ago ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="comment-content">
                            <?= nl2br(htmlspecialchars($comment['content'])) ?>
                        </div>
                        
                        <div class="comment-post-info">
                            Commented on post by 
                            <strong><?= htmlspecialchars($comment['post_author_name'] ?? $comment['post_username']) ?></strong>: 
                            "<a href="posts.php?post=<?= $comment['post_id'] ?>"><?= substr(htmlspecialchars($comment['post_content']), 0, 80) . (strlen($comment['post_content']) > 80 ? '...' : '') ?></a>"
                        </div>
                        
                        <div class="comment-actions">
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                <button type="submit" name="like_comment" class="comment-action <?= $has_liked ? 'liked' : '' ?>" style="background:none;border:none;cursor:pointer;padding:0;">
                                    <i class="<?= $has_liked ? 'fas' : 'far' ?> fa-heart"></i>
                                    <span><?= $comment['like_count'] ?> Like<?= $comment['like_count'] != 1 ? 's' : '' ?></span>
                                </button>
                            </form>
                            
                            <div class="comment-action">
                                <i class="fas fa-reply"></i>
                                <span>Reply</span>
                            </div>
                            
                            <?php if($comment['user_id'] == $current_user_id): ?>
                            <div class="comment-action">
                                <i class="fas fa-edit"></i>
                                <span>Edit</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-comment-dots"></i>
                    <h3>No Comments Yet</h3>
                    <p>Be the first to comment on posts in your network!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for sidebar toggle on mobile -->
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