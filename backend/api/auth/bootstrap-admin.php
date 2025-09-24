<?php
header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174', 'http://aiblog'];
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../utils/Env.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';

try {
    // 仅在DEBUG模式允许
    Env::load();
    $debug = Env::get('DEBUG', 'false');
    if (!in_array(strtolower((string)$debug), ['1','true','yes','on'])) {
        Response::error('Not allowed in production', 403);
    }

    // 创建/重置管理员
    $adminUsername = 'admin';
    $adminEmail = 'admin@example.com';
    $adminPasswordPlain = '123456';
    $adminPasswordHash = password_hash($adminPasswordPlain, PASSWORD_BCRYPT);

    $db = (new Database())->getConnection();

    // 确保users表存在（防御）
    $db->query("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        nickname VARCHAR(50),
        avatar VARCHAR(500),
        bio TEXT,
        role ENUM('admin','user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('active','inactive') DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 插入或更新
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $adminUsername]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $db->prepare("UPDATE users SET password = :p, email = :e, role='admin', status='active' WHERE username = :u");
        $stmt->execute([':p' => $adminPasswordHash, ':e' => $adminEmail, ':u' => $adminUsername]);
        $action = 'updated';
    } else {
        $stmt = $db->prepare("INSERT INTO users (username,password,email,nickname,avatar,bio,role,status) VALUES
            (:u,:p,:e,'管理员','https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png','系统初始化管理员','admin','active')");
        $stmt->execute([':u' => $adminUsername, ':p' => $adminPasswordHash, ':e' => $adminEmail]);
        $action = 'created';
    }

    Response::success([
        'action' => $action,
        'username' => $adminUsername,
        'password' => $adminPasswordPlain
    ], 'Admin ready');

} catch (Exception $e) {
    Response::error('Bootstrap failed: ' . $e->getMessage(), 500);
}