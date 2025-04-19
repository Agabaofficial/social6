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
$current_date = date('Y-m-d H:i:s'); // Current UTC time

// Handle group related actions
if(isset($_POST['join_group'])) {
    $group_id = intval($_POST['group_id']);
    
    // Check if already a member
    $check_member = $conn->query("SELECT * FROM groupmembership WHERE group_id = $group_id AND user_id = $current_user_id");
    
    if($check_member->num_rows == 0) {
        // Join the group
        $conn->query("INSERT INTO groupmembership (group_id, user_id, role) VALUES ($group_id, $current_user_id, 'member')");
        
        // Set success message
        $_SESSION['success_message'] = "You have successfully joined the group!";
    }
    
    header("Location: groups.php");
    exit();
} elseif(isset($_POST['leave_group'])) {
    $group_id = intval($_POST['group_id']);
    
    // Leave the group
    $conn->query("DELETE FROM groupmembership WHERE group_id = $group_id AND user_id = $current_user_id");
    
    // Set success message
    $_SESSION['success_message'] = "You have left the group.";
    
    header("Location: groups.php");
    exit();
} elseif(isset($_POST['create_group'])) {
    $name = $conn->real_escape_string($_POST['group_name']);
    $description = $conn->real_escape_string($_POST['group_description']);
    $visibility = $conn->real_escape_string($_POST['group_visibility']);
    
    // Create new group
    $conn->query("INSERT INTO `group` (name, description, creator_id, visibility) VALUES ('$name', '$description', $current_user_id, '$visibility')");
    $new_group_id = $conn->insert_id;
    
    // Add creator as admin
    if($new_group_id) {
        $conn->query("INSERT INTO groupmembership (group_id, user_id, role) VALUES ($new_group_id, $current_user_id, 'admin')");
        
        // Set success message
        $_SESSION['success_message'] = "Group '$name' has been created successfully!";
    }
    
    header("Location: groups.php");
    exit();
}

// Fetch groups the user is a member of
$my_groups_query = "
    SELECT g.*, gm.role,
           (SELECT COUNT(*) FROM groupmembership WHERE group_id = g.group_id) as member_count,
           u.username as creator_username, u.full_name as creator_name
    FROM `group` g
    JOIN groupmembership gm ON g.group_id = gm.group_id
    JOIN users u ON g.creator_id = u.user_id
    WHERE gm.user_id = $current_user_id
    ORDER BY g.created_at DESC
";

$my_groups_result = $conn->query($my_groups_query);

// Fetch public groups the user is not a member of
$public_groups_query = "
    SELECT g.*,
           (SELECT COUNT(*) FROM groupmembership WHERE group_id = g.group_id) as member_count,
           u.username as creator_username, u.full_name as creator_name
    FROM `group` g
    JOIN users u ON g.creator_id = u.user_id
    WHERE g.visibility = 'public'
    AND NOT EXISTS (
        SELECT 1 FROM groupmembership gm 
        WHERE gm.group_id = g.group_id AND gm.user_id = $current_user_id
    )
    ORDER BY g.created_at DESC
";

$public_groups_result = $conn->query($public_groups_query);

// Count total groups the user is a member of
$total_groups = $my_groups_result ? $my_groups_result->num_rows : 0;

// Count groups where user is admin
$admin_groups_count = 0;
if($my_groups_result && $my_groups_result->num_rows > 0) {
    $my_groups_result->data_seek(0);
    while($group = $my_groups_result->fetch_assoc()) {
        if($group['role'] == 'admin') {
            $admin_groups_count++;
        }
    }
    $my_groups_result->data_seek(0);
}

// Current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groups | MySocial</title>
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
            --border-radius: 15px;
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
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .alert-success {
            background-color: rgba(23, 191, 99, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-dismiss {
            cursor: pointer;
            font-size: 16px;
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
            gap: 8px;
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
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c21e53;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-block {
            width: 100%;
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
        
        /* Groups Layout */
        .groups-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        
        .groups-main {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .groups-sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        /* Card Component */
        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: bold;
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
            width: 24px;
            height: 24px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Group Grid */
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        /* Group Card */
        .group-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .group-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .group-cover {
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), #4dabf7);
            position: relative;
        }
        
        .group-visibility-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .group-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .group-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .group-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .group-description {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.5;
            flex-grow: 1;
        }
        
        .group-stats {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .group-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .group-actions {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .group-actions form {
            display: inline;
        }
        
        .group-role {
            display: inline-flex;
            align-items: center;
            background: rgba(29, 161, 242, 0.1);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            gap: 5px;
        }
        
        .group-role.admin {
            background: rgba(23, 191, 99, 0.1);
            color: var(--success-color);
        }
        
        /* Create Group Form */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--light-bg);
            color: var(--text-color);
            font-family: inherit;
            font-size: 15px;
            transition: var(--transition);
        }
        
        .dark-mode .form-control {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(29, 161, 242, 0.2);
        }
        
        .form-control::placeholder {
            color: var(--text-secondary);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            cursor: pointer;
        }
        
        .form-check input[type="radio"] {
            transform: scale(1.2);
            accent-color: var(--primary-color);
        }
        
        .form-actions {
            margin-top: 20px;
        }
        
        /* Tips Component */
        .tips-list {
            list-style: none;
        }
        
        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .tip-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .tip-icon {
            color: var(--primary-color);
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .tip-content {
            flex-grow: 1;
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state-icon {
            font-size: 60px;
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .empty-state-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .empty-state-description {
            color: var(--text-secondary);
            margin-bottom: 20px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Modal */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .modal-backdrop.show {
            opacity: 1;
            pointer-events: auto;
        }
        
        .modal {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal-backdrop.show .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .groups-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .groups-container {
                grid-template-columns: 1fr;
            }
            
            .groups-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
                display: inline;
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
            
            .main-content, 
            .top-nav {
                margin-left: 0;
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
                width: 200px;
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
                justify-content: center;
            }
            
            .stats-card {
                flex-wrap: wrap;
            }
            
            .stat-item {
                flex-basis: 50%;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                margin-bottom: 10px;
            }
            
            .stat-item:nth-child(3),
            .stat-item:nth-child(4) {
                border-bottom: none;
                margin-bottom: 0;
            }
        }
        
        @media (max-width: 576px) {
            .search-bar {
                display: none;
            }
            
            .groups-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                display: none;
            }
            
            .stat-item {
                flex-basis: 100%;
                padding: 15px 0;
            }
            
            .stat-item:nth-child(3) {
                border-bottom: 1px solid var(--border-color);
                margin-bottom: 10px;
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
                    <?php if($total_groups > 0): ?>
                        <span class="badge"><?= $total_groups ?></span>
                    <?php endif; ?>
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
        <div class="left-section">
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="page-title">Groups</div>
        </div>
        
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="search-groups" placeholder="Search groups...">
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
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <div><?= $_SESSION['success_message'] ?></div>
                <div class="alert-dismiss">&times;</div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <div class="page-header">
            <div class="header-title">
                <h1>Groups</h1>
                <div class="header-subtitle">
                    <span>Connect with people who share your interests</span>
                </div>
            </div>
            <div class="header-actions">
                <button id="create-group-btn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Group
                </button>
                <button id="discover-groups-btn" class="btn btn-outline">
                    <i class="fas fa-compass"></i> Discover Groups
                </button>
            </div>
        </div>
        
        <?php if($total_groups > 0): ?>
            <!-- Stats Card -->
            <div class="stats-card">
                <div class="stat-item">
                    <div class="stat-value"><?= $total_groups ?></div>
                    <div class="stat-label">Groups</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?= $admin_groups_count ?></div>
                    <div class="stat-label">Admin Role</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value"><?= $total_groups - $admin_groups_count ?></div>
                    <div class="stat-label">Member Role</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-value">
                        <?php 
                        // Get today's date
                        $today = new DateTime();
                        $today->setTime(0, 0, 0);
                        
                        // Count today's groups
                        $today_groups = 0;
                        
                        if($my_groups_result && $my_groups_result->num_rows > 0) {
                            $my_groups_result->data_seek(0);
                            
                            while($group = $my_groups_result->fetch_assoc()) {
                                $group_date = new DateTime($group['created_at']);
                                if($group_date >= $today) {
                                    $today_groups++;
                                }
                            }
                            
                            // Reset result pointer
                            $my_groups_result->data_seek(0);
                        }
                        
                        echo $today_groups;
                        ?>
                    </div>
                    <div class="stat-label">New Today</div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="groups-container">
            <div class="groups-main">
                <!-- My Groups -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-users"></i> My Groups
                            <?php if($total_groups > 0): ?>
                                <span class="card-badge"><?= $total_groups ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if($total_groups > 0): ?>
                            <div class="card-actions">
                                <div class="btn-group" id="groups-view-toggle">
                                    <button class="active" data-view="grid"><i class="fas fa-th-large"></i></button>
                                    <button data-view="list"><i class="fas fa-list"></i></button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if($my_groups_result && $my_groups_result->num_rows > 0): ?>
                            <div class="groups-grid">
                                <?php while($group = $my_groups_result->fetch_assoc()): ?>
                                    <?php 
                                    // Format the group creation date
                                    $created_date = new DateTime($group['created_at']);
                                    $now = new DateTime($current_date);
                                    $interval = $created_date->diff($now);
                                    
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
                                    
                                    // Get first letter of creator's name for potential avatar display
                                    $creator_avatar = substr($group['creator_name'] ?? $group['creator_username'], 0, 1);
                                    ?>
                                    <div class="group-card" data-name="<?= htmlspecialchars($group['name']) ?>" data-visibility="<?= $group['visibility'] ?>">
                                        <div class="group-cover">
                                            <div class="group-visibility-badge">
                                                <?php if($group['visibility'] == 'public'): ?>
                                                    <i class="fas fa-globe"></i> Public
                                                <?php else: ?>
                                                    <i class="fas fa-lock"></i> Private
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="group-content">
                                            <h3 class="group-name"><?= htmlspecialchars($group['name']) ?></h3>
                                            <div class="group-meta">
                                                <span><i class="far fa-clock"></i> <?= $time_ago ?></span>
                                                <span class="group-role <?= $group['role'] == 'admin' ? 'admin' : '' ?>">
                                                    <?php if($group['role'] == 'admin'): ?>
                                                        <i class="fas fa-crown"></i> Admin
                                                    <?php else: ?>
                                                        <i class="fas fa-user"></i> Member
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="group-description">
                                                <?= nl2br(htmlspecialchars($group['description'] ?? 'No description available.')) ?>
                                            </div>
                                        </div>
                                        <div class="group-stats">
                                            <div class="group-stat">
                                                <i class="fas fa-users"></i> <?= $group['member_count'] ?> member<?= $group['member_count'] != 1 ? 's' : '' ?>
                                            </div>
                                            <div class="group-stat">
                                                <i class="fas fa-user"></i> Created by @<?= htmlspecialchars($group['creator_username']) ?>
                                            </div>
                                        </div>
                                        <div class="group-actions">
                                            <a href="group.php?id=<?= $group['group_id'] ?>" class="btn btn-outline btn-sm">
                                                <i class="fas fa-eye"></i> View Group
                                            </a>
                                            <?php if($group['role'] == 'admin'): ?>
                                                <a href="group_manage.php?id=<?= $group['group_id'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-cog"></i> Manage
                                                </a>
                                            <?php else: ?>
                                                <form method="POST" action="" class="leave-group-form">
                                                    <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                                    <button type="submit" name="leave_group" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-sign-out-alt"></i> Leave
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h2 class="empty-state-title">No Groups Yet</h2>
                                <p class="empty-state-description">
                                    You haven't joined any groups yet. Create a new group or join existing ones to connect with people who share your interests.
                                </p>
                                <button id="empty-create-btn" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Your First Group
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Discover Public Groups -->
                <div class="card" id="discover-section">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-compass"></i> Discover Public Groups
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($public_groups_result && $public_groups_result->num_rows > 0): ?>
                            <div class="groups-grid">
                                <?php while($group = $public_groups_result->fetch_assoc()): ?>
                                    <?php 
                                    // Format the group creation date
                                    $created_date = new DateTime($group['created_at']);
                                    $now = new DateTime($current_date);
                                    $interval = $created_date->diff($now);
                                    
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
                                    ?>
                                    <div class="group-card" data-name="<?= htmlspecialchars($group['name']) ?>">
                                        <div class="group-cover">
                                            <div class="group-visibility-badge">
                                                <i class="fas fa-globe"></i> Public
                                            </div>
                                        </div>
                                        <div class="group-content">
                                            <h3 class="group-name"><?= htmlspecialchars($group['name']) ?></h3>
                                            <div class="group-meta">
                                                <span><i class="far fa-clock"></i> <?= $time_ago ?></span>
                                            </div>
                                            <div class="group-description">
                                                <?= nl2br(htmlspecialchars($group['description'] ?? 'No description available.')) ?>
                                            </div>
                                        </div>
                                        <div class="group-stats">
                                            <div class="group-stat">
                                                <i class="fas fa-users"></i> <?= $group['member_count'] ?> member<?= $group['member_count'] != 1 ? 's' : '' ?>
                                            </div>
                                            <div class="group-stat">
                                                <i class="fas fa-user"></i> Created by @<?= htmlspecialchars($group['creator_username']) ?>
                                            </div>
                                        </div>
                                        <div class="group-actions">
                                            <form method="POST" action="" class="join-group-form">
                                                <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                                <button type="submit" name="join_group" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-sign-in-alt"></i> Join Group
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-compass"></i>
                                </div>
                                <h2 class="empty-state-title">No Public Groups Available</h2>
                                <p class="empty-state-description">
                                    There are no public groups available to join at the moment. Why not create your own group?
                                </p>
                                <button id="empty-create-btn-2" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create a Group
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="groups-sidebar">
                <!-- Create Group Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-plus-circle"></i> Create New Group
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="create-group-form">
                            <div class="form-group">
                                <label for="group_name">Group Name</label>
                                <input type="text" id="group_name" name="group_name" class="form-control" placeholder="Enter group name" required maxlength="50">
                            </div>
                            <div class="form-group">
                                <label for="group_description">Description</label>
                                <textarea id="group_description" name="group_description" class="form-control" placeholder="What's this group about?" maxlength="500"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Privacy</label>
                                <div class="form-check">
                                    <input type="radio" id="public" name="group_visibility" value="public" checked>
                                    <label for="public">Public - Anyone can see and join</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="private" name="group_visibility" value="private">
                                    <label for="private">Private - Only visible to members</label>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="create_group" class="btn btn-primary btn-block">
                                    <i class="fas fa-users"></i> Create Group
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Group Tips Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-lightbulb"></i> Group Tips
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="tips-list">
                            <li class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="tip-content">
                                    Create groups around specific interests, hobbies, or topics you're passionate about.
                                </div>
                            </li>
                            <li class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="tip-content">
                                    Public groups are visible to everyone, while private groups are only visible to members.
                                </div>
                            </li>
                            <li class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="tip-content">
                                    As a group admin, you can manage members, posts, and settings.
                                </div>
                            </li>
                            <li class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="tip-content">
                                    Stay engaged by posting regularly and encouraging discussions in your groups.
                                </div>
                            </li>
                            <li class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="tip-content">
                                    Invite friends to join your groups to help build an active community.
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Group Modal (for mobile) -->
    <div class="modal-backdrop" id="create-group-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Create New Group</div>
                <button class="modal-close" id="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="create-group-form-mobile">
                    <div class="form-group">
                        <label for="group_name_mobile">Group Name</label>
                        <input type="text" id="group_name_mobile" name="group_name" class="form-control" placeholder="Enter group name" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="group_description_mobile">Description</label>
                        <textarea id="group_description_mobile" name="group_description" class="form-control" placeholder="What's this group about?" maxlength="500"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Privacy</label>
                        <div class="form-check">
                            <input type="radio" id="public_mobile" name="group_visibility" value="public" checked>
                            <label for="public_mobile">Public - Anyone can see and join</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="private_mobile" name="group_visibility" value="private">
                            <label for="private_mobile">Private - Only visible to members</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="modal-cancel">Cancel</button>
                <button type="button" id="modal-create" class="btn btn-primary">Create Group</button>
            </div>
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
            
            // Alert dismiss
            const alertDismiss = document.querySelector('.alert-dismiss');
            if (alertDismiss) {
                alertDismiss.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
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
            const searchInput = document.getElementById('search-groups');
            const groupCards = document.querySelectorAll('.group-card');
            
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    groupCards.forEach(card => {
                        const groupName = card.getAttribute('data-name').toLowerCase();
                        const groupVisibility = card.getAttribute('data-visibility');
                        const groupDescription = card.querySelector('.group-description')?.textContent.toLowerCase();
                        
                        // Show/hide based on search term match in name or description
                        if (groupName.includes(searchTerm) || (groupDescription && groupDescription.includes(searchTerm))) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
            
            // Scroll to discover section when clicking the discover button
            const discoverBtn = document.getElementById('discover-groups-btn');
            const discoverSection = document.getElementById('discover-section');
            
            if (discoverBtn && discoverSection) {
                discoverBtn.addEventListener('click', function() {
                    discoverSection.scrollIntoView({ behavior: 'smooth' });
                });
            }
            
            // Leave group confirmation
            const leaveGroupForms = document.querySelectorAll('.leave-group-form');
            
            leaveGroupForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to leave this group? You will need to join again to access it.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Modal handling for mobile
            const createGroupModal = document.getElementById('create-group-modal');
            const createGroupBtn = document.getElementById('create-group-btn');
            const modalClose = document.getElementById('modal-close');
            const modalCancel = document.getElementById('modal-cancel');
            const modalCreate = document.getElementById('modal-create');
            const emptyCreateBtn = document.getElementById('empty-create-btn');
            const emptyCreateBtn2 = document.getElementById('empty-create-btn-2');
            const createGroupFormMobile = document.getElementById('create-group-form-mobile');
            
            // Function to toggle modal
            function toggleModal() {
                createGroupModal.classList.toggle('show');
                document.body.style.overflow = createGroupModal.classList.contains('show') ? 'hidden' : '';
            }
            
            // Open modal buttons
            if (createGroupBtn) {
                createGroupBtn.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleModal();
                    } else {
                        // Scroll to the create form on desktop
                        document.getElementById('create-group-form').scrollIntoView({ behavior: 'smooth' });
                        document.getElementById('group_name').focus();
                    }
                });
            }
            
            // Empty state create buttons
            if (emptyCreateBtn) {
                emptyCreateBtn.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleModal();
                    } else {
                        document.getElementById('create-group-form').scrollIntoView({ behavior: 'smooth' });
                        document.getElementById('group_name').focus();
                    }
                });
            }
            
            if (emptyCreateBtn2) {
                emptyCreateBtn2.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleModal();
                    } else {
                        document.getElementById('create-group-form').scrollIntoView({ behavior: 'smooth' });
                        document.getElementById('group_name').focus();
                    }
                });
            }
            
            // Close modal buttons
            if (modalClose) modalClose.addEventListener('click', toggleModal);
            if (modalCancel) modalCancel.addEventListener('click', toggleModal);
            
                        // Submit modal form
            if (modalCreate) {
                modalCreate.addEventListener('click', function() {
                    createGroupFormMobile.submit();
                });
            }
            
            // Close modal when clicking outside
            createGroupModal.addEventListener('click', function(e) {
                if (e.target === createGroupModal) {
                    toggleModal();
                }
            });
            
            // Form validation for both desktop and mobile forms
            const desktopForm = document.getElementById('create-group-form');
            
            function validateGroupForm(form) {
                const nameInput = form.querySelector('[name="group_name"]');
                const descInput = form.querySelector('[name="group_description"]');
                
                if (!nameInput.value.trim()) {
                    alert('Please enter a group name');
                    nameInput.focus();
                    return false;
                }
                
                if (nameInput.value.length < 3) {
                    alert('Group name must be at least 3 characters long');
                    nameInput.focus();
                    return false;
                }
                
                return true;
            }
            
            if (desktopForm) {
                desktopForm.addEventListener('submit', function(e) {
                    if (!validateGroupForm(this)) {
                        e.preventDefault();
                    }
                });
            }
            
            if (createGroupFormMobile) {
                createGroupFormMobile.addEventListener('submit', function(e) {
                    if (!validateGroupForm(this)) {
                        e.preventDefault();
                    }
                });
            }
            
            // Sync form inputs between desktop and mobile
            const desktopName = document.getElementById('group_name');
            const desktopDesc = document.getElementById('group_description');
            const mobileName = document.getElementById('group_name_mobile');
            const mobileDesc = document.getElementById('group_description_mobile');
            
            if (desktopName && mobileName) {
                desktopName.addEventListener('input', function() {
                    mobileName.value = this.value;
                });
                
                mobileName.addEventListener('input', function() {
                    desktopName.value = this.value;
                });
            }
            
            if (desktopDesc && mobileDesc) {
                desktopDesc.addEventListener('input', function() {
                    mobileDesc.value = this.value;
                });
                
                mobileDesc.addEventListener('input', function() {
                    desktopDesc.value = this.value;
                });
            }
            
            // Toggle view between grid and list (for future implementation)
            const viewToggleButtons = document.querySelectorAll('#groups-view-toggle button');
            const groupsGrid = document.querySelector('.groups-grid');
            
            if (viewToggleButtons.length && groupsGrid) {
                viewToggleButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        viewToggleButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        
                        const view = this.getAttribute('data-view');
                        if (view === 'grid') {
                            groupsGrid.classList.remove('list-view');
                        } else {
                            groupsGrid.classList.add('list-view');
                        }
                    });
                });
            }
            
            // Character counter for text inputs
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    const maxLength = this.getAttribute('maxlength');
                    const current = this.value.length;
                    
                    // Find or create counter element
                    let counter = this.nextElementSibling;
                    if (!counter || !counter.classList.contains('char-counter')) {
                        counter = document.createElement('div');
                        counter.classList.add('char-counter');
                        counter.style.fontSize = '12px';
                        counter.style.color = 'var(--text-secondary)';
                        counter.style.textAlign = 'right';
                        counter.style.marginTop = '5px';
                        this.parentNode.insertBefore(counter, this.nextSibling);
                    }
                    
                    counter.textContent = `${current}/${maxLength}`;
                    
                    // Change color when approaching limit
                    if (current > maxLength * 0.9) {
                        counter.style.color = 'var(--danger-color)';
                    } else {
                        counter.style.color = 'var(--text-secondary)';
                    }
                });
                
                // Trigger once to initialize counters
                const event = new Event('input');
                textarea.dispatchEvent(event);
            });
        });
    </script>
</body>
</html>