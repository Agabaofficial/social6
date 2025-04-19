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
$current_date = date('Y-m-d H:i:s'); // Current UTC time: 2025-04-18 17:04:38

// Fetch user data
$user_query = "SELECT * FROM users WHERE user_id = $current_user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Fetch privacy settings
$privacy_query = "SELECT * FROM privacysetting WHERE user_id = $current_user_id";
$privacy_result = $conn->query($privacy_query);

if($privacy_result && $privacy_result->num_rows > 0) {
    $privacy = $privacy_result->fetch_assoc();
} else {
    // Default privacy settings if none exist
    $privacy = [
        'profile_visibility' => 'public',
        'post_default_visibility' => 'public',
        'message_privacy' => 'anyone'
    ];
    
    // Insert default privacy settings
    $conn->query("INSERT INTO privacysetting (user_id, profile_visibility, post_default_visibility, message_privacy) 
                  VALUES ($current_user_id, 'public', 'public', 'anyone')");
    
    $privacy_query = "SELECT * FROM privacysetting WHERE user_id = $current_user_id";
    $privacy_result = $conn->query($privacy_query);
    $privacy = $privacy_result->fetch_assoc();
}

// Handle form submissions
if(isset($_POST['update_profile'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $bio = $conn->real_escape_string($_POST['bio']);
    $location = $conn->real_escape_string($_POST['location']);
    $website = $conn->real_escape_string($_POST['website']);
    
    // Update user profile
    $update_query = "UPDATE users SET 
                    full_name = '$full_name', 
                    email = '$email', 
                    bio = '$bio',
                    location = '$location',
                    website = '$website'
                    WHERE user_id = $current_user_id";
    
    if($conn->query($update_query)) {
        $success_message = "Profile updated successfully.";
        
        // Refresh user data
        $user_result = $conn->query($user_query);
        $user = $user_result->fetch_assoc();
    } else {
        $error_message = "Failed to update profile: " . $conn->error;
    }
} elseif(isset($_POST['update_privacy'])) {
    $profile_visibility = $conn->real_escape_string($_POST['profile_visibility']);
    $post_default_visibility = $conn->real_escape_string($_POST['post_default_visibility']);
    $message_privacy = $conn->real_escape_string($_POST['message_privacy']);
    $tag_privacy = $conn->real_escape_string($_POST['tag_privacy']);
    $online_status = isset($_POST['online_status']) ? 1 : 0;
    $read_receipts = isset($_POST['read_receipts']) ? 1 : 0;
    
    // Update privacy settings
    $update_query = "UPDATE privacysetting SET 
                     profile_visibility = '$profile_visibility', 
                     post_default_visibility = '$post_default_visibility', 
                     message_privacy = '$message_privacy',
                     tag_privacy = '$tag_privacy',
                     online_status = $online_status,
                     read_receipts = $read_receipts
                     WHERE user_id = $current_user_id";
    
    if($conn->query($update_query)) {
        $success_message = "Privacy settings updated successfully.";
        
        // Refresh privacy data
        $privacy_result = $conn->query($privacy_query);
        $privacy = $privacy_result->fetch_assoc();
    } else {
        $error_message = "Failed to update privacy settings: " . $conn->error;
    }
} elseif(isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if(password_verify($current_password, $user['password_hash'])) {
        // Check if new password and confirmation match
        if($new_password === $confirm_password) {
            // Check password strength
            if(strlen($new_password) < 8) {
                $error_message = "Password must be at least 8 characters long.";
            } else {
                // Hash the new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE users SET password_hash = '$new_password_hash' WHERE user_id = $current_user_id";
                
                if($conn->query($update_query)) {
                    $success_message = "Password updated successfully.";
                } else {
                    $error_message = "Failed to update password: " . $conn->error;
                }
            }
        } else {
            $error_message = "New password and confirmation do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
} elseif(isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $friend_requests = isset($_POST['friend_requests']) ? 1 : 0;
    $post_likes = isset($_POST['post_likes']) ? 1 : 0;
    $post_comments = isset($_POST['post_comments']) ? 1 : 0;
    $mentions = isset($_POST['mentions']) ? 1 : 0;
    
    // Check if notification settings exist
    $check_query = "SELECT * FROM notificationsetting WHERE user_id = $current_user_id";
    $check_result = $conn->query($check_query);
    
    if($check_result && $check_result->num_rows > 0) {
        // Update existing notification settings
        $update_query = "UPDATE notificationsetting SET 
                         email_notifications = $email_notifications,
                         friend_requests = $friend_requests,
                         post_likes = $post_likes,
                         post_comments = $post_comments,
                         mentions = $mentions
                         WHERE user_id = $current_user_id";
    } else {
        // Insert new notification settings
        $update_query = "INSERT INTO notificationsetting 
                        (user_id, email_notifications, friend_requests, post_likes, post_comments, mentions) 
                        VALUES 
                        ($current_user_id, $email_notifications, $friend_requests, $post_likes, $post_comments, $mentions)";
    }
    
    if($conn->query($update_query)) {
        $success_message = "Notification preferences updated successfully.";
    } else {
        $error_message = "Failed to update notification preferences: " . $conn->error;
    }
}

// Fetch notification settings
$notification_query = "SELECT * FROM notificationsetting WHERE user_id = $current_user_id";
$notification_result = $conn->query($notification_query);

if($notification_result && $notification_result->num_rows > 0) {
    $notifications = $notification_result->fetch_assoc();
} else {
    // Default notification settings
    $notifications = [
        'email_notifications' => 1,
        'friend_requests' => 1,
        'post_likes' => 1,
        'post_comments' => 1,
        'mentions' => 1
    ];
    
    // Insert default notification settings
    $conn->query("INSERT INTO notificationsetting 
                (user_id, email_notifications, friend_requests, post_likes, post_comments, mentions) 
                VALUES 
                ($current_user_id, 1, 1, 1, 1, 1)");
    
    $notification_result = $conn->query($notification_query);
    $notifications = $notification_result->fetch_assoc();
}

// Fetch account stats
$post_count = $conn->query("SELECT COUNT(*) as count FROM post WHERE user_id = $current_user_id")->fetch_assoc()['count'];
$friend_count = $conn->query("SELECT COUNT(*) as count FROM friend WHERE (user_id1 = $current_user_id OR user_id2 = $current_user_id) AND status = 'accepted'")->fetch_assoc()['count'];
$like_count = $conn->query("SELECT COUNT(*) as count FROM `like` WHERE user_id = $current_user_id")->fetch_assoc()['count'];
$comment_count = $conn->query("SELECT COUNT(*) as count FROM comment WHERE user_id = $current_user_id")->fetch_assoc()['count'];
$group_count = $conn->query("SELECT COUNT(*) as count FROM groupmembership WHERE user_id = $current_user_id")->fetch_assoc()['count'];
$unread_messages = $conn->query("SELECT COUNT(*) as count FROM message WHERE receiver_id = $current_user_id AND is_read = 0")->fetch_assoc()['count'];

// Current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get active tab from URL if set
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | MySocial</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #1DA1F2; /* Twitter Blue */
            --primary-hover: #1a91da;
            --secondary-color: #14171A;
            --accent-color: #657786;
            --success-color: #17BF63;
            --warning-color: #FFAD1F;
            --danger-color: #E0245E;
            --text-color: #14171A;
            --text-secondary: #657786;
            --text-light: #AAB8C2;
            --bg-color: #F5F8FA;
            --light-bg: #E1E8ED;
            --card-bg: #FFFFFF;
            --border-color: #E1E8ED;
            --border-radius: 16px;
            --button-radius: 30px;
            --sidebar-width: 240px;
            --sidebar-collapsed-width: 70px;
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
            --text-color: #FFFFFF;
            --text-secondary: #AAB8C2;
            --text-light: #657786;
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
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.3s ease, color 0.3s ease;
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
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .sidebar-logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
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
            padding: 30px;
            padding-top: calc(var(--header-height) + 30px);
            transition: var(--transition);
            min-height: 100vh;
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
            padding: 0 30px;
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
            background: var(--card-bg);
            box-shadow: 0 0 0 1px var(--primary-color);
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
            gap: 20px;
        }
        
        .theme-toggle {
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
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .notification-icon {
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
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .alert-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-icon {
            font-size: 20px;
        }
        
        .alert-close {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .alert-close:hover {
            opacity: 1;
        }
        
        .alert-success {
            background: rgba(23, 191, 99, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background: rgba(224, 36, 94, 0.1);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        
        .page-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        /* Settings Container */
        .settings-container {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 30px;
        }
        
        /* Settings Tabs */
        .settings-tabs {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .tabs-header {
            display: flex;
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
            border-bottom: 1px solid var(--border-color);
        }
        
        .tabs-header::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        .tab {
            padding: 16px 20px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            white-space: nowrap;
            position: relative;
            transition: var(--transition);
        }
        
        .tab.active {
            color: var(--primary-color);
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px 3px 0 0;
        }
        
        .tab-content {
            display: none;
            padding: 25px;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Form Styles */
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        
        .form-help {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 5px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 15px;
            background: var(--light-bg);
            color: var(--text-color);
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
            background: var(--card-bg);
        }
        
        .form-control::placeholder {
            color: var(--text-secondary);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon .form-control {
            padding-left: 35px;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .form-check {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            cursor: pointer;
            font-weight: normal;
        }
        
        .form-check-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: relative;
            height: 20px;
            width: 20px;
            background-color: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-right: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .form-check:hover .checkmark {
            background-color: var(--border-color);
        }
        
        .form-check-input:checked ~ .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .checkmark:after {
            content: '';
            position: absolute;
            display: none;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .form-check-input:checked ~ .checkmark:after {
            display: block;
        }
        
        .radio-container {
            margin-bottom: 15px;
        }
        
        .form-radio {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            cursor: pointer;
            font-weight: normal;
        }
        
        .form-radio-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .radio-mark {
            position: relative;
            height: 20px;
            width: 20px;
            background-color: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .form-radio:hover .radio-mark {
            background-color: var(--border-color);
        }
        
        .form-radio-input:checked ~ .radio-mark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .radio-mark:after {
            content: '';
            position: absolute;
            display: none;
            top: 5px;
            left: 5px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
        }
        
        .form-radio-input:checked ~ .radio-mark:after {
            display: block;
        }
        
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: var(--button-radius);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--light-bg);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background: var(--border-color);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c91c54;
        }
        
        .btn-lg {
            padding: 12px 24px;
            font-size: 16px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-icon {
            padding: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        /* Profile Styles */
        .profile-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .profile-cover {
            background: linear-gradient(120deg, var(--primary-color), #4dabf7);
            height: 100px;
            position: relative;
        }
        
        .profile-avatar-container {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 40px;
            border: 4px solid var(--card-bg);
            position: relative;
        }
        
        .upload-avatar {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--card-bg);
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .upload-avatar:hover {
            background: var(--light-bg);
        }
        
        .profile-info {
            padding: 60px 20px 20px;
            text-align: center;
        }
        
        .profile-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .profile-username {
            color: var(--text-secondary);
            font-size: 15px;
            margin-bottom: 15px;
        }
        
        .profile-bio {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .profile-meta {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        /* Account Info */
        .account-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .card-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .card-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 5px;
        }
        
        .account-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .detail-label {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .detail-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(23, 191, 99, 0.1);
            color: var(--success-color);
        }
        
        .badge-warning {
            background: rgba(255, 173, 31, 0.1);
            color: var(--warning-color);
        }
        
        .badge-danger {
            background: rgba(224, 36, 94, 0.1);
            color: var(--danger-color);
        }
        
        /* Footer */
        .settings-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .footer-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .footer-link:hover {
            color: var(--primary-color);
        }
        
        .copyright {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 1200px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: var(--sidebar-collapsed-width);
                z-index: 1000;
            }
            
            .sidebar-logo span {
                display: none;
            }
            
            .nav-link span, 
            .nav-link .badge {
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
            
            .main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .top-nav {
                left: var(--sidebar-collapsed-width);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-logo span {
                display: block;
            }
            
            .nav-link span, 
            .nav-link .badge {
                display: inline-flex;
            }
            
            .nav-link {
                justify-content: flex-start;
                padding: 15px 20px;
            }
            
            .nav-link i {
                margin-right: 15px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
                padding-top: calc(var(--header-height) + 20px);
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
            
            .search-bar {
                display: none;
            }
            
            .tabs-header {
                flex-wrap: wrap;
            }
            
            .tab {
                flex-grow: 1;
                text-align: center;
                padding: 12px 15px;
            }
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
        
        .hidden {
            display: none !important;
        }
        
        /* Loading spinner */
        .loader {
            width: 20px;
            height: 20px;
            border: 3px solid var(--light-bg);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s linear infinite;
            margin-right: 10px;
            display: none;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Toggle switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
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
            background-color: var(--light-bg);
            transition: .4s;
            border-radius: 24px;
            border: 1px solid var(--border-color);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .slider {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }
        
        /* Password strength meter */
        .password-strength {
            margin-top: 8px;
            height: 5px;
            background: var(--light-bg);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            border-radius: 5px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-weak {
            background-color: var(--danger-color);
            width: 25%;
        }
        
        .strength-medium {
            background-color: var(--warning-color);
            width: 50%;
        }
        
        .strength-strong {
            background-color: var(--success-color);
            width: 100%;
        }
        
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
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
                    <i class="fas fa-home"></i> <span>Home</span>
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
                    <?php if($unread_messages > 0): ?>
                        <span class="badge"><?= $unread_messages ?></span>
                    <?php endif; ?>
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
        <div class="left-section">
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="page-title">Settings</div>
        </div>
        
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search MySocial...">
        </div>
        
        <div class="user-menu">
            <div class="theme-toggle" id="theme-toggle">
                <i class="fas fa-moon"></i>
            </div>
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <?php if($unread_messages > 0): ?>
                    <span class="notification-badge"><?= $unread_messages ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar">
                <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Alert Messages -->
        <?php if(isset($success_message)): ?>
            <div class="alert alert-success">
                <div class="alert-content">
                    <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                    <div><?= $success_message ?></div>
                </div>
                <button type="button" class="alert-close">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="alert alert-danger">
                <div class="alert-content">
                    <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div><?= $error_message ?></div>
                </div>
                <button type="button" class="alert-close">&times;</button>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>Account Settings</h1>
            <p>Manage your account preferences, privacy, and security settings</p>
        </div>
        
        <div class="settings-container">
            <div class="settings-main">
                <div class="settings-tabs">
                    <div class="tabs-header">
                        <div class="tab <?= $active_tab == 'profile' ? 'active' : '' ?>" data-tab="profile">
                            <i class="fas fa-user"></i> Profile
                        </div>
                        <div class="tab <?= $active_tab == 'privacy' ? 'active' : '' ?>" data-tab="privacy">
                            <i class="fas fa-shield-alt"></i> Privacy
                        </div>
                        <div class="tab <?= $active_tab == 'password' ? 'active' : '' ?>" data-tab="password">
                            <i class="fas fa-lock"></i> Password
                        </div>
                        <div class="tab <?= $active_tab == 'notifications' ? 'active' : '' ?>" data-tab="notifications">
                            <i class="fas fa-bell"></i> Notifications
                        </div>
                        <div class="tab <?= $active_tab == 'account' ? 'active' : '' ?>" data-tab="account">
                            <i class="fas fa-id-card"></i> Account
                        </div>
                    </div>
                    
                    <!-- Profile Tab -->
                    <div class="tab-content <?= $active_tab == 'profile' ? 'active' : '' ?>" id="profile-tab">
                        <form method="POST" action="" id="profile-form">
                            <div class="form-section">
                                <div class="form-section-title">Personal Information</div>
                                
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input 
                                        type="text" 
                                        id="full_name" 
                                        name="full_name" 
                                        class="form-control" 
                                        value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                        placeholder="Your full name"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="bio">Bio</label>
                                    <textarea 
                                        id="bio" 
                                        name="bio" 
                                        class="form-control" 
                                        placeholder="Tell people about yourself"
                                        maxlength="160"
                                    ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                    <div class="form-help">
                                        <span id="bio-count">0</span>/160 characters
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <input 
                                            type="text" 
                                            id="location" 
                                            name="location" 
                                            class="form-control" 
                                            value="<?= htmlspecialchars($user['location'] ?? '') ?>"
                                            placeholder="Your location"
                                        >
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="website">Website</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-link"></i>
                                        <input 
                                            type="url" 
                                            id="website" 
                                            name="website" 
                                            class="form-control" 
                                            value="<?= htmlspecialchars($user['website'] ?? '') ?>"
                                            placeholder="https://yourwebsite.com"
                                        >
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Contact Information</div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-envelope"></i>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            name="email" 
                                            class="form-control" 
                                            value="<?= htmlspecialchars($user['email']) ?>" 
                                            required
                                        >
                                    </div>
                                    <div class="form-help">
                                        We'll never share your email with anyone else.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <div class="loader" id="profile-loader"></div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Privacy Tab -->
                    <div class="tab-content <?= $active_tab == 'privacy' ? 'active' : '' ?>" id="privacy-tab">
                        <form method="POST" action="" id="privacy-form">
                            <div class="form-section">
                                <div class="form-section-title">Profile Privacy</div>
                                
                                <div class="form-group">
                                    <label>Who can see your profile</label>
                                    <div class="radio-container">
                                        <label class="form-radio">
                                            <input type="radio" name="profile_visibility" class="form-radio-input" value="public" <?= $privacy['profile_visibility'] == 'public' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Public</strong>
                                                <div class="form-help">Anyone can see your profile</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="profile_visibility" class="form-radio-input" value="friends" <?= $privacy['profile_visibility'] == 'friends' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Friends Only</strong>
                                                <div class="form-help">Only your friends can see your profile</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="profile_visibility" class="form-radio-input" value="private" <?= $privacy['profile_visibility'] == 'private' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Private</strong>
                                                <div class="form-help">Only you can see your profile</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Post Privacy</div>
                                
                                <div class="form-group">
                                    <label>Default post visibility</label>
                                    <div class="radio-container">
                                        <label class="form-radio">
                                            <input type="radio" name="post_default_visibility" class="form-radio-input" value="public" <?= $privacy['post_default_visibility'] == 'public' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Public</strong>
                                                <div class="form-help">Anyone can see your posts</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="post_default_visibility" class="form-radio-input" value="friends" <?= $privacy['post_default_visibility'] == 'friends' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Friends Only</strong>
                                                <div class="form-help">Only your friends can see your posts</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="post_default_visibility" class="form-radio-input" value="private" <?= $privacy['post_default_visibility'] == 'private' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Private</strong>
                                                <div class="form-help">Only you can see your posts</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Who can tag you</label>
                                    <div class="radio-container">
                                        <label class="form-radio">
                                            <input type="radio" name="tag_privacy" class="form-radio-input" value="anyone" <?= ($privacy['tag_privacy'] ?? 'anyone') == 'anyone' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Anyone</strong>
                                                <div class="form-help">Anyone can tag you in posts</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="tag_privacy" class="form-radio-input" value="friends" <?= ($privacy['tag_privacy'] ?? 'friends') == 'friends' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Friends Only</strong>
                                                <div class="form-help">Only your friends can tag you</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="tag_privacy" class="form-radio-input" value="none" <?= ($privacy['tag_privacy'] ?? '') == 'none' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>No One</strong>
                                                <div class="form-help">No one can tag you in posts</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Messages</div>
                                
                                <div class="form-group">
                                    <label>Who can message you</label>
                                    <div class="radio-container">
                                        <label class="form-radio">
                                            <input type="radio" name="message_privacy" class="form-radio-input" value="anyone" <?= $privacy['message_privacy'] == 'anyone' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Anyone</strong>
                                                <div class="form-help">Anyone can send you messages</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="message_privacy" class="form-radio-input" value="friends" <?= $privacy['message_privacy'] == 'friends' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>Friends Only</strong>
                                                <div class="form-help">Only your friends can send you messages</div>
                                            </div>
                                        </label>
                                        
                                        <label class="form-radio">
                                            <input type="radio" name="message_privacy" class="form-radio-input" value="none" <?= $privacy['message_privacy'] == 'none' ? 'checked' : '' ?>>
                                            <span class="radio-mark"></span>
                                            <div>
                                                <strong>No One</strong>
                                                <div class="form-help">No one can send you messages</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-check">
                                        <input type="checkbox" name="read_receipts" class="form-check-input" <?= isset($privacy['read_receipts']) && $privacy['read_receipts'] ? 'checked' : '' ?>>
                                        <span class="checkmark"></span>
                                        <div>
                                            <strong>Send read receipts</strong>
                                            <div class="form-help">Let others know when you've seen their messages</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Online Status</div>
                                
                                <div class="form-group">
                                    <label class="form-check">
                                        <input type="checkbox" name="online_status" class="form-check-input" <?= isset($privacy['online_status']) && $privacy['online_status'] ? 'checked' : '' ?>>
                                        <span class="checkmark"></span>
                                        <div>
                                            <strong>Show when you're active</strong>
                                            <div class="form-help">Let others see when you're online</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <div class="loader" id="privacy-loader"></div>
                                <button type="submit" name="update_privacy" class="btn btn-primary">
                                    <i class="fas fa-shield-alt"></i> Update Privacy Settings
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Password Tab -->
                    <div class="tab-content <?= $active_tab == 'password' ? 'active' : '' ?>" id="password-tab">
                        <form method="POST" action="" id="password-form">
                            <div class="form-section">
                                <div class="form-section-title">Change Password</div>
                                
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input 
                                            type="password" 
                                            id="current_password" 
                                            name="current_password" 
                                            class="form-control" 
                                            required
                                        >
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-key"></i>
                                        <input 
                                            type="password" 
                                            id="new_password" 
                                            name="new_password" 
                                            class="form-control" 
                                            required
                                            minlength="8"
                                        >
                                    </div>
                                    <div class="password-strength">
                                                                            <div class="password-strength">
                                        <div class="strength-meter" id="strength-meter"></div>
                                    </div>
                                    <div class="strength-text" id="strength-text">Password strength</div>
                                    <div class="form-help">
                                        Password must be at least 8 characters long and include letters, numbers, and special characters.
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-check-circle"></i>
                                        <input 
                                            type="password" 
                                            id="confirm_password" 
                                            name="confirm_password" 
                                            class="form-control" 
                                            required
                                        >
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <div class="loader" id="password-loader"></div>
                                <button type="submit" name="update_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div class="tab-content <?= $active_tab == 'notifications' ? 'active' : '' ?>" id="notifications-tab">
                        <form method="POST" action="" id="notifications-form">
                            <div class="form-section">
                                <div class="form-section-title">Email Notifications</div>
                                
                                <div class="form-group">
                                    <div class="detail-item">
                                        <div>
                                            <strong>Email Notifications</strong>
                                            <div class="form-help">Receive email notifications</div>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="email_notifications" <?= $notifications['email_notifications'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Notification Preferences</div>
                                
                                <div class="form-group">
                                    <div class="detail-item">
                                        <div>
                                            <strong>Friend Requests</strong>
                                            <div class="form-help">Get notified when someone sends you a friend request</div>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="friend_requests" <?= $notifications['friend_requests'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div>
                                            <strong>Post Likes</strong>
                                            <div class="form-help">Get notified when someone likes your post</div>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="post_likes" <?= $notifications['post_likes'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div>
                                            <strong>Post Comments</strong>
                                            <div class="form-help">Get notified when someone comments on your post</div>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="post_comments" <?= $notifications['post_comments'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <div>
                                            <strong>Mentions</strong>
                                            <div class="form-help">Get notified when someone mentions you</div>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="mentions" <?= $notifications['mentions'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <div class="loader" id="notifications-loader"></div>
                                <button type="submit" name="update_notifications" class="btn btn-primary">
                                    <i class="fas fa-bell"></i> Save Notification Settings
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Account Tab -->
                    <div class="tab-content <?= $active_tab == 'account' ? 'active' : '' ?>" id="account-tab">
                        <div class="form-section">
                            <div class="form-section-title">Account Information</div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Username</div>
                                <div class="detail-value">@<?= htmlspecialchars($user['username']) ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Member Since</div>
                                <div class="detail-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Account Status</div>
                                <div class="detail-value">
                                    <?php if($user['is_active']): ?>
                                        <span class="detail-badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="detail-badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Last Login</div>
                                <div class="detail-value"><?= date('F j, Y g:i A', strtotime($user['last_login'] ?? $current_date)) ?></div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Account Actions</div>
                            
                            <div class="form-actions">
                                <a href="download_data.php" class="btn btn-secondary">
                                    <i class="fas fa-download"></i> Download My Data
                                </a>
                                
                                <button type="button" id="deactivate-account-btn" class="btn btn-danger">
                                    <i class="fas fa-user-slash"></i> Deactivate Account
                                </button>
                            </div>
                            
                            <div class="form-help" style="margin-top: 15px;">
                                <strong>Note:</strong> Deactivating your account will hide your profile and content from other users. You can reactivate your account at any time by logging in again.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Footer -->
                <div class="settings-footer">
                    <div class="footer-links">
                        <a href="terms.php" class="footer-link">Terms of Service</a>
                        <a href="privacy.php" class="footer-link">Privacy Policy</a>
                        <a href="cookies.php" class="footer-link">Cookie Policy</a>
                        <a href="help.php" class="footer-link">Help Center</a>
                        <a href="contact.php" class="footer-link">Contact Us</a>
                    </div>
                    <div class="copyright">
                        &copy; <?= date('Y') ?> MySocial. All rights reserved.
                    </div>
                </div>
            </div>
            
            <div class="settings-sidebar">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-cover"></div>
                    <div class="profile-avatar-container">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                            <div class="upload-avatar" title="Change profile picture">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <div class="profile-name"><?= htmlspecialchars($user['full_name'] ?? 'Set your name') ?></div>
                        <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
                        
                        <div class="profile-bio">
                            <?= $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : '<span style="color: var(--accent-color);">Add a bio to tell people about yourself</span>' ?>
                        </div>
                        
                        <div class="profile-meta">
                            <?php if($user['location']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($user['location']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($user['website']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-link"></i>
                                    <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                        <?= preg_replace('~^(?:https?://)?(?:www\.)?~i', '', htmlspecialchars($user['website'])) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Joined <?= date('M Y', strtotime($user['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?= $post_count ?></div>
                                <div class="stat-label">Posts</div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-value"><?= $friend_count ?></div>
                                <div class="stat-label">Friends</div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-value"><?= $like_count ?></div>
                                <div class="stat-label">Likes</div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-value"><?= $group_count ?></div>
                                <div class="stat-label">Groups</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Activity Card -->
                <div class="account-card">
                    <div class="card-header">
                        <div class="card-title">Account Activity</div>
                        <div class="card-subtitle">Recent actions on your account</div>
                    </div>
                    
                    <div class="account-details">
                        <div class="detail-item">
                            <div class="detail-label">Last Login</div>
                            <div class="detail-value"><?= date('M j, g:i A', strtotime($user['last_login'] ?? $current_date)) ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">IP Address</div>
                            <div class="detail-value">192.168.1.*** (Hidden)</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Login Method</div>
                            <div class="detail-value">Password</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Device</div>
                            <div class="detail-value">Web Browser</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Last Password Change</div>
                            <div class="detail-value">
                                <?php 
                                    $last_password_change = strtotime($user['password_changed_at'] ?? $user['created_at']);
                                    $days_since = round((time() - $last_password_change) / (60 * 60 * 24));
                                    echo date('M j, Y', $last_password_change);
                                    
                                    if($days_since > 90) {
                                        echo ' <span class="detail-badge badge-warning">Consider updating</span>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Connected Services Card (mockup) -->
                <div class="account-card">
                    <div class="card-header">
                        <div class="card-title">Connected Services</div>
                        <div class="card-subtitle">Link your accounts for easier access</div>
                    </div>
                    
                    <div class="account-details">
                        <div class="detail-item">
                            <div>
                                <i class="fab fa-google" style="color: #4285F4; margin-right: 10px;"></i> 
                                <strong>Google</strong>
                            </div>
                            <button class="btn btn-sm btn-secondary">Connect</button>
                        </div>
                        
                        <div class="detail-item">
                            <div>
                                <i class="fab fa-facebook" style="color: #1877F2; margin-right: 10px;"></i> 
                                <strong>Facebook</strong>
                            </div>
                            <button class="btn btn-sm btn-secondary">Connect</button>
                        </div>
                        
                        <div class="detail-item">
                            <div>
                                <i class="fab fa-twitter" style="color: #1DA1F2; margin-right: 10px;"></i> 
                                <strong>Twitter</strong>
                            </div>
                            <button class="btn btn-sm btn-secondary">Connect</button>
                        </div>
                        
                        <div class="detail-item">
                            <div>
                                <i class="fab fa-apple" style="color: #555; margin-right: 10px;"></i> 
                                <strong>Apple</strong>
                            </div>
                            <button class="btn btn-sm btn-secondary">Connect</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deactivate Account Modal -->
    <div class="modal-backdrop" id="deactivate-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Deactivate Account</div>
                <button type="button" class="modal-close" id="close-deactivate-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate your account? This will:</p>
                <ul style="margin: 15px 0; margin-left: 20px; color: var(--text-secondary);">
                    <li>Hide your profile from other users</li>
                    <li>Remove your posts from the public feed</li>
                    <li>Preserve your data for when you return</li>
                </ul>
                <p>You can reactivate your account at any time by logging in again.</p>
                
                <div style="margin-top: 20px;">
                    <label class="form-check">
                        <input type="checkbox" id="confirm-deactivate" class="form-check-input">
                        <span class="checkmark"></span>
                        <span>I understand that my account will be deactivated</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-deactivate">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-deactivate-btn" disabled>
                    <i class="fas fa-user-slash"></i> Deactivate Account
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const body = document.body;
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            const backdrop = document.querySelector('.backdrop');
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            const alerts = document.querySelectorAll('.alert');
            const alertCloseButtons = document.querySelectorAll('.alert-close');
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthMeter = document.getElementById('strength-meter');
            const strengthText = document.getElementById('strength-text');
            const bioInput = document.getElementById('bio');
            const bioCount = document.getElementById('bio-count');
            const themeToggle = document.getElementById('theme-toggle');
            const deactivateAccountBtn = document.getElementById('deactivate-account-btn');
            const deactivateModal = document.getElementById('deactivate-modal');
            const closeDeactivateModal = document.getElementById('close-deactivate-modal');
            const cancelDeactivate = document.getElementById('cancel-deactivate');
            const confirmDeactivateCheckbox = document.getElementById('confirm-deactivate');
            const confirmDeactivateBtn = document.getElementById('confirm-deactivate-btn');
            
            // Initialize
            
            // Theme mode handling
            function initTheme() {
                // Check for saved theme preference
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme === 'dark') {
                    body.classList.add('dark-mode');
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                } else if (savedTheme === 'light') {
                    body.classList.remove('dark-mode');
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                } else {
                    // Check system preference if no saved preference
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        body.classList.add('dark-mode');
                        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    }
                }
            }
            
            // Initialize theme
            initTheme();
            
            // Bio character counter
            if (bioInput && bioCount) {
                bioCount.textContent = bioInput.value.length;
                
                bioInput.addEventListener('input', function() {
                    bioCount.textContent = this.value.length;
                });
            }
            
            // Toggle sidebar on mobile
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    backdrop.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking backdrop
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    backdrop.classList.remove('active');
                });
            }
            
            // Tab switching
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Get tab ID
                    const tabId = this.getAttribute('data-tab');
                    
                    // Update URL with tab parameter without page reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({}, '', url);
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Alert close buttons
            alertCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const alert = this.closest('.alert');
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                });
            });
            
            // Auto dismiss alerts after 5 seconds
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        setTimeout(() => {
                            alert.style.display = 'none';
                        }, 300);
                    });
                }, 5000);
            }
            
            // Password validation
            if (newPasswordInput && confirmPasswordInput) {
                // Check password match
                function validatePasswordMatch() {
                    if (newPasswordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                }
                
                newPasswordInput.addEventListener('change', validatePasswordMatch);
                confirmPasswordInput.addEventListener('keyup', validatePasswordMatch);
                
                // Password strength meter
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    // Length check
                    if (password.length >= 8) {
                        strength += 1;
                    }
                    
                    // Complexity checks
                    if (/[A-Z]/.test(password)) {
                        strength += 1;
                    }
                    
                    if (/[0-9]/.test(password)) {
                        strength += 1;
                    }
                    
                    if (/[^A-Za-z0-9]/.test(password)) {
                        strength += 1;
                    }
                    
                    // Update strength meter
                    strengthMeter.className = 'strength-meter';
                    
                    if (password.length === 0) {
                        strengthMeter.style.width = '0';
                        strengthText.textContent = 'Password strength';
                    } else if (strength < 2) {
                        strengthMeter.classList.add('strength-weak');
                        strengthText.textContent = 'Weak password';
                        strengthText.style.color = 'var(--danger-color)';
                    } else if (strength < 4) {
                        strengthMeter.classList.add('strength-medium');
                        strengthText.textContent = 'Medium password';
                        strengthText.style.color = 'var(--warning-color)';
                    } else {
                        strengthMeter.classList.add('strength-strong');
                        strengthText.textContent = 'Strong password';
                        strengthText.style.color = 'var(--success-color)';
                    }
                });
            }
            
            // Theme toggle
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
            
            // Form submissions with loading state
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const formId = this.id;
                    const loader = document.getElementById(`${formId.split('-')[0]}-loader`);
                    
                    if (loader) {
                        loader.style.display = 'inline-block';
                    }
                });
            });
            
            // Deactivate account modal
            if (deactivateAccountBtn) {
                deactivateAccountBtn.addEventListener('click', function() {
                    deactivateModal.style.display = 'flex';
                    setTimeout(() => {
                        deactivateModal.style.opacity = '1';
                    }, 10);
                });
            }
            
            if (closeDeactivateModal) {
                closeDeactivateModal.addEventListener('click', closeDeactivateModalFn);
            }
            
            if (cancelDeactivate) {
                cancelDeactivate.addEventListener('click', closeDeactivateModalFn);
            }
            
            function closeDeactivateModalFn() {
                deactivateModal.style.opacity = '0';
                setTimeout(() => {
                    deactivateModal.style.display = 'none';
                    if (confirmDeactivateCheckbox) {
                        confirmDeactivateCheckbox.checked = false;
                    }
                    if (confirmDeactivateBtn) {
                        confirmDeactivateBtn.disabled = true;
                    }
                }, 300);
            }
            
            // Handle deactivate confirmation checkbox
            if (confirmDeactivateCheckbox && confirmDeactivateBtn) {
                confirmDeactivateCheckbox.addEventListener('change', function() {
                    confirmDeactivateBtn.disabled = !this.checked;
                });
            }
            
            // Handle deactivate account action
            if (confirmDeactivateBtn) {
                confirmDeactivateBtn.addEventListener('click', function() {
                    // Show loading state
                    this.innerHTML = '<div class="loader" style="display:inline-block;margin-right:8px;"></div> Deactivating...';
                    this.disabled = true;
                    
                    // Simulate API call (replace with actual implementation)
                    setTimeout(() => {
                        // Redirect to deactivation confirmation page
                        window.location.href = 'account_deactivated.php';
                    }, 2000);
                });
            }
            
            // File upload preview (for avatar)
            const uploadAvatar = document.querySelector('.upload-avatar');
            
            if (uploadAvatar) {
                uploadAvatar.addEventListener('click', function() {
                    // Create a file input and trigger it
                    const fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = 'image/*';
                    fileInput.style.display = 'none';
                    
                    fileInput.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            // Here you'd usually upload the file to the server
                            // For now, just show an alert
                            alert('Profile picture uploaded! (This is a mockup)');
                        }
                    });
                    
                    // Append to body and trigger click
                    document.body.appendChild(fileInput);
                    fileInput.click();
                    
                    // Clean up
                    setTimeout(() => {
                        document.body.removeChild(fileInput);
                    }, 1000);
                });
            }
            
            // Handle clicks outside modals to close them
            window.addEventListener('click', function(event) {
                if (event.target === deactivateModal) {
                    closeDeactivateModalFn();
                }
            });
            
            // Current date display (just for demonstration)
            const currentDateElements = document.querySelectorAll('.current-date');
            if (currentDateElements.length > 0) {
                const formattedDate = new Date('<?= $current_date ?>').toLocaleString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                currentDateElements.forEach(el => {
                    el.textContent = formattedDate;
                });
            }
        });
    </script>
</body>
</html>
                                    