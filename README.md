# AI助手增强博客系统

一个集成了AI功能的现代化博客系统，支持智能写作、代码检测、自动摘要等功能。

## 系统架构

- **前端**: Vue 3 + Element Plus + Vite
- **后端**: PHP 8+ + MySQL 8+
- **认证**: JWT Token
- **AI功能**: 模拟AI接口（可扩展为真实AI服务）

## 功能特性

### 用户功能
- ✅ 用户注册/登录
- ✅ 个人资料管理
- ✅ 密码修改
- ✅ 头像设置

### 文章管理
- ✅ 文章创建/编辑/删除
- ✅ 草稿/发布状态管理
- ✅ Markdown编辑器
- ✅ 标签系统
- ✅ 文章搜索
- ✅ 文章统计

### AI增强功能
- ✅ AI润色：优化文章表达
- ✅ 代码检测：检查代码质量和安全性
- ✅ 自动摘要：智能生成文章摘要
- ✅ 标签推荐：根据内容推荐合适标签

## 安装部署

### 环境要求

- PHP 8.0+
- MySQL 8.0+
- Apache/Nginx
- Node.js 16+ (用于前端开发)

### 1. 数据库设置

```bash
# 1. 创建数据库
mysql -u root -p < database/create_database.sql

# 2. 初始化数据
mysql -u root -p < database/init_data.sql
```

### 2. 后端配置

```bash
# 1. 修改数据库配置
# 编辑 backend/config/database.php
# 修改数据库连接信息

# 2. 配置Web服务器
# 将 backend 目录设置为Web根目录
# 或者配置虚拟主机指向 backend 目录

# 3. 设置权限
chmod -R 755 backend/
chmod -R 777 backend/logs/ # 如果有日志目录
```

### 3. 前端配置

```bash
# 1. 进入前端目录
cd ai-blog-frontend

# 2. 安装依赖
npm install

# 3. 修改API配置
# 编辑 src/utils/api.js (如果存在)
# 设置后端API地址

# 4. 开发模式运行
npm run dev

# 5. 生产构建
npm run build
```

## API接口文档

### 认证接口

#### 用户登录
```
POST /auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "123456"
}
```

#### 用户注册
```
POST /auth/register
Content-Type: application/json

{
  "username": "newuser",
  "password": "password123",
  "email": "user@example.com",
  "nickname": "昵称"
}
```

### 用户接口

#### 获取用户资料
```
GET /user/profile
Authorization: Bearer {token}
```

#### 更新用户资料
```
PUT /user/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "username": "newusername",
  "email": "new@example.com",
  "nickname": "新昵称",
  "bio": "个人简介"
}
```

#### 修改密码
```
PUT /user/password
Authorization: Bearer {token}
Content-Type: application/json

{
  "oldPassword": "oldpass",
  "newPassword": "newpass",
  "confirmPassword": "newpass"
}
```

### 文章接口

#### 获取文章列表
```
GET /articles?page=1&limit=10&status=published&search=关键词
Authorization: Bearer {token}
```

#### 创建文章
```
POST /articles
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "文章标题",
  "summary": "文章摘要",
  "content": "文章内容（Markdown格式）",
  "tags": ["Vue", "前端"],
  "status": "published"
}
```

#### 获取文章详情
```
GET /articles/detail?id=1
Authorization: Bearer {token}
```

#### 更新文章
```
PUT /articles/detail?id=1
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "更新的标题",
  "content": "更新的内容",
  "status": "published"
}
```

#### 删除文章
```
DELETE /articles/detail?id=1
Authorization: Bearer {token}
```

### AI功能接口

#### AI润色
```
POST /ai/polish
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "需要润色的文本内容"
}
```

#### 代码检测
```
POST /ai/code-check
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "包含代码的文章内容"
}
```

#### 自动摘要
```
POST /ai/summary
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "文章完整内容"
}
```

#### 标签推荐
```
POST /ai/tag-recommend
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "文章内容"
}
```

### 标签接口

#### 获取标签列表
```
GET /tags?type=popular&limit=20
Authorization: Bearer {token}
```

## 数据库表结构

### 用户表 (users)
- id: 主键
- username: 用户名
- password: 加密密码
- email: 邮箱
- nickname: 昵称
- avatar: 头像URL
- bio: 个人简介
- role: 用户角色 (admin/user)
- status: 账户状态
- created_at/updated_at: 时间戳

### 文章表 (articles)
- id: 主键
- title: 标题
- summary: 摘要
- content: 内容
- author_id: 作者ID
- status: 状态 (draft/published)
- view_count: 浏览次数
- like_count: 点赞次数
- created_at/updated_at/published_at: 时间戳

### 标签表 (tags)
- id: 主键
- name: 标签名
- color: 标签颜色
- use_count: 使用次数
- created_at: 创建时间

### 文章标签关联表 (article_tags)
- id: 主键
- article_id: 文章ID
- tag_id: 标签ID
- created_at: 创建时间

### AI使用记录表 (ai_usage_logs)
- id: 主键
- user_id: 用户ID
- feature_type: AI功能类型
- input_content: 输入内容
- output_content: 输出内容
- article_id: 关联文章ID
- created_at: 使用时间

### 系统配置表 (system_configs)
- id: 主键
- config_key: 配置键
- config_value: 配置值
- description: 配置描述
- created_at/updated_at: 时间戳

## 默认账户

- 用户名: `admin`
- 密码: `123456`
- 角色: 管理员

## 开发说明

### 前端开发
```bash
cd ai-blog-frontend
npm run dev
```

### 后端开发
- 后端使用原生PHP开发，无需额外构建步骤
- 修改代码后直接刷新浏览器即可看到效果
- 建议开启PHP错误显示用于调试

### AI功能扩展

当前AI功能使用模拟数据，如需接入真实AI服务：

1. 修改 `backend/api/ai/` 目录下的相关文件
2. 集成OpenAI API或其他AI服务
3. 在 `system_configs` 表中配置API密钥

### 安全注意事项

1. 修改JWT密钥：编辑 `backend/utils/JWT.php` 中的 `$secret_key`
2. 配置HTTPS：生产环境必须使用HTTPS
3. 数据库安全：使用强密码，限制数据库访问权限
4. 文件权限：合理设置文件和目录权限
5. 输入验证：所有用户输入都经过验证和过滤

## 故障排除

### 常见问题

1. **数据库连接失败**
   - 检查数据库配置信息
   - 确认数据库服务已启动
   - 验证用户权限

2. **API请求失败**
   - 检查跨域配置
   - 确认.htaccess文件生效
   - 查看PHP错误日志

3. **前端无法访问后端**
   - 检查API基础URL配置
   - 确认后端服务正常运行
   - 验证网络连接

4. **JWT认证失败**
   - 检查token是否正确传递
   - 确认token未过期
   - 验证JWT密钥配置

## 技术支持

如有问题，请检查：
1. PHP错误日志
2. 浏览器开发者工具
3. 数据库连接状态
4. 文件权限设置

## 许可证

MIT License

## 更新日志

### v1.0.0 (2024-01-01)
- 初始版本发布
- 基础用户管理功能
- 文章CRUD操作
- AI功能模拟实现
- 标签系统
- JWT认证