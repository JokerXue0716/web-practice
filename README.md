# 🎯 智能井字棋游戏

一个基于 Vue 3 + TypeScript + Minimax算法开发的智能井字棋游戏，具有完整的AI对战功能和现代化UI设计。

![游戏截图](https://img.shields.io/badge/Vue-3.0-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-007ACC?style=for-the-badge&logo=typescript&logoColor=white)
![Pinia](https://img.shields.io/badge/Pinia-FFD859?style=for-the-badge&logo=pinia&logoColor=black)

## ✨ 项目特色

- 🤖 **智能AI对手** - 基于Minimax算法，三种难度可选
- 📊 **数据统计** - 实时胜负记录和胜率分析
- 🎨 **现代化UI** - 渐变背景、毛玻璃效果、流畅动画
- 📱 **响应式设计** - 完美适配桌面端和移动端
- 💾 **数据持久化** - 自动保存游戏统计数据

## 🚀 在线演示

访问 [在线演示](http://localhost:8084/) 体验游戏

## 🛠️ 技术栈

- **前端框架**: Vue 3 (Composition API)
- **编程语言**: TypeScript
- **状态管理**: Pinia
- **样式技术**: CSS3 (Grid, Flexbox, 动画)
- **AI算法**: Minimax算法
- **构建工具**: Vue CLI
- **数据存储**: LocalStorage API

## 📦 快速开始

### 环境要求
- Node.js >= 14.0.0
- npm >= 6.0.0

### 安装依赖
```bash
npm install
```

### 开发模式
```bash
npm run serve
```

### 生产构建
```bash
npm run build
```

## 🎮 游戏玩法

1. **选择难度**: 简单、中等、困难三种AI难度
2. **开始游戏**: 点击"开始游戏"按钮
3. **玩家落子**: 你是X，点击空格子落子
4. **AI回应**: AI是O，会自动选择最佳位置
5. **查看统计**: 右侧面板显示详细的游戏统计

## 🧠 AI算法详解

### Minimax算法
游戏使用经典的Minimax算法实现AI决策：

- **简单模式**: 随机选择可用位置
- **中等模式**: 70%使用最优策略，30%随机选择
- **困难模式**: 完全使用Minimax算法，几乎无法战胜

### 算法特点
- 递归搜索所有可能的游戏状态
- 评估每个位置的得分
- 选择对AI最有利的走法
- 时间复杂度: O(b^d)，其中b是分支因子，d是搜索深度

## 📁 项目结构

```
tic-tac-toe/
├── src/
│   ├── components/          # Vue组件
│   │   └── GameBoard.vue    # 游戏棋盘组件
│   ├── stores/              # Pinia状态管理
│   │   └── gameStore.ts     # 游戏状态存储
│   ├── composables/         # 组合式函数
│   │   └── useGameLogic.ts  # 游戏逻辑和AI算法
│   ├── App.vue              # 根组件
│   └── main.ts              # 应用入口
├── public/                  # 静态资源
├── README.md               # 项目说明
└── PROJECT_DOCUMENTATION.md # 详细技术文档
```

## 🎨 UI设计特色

### 布局设计
- **左侧**: 游戏棋盘主区域
- **右上**: 游戏控制、AI难度、玩家状态三个面板
- **右下**: 游戏统计、胜率统计两个面板

### 视觉效果
- **渐变背景**: 多层次色彩渐变
- **毛玻璃效果**: backdrop-filter模糊背景
- **动画交互**: 按钮悬停、落子动画、进度条动画
- **响应式布局**: CSS Grid + Flexbox

## 📊 功能模块

### 游戏控制
- 开始/重新开始游戏
- 重置游戏状态
- 游戏状态显示

### AI难度选择
- 简单: 随机策略
- 中等: 混合策略
- 困难: 最优策略

### 数据统计
- 总游戏局数
- 玩家胜利次数
- AI胜利次数
- 平局次数
- 胜率可视化

## 🔧 开发亮点

### 代码质量
- **TypeScript**: 完整的类型安全
- **组件化**: 高度模块化的组件设计
- **状态管理**: 集中式状态管理
- **代码注释**: 详细的代码注释

### 性能优化
- **计算属性**: 自动缓存计算结果
- **事件处理**: 高效的事件绑定
- **内存管理**: 合理的组件生命周期

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 打开 Pull Request

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情

## 👨‍💻 作者

- **你的名字** - [GitHub](https://github.com/yourusername)

## 🙏 致谢

- Vue.js 团队提供的优秀框架
- Pinia 团队的状态管理解决方案
- 所有为开源社区做出贡献的开发者们

---

⭐ 如果这个项目对你有帮助，请给它一个星标！