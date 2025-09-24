# AI助手增强博客系统 - 项目结构说明

## 📁 项目目录结构

```
my_ai_blog/
├── 📁 ai-blog-frontend/          # Vue.js 前端应用
│   ├── 📁 src/
│   │   ├── 📁 components/        # Vue组件
│   │   ├── 📁 views/            # 页面视图
│   │   ├── 📁 stores/           # Pinia状态管理
│   │   ├── 📁 router/           # Vue Router路由
│   │   ├── 📁 utils/            # 工具函数
│   │   └── 📁 assets/           # 静态资源
│   ├── 📄 package.json          # 前端依赖配置
│   └── 📄 vite.config.js        # Vite构建配置
│
├── 📁 backend/                   # PHP 后端API
│   ├── 📁 api/                  # API接口
│   │   ├── 📁 auth/             # 认证相关接口
│   │   │   ├── 📄 login.php     # 用户登录
│   │   │   └── 📄 register.php  # 用户注册
│   │   ├── 📁 user/             # 用户相关接口
│   │   │   ├── 📄 profile.php   # 用户资料
│   │   │   └── 📄 password.php  # 修改密码
│   │   ├── 📁 articles/         # 文章相关接口
│   │   │   ├── 📄 index.php     # 文章列表
│   │   │   └── 📄 detail.php    # 文章详情
│   │   ├── 📁 tags/             # 标签相关接口
│   │   │   └── 📄 index.php     # 标签管理
│   │   ├── 📁 ai/               # AI功能接口
│   │   │   ├── 📄 polish.php    # AI润色
│   │   │   ├── 📄 code-check.php # 代码检测
│   │   │   ├── 📄 summary.php   # 自动摘要
│   │   │   └── 📄 tag-recommend.php # 标签推荐
│   │   └── 📁 system/           # 系统接口
│   │       └── 📄 status.php    # 系统状态检查
│   ├── 📁 config/               # 配置文件
│   │   ├── 📄 database.php      # 数据库配置
│   │   ├── 📄 .env.example      # 环境变量示例
│   │   └── 📄 .env              # 环境变量配置（部署后生成）
│   ├── 📁 models/               # 数据模型
│   │   ├── 📄 User.php          # 用户模型
│   │   ├── 📄 Article.php       # 文章模型
│   │   └── 📄 Tag.php           # 标签模型
│   ├── 📁 utils/                # 工具类
│   │   ├── 📄 Response.php      # API响应处理
│   │   ├── 📄 JWT.php           # JWT认证工具
│   │   ├── 📄 Validator.php     # 请求验证工具
│   │   └── 📄 Env.php           # 环境变量加载
│   ├── 📄 index.php             # API统一入口
│   └── 📄 .htaccess             # Apache重写规则
│
├── 📁 database/                  # 数据库相关
│   ├── 📄 create_database.sql   # 数据库创建脚本
│   └── 📄 init_data.sql         # 初始化数据脚本
│
├── 📄 README.md                  # 项目说明文档
├── 📄 PROJECT_STRUCTURE.md       # 项目结构说明（本文件）
├── 📄 install.bat               # 快速安装脚本
└── 📄 deploy.bat                # 一键部署脚本
```

## 🗄️ 数据库表结构

### 1. users (用户表)
- `id` - 用户ID（主键）
- `username` - 用户名（唯一）
- `password` - 加密密码
- `email` - 邮箱（唯一）
- `nickname` - 昵称
- `avatar` - 头像URL
- `bio` - 个人简介
- `role` - 用户角色（admin/user）
- `created_at` - 创建时间
- `updated_at` - 更新时间
- `status` - 账户状态

### 2. articles (文章表)
- `id` - 文章ID（主键）
- `title` - 文章标题
- `summary` - 文章摘要
- `content` - 文章内容（Markdown）
- `author_id` - 作者ID（外键）
- `status` - 文章状态（draft/published）
- `view_count` - 浏览次数
- `like_count` - 点赞次数
- `created_at` - 创建时间
- `updated_at` - 更新时间
- `published_at` - 发布时间

### 3. tags (标签表)
- `id` - 标签ID（主键）
- `name` - 标签名称（唯一）
- `color` - 标签颜色
- `created_at` - 创建时间
- `use_count` - 使用次数

### 4. article_tags (文章标签关联表)
- `id` - 关联ID（主键）
- `article_id` - 文章ID（外键）
- `tag_id` - 标签ID（外键）
- `created_at` - 创建时间

### 5. ai_usage_logs (AI使用记录表)
- `id` - 记录ID（主键）
- `user_id` - 用户ID（外键）
- `feature_type` - AI功能类型
- `input_content` - 输入内容
- `output_content` - 输出内容
- `article_id` - 关联文章ID（可选）
- `created_at` - 使用时间

### 6. system_configs (系统配置表)
- `id` - 配置ID（主键）
- `config_key` - 配置键（唯一）
- `config_value` - 配置值
- `description` - 配置描述
- `created_at` - 创建时间
- `updated_at` - 更新时间

## 🔧 技术栈

### 前端技术
- **Vue.js 3** - 渐进式JavaScript框架
- **Vite** - 现代化构建工具
- **Element Plus** - Vue 3 UI组件库
- **Pinia** - Vue状态管理
- **Vue Router** - 路由管理
- **Axios/Fetch** - HTTP请求

### 后端技术
- **PHP 7.4+** - 服务端脚本语言
- **MySQL 8.0+** - 关系型数据库
- **PDO** - PHP数据库抽象层
- **JWT** - JSON Web Token认证
- **RESTful API** - API设计风格

### 开发工具
- **PHPStudy** - PHP集成开发环境
- **Node.js** - JavaScript运行环境
- **npm** - 包管理器

## 🚀 部署流程

### 方式一：一键部署（推荐）
```bash
# 运行一键部署脚本
deploy.bat
```

### 方式二：手动部署
1. **环境准备**
   - 安装PHP 7.4+
   - 安装MySQL 8.0+
   - 安装Node.js 16+

2. **数据库初始化**
   ```bash
   mysql -u root -p < database/create_database.sql
   mysql -u root -p < database/init_data.sql
   ```

3. **后端配置**
   ```bash
   # 复制环境配置文件
   copy backend\config\.env.example backend\config\.env
   # 编辑.env文件，配置数据库连接信息
   ```

4. **前端部署**
   ```bash
   cd ai-blog-frontend
   npm install
   npm run dev
   ```

## 🔐 默认账户

- **管理员账户**
  - 用户名: `admin`
  - 密码: `123456`
  - 邮箱: `admin@example.com`

## 📡 API接口文档

### 认证接口
- `POST /api/auth/login` - 用户登录
- `POST /api/auth/register` - 用户注册

### 用户接口
- `GET /api/user/profile` - 获取用户资料
- `PUT /api/user/profile` - 更新用户资料
- `PUT /api/user/password` - 修改密码

### 文章接口
- `GET /api/articles` - 获取文章列表
- `GET /api/articles/detail?id={id}` - 获取文章详情
- `POST /api/articles` - 创建文章
- `PUT /api/articles/detail?id={id}` - 更新文章
- `DELETE /api/articles/detail?id={id}` - 删除文章

### 标签接口
- `GET /api/tags` - 获取标签列表

### AI功能接口
- `POST /api/ai/polish` - AI润色
- `POST /api/ai/code-check` - 代码检测
- `POST /api/ai/summary` - 自动摘要
- `POST /api/ai/tag-recommend` - 标签推荐

### 系统接口
- `GET /api/system/status` - 系统状态检查

## 🛠️ 开发指南

### 前端开发
```bash
cd ai-blog-frontend
npm run dev      # 启动开发服务器
npm run build    # 构建生产版本
npm run preview  # 预览生产版本
```

### 后端开发
- 将`backend`目录配置为Web服务器根目录
- 确保PHP扩展：`pdo`, `pdo_mysql`, `json`, `mbstring`
- 开启URL重写功能

### 数据库管理
- 使用phpMyAdmin或其他数据库管理工具
- 定期备份数据库
- 监控数据库性能

## 🔍 故障排除

### 常见问题
1. **数据库连接失败**
   - 检查MySQL服务是否启动
   - 验证数据库连接信息
   - 确认数据库用户权限

2. **前端无法访问后端**
   - 检查跨域配置
   - 验证API地址配置
   - 确认Web服务器运行状态

3. **JWT认证失败**
   - 检查JWT密钥配置
   - 验证token格式
   - 确认token未过期

### 日志查看
- 前端：浏览器开发者工具Console
- 后端：PHP错误日志
- 数据库：MySQL错误日志

## 📞 技术支持

如遇到问题，请检查：
1. 系统状态：访问 `/api/system/status`
2. 错误日志：查看相关日志文件
3. 配置文件：验证配置是否正确

---

*最后更新时间：2025年9月17日*