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
    
    // 模拟AI摘要生成功能
    $summary = aiSummarySimulation($content);
    
    // 记录AI使用日志
    logAiUsage($user_id, 'summary', $content, $summary);

    Response::success([
        'summary' => $summary,
        'word_count' => mb_strlen($content),
        'summary_ratio' => round(mb_strlen($summary) / mb_strlen($content) * 100, 2)
    ], 'AI摘要生成完成');

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('AI摘要API错误: ' . $e->getMessage());
        Response::serverError('AI摘要生成失败，请稍后重试');
    }
}

/**
 * 模拟AI摘要生成功能
 */
function aiSummarySimulation($content) {
    // 移除Markdown标记
    $cleanContent = preg_replace('/```[\s\S]*?```/', '[代码块]', $content);
    $cleanContent = preg_replace('/#{1,6}\s+/', '', $cleanContent);
    $cleanContent = preg_replace('/\*\*(.*?)\*\*/', '$1', $cleanContent);
    $cleanContent = preg_replace('/\*(.*?)\*/', '$1', $cleanContent);
    $cleanContent = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $cleanContent);
    $cleanContent = trim($cleanContent);
    
    // 按句子分割
    $sentences = preg_split('/[。！？.!?]/', $cleanContent);
    $sentences = array_filter($sentences, function($sentence) {
        return mb_strlen(trim($sentence)) > 10;
    });
    
    if (empty($sentences)) {
        return '本文主要介绍了相关技术内容，包含了详细的实现方案和代码示例。';
    }
    
    // 提取关键句子（简单算法：取前几句和包含关键词的句子）
    $keywords = ['技术', '方法', '实现', '功能', '系统', '开发', '设计', '解决', '优化', '性能'];
    $importantSentences = [];
    
    // 添加前两句
    for ($i = 0; $i < min(2, count($sentences)); $i++) {
        $sentence = trim($sentences[$i]);
        if (!empty($sentence)) {
            $importantSentences[] = $sentence;
        }
    }
    
    // 添加包含关键词的句子
    foreach ($sentences as $sentence) {
        $sentence = trim($sentence);
        if (empty($sentence) || in_array($sentence, $importantSentences)) continue;
        
        foreach ($keywords as $keyword) {
            if (strpos($sentence, $keyword) !== false) {
                $importantSentences[] = $sentence;
                break;
            }
        }
        
        if (count($importantSentences) >= 3) break;
    }
    
    // 生成摘要
    $summary = implode('。', array_slice($importantSentences, 0, 3));
    if (!empty($summary) && !preg_match('/[。！？.!?]$/', $summary)) {
        $summary .= '。';
    }
    
    // 如果摘要太短，添加通用描述
    if (mb_strlen($summary) < 50) {
        $summary = '本文详细介绍了相关技术实现方案，包含了具体的开发步骤和代码示例，为读者提供了实用的参考价值。';
    }
    
    return $summary;
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