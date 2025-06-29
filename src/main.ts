// 从 'vue' 包中导入 createApp 函数，创建 Vue 应用实例
import { createApp } from 'vue';

// 导入根组件 App.vue
import App from './App.vue';

// 导入路由配置（router）
import router from './router';

// 导入 ElementPlus UI 库
import ElementPlus from 'element-plus';

// 导入 ElementPlus 的 CSS 样式文件
import 'element-plus/dist/index.css';

// 创建 Vue 应用实例，并将根组件 App 传递给 createApp 函数
const app = createApp(App);

// 使用 Vue 应用实例中的路由配置
app.use(router);

// 使用 ElementPlus UI 库
app.use(ElementPlus);

// 将 Vue 应用挂载到 HTML 中的 id 为 'app' 的 DOM 元素上
app.mount('#app');
