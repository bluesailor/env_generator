<?php
/**
 * 配置文件示例 - 演示如何在 PHP 项目中使用 .env 文件
 * 
 * 使用方法：
 * 1. 确保项目根目录存在 .env 文件
 * 2. 在其他文件中引入此配置文件：require_once 'config.php';
 * 3. 使用全局配置：echo Config::get('app.name');
 */

// 防止直接访问
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

/**
 * 简单的 .env 文件解析器
 */
class DotEnv
{
    protected $path;
    protected $variables = [];

    public function __construct($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    /**
     * 加载 .env 文件
     */
    public function load()
    {
        if (!is_readable($this->path)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // 跳过注释行
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // 解析键值对
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // 移除引号
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                    $value = $matches[1];
                }

                // 处理转义字符
                $value = str_replace('\n', "\n", $value);
                $value = str_replace('\r', "\r", $value);

                $this->variables[$name] = $value;

                // 设置环境变量（可选）
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    /**
     * 获取环境变量
     */
    public function get($key, $default = null)
    {
        return isset($this->variables[$key]) ? $this->variables[$key] : $default;
    }

    /**
     * 获取所有变量
     */
    public function all()
    {
        return $this->variables;
    }
}

/**
 * 配置管理类
 */
class Config
{
    private static $instance = null;
    private static $config = [];
    private static $env = null;

    /**
     * 初始化配置
     */
    public static function init($envFile = '.env')
    {
        if (self::$instance === null) {
            self::$instance = new self();
            
            // 加载 .env 文件
            $envPath = ROOT_PATH . '/' . $envFile;
            if (file_exists($envPath)) {
                self::$env = new DotEnv($envPath);
                self::$env->load();
                
                // 构建配置数组
                self::buildConfig();
            }
        }
        
        return self::$instance;
    }

    /**
     * 构建配置数组
     */
    private static function buildConfig()
    {
        // 应用配置
        self::$config['app'] = [
            'name' => self::env('APP_NAME', 'My Application'),
            'env' => self::env('APP_ENV', 'production'),
            'debug' => self::env('APP_DEBUG', 'false') === 'true',
            'url' => self::env('APP_URL', 'http://localhost'),
            'timezone' => self::env('APP_TIMEZONE', 'UTC'),
        ];

        // 数据库配置
        self::$config['database'] = [
            'default' => self::env('DB_CONNECTION', 'mysql'),
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => self::env('DB_HOST', 'localhost'),
                    'port' => self::env('DB_PORT', '3306'),
                    'database' => self::env('DB_DATABASE', 'test'),
                    'username' => self::env('DB_USERNAME', 'root'),
                    'password' => self::env('DB_PASSWORD', ''),
                    'charset' => self::env('DB_CHARSET', 'utf8mb4'),
                    'collation' => self::env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                    'prefix' => self::env('DB_PREFIX', ''),
                ],
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => self::env('DB_DATABASE', ROOT_PATH . '/database.sqlite'),
                ],
            ],
        ];

        // 缓存配置
        self::$config['cache'] = [
            'default' => self::env('CACHE_DRIVER', 'file'),
            'stores' => [
                'file' => [
                    'driver' => 'file',
                    'path' => ROOT_PATH . '/cache',
                ],
                'redis' => [
                    'driver' => 'redis',
                    'host' => self::env('REDIS_HOST', '127.0.0.1'),
                    'port' => self::env('REDIS_PORT', 6379),
                    'password' => self::env('REDIS_PASSWORD', null),
                ],
            ],
        ];

        // 日志配置
        self::$config['logging'] = [
            'default' => self::env('LOG_CHANNEL', 'single'),
            'channels' => [
                'single' => [
                    'driver' => 'single',
                    'path' => ROOT_PATH . '/logs/app.log',
                    'level' => self::env('LOG_LEVEL', 'debug'),
                ],
            ],
        ];
    }

    /**
     * 获取环境变量
     */
    public static function env($key, $default = null)
    {
        // 优先级：$_ENV > $_SERVER > getenv() > .env文件 > 默认值
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        if (self::$env !== null) {
            return self::$env->get($key, $default);
        }
        
        return $default;
    }

    /**
     * 获取配置值
     * 
     * @param string $key 使用点号分隔的配置键，如 'database.connections.mysql.host'
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (self::$instance === null) {
            self::init();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * 设置配置值（仅运行时有效）
     */
    public static function set($key, $value)
    {
        if (self::$instance === null) {
            self::init();
        }

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }
    }

    /**
     * 获取所有配置
     */
    public static function all()
    {
        if (self::$instance === null) {
            self::init();
        }
        
        return self::$config;
    }
}

/**
 * 数据库连接类示例
 */
class Database
{
    private static $connection = null;

    /**
     * 获取数据库连接
     */
    public static function getConnection()
    {
        if (self::$connection === null) {
            $config = Config::get('database.connections.' . Config::get('database.default'));
            
            try {
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['driver'],
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset']
                );

                self::$connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config['charset']
                    ]
                );
            } catch (PDOException $e) {
                if (Config::get('app.debug')) {
                    throw $e;
                } else {
                    die('Database connection failed');
                }
            }
        }

        return self::$connection;
    }
}

/**
 * 辅助函数
 */

/**
 * 获取配置值
 */
function config($key, $default = null)
{
    return Config::get($key, $default);
}

/**
 * 获取环境变量
 */
function env($key, $default = null)
{
    return Config::env($key, $default);
}

/**
 * 调试输出（仅在调试模式下）
 */
function debug($data, $die = false)
{
    if (Config::get('app.debug')) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($die) {
            die();
        }
    }
}

// ========================================
// 使用示例
// ========================================

// 初始化配置（可以指定不同的 .env 文件）
// Config::init('.env.local');
Config::init();

// 使用示例 1：获取配置
/*
$appName = Config::get('app.name');
$dbHost = Config::get('database.connections.mysql.host');
$isDebug = Config::get('app.debug');

echo "应用名称: " . $appName . PHP_EOL;
echo "数据库主机: " . $dbHost . PHP_EOL;
echo "调试模式: " . ($isDebug ? '开启' : '关闭') . PHP_EOL;
*/

// 使用示例 2：数据库连接
/*
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "MySQL 版本: " . $result['version'] . PHP_EOL;
} catch (Exception $e) {
    echo "数据库连接失败: " . $e->getMessage() . PHP_EOL;
}
*/

// 使用示例 3：使用辅助函数
/*
$appUrl = config('app.url');
$dbPrefix = env('DB_PREFIX', 'wp_');

debug(['app_url' => $appUrl, 'db_prefix' => $dbPrefix]);
*/

// 使用示例 4：在其他文件中使用
/*
// 在 index.php 或其他文件中
require_once 'config.php';

// 现在可以在任何地方使用配置
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 设置时区
date_default_timezone_set(config('app.timezone', 'Asia/Shanghai'));
*/

// 使用示例 5：动态修改配置（仅当前请求有效）
/*
Config::set('app.name', '新的应用名称');
Config::set('custom.key', 'custom value');
echo config('custom.key'); // 输出: custom value
*/