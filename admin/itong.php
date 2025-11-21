<?php
define('IN_SYSTEM', true);
require_once '../inc/conn.php';
require_once '../inc/pubs.php';
require_once '../inc/sqls.php';

check_login();

$db = new Database($pdo);

// 获取所有模板的统计信息
$sql = "SELECT 
            `template_id`,
            COUNT(*) as total_entries,
            MIN(CONCAT(`submit_date`, ' ', `submit_time`)) as first_submit,
            MAX(CONCAT(`submit_date`, ' ', `submit_time`)) as last_submit
        FROM `entries` 
        GROUP BY `template_id` 
        ORDER BY `template_id` ASC";

$stmt = $pdo->query($sql);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'head.php';
?>

<div class="content">
    <div class="page-header">
        <h2>模板统计</h2>
    </div>

        <div class="templates-list">
            <h3>所有模板统计</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>模板ID</th>
                        <th>报名人数</th>
                        <th>最早提交</th>
                        <th>最后提交</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($templates as $template): ?>
                    <tr>
                        <td>模板<?php echo $template['template_id']; ?></td>
                        <td><?php echo $template['total_entries']; ?></td>
                        <td><?php echo $template['first_submit']; ?></td>
                        <td><?php echo $template['last_submit']; ?></td>
                        <td>
                          <button onclick="exportData('<?php echo $template['template_id']; ?>')">下载</button>
        <div id="exportResult<?php echo $template['template_id']; ?>" style="display:none;">
            <p><a href="#" id="downloadLink<?php echo $template['template_id']; ?>" target="_blank">点击下载</a></p>
        </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.content {
    padding: 20px;
}
.page-header {
    margin-bottom: 20px;
}
.template-select {
    margin-bottom: 30px;
}
.template-select select {
    padding: 8px;
    font-size: 16px;
    min-width: 200px;
}
.template-info {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}
.info-table {
    width: 100%;
    margin: 15px 0;
}
.info-table th {
    text-align: right;
    padding: 8px;
    width: 150px;
}
.info-table td {
    padding: 8px;
}
.template-actions {
    margin-top: 20px;
    text-align: center;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
.data-table th,
.data-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}
.data-table th {
    background-color: #f5f5f5;
}
.button {
    display: inline-block;
    padding: 5px 15px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 3px;
}
.button:hover {
    background-color: #45a049;
}
</style>

<script>
function exportData(pi) {
    ajax({
        url: 'idown.php?act=down&pi='+pi,
        method: 'POST',
        data: {},
        success: function(res) {
            if (res.code == 1) {
                document.getElementById('exportResult'+pi).style.display = 'block';
                document.getElementById('downloadLink'+pi).href = res.data.url;
            } else {
                alert(res.msg);
            }
        }
    });
}
</script>

<?php require_once 'foot.php'; ?>