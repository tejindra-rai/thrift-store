<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Tejindra_Rai_25126478');

define('SITE_URL', 'http://localhost/thrift-store');
define('SITE_NAME', 'JML Thrift Store');

define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); 
session_start();

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}


function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit();
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function format_price($price) {
    return 'Rs ' . number_format($price, 2);
}

function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if($seconds <= 60) return "Just now";
    else if($minutes <= 60) return "$minutes minutes ago";
    else if($hours <= 24) return "$hours hours ago";
    else if($days <= 7) return "$days days ago";
    else if($weeks <= 4.3) return "$weeks weeks ago";
    else if($months <= 12) return "$months months ago";
    else return "$years years ago";
}

function get_user_rating($user_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT rating FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? round($result['rating'], 1) : 0;
}


function upload_image($file, $type = 'general') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        error_log("Upload failed: No file provided");
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error code: " . $file['error']);
        return false;
    }
    
    $subfolders = [
        'profile' => 'profiles/',
        'listing' => 'listings/',
        'general' => 'general/',
        'temp' => 'temp/'
    ];
    
    $subfolder = isset($subfolders[$type]) ? $subfolders[$type] : $subfolders['general'];
    
    $upload_base = UPLOAD_PATH;
    $upload_dir = $upload_base . $subfolder;
    
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            error_log("Failed to create directory: " . $upload_dir);
            return false;
        }
        chmod($upload_dir, 0777);
    }
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_extensions)) {
        error_log("Invalid file extension: " . $file_ext);
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log("File too large: " . $file['size'] . " bytes");
        return false;
    }
    
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        error_log("Not a valid image file");
        return false;
    }
    
    $unique_name = $type . '_' . time() . '_' . uniqid() . '.' . $file_ext;
    $destination = $upload_dir . $unique_name;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        chmod($destination, 0644);
        
        return $subfolder . $unique_name;
    } else {
        error_log("Failed to move uploaded file to: " . $destination);
        return false;
    }
}

function delete_image($filepath) {
    if (empty($filepath) || $filepath === 'default.jpg') {
        return false;
    }
    
    $full_path = UPLOAD_PATH . $filepath;
    
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    
    return false;
}

function get_image_url($filepath) {
    if (empty($filepath)) {
        return SITE_URL . '/images/default-avatar.jpg';
    }
    
    return SITE_URL . '/uploads/' . $filepath;
}

function image_exists($filepath) {
    if (empty($filepath)) {
        return false;
    }
    
    $full_path = UPLOAD_PATH . $filepath;
    return file_exists($full_path);
}
function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([get_current_user_id()]);
    $user = $stmt->fetch();
    
    return $user && $user['is_admin'] == 1;
}

function require_admin() {
    if (!is_admin()) {
        header("Location: " . SITE_URL . "/index.php");
        exit();
    }
}
?>