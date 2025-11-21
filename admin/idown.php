<?php
define('IN_SYSTEM', true);
require_once '../inc/conn.php';
require_once '../inc/pubs.php';
require_once '../inc/sqls.php';

check_login();

$db = new Database($pdo);

// 处理AJAX请求
if ($_GET['act'] == 'down') {
  if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $sqe=" WHERE `id`=$id "; 
    $sqo=" WHERE e.`id`=$id "; 
    $fso="id@$id";
  }elseif( isset($_GET['pi']) ){
    $pi = $_GET['pi'];
    $pi = preg_replace('/[^a-zA-Z0-9_]+/u', '', $pi);
    $sqe=" WHERE `template_id`='$pi' "; 
    $sqo=" WHERE e.`template_id`='$pi' "; 
    $fso="tp@$pi";
  }else{
    $sqe=""; $sqo=""; $fso="all";
  }
    $sql="SELECT * FROM `entries` {$sqe} ORDER BY `id` DESC";
    $stmt = $pdo->query($sql);
    $entries = $stmt->fetchAll();
    
    if (empty($entries)) {
        json_result(0, '没有数据可导出');
    }
    
    // Get all unique fields from idesc JSON
    $fields = ['id', 'template_id', 'username', 'password', 'email', 'submit_date', 'submit_time', 'submit_ip', 'status'];
    foreach ($entries as $entry) {
        $idesc = json_decode($entry['idesc'], true);
        if ($idesc) {
            foreach ($idesc as $key => $value) {
                if (!in_array($key, $fields)) {
                    $fields[] = $key;
                }
            }
        }
    }
    $export_dir = '../exports';
    $filename = 'Down_' . $fso .'@'. date('YmdHis') . '.csv';
    $filepath = $export_dir . '/' . $filename;
    if (!file_exists($export_dir)) {
        mkdir($export_dir, 0755, True);
    }    
    $file = fopen($filepath, 'w');
    $file1 = fopen($filepath . ".txt", 'w');
      fputcsv($file1, $fields, "\t");
      fputcsv($file, $fields, ",");
    foreach ($entries as $entry) {
        $row = [];
        $idesc = json_decode($entry['idesc'], true);
        
        foreach ($fields as $field) {
            if (isset($entry[$field])) {
                $row[] = $entry[$field];
            } elseif (is_array($idesc[$field])) {
              $row[] = join("|",$idesc[$field]);
            } elseif (isset($idesc[$field])) {
                $row[] = $idesc[$field];
            } else {
                $row[] = '-';
            }
        }
      fputcsv($file1, $row, "\t");
      fputcsv($file, $row, ",");
    }

    // Create zip file
    $zip = new ZipArchive();
    $zipname = $filename . '.zip';
    $zippath = $export_dir . '/' . $zipname;
    
    if ($zip->open($zippath, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($filepath, $filename);
        $zip->addFile($filepath.".txt", $filename.".txt");
        $sqly = "SELECT e.template_id, e.id, e.username, a.* FROM `attachments` a JOIN `entries` e ON a.entry_id = e.id {$sqo} ORDER BY e.id ASC";
        $stmt = $pdo->query($sqly);
        $readme_content = "文件清单：$sqly\n\n";
        $readme_content .= "export_" . date('YmdHis') . ".csv - 数据文件\n";
        while ($row = $stmt->fetch()) {
                $readme_content .= "\n" . $row['field_name'] . $row['field_index'] . "：\n";
                $readme_content .= "原始路径：" . $row['file_path'] . "\n";
            if (file_exists("../".$row['file_path'])) {
                $newname = $row['template_id'] . '/' . 
                          $row['entry_id'] . '_' . 
                          preg_replace('/[^a-zA-Z0-9_\x{4e00}-\x{9fa5}]+/u', '', $row['username']) . '/' .
                          $row['field_name'] . $row['field_index'] . '.' . 
                          pathinfo($row['file_path'], PATHINFO_EXTENSION);
                $zip->addFile("../".$row['file_path'], $newname);
                $readme_content .= "新路径：" . $newname . "\n";
            }else{
                $readme_content .= "------路径错误/失效-----\n";
            }
        }
        //$zip->addFromString('readme.txt', $readme_content);
      $zip->addFromString($filepath.'.Bom.txt', chr(0xEF) . chr(0xBB) . chr(0xBF) . "");        
      $zip->addFromString($filepath.'.Bom.csv', chr(0xEF) . chr(0xBB) . chr(0xBF) . "");        
      $zip->addFromString('readme.txt', ".csv文件:excel直接打开,时间/手机号等长数字单元格宽度拉大后保持为xlsx文件。\r\n .txt文件:excel空白sheet表，全选，右键单元格格式设置为文本后，点第一行第一格粘贴.txt文件内容");
        $zip->close();
        
        // Delete CSV file
        unlink($filepath);
        
        json_result(1, '导出成功', ['url' => '../exports/' . $zipname]);
    } else {
        json_result(0, '创建ZIP文件失败');
    }
}


require_once 'head.php';
?>

<div class="content">
    <h2>统计导出</h2>
    <div class="export-box">
        <button onclick="exportData()">开始导出</button>
        <div id="exportResult" style="display:none;">
            <p>导出成功！<a href="#" id="downloadLink" target="_blank">点击下载</a></p>
        </div>
    </div>
</div>

<script src="../inc/admin.js?t=<?php echo CACHE_VERSION; ?>"></script>
<script>
function exportData() {
    ajax({
        url: '?act=down',
        method: 'POST',
        data: {},
        success: function(res) {
            if (res.code == 1) {
                document.getElementById('exportResult').style.display = 'block';
                document.getElementById('downloadLink').href = res.data.url;
            } else {
                alert(res.msg);
            }
        }
    });
}
</script>

<?php require_once 'foot.php'; ?>