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
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/JWT.php';

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('只允许POST请求', 405);
}

try {
    // 验证JWT token
    $payload = JWT::validateRequest();
    $user_id = $payload['user_id'];

    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('无效的JSON数据');
    }

    if (empty($input['content'])) {
        Response::error('内容不能为空');
    }

    $content = trim($input['content']);
    
    // 模拟AI代码检测功能
    $checkResult = aiCodeCheckSimulation($content);
    
    // 记录AI使用日志
    logAiUsage($user_id, 'code_check', $content, json_encode($checkResult));

    Response::success($checkResult, 'AI代码检测完成');

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('AI代码检测API错误: ' . $e->getMessage());
        Response::serverError('AI代码检测失败，请稍后重试');
    }
}

/**
 * 模拟AI代码检测功能
 */
function aiCodeCheckSimulation($content) {
    $issues = [];
    $suggestions = [];
    
    // 检测常见的代码问题
    if (preg_match('/```[\s\S]*?```/', $content)) {
        // 包含代码块
        
        // 检测JavaScript相关问题
        if (strpos($content, 'var ') !== false) {
            $issues[] = [
                'type' => 'warning',
                'message' => '建议使用 let 或 const 替代 var',
                'line' => '代码块中'
            ];
        }
        
        if (strpos($content, '==') !== false && strpos($content, '===') === false) {
            $issues[] = [
                'type' => 'warning',
                'message' => '建议使用严格相等 === 替代 ==',
                'line' => '代码块中'
            ];
        }
        
        if (strpos($content, 'console.log') !== false) {
            $issues[] = [
                'type' => 'info',
                'message' => '生产环境中应移除 console.log 语句',
                'line' => '代码块中'
            ];
        }
        
        // 检测SQL注入风险
        if (preg_match('/SELECT.*FROM.*WHERE.*\$/', $content)) {
            $issues[] = [
                'type' => 'error',
                'message' => '可能存在SQL注入风险，建议使用参数化查询',
                'line' => '数据库查询'
            ];
        }
        
        // 安全建议
        $suggestions[] = '使用参数化查询防止SQL注入';
        $suggestions[] = '对用户输入进行验证和过滤';
        $suggestions[] = '使用HTTPS传输敏感数据';
        $suggestions[] = '定期更新依赖包版本';
        
    } else {
        // 纯文本内容
        $issues[] = [
            'type' => 'info',
            'message' => '未检测到代码块，这是一篇普通文章',
            'line' => '全文'
        ];
        
        $suggestions[] = '如果包含代码，建议使用代码块格式（```）';
        $suggestions[] = '添加代码注释提高可读性';
    }
    
    return [
        'summary' => [
            'total_issues' => count($issues),
            'errors' => count(array_filter($issues, function($issue) { return $issue['type'] === 'error'; })),
            'warnings' => count(array_filter($issues, function($issue) { return $issue['type'] === 'warning'; })),
            'info' => count(array_filter($issues, function($issue) { return $issue['type'] === 'info'; }))
        ],
        'issues' => $issues,
        'suggestions' => $suggestions,
        'score' => max(0, 100 - count($issues) * 10) // 简单的评分算法
    ];
}

/**
 * 记录AI使用日志
 */
function logAiUsage($user_id, $feature_type, $input_content, $output_content, $article_id = null) {
    try {
        require_once '../../config/database.php';
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "INSERT INTO ai_usage_logs (user_id, feature_type, input_content, output_content, article_id) 
                  VALUES (:user_id, :feature_type, :input_content, :output_content, :article_id)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':feature_type', $feature_type);
        $stmt->bindParam(':input_content', $input_content);
        $stmt->bindParam(':output_content', $output_content);
        $stmt->bindParam(':article_id', $article_id);
        
        $stmt->execute();
    } catch (Exception $e) {
        error_log('AI使用日志记录失败: ' . $e->getMessage());
    }
}
?>