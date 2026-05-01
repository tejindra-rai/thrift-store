<?php
require_once '../../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = get_current_user_id();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        CASE 
            WHEN m.sender_id = ? THEN receiver.username 
            ELSE sender.username 
        END as other_username,
        CASE 
            WHEN m.sender_id = ? THEN receiver.profile_pic 
            ELSE sender.profile_pic 
        END as other_profile_pic,
        l.id as listing_id,
        l.title as listing_title,
        MAX(m.sent_at) as last_message_time,
        (SELECT message FROM messages 
         WHERE (sender_id = ? AND receiver_id = other_user_id) 
            OR (sender_id = other_user_id AND receiver_id = ?)
         ORDER BY sent_at DESC LIMIT 1) as last_message,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
    FROM messages m
    JOIN users sender ON m.sender_id = sender.id
    JOIN users receiver ON m.receiver_id = receiver.id
    JOIN listings l ON m.listing_id = l.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id, listing_id
    ORDER BY last_message_time DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

$selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$selected_listing_id = isset($_GET['listing']) ? (int)$_GET['listing'] : 0;

$messages = [];
$other_user = null;
$listing_info = null;

if ($selected_user_id > 0 && $selected_listing_id > 0) {
    $stmt = $db->prepare("
        SELECT m.*, u.username, u.profile_pic
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.listing_id = ?
          AND ((m.sender_id = ? AND m.receiver_id = ?) 
            OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([$selected_listing_id, $user_id, $selected_user_id, $selected_user_id, $user_id]);
    $messages = $stmt->fetchAll();
    
    $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND listing_id = ?");
    $stmt->execute([$user_id, $selected_user_id, $selected_listing_id]);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$selected_user_id]);
    $other_user = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT * FROM listings WHERE id = ?");
    $stmt->execute([$selected_listing_id]);
    $listing_info = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main_css/messages.css">
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="container-fluid messages-container">
        <div class="row h-100">
            <!-- Conversations List -->
            <div class="col-md-4">
                <div class="conversations-list">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="fas fa-comments"></i> Messages</h5>
                    </div>
                    <?php if(empty($conversations)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="far fa-comment-dots fa-3x mb-3"></i>
                            <p>No conversations yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($conversations as $conv): ?>
                            <a href="messages.php?user=<?php echo $conv['other_user_id']; ?>&listing=<?php echo $conv['listing_id']; ?>" 
                               class="conversation-item d-flex align-items-center text-decoration-none <?php echo ($conv['other_user_id'] == $selected_user_id && $conv['listing_id'] == $selected_listing_id) ? 'active' : ''; ?>">
                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $conv['other_profile_pic']; ?>" 
                                     class="conversation-avatar"
                                     onerror="this.src='assets/default-avatar.jpg'">
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($conv['other_username']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($conv['listing_title']); ?></div>
                                    <div class="small text-truncate" style="max-width: 200px;">
                                        <?php echo htmlspecialchars($conv['last_message']); ?>
                                    </div>
                                </div>
                                <?php if($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="col-md-8">
                <div class="chat-area">
                    <?php if($other_user && $listing_info): ?>
                        <div class="chat-header">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $other_user['profile_pic']; ?>" 
                                     class="conversation-avatar"
                                     onerror="this.src='assets/default-avatar.jpg'">
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($other_user['username']); ?></h5>
                                    <small>Re: <?php echo htmlspecialchars($listing_info['title']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php if($listing_info): ?>
                                <div class="listing-preview">
                                    <img src="<?php echo $listing_info['primary_image'] ? SITE_URL.'/uploads/'.$listing_info['primary_image'] : 'https://via.placeholder.com/200x150/cccccc/666666?text=No+Image'; ?>" 
                                         onerror="this.src='https://via.placeholder.com/200x150/cccccc/666666?text=No+Image'">
                                    <div>
                                        <strong><?php echo htmlspecialchars($listing_info['title']); ?></strong>
                                        <div class="text-primary"><?php echo format_price($listing_info['price']); ?></div>
                                        <a href="listing_detail.php?id=<?php echo $listing_info['id']; ?>" class="btn btn-sm btn-primary mt-1">View Listing</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php foreach($messages as $msg): ?>
                                <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $msg['profile_pic']; ?>" 
                                         class="message-avatar"
                                         onerror="this.src='assets/default-avatar.jpg'">
                                    <div>
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                        <div class="message-time"><?php echo time_ago($msg['sent_at']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="chat-input">
                            <form id="messageForm">
                                <input type="hidden" name="listing_id" value="<?php echo $selected_listing_id; ?>">
                                <input type="hidden" name="receiver_id" value="<?php echo $selected_user_id; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="message" placeholder="Type your message..." required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="empty-chat">
                            <i class="far fa-comments"></i>
                            <h4>Select a conversation</h4>
                            <p>Choose a conversation from the list to start messaging</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const chatMessages = document.getElementById('chatMessages');
        if(chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        document.getElementById('messageForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../ajax/send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>