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
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
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

try {
    // 验证JWT token
    $payload = JWT::validateRequest();
    $user_id = $payload['user_id'];

    $user = new User();
    if (!$user->findById($user_id)) {
        Response::notFound('用户不存在');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取用户资料
        Response::success($user->getPublicInfo());
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // 更新用户资料
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('无效的JSON数据');
        }

        // 验证数据
        $errors = [];
        if (isset($input['username'])) {
            if (empty($input['username'])) {
                $errors['username'] = '用户名不能为空';
            } elseif (strlen($input['username']) < 3 || strlen($input['username']) > 20) {
                $errors['username'] = '用户名长度必须在3-20个字符之间';
            } elseif ($user->usernameExists($input['username'], $user_id)) {
                $errors['username'] = '用户名已存在';
            }
        }

        if (isset($input['email'])) {
            if (empty($input['email'])) {
                $errors['email'] = '邮箱不能为空';
            } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '邮箱格式不正确';
            } elseif ($user->emailExists($input['email'], $user_id)) {
                $errors['email'] = '邮箱已被使用';
            }
        }

        if (isset($input['nickname']) && strlen($input['nickname']) > 20) {
            $errors['nickname'] = '昵称长度不能超过20个字符';
        }

        if (isset($input['bio']) && strlen($input['bio']) > 200) {
            $errors['bio'] = '个人简介长度不能超过200个字符';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        // 更新用户信息
        if (isset($input['username'])) $user->username = trim($input['username']);
        if (isset($input['email'])) $user->email = trim($input['email']);
        if (isset($input['nickname'])) $user->nickname = trim($input['nickname']);
        if (isset($input['avatar'])) $user->avatar = trim($input['avatar']);
        if (isset($input['bio'])) $user->bio = trim($input['bio']);

        if ($user->update()) {
            // 更新成功后从数据库重新加载，确保返回为最新数据
            $user->findById($user_id);
            Response::success($user->getPublicInfo(), '资料更新成功');
        } else {
            Response::serverError('更新失败，请稍后重试');
        }
        
    } else {
        Response::error('不支持的请求方法', 405);
    }

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('用户资料API错误: ' . $e->getMessage());
        Response::serverError('服务器错误，请稍后重试');
    }
}
?>