<?php
$message = "";
$preview = "";

// 预定义字段及默认值
$fields = [
    'APP_NAME' => 'MyApp',
    'APP_ENV' => 'local',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'http://localhost',
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_DATABASE' => 'test',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '123456',
    'DB_CHARSET' => 'utf8mb4',
    'DB_COLLATION' => 'utf8mb4_unicode_ci',
    'DB_PREFIX' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename_input = $_POST['env_filename'] ?? '.env';
    $filename = basename($filename_input); // 防止路径穿越

    // 允许格式：.env 或 .env.xxx
    if (!preg_match('/^\.env(\.[\w\-]+)?$/', $filename)) {
        $message = "❌ 文件名无效，只允许形如 .env、.env.production 的格式";
    } else {
        $env_lines = [];
        foreach ($fields as $key => $default) {
            $value = $_POST[$key] ?? $default;
            $escaped = addcslashes($value, "\n\r\"\\");
            $env_lines[] = "{$key}=\"{$escaped}\"";
        }

        $env_content = implode(PHP_EOL, $env_lines);
        $saved_path = __DIR__ . '/' . $filename;

        if (!is_writable(__DIR__)) {
            $message = "❌ 当前目录不可写入，请检查权限。";
        } else {
            $result = file_put_contents($saved_path, $env_content);
            if ($result !== false) {
                $message = "✅ 文件已成功保存为：<code>" . htmlspecialchars($filename) . "</code>";
                $preview = htmlspecialchars($env_content);
            } else {
                $message = "❌ 写入失败，请检查文件夹权限。";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <title>.env 文件生成器</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function syncFilename(selectEl) {
      document.getElementById('env_filename').value = selectEl.value;
    }
  </script>
</head>
<body class="bg-gray-100 text-gray-800">
  <div class="max-w-3xl mx-auto mt-10 p-6 bg-white rounded shadow-md">
    <h1 class="text-2xl font-bold text-blue-700 mb-6">.env 文件生成器</h1>

    <?php if ($message): ?>
      <div class="mb-4 px-4 py-3 rounded <?= strpos($message, '✅') === 0 ? 'bg-green-100 text-green-800 border border-green-400' : 'bg-red-100 text-red-800 border border-red-400' ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <?php if ($preview): ?>
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">文件内容预览：</label>
        <pre class="bg-gray-100 p-3 rounded text-sm border overflow-x-auto whitespace-pre-wrap"><?= $preview ?></pre>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">选择文件名</label>
        <select onchange="syncFilename(this)" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded focus:ring-blue-200">
          <option value=".env">.env（默认）</option>
          <option value=".env.local">.env.local（本地开发）</option>
          <option value=".env.testing">.env.testing（测试环境）</option>
          <option value=".env.staging">.env.staging（预发布）</option>
          <option value=".env.production">.env.production（生产环境）</option>
        </select>
        <input id="env_filename" name="env_filename" value="<?= htmlspecialchars($_POST['env_filename'] ?? '.env', ENT_QUOTES) ?>"
          class="w-full mt-2 px-3 py-2 border border-gray-300 rounded shadow-sm focus:ring focus:ring-blue-200" />
        <p class="text-xs text-gray-500 mt-1">支持格式：<code>.env</code> 或 <code>.env.xxx</code></p>
      </div>

      <?php
        foreach ($fields as $key => $default) {
            $value = $_POST[$key] ?? $default;
            $value = htmlspecialchars($value, ENT_QUOTES);
            echo <<<HTML
            <div>
              <label for="{$key}" class="block text-sm font-medium text-gray-700">{$key}</label>
              <input id="{$key}" name="{$key}" value="{$value}"
                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded shadow-sm focus:ring focus:ring-blue-200" />
            </div>
            HTML;
        }
      ?>

      <div class="pt-4">
        <button type="submit"
          class="px-6 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">
          生成 .env 文件
        </button>
      </div>
    </form>
  </div>
</body>
</html>