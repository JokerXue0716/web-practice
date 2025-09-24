<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 标签模型类
 */
class Tag {
    private $conn;
    private $table_name = "tags";

    public $id;
    public $name;
    public $color;
    public $use_count;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * 获取所有标签
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY use_count DESC, name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * 根据名称查找标签
     */
    public function findByName($name) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE name = :name LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->color = $row['color'];
            $this->use_count = $row['use_count'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    /**
     * 创建标签
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, color) VALUES (:name, :color)";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->color = htmlspecialchars(strip_tags($this->color));

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':color', $this->color);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * 为文章添加标签
     */
    public function addToArticle($article_id, $tag_names) {
        if (empty($tag_names)) {
            return true;
        }

        // 兼容：可能传入的是以 name 为单位的字符串数组或逗号串，外层已做一次处理
        $names = [];
        foreach ($tag_names as $t) {
            $t = is_string($t) ? trim($t) : $t;
            if (!empty($t)) $names[] = $t;
        }
        if (empty($names)) return true;

        // 仅接受已存在的标签（白名单）：不自动创建，未知标签忽略
        // 一次性读取所有标签，构建 name->id 映射
        $all = $this->getAll();
        $name2id = [];
        foreach ($all as $row) {
            $name2id[$row['name']] = (int)$row['id'];
        }

        // 先删除文章的所有标签
        $this->removeFromArticle($article_id);

        $inserted = 0;
        foreach ($names as $name) {
            if (!isset($name2id[$name])) {
                // 未知标签丢弃
                continue;
            }
            $tagId = $name2id[$name];

            // 添加文章标签关联
            $query = "INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (:article_id, :tag_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':article_id', $article_id);
            $stmt->bindParam(':tag_id', $tagId);
            $stmt->execute();

            // 更新标签使用次数
            $this->id = $tagId;
            $this->incrementUseCount();
            $inserted++;
        }

        return $inserted >= 0;
    }

    /**
     * 从文章移除标签
     */
    public function removeFromArticle($article_id) {
        $query = "DELETE FROM article_tags WHERE article_id = :article_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article_id', $article_id);
        return $stmt->execute();
    }

    /**
     * 增加标签使用次数
     */
    public function incrementUseCount() {
        $query = "UPDATE " . $this->table_name . " SET use_count = use_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    /**
     * 获取文章的标签
     */
    public function getByArticle($article_id) {
        $query = "SELECT t.* FROM " . $this->table_name . " t
                  INNER JOIN article_tags at ON t.id = at.tag_id
                  WHERE at.article_id = :article_id
                  ORDER BY t.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article_id', $article_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * 获取热门标签
     */
    public function getPopular($limit = 20) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE use_count > 0 
                  ORDER BY use_count DESC, name ASC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
?>