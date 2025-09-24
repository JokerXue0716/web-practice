<?php
/**
 * API统一入口文件
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'utils/Response.php';

try {
    // 获取请求路径
    $request_uri = $_SERVER['REQUEST_URI'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $path = str_replace(dirname($script_name), '', $request_uri);
    $path = trim($path, '/');
    
    // 移除查询参数
    if (($pos = strpos($path, '?')) !== false) {
        $path = substr($path, 0, $pos);
    }
    
    // 路由映射
    $routes = [
        // 认证相关
        'auth/login' => 'api/auth/login.php',
        'auth/register' => 'api/auth/register.php',
        
        // 用户相关
        'user/profile' => 'api/user/profile.php',
        'user/password' => 'api/user/password.php',
        
        // 文章相关
        'articles' => 'api/articles/index.php',
        'articles/detail' => 'api/articles/detail.php',
        
        // 标签相关
        'tags' => 'api/tags/index.php',
        
        // AI功能相关
        'ai/polish' => 'api/ai/polish.php',
        'ai/code-check' => 'api/ai/code-check.php',
        'ai/summary' => 'api/ai/summary.php',
        'ai/tag-recommend' => 'api/ai/tag-recommend.php',
    ];
    
    // 查找匹配的路由
    if (isset($routes[$path])) {
        $file = $routes[$path];
        if (file_exists($file)) {
            include $file;
        } else {
            Response::notFound('API文件不存在');
        }
    } else {
        Response::notFound('API接口不存在');
    }
    
} catch (Exception $e) {
    error_log('API路由错误: ' . $e->getMessage());
    Response::serverError('服务器错误');
}
?>