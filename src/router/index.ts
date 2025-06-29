// 导入Vue Router的相关功能
import { createRouter, createWebHistory } from 'vue-router'
// 导入视图组件（Login和Home使用直接导入，其他使用懒加载）
import Login from '../views/Login.vue'
import Home from '../views/Home.vue'

// 定义路由配置数组
const routes = [
  {
    path: '/',                   // 根路径
    redirect: '/login'           // 自动重定向到登录页
  },
  {
    path: '/login',              // 登录页路径
    name: 'Login',               // 路由名称
    component: Login             // 对应的组件
  },
  {
    path: '/home',               // 首页路径
    name: 'Home',                // 路由名称
    component: Home,             // 对应的组件
    meta: { requiresAuth: true } // 元信息：标记此路由需要认证
  },
  // 以下是动态导入的路由（懒加载，提高首屏加载速度）
  {
    path: '/first-tour',         // 一巡页面路径
    name: 'FirstTour',           // 路由名称
    component: () => import('../views/FirstTour.vue'), // 懒加载组件
    meta: { requiresAuth: true } // 需要认证
  },
  {
    path: '/second-tour',        // 二巡页面路径
    name: 'SecondTour',          // 路由名称
    component: () => import('../views/SecondTour.vue'), // 懒加载组件
    meta: { requiresAuth: true } // 需要认证
  },
  {
    path: '/third-tour',         // 三巡页面路径
    name: 'ThirdTour',           // 路由名称
    component: () => import('../views/ThirdTour.vue'), // 懒加载组件
    meta: { requiresAuth: true } // 需要认证
  },
  {
    path: '/previous-decade',    // 前十年页面路径
    name: 'PreviousDecade',      // 路由名称
    component: () => import('../views/ThePreviousDecade.vue'), // 懒加载组件
    meta: { requiresAuth: true } // 需要认证
  },
  {
    path: '/next-decade',        // 后十年页面路径
    name: 'NextDecade',          // 路由名称
    component: () => import('../views/TheNextDecade.vue'), // 懒加载组件
    meta: { requiresAuth: true } // 需要认证
  }
]

// 创建路由实例
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL), // 使用HTML5历史模式
  routes                                              // 传入路由配置
})

// 全局路由守卫（在每次路由跳转前执行）
router.beforeEach((to, from, next) => {
  // 检查本地存储中是否有登录标记
  const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'
  
  // 检查目标路由是否需要认证
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // 如果需要认证但用户未登录，重定向到登录页
    if (!isLoggedIn) {
      next('/login')
    } else {
      next() // 已登录，允许访问
    }
  } else {
    next() // 不需要认证的路由，直接放行
  }
})

// 导出路由实例
export default router