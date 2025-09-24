<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/Response.php';
require_once '../../utils/Env.php';

try {
    // 检查数据库连接
    $database = new Database();
    $dbStatus = $database->testConnection();
    $dbConfig = $database->getConfig();
    
    // 检查PHP版本
    $phpVersion = phpversion();
    $phpOk = version_compare($phpVersion, '7.4.0', '>=');
    
    // 检查必要的PHP扩展
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    $extensions = [];
    foreach ($requiredExtensions as $ext) {
        $extensions[$ext] = extension_loaded($ext);
    }
    
    // 检查文件权限
    $writableDirectories = [
        '../../uploads' => is_writable('../../uploads') || mkdir('../../uploads', 0755, true),
        '../../logs' => is_writable('../../logs') || mkdir('../../logs', 0755, true)
    ];
    
    // 系统信息
    $systemInfo = [
        'php_version' => $phpVersion,
        'php_ok' => $phpOk,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'current_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
    
    // 环境配置
    Env::load();
    $envConfig = [
        'debug' => Env::getBool('DEBUG', false),
        'site_name' => Env::get('SITE_NAME', 'AI助手增强博客系统'),
        'site_url' => Env::get('SITE_URL', 'http://localhost'),
        'jwt_configured' => Env::get('JWT_SECRET') !== 'your-secret-key-here'
    ];
    
    // 总体状态
    $allExtensionsOk = !in_array(false, $extensions);
    $allDirectoriesWritable = !in_array(false, $writableDirectories);
    $overallStatus = $dbStatus && $phpOk && $allExtensionsOk && $allDirectoriesWritable;
    
    Response::success([
        'status' => $overallStatus ? 'healthy' : 'error',
        'message' => $overallStatus ? '系统运行正常' : '系统存在问题，请检查配置',
        'checks' => [
            'database' => [
                'status' => $dbStatus,
                'config' => $dbConfig,
                'message' => $dbStatus ? '数据库连接正常' : '数据库连接失败'
            ],
            'php' => [
                'status' => $phpOk,
                'version' => $phpVersion,
                'message' => $phpOk ? 'PHP版本符合要求' : 'PHP版本过低，需要7.4或更高版本'
            ],
            'extensions' => [
                'status' => $allExtensionsOk,
                'details' => $extensions,
                'message' => $allExtensionsOk ? '所有必需扩展已安装' : '缺少必需的PHP扩展'
            ],
            'permissions' => [
                'status' => $allDirectoriesWritable,
                'details' => $writableDirectories,
                'message' => $allDirectoriesWritable ? '目录权限正常' : '部分目录不可写'
            ]
        ],
        'system_info' => $systemInfo,
        'environment' => $envConfig,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    Response::error('系统状态检查失败: ' . $e->getMessage(), 500);
}