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

require_once '../../models/User.php';
require_once '../../utils/Response.php';
require_once '../../utils/JWT.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
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
    $errors = [];
    if (empty($input['username'])) {
        $errors['username'] = '用户名不能为空';
    } elseif (strlen($input['username']) < 3 || strlen($input['username']) > 20) {
        $errors['username'] = '用户名长度必须在3-20个字符之间';
    }

    if (empty($input['password'])) {
        $errors['password'] = '密码不能为空';
    } elseif (strlen($input['password']) < 6) {
        $errors['password'] = '密码长度不能少于6位';
    }

    if (empty($input['email'])) {
        $errors['email'] = '邮箱不能为空';
    } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = '邮箱格式不正确';
    }

    if (!empty($errors)) {
        Response::validationError($errors);
    }

    $username = trim($input['username']);
    $password = $input['password'];
    $email = trim($input['email']);
    $nickname = !empty($input['nickname']) ? trim($input['nickname']) : $username;

    // 检查用户名和邮箱是否已存在
    $user = new User();

    // DEBUG 开关
    $debugEnabled = false;
    try {
        require_once __DIR__ . '/../../utils/Env.php';
        Env::load();
        $debugEnabled = in_array(strtolower((string)Env::get('DEBUG','false')), ['1','true','yes','on']);
    } catch (\Throwable $t) {}

    if ($user->usernameExists($username)) {
        if ($debugEnabled) {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            @file_put_contents($logDir . '/auth.log', '['.date('Y-m-d H:i:s')."] register conflict username={$username}
", FILE_APPEND);
        }
        Response::error('用户名已存在', 409);
    }

    if ($user->emailExists($email)) {
        if ($debugEnabled) {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            @file_put_contents($logDir . '/auth.log', '['.date('Y-m-d H:i:s')."] register conflict email={$email}
", FILE_APPEND);
        }
        Response::error('邮箱已被注册', 409);
    }

    // 创建新用户
    $user->username = $username;
    $user->password = password_hash($password, PASSWORD_DEFAULT);
    $user->email = $email;
    $user->nickname = $nickname;
    $user->avatar = 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png';
    $user->bio = '';
    $user->role = 'user';
    $user->status = 'active';

    if ($user->create()) {
        if ($debugEnabled) {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            @file_put_contents($logDir . '/auth.log', '['.date('Y-m-d H:i:s')."] register ok id={$user->id} username={$user->username} status={$user->status}
", FILE_APPEND);
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
        ], '注册成功');
    } else {
        if ($debugEnabled) {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            // 进一步输出 PDO 错误信息
            try {
                // 直接复用 User::create 内部的错误日志，外层再打一次兜底
                @file_put_contents($logDir . '/auth.log', '['.date('Y-m-d H:i:s')."] register failed username={$username} email={$email}
", FILE_APPEND);
            } catch (\Throwable $t) {}
        }
        Response::serverError('注册失败，请稍后重试');
    }

} catch (Exception $e) {
    error_log('注册错误: ' . $e->getMessage());
    Response::serverError('注册失败，请稍后重试');
}
?>