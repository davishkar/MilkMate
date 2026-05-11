<?php
define('APP_NAME', 'MilkMate');
define('APP_URL', 'http://localhost/MilkMate');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

date_default_timezone_set('Asia/Kolkata');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('MILKMATE_SESSION');
    session_start();
}

function sanitize($v) { return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }
function redirect($u) { header("Location: $u"); exit; }
function isLoggedIn() { return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); }
function requireLogin() { if (!isLoggedIn()) redirect(APP_URL . '/auth/login.php'); }
function isAdmin() { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function jsonResponse($data, $code = 200) { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit; }
function formatCurrency($a) { return '₹' . number_format($a, 2); }
function formatDate($d) { return date('d M Y', strtotime($d)); }

function logActivity($action, $module, $desc = '') {
    try {
        $pdo = db();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id,action,module,description,ip_address) VALUES (?,?,?,?,?)");
        $stmt->execute([$_SESSION['user_id'] ?? null, $action, $module, $desc, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}

function getSetting($key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $stmt = db()->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['setting_value'] : $default;
        return $cache[$key];
    } catch(Exception $e) { return $default; }
}

function uploadFile($file, $dir = 'farmers') {
    $uploadDir = UPLOAD_PATH . $dir . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) return ['success'=>false,'message'=>'Invalid file type.'];
    if ($file['size'] > 5*1024*1024) return ['success'=>false,'message'=>'Max 5MB allowed.'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fname = uniqid().'_'.time().'.'.$ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir.$fname))
        return ['success'=>true,'filename'=>$dir.'/'.$fname];
    return ['success'=>false,'message'=>'Upload failed.'];
}
?>
