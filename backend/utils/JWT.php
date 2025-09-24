<?php
/**
 * JWT工具类（增强版）
 * - 兼容多种环境读取 Authorization 头
 * - 保持 encode/decode/verify/validateRequest 接口不变
 */

require_once __DIR__ . '/Env.php';

class JWT {
    private static $secret;
    private static $expire;

    private static function init() {
        if (self::$secret === null) {
            Env::load();
            self::$secret = Env::get('JWT_SECRET', 'your-secret-key-here');
            self::$expire = (int) Env::get('JWT_EXPIRE', 7200); // 2小时
        }
    }

    /**
     * 生成JWT token
     */
    public static function encode($payload) {
        self::init();
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $payload['iat'] = time();
        $payload['exp'] = time() + self::$expire;
        $payload = json_encode($payload);

        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secret, true);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    /**
     * 解析JWT token
     */
    public static function decode($jwt) {
        self::init();
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $signature = base64_decode(strtr($base64Signature, '-_', '+/'));
        $expectedSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secret, true);
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid JWT signature');
        }

        $payload = json_decode(base64_decode(strtr($base64Payload, '-_', '+/')), true);
        if (!$payload) {
            throw new Exception('Invalid JWT payload');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('JWT token has expired');
        }

        return $payload;
    }

    /**
     * 关键增强：尽可能可靠地从请求中获取 Bearer Token
     */
    public static function getTokenFromHeader() {
        $authHeader = null;
        $debug = getenv('DEBUG') === 'true' || getenv('DEBUG') === '1';
        $log = function($msg) use ($debug) {
            if ($debug) {
                $dir = dirname(__DIR__) . '/../logs';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                @file_put_contents($dir . '/auth.log', '['.date('Y-m-d H:i:s')."] ".$msg."
", FILE_APPEND);
            }
        };

        // 1) 优先用 getallheaders（有的环境可用）
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (!empty($headers)) {
                foreach ($headers as $k => $v) {
                    if (strcasecmp($k, 'Authorization') === 0) {
                        $authHeader = $v;
                        break;
                    }
                }
                $log('getallheaders Authorization='.($authHeader?$authHeader:'<none>'));
            } else {
                $log('getallheaders empty');
            }
        } else {
            $log('getallheaders not available');
        }

        // 2) 服务器变量兜底（常见于某些 Apache/PHPStudy 配置）
        if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            $log('$_SERVER[HTTP_AUTHORIZATION]='.$authHeader);
        }
        if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            $log('$_SERVER[REDIRECT_HTTP_AUTHORIZATION]='.$authHeader);
        }

        // 3) 彻底兜底：把所有 HTTP_* 头汇总并尝试找到 authorization
        if (!$authHeader) {
            foreach ($_SERVER as $name => $value) {
                if (stripos($name, 'HTTP_') === 0) {
                    $key = str_replace('_', '-', strtolower(substr($name, 5)));
                    if ($key === 'authorization') {
                        $authHeader = $value;
                        $log('scan $_SERVER '.$name.'='.$value);
                        break;
                    }
                }
            }
        }
        $log('final authHeader='.($authHeader?$authHeader:'<none>'));

        if ($authHeader && preg_match('/Bearer\s+(.+)$/i', trim($authHeader), $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * 验证JWT token（返回payload或false）
     */
    public static function verify($jwt) {
        try {
            return self::decode($jwt);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 从HTTP头中获取当前用户payload（无则返回null）
     */
    public static function getCurrentUser() {
        $token = self::getTokenFromHeader();
        if (!$token) return null;
        $payload = self::verify($token);
        return $payload ?: null;
    }

    /**
     * 验证请求（无token或无效则直接返回401）
     */
    public static function validateRequest() {
        $token = self::getTokenFromHeader();
        if (!$token) {
            require_once __DIR__ . '/Response.php';
            Response::error('未提供认证token', 401);
            exit;
        }
        $payload = self::verify($token);
        if (!$payload) {
            require_once __DIR__ . '/Response.php';
            Response::error('无效的认证token', 401);
            exit;
        }
        return $payload;
    }
}