<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 文章模型类
 */
class Article {
    private $conn;
    private $table_name = "articles";

    public $id;
    public $title;
    public $summary;
    public $content;
    public $author_id;
    public $status;
    public $view_count;
    public $like_count;
    public $created_at;
    public $updated_at;
    public $published_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * 获取用户的文章列表
     */
    public function getByAuthor($author_id, $status = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT a.*, u.username as author_name,
                         GROUP_CONCAT(t.name) as tags
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.author_id = u.id
                  LEFT JOIN article_tags at ON a.id = at.article_id
                  LEFT JOIN tags t ON at.tag_id = t.id
                  WHERE a.author_id = :author_id";
        
        if ($status) {
            $query .= " AND a.status = :status";
        }
        
        $query .= " GROUP BY a.id ORDER BY a.updated_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':author_id', $author_id, PDO::PARAM_INT);
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $articles = [];
        while ($row = $stmt->fetch()) {
            $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
            $articles[] = $row;
        }

        return $articles;
    }

    /**
     * 根据ID获取文章
     */
    public function findById($id) {
        $query = "SELECT a.*, u.username as author_name,
                         GROUP_CONCAT(t.name) as tags
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.author_id = u.id
                  LEFT JOIN article_tags at ON a.id = at.article_id
                  LEFT JOIN tags t ON at.tag_id = t.id
                  WHERE a.id = :id
                  GROUP BY a.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $this->id = $row['id'];
            $this->title = $row['title'];
            $this->summary = $row['summary'];
            $this->content = $row['content'];
            $this->author_id = $row['author_id'];
            $this->status = $row['status'];
            $this->view_count = $row['view_count'];
            $this->like_count = $row['like_count'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->published_at = $row['published_at'];
            
            $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
            return $row;
        }
        return false;
    }

    /**
     * 创建文章
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (title, summary, content, author_id, status, published_at) 
                  VALUES (:title, :summary, :content, :author_id, :status, :published_at)";

        $stmt = $this->conn->prepare($query);

        // 清理数据
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->summary = htmlspecialchars(strip_tags($this->summary));
        $this->content = htmlspecialchars($this->content);
        $this->status = htmlspecialchars(strip_tags($this->status));

        // 设置发布时间
        $published_at = ($this->status === 'published') ? date('Y-m-d H:i:s') : null;

        // 绑定参数
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':summary', $this->summary);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':author_id', $this->author_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':published_at', $published_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * 更新文章
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET title = :title, summary = :summary, content = :content, 
                      status = :status, published_at = :published_at, updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id AND author_id = :author_id";

        $stmt = $this->conn->prepare($query);

        // 清理数据
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->summary = htmlspecialchars(strip_tags($this->summary));
        $this->content = htmlspecialchars($this->content);
        $this->status = htmlspecialchars(strip_tags($this->status));

        // 设置发布时间
        $published_at = ($this->status === 'published' && !$this->published_at) ? date('Y-m-d H:i:s') : $this->published_at;

        // 绑定参数
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':summary', $this->summary);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':published_at', $published_at);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':author_id', $this->author_id);

        return $stmt->execute();
    }

    /**
     * 删除文章
     */
    public function delete() {
        // 先删除文章标签关联
        $query = "DELETE FROM article_tags WHERE article_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        // 删除文章
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND author_id = :author_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':author_id', $this->author_id);

        return $stmt->execute();
    }

    /**
     * 增加浏览次数
     */
    public function incrementViewCount() {
        $query = "UPDATE " . $this->table_name . " SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    /**
     * 搜索文章
     */
    public function search($keyword, $author_id = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT a.*, u.username as author_name,
                         GROUP_CONCAT(t.name) as tags
                  FROM " . $this->table_name . " a
                  LEFT JOIN users u ON a.author_id = u.id
                  LEFT JOIN article_tags at ON a.id = at.article_id
                  LEFT JOIN tags t ON at.tag_id = t.id
                  WHERE (a.title LIKE :keyword OR a.content LIKE :keyword)";
        
        if ($author_id) {
            $query .= " AND a.author_id = :author_id";
        }
        
        $query .= " GROUP BY a.id ORDER BY a.updated_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $searchKeyword = '%' . $keyword . '%';
        $stmt->bindParam(':keyword', $searchKeyword);
        if ($author_id) {
            $stmt->bindParam(':author_id', $author_id, PDO::PARAM_INT);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $articles = [];
        while ($row = $stmt->fetch()) {
            $row['tags'] = $row['tags'] ? explode(',', $row['tags']) : [];
            $articles[] = $row;
        }

        return $articles;
    }

    /**
     * 获取文章统计信息
     */
    public function getStats($author_id) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(view_count) as total_views,
                    SUM(like_count) as total_likes
                  FROM " . $this->table_name . " 
                  WHERE author_id = :author_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':author_id', $author_id);
        $stmt->execute();

        return $stmt->fetch();
    }
}
?>