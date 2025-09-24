<?php
/**
 * 数据库配置文件
 */

require_once __DIR__ . '/../utils/Env.php';

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
        // 加载环境变量
        Env::load();
        
        // 从环境变量获取配置
        $this->host = Env::get('DB_HOST', 'localhost');
        $this->port = Env::getInt('DB_PORT', 3306);
        $this->db_name = Env::get('DB_NAME', 'ai_blog_system');
        $this->username = Env::get('DB_USER', 'root');
        $this->password = Env::get('DB_PASS', 'root');
    }

    /**
     * 获取数据库连接
     */
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
                ];

                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new Exception("数据库连接失败: " . $e->getMessage());
            }
        }

        return $this->pdo;
    }

    /**
     * 关闭数据库连接
     */
    public function closeConnection() {
        $this->pdo = null;
    }

    /**
     * 测试数据库连接
     */
    public function testConnection() {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取数据库配置信息（不包含密码）
     */
    public function getConfig() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->db_name,
            'username' => $this->username,
            'charset' => $this->charset
        ];
    }
}