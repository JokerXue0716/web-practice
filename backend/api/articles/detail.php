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
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
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

require_once __DIR__ . '/../../models/Article.php';
require_once __DIR__ . '/../../models/Tag.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/JWT.php';

try {
    // 验证JWT token
    $payload = JWT::validateRequest();
    $user_id = $payload['user_id'];

    // 获取文章ID
    $article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$article_id) {
        Response::error('文章ID不能为空');
    }

    $article = new Article();
    $articleData = $article->findById($article_id);

    if (!$articleData) {
        Response::notFound('文章不存在');
    }

    // 检查权限（只能操作自己的文章）
    if ($articleData['author_id'] != $user_id) {
        Response::forbidden('无权限操作此文章');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取文章详情
        Response::success($articleData);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // 更新文章
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('无效的JSON数据');
        }

        // 验证数据
        $errors = [];
        if (isset($input['title'])) {
            if (empty($input['title'])) {
                $errors['title'] = '文章标题不能为空';
            } elseif (strlen($input['title']) > 200) {
                $errors['title'] = '文章标题不能超过200个字符';
            }
        }

        if (isset($input['content']) && empty($input['content'])) {
            $errors['content'] = '文章内容不能为空';
        }

        // 摘要长度限制（按字符计数，兼容中英文）
        if (isset($input['summary']) && mb_strlen($input['summary'], 'UTF-8') > 500) {
            $errors['summary'] = '文章摘要不能超过500个字符';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        // 更新文章信息
        $article->id = $article_id;
        $article->author_id = $user_id;
        if (isset($input['title'])) $article->title = trim($input['title']);
        if (isset($input['summary'])) $article->summary = trim($input['summary']);
        if (isset($input['content'])) $article->content = $input['content'];
        if (isset($input['status']) && in_array($input['status'], ['draft', 'published'])) {
            $article->status = $input['status'];
        }
        $article->published_at = $articleData['published_at'];

        if ($article->update()) {
            // 处理标签
            if (isset($input['tags']) && is_array($input['tags'])) {
                $tag = new Tag();
                $tag->addToArticle($article_id, $input['tags']);
            }

            // 获取更新后的文章信息
            $updatedArticle = $article->findById($article_id);
            Response::success($updatedArticle, '文章更新成功');
        } else {
            Response::serverError('文章更新失败，请稍后重试');
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // 删除文章
        $article->id = $article_id;
        $article->author_id = $user_id;

        if ($article->delete()) {
            Response::success(null, '文章删除成功');
        } else {
            Response::serverError('文章删除失败，请稍后重试');
        }

    } else {
        Response::error('不支持的请求方法', 405);
    }

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('文章详情API错误: ' . $e->getMessage());
        Response::serverError('服务器错误，请稍后重试');
    }
}
?>