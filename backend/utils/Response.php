<?php
/**
 * API响应处理类
 */

class Response {
    /**
     * 成功响应
     */
    public static function success($data = null, $message = '操作成功', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'code' => $code,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 错误响应
     */
    public static function error($message = '操作失败', $code = 400, $data = null) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 验证失败响应
     */
    public static function validationError($errors, $message = '数据验证失败') {
        self::error($message, 422, ['errors' => $errors]);
    }

    /**
     * 未授权响应
     */
    public static function unauthorized($message = '未授权访问') {
        self::error($message, 401);
    }

    /**
     * 禁止访问响应
     */
    public static function forbidden($message = '禁止访问') {
        self::error($message, 403);
    }

    /**
     * 资源未找到响应
     */
    public static function notFound($message = '资源未找到') {
        self::error($message, 404);
    }

    /**
     * 服务器错误响应
     */
    public static function serverError($message = '服务器内部错误') {
        self::error($message, 500);
    }
}
?>