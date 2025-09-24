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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// 预检直接返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../models/Tag.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/JWT.php';

try {
    // 验证JWT token
    $payload = JWT::validateRequest();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $tag = new Tag();
        
        // 获取查询参数
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $source = isset($_GET['source']) ? $_GET['source'] : '';

        // 如果指定强制使用白名单，则直接返回白名单
        if ($source === 'whitelist') {
            $whitelist = include __DIR__ . '/../../config/tags_whitelist.php';
            $tags = array_map(function($name) {
                return [
                    'id' => null,
                    'name' => $name,
                    'color' => '#409EFF',
                    'use_count' => 0,
                ];
            }, $whitelist);
            Response::success($tags);
        }

        switch ($type) {
            case 'popular':
                $tags = $tag->getPopular($limit);
                break;
            case 'all':
            default:
                $tags = $tag->getAll();
                break;
        }

        // 若数据库暂无标签，则回退使用官方白名单，生成默认结构
        if (empty($tags)) {
            $whitelist = include __DIR__ . '/../../config/tags_whitelist.php';
            $tags = array_map(function($name) {
                return [
                    'id' => null,
                    'name' => $name,
                    'color' => '#409EFF',
                    'use_count' => 0,
                ];
            }, $whitelist);
        }

        Response::success($tags);

    } else {
        Response::error('不支持的请求方法', 405);
    }

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('标签API错误: ' . $e->getMessage());
        Response::serverError('服务器错误，请稍后重试');
    }
}
?>