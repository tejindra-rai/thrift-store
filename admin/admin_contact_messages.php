<?php
require_once '../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_admin();

$db = Database::getInstance()->getConnection();
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_read'])) {
        $contact_id = (int)$_POST['contact_id'];
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        if ($stmt->execute([$contact_id])) {
            $message = '<div class="alert alert-success">Message marked as read!</div>';
        }
    }
    
    if (isset($_POST['mark_replied'])) {
        $contact_id = (int)$_POST['contact_id'];
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied' WHERE id = ?");
        if ($stmt->execute([$contact_id])) {
            $message = '<div class="alert alert-success">Message marked as replied!</div>';
        }
    }
    
    if (isset($_POST['delete_message'])) {
        $contact_id = (int)$_POST['contact_id'];
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
        if ($stmt->execute([$contact_id])) {
            $message = '<div class="alert alert-success">Message deleted successfully!</div>';
        }
    }
}

// Get filters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "SELECT * FROM contact_messages WHERE $where_sql ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM contact_messages");
$total_messages = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'new'");
$new_messages = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'read'");
$read_messages = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'replied'");
$replied_messages = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_contact_messages.css">
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
                <a href="admin_messages.php">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                </a>
                <a href="admin_contact_messages.php" class="active">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                    <?php if($new_messages > 0): ?>
                        <span class="badge bg-danger"><?php echo $new_messages; ?></span>
                    <?php endif; ?>
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
            <h1 class="mb-4">Contact Messages</h1>

            <?php echo $message; ?>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-box">
                    <h3><?php echo $total_messages; ?></h3>
                    <p class="text-muted mb-0">Total Messages</p>
                </div>
                <div class="stat-box new">
                    <h3><?php echo $new_messages; ?></h3>
                    <p class="text-muted mb-0">New Messages</p>
                </div>
                <div class="stat-box read">
                    <h3><?php echo $read_messages; ?></h3>
                    <p class="text-muted mb-0">Read Messages</p>
                </div>
                <div class="stat-box replied">
                    <h3><?php echo $replied_messages; ?></h3>
                    <p class="text-muted mb-0">Replied Messages</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Search by name, email, subject, or message..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
                                <option value="replied" <?php echo $status_filter == 'replied' ? 'selected' : ''; ?>>Replied</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-envelope"></i> All Messages (<?php echo count($contacts); ?>)
                </div>
                <div class="card-body">
                    <?php if(empty($contacts)): ?>
                        <p class="text-muted text-center">No contact messages found</p>
                    <?php else: ?>
                        <?php foreach($contacts as $contact): ?>
                            <div class="message-card <?php echo $contact['status']; ?>">
                                <div class="message-header">
                                    <div>
                                        <h5>
                                            <strong><?php echo htmlspecialchars($contact['name']); ?></strong>
                                            <span class="badge bg-<?php echo $contact['status'] == 'new' ? 'danger' : ($contact['status'] == 'read' ? 'warning' : 'success'); ?> ms-2">
                                                <?php echo ucfirst($contact['status']); ?>
                                            </span>
                                        </h5>
                                        <div class="message-meta">
                                            <span>
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($contact['email']); ?>
                                            </span>
                                            <span>
                                                <i class="far fa-clock"></i>
                                                <?php echo time_ago($contact['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="message-actions">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if($contact['status'] == 'new'): ?>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                            <button type="submit" name="mark_read" class="dropdown-item">
                                                                <i class="fas fa-eye"></i> Mark as Read
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                <?php if($contact['status'] != 'replied'): ?>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                            <button type="submit" name="mark_replied" class="dropdown-item">
                                                                <i class="fas fa-reply"></i> Mark as Replied
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                        <button type="submit" name="delete_message" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="message-subject">
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($contact['subject']); ?>
                                </div>
                                
                                <div class="message-body">
                                    <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
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