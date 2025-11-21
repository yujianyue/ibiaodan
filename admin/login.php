<?php
define('IN_SYSTEM', true);
require_once '../inc/conn.php';
require_once '../inc/pubs.php';

session_start();

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['admin_logged_in']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? safe_input($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $config = get_config();
    if (!$config) {
        json_result(0, '系统未初始化');
    }
    
    if ($username == $config['admin_username'] && jiami($password) == $config['admin_password']) {
        $_SESSION['admin_logged_in'] = true;
        json_result(1, '登录成功');
    } else {
        json_result(0, '用户名或密码错误');
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理登录</title>
    <link rel="stylesheet" href="../inc/css.css?t=<?php echo CACHE_VERSION; ?>">
</head>
<body class="login-page">
    <div class="login-box">
        <h2>管理登录</h2>
        <form id="loginForm" onsubmit="return false;">
            <div class="form-group">
                <input type="text" name="username" placeholder="用户名" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码" required>
            </div>
            <div class="form-group">
                <button type="submit" onclick="doLogin()">登录</button>
            </div>
        </form>
    </div>
    <script src="../inc/js.js?t=<?php echo CACHE_VERSION; ?>"></script>
    <script>
    function doLogin() {
        var form = document.getElementById('loginForm');
        var formData = new FormData(form);
        
        ajax({
            url: 'login.php',
            method: 'POST',
            data: formData,
            success: function(res) {
                if (res.code == 1) {
                    window.location.href = 'isite.php';
                } else {
                    alert(res.msg);
                }
            }
        });
    }
    </script>
</body>
</html>
