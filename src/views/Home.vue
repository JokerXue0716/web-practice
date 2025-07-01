<template>
  <div class="home-container">
    <NavBar />
    
    <div class="main-content">
      <div class="content-header">
        <div class="header-left">
          <h1 class="page-title">我的文章</h1>
          <p class="page-subtitle">管理您的技术博客文章</p>
        </div>
        <div class="header-right">
          <el-button type="primary" @click="goToEditor">
            <el-icon><Plus /></el-icon>
            写新文章
          </el-button>
        </div>
      </div>

      <div class="content-body">
        <el-row :gutter="20">
          <el-col :span="24">
            <el-card class="articles-card">
              <template #header>
                <div class="card-header">
                  <span>文章列表</span>
                  <div class="header-actions">
                    <el-input
                      v-model="searchKeyword"
                      placeholder="搜索文章..."
                      style="width: 200px"
                      clearable
                    >
                      <template #prefix>
                        <el-icon><Search /></el-icon>
                      </template>
                    </el-input>
                    <el-select v-model="filterStatus" placeholder="状态筛选" style="width: 120px">
                      <el-option label="全部" value="" />
                      <el-option label="已发布" value="published" />
                      <el-option label="草稿" value="draft" />
                    </el-select>
                  </div>
                </div>
              </template>

              <el-table
                :data="filteredArticles"
                style="width: 100%"
                v-loading="loading"
              >
                <el-table-column prop="title" label="标题" min-width="200">
                  <template #default="{ row }">
                    <div class="article-title">
                      <span class="title-text">{{ row.title }}</span>
                      <el-tag v-if="row.status === 'draft'" size="small" type="warning">草稿</el-tag>
                      <el-tag v-else size="small" type="success">已发布</el-tag>
                    </div>
                  </template>
                </el-table-column>
                
                <el-table-column prop="summary" label="摘要" min-width="300">
                  <template #default="{ row }">
                    <div class="article-summary">{{ row.summary || '暂无摘要' }}</div>
                  </template>
                </el-table-column>
                
                <el-table-column prop="tags" label="标签" width="200">
                  <template #default="{ row }">
                    <div class="article-tags">
                      <el-tag
                        v-for="tag in row.tags"
                        :key="tag"
                        size="small"
                        style="margin-right: 4px"
                      >
                        {{ tag }}
                      </el-tag>
                    </div>
                  </template>
                </el-table-column>
                
                <el-table-column prop="updatedAt" label="更新时间" width="180">
                  <template #default="{ row }">
                    {{ formatDate(row.updatedAt) }}
                  </template>
                </el-table-column>
                
                <el-table-column label="操作" width="200" fixed="right">
                  <template #default="{ row }">
                    <el-button size="small" @click="editArticle(row)">编辑</el-button>
                    <el-button size="small" type="primary" @click="viewArticle(row)">查看</el-button>
                    <el-button size="small" type="danger" @click="deleteArticle(row)">删除</el-button>
                  </template>
                </el-table-column>
              </el-table>

              <div v-if="filteredArticles.length === 0 && !loading" class="empty-state">
                <el-empty description="暂无文章">
                  <el-button type="primary" @click="goToEditor">开始写作</el-button>
                </el-empty>
              </div>
            </el-card>
          </el-col>
        </el-row>
      </div>
    </div>

    <el-dialog v-model="previewVisible" :title="previewArticle.title" width="700px" top="8vh" :close-on-click-modal="true">
      <div style="margin-bottom: 8px;">
        <el-tag v-for="tag in previewArticle.tags" :key="tag" size="small" style="margin-right: 4px;">{{ tag }}</el-tag>
        <span style="color: #999; font-size: 13px; margin-left: 12px;">更新时间：{{ formatDate(previewArticle.updatedAt) }}</span>
      </div>
      <div v-html="renderedPreviewContent" style="background: #fafbfc; border-radius: 6px; padding: 16px; min-height: 200px;"></div>
      <template #footer>
        <el-button @click="previewVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import NavBar from '../components/NavBar.vue'
import { useArticleStore } from '../stores/article.store'
import { marked } from 'marked'

const router = useRouter()
const articleStore = useArticleStore()

// 响应式数据
const loading = ref(false)
const searchKeyword = ref('')
const filterStatus = ref('')
const previewVisible = ref(false)
const previewArticle = ref({})

// 直接响应式绑定当前用户的文章
const articles = computed(() => articleStore.articles.slice().sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt)))

// 模拟文章数据
const mockArticles = [
  {
    id: 1,
    title: 'Vue 3 组合式 API 最佳实践',
    summary: '深入探讨Vue 3组合式API的使用技巧和最佳实践，包括响应式系统、生命周期钩子等核心概念。',
    content: '# Vue 3 组合式 API 最佳实践\n\n这是一篇关于Vue 3的文章...',
    tags: ['Vue', '前端', 'JavaScript'],
    status: 'published',
    createdAt: '2024-01-15T10:00:00Z',
    updatedAt: '2024-01-15T10:00:00Z'
  },
  {
    id: 2,
    title: 'AI在Web安全中的应用',
    summary: '探讨人工智能技术在Web安全领域的应用，包括异常检测、威胁分析等前沿技术。',
    content: '# AI在Web安全中的应用\n\n这是一篇关于AI安全的文章...',
    tags: ['AI', '安全', 'Web'],
    status: 'draft',
    createdAt: '2024-01-14T15:30:00Z',
    updatedAt: '2024-01-14T15:30:00Z'
  },
  {
    id: 3,
    title: 'Element Plus 组件库深度解析',
    summary: '详细介绍Element Plus组件库的设计理念、使用方法和自定义配置技巧。',
    content: '# Element Plus 组件库深度解析\n\n这是一篇关于Element Plus的文章...',
    tags: ['Element Plus', 'UI', 'Vue'],
    status: 'published',
    createdAt: '2024-01-13T09:15:00Z',
    updatedAt: '2024-01-13T09:15:00Z'
  }
]

// 计算属性
const filteredArticles = computed(() => {
  let result = articles.value

  // 关键词搜索
  if (searchKeyword.value) {
    result = result.filter(article => 
      article.title.toLowerCase().includes(searchKeyword.value.toLowerCase()) ||
      article.summary.toLowerCase().includes(searchKeyword.value.toLowerCase())
    )
  }

  // 状态筛选
  if (filterStatus.value) {
    result = result.filter(article => article.status === filterStatus.value)
  }

  return result
})

const renderedPreviewContent = computed(() =>
  previewArticle.value && previewArticle.value.content
    ? marked.parse(previewArticle.value.content)
    : ''
)

// 方法
const loadArticles = async () => {
  loading.value = true
  try {
    articleStore.loadArticles()
  } catch (error) {
    ElMessage.error('加载文章失败')
  } finally {
    loading.value = false
  }
}

const goToEditor = () => {
  router.push('/editor')
}

const editArticle = (article) => {
  router.push(`/editor?id=${article.id}`)
}

const viewArticle = (article) => {
  previewArticle.value = article
  previewVisible.value = true
}

const deleteArticle = async (article) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除文章"${article.title}"吗？`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
    )
    // 删除本地存储
    articleStore.deleteArticle(article.id)
    loadArticles()
    ElMessage.success('删除成功')
  } catch {
    // 用户取消
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 生命周期
onMounted(() => {
  loadArticles()
})
</script>

<style scoped>
.home-container {
  min-height: 100vh;
  background-color: #f5f7fa;
}

.main-content {
  padding-top: 80px;
  width: 100vw;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 24px 2vw 0 2vw;
  width: 100%;
  max-width: 1200px;
  box-sizing: border-box;
}

.header-left .page-title {
  font-size: 28px;
  font-weight: 600;
  color: #2c3e50;
  margin: 0 0 8px 0;
}

.header-left .page-subtitle {
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

.articles-card {
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: #2c3e50;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.article-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.title-text {
  font-weight: 500;
  color: #2c3e50;
}

.article-summary {
  color: #606266;
  font-size: 14px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.empty-state {
  padding: 40px 0;
}

@media (max-width: 1400px) {
  .content-header, .articles-card {
    max-width: 98vw;
  }
}

@media (max-width: 1024px) {
  .main-content, .content-header, .articles-card, .content-body {
    max-width: 100vw;
  }
}

/* 响应式设计 */
@media (max-width: 768px) {
  .main-content {
    padding-left: 0;
    padding-right: 0;
  }
  .content-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 2vw 0 2vw;
  }
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .header-actions {
    width: 100%;
    justify-content: space-between;
  }
  .articles-card {
    max-width: 100vw;
  }
}
</style>

