<?php check_login(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理系统</title>
    <link rel="stylesheet" href="../inc/css.css?t=<?php echo CACHE_VERSION; ?>">
<script src="../inc/js.js?t=<?php echo CACHE_VERSION; ?>"></script>
</head>
<body>
    <div class="header">
        <div class="logo">表单管理系统</div>
        <div class="nav">
            <ul>
                <li><a href="isite.php">系统设置</a></li>
                <li><a href="ilist.php">数据列表</a></li>
                <li class="dropdown">
                    <a href="javascript:;">导出功能</a>
                    <ul>
                        <li><a href="idown.php">批量导出</a></li>
                        <li><a href="itong.php">分类统计</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="javascript:;">账户管理</a>
                    <ul>
                        <li><a href="ipass.php">修改密码</a></li>
                        <li><a href="login.php?action=logout">退出登录</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="container">
