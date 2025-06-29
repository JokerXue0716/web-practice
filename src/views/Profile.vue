<template>
  <div class="profile-container">
    <NavBar />
    
    <div class="main-content">
      <div class="content-header">
        <h1 class="page-title">个人中心</h1>
        <p class="page-subtitle">管理您的个人信息和账户设置</p>
      </div>

      <div class="content-body">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-card class="profile-card">
              <div class="profile-avatar">
                <el-avatar :size="120" :src="userInfo.avatar">
                  <el-icon><User /></el-icon>
                </el-avatar>
                <h3 class="profile-name">{{ userInfo.username }}</h3>
                <p class="profile-email">{{ userInfo.email }}</p>
                <el-tag type="primary">{{ userInfo.role }}</el-tag>
              </div>
              
              <el-divider />
              
              <div class="profile-stats">
                <div class="stat-item">
                  <div class="stat-number">{{ totalArticles }}</div>
                  <div class="stat-label">总文章数</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number">{{ publishedArticles }}</div>
                  <div class="stat-label">已发布</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number">{{ draftArticles }}</div>
                  <div class="stat-label">草稿</div>
                </div>
              </div>
            </el-card>
          </el-col>
          
          <el-col :span="16">
            <el-card class="settings-card">
              <template #header>
                <span>账户设置</span>
              </template>
              
              <el-form
                ref="profileFormRef"
                :model="profileForm"
                :rules="profileRules"
                label-width="100px"
              >
                <el-form-item label="用户名" prop="username">
                  <el-input v-model="profileForm.username" />
                </el-form-item>
                
                <el-form-item label="邮箱" prop="email">
                  <el-input v-model="profileForm.email" />
                </el-form-item>
                
                <el-form-item label="昵称" prop="nickname">
                  <el-input v-model="profileForm.nickname" />
                </el-form-item>
                
                <el-form-item label="个人简介" prop="bio">
                  <el-input
                    v-model="profileForm.bio"
                    type="textarea"
                    :rows="3"
                    placeholder="介绍一下自己..."
                  />
                </el-form-item>
                
                <el-form-item>
                  <el-button type="primary" @click="saveProfile" :loading="saving">
                    保存设置
                  </el-button>
                </el-form-item>
              </el-form>
            </el-card>
            
            <el-card class="security-card" style="margin-top: 20px;">
              <template #header>
                <span>安全设置</span>
              </template>
              
              <el-form label-width="100px">
                <el-form-item label="当前密码">
                  <el-input
                    v-model="passwordForm.oldPassword"
                    type="password"
                    show-password
                    placeholder="请输入当前密码"
                  />
                </el-form-item>
                
                <el-form-item label="新密码">
                  <el-input
                    v-model="passwordForm.newPassword"
                    type="password"
                    show-password
                    placeholder="请输入新密码"
                  />
                </el-form-item>
                
                <el-form-item label="确认密码">
                  <el-input
                    v-model="passwordForm.confirmPassword"
                    type="password"
                    show-password
                    placeholder="请再次输入新密码"
                  />
                </el-form-item>
                
                <el-form-item>
                  <el-button type="warning" @click="changePassword" :loading="changingPassword">
                    修改密码
                  </el-button>
                </el-form-item>
              </el-form>
            </el-card>
          </el-col>
        </el-row>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth.store'
import { useArticleStore } from '../stores/article.store'
import { ElMessage } from 'element-plus'
import NavBar from '../components/NavBar.vue'

const authStore = useAuthStore()
const articleStore = useArticleStore()

// 响应式数据
const profileFormRef = ref()
const saving = ref(false)
const changingPassword = ref(false)

// 用户信息
const userInfo = reactive({
  username: 'admin',
  email: 'admin@example.com',
  avatar: 'https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png',
  role: '管理员',
  nickname: '管理员',
  bio: '热爱技术，专注于前端开发和AI应用'
})

// 统计数据（动态computed）
const totalArticles = computed(() => articleStore.articles.length)
const publishedArticles = computed(() => articleStore.articles.filter(a => a.status === 'published').length)
const draftArticles = computed(() => articleStore.articles.filter(a => a.status === 'draft').length)

// 表单数据
const profileForm = reactive({
  username: '',
  email: '',
  nickname: '',
  bio: ''
})

const passwordForm = reactive({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
})

// 表单验证规则
const profileRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '用户名长度在 3 到 20 个字符', trigger: 'blur' }
  ],
  email: [
    { required: true, message: '请输入邮箱', trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
  ],
  nickname: [
    { max: 20, message: '昵称长度不能超过 20 个字符', trigger: 'blur' }
  ],
  bio: [
    { max: 200, message: '个人简介长度不能超过 200 个字符', trigger: 'blur' }
  ]
}

// 方法
const saveProfile = async () => {
  if (!profileFormRef.value) return
  try {
    await profileFormRef.value.validate()
    saving.value = true
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 1000))
    // 更新本地数据
    Object.assign(userInfo, {
      username: profileForm.username,
      email: profileForm.email,
      nickname: profileForm.nickname,
      bio: profileForm.bio
    })
    // 更新authStore和localStorage
    if (authStore.user) {
      Object.assign(authStore.user, userInfo)
    }
    // 更新localStorage user
    localStorage.setItem('user', JSON.stringify(userInfo))
    // 同步更新注册用户表（users）
    let users = JSON.parse(localStorage.getItem('users') || '[]')
    users = users.map(u =>
      u.username === userInfo.username ? { ...u, ...userInfo } : u
    )
    localStorage.setItem('users', JSON.stringify(users))
    ElMessage.success('个人信息保存成功')
  } catch (error) {
    console.error('表单验证失败:', error)
  } finally {
    saving.value = false
  }
}

const changePassword = async () => {
  if (!passwordForm.oldPassword) {
    ElMessage.warning('请输入当前密码')
    return
  }
  if (!passwordForm.newPassword) {
    ElMessage.warning('请输入新密码')
    return
  }
  if (passwordForm.newPassword !== passwordForm.confirmPassword) {
    ElMessage.error('两次输入的密码不一致')
    return
  }
  if (passwordForm.newPassword.length < 6) {
    ElMessage.warning('新密码长度不能少于6位')
    return
  }
  // 校验旧密码
  let users = JSON.parse(localStorage.getItem('users') || '[]')
  const idx = users.findIndex(u => u.username === userInfo.username)
  if (idx === -1 || users[idx].password !== passwordForm.oldPassword) {
    ElMessage.error('当前密码错误')
    return
  }
  try {
    changingPassword.value = true
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 1000))
    // 更新密码
    users[idx].password = passwordForm.newPassword
    localStorage.setItem('users', JSON.stringify(users))
    ElMessage.success('密码修改成功')
    // 清空表单
    passwordForm.oldPassword = ''
    passwordForm.newPassword = ''
    passwordForm.confirmPassword = ''
  } catch (error) {
    ElMessage.error('密码修改失败')
  } finally {
    changingPassword.value = false
  }
}

// 初始化数据
const initData = () => {
  // 优先从localStorage读取user
  const savedUser = localStorage.getItem('user')
  if (savedUser) {
    try {
      Object.assign(userInfo, JSON.parse(savedUser))
    } catch (e) {}
  } else {
    // 从认证store获取用户信息
    const currentUser = authStore.currentUser
    if (currentUser) {
      Object.assign(userInfo, currentUser)
    }
  }
  // 初始化表单数据
  Object.assign(profileForm, {
    username: userInfo.username,
    email: userInfo.email,
    nickname: userInfo.nickname || userInfo.username,
    bio: userInfo.bio || ''
  })
}

// 生命周期
onMounted(() => {
  initData()
})
</script>

<style scoped>
.profile-container {
  min-height: 100vh;
  background-color: #f5f7fa;
}

.main-content {
  padding-top: 80px;
  width: 100vw;
  margin: 0;
  padding-left: 0;
  padding-right: 0;
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

.content-header {
  margin-bottom: 24px;
  padding: 24px 2vw 0 2vw;
  width: 100%;
  box-sizing: border-box;
}

.page-title {
  font-size: 28px;
  font-weight: 600;
  color: #2c3e50;
  margin: 0 0 8px 0;
}

.page-subtitle {
  font-size: 14px;
  color: #7f8c8d;
  margin: 0;
}

.content-body {
  margin-bottom: 40px;
  width: 100vw;
  display: flex;
  justify-content: center;
  box-sizing: border-box;
}

.el-row {
  width: 100vw !important;
  margin: 0 !important;
  max-width: 1200px;
  display: flex;
  justify-content: center;
}

.el-col {
  width: 100% !important;
  padding: 0 10px;
}

.profile-card, .settings-card, .security-card {
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  width: 100%;
  min-width: 260px;
  box-sizing: border-box;
}

.profile-avatar {
  text-align: center;
  padding: 20px 0;
}

.profile-name {
  font-size: 20px;
  font-weight: 600;
  color: #2c3e50;
  margin: 16px 0 8px 0;
}

.profile-email {
  font-size: 14px;
  color: #7f8c8d;
  margin: 0 0 16px 0;
}

.profile-stats {
  display: flex;
  justify-content: space-around;
  text-align: center;
}

.stat-item {
  flex: 1;
}

.stat-number {
  font-size: 24px;
  font-weight: 600;
  color: #409eff;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;
  color: #7f8c8d;
}

@media (max-width: 1400px) {
  .el-row {
    max-width: 98vw;
  }
}

@media (max-width: 1024px) {
  .main-content, .content-header, .content-body, .el-row {
    max-width: 100vw;
  }
}

/* 响应式设计 */
@media (max-width: 768px) {
  .main-content {
    padding-left: 0;
    padding-right: 0;
  }
  .el-col {
    margin-bottom: 20px;
    padding: 0 2vw;
  }
  .profile-stats {
    flex-direction: column;
    gap: 16px;
  }
  .el-row {
    flex-direction: column;
    width: 100vw !important;
    max-width: 100vw;
  }
}
</style>
