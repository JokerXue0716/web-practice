<?php
header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174', 'http://aiblog'];
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/JWT.php';

// 处理预检请求 (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // 直接返回 200 响应，不执行后续代码
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('只允许POST请求', 405);
}

try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('无效的JSON数据');
    }

    // 验证必填字段
    if (empty($input['username']) || empty($input['password'])) {
        Response::validationError([
            'username' => empty($input['username']) ? '用户名不能为空' : null,
            'password' => empty($input['password']) ? '密码不能为空' : null
        ]);
    }

    $username = trim($input['username']);
    $password = $input['password'];

    // 查找用户
    $user = new User();
    $found = $user->findByUsername($username);

    // DEBUG 日志（仅在 DEBUG=true|1|yes|on 时）
    $debugEnabled = false;
    try {
        require_once __DIR__ . '/../../utils/Env.php';
        Env::load();
        $debugEnabled = in_array(strtolower((string)Env::get('DEBUG','false')), ['1','true','yes','on']);
    } catch (\Throwable $t) {}

    if ($debugEnabled) {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
        $hashPrefix = ($found && !empty($user->password)) ? substr((string)$user->password, 0, 4) : '<none>';
        $log = sprintf("[%s] login check user=%s found=%s status=%s hashPrefix=%s
",
            date('Y-m-d H:i:s'), $username, $found?'yes':'no', $found?($user->status??'<null>'):'<na>', $hashPrefix);
        @file_put_contents($logDir . '/auth.log', $log, FILE_APPEND);
    }

    if (!$found) {
        Response::error('用户名或密码错误', 401);
    }

    // 验证密码
    $passOk = $user->verifyPassword($password);

    if ($debugEnabled) {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
        $log = sprintf("[%s] login verify user=%s result=%s
", date('Y-m-d H:i:s'), $username, $passOk?'ok':'fail');
        @file_put_contents($logDir . '/auth.log', $log, FILE_APPEND);
    }

    if (!$passOk) {
        Response::error('用户名或密码错误', 401);
    }

    $debugEnabled = false;
try {
    require_once __DIR__ . '/../../utils/Env.php';
    Env::load();
    $debugEnabled = in_array(strtolower((string)Env::get('DEBUG','false')), ['1','true','yes','on']);
} catch (\Throwable $t) {}

if ($debugEnabled) {
    // 记录调试日志
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
    $log = sprintf("[%s] login try: user=%s, found=%s\n", date('Y-m-d H:i:s'), $username, isset($user) && $user->id ? 'yes' : 'no');
    @file_put_contents($logDir . '/auth.log', $log, FILE_APPEND);
}
// 生成JWT token
    $payload = [
        'user_id' => $user->id,
        'username' => $user->username,
        'role' => $user->role
    ];
    $token = JWT::encode($payload);

    // 返回成功响应
    Response::success([
        'token' => $token,
        'user' => $user->getPublicInfo()
    ], '登录成功');

} catch (Exception $e) {
    error_log('登录错误: ' . $e->getMessage());
    Response::serverError('登录失败，请稍后重试');
}
?>