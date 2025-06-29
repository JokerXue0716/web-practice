<template>
  <div class="album-management">
    <h2>前十年专辑管理</h2>
    
    <!-- 搜索和操作区 -->
    <div class="action-bar">
      <el-input v-model="queryParams.albumName" placeholder="专辑名" clearable style="width: 180px" />
      <el-input v-model="queryParams.company" placeholder="发行公司" clearable style="width: 180px" />
      <el-input-number v-model="queryParams.minPrice" :min="0" placeholder="最低价" controls-position="right" style="width: 120px" />
      <el-input-number v-model="queryParams.maxPrice" :min="0" placeholder="最高价" controls-position="right" style="width: 120px" />
      
      <div class="action-buttons">
        <el-button type="primary" @click="handleSearch" :icon="Search">搜索</el-button>
        <el-button type="success" @click="handleAdd" :icon="Plus">新增专辑</el-button>
      </div>
    </div>

    <!-- 数据表格 -->
    <el-table
      :data="filteredData"
      border
      stripe
      v-loading="loading"
      style="width: 100%"
      empty-text="暂无数据"
      @sort-change="handleSortChange"
    >
      <el-table-column 
        prop="albumNumber" 
        label="专辑编号" 
        width="100" 
        sortable="custom"
      />
      <el-table-column prop="albumName" label="专辑名" width="200" />
      <el-table-column prop="company" label="发行公司" width="180" />
      <el-table-column prop="songCount" label="歌曲数量" width="120" sortable="custom">
        <template #default="{ row }">
          {{ row.songCount }} 首
        </template>
      </el-table-column>
      <el-table-column prop="price" label="实体售价" width="150" sortable="custom">
        <template #default="{ row }">
          ¥{{ row.price.toFixed(2) }}
        </template>
      </el-table-column>
      <el-table-column label="操作" width="180" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="handleEdit(row)">编辑</el-button>
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

    <!-- 编辑弹窗 -->
    <el-dialog
      :title="dialogTitle"
      v-model="dialogVisible"
      width="600px"
      @closed="handleDialogClosed"
    >
      <el-form 
        :model="formData" 
        :rules="formRules"
        ref="formRef"
        label-width="100px"
      >
        <el-form-item label="专辑编号" prop="albumNumber">
          <el-input-number 
            v-model="formData.albumNumber" 
            :min="1" 
            controls-position="right"
          />
        </el-form-item>
        <el-form-item label="专辑名称" prop="albumName">
          <el-input v-model="formData.albumName" />
        </el-form-item>
        <el-form-item label="发行公司" prop="company">
          <el-input v-model="formData.company" />
        </el-form-item>
        <el-form-item label="歌曲数量" prop="songCount">
          <el-input-number 
            v-model="formData.songCount" 
            :min="1" 
            :max="50"
            controls-position="right"
          />
        </el-form-item>
        <el-form-item label="实体售价" prop="price">
          <el-input-number 
            v-model="formData.price" 
            :min="0" 
            :precision="2"
            controls-position="right"
          >
            <template #prefix>¥</template>
          </el-input-number>
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
interface Album {
  id?: number
  albumNumber: number
  albumName: string
  company: string
  songCount: number
  price: number
  decade: 'previous' | 'next' // 年代分类
}

interface QueryParams {
  albumName: string
  company: string
  minPrice?: number
  maxPrice?: number
  sortField?: string
  sortOrder?: string
}

// API 配置
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000',
  timeout: 10000
})

// 状态管理
const loading = ref(false)
const submitting = ref(false)
const dialogVisible = ref(false)
const isEditMode = ref(false)
const deleteLoading = reactive<Record<number, boolean>>({})
const formRef = ref<FormInstance>()
const albumList = ref<Album[]>([])

const queryParams = reactive<QueryParams>({
  albumName: '',
  company: '',
  minPrice: undefined,
  maxPrice: undefined
})

const formData = reactive<Album>({
  albumNumber: 1,
  albumName: '',
  company: '',
  songCount: 10,
  price: 99.0,
  decade: 'previous' // 固定为前十年
})

const formRules = reactive<FormRules<Album>>({
  albumNumber: [
    { required: true, message: '请输入专辑编号', trigger: 'blur' },
    { type: 'number', min: 1, message: '编号必须大于0', trigger: 'blur' }
  ],
  albumName: [
    { required: true, message: '请输入专辑名', trigger: 'blur' },
    { min: 2, max: 50, message: '长度在2-50个字符', trigger: 'blur' }
  ],
  company: [
    { required: true, message: '请输入发行公司', trigger: 'blur' },
    { min: 2, max: 50, message: '长度在2-50个字符', trigger: 'blur' }
  ],
  songCount: [
    { required: true, message: '请输入歌曲数量', trigger: 'blur' },
    { type: 'number', min: 1, max: 50, message: '1-50首之间', trigger: 'blur' }
  ],
  price: [
    { required: true, message: '请输入售价', trigger: 'blur' },
    { type: 'number', min: 0, message: '不能为负数', trigger: 'blur' }
  ]
})

// 计算属性
const dialogTitle = computed(() => isEditMode.value ? '编辑专辑' : '新增专辑')
const filteredData = computed(() => {
  return albumList.value.filter(item => {
    const priceValid = (
      (queryParams.minPrice === undefined || item.price >= queryParams.minPrice) &&
      (queryParams.maxPrice === undefined || item.price <= queryParams.maxPrice)
    )
    return (
      item.albumName.includes(queryParams.albumName) &&
      item.company.includes(queryParams.company) &&
      priceValid
    )
  })
})

// 生命周期
onMounted(() => {
  fetchData()
})

// 方法定义
const fetchData = async () => {
  try {
    loading.value = true
    const { data } = await api.get('/albums', {
      params: {
        decade: 'previous', // 关键过滤参数
        _sort: queryParams.sortField,
        _order: queryParams.sortOrder,
        price_gte: queryParams.minPrice,
        price_lte: queryParams.maxPrice
      }
    })
    albumList.value = data
  } catch (error) {
    handleApiError(error, '获取数据失败')
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  fetchData()
}

const handleAdd = () => {
  isEditMode.value = false
  resetFormData()
  dialogVisible.value = true
}

const handleEdit = (row: Album) => {
  isEditMode.value = true
  Object.assign(formData, JSON.parse(JSON.stringify(row)))
  dialogVisible.value = true
}

const handleDelete = async (id: number) => {
  try {
    await ElMessageBox.confirm('确定删除该专辑吗？相关数据将永久丢失！', '警告', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    deleteLoading[id] = true
    await api.delete(`/albums/${id}`)
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
      decade: 'previous' // 确保数据分类正确
    }

    if (isEditMode.value && formData.id) {
      await api.put(`/albums/${formData.id}`, payload)
      ElMessage.success('更新成功')
    } else {
      await api.post('/albums', payload)
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
    albumNumber: 1,
    albumName: '',
    company: '',
    songCount: 10,
    price: 99.0,
    decade: 'previous'
  })
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
.album-management {
  padding: 20px;
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

h2 {
  margin-bottom: 20px;
  color: var(--el-color-primary);
  font-size: 20px;
  font-weight: 600;
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

:deep(.el-input-number .el-input__inner) {
  text-align: left;
}
</style>