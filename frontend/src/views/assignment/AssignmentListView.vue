<template>
  <div class="assignment-list-container">
    <!-- 页面标题 -->
    <div class="page-header">
      <a-page-header title="数据分发管理" subtitle="管理数据记录的分发和处理状态" />
    </div>

    <!-- 搜索和筛选区域 -->
    <div class="search-section">
      <a-card>
        <a-form :model="searchForm" layout="inline" @submit="handleSearch">
          <a-form-item label="状态">
            <a-select 
              v-model="searchForm.status" 
              placeholder="选择状态" 
              allow-clear
              style="width: 150px"
            >
              <a-option value="pending">待处理</a-option>
              <a-option value="in_progress">处理中</a-option>
              <a-option value="completed">已完成</a-option>
            </a-select>
          </a-form-item>
          
          <a-form-item label="公司">
            <a-select 
              v-model="searchForm.company_id" 
              placeholder="选择公司" 
              allow-clear
              style="width: 200px"
            >
              <a-option 
                v-for="company in companies" 
                :key="company.id" 
                :value="company.id"
              >
                {{ company.name }}
              </a-option>
            </a-select>
          </a-form-item>

          <a-form-item label="处理人">
            <a-select 
              v-model="searchForm.assigned_to" 
              placeholder="选择处理人" 
              allow-clear
              style="width: 150px"
            >
              <a-option 
                v-for="user in users" 
                :key="user.id" 
                :value="user.id"
              >
                {{ user.name }}
              </a-option>
            </a-select>
          </a-form-item>

          <a-form-item label="分发日期">
            <a-range-picker 
              v-model="searchForm.dateRange" 
              format="YYYY-MM-DD"
              style="width: 250px"
            />
          </a-form-item>

          <a-form-item>
            <a-button type="primary" html-type="submit" :loading="loading">
              <template #icon><icon-search /></template>
              搜索
            </a-button>
            <a-button @click="handleReset" style="margin-left: 8px">
              重置
            </a-button>
          </a-form-item>
        </a-form>
      </a-card>
    </div>

    <!-- 操作按钮区域 -->
    <div class="action-section">
      <a-space>
        <a-button 
          type="primary" 
          @click="showCreateModal = true"
          v-if="authStore.user?.role === 'admin'"
        >
          <template #icon><icon-plus /></template>
          新建分发
        </a-button>
        <a-button 
          type="outline" 
          @click="showBatchModal = true"
          v-if="authStore.user?.role === 'admin'"
        >
          <template #icon><icon-send /></template>
          批量分发
        </a-button>
        <a-button type="outline" @click="loadAssignments">
          <template #icon><icon-refresh /></template>
          刷新
        </a-button>
      </a-space>
    </div>

    <!-- 数据表格 -->
    <div class="table-section">
      <a-table 
        :columns="columns" 
        :data="assignments"
        :loading="loading"
        :pagination="pagination"
        @page-change="handlePageChange"
        @page-size-change="handlePageSizeChange"
        row-key="id"
      >
        <template #status="{ record }">
          <a-tag 
            :color="getStatusColor(record.status)"
            :bordered="false"
          >
            {{ getStatusText(record.status) }}
          </a-tag>
        </template>

        <template #company="{ record }">
          <div class="company-info">
            <div class="company-name">{{ record.company?.name }}</div>
            <div class="company-code">{{ record.company?.code }}</div>
          </div>
        </template>

        <template #dataRecord="{ record }">
          <div class="data-record-info">
            <div class="record-title">{{ record.data_record?.title || '无标题' }}</div>
            <div class="record-platform">
              <a-tag size="small">{{ getPlatformText(record.data_record?.platform) }}</a-tag>
            </div>
          </div>
        </template>

        <template #assignedTo="{ record }">
          <div v-if="record.assigned_to_user">
            <div class="user-name">{{ record.assigned_to_user.name }}</div>
            <div class="user-email">{{ record.assigned_to_user.email }}</div>
          </div>
          <span v-else class="text-gray">未指定</span>
        </template>

        <template #progress="{ record }">
          <div class="progress-info">
            <div class="time-info">
              <div v-if="record.assigned_at">
                分发时间：{{ formatDateTime(record.assigned_at) }}
              </div>
              <div v-if="record.started_at">
                开始时间：{{ formatDateTime(record.started_at) }}
              </div>
              <div v-if="record.completed_at">
                完成时间：{{ formatDateTime(record.completed_at) }}
              </div>
            </div>
          </div>
        </template>

        <template #actions="{ record }">
          <a-space>
            <a-button 
              type="text" 
              size="small" 
              @click="viewAssignment(record)"
            >
              查看
            </a-button>
            <a-button 
              type="text" 
              size="small" 
              @click="editAssignment(record)"
              v-if="canEditAssignment(record)"
            >
              编辑
            </a-button>
            <a-popconfirm
              content="确定要删除这个分发记录吗？"
              @ok="deleteAssignment(record.id)"
              v-if="authStore.user?.role === 'admin'"
            >
              <a-button type="text" size="small" status="danger">
                删除
              </a-button>
            </a-popconfirm>
          </a-space>
        </template>
      </a-table>
    </div>

    <!-- 创建分发模态框 -->
    <a-modal
      v-model:visible="showCreateModal"
      title="新建数据分发"
      width="600px"
      @ok="handleCreateAssignment"
      :confirm-loading="createLoading"
    >
      <a-form :model="createForm" layout="vertical">
        <a-form-item label="数据记录" required>
          <a-select 
            v-model="createForm.data_record_ids" 
            placeholder="选择数据记录"
            multiple
            :loading="recordsLoading"
            @dropdown-visible-change="loadAvailableRecords"
          >
            <a-option 
              v-for="record in availableRecords" 
              :key="record.id" 
              :value="record.id"
            >
              <div class="record-option">
                <div>{{ record.title || '无标题' }}</div>
                <div class="record-meta">
                  <a-tag size="small">{{ getPlatformText(record.platform) }}</a-tag>
                  <span class="record-date">{{ formatDate(record.created_at) }}</span>
                </div>
              </div>
            </a-option>
          </a-select>
        </a-form-item>

        <a-form-item label="分发公司" required>
          <a-select 
            v-model="createForm.company_id" 
            placeholder="选择公司"
          >
            <a-option 
              v-for="company in companies" 
              :key="company.id" 
              :value="company.id"
            >
              {{ company.name }} ({{ company.code }})
            </a-option>
          </a-select>
        </a-form-item>

        <a-form-item label="指定处理人">
          <a-select 
            v-model="createForm.assigned_to" 
            placeholder="选择处理人（可选）"
            allow-clear
          >
            <a-option 
              v-for="user in users" 
              :key="user.id" 
              :value="user.id"
            >
              {{ user.name }} ({{ user.email }})
            </a-option>
          </a-select>
        </a-form-item>

        <a-form-item label="备注">
          <a-textarea 
            v-model="createForm.notes" 
            placeholder="输入备注信息（可选）"
            :max-length="500"
            show-word-limit
          />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 批量分发模态框 -->
    <a-modal
      v-model:visible="showBatchModal"
      title="批量数据分发"
      width="700px"
      @ok="handleBatchAssignment"
      :confirm-loading="batchLoading"
    >
      <a-form :model="batchForm" layout="vertical">
        <a-form-item label="选择数据记录" required>
          <a-select 
            v-model="batchForm.data_record_ids" 
            placeholder="选择多个数据记录"
            multiple
            :loading="recordsLoading"
            @dropdown-visible-change="loadAvailableRecords"
          >
            <a-option 
              v-for="record in availableRecords" 
              :key="record.id" 
              :value="record.id"
            >
              <div class="record-option">
                <div>{{ record.title || '无标题' }}</div>
                <div class="record-meta">
                  <a-tag size="small">{{ getPlatformText(record.platform) }}</a-tag>
                  <span class="record-date">{{ formatDate(record.created_at) }}</span>
                </div>
              </div>
            </a-option>
          </a-select>
        </a-form-item>

        <a-form-item label="分发公司" required>
          <a-select 
            v-model="batchForm.company_ids" 
            placeholder="选择多个公司"
            multiple
          >
            <a-option 
              v-for="company in companies" 
              :key="company.id" 
              :value="company.id"
            >
              {{ company.name }} ({{ company.code }})
            </a-option>
          </a-select>
        </a-form-item>

        <a-form-item label="备注">
          <a-textarea 
            v-model="batchForm.notes" 
            placeholder="输入批量分发的备注信息（可选）"
            :max-length="500"
            show-word-limit
          />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
/**
 * 数据分发管理页面
 * 提供数据分发的列表展示、创建、编辑、删除等功能
 */

import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import { 
  IconSearch, 
  IconPlus, 
  IconSend, 
  IconRefresh 
} from '@arco-design/web-vue/es/icon'
import { useAuthStore } from '@/stores/auth'
import { 
  getDataAssignments, 
  createDataAssignment, 
  batchAssignData, 
  deleteDataAssignment,
  getAvailableRecords,
  type DataRecordAssignment,
  type DataAssignmentListParams,
  type DataAssignmentCreateRequest,
  type BatchAssignRequest
} from '@/api/dataAssignment'
import { getActiveCompanies, type Company } from '@/api/company'
import { type DataRecord } from '@/api/dataRecord'

const router = useRouter()
const authStore = useAuthStore()

// 响应式数据
const loading = ref(false)
const createLoading = ref(false)
const batchLoading = ref(false)
const recordsLoading = ref(false)
const assignments = ref<DataRecordAssignment[]>([])
const companies = ref<Company[]>([])
const users = ref<any[]>([])
const availableRecords = ref<DataRecord[]>([])
const showCreateModal = ref(false)
const showBatchModal = ref(false)

// 搜索表单
const searchForm = reactive<DataAssignmentListParams & { dateRange?: string[] }>({
  page: 1,
  per_page: 20,
  status: undefined,
  company_id: undefined,
  assigned_to: undefined,
  date_from: undefined,
  date_to: undefined,
  dateRange: undefined
})

// 创建表单
const createForm = reactive<DataAssignmentCreateRequest>({
  data_record_ids: [],
  company_id: 0,
  assigned_to: undefined,
  notes: ''
})

// 批量分发表单
const batchForm = reactive<BatchAssignRequest>({
  data_record_ids: [],
  company_ids: [],
  notes: undefined
})

// 分页信息
const pagination = reactive({
  current: 1,
  pageSize: 20,
  total: 0,
  showTotal: true,
  showPageSize: true,
  pageSizeOptions: ['10', '20', '50', '100']
})

// 表格列定义
const columns = [
  {
    title: '数据记录',
    dataIndex: 'data_record',
    slotName: 'dataRecord',
    width: 200
  },
  {
    title: '分发公司',
    dataIndex: 'company',
    slotName: 'company',
    width: 150
  },
  {
    title: '处理人',
    dataIndex: 'assigned_to_user',
    slotName: 'assignedTo',
    width: 150
  },
  {
    title: '状态',
    dataIndex: 'status',
    slotName: 'status',
    width: 100
  },
  {
    title: '进度信息',
    dataIndex: 'progress',
    slotName: 'progress',
    width: 200
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 150,
    fixed: 'right'
  }
]

// 计算属性
const canEditAssignment = (record: DataRecordAssignment) => {
  const user = authStore.user
  if (!user) return false
  
  // 管理员可以编辑所有分发
  if (user.role === 'admin') return true
  
  // 公司管理员可以编辑本公司的分发
  if (user.role === 'company_admin' && user.company_id === record.company_id) {
    return true
  }
  
  // 处理人可以编辑分配给自己的分发
  if (record.assigned_to === user.id) return true
  
  return false
}

// 方法定义
const loadAssignments = async () => {
  try {
    loading.value = true
    
    // 处理日期范围
    if (searchForm.dateRange && searchForm.dateRange.length === 2) {
      searchForm.date_from = searchForm.dateRange[0]
      searchForm.date_to = searchForm.dateRange[1]
    } else {
      searchForm.date_from = undefined
      searchForm.date_to = undefined
    }
    
    const response = await getDataAssignments(searchForm)
    assignments.value = response.data.data
    pagination.total = response.data.total
    pagination.current = response.data.current_page
  } catch (error) {
    console.error('加载分发列表失败:', error)
    Message.error('加载分发列表失败')
  } finally {
    loading.value = false
  }
}

const loadCompanies = async () => {
  try {
    const response = await getActiveCompanies()
    if (response.success && response.data) {
      companies.value = response.data
    }
  } catch (error) {
    console.error('加载公司列表失败:', error)
  }
}

const loadAvailableRecords = async (visible: boolean) => {
  if (!visible || availableRecords.value.length > 0) return
  
  try {
    recordsLoading.value = true
    const response = await getAvailableRecords({
      per_page: 100
    })
    availableRecords.value = response.data.data
  } catch (error) {
    console.error('加载可用数据记录失败:', error)
  } finally {
    recordsLoading.value = false
  }
}

const handleSearch = () => {
  searchForm.page = 1
  pagination.current = 1
  loadAssignments()
}

const handleReset = () => {
  Object.assign(searchForm, {
    page: 1,
    per_page: 20,
    status: undefined,
    company_id: undefined,
    assigned_to: undefined,
    date_from: undefined,
    date_to: undefined,
    dateRange: undefined
  })
  pagination.current = 1
  loadAssignments()
}

const handlePageChange = (page: number) => {
  searchForm.page = page
  pagination.current = page
  loadAssignments()
}

const handlePageSizeChange = (pageSize: number) => {
  searchForm.per_page = pageSize
  searchForm.page = 1
  pagination.pageSize = pageSize
  pagination.current = 1
  loadAssignments()
}

const handleCreateAssignment = async () => {
  if (!createForm.data_record_ids?.length || !createForm.company_id || createForm.company_id === 0) {
    Message.error('请填写必填字段')
    return
  }
  
  try {
    createLoading.value = true
    await createDataAssignment(createForm)
    Message.success('创建分发成功')
    showCreateModal.value = false
    resetCreateForm()
    loadAssignments()
  } catch (error) {
    console.error('创建分发失败:', error)
    Message.error('创建分发失败')
  } finally {
    createLoading.value = false
  }
}

const handleBatchAssignment = async () => {
  if (!batchForm.data_record_ids.length || !batchForm.company_ids.length) {
    Message.error('请选择数据记录和公司')
    return
  }
  
  try {
    batchLoading.value = true
    await batchAssignData(batchForm)
    Message.success('批量分发成功')
    showBatchModal.value = false
    resetBatchForm()
    loadAssignments()
  } catch (error) {
    console.error('批量分发失败:', error)
    Message.error('批量分发失败')
  } finally {
    batchLoading.value = false
  }
}

const viewAssignment = (record: DataRecordAssignment) => {
  router.push(`/assignment/${record.id}`)
}

const editAssignment = (record: DataRecordAssignment) => {
  router.push(`/assignment/${record.id}/edit`)
}

const deleteAssignment = async (id: number) => {
  try {
    await deleteDataAssignment(id)
    Message.success('删除成功')
    loadAssignments()
  } catch (error) {
    console.error('删除失败:', error)
    Message.error('删除失败')
  }
}

const resetCreateForm = () => {
  Object.assign(createForm, {
    data_record_ids: [],
    company_id: undefined,
    assigned_to: undefined,
    notes: undefined
  })
}

const resetBatchForm = () => {
  Object.assign(batchForm, {
    data_record_ids: [],
    company_ids: [],
    notes: undefined
  })
}

// 工具方法
const getStatusColor = (status: string) => {
  const colors = {
    pending: 'orange',
    in_progress: 'blue',
    completed: 'green'
  }
  return colors[status as keyof typeof colors] || 'gray'
}

const getStatusText = (status: string) => {
  const texts = {
    pending: '待处理',
    in_progress: '处理中',
    completed: '已完成'
  }
  return texts[status as keyof typeof texts] || status
}

const getPlatformText = (platform: string) => {
  const platforms = {
    xiaohongshu: '小红书',
    douyin: '抖音',
    kuaishou: '快手',
    weibo: '微博',
    bilibili: 'B站'
  }
  return platforms[platform as keyof typeof platforms] || platform
}

const formatDateTime = (dateTime: string) => {
  return new Date(dateTime).toLocaleString('zh-CN')
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('zh-CN')
}

// 生命周期
onMounted(() => {
  loadAssignments()
  loadCompanies()
})
</script>

<style scoped>
.assignment-list-container {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.search-section {
  margin-bottom: 20px;
}

.action-section {
  margin-bottom: 20px;
}

.table-section {
  background: white;
  border-radius: 6px;
}

.company-info .company-name {
  font-weight: 500;
  margin-bottom: 4px;
}

.company-info .company-code {
  font-size: 12px;
  color: #86909c;
}

.data-record-info .record-title {
  font-weight: 500;
  margin-bottom: 4px;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.data-record-info .record-platform {
  margin-top: 4px;
}

.user-name {
  font-weight: 500;
  margin-bottom: 2px;
}

.user-email {
  font-size: 12px;
  color: #86909c;
}

.progress-info .time-info {
  font-size: 12px;
  color: #86909c;
}

.progress-info .time-info > div {
  margin-bottom: 2px;
}

.text-gray {
  color: #86909c;
}

.record-option .record-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.record-option .record-date {
  font-size: 12px;
  color: #86909c;
}
</style>