<?php
/**
 * 环境变量加载工具
 */

class Env {
    private static $loaded = false;
    private static $vars = [];

    /**
     * 加载环境变量文件
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = __DIR__ . '/../config/.env';
        }

        if (!file_exists($path)) {
            // 如果.env文件不存在，尝试加载.env.example
            $examplePath = __DIR__ . '/../config/.env.example';
            if (file_exists($examplePath)) {
                $path = $examplePath;
            } else {
                return;
            }
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // 跳过注释行
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 移除引号
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                self::$vars[$key] = $value;
                
                // 设置到环境变量中
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * 获取环境变量值
     */
    public static function get($key, $default = null) {
        self::load();
        
        // 优先从系统环境变量获取
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // 然后从加载的文件中获取
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        
        return $default;
    }

    /**
     * 获取布尔值
     */
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }

    /**
     * 获取整数值
     */
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }
}