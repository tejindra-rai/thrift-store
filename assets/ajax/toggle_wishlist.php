<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$listing_id = isset($data['listing_id']) ? (int)$data['listing_id'] : 0;
$user_id = get_current_user_id();

if ($listing_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid listing ID']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?");
    $stmt->execute([$user_id, $listing_id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND listing_id = ?");
        $stmt->execute([$user_id, $listing_id]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist']);
    } else {
        $stmt = $db->prepare("INSERT INTO wishlists (user_id, listing_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $listing_id]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>