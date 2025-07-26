<?php
// 1. 在项目入口文件引入
require_once 'config.php';

// 2. 获取配置值
$appName = Config::get('app.name');
$dbHost = config('database.connections.mysql.host');

// 3. 获取环境变量
$customVar = env('CUSTOM_VAR', 'default');

// 4. 数据库连接
$db = Database::getConnection();