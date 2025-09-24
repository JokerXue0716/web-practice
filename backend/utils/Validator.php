<?php
/**
 * 请求验证工具类
 */

class Validator {
    /**
     * 验证请求方法
     */
    public static function validateRequest($allowedMethods = ['GET']) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $allowedMethods)) {
            http_response_code(405);
            Response::error('Method Not Allowed', 405);
            exit;
        }
        
        return $method;
    }

    /**
     * 验证必需字段
     */
    public static function validateRequired($data, $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            Response::error('缺少必需字段: ' . implode(', ', $missing), 400);
            exit;
        }
        
        return true;
    }

    /**
     * 验证邮箱格式
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 验证密码强度
     */
    public static function validatePassword($password) {
        // 至少6位，包含字母和数字
        return strlen($password) >= 6 && preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $password);
    }

    /**
     * 验证用户名格式
     */
    public static function validateUsername($username) {
        // 3-20位，只能包含字母、数字、下划线
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
    }

    /**
     * 清理输入数据
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * 验证JWT token
     */
    public static function validateToken() {
        $token = JWT::getTokenFromHeader();
        
        if (!$token) {
            Response::error('未提供认证token', 401);
            exit;
        }
        
        $payload = JWT::verify($token);
        
        if (!$payload) {
            Response::error('无效的认证token', 401);
            exit;
        }
        
        return $payload;
    }

    /**
     * 验证分页参数
     */
    public static function validatePagination($page = 1, $limit = 10) {
        $page = max(1, intval($page));
        $limit = max(1, min(100, intval($limit))); // 限制最大100条
        
        return [$page, $limit];
    }
}