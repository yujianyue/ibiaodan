<?php
session_start();

$dd = date("YmdHis");

// Database connection
require_once __DIR__ . '/config/database.php';
$db = getDbConnection();

// Get template ID
$template_id = $_GET['tp'] ?? 'demo1';

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

// 读取模板
$template = json_decode(file_get_contents('./moban/' . $template_id . '.json'), true);
$rules = json_decode(file_get_contents('./inc/rule.json'), true);

// AJAX验证处理
if (isset($_POST['action']) && $_POST['action'] == 'validate') {
    $field = $_POST['field'];
    $value = $_POST['value'];
    $pattern = $_POST['pattern'];
    
    if (isset($rules[$pattern])) {
        $result = preg_match($rules[$pattern], $value);
        echo json_encode(['valid' => $result == 1]);
        exit;
    }
}

// 表单提交处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    try {
$now = time();
$start_time = strtotime($config['start_time']);
$end_time = strtotime($config['end_time']);

if ($now < $start_time) {
            exit('该报名尚未开始!'.$config['start_time']);
}
if ($now > $end_time) {
           exit('该报名已结束!'.$config['end_time']);
}
        $db->beginTransaction();
        
        $uniqid = uniqid();
        
        $data = [
            'template_id' => $template_id,
            'username' => $_POST[$template[0][0]['name']],
            'password' => $_POST[$template[0][1]['name']],
            'email' => $_POST[$template[0][2]['name']],
            'submit_date' => date('Y-m-d'),
            'submit_time' => date('H:i:s'),
            'submit_ip' => $_SERVER['REMOTE_ADDR'],
            'uniqid' => $uniqid
        ];
        $template[0][0]['name'];
        $other_fields = $_POST;
        unset($_POST[$template[0][0]['name']], $_POST[$template[0][1]['name']], $_POST[$template[0][2]['name']]);
        $data['idesc'] = json_encode($other_fields);
        
        $sql = "INSERT INTO entries (template_id, username, password, email, idesc, submit_date, submit_time, submit_ip, uniqid) 
                VALUES (:template_id, :username, :password, :email, :idesc, :submit_date, :submit_time, :submit_ip, :uniqid)";
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        
        $entry_id = $db->lastInsertId();
        $db->commit();
        
        header("Location: upload.php?id=$entry_id&uniqid=$uniqid");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>报名系统 - <?php echo $config["title"];?></title>
    <link rel="stylesheet" href="./inc/style.css?v=<?php echo $dd;?>">
  <style>
        .form-description {
        margin-bottom: 2rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 4px;
    }
    h3 { color:red; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h2><?php echo $config["title"];?></h2>
                </div>
                  <div class="form-description">
                    <?php echo $config["description"];?>
                    <p>截止时间:<?php echo $config["end_time"];?></p>                
                    
                    <?php                          
$now = time();
$start_time = strtotime($config['start_time']);
$end_time = strtotime($config['end_time']);
if ($now < $start_time) echo $tips = "<h3>该报名尚未开始({$config["start_time"]})!</h3>";
if ($now > $end_time) echo $tips = "<h3>该报名已结束({$config["end_time"]})!</h3>";
?>
                    
                  </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠</span>
                        <span class="alert-message"><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" id="signupForm" class="animated-form">
                    <?php foreach ($template[0] as $field): ?>
                        <div class="form-group">
                            <label>
                                <?= htmlspecialchars($field['label']) ?>
                                <?php if ($field['required']): ?>
                                    <span class="required" title="必填项">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($field['type'] == 'select'): ?>
                                <div class="select-wrapper">
                                    <select name="<?= $field['name'] ?>" 
                                            <?= $field['required'] ? 'required' : '' ?>>
                                        <?php foreach ($field['options'] as $option): ?>
                                            <option value="<?= $option ?>" 
                                                <?= isset($field['index']) && $field['index'] == $option ? 'selected' : '' ?>>
                                                <?= $option ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            <?php elseif ($field['type'] == 'checkbox'): ?>
                        <div class="checkbox-group">
                            <?php foreach ($field['options'] as $option): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                    name="<?php echo $field['name']; ?>[]" 
                                    value="<?php echo htmlspecialchars($option); ?>"
                                    <?= isset($field['index']) && $field['index'] == $option ? 'checked' : '' ?>>
                                <?php echo htmlspecialchars($option); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>                          
                            <?php elseif ($field['type'] == 'textarea'): ?>
                                <textarea name="<?= $field['name'] ?>" 
                                        <?= $field['required'] ? 'required' : '' ?>
                                        placeholder="请输入<?= $field['label'] ?>"><?= $field['index'] ?? '' ?></textarea>
                            <?php else: ?>
                                <div class="input-wrapper">
                                    <input type="<?= $field['type'] ?>" 
                                           name="<?= $field['name'] ?>" 
                                           value="<?= $field['index'] ?? '' ?>"
                                           <?= $field['required'] ? 'required' : '' ?>
                                           <?= isset($field['pattern']) ? 'data-pattern="'.$field['pattern'].'"' : '' ?>
                                           placeholder="请输入<?= $field['label'] ?>">
                                    <?php if (isset($field['pattern'])): ?>
                                        <span class="validation-icon"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="error-message"></div>
                        </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="tpid" value="<?= $template_id ?>">
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <span class="btn-text">提交</span>
                            <span class="btn-loader"></span>
                        </button><?php echo $tips;?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="./inc/step1.js?v=<?php echo $dd;?>"></script>
</body>
</html>