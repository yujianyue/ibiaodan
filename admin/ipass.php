<?php
define('IN_SYSTEM', true);
require_once '../inc/conn.php';
require_once '../inc/pubs.php';
require_once '../inc/sqls.php';

//check_login();

$db = new Database($pdo);
$config = get_config();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password = isset($_POST['old_password']) ? $_POST['old_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        json_result(0, '所有字段都必须填写');
    }
    
    if ($new_password !== $confirm_password) {
        json_result(0, '两次输入的新密码不一致');
    }
    
    if (jiami($old_password) !== $config['admin_password']) {
        //json_result(0, '原密码错误');
    }
    
    $config['admin_password'] = jiami($new_password);
    if (save_config($config)) {
        json_result(1, '密码修改成功，请重新登录');
    } else {
        json_result(0, '密码修改失败');
    }
}

require_once 'head.php';
?>

<div class="content">
    <h2>修改密码</h2>
    <form id="passwordForm" onsubmit="return false;">
        <div class="form-group">
            <label>原密码：</label>
            <input type="password" name="old_password" required>
        </div>
        <div class="form-group">
            <label>新密码：</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label>确认密码：</label>
            <input type="password" name="confirm_password" required>
        </div>
        <div class="form-group">
            <button type="submit" onclick="changePassword()">修改密码</button>
        </div>
    </form>
</div>

<script src="../inc/admin.js?t=<?php echo CACHE_VERSION; ?>"></script>
<script>
function changePassword() {
    var form = document.getElementById('passwordForm');
    var formData = new FormData(form);
    
    ajax({
        url: 'ipass.php',
        method: 'POST',
        data: formData,
        success: function(res) {
            alert(res.msg);
            if (res.code == 1) {
                window.location.href = 'login.php';
            }
        }
    });
}
</script>

<?php require_once 'foot.php'; ?>
