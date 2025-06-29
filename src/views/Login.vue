<template>
  <div class="login-container">
    <div class="login-background">
      <div class="ai-particles">
        <div class="particle" v-for="i in 20" :key="i"></div>
      </div>
    </div>
    
    <div class="login-card">
      <div class="login-header">
        <div class="logo-section">
          <div class="ai-logo">
            <i class="el-icon-cpu"></i>
          </div>
          <h1 class="app-title">AI助手增强博客系统</h1>
          <p class="app-subtitle">智能写作 · 安全检测 · 内容优化</p>
        </div>
      </div>

      <el-tabs v-model="activeTab" stretch>
        <el-tab-pane label="登录" name="login">
          <el-form
            ref="loginFormRef"
            :model="loginForm"
            :rules="loginRules"
            class="login-form"
            @submit.prevent="handleLogin"
          >
            <el-form-item prop="username">
              <el-input
                v-model="loginForm.username"
                placeholder="请输入用户名"
                size="large"
                prefix-icon="User"
                clearable
              />
            </el-form-item>

            <el-form-item prop="password">
              <el-input
                v-model="loginForm.password"
                type="password"
                placeholder="请输入密码"
                size="large"
                prefix-icon="Lock"
                show-password
                clearable
              />
            </el-form-item>

            <el-form-item>
              <el-button
                type="primary"
                size="large"
                class="login-button"
                :loading="loading"
                @click="handleLogin"
                style="width: 100%"
              >
                {{ loading ? '登录中...' : '登录' }}
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
        <el-tab-pane label="注册" name="register">
          <el-form
            ref="registerFormRef"
            :model="registerForm"
            :rules="registerRules"
            class="login-form"
            @submit.prevent="handleRegister"
          >
            <el-form-item>
              <el-row :gutter="12">
                <el-col :span="12">
                  <el-form-item prop="username">
                    <el-input
                      v-model="registerForm.username"
                      placeholder="请输入用户名"
                      size="large"
                      prefix-icon="User"
                      clearable
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item prop="email">
                    <el-input
                      v-model="registerForm.email"
                      placeholder="请输入邮箱"
                      size="large"
                      prefix-icon="Message"
                      clearable
                    />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-form-item>
            <el-form-item>
              <el-row :gutter="12">
                <el-col :span="12">
                  <el-form-item prop="password">
                    <el-input
                      v-model="registerForm.password"
                      type="password"
                      placeholder="请输入密码"
                      size="large"
                      prefix-icon="Lock"
                      show-password
                      clearable
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item prop="confirmPassword">
                    <el-input
                      v-model="registerForm.confirmPassword"
                      type="password"
                      placeholder="请再次输入密码"
                      size="large"
                      prefix-icon="Lock"
                      show-password
                      clearable
                    />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-form-item>
            <el-form-item>
              <el-button
                type="success"
                size="large"
                class="login-button"
                :loading="registering"
                @click="handleRegister"
                style="width: 100%"
              >
                {{ registering ? '注册中...' : '注册' }}
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>

      <div class="features-preview">
        <h3>AI功能预览</h3>
        <div class="feature-grid">
          <div class="feature-item">
            <i class="el-icon-edit-outline"></i>
            <span>智能文章润色</span>
          </div>
          <div class="feature-item">
            <i class="el-icon-warning"></i>
            <span>代码漏洞检测</span>
          </div>
          <div class="feature-item">
            <i class="el-icon-document"></i>
            <span>自动生成摘要</span>
          </div>
          <div class="feature-item">
            <i class="el-icon-collection-tag"></i>
            <span>智能标签推荐</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.store'
import { ElMessage } from 'element-plus'

const router = useRouter()
const authStore = useAuthStore()

const activeTab = ref('login')

// 登录表单
const loginFormRef = ref()
const loading = ref(false)
const loginForm = reactive({
  username: '',
  password: ''
})
const loginRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '用户名长度在 3 到 20 个字符', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, max: 20, message: '密码长度在 6 到 20 个字符', trigger: 'blur' }
  ]
}

// 注册表单
const registerFormRef = ref()
const registering = ref(false)
const registerForm = reactive({
  username: '',
  email: '',
  password: '',
  confirmPassword: ''
})
const registerRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '用户名长度在 3 到 20 个字符', trigger: 'blur' }
  ],
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, max: 20, message: '密码长度在 6 到 20 个字符', trigger: 'blur' }
  ],
  confirmPassword: [
    { required: true, message: '请再次输入密码', trigger: 'blur' },
    { validator: (rule, value, callback) => {
      if (value !== registerForm.password) {
        callback(new Error('两次输入的密码不一致'))
      } else {
        callback()
      }
    }, trigger: 'blur' }
  ]
}

// 登录处理
const handleLogin = async () => {
  if (!loginFormRef.value) return
  try {
    await loginFormRef.value.validate()
    loading.value = true
    const result = await authStore.login(loginForm)
    if (result.success) {
      router.push('/')
    }
  } catch (error) {
    // 验证失败
  } finally {
    loading.value = false
  }
}

// 注册处理（本地模拟，注册后自动登录）
const handleRegister = async () => {
  if (!registerFormRef.value) return
  try {
    await registerFormRef.value.validate()
    registering.value = true
    // 模拟注册逻辑：保存到localStorage
    const users = JSON.parse(localStorage.getItem('users') || '[]')
    if (users.find(u => u.username === registerForm.username)) {
      ElMessage.error('用户名已存在')
      registering.value = false
      return
    }
    const newUser = {
      username: registerForm.username,
      email: registerForm.email,
      password: registerForm.password,
      avatar: 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png',
      role: 'user'
    }
    users.push(newUser)
    localStorage.setItem('users', JSON.stringify(users))
    // 自动登录
    await authStore.login({ username: newUser.username, password: newUser.password })
    ElMessage.success('注册成功，已自动登录')
    router.push('/')
  } catch (error) {
    // 验证失败
  } finally {
    registering.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  width: 100vw;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

.login-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
}

.ai-particles {
  position: absolute;
  width: 100%;
  height: 100%;
}

.particle {
  position: absolute;
  width: 4px;
  height: 4px;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 50%;
  animation: float 6s ease-in-out infinite;
}

.particle:nth-child(odd) {
  animation-delay: 0s;
}

.particle:nth-child(even) {
  animation-delay: 3s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
    opacity: 0.6;
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
    opacity: 1;
  }
}

.login-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 56px 56px 40px 56px;
  width: 100%;
  max-width: 560px;
  min-width: 340px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-section {
  margin-bottom: 20px;
}

.ai-logo {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-size: 40px;
  color: white;
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.app-title {
  font-size: 28px;
  font-weight: 700;
  color: #2c3e50;
  margin: 0 0 10px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.app-subtitle {
  font-size: 16px;
  color: #7f8c8d;
  margin: 0;
  font-weight: 400;
}

.login-form {
  margin-bottom: 10px;
}

.login-button {
  width: 100%;
  height: 50px;
  font-size: 16px;
  font-weight: 600;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  border-radius: 10px;
  transition: all 0.3s ease;
}

.login-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.features-preview {
  text-align: center;
  margin-top: 24px;
}

.features-preview h3 {
  font-size: 18px;
  color: #2c3e50;
  margin-bottom: 20px;
  font-weight: 600;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.feature-item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 10px;
  color: #667eea;
  font-weight: 500;
  transition: all 0.3s ease;
}

.feature-item:hover {
  background: rgba(102, 126, 234, 0.2);
  transform: translateY(-2px);
}

.feature-item i {
  margin-right: 8px;
  font-size: 18px;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .login-card {
    margin: 20px;
    padding: 24px 6px;
    max-width: 98vw;
    min-width: unset;
  }
  .app-title {
    font-size: 24px;
  }
  .feature-grid {
    grid-template-columns: 1fr;
  }
  .el-col {
    max-width: 100% !important;
    flex: 0 0 100% !important;
  }
}

/* 粒子位置随机化 */
.particle:nth-child(1) { left: 10%; animation-delay: 0s; }
.particle:nth-child(2) { left: 20%; animation-delay: 1s; }
.particle:nth-child(3) { left: 30%; animation-delay: 2s; }
.particle:nth-child(4) { left: 40%; animation-delay: 3s; }
.particle:nth-child(5) { left: 50%; animation-delay: 4s; }
.particle:nth-child(6) { left: 60%; animation-delay: 5s; }
.particle:nth-child(7) { left: 70%; animation-delay: 0s; }
.particle:nth-child(8) { left: 80%; animation-delay: 1s; }
.particle:nth-child(9) { left: 90%; animation-delay: 2s; }
.particle:nth-child(10) { left: 15%; animation-delay: 3s; }
.particle:nth-child(11) { left: 25%; animation-delay: 4s; }
.particle:nth-child(12) { left: 35%; animation-delay: 5s; }
.particle:nth-child(13) { left: 45%; animation-delay: 0s; }
.particle:nth-child(14) { left: 55%; animation-delay: 1s; }
.particle:nth-child(15) { left: 65%; animation-delay: 2s; }
.particle:nth-child(16) { left: 75%; animation-delay: 3s; }
.particle:nth-child(17) { left: 85%; animation-delay: 4s; }
.particle:nth-child(18) { left: 95%; animation-delay: 5s; }
.particle:nth-child(19) { left: 5%; animation-delay: 0s; }
.particle:nth-child(20) { left: 85%; animation-delay: 1s; }
</style>
