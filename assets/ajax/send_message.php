<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
$sender_id = get_current_user_id();

if ($listing_id <= 0 || $receiver_id <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit;
}

if ($sender_id == $receiver_id) {
    echo json_encode(['success' => false, 'message' => 'Cannot message yourself']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        INSERT INTO messages (listing_id, sender_id, receiver_id, message) 
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$listing_id, $sender_id, $receiver_id, $message])) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>