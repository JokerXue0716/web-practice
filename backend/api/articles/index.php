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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

    $article = new Article();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 获取文章列表
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (!empty($search)) {
            $articles = $article->search($search, $user_id, $page, $limit);
        } else {
            $articles = $article->getByAuthor($user_id, $status, $page, $limit);
        }

        // 获取统计信息（整体统计）
        $stats = $article->getStats($user_id);

        // 计算当前筛选条件下的总数 total（用于分页）
        // 注意：search 优先，其次是按 author/status 的列表
        $total = 0;
        try {
            if (!empty($search)) {
                $countSql = "SELECT COUNT(*) as cnt FROM articles a WHERE (a.title LIKE :kw OR a.content LIKE :kw)";
                if ($user_id) {
                    $countSql .= " AND a.author_id = :author_id";
                }
                $stmtCnt = (new Database())->getConnection()->prepare($countSql);
                $kw = '%' . $search . '%';
                $stmtCnt->bindParam(':kw', $kw);
                if ($user_id) {
                    $stmtCnt->bindParam(':author_id', $user_id, PDO::PARAM_INT);
                }
                $stmtCnt->execute();
                $rowCnt = $stmtCnt->fetch();
                $total = (int)($rowCnt['cnt'] ?? 0);
            } else {
                $countSql = "SELECT COUNT(*) as cnt FROM articles a WHERE a.author_id = :author_id";
                if (!empty($status)) {
                    $countSql .= " AND a.status = :status";
                }
                $stmtCnt = (new Database())->getConnection()->prepare($countSql);
                $stmtCnt->bindParam(':author_id', $user_id, PDO::PARAM_INT);
                if (!empty($status)) {
                    $stmtCnt->bindParam(':status', $status);
                }
                $stmtCnt->execute();
                $rowCnt = $stmtCnt->fetch();
                $total = (int)($rowCnt['cnt'] ?? 0);
            }
        } catch (Exception $e) {
            // 回退策略：统计失败不影响列表，但分页 total 为已返回的数量
            $total = is_array($articles) ? count($articles) : 0;
            error_log('分页统计失败: ' . $e->getMessage());
        }

        Response::success([
            'articles' => $articles,
            'stats' => $stats,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 创建新文章
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            Response::error('无效的JSON数据');
        }

        // 读取目标状态并做校验（发布严格校验，草稿放宽）
        $errors = [];
        $targetStatus = isset($input['status']) && in_array($input['status'], ['draft', 'published'])
            ? $input['status'] : 'draft';

        // 标题长度上限始终生效；是否必填取决于是否发布
        if (isset($input['title']) && strlen($input['title']) > 200) {
            $errors['title'] = '文章标题不能超过200个字符';
        }
        if ($targetStatus === 'published') {
            if (empty($input['title'])) {
                $errors['title'] = $errors['title'] ?? '文章标题不能为空';
            }
            if (empty($input['content'])) {
                $errors['content'] = '文章内容不能为空';
            }
        }

        // 摘要长度限制（按字符计数，兼容中英文）
        if (isset($input['summary']) && mb_strlen($input['summary'], 'UTF-8') > 500) {
            $errors['summary'] = '文章摘要不能超过500个字符';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        // 创建文章
        $article->title = isset($input['title']) ? trim($input['title']) : '';
        $article->summary = isset($input['summary']) ? trim($input['summary']) : '';
        $article->content = isset($input['content']) ? $input['content'] : '';
        $article->author_id = $user_id;
        $article->status = $targetStatus;

        if ($article->create()) {
            // 处理标签（白名单校验 + 容错，避免保存成功后抛错导致 500）
            try {
                if (isset($input['tags'])) {
                    $rawTags = $input['tags'];
                    if (is_string($rawTags)) {
                        // 兼容逗号分隔字符串
                        $rawTags = array_filter(array_map('trim', explode(',', $rawTags)));
                    }
                    if (is_array($rawTags) && !empty($rawTags)) {
                        $tag = new Tag();
                        // 仅接受已存在于 tags 表的标签，不自动创建
                        $tag->addToArticle($article->id, $rawTags);
                    }
                }
            } catch (Exception $e) {
                // 标签处理失败不影响文章已创建结果，记录日志即可
                error_log('标签处理失败: ' . $e->getMessage());
            }

            // 获取完整的文章信息
            $articleData = $article->findById($article->id);
            Response::success($articleData, '文章创建成功');
        } else {
            Response::serverError('文章创建失败，请稍后重试');
        }

    } else {
        Response::error('不支持的请求方法', 405);
    }

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('文章API错误: ' . $e->getMessage());
        Response::serverError('服务器错误，请稍后重试');
    }
}
?>