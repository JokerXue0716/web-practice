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
require_once __DIR__ . '/../../config/tags_whitelist.php';

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

    // 读取官方白名单
    $whitelist = include __DIR__ . '/../../config/tags_whitelist.php';
    $allowed = array_values(array_unique(array_map('strval', $whitelist)));

    // 若前端传入 allowed_tags，则与官方白名单取交集
    if (!empty($input['allowed_tags']) && is_array($input['allowed_tags'])) {
        $clientAllowed = array_values(array_unique(array_map('strval', $input['allowed_tags'])));
        $allowed = array_values(array_intersect($allowed, $clientAllowed));
        if (empty($allowed)) {
            // 保底使用官方白名单
            $allowed = array_values(array_unique(array_map('strval', $whitelist)));
        }
    }

    // 模拟AI标签推荐功能（仅从 allowed 中选择）
    $recommendedTags = aiTagRecommendSimulation($content, $allowed);

    // 记录AI使用日志
    logAiUsage($user_id, 'tag_recommend', $content, implode(',', $recommendedTags));

    Response::success([
        'tags' => $recommendedTags,
        'count' => count($recommendedTags)
    ], 'AI标签推荐完成');

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Token') !== false) {
        Response::unauthorized($e->getMessage());
    } else {
        error_log('AI标签推荐API错误: ' . $e->getMessage());
        Response::serverError('AI标签推荐失败，请稍后重试');
    }
}

/**
 * 模拟AI标签推荐功能
 */
function aiTagRecommendSimulation($content, $allowed = []) {
    $content = strtolower($content);
    $recommendedTags = [];
    
    // 技术标签映射
    $tagMappings = [
        // 前端技术
        'vue' => ['Vue', '前端'],
        'react' => ['React', '前端'],
        'angular' => ['Angular', '前端'],
        'javascript' => ['JavaScript', '前端'],
        'typescript' => ['TypeScript', '前端'],
        'html' => ['HTML', '前端', 'Web'],
        'css' => ['CSS', '前端', 'Web'],
        'sass' => ['Sass', 'CSS', '前端'],
        'less' => ['Less', 'CSS', '前端'],
        'webpack' => ['Webpack', '前端', '构建工具'],
        'vite' => ['Vite', '前端', '构建工具'],
        
        // 后端技术
        'php' => ['PHP', '后端'],
        'python' => ['Python', '后端'],
        'java' => ['Java', '后端'],
        'node' => ['Node.js', '后端', 'JavaScript'],
        'express' => ['Express', 'Node.js', '后端'],
        'spring' => ['Spring', 'Java', '后端'],
        'django' => ['Django', 'Python', '后端'],
        'flask' => ['Flask', 'Python', '后端'],
        
        // 数据库
        'mysql' => ['MySQL', '数据库'],
        'postgresql' => ['PostgreSQL', '数据库'],
        'mongodb' => ['MongoDB', '数据库', 'NoSQL'],
        'redis' => ['Redis', '数据库', '缓存'],
        
        // 移动端
        'android' => ['Android', '移动端'],
        'ios' => ['iOS', '移动端'],
        'flutter' => ['Flutter', '移动端'],
        'react native' => ['React Native', '移动端', 'React'],
        
        // 云服务和部署
        'docker' => ['Docker', '容器', '部署'],
        'kubernetes' => ['Kubernetes', '容器', '部署'],
        'aws' => ['AWS', '云服务'],
        'azure' => ['Azure', '云服务'],
        'nginx' => ['Nginx', '服务器'],
        
        // AI和机器学习
        'ai' => ['AI', '人工智能'],
        'machine learning' => ['机器学习', 'AI'],
        'deep learning' => ['深度学习', 'AI'],
        'tensorflow' => ['TensorFlow', 'AI', '机器学习'],
        'pytorch' => ['PyTorch', 'AI', '机器学习'],
        
        // 其他
        'git' => ['Git', '版本控制'],
        'api' => ['API', 'Web'],
        'rest' => ['REST', 'API'],
        'graphql' => ['GraphQL', 'API'],
        'security' => ['安全'],
        'performance' => ['性能优化'],
        'test' => ['测试'],
        'unit test' => ['单元测试', '测试']
    ];
    
    // 根据内容匹配标签
    foreach ($tagMappings as $keyword => $tags) {
        if (strpos($content, $keyword) !== false) {
            $recommendedTags = array_merge($recommendedTags, $tags);
        }
    }
    
    // 去重
    $recommendedTags = array_unique($recommendedTags);

    // 强制白名单过滤（大小写/中英文已按枚举）
    if (!empty($allowed)) {
        $set = array_flip($allowed);
        $recommendedTags = array_values(array_filter($recommendedTags, function($t) use ($set) {
            return isset($set[$t]);
        }));
    }

    // 限制数量
    $recommendedTags = array_slice($recommendedTags, 0, 5);
    
    // 如果没有匹配到标签，提供默认标签
    if (empty($recommendedTags)) {
        // 根据内容长度和特征推荐通用标签
        if (preg_match('/```[\s\S]*?```/', $content)) {
            $recommendedTags[] = '编程';
            $recommendedTags[] = '技术';
        }
        
        if (strpos($content, '教程') !== false || strpos($content, '学习') !== false) {
            $recommendedTags[] = '教程';
        }
        
        if (strpos($content, '项目') !== false || strpos($content, '开发') !== false) {
            $recommendedTags[] = '项目开发';
        }
        
        // 确保至少有一个标签
        if (empty($recommendedTags)) {
            $recommendedTags[] = '技术分享';
        }
    }
    
    return array_values($recommendedTags);
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