<template>
  <div class="editor-container">
    <NavBar />
    
    <div class="main-content">
      <div class="content-header">
        <div class="header-left">
          <h1 class="page-title">{{ isEdit ? '编辑文章' : '写新文章' }}</h1>
          <p class="page-subtitle">使用AI助手增强您的写作体验</p>
        </div>
        <div class="header-right">
          <el-button @click="goBack">返回</el-button>
          <el-button type="success" @click="saveDraft">保存草稿</el-button>
          <el-button type="primary" @click="publishArticle">发布文章</el-button>
        </div>
      </div>

      <div class="content-body">
        <el-row :gutter="20">
          <el-col :span="24">
            <el-card class="editor-card">
              <el-form :model="articleForm" label-width="80px">
                <el-form-item label="标题">
                  <el-input
                    v-model="articleForm.title"
                    placeholder="请输入文章标题"
                    size="large"
                  />
                </el-form-item>
                
                <el-form-item label="摘要">
                  <el-input
                    v-model="articleForm.summary"
                    type="textarea"
                    :rows="3"
                    placeholder="请输入文章摘要"
                  />
                </el-form-item>
                
                <el-form-item label="标签">
                  <el-select
                    v-model="articleForm.tags"
                    multiple
                    filterable
                    allow-create
                    default-first-option
                    placeholder="请选择或输入标签"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="tag in commonTags"
                      :key="tag"
                      :label="tag"
                      :value="tag"
                    />
                  </el-select>
                </el-form-item>
                
                <el-form-item label="内容">
                  <div class="editor-wrapper">
                    <div class="editor-toolbar">
                      <el-button-group>
                        <el-button size="small" @click="insertText('# ')">H1</el-button>
                        <el-button size="small" @click="insertText('## ')">H2</el-button>
                        <el-button size="small" @click="insertText('### ')">H3</el-button>
                      </el-button-group>
                      <el-button-group>
                        <el-button size="small" @click="insertText('**粗体**')">粗体</el-button>
                        <el-button size="small" @click="insertText('*斜体*')">斜体</el-button>
                        <el-button size="small" @click="insertText('[链接](url)')">链接</el-button>
                      </el-button-group>
                      <el-button-group>
                        <el-button size="small" @click="insertText('```\n代码块\n```')">代码块</el-button>
                        <el-button size="small" @click="insertText('- 列表项')">列表</el-button>
                      </el-button-group>
                      <el-divider direction="vertical" />
                      <el-button size="small" type="primary" @click="aiEnhance">
                        <el-icon><MagicStick /></el-icon>
                        AI润色
                      </el-button>
                      <el-button size="small" @click="aiCodeCheckFunc">代码检测</el-button>
                      <el-button size="small" @click="aiSummaryFunc">自动摘要</el-button>
                      <el-button size="small" @click="aiTagRecommendFunc">标签推荐</el-button>
                    </div>
                    <el-input
                      v-model="articleForm.content"
                      type="textarea"
                      :rows="20"
                      placeholder="请输入文章内容（支持Markdown格式）"
                      class="content-editor"
                    />
                    <div v-if="aiPolishResult && showPolishActions" class="ai-polish-result">
                      <h4>AI润色结果：</h4>
                      <div style="white-space: pre-wrap; background: #f6f8fa; border: 1px solid #eaecef; border-radius: 6px; padding: 16px; margin-bottom: 12px;">
                        {{ aiPolishResult }}
                      </div>
                      <el-button type="primary" size="small" @click="usePolishedContent">使用润色内容</el-button>
                      <el-button size="small" @click="cancelPolish">放弃</el-button>
                    </div>
                  </div>
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
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import NavBar from '../components/NavBar.vue'
import { useArticleStore } from '../stores/article.store'
import { aiPolish, aiCodeCheck, aiSummary, aiTagRecommend } from '../utils/ai'

const route = useRoute()
const router = useRouter()
const articleStore = useArticleStore()

// 响应式数据
const articleForm = reactive({
  title: '',
  summary: '',
  content: '',
  tags: [],
  status: 'draft',
  id: null
})

// 常用标签
const commonTags = [
  'Vue', 'React', 'JavaScript', 'TypeScript', 'Node.js', 'Python', 'Java',
  '前端', '后端', 'AI', '安全', 'Web', '移动端', '数据库', '云服务'
]

// 计算属性
const isEdit = computed(() => !!route.query.id)

// 方法
const goBack = () => {
  router.push('/')
}

const insertText = (text) => {
  const textarea = document.querySelector('.content-editor textarea')
  if (textarea) {
    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const currentText = articleForm.content
    articleForm.content = currentText.substring(0, start) + text + currentText.substring(end)
    
    // 设置光标位置
    nextTick(() => {
      textarea.focus()
      textarea.setSelectionRange(start + text.length, start + text.length)
    })
  }
}

const aiPolishResult = ref('')
const showPolishActions = ref(false)

const aiEnhance = async () => {
  const textarea = document.querySelector('.content-editor textarea')
  if (!textarea) return
  let selected = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd)
  let isSelection = !!selected
  let targetText = isSelection ? selected : articleForm.content
  if (!targetText.trim()) {
    ElMessage.warning('请先输入或选中需要润色的内容')
    return
  }
  try {
    ElMessage.info('AI润色中，请稍候...')
    const polished = await aiPolish(targetText)
    aiPolishResult.value = polished
    showPolishActions.value = true
  } catch (e) {
    ElMessage.error('AI润色失败，请检查API配置或网络')
  }
}

const usePolishedContent = () => {
  articleForm.content = aiPolishResult.value
  aiPolishResult.value = ''
  showPolishActions.value = false
  ElMessage.success('已使用AI润色内容')
}

const cancelPolish = () => {
  aiPolishResult.value = ''
  showPolishActions.value = false
}

const aiCodeCheckFunc = async () => {
  if (!articleForm.content.trim()) {
    ElMessage.warning('请输入文章内容')
    return
  }
  ElMessage.info('AI代码检测中，请稍候...')
  try {
    const res = await aiCodeCheck(articleForm.content)
    ElMessageBox.alert(res, '代码检测结果')
  } catch (e) {
    ElMessage.error('AI代码检测失败')
  }
}

const aiSummaryFunc = async () => {
  if (!articleForm.content.trim()) {
    ElMessage.warning('请输入文章内容')
    return
  }
  ElMessage.info('AI生成摘要中，请稍候...')
  try {
    const res = await aiSummary(articleForm.content)
    articleForm.summary = res
    ElMessage.success('摘要已自动生成')
  } catch (e) {
    ElMessage.error('AI生成摘要失败')
  }
}

const aiTagRecommendFunc = async () => {
  if (!articleForm.content.trim()) {
    ElMessage.warning('请输入文章内容')
    return
  }
  ElMessage.info('AI标签推荐中，请稍候...')
  try {
    const res = await aiTagRecommend(articleForm.content)
    articleForm.tags = res.split(',').map(t => t.trim())
    ElMessage.success('标签已自动推荐')
  } catch (e) {
    ElMessage.error('AI标签推荐失败')
  }
}

const saveDraft = async () => {
  try {
    articleForm.status = 'draft'
    // 保存到本地存储
    articleStore.saveArticle({ ...articleForm })
    ElMessage.success('草稿保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

const publishArticle = async () => {
  if (!articleForm.title.trim()) {
    ElMessage.warning('请输入文章标题')
    return
  }
  if (!articleForm.content.trim()) {
    ElMessage.warning('请输入文章内容')
    return
  }
  try {
    articleForm.status = 'published'
    articleStore.saveArticle({ ...articleForm })
    ElMessage.success('文章发布成功')
    router.push('/')
  } catch (error) {
    ElMessage.error('发布失败')
  }
}

const loadArticle = async (id) => {
  // 优先从本地存储加载
  const local = articleStore.getArticleById(Number(id))
  if (local) {
    Object.assign(articleForm, local)
    return
  }
  // fallback: mock
  const mockArticle = {
    id: id,
    title: '示例文章标题',
    summary: '这是一篇示例文章的摘要...',
    content: '# 示例文章\n\n这是文章内容...',
    tags: ['Vue', '前端'],
    status: 'draft'
  }
  Object.assign(articleForm, mockArticle)
}

// 生命周期
onMounted(() => {
  if (isEdit.value && route.query.id) {
    loadArticle(route.query.id)
  }
})
</script>

<style scoped>
.editor-container {
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 24px 2vw 0 2vw;
  width: 100%;
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

.header-right {
  display: flex;
  gap: 12px;
}

.content-body {
  margin-bottom: 40px;
  width: 100vw;
  display: flex;
  justify-content: center;
  box-sizing: border-box;
}

.editor-card {
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  width: 100vw;
  max-width: 1200px;
  margin: 0;
}

.el-form {
  width: 100%;
}

.editor-wrapper {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;
  width: 100%;
}

.editor-toolbar {
  background: #f5f7fa;
  padding: 12px;
  border-bottom: 1px solid #dcdfe6;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.content-editor {
  border: none;
  width: 100%;
}

.content-editor :deep(.el-textarea__inner) {
  border: none;
  resize: vertical;
  min-height: 400px;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 14px;
  line-height: 1.6;
  width: 100%;
}

.ai-polish-result {
  margin-top: 16px;
  padding: 16px;
  background: #f6f8fa;
  border: 1px solid #eaecef;
  border-radius: 6px;
}

.ai-polish-result h4 {
  font-size: 18px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 12px;
}

@media (max-width: 1400px) {
  .editor-card {
    max-width: 98vw;
  }
}

@media (max-width: 1024px) {
  .main-content, .editor-card, .content-header, .content-body {
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
  .header-right {
    width: 100%;
    justify-content: space-between;
  }
  .editor-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .editor-card {
    max-width: 100vw;
  }
}
</style>
