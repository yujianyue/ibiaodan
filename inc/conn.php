<?php
// 雪里开PHP简易表单系统 V1.0
if(!defined('IN_SYSTEM')) {
    exit('Access Denied');
}

// 引入数据库配置
require_once dirname(__DIR__) . '/config/database.php';

// 缓存版本
define('CACHE_VERSION', '1.1.'.date("YmdHis")); // JS/CSS cache version

// 获取数据库连接
$pdo = getDbConnection();
