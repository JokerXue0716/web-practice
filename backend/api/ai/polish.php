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

require_once __DIR__ . '/../../models/User.php';
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
    
    // 模拟AI润色功能
    // 在实际项目中，这里会调用OpenAI API或其他AI服务
    $polishedContent = aiPolishSimulation($content);
    
    // 记录AI使用日志（可选）
    logAiUsage($user_id, 'polish', $content, $polishedContent);

    Response::success([
        'original' => $content,
        'polished' => $polishedContent
    ], 'AI润色完成');

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('AI润色API错误: ' . $e->getMessage());
        Response::serverError('AI润色失败，请稍后重试');
    }
}

/**
 * 模拟AI润色功能
 */
function aiPolishSimulation($content) {
    // 这里是模拟的AI润色逻辑
    // 实际项目中应该调用真实的AI API
    
    $improvements = [
        '优化了语言表达，使其更加流畅自然',
        '调整了句式结构，提高了可读性',
        '增强了逻辑连贯性，使文章更具说服力',
        '润色了用词，使表达更加准确生动'
    ];
    
    // 简单的文本处理示例
    $polished = $content;
    
    // 替换一些常见的表达
    $replacements = [
        '很好' => '非常出色',
        '不错' => '相当优秀',
        '可以' => '能够',
        '比较' => '相对',
        '应该' => '建议',
        '可能' => '或许',
        '一些' => '部分',
        '很多' => '众多'
    ];
    
    foreach ($replacements as $old => $new) {
        $polished = str_replace($old, $new, $polished);
    }
    
    // 在开头添加润色说明
    $improvement = $improvements[array_rand($improvements)];
    $polished = "【AI润色】" . $improvement . "\n\n" . $polished;
    
    return $polished;
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