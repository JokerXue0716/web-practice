<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 用户模型类
 */
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $email;
    public $nickname;
    public $avatar;
    public $bio;
    public $role;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * 根据用户名查找用户
     */
    public function findByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->email = $row['email'];
            $this->nickname = $row['nickname'];
            $this->avatar = $row['avatar'];
            $this->bio = $row['bio'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    /**
     * 根据ID查找用户
     */
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND status = 'active' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $this->id = $row['id'];
            $this->username = $row['username'];
            // 关键：把密码哈希一并加载，用于后续 password_verify
            $this->password = $row['password'];
            $this->email = $row['email'];
            $this->nickname = $row['nickname'];
            $this->avatar = $row['avatar'];
            $this->bio = $row['bio'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    /**
     * 创建新用户
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, email, nickname, avatar, bio, role, status) 
                  VALUES (:username, :password, :email, :nickname, :avatar, :bio, :role, :status)";

        $stmt = $this->conn->prepare($query);

        // 清理数据
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nickname = htmlspecialchars(strip_tags($this->nickname));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->status = htmlspecialchars(strip_tags($this->status ?: 'active'));

        // 绑定参数
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':nickname', $this->nickname);
        $stmt->bindParam(':avatar', $this->avatar);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        // DEBUG：记录错误信息
        try {
            require_once __DIR__ . '/../utils/Env.php';
            Env::load();
            $debug = in_array(strtolower((string)Env::get('DEBUG','false')), ['1','true','yes','on']);
            if ($debug) {
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
                $info = $stmt->errorInfo();
                @file_put_contents($logDir . '/auth.log', '['.date('Y-m-d H:i:s')."] user.create failed err=".json_encode($info,JSON_UNESCAPED_UNICODE)."
", FILE_APPEND);
            }
        } catch (\Throwable $t) {}

        return false;
    }

    /**
     * 更新用户信息
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username = :username, email = :email, nickname = :nickname, 
                      avatar = :avatar, bio = :bio, updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // 清理数据
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nickname = htmlspecialchars(strip_tags($this->nickname));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->bio = htmlspecialchars(strip_tags($this->bio));

        // 绑定参数
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':nickname', $this->nickname);
        $stmt->bindParam(':avatar', $this->avatar);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * 更新密码
     */
    public function updatePassword($newPassword) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password, updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * 验证密码
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }

    /**
     * 检查用户名是否存在
     */
    public function usernameExists($username, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * 检查邮箱是否存在
     */
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        if ($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * 获取用户公开信息
     */
    public function getPublicInfo() {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>