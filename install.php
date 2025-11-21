<?php
define('IN_SYSTEM', true);
require_once __DIR__ . '/config/database.php';

// 检查是否已安装
$isInstalled = false;
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SHOW TABLES LIKE 'entries'");
    $isInstalled = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    // Database might not exist yet
}

// 处理安装请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $installDemo = isset($_POST['install_demo']) && $_POST['install_demo'] == '1';
    
    try {
        // 创建数据库
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // 创建表
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `entries` (
                `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '主键ID',
                `template_id` VARCHAR(50) NOT NULL COMMENT '模板ID',
                `username` VARCHAR(100) NOT NULL COMMENT '用户名',
                `password` VARCHAR(100) NOT NULL COMMENT '密码',
                `email` VARCHAR(150) NOT NULL COMMENT '邮箱',
                `idesc` TEXT NOT NULL COMMENT '表单数据(JSON格式)',
                `submit_date` DATE NOT NULL COMMENT '提交日期',
                `submit_time` TIME NOT NULL COMMENT '提交时间',
                `submit_ip` VARCHAR(45) NOT NULL COMMENT '提交IP',
                `uniqid` VARCHAR(32) NOT NULL COMMENT '唯一标识',
                `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态：0=待处理，1=已处理',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                INDEX `idx_template` (`template_id`) COMMENT '模板索引',
                INDEX `idx_username` (`username`) COMMENT '用户名索引',
                INDEX `idx_email` (`email`) COMMENT '邮箱索引'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `attachments` (
                `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '主键ID',
                `unid` VARCHAR(100) NOT NULL COMMENT '附件唯一标识',
                `entry_id` INT UNSIGNED NOT NULL COMMENT '关联的报名ID',
                `field_name` VARCHAR(50) NOT NULL COMMENT '字段名称',
                `field_index` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '字段索引',
                `file_path` VARCHAR(255) NOT NULL COMMENT '文件路径',
                `file_size` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
                `file_type` VARCHAR(50) DEFAULT NULL COMMENT '文件类型',
                `upload_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                FOREIGN KEY (`entry_id`) REFERENCES `entries`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX `idx_entry_field` (`entry_id`, `field_name`, `field_index`) COMMENT '报名字段索引',
                UNIQUE INDEX `idx_unid` (`unid`) COMMENT '唯一标识索引'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 创建初始配置
        $config = [
            'title' => '演示活动',
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => date('Y-m-d H:i:s', strtotime('+14 days')),
            'template' => 'demo1',
            'contact' => '管理员',
            'phone' => '13800138000',
            'description' => '这是一个演示活动，用于测试系统功能。',
            'admin_username' => 'admin',
            'admin_password' => md5('admin123' . 'form_salt_2025')
        ];
        
        file_put_contents(__DIR__ . '/json.php', '<?php die(); ?>' . json_encode($config));
        
        // 创建必要的目录
        $dirs = ['uploads', 'exports', 'moban'];
        foreach ($dirs as $dir) {
            if (!file_exists(__DIR__ . '/' . $dir)) {
                mkdir(__DIR__ . '/' . $dir, 0777, true);
            }
        }
        
        // 安装演示数据
        if ($installDemo) {
            // 模拟不同模板
            $templates = ['demo1', 'demo2', 'demo3'];
            // 模拟不同状态
            $statuses = [0, 1];
            // 模拟不同字段类型
            $fieldTypes = ['files', 'images', 'videos'];
            
            // 生成24条记录
            for ($i = 1; $i <= 24; $i++) {
                $template = $templates[array_rand($templates)];
                $status = $statuses[array_rand($statuses)];
                $date = date('Y-m-d', strtotime("-" . rand(1, 10) . " days"));
                $time = date('H:i:s', strtotime("-" . rand(1, 14) . " hours"));
                
                // 自定义字段
                $idesc = [
                    'company' => '测试公司' . $i,
                    'position' => '职位' . $i,
                    'experience' => rand(1, 10) . '年',
                    'education' => ['本科', '硕士', '博士'][rand(0, 2)],
                    'skills' => 'Skill ' . $i,
                    'introduction' => '这是第' . $i . '条演示数据的介绍内容。'
                ];
                
                // 插入主记录
                $stmt = $pdo->prepare("
                    INSERT INTO entries (
                        template_id, username, password, email, idesc, 
                        submit_date, submit_time, submit_ip, uniqid, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $template,
                    '用户' . $i,
                    'password' . $i,
                    'user' . $i . '@example.com',
                    json_encode($idesc),
                    $date,
                    $time,
                    long2ip(rand(0, 4294967295)),
                    md5(uniqid(mt_rand(), true)),
                    $status
                ]);
                
                $entryId = $pdo->lastInsertId();
                
                // 为每种字段类型创建2个附件记录
                foreach ($fieldTypes as $fieldType) {
                    for ($j = 1; $j <= 2; $j++) {
                        $filePath = 'uploads/' . $template . '/' . $entryId . '/' . $fieldType . $j . '.demo';
                        
                        // 确保目录存在
                        $uploadDir = __DIR__ . '/uploads/' . $template . '/' . $entryId;
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        // 创建演示文件
                        file_put_contents(__DIR__ . '/' . $filePath, 'Demo file content for ' . $fieldType . $j);
                        
                        // 插入附件记录
                        $stmt = $pdo->prepare("
                            INSERT INTO attachments (
                                entry_id, unid, field_name, field_index, file_path, upload_time
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $unid = "{$entryId}_{$fieldType}_{$j}";
                        $stmt->execute([
                            $entryId,
                            $unid,
                            $fieldType,
                            $j,
                            $filePath,
                            date('Y-m-d H:i:s', strtotime($date . ' ' . $time))
                        ]);
                    }
                }
            }
        }
        
        $message = $installDemo ? '系统安装成功，已导入演示数据！' : '系统安装成功！';
        $error = '';
    } catch (Exception $e) {
        $message = '';
        $error = '安装失败：' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装</title>
    <link rel="stylesheet" href="inc/css.css">
    <style>
        .install-box {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .result {
            margin: 15px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background: #f6ffed;
            border: 1px solid #b7eb8f;
            color: #52c41a;
        }
        .error {
            background: #fff2f0;
            border: 1px solid #ffccc7;
            color: #ff4d4f;
        }
    </style>
</head>
<body class="login-page">
    <div class="install-box">
        <h2>系统安装</h2>
        <?php if ($isInstalled): ?>
        <div class="result">系统已安装！如需重新安装，请删除数据库文件后刷新页面。</div>
        <p><a href="admin/login.php">进入管理后台</a></p>
        <?php else: ?>
            <?php if (isset($message) && $message): ?>
            <div class="result success"><?php echo $message; ?></div>
            <p><a href="admin/login.php">进入管理后台</a></p>
            <?php elseif (isset($error) && $error): ?>
            <div class="result error"><?php echo $error; ?></div>
            <p><a href="javascript:history.back()">返回重试</a></p>
            <?php else: ?>
            <form method="post" onsubmit="return confirm('确定要安装系统吗？');">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="install_demo" value="1" checked>
                        安装演示数据（24条记录，每条包含6个附件）
                    </label>
                </div>
                <div class="form-group">
                    <button type="submit">开始安装</button>
                </div>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
