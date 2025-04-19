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
$current_date = date('Y-m-d H:i:s'); // Current UTC time: 2025-04-18 16:58:13

// Handle AJAX send message request
if(isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $receiver_id = intval($_POST['receiver_id']);
    $content = $conn->real_escape_string($_POST['message_content']);
    
    $insert_query = "INSERT INTO message (sender_id, receiver_id, content) VALUES ($current_user_id, $receiver_id, '$content')";
    if($conn->query($insert_query)) {
        // Get the newly created message with time
        $message_id = $conn->insert_id;
        $message_query = "
            SELECT m.*, 
                   DATE_FORMAT(m.sent_at, '%h:%i %p') as formatted_time
            FROM message m
            WHERE m.message_id = $message_id
        ";
        $message_result = $conn->query($message_query);
        $message = $message_result->fetch_assoc();
        
        echo json_encode([
            "status" => "success",
            "message" => $message
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to send message: " . $conn->error
        ]);
    }
    exit();
}

// AJAX fetch new messages
if(isset($_GET['action']) && $_GET['action'] === 'get_new_messages') {
    $other_user_id = intval($_GET['user_id']);
    $last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;
    
    $messages_query = "
        SELECT m.*, 
               CASE WHEN m.sender_id = $current_user_id THEN 'sent' ELSE 'received' END as message_type,
               DATE_FORMAT(m.sent_at, '%h:%i %p') as formatted_time
        FROM message m
        WHERE ((m.sender_id = $current_user_id AND m.receiver_id = $other_user_id)
           OR (m.sender_id = $other_user_id AND m.receiver_id = $current_user_id))
           AND m.message_id > $last_message_id
        ORDER BY m.sent_at ASC
    ";
    
    $messages_result = $conn->query($messages_query);
    $messages = [];
    
    if($messages_result && $messages_result->num_rows > 0) {
        while($message = $messages_result->fetch_assoc()) {
            $messages[] = $message;
        }
        
        // Mark received messages as read
        $conn->query("UPDATE message SET is_read = 1 
                     WHERE sender_id = $other_user_id 
                     AND receiver_id = $current_user_id 
                     AND message_id > $last_message_id");
    }
    
    echo json_encode([
        "status" => "success",
        "messages" => $messages
    ]);
    exit();
}

// AJAX check for unread message counts
if(isset($_GET['action']) && $_GET['action'] === 'get_unread_counts') {
    $unread_counts_query = "
        SELECT sender_id, COUNT(*) as count
        FROM message
        WHERE receiver_id = $current_user_id AND is_read = 0
        GROUP BY sender_id
    ";
    
    $unread_counts_result = $conn->query($unread_counts_query);
    $unread_counts = [];
    
    if($unread_counts_result && $unread_counts_result->num_rows > 0) {
        while($row = $unread_counts_result->fetch_assoc()) {
            $unread_counts[$row['sender_id']] = $row['count'];
        }
    }
    
    echo json_encode([
        "status" => "success",
        "unread_counts" => $unread_counts
    ]);
    exit();
}

// Mark messages as read when viewing conversation via regular page load
if(isset($_GET['user'])) {
    $other_user_id = intval($_GET['user']);
    $conn->query("UPDATE message SET is_read = 1 WHERE sender_id = $other_user_id AND receiver_id = $current_user_id");
}

// Fetch list of conversations (users the current user has exchanged messages with)
$conversations_query = "
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = $current_user_id THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        u.username, u.full_name, u.profile_picture,
        (SELECT COUNT(*) FROM message 
         WHERE receiver_id = $current_user_id 
         AND sender_id = (CASE WHEN m.sender_id = $current_user_id THEN m.receiver_id ELSE m.sender_id END)
         AND is_read = 0) as unread_count,
        (SELECT content FROM message 
         WHERE (sender_id = $current_user_id AND receiver_id = (CASE WHEN m.sender_id = $current_user_id THEN m.receiver_id ELSE m.sender_id END))
            OR (sender_id = (CASE WHEN m.sender_id = $current_user_id THEN m.receiver_id ELSE m.sender_id END) AND receiver_id = $current_user_id)
         ORDER BY sent_at DESC LIMIT 1) as last_message,
        (SELECT sent_at FROM message 
         WHERE (sender_id = $current_user_id AND receiver_id = (CASE WHEN m.sender_id = $current_user_id THEN m.receiver_id ELSE m.sender_id END))
            OR (sender_id = (CASE WHEN m.sender_id = $current_user_id THEN m.receiver_id ELSE m.sender_id END) AND receiver_id = $current_user_id)
         ORDER BY sent_at DESC LIMIT 1) as last_message_time
    FROM message m
    JOIN users u ON (m.sender_id = u.user_id OR m.receiver_id = u.user_id) 
                AND u.user_id != $current_user_id
                AND (m.sender_id = $current_user_id OR m.receiver_id = $current_user_id)
    WHERE (m.sender_id = $current_user_id OR m.receiver_id = $current_user_id)
    GROUP BY other_user_id, u.username, u.full_name, u.profile_picture
    ORDER BY last_message_time DESC
";

$conversations_result = $conn->query($conversations_query);

// Count total unread messages
$total_unread_query = "SELECT COUNT(*) as count FROM message WHERE receiver_id = $current_user_id AND is_read = 0";
$total_unread_result = $conn->query($total_unread_query);
$total_unread = 0;
if($total_unread_result && $total_unread_result->num_rows > 0) {
    $row = $total_unread_result->fetch_assoc();
    $total_unread = $row['count'];
}

// If a specific conversation is selected, fetch messages for that conversation
$conversation_user = null;
$messages_result = null;
$last_message_id = 0;

if(isset($_GET['user'])) {
    $other_user_id = intval($_GET['user']);
    
    // Get user info
    $user_query = $conn->query("SELECT * FROM users WHERE user_id = $other_user_id");
    if($user_query && $user_query->num_rows > 0) {
        $conversation_user = $user_query->fetch_assoc();
        
        // Fetch messages between the two users
        $messages_query = "
            SELECT m.*, 
                   CASE WHEN m.sender_id = $current_user_id THEN 'sent' ELSE 'received' END as message_type,
                   DATE_FORMAT(m.sent_at, '%h:%i %p') as formatted_time
            FROM message m
            WHERE (m.sender_id = $current_user_id AND m.receiver_id = $other_user_id)
               OR (m.sender_id = $other_user_id AND m.receiver_id = $current_user_id)
            ORDER BY m.sent_at ASC
        ";
        
        $messages_result = $conn->query($messages_query);
        
        // Get last message ID for polling new messages
        if($messages_result && $messages_result->num_rows > 0) {
            $messages_result->data_seek($messages_result->num_rows - 1);
            $last_message = $messages_result->fetch_assoc();
            $last_message_id = $last_message['message_id'];
            $messages_result->data_seek(0); // Reset pointer
        }
    }
}

// Fetch friends for "New Message" functionality
$friends_query = "
    SELECT u.user_id, u.username, u.full_name, u.profile_picture
    FROM users u
    JOIN friend f ON (f.user_id1 = u.user_id OR f.user_id2 = u.user_id)
                  AND (f.user_id1 = $current_user_id OR f.user_id2 = $current_user_id)
                  AND u.user_id != $current_user_id
    WHERE f.status = 'accepted'
    ORDER BY u.full_name
";

$friends_result = $conn->query($friends_query);

// Current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | MySocial</title>
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
            --chat-sidebar-width: 360px;
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
            height: 100vh;
            display: flex;
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
        
        .left-section {
            display: flex;
            align-items: center;
        }
        
        .page-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-color);
            margin-left: 15px;
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
        
        /* Messages Page Layout */
        .messages-container {
            display: flex;
            width: 100%;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Chat Sidebar */
        .chat-sidebar {
            width: var(--chat-sidebar-width);
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: var(--transition);
        }
        
        .chat-sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: var(--header-height);
        }
        
        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .new-message-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
        }
        
        .new-message-btn:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .conversation-search {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .search-wrapper {
            position: relative;
        }
        
        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-color);
        }
        
        .conversation-search input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: 30px;
            font-size: 14px;
            outline: none;
            background: var(--light-bg);
            color: var(--text-color);
            transition: var(--transition);
        }
        
        .conversation-search input:focus {
            border-color: var(--primary-color);
            background: var(--card-bg);
            box-shadow: 0 0 0 1px var(--primary-color);
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 15px 0;
        }
        
        .conversation {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-color);
            position: relative;
        }
        
        .conversation:last-child {
            border-bottom: none;
        }
        
        .conversation:hover {
            background: var(--light-bg);
        }
        
        .conversation.active {
            background: rgba(29, 161, 242, 0.1);
        }
        
        .conversation.unread {
            background: rgba(29, 161, 242, 0.05);
            font-weight: 500;
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .conversation-name {
            font-weight: 600;
            font-size: 15px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 12px;
            color: var(--text-secondary);
            white-space: nowrap;
        }
        
        .conversation-preview {
            display: flex;
            align-items: center;
        }
        
        .conversation-last-message {
            color: var(--text-secondary);
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }
        
        .unread-badge {
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
            padding: 0 5px;
        }
        
        /* Chat Main Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            background: var(--bg-color);
            position: relative;
        }
        
        .chat-header {
            padding: 10px 20px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            height: var(--header-height);
        }
        
        .back-button {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-secondary);
            cursor: pointer;
            margin-right: 15px;
        }
        
        .chat-user-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .chat-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 15px;
        }
        
        .chat-user-details {
            display: flex;
            flex-direction: column;
        }
        
        .chat-user-name {
            font-weight: 700;
            font-size: 16px;
            color: var(--text-color);
        }
        
        .chat-user-status {
            font-size: 13px;
            color: var(--success-color);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success-color);
        }
        
        .chat-actions {
            display: flex;
            gap: 15px;
        }
        
        .chat-action {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            transition: var(--transition);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .chat-action:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }
        
        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .messages-wrapper {
            max-width: 100%;
            margin: 0 auto;
            width: 100%;
        }
        
        .message-item {
            display: flex;
            flex-direction: column;
            max-width: 70%;
            margin-bottom: 15px;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-item.sent {
            align-self: flex-end;
        }
        
        .message-item.received {
            align-self: flex-start;
        }
        
        .message-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }
        
        .message-item.sent .message-bubble {
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message-item.received .message-bubble {
            background: var(--card-bg);
            color: var(--text-color);
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .message-meta {
            margin-top: 5px;
            display: flex;
            align-items: center;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .message-time {
            margin-right: 5px;
        }
        
        .message-status {
            display: flex;
            align-items: center;
        }
        
        .message-item.sent .message-meta {
            justify-content: flex-end;
        }
        
        .chat-input-container {
            padding: 15px 20px;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-tools {
            display: flex;
            gap: 10px;
        }
        
        .tool-button {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            transition: var(--transition);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .tool-button:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }
        
        .chat-input-wrapper {
            flex: 1;
            position: relative;
        }
        
        .chat-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            outline: none;
            font-size: 15px;
            background: var(--light-bg);
            color: var(--text-color);
            transition: var(--transition);
            resize: none;
            max-height: 120px;
            overflow-y: auto;
        }
        
        .chat-input:focus {
            border-color: var(--primary-color);
            background: var(--card-bg);
        }
        
        .send-button {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
        }
        
        .send-button:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .send-button:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
        }
        
        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 0 20px;
        }
        
        .empty-icon {
            font-size: 70px;
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .empty-subtitle {
            color: var(--text-secondary);
            margin-bottom: 25px;
            max-width: 400px;
        }
        
        .empty-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 30px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .empty-button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        /* New Message Modal */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: var(--card-bg);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: translateY(20px);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .modal-backdrop.show .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-secondary);
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .modal-close:hover {
            background: var(--light-bg);
            color: var(--danger-color);
        }
        
        .modal-body {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        
        .modal-search {
            margin-bottom: 20px;
            position: relative;
        }
        
        .modal-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-color);
        }
        
        .modal-search input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid var(--border-color);
            border-radius: 30px;
            font-size: 15px;
            outline: none;
            background: var(--light-bg);
            color: var(--text-color);
            transition: var(--transition);
        }
        
        .modal-search input:focus {
            border-color: var(--primary-color);
            background: var(--card-bg);
            box-shadow: 0 0 0 1px var(--primary-color);
        }
        
        .contacts-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .contact-item:hover {
            background: var(--light-bg);
        }
        
        .contact-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .contact-info {
            flex: 1;
            min-width: 0;
        }
        
        .contact-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .contact-username {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-secondary {
            background: var(--light-bg);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background: var(--border-color);
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            background: var(--light-bg);
            padding: 10px 15px;
            border-radius: 18px;
            margin-bottom: 15px;
            align-self: flex-start;
            animation: fadeIn 0.3s ease-out;
        }
        
        .typing-dots {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-secondary);
            animation: typing 1.5s infinite;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }
        
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .chat-sidebar {
                width: 300px;
            }
            
            .conversation-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
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
            
            .chat-sidebar {
                width: 100%;
                position: absolute;
                height: 100%;
                z-index: 10;
                transform: translateX(-100%);
            }
            
            .chat-sidebar.active {
                transform: translateX(0);
            }
            
            .chat-main {
                width: 100%;
            }
            
            .chat-sidebar.active + .chat-main {
                display: none;
            }
            
            .back-button {
                display: flex;
            }
            
            .page-title {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .search-bar {
                display: none;
            }
            
            .message-item {
                max-width: 85%;
            }
            
            .message-bubble {
                padding: 10px 14px;
            }
            
            .chat-tools {
                display: none;
            }
        }
        
        /* Dark Mode Support */
        .dark-mode .message-item.received .message-bubble {
            background: #2A3942;
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
        
        .char-count {
            position: absolute;
            right: 50px;
            bottom: 12px;
            font-size: 12px;
            color: var(--text-secondary);
            pointer-events: none;
        }
        
        .hidden {
            display: none !important;
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
                    <?php if($total_unread > 0): ?>
                        <span class="badge"><?= $total_unread ?></span>
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
            <div class="page-title">Messages</div>
        </div>
        
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="global-search" placeholder="Search MySocial...">
        </div>
        
        <div class="user-menu">
            <div class="theme-toggle" id="theme-toggle">
                <i class="fas fa-moon"></i>
            </div>
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">2</span>
            </div>
            <div class="avatar" id="user-avatar">
                <?= strtoupper(substr($current_username, 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="messages-container">
            <!-- Chat Sidebar -->
            <div class="chat-sidebar <?= isset($_GET['user']) ? '' : 'active' ?>">
                <div class="chat-sidebar-header">
                    <div class="sidebar-title">Messages</div>
                    <button type="button" class="new-message-btn" id="new-message-btn" aria-label="New Message">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
                
                <div class="conversation-search">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="conversation-search-input" placeholder="Search in messages">
                    </div>
                </div>
                
                <div class="conversations-list" id="conversations-list">
                    <?php if($conversations_result && $conversations_result->num_rows > 0): ?>
                        <?php while($conversation = $conversations_result->fetch_assoc()): ?>
                            <?php 
                            // Format last message time
                            $message_time = new DateTime($conversation['last_message_time']);
                            $now = new DateTime($current_date);
                            $diff = $now->diff($message_time);
                            
                            if($diff->y > 0) {
                                $time_ago = $diff->y . 'y';
                            } elseif($diff->m > 0) {
                                $time_ago = $diff->m . 'mo';
                            } elseif($diff->d > 0) {
                                $time_ago = $diff->d . 'd';
                            } elseif($diff->h > 0) {
                                $time_ago = $diff->h . 'h';
                            } elseif($diff->i > 0) {
                                $time_ago = $diff->i . 'm';
                            } else {
                                $time_ago = 'now';
                            }
                            
                            // Get first letter of username for avatar
                            $avatar_letter = substr($conversation['full_name'] ?? $conversation['username'], 0, 1);
                            
                            // Check if this is the current conversation
                            $is_active = isset($_GET['user']) && $_GET['user'] == $conversation['other_user_id'];
                            ?>
                            <a href="messages.php?user=<?= $conversation['other_user_id'] ?>" class="conversation <?= $is_active ? 'active' : '' ?> <?= $conversation['unread_count'] > 0 ? 'unread' : '' ?>" data-user-id="<?= $conversation['other_user_id'] ?>">
                                <div class="conversation-avatar">
                                    <?= strtoupper($avatar_letter) ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-header">
                                        <div class="conversation-name"><?= htmlspecialchars($conversation['full_name'] ?? $conversation['username']) ?></div>
                                        <div class="conversation-time"><?= $time_ago ?></div>
                                    </div>
                                    <div class="conversation-preview">
                                        <div class="conversation-last-message">
                                            <?= htmlspecialchars(substr($conversation['last_message'], 0, 50)) . (strlen($conversation['last_message']) > 50 ? '...' : '') ?>
                                        </div>
                                        <?php if($conversation['unread_count'] > 0): ?>
                                            <div class="unread-badge" data-user-id="<?= $conversation['other_user_id'] ?>"><?= $conversation['unread_count'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-conversations" id="empty-conversations">
                            <div style="padding: 30px; text-align: center; color: var(--text-secondary);">
                                <i class="far fa-comment-dots" style="font-size: 40px; margin-bottom: 15px; opacity: 0.7;"></i>
                                <p>No conversations yet</p>
                                <p>Start a new message to begin chatting</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Main -->
            <div class="chat-main <?= isset($_GET['user']) ? 'active' : '' ?>">
                <?php if(isset($_GET['user']) && $conversation_user): ?>
                    <div class="chat-header">
                        <button type="button" class="back-button" id="back-button" aria-label="Back to conversations">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        
                        <div class="chat-user-info">
                            <div class="chat-user-avatar">
                                <?= strtoupper(substr($conversation_user['full_name'] ?? $conversation_user['username'], 0, 1)) ?>
                            </div>
                            <div class="chat-user-details">
                                <div class="chat-user-name"><?= htmlspecialchars($conversation_user['full_name'] ?? $conversation_user['username']) ?></div>
                                <div class="chat-user-status">
                                    <div class="status-indicator"></div>
                                    <span>Online</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-actions">
                            <button type="button" class="chat-action" aria-label="Search in conversation">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="chat-action" aria-label="Call">
                                <i class="fas fa-phone"></i>
                            </button>
                            <button type="button" class="chat-action" aria-label="Video call">
                                <i class="fas fa-video"></i>
                            </button>
                            <button type="button" class="chat-action" aria-label="More options">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="messages-container" id="messages-container">
                        <div class="messages-wrapper" id="messages-wrapper">
                            <?php if($messages_result && $messages_result->num_rows > 0): ?>
                                <?php while($message = $messages_result->fetch_assoc()): ?>
                                    <div class="message-item <?= $message['message_type'] ?>" data-message-id="<?= $message['message_id'] ?>">
                                        <div class="message-bubble">
                                            <?= nl2br(htmlspecialchars($message['content'])) ?>
                                        </div>
                                        <div class="message-meta">
                                            <span class="message-time"><?= $message['formatted_time'] ?></span>
                                            <?php if($message['message_type'] == 'sent'): ?>
                                                <span class="message-status">
                                                    <?php if($message['is_read']): ?>
                                                        <i class="fas fa-check-double" title="Read"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-check" title="Delivered"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-messages-yet" style="text-align: center; margin: 30px 0; color: var(--text-secondary); padding: 20px;">
                                    <p>No messages yet</p>
                                    <p>Send a message to start the conversation!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="chat-input-container">
                        <div class="chat-tools">
                            <button type="button" class="tool-button" aria-label="Emoji">
                                <i class="far fa-smile"></i>
                            </button>
                            <button type="button" class="tool-button" aria-label="Attach file">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <button type="button" class="tool-button" aria-label="Take photo">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        
                        <div class="chat-input-wrapper">
                            <textarea 
                                id="message-input" 
                                class="chat-input" 
                                placeholder="Type a message..." 
                                rows="1" 
                                maxlength="500"
                                data-receiver-id="<?= $conversation_user['user_id'] ?>"
                            ></textarea>
                            <div class="char-count hidden">0/500</div>
                        </div>
                        
                        <button 
                            type="button" 
                            id="send-message-btn" 
                            class="send-button" 
                            aria-label="Send message" 
                            disabled
                        >
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="far fa-comments"></i>
                        </div>
                        <h2 class="empty-title">Your Messages</h2>
                        <p class="empty-subtitle">
                            Send private messages to a friend or group of friends. 
                            Your messages are end-to-end encrypted.
                        </p>
                        <button type="button" class="empty-button" id="new-message-btn-alt">
                            <i class="fas fa-edit"></i> New Message
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- New Message Modal -->
    <div class="modal-backdrop" id="new-message-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">New Message</div>
                <button type="button" class="modal-close" id="close-modal" aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="modal-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="contact-search" placeholder="Search for people">
                </div>
                
                <div class="contacts-list" id="contacts-list">
                    <?php if($friends_result && $friends_result->num_rows > 0): ?>
                        <?php while($friend = $friends_result->fetch_assoc()): ?>
                            <?php 
                            // Get first letter of friend's name for avatar
                            $avatar_letter = substr($friend['full_name'] ?? $friend['username'], 0, 1);
                            ?>
                            <a href="messages.php?user=<?= $friend['user_id'] ?>" class="contact-item" data-user-id="<?= $friend['user_id'] ?>" data-username="<?= htmlspecialchars($friend['username']) ?>">
                                <div class="contact-avatar">
                                    <?= strtoupper($avatar_letter) ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($friend['full_name'] ?? $friend['username']) ?></div>
                                    <div class="contact-username">@<?= htmlspecialchars($friend['username']) ?></div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="padding: 30px; text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-user-friends" style="font-size: 40px; margin-bottom: 15px; opacity: 0.7;"></i>
                            <p>You don't have any friends yet</p>
                            <p>Add friends to start messaging!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-modal">Cancel</button>
            </div>
        </div>
    </div>

    <!-- JavaScript (AJAX functionality) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const body = document.body;
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            const backdrop = document.querySelector('.backdrop');
            const themeToggle = document.getElementById('theme-toggle');
            const chatSidebar = document.querySelector('.chat-sidebar');
            const chatMain = document.querySelector('.chat-main');
            const backButton = document.getElementById('back-button');
            const messagesContainer = document.getElementById('messages-container');
            const messagesWrapper = document.getElementById('messages-wrapper');
            const messageInput = document.getElementById('message-input');
            const sendMessageBtn = document.getElementById('send-message-btn');
            const newMessageBtn = document.getElementById('new-message-btn');
            const newMessageBtnAlt = document.getElementById('new-message-btn-alt');
            const newMessageModal = document.getElementById('new-message-modal');
            const closeModal = document.getElementById('close-modal');
            const cancelModal = document.getElementById('cancel-modal');
            const contactSearch = document.getElementById('contact-search');
            const contactItems = document.querySelectorAll('.contact-item');
            const conversationSearchInput = document.getElementById('conversation-search-input');
            const conversations = document.querySelectorAll('.conversation');
            
            // Variables
            let typingTimer;
            let lastMessageId = <?= $last_message_id ?>;
            let isPolling = <?= isset($_GET['user']) ? 'true' : 'false' ?>;
            let receiverId = <?= isset($_GET['user']) ? intval($_GET['user']) : '0' ?>;
            
                        // Initialize - scroll to bottom of messages
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
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
            
            // Toggle dark/light mode
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
                
                // Initialize theme
                initTheme();
            }
            
            // Mobile sidebar toggle
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
            
            // Back button on mobile view
            if (backButton) {
                backButton.addEventListener('click', function() {
                    chatSidebar.classList.add('active');
                    chatMain.classList.remove('active');
                    history.pushState({}, '', 'messages.php');
                    isPolling = false; // Stop polling for new messages
                });
            }
            
            // New Message Modal Functionality
            function openNewMessageModal() {
                newMessageModal.classList.add('show');
                document.body.style.overflow = 'hidden';
                
                // Focus the search input
                if (contactSearch) {
                    setTimeout(() => {
                        contactSearch.focus();
                    }, 300);
                }
            }
            
            function closeNewMessageModal() {
                newMessageModal.classList.remove('show');
                document.body.style.overflow = '';
                
                // Reset the search
                if (contactSearch) {
                    contactSearch.value = '';
                    contactItems.forEach(item => {
                        item.style.display = '';
                    });
                }
            }
            
            // Open new message modal buttons
            if (newMessageBtn) {
                newMessageBtn.addEventListener('click', openNewMessageModal);
            }
            
            if (newMessageBtnAlt) {
                newMessageBtnAlt.addEventListener('click', openNewMessageModal);
            }
            
            // Close new message modal buttons
            if (closeModal) {
                closeModal.addEventListener('click', closeNewMessageModal);
            }
            
            if (cancelModal) {
                cancelModal.addEventListener('click', closeNewMessageModal);
            }
            
            // Close modal when clicking outside of it
            if (newMessageModal) {
                newMessageModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeNewMessageModal();
                    }
                });
            }
            
            // Contact search functionality
            if (contactSearch) {
                contactSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    contactItems.forEach(item => {
                        const name = item.querySelector('.contact-name').textContent.toLowerCase();
                        const username = item.querySelector('.contact-username').textContent.toLowerCase();
                        
                        if (name.includes(searchTerm) || username.includes(searchTerm)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
            
            // Conversation search functionality
            if (conversationSearchInput) {
                conversationSearchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    
                    conversations.forEach(convo => {
                        const name = convo.querySelector('.conversation-name').textContent.toLowerCase();
                        const message = convo.querySelector('.conversation-last-message').textContent.toLowerCase();
                        
                        if (name.includes(searchTerm) || message.includes(searchTerm)) {
                            convo.style.display = '';
                        } else {
                            convo.style.display = 'none';
                        }
                    });
                });
            }
            
            // Message input auto-grow functionality
            if (messageInput) {
                // Function to update character count
                function updateCharCount() {
                    const current = messageInput.value.length;
                    const max = messageInput.getAttribute('maxlength');
                    const charCount = messageInput.parentNode.querySelector('.char-count');
                    
                    if (current > 0) {
                        charCount.textContent = `${current}/${max}`;
                        charCount.classList.remove('hidden');
                    } else {
                        charCount.classList.add('hidden');
                    }
                    
                    if (current > max * 0.8) {
                        charCount.style.color = 'var(--danger-color)';
                    } else {
                        charCount.style.color = 'var(--text-secondary)';
                    }
                }
                
                // Auto-grow functionality
                function adjustHeight() {
                    messageInput.style.height = 'auto';
                    const newHeight = Math.min(messageInput.scrollHeight, 120);
                    messageInput.style.height = newHeight + 'px';
                }
                
                messageInput.addEventListener('input', function() {
                    adjustHeight();
                    updateCharCount();
                    
                    // Enable/disable send button based on content
                    if (this.value.trim().length > 0) {
                        sendMessageBtn.removeAttribute('disabled');
                    } else {
                        sendMessageBtn.setAttribute('disabled', 'disabled');
                    }
                });
                
                // Initialize height
                adjustHeight();
                
                // Submit on Enter (without Shift)
                messageInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (!sendMessageBtn.hasAttribute('disabled')) {
                            sendMessageBtn.click();
                        }
                    }
                });
            }
            
            // AJAX Send Message functionality
            if (sendMessageBtn) {
                sendMessageBtn.addEventListener('click', function() {
                    if (!messageInput.value.trim()) return;
                    
                    const content = messageInput.value;
                    const receiver = messageInput.getAttribute('data-receiver-id');
                    
                    // Disable input and button while sending
                    messageInput.setAttribute('disabled', 'disabled');
                    sendMessageBtn.setAttribute('disabled', 'disabled');
                    
                    // AJAX request to send message
                    fetch('messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=send_message&receiver_id=${receiver}&message_content=${encodeURIComponent(content)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Add the message to the UI
                            const message = data.message;
                            const formattedTime = message.formatted_time;
                            
                            const messageElement = document.createElement('div');
                            messageElement.className = 'message-item sent';
                            messageElement.setAttribute('data-message-id', message.message_id);
                            
                            messageElement.innerHTML = `
                                <div class="message-bubble">
                                    ${content.replace(/\n/g, '<br>')}
                                </div>
                                <div class="message-meta">
                                    <span class="message-time">${formattedTime}</span>
                                    <span class="message-status">
                                        <i class="fas fa-check" title="Delivered"></i>
                                    </span>
                                </div>
                            `;
                            
                            messagesWrapper.appendChild(messageElement);
                            
                            // Scroll to the bottom
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            
                            // Clear the input and reset height
                            messageInput.value = '';
                            messageInput.style.height = 'auto';
                            
                            // Update last message ID for polling
                            lastMessageId = message.message_id;
                            
                            // Update the conversation list
                            updateConversationPreview(receiver, content);
                        } else {
                            console.error('Failed to send message:', data.message);
                            alert('Failed to send message. Please try again.');
                        }
                        
                        // Re-enable input and button
                        messageInput.removeAttribute('disabled');
                        sendMessageBtn.setAttribute('disabled', 'disabled');
                        messageInput.focus();
                    })
                    .catch(error => {
                        console.error('Error sending message:', error);
                        alert('Network error. Please try again.');
                        
                        // Re-enable input and button
                        messageInput.removeAttribute('disabled');
                        sendMessageBtn.removeAttribute('disabled');
                        messageInput.focus();
                    });
                });
            }
            
            // Function to update conversation preview in the sidebar
            function updateConversationPreview(userId, message) {
                // Find the conversation in the list
                const conversation = document.querySelector(`.conversation[data-user-id="${userId}"]`);
                
                if (conversation) {
                    // Update the last message
                    const lastMessageEl = conversation.querySelector('.conversation-last-message');
                    if (lastMessageEl) {
                        lastMessageEl.textContent = message.length > 50 ? message.substring(0, 50) + '...' : message;
                    }
                    
                    // Update the time
                    const timeEl = conversation.querySelector('.conversation-time');
                    if (timeEl) {
                        timeEl.textContent = 'now';
                    }
                    
                    // Move the conversation to the top
                    const parent = conversation.parentElement;
                    if (parent && parent.firstChild) {
                        parent.insertBefore(conversation, parent.firstChild);
                    }
                } else {
                    // This is a new conversation, we'll update on page refresh
                }
            }
            
            // Poll for new messages
            function pollNewMessages() {
                if (!isPolling || !receiverId) return;
                
                fetch(`messages.php?action=get_new_messages&user_id=${receiverId}&last_message_id=${lastMessageId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.messages.length > 0) {
                            let shouldScroll = 
                                messagesContainer.scrollTop + messagesContainer.clientHeight >= 
                                messagesContainer.scrollHeight - 100;
                            
                            data.messages.forEach(message => {
                                // Check if the message is already in the DOM
                                if (!document.querySelector(`.message-item[data-message-id="${message.message_id}"]`)) {
                                    // Add the message to the UI
                                    const messageElement = document.createElement('div');
                                    messageElement.className = `message-item ${message.message_type}`;
                                    messageElement.setAttribute('data-message-id', message.message_id);
                                    
                                    messageElement.innerHTML = `
                                        <div class="message-bubble">
                                            ${message.content.replace(/\n/g, '<br>')}
                                        </div>
                                        <div class="message-meta">
                                            <span class="message-time">${message.formatted_time}</span>
                                            ${message.message_type === 'sent' ? `
                                                <span class="message-status">
                                                    <i class="${message.is_read ? 'fas fa-check-double' : 'fas fa-check'}" title="${message.is_read ? 'Read' : 'Delivered'}"></i>
                                                </span>
                                            ` : ''}
                                        </div>
                                    `;
                                    
                                    messagesWrapper.appendChild(messageElement);
                                    
                                    // Update last message ID
                                    if (message.message_id > lastMessageId) {
                                        lastMessageId = message.message_id;
                                    }
                                }
                            });
                            
                            // Scroll to bottom if we were at the bottom before new messages
                            if (shouldScroll) {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error polling messages:', error);
                    })
                    .finally(() => {
                        // Continue polling if active
                        if (isPolling) {
                            setTimeout(pollNewMessages, 3000);
                        }
                    });
            }
            
            // Start polling if we're on a conversation
            if (isPolling) {
                pollNewMessages();
            }
            
            // Poll for unread message counts
            function pollUnreadCounts() {
                fetch('messages.php?action=get_unread_counts')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const unreadCounts = data.unread_counts;
                            
                            // Update unread counts in conversations
                            for (const userId in unreadCounts) {
                                const count = unreadCounts[userId];
                                const badge = document.querySelector(`.unread-badge[data-user-id="${userId}"]`);
                                const conversation = document.querySelector(`.conversation[data-user-id="${userId}"]`);
                                
                                if (badge) {
                                    badge.textContent = count;
                                } else if (count > 0 && conversation) {
                                    // Create badge if it doesn't exist
                                    const preview = conversation.querySelector('.conversation-preview');
                                    if (preview) {
                                        const newBadge = document.createElement('div');
                                        newBadge.className = 'unread-badge';
                                        newBadge.setAttribute('data-user-id', userId);
                                        newBadge.textContent = count;
                                        preview.appendChild(newBadge);
                                    }
                                    
                                    // Add unread class
                                    conversation.classList.add('unread');
                                }
                            }
                            
                            // Update total unread count in navbar
                            const totalUnread = Object.values(unreadCounts).reduce((a, b) => a + b, 0);
                            const navBadge = document.querySelector('.nav-link .badge');
                            
                            if (totalUnread > 0) {
                                if (navBadge) {
                                    navBadge.textContent = totalUnread;
                                } else {
                                    const messagesLink = document.querySelector('.nav-link[href="messages.php"]');
                                    if (messagesLink) {
                                        const newBadge = document.createElement('span');
                                        newBadge.className = 'badge';
                                        newBadge.textContent = totalUnread;
                                        messagesLink.appendChild(newBadge);
                                    }
                                }
                            } else if (navBadge) {
                                navBadge.remove();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error polling unread counts:', error);
                    })
                    .finally(() => {
                        setTimeout(pollUnreadCounts, 5000);
                    });
            }
            
            // Start polling for unread counts
            pollUnreadCounts();
            
            // Handle contact item clicks in modal
            document.querySelectorAll('.contact-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-user-id');
                    const username = this.getAttribute('data-username');
                    
                    // Close the modal
                    closeNewMessageModal();
                    
                    // Navigate to the conversation
                    window.location.href = `messages.php?user=${userId}`;
                });
            });
            
            // Update timestamp relative to current time
            function updateRelativeTimes() {
                const now = new Date('<?= $current_date ?>');
                const timeElements = document.querySelectorAll('.conversation-time');
                
                timeElements.forEach(el => {
                    const parent = el.closest('.conversation');
                    if (!parent) return;
                    
                    const userId = parent.getAttribute('data-user-id');
                    // We'd need the actual timestamp for each conversation to update properly
                    // This is just a placeholder for the concept
                });
            }
            
            // Update relative times periodically
            setInterval(updateRelativeTimes, 60000);
            
            // Display typing indicator (simulation)
            function showTypingIndicator() {
                if (!messagesWrapper || !isPolling) return;
                
                const existingIndicator = document.querySelector('.typing-indicator');
                if (existingIndicator) return;
                
                const typingIndicator = document.createElement('div');
                typingIndicator.className = 'typing-indicator';
                typingIndicator.innerHTML = `
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                `;
                
                messagesWrapper.appendChild(typingIndicator);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
                // Remove after a few seconds
                setTimeout(() => {
                    if (typingIndicator.parentNode) {
                        typingIndicator.parentNode.removeChild(typingIndicator);
                    }
                }, 3000);
            }
            
            // Simulate typing indicator randomly for demo purposes
            if (isPolling) {
                setTimeout(() => {
                    const random = Math.random();
                    if (random > 0.7) {
                        showTypingIndicator();
                    }
                }, 10000);
            }
            
            // Initialize current user in UI
            const userAvatar = document.getElementById('user-avatar');
            if (userAvatar) {
                userAvatar.textContent = '<?= strtoupper(substr($current_username, 0, 1)) ?>';
            }

            // Responsive behavior: toggle to conversation view on small screens when a conversation is selected
            if (window.innerWidth <= 768 && chatMain.classList.contains('active')) {
                chatSidebar.classList.remove('active');
            }
            
            // Handle window resize events for responsive behavior
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768 && chatMain.classList.contains('active')) {
                    chatSidebar.classList.remove('active');
                } else if (window.innerWidth > 768) {
                    chatSidebar.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>