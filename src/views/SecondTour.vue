<template>
  <div class="tour-management">
    <h2>摩天大楼</h2>
    
    <!-- 搜索和操作区 -->
    <div class="action-bar">
      <el-input v-model="queryParams.city" placeholder="城市" clearable style="width: 150px" />
      <el-input v-model="queryParams.date" placeholder="日期" clearable style="width: 150px" />
      <el-input v-model="queryParams.venue" placeholder="地点" clearable style="width: 180px" />
      <el-input v-model="queryParams.tour" placeholder="场次" clearable style="width: 100px" />
      
      <div class="action-buttons">
        <el-button type="primary" @click="handleSearch" :icon="Search">搜索</el-button>
        <el-button type="success" @click="handleAdd" :icon="Plus">新增场次</el-button>
      </div>
    </div>

    <!-- 数据表格 -->
    <el-table
      :data="pagedData"
      border
      stripe
      v-loading="loading"
      style="width: 100%"
      empty-text="暂无数据"
      @sort-change="handleSortChange"
    >
      <el-table-column 
        prop="tour" 
        label="场次" 
        width="100" 
        sortable="custom"
      />
      <el-table-column prop="city" label="城市" width="150" />
      <el-table-column prop="date" label="日期" width="180">
        <template #default="{ row }">
          {{ formatDisplayDate(row.date) }}
        </template>
      </el-table-column>
      <el-table-column prop="venue" label="地点" />
      <el-table-column label="操作" width="180" fixed="right">
        <template #default="{ row }">
          <el-button 
            size="small" 
            @click="handleEdit(row)"
            :disabled="row.status === 'published'"
          >
            编辑
          </el-button>
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

    <!-- 分页组件 -->
    <div class="pagination-container">
      <!-- Element Plus的分页组件 -->
      <!-- 双向绑定当前页码 双向绑定每页条数 总数据条数 可选的每页条数选项 布局配置 背景样式 每页条数改变事件 页码改变事件 -->
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :total="filteredData.length"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        background
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>

    <!-- 编辑弹窗 -->
    <el-dialog
      :title="dialogTitle"
      v-model="dialogVisible"
      width="500px"
      @closed="handleDialogClosed"
    >
      <el-form 
        :model="formData" 
        :rules="formRules"
        ref="formRef"
        label-width="80px"
      >
        <el-form-item label="场次" prop="tour">
          <el-input-number 
            v-model="formData.tour" 
            :min="1" 
            :max="200"
            controls-position="right"
          />
        </el-form-item>
        <el-form-item label="城市" prop="city">
          <el-input v-model="formData.city" />
        </el-form-item>
        <el-form-item label="日期" prop="date">
          <el-date-picker
            v-model="formData.date"
            type="date"
            value-format="YYYY-MM-DD"
            placeholder="选择日期"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="地点" prop="venue">
          <el-input v-model="formData.venue" />
        </el-form-item>
      </el-form>
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
import { ref, computed, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Plus } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import axios from 'axios'

// 类型定义
interface TourItem {
  id?: number
  tour: number
  city: string
  date: string
  venue: string
  status?: string
  tourType?: 'secondTour'
}

interface QueryParams {
  city: string
  date: string
  venue: string
  tour: string
  sortField?: string
  sortOrder?: string
}

// API 配置
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

// 当前巡演类型常量
const CURRENT_TOUR_TYPE = 'secondTour'

// 状态管理
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const isEditMode = ref(false)
const deleteLoading = reactive<Record<number, boolean>>({})
const formRef = ref<FormInstance>()
const tourList = ref<TourItem[]>([])

// 分页相关
// 当前页码，初始值为1（第一页）
const currentPage = ref(1)
// 每页显示的数据条数，初始值为10
const pageSize = ref(10)

const queryParams = reactive<QueryParams>({
  city: '',
  date: '',
  venue: '',
  tour: ''
})

const formData = reactive<TourItem>({
  id: undefined,
  tour: 1,
  city: '',
  date: '',
  venue: '',
  tourType: CURRENT_TOUR_TYPE
})

const formRules = reactive<FormRules<TourItem>>({
  tour: [
    { required: true, message: '请输入场次', trigger: 'blur' },
    { type: 'number', min: 1, max: 200, message: '场次需在1-200之间', trigger: 'blur' }
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

// 计算属性
const dialogTitle = computed(() => isEditMode.value ? '编辑场次' : '新增场次')
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
// 分页后的数据计算属性
const pagedData = computed(() => {
  // 计算当前页数据的起始索引
  // 例如：第2页，每页10条，则start = (2-1)*10 = 10
  const start = (currentPage.value - 1) * pageSize.value

  // 计算当前页数据的结束索引
  // 例如：第2页，每页10条，则end = 10 + 10 = 20
  const end = start + pageSize.value
  // 从过滤后的数据中截取当前页的数据
  return filteredData.value.slice(start, end)
})

// 生命周期
onMounted(() => {
  fetchData()
})

// 方法定义
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
    currentPage.value = 1 // 获取新数据时重置到第一页
  } catch (error) {
    handleApiError(error, '获取数据失败')
  } finally {
    loading.value = false
  }
}

// 分页处理方法
const handleSizeChange = (val: number) => {
  // val是用户选择的新每页条数
  pageSize.value = val
  // 当每页条数改变时，重置到第一页
  currentPage.value = 1
}

// 处理页码变化的函数
const handleCurrentChange = (val: number) => {
  // val是用户点击的新页码
  currentPage.value = val
  // 注意：这里不需要手动更新数据，因为pagedData是计算属性
  // 当currentPage变化时，pagedData会自动重新计算
}

const handleSearch = () => {
  fetchData()
}

const handleAdd = () => {
  isEditMode.value = false
  resetFormData()
  dialogVisible.value = true
}

const handleEdit = (row: TourItem) => {
  isEditMode.value = true
  Object.assign(formData, JSON.parse(JSON.stringify(row)))
  dialogVisible.value = true
}

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

const submitForm = async () => {
  try {
    await formRef.value?.validate()
    submitting.value = true

    const payload = {
      ...formData,
      tourType: CURRENT_TOUR_TYPE
    }

    if (isEditMode.value && formData.id) {
      await api.put(`/tours/${formData.id}`, payload)
      ElMessage.success('更新成功')
    } else {
      await api.post('/tours', payload)
      ElMessage.success('添加成功')
    }
    
    dialogVisible.value = false
    await fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      handleApiError(error, '提交失败')
    }
  } finally {
    submitting.value = false
  }
}

const handleSortChange = ({ prop, order }: { prop: string; order: string }) => {
  queryParams.sortField = prop
  queryParams.sortOrder = order === 'ascending' ? 'asc' : 'desc'
  fetchData()
}

const handleDialogClosed = () => {
  formRef.value?.resetFields()
}

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

const formatDisplayDate = (dateStr: string) => {
  if (!dateStr) return ''
  try {
    return new Date(dateStr).toLocaleDateString('zh-CN')
  } catch {
    return dateStr
  }
}

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
.tour-management {
  padding: 20px;
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

h2 {
  margin-bottom: 20px;
  color: var(--el-color-primary);
  font-size: 18px;
  font-weight: 500;
}

.action-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
  align-items: center;
}

.action-buttons {
  margin-left: auto;
  display: flex;
  gap: 12px;
}

.el-table {
  margin-top: 16px;
}

:deep(.el-table__empty-block) {
  min-height: 200px;
}

/* 分页容器样式 */
.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>