<?php
// 基础响应头
header('Content-Type: application/json; charset=utf-8');
// CORS 统一处理
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173','http://localhost:5174','http://aiblog'];
if ($origin && in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
 // 预检直接返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// 强制注入 Authorization 到 $_SERVER（兼容 CGI/FastCGI）
if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (function_exists('getallheaders')) {
        $__all = getallheaders();
        foreach ($__all as $k => $v) {
            if (strcasecmp($k, 'Authorization') === 0) {
                $_SERVER['HTTP_AUTHORIZATION'] = $v;
                break;
            }
        }
    }
    if (empty($_SERVER['HTTP_AUTHORIZATION']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
}

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/JWT.php';

// 只允许PUT请求
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('只允许PUT请求', 405);
}

try {
    // 验证JWT token
    $payload = JWT::validateRequest();
    $user_id = $payload['user_id'];

    $user = new User();
    if (!$user->findById($user_id)) {
        Response::notFound('用户不存在');
    }

    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('无效的JSON数据');
    }
    // 兼容前端可能的不同字段命名
    if (isset($input['currentPassword']) && !isset($input['oldPassword'])) {
        $input['oldPassword'] = $input['currentPassword'];
    }
    if (isset($input['new_password']) && !isset($input['newPassword'])) {
        $input['newPassword'] = $input['new_password'];
    }
    if (isset($input['confirm_password']) && !isset($input['confirmPassword'])) {
        $input['confirmPassword'] = $input['confirm_password'];
    }

    // 验证必填字段
    $errors = [];
    if (empty($input['oldPassword'])) {
        $errors['oldPassword'] = '当前密码不能为空';
    }

    if (empty($input['newPassword'])) {
        $errors['newPassword'] = '新密码不能为空';
    } elseif (strlen($input['newPassword']) < 6) {
        $errors['newPassword'] = '新密码长度不能少于6位';
    }

    if (empty($input['confirmPassword'])) {
        $errors['confirmPassword'] = '确认密码不能为空';
    } elseif ($input['newPassword'] !== $input['confirmPassword']) {
        $errors['confirmPassword'] = '两次输入的密码不一致';
    }

    if (!empty($errors)) {
        Response::validationError($errors);
    }

    // 验证当前密码
    if (!$user->verifyPassword($input['oldPassword'])) {
        // 可选：记录输入长度，避免记录明文密码（仅 DEBUG）
        if (getenv('DEBUG') === 'true' || getenv('DEBUG') === '1') {
            @file_put_contents(__DIR__ . '/../../logs/app.log', '['.date('Y-m-d H:i:s')."] password verify failed user_id={$user_id} oldLen=".(isset($input['oldPassword'])?strlen($input['oldPassword']):0)."
", FILE_APPEND);
        }
        Response::error('当前密码错误', 400);
    }

    // 更新密码
    if ($user->updatePassword($input['newPassword'])) {
        // 刷新并返回最新公开信息，便于前端同步
        $user->findById($user_id);
        Response::success($user->getPublicInfo(), '密码修改成功');
    } else {
        Response::serverError('密码修改失败，请稍后重试');
    }

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('修改密码API错误: ' . $e->getMessage());
        Response::serverError('服务器错误，请稍后重试');
    }
}
?>