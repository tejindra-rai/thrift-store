<?php
require_once '../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_admin();

$db = Database::getInstance()->getConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_message'])) {
    $message_id = (int)$_POST['message_id'];
    
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    if ($stmt->execute([$message_id])) {
        $message = '<div class="alert alert-success">Message deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to delete message.</div>';
    }
}

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(sender.username LIKE ? OR receiver.username LIKE ? OR m.message LIKE ? OR l.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "
    SELECT m.*, 
           sender.username as sender_name, 
           receiver.username as receiver_name,
           l.title as listing_title
    FROM messages m
    JOIN users sender ON m.sender_id = sender.id
    JOIN users receiver ON m.receiver_id = receiver.id
    JOIN listings l ON m.listing_id = l.id
    WHERE $where_sql
    ORDER BY m.sent_at DESC
    LIMIT 100
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$stmt = $db->query("SELECT COUNT(*) as total FROM messages");
$total_messages = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
$unread_messages = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Messages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_messages.css">

</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
                <small>Thrift Store Management</small>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_users.php">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="admin_listings.php">
                    <i class="fas fa-box"></i>
                    <span>Manage Listings</span>
                </a>
                <a href="admin_categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="admin_messages.php" class="active">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                </a>
                <a href="admin_contact_messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                </a>
                <a href="admin_reviews.php">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 20px 0;">
                <a href="../index.php">
                    <i class="fas fa-globe"></i>
                    <span>View Site</span>
                </a>
                <a href="../assets/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1 class="mb-4">Manage Messages</h1>

            <?php echo $message; ?>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-box">
                    <h3><?php echo $total_messages; ?></h3>
                    <p class="text-muted mb-0">Total Messages</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $unread_messages; ?></h3>
                    <p class="text-muted mb-0">Unread Messages</p>
                </div>
            </div>

            <!-- Search -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="search" placeholder="Search messages, users, or listings..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-comments"></i> Recent Messages (Last 100)
                </div>
                <div class="card-body">
                    <?php if(empty($messages)): ?>
                        <p class="text-muted text-center">No messages found</p>
                    <?php else: ?>
                        <?php foreach($messages as $msg): ?>
                            <div class="message-row">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="mb-2">
                                            <?php if(!$msg['is_read']): ?>
                                                <span class="unread-indicator"></span>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                            <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                            <strong><?php echo htmlspecialchars($msg['receiver_name']); ?></strong>
                                            <span class="badge bg-info ms-2">
                                                <i class="fas fa-box"></i> <?php echo htmlspecialchars($msg['listing_title']); ?>
                                            </span>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i> <?php echo time_ago($msg['sent_at']); ?>
                                            <?php if(!$msg['is_read']): ?>
                                                <span class="badge bg-danger ms-2">Unread</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" name="delete_message" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin-mobile.js"></script>

</body>
</html>