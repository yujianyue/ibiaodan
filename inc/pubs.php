<?php
if(!defined('IN_SYSTEM')) {
    exit('Access Denied');
}

// JSON response function
function json_result($code, $msg = '', $data = null) {
    $result = ['code' => $code, 'msg' => $msg];
    if ($data !== null) {
        $result['data'] = $data;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit;
}

// Password encryption
function jiami($str) {
    return md5($str . 'form_salt_2025'); // Custom salt
}

// XSS prevention
function safe_html($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Input sanitization
function safe_input($str) {
    return trim(strip_tags($str));
}

// Check login status
function check_login() {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            json_result(403, '请先登录');
        } else {
            header('Location: login.php');
            exit;
        }
    }
    return true;
}

// Get configuration
function get_config() {
    $config_file = dirname(__DIR__) . '/json.php';
    if (file_exists($config_file)) {
        $content = file_get_contents($config_file);
        $content = str_replace('<?php die(); ?>', '', $content);
        return json_decode($content, true);
    }
    return false;
}

// Save configuration
function save_config($config) {
    $config_file = dirname(__DIR__) . '/json.php';
    $content = '<?php die(); ?>' . json_encode($config);
    return file_put_contents($config_file, $content);
}

// Generate unique ID
function generate_uniqid() {
    return md5(uniqid(mt_rand(), true));
}

// Format date time
function format_datetime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}
