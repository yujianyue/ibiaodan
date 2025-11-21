<?php
define('IN_SYSTEM', true);
require_once '../inc/conn.php';
require_once '../inc/pubs.php';
require_once '../inc/sqls.php';

//check_login();

$db = new Database($pdo);
$config = get_config();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $config = [
        'title' => isset($_POST['title']) ? safe_input($_POST['title']) : '',
        'start_time' => isset($_POST['start_time']) ? safe_input($_POST['start_time']) : '',
        'end_time' => isset($_POST['end_time']) ? safe_input($_POST['end_time']) : '',
        'template' => isset($_POST['template']) ? safe_input($_POST['template']) : '',
        'contact' => isset($_POST['contact']) ? safe_input($_POST['contact']) : '',
        'phone' => isset($_POST['phone']) ? safe_input($_POST['phone']) : '',
        'description' => isset($_POST['description']) ? safe_input($_POST['description']) : '',
        'admin_username' => $config['admin_username'],
        'admin_password' => $config['admin_password']
    ];
    
    if (save_config($config)) {
        json_result(1, '保存成功');
    } else {
        json_result(0, '保存失败');
    }
}

$templates = [];
foreach (glob("../moban/*.json") as $file) {
    $templates[] = basename($file, '.json');
}

require_once 'head.php';
?>

<div class="content">
    <h2>系统设置</h2>
    <form id="siteForm" onsubmit="return false;">
        <div class="form-group">
            <label>活动标题：</label>
            <input type="text" name="title" value="<?php echo $config['title'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label>开始时间：</label>
            <input type="datetime-local" name="start_time" value="<?php echo $config['start_time'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label>结束时间：</label>
            <input type="datetime-local" name="end_time" value="<?php echo $config['end_time'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label>选择模板：</label>
            <select name="template" required>
                <?php foreach ($templates as $tpl): ?>
                <option value="<?php echo $tpl; ?>" <?php echo ($config['template'] ?? '') == $tpl ? 'selected' : ''; ?>><?php echo $tpl; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>联系人：</label>
            <input type="text" name="contact" value="<?php echo $config['contact'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label>联系电话：</label>
            <input type="text" name="phone" value="<?php echo $config['phone'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label>活动简介：</label>
            <textarea name="description" maxlength="150" required><?php echo $config['description'] ?? ''; ?></textarea>
        </div>
        <div class="form-group">
            <button type="submit" onclick="saveSite()">保存设置</button>
        </div>
    </form>
</div>

<script src="../inc/admin.js?t=<?php echo CACHE_VERSION; ?>"></script>
<script>
function saveSite() {
    var form = document.getElementById('siteForm');
    var formData = new FormData(form);
    
    ajax({
        url: 'isite.php',
        method: 'POST',
        data: formData,
        success: function(res) {
            alert(res.msg);
        }
    });
}
</script>

<?php require_once 'foot.php'; ?>
