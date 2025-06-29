import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'

export const useAuthStore = defineStore('auth', () => {
  // 状态
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  // 计算属性
  const isAuthenticated = computed(() => !!token.value)
  const currentUser = computed(() => user.value)

  // 登录方法
  const login = async (credentials) => {
    try {
      // 模拟API调用 - 实际项目中这里会调用真实的登录API
      const response = await mockLoginAPI(credentials)
      
      if (response.success) {
        token.value = response.token
        user.value = response.user
        localStorage.setItem('token', response.token)
        localStorage.setItem('user', JSON.stringify(response.user))
        
        ElMessage.success('登录成功！')
        return { success: true }
      } else {
        ElMessage.error(response.message || '登录失败')
        return { success: false, message: response.message }
      }
    } catch (error) {
      ElMessage.error('登录失败，请稍后重试')
      return { success: false, message: '网络错误' }
    }
  }

  // 登出方法
  const logout = () => {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    ElMessage.success('已退出登录')
  }

  // 初始化用户信息
  const initUser = () => {
    const savedUser = localStorage.getItem('user')
    if (savedUser && token.value) {
      try {
        user.value = JSON.parse(savedUser)
      } catch (error) {
        console.error('解析用户信息失败:', error)
        logout()
      }
    }
  }

  // 模拟登录API
  const mockLoginAPI = async (credentials) => {
    // 模拟网络延迟
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // 先查找localStorage注册用户
    const users = JSON.parse(localStorage.getItem('users') || '[]')
    const found = users.find(u => u.username === credentials.username && u.password === credentials.password)
    if (found) {
      return {
        success: true,
        token: 'mock-jwt-token-' + Date.now(),
        user: {
          id: found.id || Date.now(),
          username: found.username,
          email: found.email,
          avatar: found.avatar,
          role: found.role || 'user'
        }
      }
    }
    // 默认admin账号
    if (credentials.username === 'admin' && credentials.password === '123456') {
      return {
        success: true,
        token: 'mock-jwt-token-' + Date.now(),
        user: {
          id: 1,
          username: 'admin',
          email: 'admin@example.com',
          avatar: 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png',
          role: 'admin'
        }
      }
    }
    return {
      success: false,
      message: '用户名或密码错误'
    }
  }

  // 初始化
  initUser()

  return {
    user,
    token,
    isAuthenticated,
    currentUser,
    login,
    logout,
    initUser
  }
})
