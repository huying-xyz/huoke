<?php
define('INSTALL_LOCK', __DIR__ . '/../../.env');
define('SQL_FILE', __DIR__ . '/mysql.sql');
define('ENV_FILE', __DIR__ . '/../../.env');
define('RUNTIME_DIR', __DIR__ . '/../../runtime');
define('MIN_PHP_VERSION', '8.4.0');

if (file_exists(INSTALL_LOCK)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>已安装</title>';
    echo '<style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}';
    echo '.card{background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.1);text-align:center;max-width:440px}';
    echo 'h2{color:#333;font-weight:400;font-size:16px;margin:0 0 24px}';
    echo 'a{display:inline-block;padding:10px 28px;background:#07c160;color:#fff;text-decoration:none;border-radius:6px;margin:4px}';
    echo '.warn{color:#999;font-size:12px;margin-top:16px}</style></head><body>';
    echo '<div class="card"><h2>系统已安装</h2>';
    echo '<a href="../">进入首页</a>';
    echo '<p class="warn">如需重新安装，请删除 .env 文件</p></div></body></html>';
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

function checkEnv()
{
    $groups = [];

    $groups['php'] = [[
        'name'  => 'PHP 版本 >= ' . MIN_PHP_VERSION,
        'pass'  => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>='),
        'value' => PHP_VERSION,
    ]];

    $extensions = ['pdo', 'pdo_mysql', 'curl', 'gd', 'mbstring', 'openssl', 'fileinfo', 'zip', 'sg16'];
    $groups['extensions'] = [];
    foreach ($extensions as $ext) {
        $groups['extensions'][] = [
            'name'  => $ext,
            'pass'  => extension_loaded($ext),
            'value' => extension_loaded($ext) ? '已安装' : '未安装',
        ];
    }

    $dirs = [
        RUNTIME_DIR                => 'runtime',
        __DIR__ . '/../../config'   => 'config',
    ];
    $groups['permissions'] = [];
    foreach ($dirs as $dir => $label) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $writable = is_writable($dir) || @mkdir($dir . '/_test_', 0755, true);
        if ($writable && is_dir($dir . '/_test_')) @rmdir($dir . '/_test_');
        $groups['permissions'][] = [
            'name'  => $label,
            'pass'  => $writable,
            'value' => $writable ? '可写' : '不可写',
        ];
    }

    return $groups;
}

function testDB($host, $port, $name, $user, $pass)
{
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($name));
        $exists = $stmt->fetch();
        return ['success' => true, 'exists' => !empty($exists), 'message' => $exists ? '数据库已存在' : '数据库不存在，将自动创建'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '连接失败: ' . $e->getMessage()];
    }
}

function doInstall($config)
{
    $result = ['success' => false, 'logs' => []];

    try {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `{$config['db_name']}`");
        $result['logs'][] = '✓ 数据库连接成功';

        $sql = file_get_contents(SQL_FILE);
        if (empty($sql)) {
            throw new Exception('无法读取 SQL 文件');
        }

        $statements = parseSQL($sql);
        $executed = 0;
        $errors = 0;
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;
            try {
                $pdo->exec($stmt);
                $executed++;
            } catch (PDOException $e) {
                if (stripos($stmt, 'DROP TABLE') !== 0) {
                    $errors++;
                    $result['logs'][] = '✗ SQL 错误: ' . mb_substr($e->getMessage(), 0, 100);
                }
            }
        }
        $result['logs'][] = "✓ SQL 导入完成 (执行 {$executed} 条语句" . ($errors > 0 ? ", {$errors} 条失败" : '') . ')';

        $hashed = password_hash($config['admin_pass'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE `api_user` SET username = ?, password = ? WHERE id = 1");
        $stmt->execute([$config['admin_user'], $hashed]);
        $result['logs'][] = '✓ 管理员账号已设置';

        $siteUrl = 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $envContent = <<<ENV
APP_DEBUG = false
APP_VERSION = 26.06.22

SITE_NAME              = {$config['site_name']}
SITE_NOTICE            = 我们会对生成的内容进行检测，如发现违反法律法规（包括但不限于色情、诈骗、赌博等），立即封禁相关账号，且不予退款。
SITE_NOTICE_MOBILE     = 更多完整功能请用电脑访问
SITE_NOTICE_POP        = "<font style="color: #07c160;font-size: 15px;font-weight: bolder;">获客大师 - 免费开源引流系统</font><br>GitHub：https://github.com/huying-xyz/huoke"
SITE_AVATAR            = https://20261688.oss-cn-hangzhou.aliyuncs.com/huoke/avatar.png
SITE_LOGO              = https://20261688.oss-cn-hangzhou.aliyuncs.com/huoke/logo.png
SITE_LOGO_MINI         = https://20261688.oss-cn-hangzhou.aliyuncs.com/huoke/logo_mini.png
SITE_KEFU_QR           = https://20261688.oss-cn-hangzhou.aliyuncs.com/huoke/kefu.png
SITE_APP_QR            = 
SITE_APP_LINK          = 
SITE_APP_VERSION       = 
SITE_COPYRIGHT         = "本站使用 <a target="_blank" href="https://github.com/huying-xyz/huoke" style="text-decoration: unset;"><text style="color:#1e9fff;">获客大师</text></a> 搭建"
SITE_ICP               = 京ICP证123456号
SITE_TMPLMSG_LOGIN     = 
SITE_BARK              = 
SITE_POINTS            = 1
SITE_TRY               = 1
SITE_FIRST_COMMISSION  = 10.00
SITE_SECOND_COMMISSION = 5.00
SITE_REGISTER          = true
SITE_APPID             = 
SITE_SECRET            = 
SITE_POINTS_DOUYIN     = 5

COOKIE_MIGU            = 
COOKIE_ZTO             = 
COOKIE_ZHAOPIN         = 
COOKIE_CLI             = 
COOKIE_TXC             = 

WECHAT_APPID           = 
WECHAT_MCH_ID          = 
WECHAT_MCH_KEY         = 

ALIPAY_APPID           = 
ALIPAY_PUBLIC_KEY      = 
ALIPAY_PRIVATE_KEY     = 

OSS_ACCESS_KEY_ID      = 
OSS_ACCESS_KEY_SECRET  = 
OSS_ENDPOINT           = 
OSS_BUCKET             = 
OSS_URL                = 

WX_APPID               = 
WX_APPSECRET           = 
WX_TOKEN               = 
WX_AESKEY              = 

DY_CLIENTKEY           = 
DY_CLIENTSECRET        = 

OPEN_URL               = 
OPEN_APPID             = 
OPEN_APPSECRET         = 

XCX_APPID              = 
XCX_APPSECRET          = 

DB_TYPE = mysql
DB_HOST = {$config['db_host']}
DB_NAME = {$config['db_name']}
DB_USER = {$config['db_user']}
DB_PASS = {$config['db_pass']}
DB_PORT = {$config['db_port']}
DB_CHARSET = utf8mb4
DB_PREFIX = api_
ENV;

        file_put_contents(ENV_FILE, $envContent);
        $result['logs'][] = '✓ 配置文件 .env 已生成';

        $assetsDir = __DIR__ . '/../assets';
        $newDomain = rtrim($siteUrl, '/') . '/';
        $pattern = '/(apiUrl:")([^"]*)(")/';
        $replaced = 0;
        if (is_dir($assetsDir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetsDir, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->getExtension() === 'js') {
                    $content = file_get_contents($file->getPathname());
                    $newContent = preg_replace($pattern, '${1}' . $newDomain . '${3}', $content, -1, $count);
                    if ($count > 0) {
                        file_put_contents($file->getPathname(), $newContent);
                        $replaced += $count;
                    }
                }
            }
        }
        if ($replaced > 0) {
            $result['logs'][] = "✓ 前端 apiUrl 已替换为 {$newDomain} ({$replaced} 处)";
        } else {
            $result['logs'][] = "⚠ 未找到前端 apiUrl，请检查 public/assets/ 目录";
        }

        if (!is_dir(RUNTIME_DIR)) {
            mkdir(RUNTIME_DIR, 0755, true);
        }

        $result['success'] = true;
        $result['admin_user'] = $config['admin_user'];
        $result['site_url'] = $siteUrl;

    } catch (Exception $e) {
        $result['logs'][] = '✗ 安装失败: ' . $e->getMessage();
    }

    return $result;
}

function parseSQL($sql)
{
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/^#.*$/m', '', $sql);

    $statements = [];
    $current = '';
    $lines = explode("\n", $sql);

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;
        
        $current .= $line . "\n";
        if (str_ends_with($trimmed, ';')) {
            $statements[] = $current;
            $current = '';
        }
    }
    if (trim($current) !== '') {
        $statements[] = $current;
    }

    return $statements;
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['action'] === 'test_db' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $res = testDB(
            $data['db_host'] ?? '127.0.0.1',
            $data['db_port'] ?? '3306',
            $data['db_name'] ?? '',
            $data['db_user'] ?? 'root',
            $data['db_pass'] ?? ''
        );
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_GET['action'] === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $config = [
            'db_host'    => $data['db_host'] ?? '127.0.0.1',
            'db_port'    => $data['db_port'] ?? '3306',
            'db_name'    => $data['db_name'] ?? 'api',
            'db_user'    => $data['db_user'] ?? 'root',
            'db_pass'    => $data['db_pass'] ?? '',
            'site_name'  => $data['site_name'] ?? '获客大师 - 免费开源引流系统',
            'admin_user' => $data['admin_user'] ?? '',
            'admin_pass' => $data['admin_pass'] ?? '',
        ];
        $res = doInstall($config);
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['error' => '未知操作'], JSON_UNESCAPED_UNICODE);
    exit;
}

$env = checkEnv();
$allPass = true;
foreach ($env as $items) {
    foreach ($items as $c) {
        if (!$c['pass']) $allPass = false;
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>获客大师 - 安装向导</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:linear-gradient(135deg,#07c160 0%,#05a34e 100%);min-height:100vh;color:#333}
.container{max-width:720px;margin:0 auto;padding:30px 20px}
.header{text-align:center;padding:40px 0 20px;color:#fff}
.header h1{font-size:28px;font-weight:600;margin-bottom:8px}
.header p{opacity:.85;font-size:14px}
.card{background:#fff;border-radius:12px;padding:32px;box-shadow:0 4px 24px rgba(0,0,0,.12);margin-bottom:20px}

.steps{display:flex;justify-content:center;margin-bottom:24px;gap:24px}
.step{display:flex;align-items:center;font-size:13px;color:#fff;opacity:.5;transition:.3s;white-space:nowrap}
.step.active{opacity:1;font-weight:600}
.step.done{opacity:.8}
.step .dot{width:8px;height:8px;border-radius:50%;background:#fff;margin-right:6px;flex-shrink:0}
.step.active .dot{width:10px;height:10px;box-shadow:0 0 8px rgba(255,255,255,.6)}

.footer{text-align:center;color:rgba(255,255,255,.7);font-size:12px;padding:20px}

.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:500;color:#666;margin-bottom:6px}
.form-group input,.form-group select{width:100%;padding:10px 14px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:14px;transition:border-color .2s;outline:none;background:#fafafa}
.form-group input:focus,.form-group select:focus{border-color:#07c160;background:#fff}
.form-row{display:flex;gap:12px}
.form-row .form-group{flex:1}
.form-hint{font-size:12px;color:#999;margin-top:4px}

.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 28px;border:none;border-radius:8px;font-size:15px;cursor:pointer;font-weight:500;transition:.2s}
.btn-primary{background:#07c160;color:#fff;text-decoration:none}
.btn-primary:hover{background:#06ad56}
.btn-primary:disabled{background:#a0e0b8;cursor:not-allowed}
.btn-outline{background:#fff;color:#07c160;border:1.5px solid #07c160}
.btn-outline:hover{background:#f0faf4}
.btn-group{display:flex;gap:12px;margin-top:20px;justify-content:flex-end}

.env-section{margin-bottom:20px}
.env-section-title{font-size:14px;font-weight:600;color:#333;margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #07c160}
.env-table{width:100%;border-collapse:collapse}
.env-table td{padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:14px}
.env-table tr:last-child td{border-bottom:none}
.env-table .name{font-weight:500}
.env-table .value{text-align:right;color:#999;font-size:13px}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:500}
.badge-pass{background:#e6f9ee;color:#07c160}
.badge-fail{background:#ffeaea;color:#e5484d}

.info-box{background:#f0faf4;border:1px solid #c6f0d8;border-radius:8px;padding:16px;margin:16px 0;text-align:left;font-size:13px;line-height:1.8}
.info-box strong{color:#07c160}

.loading{display:inline-block;width:16px;height:16px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite;margin-right:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}

.alert{padding:12px 16px;border-radius:8px;margin:12px 0;font-size:13px}
.alert-warn{background:#fff8e6;border:1px solid #ffe0a0;color:#b87a00}
.alert-info{background:#e8f4fd;border:1px solid #b8d8f0;color:#0b6eaf}

@media(max-width:600px){
    .container{padding:16px 10px}
    .card{padding:20px}
    .form-row{flex-direction:column;gap:0}
    .steps{font-size:12px;gap:0}
}
</style>
</head>
<body>
<div class="container">

<div class="header">
    <h1>获客大师免费开源引流系统</h1>
    <p>连接微信生态 · 助力私域营销</p>
</div>

<div class="steps">
    <div class="step <?= $step === 0 ? 'active' : ($step > 0 ? 'done' : '') ?>">
        <span class="dot"></span>环境检测
    </div>
    <div class="step <?= $step === 1 ? 'active' : ($step > 1 ? 'done' : '') ?>">
        <span class="dot"></span>数据库配置
    </div>
    <div class="step <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>">
        <span class="dot"></span>站点设置
    </div>
    <div class="step <?= $step === 3 ? 'active' : ($step > 3 ? 'done' : '') ?>">
        <span class="dot"></span>开始安装
    </div>
</div>

<?php if ($step === 0): ?>
<div class="card">
    <?php 
    $sections = [
        'php'         => 'PHP 版本',
        'extensions'  => 'PHP 扩展',
        'permissions' => '目录权限',
    ];
    foreach ($sections as $key => $title): 
    ?>
    <div class="env-section">
        <div class="env-section-title"><?= $title ?></div>
        <table class="env-table">
        <?php foreach ($env[$key] as $item): ?>
            <tr>
                <td class="name"><?= htmlspecialchars($item['name']) ?></td>
                <td class="value"><?= htmlspecialchars($item['value']) ?></td>
                <td style="width:80px;text-align:right">
                    <span class="badge <?= $item['pass'] ? 'badge-pass' : 'badge-fail' ?>">
                        <?= $item['pass'] ? '通过' : '失败' ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
        </table>
    </div>
    <?php endforeach; ?>

    <?php if (!$allPass): ?>
    <div class="alert alert-warn">
        ⚠️ 部分环境检查未通过，请根据提示修正后再继续安装。
    </div>
    <?php endif; ?>

    <div class="btn-group">
        <button class="btn btn-primary" onclick="location.href='?step=1'" <?= !$allPass ? 'disabled' : '' ?>>
            下一步 →
        </button>
    </div>
</div>

<?php elseif ($step === 1): ?>
<div class="card">
    <div class="form-group">
        <label>数据库主机</label>
        <input type="text" id="db_host" value="127.0.0.1" placeholder="通常为 127.0.0.1 或 localhost">
    </div>
    <div class="form-group">
        <label>端口</label>
        <input type="text" id="db_port" value="3306">
    </div>
    <div class="form-group">
        <label>数据库用户名</label>
        <input type="text" id="db_user" value="root">
    </div>
    <div class="form-group">
        <label>数据库名</label>
        <input type="text" id="db_name" value="api" placeholder="将自动创建">
    </div>
    <div class="form-group">
        <label>数据库密码</label>
        <input type="password" id="db_pass" placeholder="留空表示无密码">
    </div>
    <div class="btn-group">
        <button class="btn btn-outline" onclick="location.href='?step=0'">← 上一步</button>
        <button class="btn btn-outline" onclick="testConnection()" id="btnTest">测试连接</button>
        <button class="btn btn-primary" onclick="saveDBAndNext()" id="btnNext">下一步 →</button>
    </div>
    <div id="dbResult" style="margin-top:12px"></div>
</div>

<?php elseif ($step === 2): ?>
<div class="card">
    <div class="form-group">
        <label>站点名称</label>
        <input type="text" id="site_name" value="获客大师" placeholder="显示在页面标题等处">
    </div>
    <div class="form-group">
        <label>手机号码 *</label>
        <input type="text" id="admin_user" value="" placeholder="请输入管理员手机号码">
    </div>
    <div class="form-group">
        <label>登录密码 *</label>
        <input type="password" id="admin_pass" placeholder="请输入管理员登录密码">
    </div>

    <div class="btn-group">
        <button class="btn btn-outline" onclick="location.href='?step=1'">← 上一步</button>
        <button class="btn btn-primary" onclick="startInstall()">开始安装</button>
    </div>
    <div id="installResult"></div>
</div>

<?php elseif ($step === 3): ?>
<div class="card" id="finalCard">
    <div id="finalResult"></div>
</div>
<?php endif; ?>

<div class="footer">
    Powered by <a href="https://huying.xyz" target="_blank" style="color:#fff;text-decoration:underline">HuYing</a> &nbsp;|&nbsp; 获客大师 v26.06.22
</div>
</div>

<script>
function getVal(id, def) {
    var el = document.getElementById(id);
    return el ? el.value : def;
}

function saveDBAndNext() {
    var db = {
        db_host:   getVal('db_host', '127.0.0.1'),
        db_port:   getVal('db_port', '3306'),
        db_name:   getVal('db_name', 'api'),
        db_user:   getVal('db_user', 'root'),
        db_pass:   getVal('db_pass', ''),
    };
    sessionStorage.setItem('db_config', JSON.stringify(db));
    location.href = '?step=2';
}

(function() {
    <?php if ($step === 1): ?>
    var db = sessionStorage.getItem('db_config');
    if (db) {
        try {
            var d = JSON.parse(db);
            if (d.db_host)   document.getElementById('db_host').value   = d.db_host;
            if (d.db_port)   document.getElementById('db_port').value   = d.db_port;
            if (d.db_name)   document.getElementById('db_name').value   = d.db_name;
            if (d.db_user)   document.getElementById('db_user').value   = d.db_user;
            if (d.db_pass)   document.getElementById('db_pass').value   = d.db_pass;
        } catch(e) {}
    }
    <?php endif; ?>
})();

(function() {
    <?php if ($step === 2): ?>
    var cfg = sessionStorage.getItem('site_config');
    if (cfg) {
        try {
            var c = JSON.parse(cfg);
            if (c.site_name)  document.getElementById('site_name').value  = c.site_name;
            if (c.admin_user) document.getElementById('admin_user').value = c.admin_user;
        } catch(e) {}
    }
    if (!sessionStorage.getItem('db_config')) {
        location.href = '?step=1';
    }
    <?php endif; ?>
})();

function testConnection() {
    var btn = document.getElementById('btnTest');
    var result = document.getElementById('dbResult');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span>测试中...';
    result.innerHTML = '';

    fetch('?action=test_db', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            db_host: getVal('db_host', '127.0.0.1'),
            db_port: getVal('db_port', '3306'),
            db_name: getVal('db_name', 'api'),
            db_user: getVal('db_user', 'root'),
            db_pass: getVal('db_pass', ''),
        })
    })
    .then(function(r){return r.json()})
    .then(function(res){
        btn.disabled = false;
        btn.innerHTML = '测试连接';
        if (res.success) {
            result.innerHTML = '<div class="alert alert-info">✅ 连接成功！' + res.message + '</div>';
        } else {
            result.innerHTML = '<div class="alert alert-warn">❌ ' + res.message + '</div>';
        }
    })
    .catch(function(e){
        btn.disabled = false;
        btn.innerHTML = '测试连接';
        result.innerHTML = '<div class="alert alert-warn">❌ 请求失败: ' + e.message + '</div>';
    });
}

function startInstall() {
    var adminUser = getVal('admin_user', '').trim();
    var adminPass = getVal('admin_pass', '');
    if (!/^1\d{10}$/.test(adminUser)) {
        alert('请输入正确的11位手机号');
        return;
    }
    if (!adminPass || adminPass.length < 6) {
        alert('管理员密码至少6位，必填');
        return;
    }

    var result = document.getElementById('installResult');
    result.innerHTML = '<div style="text-align:center;padding:20px"><span class="loading" style="border-color:#07c160;border-top-color:transparent;width:24px;height:24px"></span><p style="margin-top:12px;color:#666">正在准备安装...</p></div>';

    var dbConfig = {};
    try {
        dbConfig = JSON.parse(sessionStorage.getItem('db_config')) || {};
    } catch(e) {}

    var allData = {
        db_host:    dbConfig.db_host    || '127.0.0.1',
        db_port:    dbConfig.db_port    || '3306',
        db_name:    dbConfig.db_name    || 'api',
        db_user:    dbConfig.db_user    || 'root',
        db_pass:    dbConfig.db_pass    || '',
        site_name:  getVal('site_name', '获客大师'),
        admin_user: getVal('admin_user', ''),
        admin_pass: adminPass,
    };

    sessionStorage.setItem('site_config', JSON.stringify({
        site_name:  allData.site_name,
        admin_user: allData.admin_user,
    }));

    sessionStorage.setItem('install_data', JSON.stringify(allData));
    location.href = '?step=3';
}

(function(){
    <?php if ($step === 3): ?>
    var stored = sessionStorage.getItem('install_data');
    if (!stored) {
        document.getElementById('finalResult').innerHTML =
            '<p style="color:#999">安装数据丢失，请从<a href="?step=2">站点设置</a>重新开始</p>';
        return;
    }

    var data = JSON.parse(stored);
    var resultDiv = document.getElementById('finalResult');
    resultDiv.innerHTML = '<div style="text-align:center;padding:20px"><span class="loading" style="border-color:#07c160;border-top-color:transparent;width:30px;height:30px"></span><p style="margin-top:16px;color:#666">正在安装，请稍候...</p></div>';

    fetch('?action=install', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(function(r){return r.json()})
    .then(function(res){
        sessionStorage.removeItem('install_data');
        sessionStorage.removeItem('db_config');
        sessionStorage.removeItem('site_config');

        if (res.success) {
            resultDiv.innerHTML =
                '<div class="info-box">' +
                '1. 手机号码：<strong>' + (res.admin_user || data.admin_user) + '</strong><br>' +
                '2. 登录密码：<strong>' + (data.admin_pass || '') + '</strong><br>' +
                '3. 请删除 <code>public/install</code> 目录' +
                '</div>' +
                '<div class="btn-group" style="justify-content:center;">' +
                '<a href="../" class="btn btn-primary">进入网站首页</a>' +
                '</div>';
        } else {
            resultDiv.innerHTML =
                '<h2 style="color:#e5484d">安装失败</h2>' +
                '<div style="color:#f07070">' + res.logs.join('<br>') + '</div>' +
                '<div class="btn-group" style="justify-content:center;">' +
                '<button class="btn btn-outline" onclick="location.href=\'?step=2\'">返回重试</button>' +
                '</div>';
        }
    })
    .catch(function(e){
        resultDiv.innerHTML =
            '<h2 style="color:#e5484d">请求失败</h2>' +
            '<p style="color:#666">' + e.message + '</p>' +
            '<div class="btn-group" style="justify-content:center;">' +
            '<button class="btn btn-outline" onclick="location.href=\'?step=2\'">返回重试</button>' +
            '</div>';
    });
    <?php endif; ?>
})();
</script>

</body>
</html>
