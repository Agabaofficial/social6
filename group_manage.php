<?php
include 'db_connect.php';
session_start();

// Current user information
$current_user_id = 1; // Assuming Agabaofficial is user_id 1
$current_username = "Agabaofficial";
$current_date = "2025-04-17 08:15:50"; // Current UTC time

// Check if group ID is provided
if(!isset($_GET['id'])) {
    header('Location: groups.php');
    exit();
}

$group_id = intval($_GET['id']);

// Check if current user is the admin of this group
$check_admin_query = "
    SELECT g.*, gm.role
    FROM `group` g
    JOIN groupmembership gm ON g.group_id = gm.group_id
    WHERE g.group_id = $group_id 
    AND gm.user_id = $current_user_id 
    AND gm.role = 'admin'
";

$check_admin_result = $conn->query($check_admin_query);

if($check_admin_result->num_rows == 0) {
    // User is not admin or group doesn't exist
    header('Location: groups.php');
    exit();
}

$group = $check_admin_result->fetch_assoc();

// Handle group actions
if(isset($_POST['update_group'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $visibility = $conn->real_escape_string($_POST['visibility']);
    
    $update_query = "UPDATE `group` SET name = '$name', description = '$description', visibility = '$visibility' WHERE group_id = $group_id";
    $conn->query($update_query);
    
    // Refresh page to show updated info
    header("Location: group_manage.php?id=$group_id&updated=1");
    exit();
} elseif(isset($_POST['remove_member'])) {
    $member_id = intval($_POST['member_id']);
    
    $remove_query = "DELETE FROM groupmembership WHERE group_id = $group_id AND user_id = $member_id";
    $conn->query($remove_query);
    
    header("Location: group_manage.php?id=$group_id&removed=1");
    exit();
} elseif(isset($_POST['make_admin'])) {
    $member_id = intval($_POST['member_id']);
    
    $promote_query = "UPDATE groupmembership SET role = 'admin' WHERE group_id = $group_id AND user_id = $member_id";
    $conn->query($promote_query);
    
    header("Location: group_manage.php?id=$group_id&promoted=1");
    exit();
} elseif(isset($_POST['delete_group'])) {
    // Delete the group (foreign key constraints will handle related data)
    $delete_query = "DELETE FROM `group` WHERE group_id = $group_id";
    $conn->query($delete_query);
    
    header("Location: groups.php?deleted=1");
    exit();
}

// Fetch group members
$members_query = "
    SELECT gm.*, u.user_id, u.username, u.full_name, u.profile_picture
    FROM groupmembership gm
    JOIN users u ON gm.user_id = u.user_id
    WHERE gm.group_id = $group_id
    ORDER BY gm.role DESC, u.full_name ASC
";

$members_result = $conn->query($members_query);

// Count members
$member_count = $conn->query("SELECT COUNT(*) as count FROM groupmembership WHERE group_id = $group_id")->fetch_assoc()['count'];

// Current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Group | MySocial</title>
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
        
        /* Group Manage Page Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .page-actions {
            display: flex;
            gap: 10px;
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
            text-decoration: none;
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
        
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: white;
        }
        
        .alert-success {
            background: var(--success-color);
        }
        
        .alert-danger {
            background: var(--danger-color);
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card-header {
            padding: 15px 20px;
            background: var(--light-bg);
            border-bottom: 1px solid var(--light-gray);
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .manage-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .manage-container {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .member-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .member-item:last-child {
            border-bottom: none;
        }
        
        .member-avatar {
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
            margin-right: 15px;
        }
        
        .member-info {
            flex-grow: 1;
        }
        
        .member-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .member-role {
            font-size: 13px;
            color: var(--dark-gray);
        }
        
        .admin-badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .member-actions {
            display: flex;
            gap: 10px;
        }
        
        .danger-zone {
            background: #fee2e2;
            border: 1px solid var(--danger-color);
            border-radius: 10px;
            padding: 20px;
        }
        
        .danger-zone-title {
            color: var(--danger-color);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .danger-zone-text {
            margin-bottom: 15px;
            color: #7f1d1d;
        }
        
        /* Modal for delete confirmation */
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
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: var(--card-bg);
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
            transform: translateY(-20px);
            transition: var(--transition);
        }
        
        .modal-backdrop.show .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            font-size: 18px;
            font-weight: 600;
            color: var(--danger-color);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .page-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 576px) {
            .search-bar {
                display: none;
            }
            
            .manage-container {
                grid-template-columns: 1fr;
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
            <input type="text" placeholder="Search...">
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
        <div class="page-header">
            <div class="page-title">Manage Group: <?= htmlspecialchars($group['name']) ?></div>
            <div class="page-actions">
                <a href="groups.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Groups
                </a>
            </div>
        </div>
        
        <!-- Status alerts -->
        <?php if(isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Group information has been updated successfully.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['removed'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Member has been removed from the group.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['promoted'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Member has been promoted to admin.
            </div>
        <?php endif; ?>
        
        <div class="manage-container">
            <div class="manage-main">
                <!-- Group Information -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Group Information
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="name">Group Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($group['name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($group['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Privacy</label>
                                <div class="form-check">
                                    <input type="radio" id="public" name="visibility" value="public" <?= $group['visibility'] == 'public' ? 'checked' : '' ?>>
                                    <label for="public">Public - Anyone can see and join</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="private" name="visibility" value="private" <?= $group['visibility'] == 'private' ? 'checked' : '' ?>>
                                    <label for="private">Private - Only visible to members</label>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_group" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Group Members -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users"></i> Members (<?= $member_count ?>)
                    </div>
                    <div class="card-body">
                        <div class="member-list">
                            <?php if($members_result && $members_result->num_rows > 0): ?>
                                <?php while($member = $members_result->fetch_assoc()): ?>
                                    <?php 
                                    // Get first letter of member's name for avatar
                                    $member_avatar = substr($member['full_name'] ?? $member['username'], 0, 1);
                                    ?>
                                    <div class="member-item">
                                        <div class="member-avatar">
                                            <?= $member_avatar ?>
                                        </div>
                                        <div class="member-info">
                                            <div class="member-name">
                                                <?= htmlspecialchars($member['full_name'] ?? $member['username']) ?>
                                                <?php if($member['role'] == 'admin'): ?>
                                                    <span class="admin-badge">Admin</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="member-role">@<?= htmlspecialchars($member['username']) ?></div>
                                        </div>
                                        
                                        <?php if($member['user_id'] != $current_user_id): ?>
                                            <div class="member-actions">
                                                <?php if($member['role'] != 'admin'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="member_id" value="<?= $member['user_id'] ?>">
                                                        <button type="submit" name="make_admin" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-user-shield"></i> Make Admin
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" action="">
                                                    <input type="hidden" name="member_id" value="<?= $member['user_id'] ?>">
                                                    <button type="submit" name="remove_member" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-user-minus"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px; color: var(--dark-gray);">
                                    No members found in this group.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="manage-sidebar">
                <!-- Group Stats -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i> Group Stats
                    </div>
                    <div class="card-body">
                        <ul style="list-style-type: none; padding: 0;">
                            <li style="margin-bottom: 10px;">
                                <strong><i class="fas fa-users"></i> Members:</strong> <?= $member_count ?>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <strong><i class="fas fa-calendar-alt"></i> Created:</strong> 
                                <?= date('F j, Y', strtotime($group['created_at'])) ?>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <strong><i class="fas fa-eye"></i> Visibility:</strong> 
                                <?= ucfirst($group['visibility']) ?>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Danger Zone -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle"></i> Danger Zone
                    </div>
                    <div class="card-body">
                        <div class="danger-zone">
                            <div class="danger-zone-title">Delete Group</div>
                            <div class="danger-zone-text">
                                Once you delete a group, there is no going back. Please be certain.
                            </div>
                            <button type="button" id="deleteGroupBtn" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Delete Group
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-backdrop" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i> Delete Group
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the group "<?= htmlspecialchars($group['name']) ?>"?</p>
                <p>This action cannot be undone. All members will be removed and group data will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelDelete" class="btn btn-secondary">Cancel</button>
                <form method="POST" action="">
                    <button type="submit" name="delete_group" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Delete Group
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for UI interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle on mobile
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Delete confirmation modal
            const deleteGroupBtn = document.getElementById('deleteGroupBtn');
            const deleteModal = document.getElementById('deleteModal');
            const cancelDelete = document.getElementById('cancelDelete');
            
            if (deleteGroupBtn) {
                deleteGroupBtn.addEventListener('click', function() {
                    deleteModal.classList.add('show');
                });
            }
            
            if (cancelDelete) {
                cancelDelete.addEventListener('click', function() {
                    deleteModal.classList.remove('show');
                });
            }
            
            deleteModal.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.classList.remove('show');
                }
            });
            
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            if(alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(alert => {
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 500);
                    });
                }, 5000);
            }
        });
    </script>
</body>
</html>