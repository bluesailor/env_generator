# .env 文件生成器

一个简单易用的 PHP 工具，用于生成和管理项目的环境配置文件（.env）。

## 功能特性

- 🔧 支持生成多种环境配置文件（.env、.env.local、.env.production 等）
- 🎨 友好的 Web 界面，使用 Tailwind CSS 设计
- 🔒 内置安全防护（路径穿越防护、XSS 防护）
- 👁️ 实时预览生成的文件内容
- 📝 预设常用配置字段
- ✅ 操作结果即时反馈

## 系统要求

- PHP 7.0 或更高版本
- Web 服务器（Apache、Nginx 等）
- 目标目录需要有写入权限

## 安装使用

### 1. 基础安装

将 `env-generator.php`（或您的文件名）放置到 Web 服务器目录中：

```bash
# 复制文件到 web 目录
cp env-generator.php /var/www/html/tools/

# 设置目录权限（确保 PHP 可以写入 .env 文件）
chmod 755 /var/www/html/tools/
```

### 2. 访问使用

通过浏览器访问：
```
http://your-domain.com/tools/env-generator.php
```

### 3. 填写配置

在表单中填写所需的环境变量值，然后点击"生成 .env 文件"按钮。

## 配置字段说明

| 字段名 | 说明 | 默认值 | 示例 |
|--------|------|--------|------|
| APP_NAME | 应用名称 | MyApp | Laravel App |
| APP_ENV | 运行环境 | local | local/testing/production |
| APP_DEBUG | 调试模式 | true | true/false |
| APP_URL | 应用 URL | http://localhost | https://example.com |
| DB_CONNECTION | 数据库类型 | mysql | mysql/pgsql/sqlite |
| DB_HOST | 数据库主机 | localhost | 127.0.0.1 |
| DB_PORT | 数据库端口 | 3306 | 3306/5432 |
| DB_DATABASE | 数据库名 | test | my_database |
| DB_USERNAME | 数据库用户名 | root | db_user |
| DB_PASSWORD | 数据库密码 | 123456 | strong_password |
| DB_CHARSET | 字符集 | utf8mb4 | utf8mb4 |
| DB_COLLATION | 排序规则 | utf8mb4_unicode_ci | utf8mb4_unicode_ci |
| DB_PREFIX | 表前缀 | (空) | wp_ |

## 文件命名规范

支持以下格式的文件名：
- `.env` - 默认配置文件
- `.env.local` - 本地开发环境
- `.env.testing` - 测试环境
- `.env.staging` - 预发布环境
- `.env.production` - 生产环境
- `.env.custom` - 自定义环境（支持任意后缀）

## 安全建议

### ⚠️ 重要安全提示

1. **不要在生产环境直接使用**
   - 此工具应仅在开发环境或受保护的内部环境中使用
   - 生产环境的 .env 文件应通过安全的部署流程管理

2. **添加访问控制**
   ```php
   // 在文件开头添加简单的密码保护
   $password = 'your_secure_password';
   if (!isset($_POST['access_key']) || $_POST['access_key'] !== $password) {
       die('Access denied');
   }
   ```

3. **限制文件生成目录**
   ```php
   // 将生成的文件限制在特定目录
   $allowed_dir = '/path/to/safe/directory/';
   ```

4. **使用 HTTPS**
   - 确保通过 HTTPS 访问此工具，避免敏感配置信息在传输中泄露

5. **定期清理**
   - 使用完毕后删除或移动此工具文件
   - 不要将此工具提交到版本控制系统

## 在项目中使用生成的 .env 文件

### Laravel 项目

```php
// Laravel 会自动加载根目录的 .env 文件
// 在代码中使用：
$appName = env('APP_NAME', 'Laravel');
$debug = env('APP_DEBUG', false);
```

### 原生 PHP 项目

参见提供的 `config.php` 示例文件。

### 其他框架

大多数现代 PHP 框架都支持 .env 文件：
- Symfony: 使用 DotEnv 组件
- Slim: 配合 vlucas/phpdotenv
- CodeIgniter 4: 内置支持

## 故障排除

### 常见问题

1. **"当前目录不可写入"错误**
   ```bash
   # 检查并修改目录权限
   chmod 755 /path/to/directory
   # 或者更改目录所有者
   chown www-data:www-data /path/to/directory
   ```

2. **生成的文件找不到**
   - 检查文件是否生成在 PHP 文件所在的同一目录
   - 确认文件名是否正确（注意 .env 是隐藏文件）

3. **特殊字符显示错误**
   - 工具会自动转义特殊字符
   - 如果仍有问题，避免在配置值中使用引号和反斜杠

## 扩展开发

### 添加自定义字段

修改 `$fields` 数组添加新字段：

```php
$fields = [
    // 原有字段...
    'REDIS_HOST' => '127.0.0.1',
    'REDIS_PORT' => '6379',
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => '587',
];
```

### 添加字段验证

```php
// 在处理 POST 请求时添加验证
if ($_POST['DB_PORT'] && !is_numeric($_POST['DB_PORT'])) {
    $message = "❌ 数据库端口必须是数字";
}
```

### 集成到现有系统

```php
// 将核心功能封装为函数
function generateEnvFile($filename, $config) {
    $env_lines = [];
    foreach ($config as $key => $value) {
        $escaped = addcslashes($value, "\n\r\"\\");
        $env_lines[] = "{$key}=\"{$escaped}\"";
    }
    
    $content = implode(PHP_EOL, $env_lines);
    return file_put_contents($filename, $content);
}
```

## 版本历史

- v1.0.0 - 初始版本
  - 基础 .env 文件生成功能
  - Web 界面
  - 安全防护措施

## 许可证

此工具仅供内部使用，请勿在未经授权的情况下分发。

## 贡献

欢迎提交问题报告和改进建议。

---

**免责声明**：此工具生成的配置文件可能包含敏感信息，请妥善保管并避免泄露。作者不对因使用此工具造成的任何安全问题负责。