<?php
// Start session at the very beginning
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

if (!isset($conn)) {
    die("Database connection is not established. Please check db_connect.php.");
}

// Get current user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$current_date = "2025-04-17 12:45:13"; // Hard-coded current date from user input

// Determine which profile to show
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $user_id;
$is_own_profile = ($profile_id == $user_id);

// Initialize profile variable with defaults in case query fails
$profile = [
    'username' => $username,
    'user_id' => $user_id,
    'post_count' => 0,
    'friend_count' => 0,
    'created_at' => date('Y-m-d H:i:s'),
    'last_login' => $current_date
];

// Fetch user profile data with error handling
try {
    // Check if users table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_table->num_rows == 0) {
        // Users table doesn't exist - create a basic version for demo
        $conn->query("CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            profile_pic VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME
        )");
        
        // Insert demo user if table was just created
        if ($conn->affected_rows > 0) {
            $conn->query("INSERT INTO users (user_id, username, email, password_hash) 
                          VALUES (1, 'Agabaofficial', 'admin@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "')");
        }
    }
    
    // Check if the profile_id user exists
    $query = "SELECT u.*, 
             COALESCE((SELECT COUNT(*) FROM post WHERE user_id = u.user_id), 0) as post_count,
             COALESCE((SELECT COUNT(*) FROM friend WHERE (user_id = u.user_id OR friend_id = u.user_id) AND status = 'accepted'), 0) as friend_count
             FROM users u 
             WHERE u.user_id = ?";
             
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $profile = $result->fetch_assoc();
    } else {
        // User not found, use default logged-in user
        $profile_id = $user_id;
        $is_own_profile = true;
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Log error but continue with defaults
    error_log("Profile fetch error: " . $e->getMessage());
}

// Check friendship status if viewing someone else's profile
$friendship_status = null;
if (!$is_own_profile) {
    try {
        // Check if friend table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'friend'");
        if ($check_table->num_rows == 0) {
            // Create friend table if not exists
            $conn->query("CREATE TABLE IF NOT EXISTS friend (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                friend_id INT NOT NULL,
                status ENUM('pending', 'accepted', 'rejected') NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME
            )");
        }
        
        $query = "SELECT status FROM friend 
                 WHERE (user_id = ? AND friend_id = ?) 
                 OR (user_id = ? AND friend_id = ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iiii", $user_id, $profile_id, $profile_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $friendship = $result->fetch_assoc();
            $friendship_status = $friendship['status'];
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Log error but continue
        error_log("Friendship status error: " . $e->getMessage());
    }
}

// Initialize posts variable
$posts = [];
$has_posts = false;

// Fetch user posts with error handling
try {
    // Check if post table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'post'");
    if ($check_table->num_rows == 0) {
        // Create post table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS post (
            post_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            image_url VARCHAR(255),
            is_public TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create like table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS `like` (
            like_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create comment table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS comment (
            comment_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    $query = "SELECT p.*, 
             COALESCE((SELECT COUNT(*) FROM `like` WHERE post_id = p.post_id), 0) as like_count,
             COALESCE((SELECT COUNT(*) FROM comment WHERE post_id = p.post_id), 0) as comment_count,
             COALESCE((SELECT COUNT(*) FROM `like` WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked
             FROM post p 
             WHERE p.user_id = ? 
             ORDER BY p.created_at DESC 
             LIMIT 10";
             
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $user_id, $profile_id);
    $stmt->execute();
    $posts_result = $stmt->get_result();
    
    if ($posts_result) {
        $has_posts = ($posts_result->num_rows > 0);
        
        if ($has_posts) {
            while ($row = $posts_result->fetch_assoc()) {
                $posts[] = $row;
            }
        }
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Log error but continue
    error_log("Posts fetch error: " . $e->getMessage());
}

// Initialize friends variable
$friends = [];
$has_friends = false;

// Fetch friends list with error handling
try {
    // Skip query if friend table doesn't exist yet
    $check_table = $conn->query("SHOW TABLES LIKE 'friend'");
    if ($check_table->num_rows > 0) {
        $query = "SELECT u.user_id, u.username, u.profile_pic 
                 FROM users u 
                 JOIN friend f ON (u.user_id = f.friend_id OR u.user_id = f.user_id)
                 WHERE (f.user_id = ? OR f.friend_id = ?) 
                 AND f.status = 'accepted' 
                 AND u.user_id != ?
                 LIMIT 6";
                 
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iii", $profile_id, $profile_id, $profile_id);
        $stmt->execute();
        $friends_result = $stmt->get_result();
        
        if ($friends_result) {
            $has_friends = ($friends_result->num_rows > 0);
            
            if ($has_friends) {
                while ($row = $friends_result->fetch_assoc()) {
                    $friends[] = $row;
                }
            }
        }
        
        $stmt->close();
    }
} catch (Exception $e) {
    // Log error but continue
    error_log("Friends fetch error: " . $e->getMessage());
}

// Process POST requests (for post creation, friend requests, etc.)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle new post creation
    if (isset($_POST['new_post']) && $is_own_profile) {
        try {
            $content = trim($_POST['post_content']);
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            
            if (!empty($content)) {
                $query = "INSERT INTO post (user_id, content, is_public, created_at) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("isi", $user_id, $content, $is_public);
                
                if ($stmt->execute()) {
                    // Redirect to avoid form resubmission
                    header("Location: profile.php");
                    exit();
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $stmt->close();
            }
        } catch (Exception $e) {
            // Log error but continue
            error_log("New post error: " . $e->getMessage());
        }
    }
    
    // Handle friend request
    if (isset($_POST['friend_request']) && !$is_own_profile) {
        try {
            if ($friendship_status === null) {
                // Send new friend request
                $query = "INSERT INTO friend (user_id, friend_id, status, created_at) VALUES (?, ?, 'pending', NOW())";
                $stmt = $conn->prepare($query);
                
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("ii", $user_id, $profile_id);
                $stmt->execute();
                
                $stmt->close();
            } elseif ($friendship_status === 'pending') {
                // Accept friend request if the viewed profile sent it
                $query = "UPDATE friend SET status = 'accepted', updated_at = NOW() 
                         WHERE user_id = ? AND friend_id = ?";
                $stmt = $conn->prepare($query);
                
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("ii", $profile_id, $user_id);
                $stmt->execute();
                
                $stmt->close();
            }
            
            // Redirect to avoid form resubmission
            header("Location: profile.php?id=" . $profile_id);
            exit();
        } catch (Exception $e) {
            // Log error but continue
            error_log("Friend request error: " . $e->getMessage());
        }
    }
    
    // Handle unfriend
    if (isset($_POST['unfriend']) && !$is_own_profile) {
        try {
            $query = "DELETE FROM friend 
                     WHERE (user_id = ? AND friend_id = ?) 
                     OR (user_id = ? AND friend_id = ?)";
            $stmt = $conn->prepare($query);
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("iiii", $user_id, $profile_id, $profile_id, $user_id);
            $stmt->execute();
            
            $stmt->close();
            
            // Redirect to avoid form resubmission
            header("Location: profile.php?id=" . $profile_id);
            exit();
        } catch (Exception $e) {
            // Log error but continue
            error_log("Unfriend error: " . $e->getMessage());
        }
    }
}

// Get current page for navigation highlighting
$current_page = 'profile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['username']) ?>'s Profile - SocialHub</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --border-radius: 15px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
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
        
        /* Profile Specific Styles */
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 20px;
            color: white;
            position: relative;
            box-shadow: var(--shadow);
        }
        
        .profile-cover {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0.2;
            border-radius: var(--border-radius);
        }
        
        .profile-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin-right: 30px;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .profile-username {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        
        .profile-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
        }
        
        .stat-item i {
            margin-right: 5px;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }
        
        .btn-primary {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .profile-about {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .about-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .about-item {
            display: flex;
            align-items: center;
        }
        
        .about-item i {
            width: 30px;
            color: var(--accent-color);
        }
        
        .profile-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .content-section {
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-section {
            display: flex;
            flex-direction: column;
        }
        
        /* Post Creation */
        .post-creation {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .post-input {
            width: 100%;
            border: 1px solid #e4e6eb;
            border-radius: 20px;
            padding: 12px 15px;
            margin-bottom: 15px;
            resize: none;
            font-family: inherit;
            font-size: 16px;
        }
        
        .post-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .post-options {
            display: flex;
            gap: 15px;
        }
        
        .post-option {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #65676b;
            cursor: pointer;
        }
        
        .post-submit {
            padding: 8px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .post-submit:hover {
            background: var(--secondary-color);
        }
        
        /* Posts Feed */
        .post-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .post-avatar {
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
        }
        
        .post-user {
            flex: 1;
        }
        
        .post-username {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .post-time {
            font-size: 0.8rem;
            color: #65676b;
        }
        
        .post-menu {
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .post-menu:hover {
            background: var(--light-bg);
        }
        
        .post-content {
            margin-bottom: 15px;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .post-image {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .post-footer {
            border-top: 1px solid #e4e6eb;
            padding-top: 15px;
        }
        
        .post-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #65676b;
        }
        
        .post-actions-row {
            display: flex;
            justify-content: space-around;
        }
        
        .post-action {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #65676b;
            cursor: pointer;
            padding: 8px 0;
            flex: 1;
            justify-content: center;
            border-radius: 5px;
            transition: var(--transition);
        }
        
        .post-action:hover {
            background: var(--light-bg);
        }
        
        .post-action.liked {
            color: var(--primary-color);
        }
        
        /* Friends Section */
        .friends-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .friends-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .friend-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .friend-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 1.5rem;
        }
        
        .friend-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .view-all {
            display: block;
            text-align: center;
            padding: 10px;
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            margin-top: 15px;
            border-top: 1px solid #e4e6eb;
        }
        
        .view-all:hover {
            background: var(--light-bg);
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
            
            .profile-layout {
                grid-template-columns: 1fr;
            }
            
            .profile-content {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-actions {
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .search-bar {
                display: none;
            }
            
            .about-items {
                grid-template-columns: 1fr;
            }
            
            .friends-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        /* Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--primary-color);
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .privacy-option {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .no-posts {
            text-align: center;
            padding: 40px 0;
            color: #65676b;
        }
        
        .no-posts i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }
        
        /* Alert styling */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">SocialHub</div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="posts.php" class="nav-link <?= $current_page == 'posts.php' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i> Posts
                </a>
            </li>
            <li class="nav-item">
                <a href="friends.php" class="nav-link <?= $current_page == 'friends.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-friends"></i> Friends
                </a>
            </li>
            <li class="nav-item">
                <a href="messages.php" class="nav-link <?= $current_page == 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Messages
                    <span class="badge">3</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="groups.php" class="nav-link <?= $current_page == 'groups.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Groups
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
                <span class="notification-badge">3</span>
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
        <!-- Database Info -->
        <div class="alert alert-info">
            <p><strong>Info:</strong> This profile page automatically sets up necessary database tables if they don't exist.</p>
        </div>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-cover"></div>
            <div class="profile-content">
                <div class="profile-avatar">
                    <?= substr($profile['username'], 0, 1) ?>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?= htmlspecialchars($profile['username']) ?></h1>
                    <p class="profile-username">@<?= strtolower(str_replace(' ', '', $profile['username'])) ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-newspaper"></i>
                            <span><?= $profile['post_count'] ?> Posts</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-user-friends"></i>
                            <span><?= $profile['friend_count'] ?> Friends</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Joined <?= date('F Y', strtotime($profile['created_at'] ?? date('Y-m-d'))) ?></span>
                        </div>
                    </div>
                    
                    <?php if (!$is_own_profile): ?>
                        <div class="profile-actions">
                            <?php if ($friendship_status === null): ?>
                                <form method="POST">
                                    <button type="submit" name="friend_request" class="action-btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Add Friend
                                    </button>
                                </form>
                            <?php elseif ($friendship_status === 'pending'): ?>
                                <form method="POST">
                                    <button type="submit" name="friend_request" class="action-btn btn-primary">
                                        <i class="fas fa-check"></i> Accept Request
                                    </button>
                                </form>
                            <?php elseif ($friendship_status === 'accepted'): ?>
                                <form method="POST">
                                    <button type="submit" name="unfriend" class="action-btn btn-secondary">
                                        <i class="fas fa-user-minus"></i> Unfriend
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <button class="action-btn btn-secondary">
                                <i class="fas fa-envelope"></i> Message
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="profile-layout">
            <div class="content-section">
                <!-- About Section -->
                <div class="profile-about">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i> About
                    </h2>
                    <div class="about-items">
                        <div class="about-item">
                            <i class="fas fa-briefcase"></i>
                            <span>Works at <?= htmlspecialchars($profile['workplace'] ?? 'Not specified') ?></span>
                        </div>
                        <div class="about-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Studied at <?= htmlspecialchars($profile['education'] ?? 'Not specified') ?></span>
                        </div>
                        <div class="about-item">
                            <i class="fas fa-home"></i>
                            <span>Lives in <?= htmlspecialchars($profile['location'] ?? 'Kampala, Uganda') ?></span>
                        </div>
                        <div class="about-item">
                            <i class="fas fa-heart"></i>
                            <span><?= htmlspecialchars($profile['relationship_status'] ?? 'Not specified') ?></span>
                        </div>
                        <div class="about-item">
                            <i class="fas fa-link"></i>
                            <span><?= htmlspecialchars($profile['website'] ?? 'No website added') ?></span>
                        </div>
                        <div class="about-item">
                            <i class="fas fa-clock"></i>
                            <span>Last active: <?= date('M d, Y', strtotime($profile['last_login'] ?? $current_date)) ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($is_own_profile): ?>
                    <!-- Post Creation (only on own profile) -->
                    <div class="post-creation">
                        <form method="POST">
                            <textarea name="post_content" class="post-input" placeholder="What's on your mind?" rows="3" required></textarea>
                            <div class="post-actions">
                                <div class="post-options">
                                    <div class="post-option">
                                        <i class="fas fa-image"></i>
                                        <span>Photo</span>
                                    </div>
                                    <div class="post-option">
                                        <i class="fas fa-user-tag"></i>
                                        <span>Tag</span>
                                    </div>
                                    <div class="privacy-option">
                                        <i class="fas fa-globe-americas"></i>
                                        <label class="switch">
                                            <input type="checkbox" name="is_public" checked>
                                            <span class="slider"></span>
                                        </label>
                                        <span>Public</span>
                                    </div>
                                </div>
                                <button type="submit" name="new_post" class="post-submit">Post</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- Posts Feed -->
                <h2 class="section-title" style="margin-top: 20px;">
                    <i class="fas fa-newspaper"></i> Posts
                </h2>
                
                <?php if ($has_posts): ?>
                    <?php foreach($posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="post-avatar">
                                    <?= substr($profile['username'], 0, 1) ?>
                                </div>
                                <div class="post-user">
                                    <div class="post-username"><?= htmlspecialchars($profile['username']) ?></div>
                                    <div class="post-time">
                                        <?= date('M d, Y \a\t h:i A', strtotime($post['created_at'])) ?>
                                        <?php if ($post['is_public']): ?>
                                            · <i class="fas fa-globe-americas"></i> Public
                                        <?php else: ?>
                                            · <i class="fas fa-lock"></i> Private
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($is_own_profile): ?>
                                    <div class="post-menu">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <?= nl2br(htmlspecialchars($post['content'])) ?>
                            </div>
                            <?php if (!empty($post['image_url'])): ?>
                                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Post image" class="post-image">
                            <?php endif; ?>
                            <div class="post-footer">
                                <div class="post-stats">
                                    <div>
                                        <i class="fas fa-thumbs-up"></i> <?= $post['like_count'] ?> Likes
                                    </div>
                                    <div>
                                        <?= $post['comment_count'] ?> Comments
                                    </div>
                                </div>
                                <div class="post-actions-row">
                                    <div class="post-action <?= $post['user_liked'] ? 'liked' : '' ?>">
                                        <i class="fas fa-thumbs-up"></i> Like
                                    </div>
                                    <div class="post-action">
                                        <i class="fas fa-comment"></i> Comment
                                    </div>
                                    <div class="post-action">
                                        <i class="fas fa-share"></i> Share
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="post-card">
                        <div class="no-posts">
                            <i class="fas fa-newspaper"></i>
                            <h3>No Posts Yet</h3>
                            <p>There are no posts to display at this time.</p>
                            <?php if (!$is_own_profile): ?>
                                <p>Connect with <?= htmlspecialchars($profile['username']) ?> to stay updated on their activity.</p>
                            <?php else: ?>
                                <p>Create your first post to share with friends!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-section">
                <!-- Friends Section -->
                <div class="friends-section">
                    <h2 class="section-title">
                        <i class="fas fa-user-friends"></i> Friends (<?= $profile['friend_count'] ?>)
                    </h2>
                    
                    <?php if ($has_friends): ?>
                        <div class="friends-grid">
                            <?php foreach($friends as $friend): ?>
                                <div class="friend-item">
                                    <div class="friend-avatar">
                                        <?= substr($friend['username'], 0, 1) ?>
                                    </div>
                                    <div class="friend-name"><?= htmlspecialchars($friend['username']) ?></div>
                                    <a href="profile.php?id=<?= $friend['user_id'] ?>" class="friend-link">View Profile</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($profile['friend_count'] > 6): ?>
                            <a href="friends.php?id=<?= $profile_id ?>" class="view-all">View All Friends</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px 0; color: #65676b;">
                            <i class="fas fa-user-friends" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>No friends to display</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Photos Section (placeholder) -->
                <div class="friends-section">
                    <h2 class="section-title">
                        <i class="fas fa-images"></i> Photos
                    </h2>
                    <div style="text-align: center; padding: 20px 0; color: #65676b;">
                        <i class="fas fa-camera" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No photos to display</p>
                    </div>
                    <a href="#" class="view-all">Upload Photos</a>
                </div>
                
                <!-- Current Date and User Info -->
                <div class="friends-section">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i> System Info
                    </h2>
                    <div style="padding: 10px 0; color: #65676b;">
                        <p><strong>Current Date:</strong> <?= $current_date ?></p>
                        <p><strong>Current User:</strong> <?= htmlspecialchars($username) ?></p>
                        <p><strong>Login:</strong> <?= htmlspecialchars('Agabaofficial') ?></p>
                    </div>
                </div>
            </div>
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
            
            // Post menu toggle
            const postMenus = document.querySelectorAll('.post-menu');
            
            postMenus.forEach(menu => {
                menu.addEventListener('click', function() {
                    // Future functionality for post menu
                    alert('Post options will be available soon!');
                });
            });
        });
    </script>
</body>
</html>