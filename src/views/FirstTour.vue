<template>
  <!-- 巡演管理主容器 -->
  <div class="tour-management">
    <!-- 页面标题 -->
    <h2>我好像在哪见过你</h2>
    
    <!-- 搜索和操作区域 -->
    <div class="action-bar">
      <!-- 城市搜索输入框 -->
      <el-input 
        v-model="queryParams.city" 
        placeholder="城市" 
        clearable 
        style="width: 150px" 
      />
      <!-- 日期搜索输入框 -->
      <el-input 
        v-model="queryParams.date" 
        placeholder="日期" 
        clearable 
        style="width: 150px" 
      />
      <!-- 地点搜索输入框 -->
      <el-input 
        v-model="queryParams.venue" 
        placeholder="地点" 
        clearable 
        style="width: 180px" 
      />
      <!-- 场次搜索输入框 -->
      <el-input 
        v-model="queryParams.tour" 
        placeholder="场次" 
        clearable 
        style="width: 100px" 
      />
      
      <!-- 操作按钮组 -->
      <div class="action-buttons">
        <!-- 搜索按钮 -->
        <el-button 
          type="primary" 
          @click="handleSearch" 
          :icon="Search"
        >
          搜索
        </el-button>
        <!-- 新增场次按钮 - 点击后会触发handleAdd方法 -->
        <el-button 
          type="success" 
          @click="handleAdd" 
          :icon="Plus"
        >
          新增场次
        </el-button>
      </div>
    </div>

    <!-- 巡演数据表格 -->
    <el-table
      :data="filteredData"
      border
      stripe
      v-loading="loading"
      style="width: 100%"
      empty-text="暂无数据"
      @sort-change="handleSortChange"
    >
      <!-- 场次列 -->
      <el-table-column 
        prop="tour" 
        label="场次" 
        width="100" 
        sortable="custom"
      />
      <!-- 城市列 -->
      <el-table-column 
        prop="city" 
        label="城市" 
        width="150" 
      />
      <!-- 日期列（带格式化显示） -->
      <el-table-column 
        prop="date" 
        label="日期" 
        width="180"
      >
        <template #default="{ row }">
          {{ formatDisplayDate(row.date) }}
        </template>
      </el-table-column>
      <!-- 地点列 -->
      <el-table-column 
        prop="venue" 
        label="地点" 
      />
      <!-- 操作列（固定在右侧） -->
      <el-table-column 
        label="操作" 
        width="180" 
        fixed="right"
      >
        <template #default="{ row, $index }">
          <!-- 编辑按钮 -->
          <el-button 
            size="small" 
            @click="handleEdit(row, $index)"
            :disabled="row.status === 'published'"
          >
            编辑
          </el-button>
          <!-- 删除按钮 -->
          <el-button 
            size="small" 
            type="danger" 
            @click="handleDelete(row.id)"
            :loading="deleteLoading[row.id]"
          >
            删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 编辑弹窗 - 这个弹窗默认是隐藏的，当dialogVisible为true时显示 -->
    <!-- 动态标题，根据是新增还是编辑显示不同文字 -->
    <!-- 控制弹窗显示/隐藏的变量 -->
    <!-- 弹窗关闭时触发的方法 -->
    <el-dialog
      :title="dialogTitle" 
      v-model="dialogVisible" 
      width="500px"
      @closed="handleDialogClosed" 
    >
      <!-- 表单区域 -->
      <el-form 
        :model="formData" 
        :rules="formRules"
        ref="formRef"
        label-width="80px"
      >
        <!-- 场次表单项 -->
        <el-form-item label="场次" prop="tour">
          <el-input-number 
            v-model="formData.tour" 
            :min="1" 
            :max="100"
            controls-position="right"
          />
        </el-form-item>
        <!-- 城市表单项 -->
        <el-form-item label="城市" prop="city">
          <el-input v-model="formData.city" />
        </el-form-item>
        <!-- 日期表单项 -->
        <el-form-item label="日期" prop="date">
          <el-date-picker
            v-model="formData.date"
            type="date"
            value-format="YYYY-MM-DD"
            placeholder="选择日期"
            style="width: 100%"
          />
        </el-form-item>
        <!-- 地点表单项 -->
        <el-form-item label="地点" prop="venue">
          <el-input v-model="formData.venue" />
        </el-form-item>
      </el-form>
      <!-- 弹窗底部按钮 -->
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button 
          type="primary" 
          @click="submitForm"
          :loading="submitting"
        >
          确认
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script lang="ts" setup>
// Vue 相关导入
import { ref, computed, reactive, onMounted } from 'vue'
// Element Plus 组件导入
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Plus } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
// HTTP 请求库
import axios from 'axios'

/**
 * 类型定义
 */
interface TourItem {
  id?: number
  tour: number
  city: string
  date: string
  venue: string
  status?: string
  tourType?: 'firstTour'
}

interface QueryParams {
  city: string
  date: string
  venue: string
  tour: string
  sortField?: string
  sortOrder?: string
}

/**
 * API 配置，通过 Axios 创建一个 API 实例来连接虚拟后端服务
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

/**
 * 常量定义
 */
const CURRENT_TOUR_TYPE = 'firstTour' // 当前巡演类型

/**
 * 响应式状态
 */
const loading = ref(false) // 表格加载状态
const submitting = ref(false) // 表单提交状态
const dialogVisible = ref(false) // 弹窗显示状态，控制弹窗显示/隐藏的响应式变量
const isEditMode = ref(false) // 是否为编辑模式，判断当前是编辑模式还是新增模式的变量
const currentEditIndex = ref(-1) // 当前编辑索引
const deleteLoading = reactive<Record<number, boolean>>({}) // 删除加载状态
const formRef = ref<FormInstance>() // 表单引用
const tourList = ref<TourItem[]>([]) // 巡演数据列表

// 查询参数
const queryParams = reactive<QueryParams>({
  city: '',
  date: '',
  venue: '',
  tour: ''
})

// 表单数据对象
const formData = reactive<TourItem>({
  id: undefined,
  tour: 1,
  city: '',
  date: '',
  venue: '',
  tourType: CURRENT_TOUR_TYPE
})

// 表单验证规则
const formRules = reactive<FormRules<TourItem>>({
  tour: [
    { required: true, message: '请输入场次', trigger: 'blur' },
    { type: 'number', min: 1, max: 100, message: '场次需在1-100之间', trigger: 'blur' }
  ],
  city: [
    { required: true, message: '请输入城市', trigger: 'blur' },
    { min: 2, max: 20, message: '长度在2到20个字符', trigger: 'blur' }
  ],
  date: [
    { required: true, message: '请选择日期', trigger: 'change' }
  ],
  venue: [
    { required: true, message: '请输入地点', trigger: 'blur' },
    { min: 2, max: 50, message: '长度在2到50个字符', trigger: 'blur' }
  ]
})

/**
 * 计算属性，根据isEditMode的值动态返回"编辑场次"或"新增场次"
 */
const dialogTitle = computed(() => isEditMode.value ? '编辑场次' : '新增场次')

/**
 * 计算过滤后的数据
 * 根据查询条件对原始数据进行筛选，城市、地点、场次、日期进行匹配
 */
const filteredData = computed(() => {
  return tourList.value.filter(item => {
    return (
      item.city.includes(queryParams.city) &&
      item.venue.includes(queryParams.venue) &&
      String(item.tour).includes(queryParams.tour) &&
      (queryParams.date ? item.date.includes(queryParams.date) : true)
    )
  })
})

/**
 * 生命周期钩子
 */
onMounted(() => {
  fetchData()
})

/**
 * 方法定义
 */

/**
 * 获取巡演数据
 */
const fetchData = async () => {
  try {
    loading.value = true
    const { data } = await api.get('/tours', {
      params: {
        tourType: CURRENT_TOUR_TYPE,
        _sort: queryParams.sortField,
        _order: queryParams.sortOrder
      }
    })
    tourList.value = data.map((item: any) => ({
      ...item,
      tour: Number(item.tour) || 1
    }))
  } catch (error) {
    handleApiError(error, '获取数据失败')
  } finally {
    loading.value = false
  }
}

/**
 * 处理搜索
 */
const handleSearch = () => {
  fetchData()
}

/**
 * 处理新增按钮点击
 */
const handleAdd = () => {
  //设置为新增模式（不是编辑模式）
  isEditMode.value = false
  
  // 重置表单数据（清空表单）
  resetFormData()
  // 显示弹窗（将dialogVisible设为true） 
  dialogVisible.value = true
}

/**
 * 处理编辑按钮点击
 * @param row 当前行数据
 * @param index 行索引
 */
const 

handleEdit = (row: TourItem, index: number) => {
  // 设置为编辑模式
  isEditMode.value = true
  // 记录当前编辑的索引
  currentEditIndex.value = index
  // 将当前行的数据复制到表单中
  // 使用JSON.parse(JSON.stringify())是为了创建深拷贝，避免直接引用
  Object.assign(formData, JSON.parse(JSON.stringify(row)))
  // 显示弹窗
  dialogVisible.value = true
}

/**
 * 处理删除
 * @param id 巡演ID
 */
const handleDelete = async (id: number) => {
  try {
    await ElMessageBox.confirm('确定删除该场次吗？此操作不可撤销！', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      center: true
    })
    
    deleteLoading[id] = true
    await api.delete(`/tours/${id}`)
    ElMessage.success('删除成功')
    await fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      handleApiError(error, '删除失败')
    }
  } finally {
    deleteLoading[id] = false
  }
}

/**
 * 提交表单的方法
 */
const submitForm = async () => {
  try {
    // 1. 先进行表单验证
    await formRef.value?.validate()
    // 2. 设置提交中状态（按钮会显示加载中）
    submitting.value = true

    // 3. 准备要提交的数据，确保包含巡演类型
    const payload = {
      ...formData,
      tourType: CURRENT_TOUR_TYPE
    }

    // 4. 判断是编辑还是新增
    if (isEditMode.value && formData.id) {
      // 编辑模式 - 发送PUT请求
      await api.put(`/tours/${formData.id}`, payload)
      ElMessage.success('更新成功')
    } else {
      // 新增模式 - 发送POST请求
      await api.post('/tours', payload)
      ElMessage.success('添加成功')
    }
    
    // 5. 关闭弹窗
    dialogVisible.value = false
    // 6. 重新获取数据，刷新表格
    await fetchData()
  } catch (error) {
    // 处理错误，但不包括用户取消操作的情况
    if (error !== 'cancel') {
      handleApiError(error, '提交失败')
    }
  } finally {
    // 无论成功失败，都取消提交中状态
    submitting.value = false
  }
}

/**
 * 处理表格排序变化
 * @param param0 排序参数
 */
const handleSortChange = ({ prop, order }: { prop: string; order: string }) => {
  // 更新查询参数中的排序字段
  queryParams.sortField = prop
  // 将Element UI的排序顺序转换为API需要的格式
  queryParams.sortOrder = order === 'ascending' ? 'asc' : 'desc'
  // 重新获取数据
  fetchData()
}

/**
 * 处理弹窗关闭
 */
const handleDialogClosed = () => {
  // 重置表单验证状态（如果有错误提示会清除）
  formRef.value?.resetFields()
}

/**
 * 重置表单数据的方法
 */
const resetFormData = () => {
  Object.assign(formData, {
    id: undefined,
    tour: 1,
    city: '',
    date: '',
    venue: '',
    tourType: CURRENT_TOUR_TYPE
  })
}

/**
 * 格式化日期显示
 * @param dateStr 日期字符串
 * @returns 格式化后的日期
 */
const formatDisplayDate = (dateStr: string) => {
  if (!dateStr) return ''
  try {
    return new Date(dateStr).toLocaleDateString('zh-CN')
  } catch {
    return dateStr
  }
}

/**
 * 处理API错误
 * @param error 错误对象
 * @param defaultMsg 默认错误消息
 */
const handleApiError = (error: unknown, defaultMsg: string) => {
  if (axios.isAxiosError(error)) {
    ElMessage.error(error.response?.data?.message || defaultMsg)
  } else {
    ElMessage.error(defaultMsg)
  }
  console.error(error)
}
</script>

<style scoped>
/* 主容器样式 */
.tour-management {
  padding: 20px;
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

/* 标题样式 */
h2 {
  margin-bottom: 20px;
  color: var(--el-color-primary);
  font-size: 18px;
  font-weight: 500;
}

/* 操作栏样式 */
.action-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
  align-items: center;
}

/* 操作按钮组样式 */
.action-buttons {
  margin-left: auto;
}

/* 表格样式 */
.el-table {
  margin-top: 16px;
}

/* 空数据提示样式 */
:deep(.el-table__empty-block) {
  min-height: 200px;
}
</style>