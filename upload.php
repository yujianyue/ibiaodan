<?php
session_start();
$dd = date("YmdHis");

require_once __DIR__ . '/config/database.php';
$db = getDbConnection();

$id = $_GET['id'] ?? 0;
$uniqid = $_GET['uniqid'] ?? '';

$stmt = $db->prepare("SELECT * FROM entries WHERE id = ? AND uniqid = ?");
$stmt->execute([$id, $uniqid]);
$entry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entry) {
    header('Location: index.php?id='.$id);
}else{
	$stmt = $db->prepare("SELECT * FROM attachments WHERE entry_id = ?");
    $stmt->execute([$id]);
    $has_attachments = false;

        $readme_content = "\n";
    while($attachment = $stmt->fetch()) {
        $readme_content .= json_encode($attachment)."\n";
        $source_file = $attachment['file_path'];
        $readme_content .= "$source_file\n";
        if(!file_exists($source_file)) {
        $readme_content .= "无附件\n";
            continue;
        }
    }
}
    $config_file = './json.php';
    if (file_exists($config_file)) {
        $content = file_get_contents($config_file);
        $content = str_replace('<?php die(); ?>', '', $content);
        $config = json_decode($content, true);
    }else{
    $stime = "".date("Y-m-d H:i:s"); 
    $etime = date("Y-m-d H:i:s", strtotime("+3 day"));  
    $config = [
        'title' => '某某报名系统',
        'start_time' => $stime,
        'end_time' => $etime,
        'template' => $template_id,
        'contact' => '联系人',
        'phone' => '15088888888',
        'description' => '介绍',
        'admin_username' => '前端不需要',
        'admin_password' => '前端不需要'
    ];
    }
$template = json_decode(file_get_contents('./moban/' . $entry['template_id'] . '.json'), true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

$now = time();
$start_time = strtotime($config['start_time']);
$end_time = strtotime($config['end_time']);

if ($now < $start_time) {
            echo json_encode([
                'success' => false, 
                'error' => '该报名尚未开始!'.$config['start_time']
            ]);
            exit;
}
if ($now > $end_time) {
            echo json_encode([
                'success' => false, 
                'error' => '该报名已结束!'.$config['end_time']
            ]);
            exit;
}
    if (isset($_POST['imageData'])) {
        $imageData = $_POST['imageData'];
        $field_name = $_POST['field_name'];
        $field_index = $_POST['field_index'];
        $unid = $id . '_' . $field_name . '_' . $field_index;
        $stmt = $db->prepare("SELECT * FROM attachments WHERE unid = ?");
        $stmt->execute([$unid]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageData = base64_decode($imageData);               
        $dir = "./uploads/{$entry['template_id']}/{$id}";
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $unid . '.jpg';
        $filepath = $dir . '/' . $filename;
        file_put_contents($filepath, $imageData);
        
        if ($existing) {
            $stmt = $db->prepare("UPDATE attachments SET file_path = ? WHERE unid = ?");
            $stmt->execute([$filepath, $unid]);
            if ($existing['file_path'] !== $filepath && file_exists($existing['file_path'])) {
                unlink($existing['file_path']);
            }
        } else {
            $stmt = $db->prepare("INSERT INTO attachments (entry_id, field_name, field_index, file_path, unid) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $field_name, $field_index, $filepath, $unid]);
        }
        
        echo json_encode([
            'success' => true,
            'file_path' => $filepath,
            'unid' => $unid
        ]);
        exit;
    }
    
    if (isset($_FILES['chunk'])) {
        // 处理分片上传
        $field_name = $_POST['field_name'];
        $field_index = $_POST['field_index'];
        $chunk_index = $_POST['chunk_index'];
        $total_chunks = $_POST['total_chunks'];
        $original_name = $_POST['original_name'];
        
        $dir = "./uploads/{$entry['template_id']}/{$id}";
        $temp_dir = "{$dir}/temp";
        
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }
        
        // 保存分片
        $chunk_path = "{$temp_dir}/{$field_name}_{$field_index}_{$chunk_index}";
        move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_path);
        
        // 检查是否所有分片都已上传
        $all_chunks_uploaded = true;
        for ($i = 0; $i < $total_chunks; $i++) {
            if (!file_exists("{$temp_dir}/{$field_name}_{$field_index}_{$i}")) {
                $all_chunks_uploaded = false;
                break;
            }
        }
        
        if ($all_chunks_uploaded) {
            // 合并所有分片
            $ext = pathinfo($original_name, PATHINFO_EXTENSION);
            $final_path = "{$dir}/{$field_name}_{$field_index}.{$ext}";
            $final = fopen($final_path, 'wb');
            
            for ($i = 0; $i < $total_chunks; $i++) {
                $chunk_path = "{$temp_dir}/{$field_name}_{$field_index}_{$i}";
                $chunk = file_get_contents($chunk_path);
                fwrite($final, $chunk);
                unlink($chunk_path); // 删除分片
            }
            
            fclose($final);
            rmdir($temp_dir); // 删除临时目录            
            
            // 生成唯一标识
            $unid = uniqid('att_', true);
            
            // 保存到数据库
            $sql = "INSERT INTO attachments (unid, entry_id, field_name, field_index, file_path, upload_time) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([$unid, $id, $field_name, $field_index, $final_path]);
            
            echo json_encode(['success' => true, 'file_path' => $final_path]);
        } else {
            echo json_encode(['success' => true, 'chunk_received' => true]);
        }
        exit;
    }
    
    if (isset($_POST['complete'])) {
        // 验证必填项
        $required_files = [];
        foreach ($template[1] as $field) {
            if ($field['required']) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM attachments WHERE entry_id = ? AND field_name = ?");
                $stmt->execute([$id, $field['name']]);
                $count = $stmt->fetchColumn();
                
                if ($count < ($field['minFiles'] ?? 1)) {
                    $required_files[] = $field['label'];
                }
            }
        }
        
        if (!empty($required_files)) {
            echo json_encode([
                'success' => false, 
                'error' => '以下文件未上传完成：' . implode(', ', $required_files)
            ]);
            exit;
        }
        
        $db->prepare("UPDATE entries SET status = 1 WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
}
?>

<?php
// Fetch existing attachments
$stmt = $db->prepare("SELECT * FROM attachments WHERE entry_id = ?");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$attachmentsByField = [];
foreach ($attachments as $attachment) {
    $key = $attachment['field_name'] . '_' . $attachment['field_index'];
    $attachmentsByField[$key] = $attachment;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件上传</title>
    <link rel="stylesheet" href="./inc/style.css?v=<?php echo $dd;?>">
</head>
<body>
    <div class="page-wrapper">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h2>文件上传</h2>
                    <p class="form-subtitle">请上传所需文件,然后提交才生效!</p>
                </div>

                <form id="uploadForm">
                    <?php foreach ($template[1] as $field): ?>
                        <div class="upload-group">
                            <h3>
                                <?= htmlspecialchars($field['label']) ?>
                                <?php if ($field['required']): ?>
                                    <span class="required" title="必填项">* </span>
                                <?php endif; ?> <span class="upload-text">
                                        <?php if ($field['type'] == 'imges'): ?>
                                            支持<?= $field['exts'] ?>格式
                                        <?php elseif ($field['type'] == 'idocx'): ?>
                                            支持<?= $field['exts'] ?>格式
                                        <?php elseif ($field['type'] == 'zfile'): ?>
                                            支持<?= $field['exts'] ?>格式
                                        <?php endif; ?></span>
                            </h3>
                            
                            <?php for ($i = 1; $i <= $field['maxFiles']; $i++): ?>
                                <?php 
                                    $attachmentKey = $field['name'] . '_' . $i;
                                    $existingAttachment = $attachmentsByField[$attachmentKey] ?? null;
                                ?>
                                <div class="upload-zone" 
                                     data-field="<?= $field['name'] ?>" 
                                     data-index="<?= $i ?>"
                                     data-exts="<?= $field['exts'] ?>"
                                     data-type="<?= $field['type'] ?>"
                                     <?php if ($existingAttachment): ?>
                                     data-unid="<?= $existingAttachment['unid'] ?>"
                                     <?php endif; ?>>
                                    <div class="upload-text">
                                        点击或拖拽文件到此处上传
                                    </div>
                                    <input type="file" class="file-input" accept="<?= $field['exts'] ?>" style="display: none;">
                                    <div class="file-preview">
                                        <?php if ($existingAttachment): ?>
                                            <span class="required">已传</span>
                                            <div class="preview-info">
                                                <div class="preview-name"><?= basename($existingAttachment['file_path']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="progress-bar"><div class="progress"></div></div>
                                    <div class="error-message"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="form-actions">
                        <button type="button" id="complete" class="btn-submit">
                            <span class="btn-text">完成提交</span>
                            <span class="btn-loader"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="./inc/step2.js?v=<?php echo $dd;?>"></script>
</body>
</html> 