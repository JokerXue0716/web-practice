-- AI博客系统初始化数据脚本
USE ai_blog_system;

-- 插入默认管理员用户
INSERT INTO users (username, password, email, nickname, avatar, bio, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', '管理员', 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png', '热爱技术，专注于前端开发和AI应用', 'admin');

-- 插入常用标签
INSERT INTO tags (name, color, use_count) VALUES 
('Vue', '#4FC08D', 0),
('React', '#61DAFB', 0),
('JavaScript', '#F7DF1E', 0),
('TypeScript', '#3178C6', 0),
('Node.js', '#339933', 0),
('Python', '#3776AB', 0),
('Java', '#ED8B00', 0),
('前端', '#409EFF', 0),
('后端', '#67C23A', 0),
('AI', '#E6A23C', 0),
('安全', '#F56C6C', 0),
('Web', '#909399', 0),
('移动端', '#606266', 0),
('数据库', '#303133', 0),
('云服务', '#909399', 0);

-- 插入系统配置
INSERT INTO system_configs (config_key, config_value, description) VALUES 
('site_title', 'AI助手增强博客系统', '网站标题'),
('site_description', '智能写作·安全检测·内容优化', '网站描述'),
('articles_per_page', '10', '每页文章数量'),
('max_upload_size', '10485760', '最大上传文件大小(字节)'),
('ai_api_enabled', '1', 'AI功能是否启用'),
('openai_api_key', '', 'OpenAI API密钥'),
('openai_model', 'gpt-3.5-turbo', 'OpenAI模型名称'),
('registration_enabled', '1', '是否允许用户注册'),
('comment_enabled', '0', '是否启用评论功能'),
('email_verification', '0', '是否需要邮箱验证');

-- 插入示例文章（可选）
INSERT INTO articles (title, summary, content, author_id, status, published_at) VALUES 
('欢迎使用AI博客系统', '这是一个功能强大的AI增强博客系统，支持智能写作、代码检测等功能。', '# 欢迎使用AI博客系统\n\n这是一个集成了AI功能的现代化博客系统，具有以下特色功能：\n\n## 主要功能\n\n### 1. 智能写作助手\n- AI润色：优化文章表达和语言风格\n- 自动摘要：智能生成文章摘要\n- 标签推荐：根据内容自动推荐合适标签\n\n### 2. 代码质量检测\n- 语法检查：检测代码中的语法错误\n- 安全扫描：识别潜在的安全问题\n- 最佳实践建议：提供代码优化建议\n\n### 3. 现代化界面\n- 响应式设计：完美适配各种设备\n- 暗色主题：保护眼睛，提升体验\n- 实时预览：Markdown实时渲染\n\n## 开始使用\n\n1. 点击"写新文章"开始创作\n2. 使用AI功能增强您的内容\n3. 发布并分享您的技术见解\n\n祝您使用愉快！', 1, 'published', NOW());