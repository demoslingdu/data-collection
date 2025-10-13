<template>
  <div class="dashboard-container">
    <!-- 页面头部 -->
    <div class="dashboard-header">
      <h1>数据记录管理</h1>
      <p>管理和查看所有数据记录，支持搜索、筛选、多公司分发功能</p>
    </div>

    <!-- 搜索功能区域 -->
    <a-card title="搜索和筛选" class="search-card">
      <a-form :model="searchForm" layout="inline" class="search-form">
        <a-form-item label="关键词">
          <a-input
            v-model="searchForm.keyword"
            placeholder="请输入关键词搜索"
            style="width: 200px"
            allow-clear
            @press-enter="handleSearch"
          >
            <template #prefix>
              <icon-search />
            </template>
          </a-input>
        </a-form-item>
        <a-form-item label="平台">
          <a-select
            v-model="searchForm.platform"
            placeholder="请选择平台"
            style="width: 120px"
            allow-clear
          >
            <a-option value="douyin">抖音</a-option>
            <a-option value="kuaishou">快手</a-option>
            <a-option value="xiaohongshu">小红书</a-option>
            <a-option value="weibo">微博</a-option>
            <a-option value="bilibili">B站</a-option>
          </a-select>
        </a-form-item>
        <a-form-item label="领取状态">
          <a-select
            v-model="searchForm.claimStatus"
            placeholder="请选择领取状态"
            style="width: 140px"
            allow-clear
          >
            <a-option value="claimed">已领取</a-option>
            <a-option value="unclaimed">未领取</a-option>
          </a-select>
        </a-form-item>
        <a-form-item label="完成状态">
          <a-select
            v-model="searchForm.completeStatus"
            placeholder="请选择完成状态"
            style="width: 140px"
            allow-clear
          >
            <a-option value="completed">已完成</a-option>
            <a-option value="uncompleted">未完成</a-option>
          </a-select>
        </a-form-item>
        <!-- 新增公司筛选 -->
        <a-form-item label="分发公司" v-if="authStore.user?.role === 'admin'">
          <a-select
            v-model="searchForm.company_id"
            placeholder="请选择公司"
            style="width: 150px"
            allow-clear
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
        <!-- 新增分发状态筛选 -->
        <a-form-item label="分发状态">
          <a-select
            v-model="searchForm.assignmentStatus"
            placeholder="请选择分发状态"
            style="width: 140px"
            allow-clear
          >
            <a-option value="assigned">已分发</a-option>
            <a-option value="unassigned">未分发</a-option>
          </a-select>
        </a-form-item>
        <a-form-item label="时间范围">
          <a-range-picker
            v-model="searchForm.dateRange"
            style="width: 250px"
          />
        </a-form-item>
        <a-form-item>
          <a-space>
            <a-button type="primary" @click="handleSearch">
              <template #icon>
                <icon-search />
              </template>
              搜索
            </a-button>
            <a-button @click="handleReset">
              <template #icon>
                <icon-refresh />
              </template>
              重置
            </a-button>
          </a-space>
        </a-form-item>
      </a-form>
    </a-card>

    <!-- 操作按钮区域 -->
    <a-card class="action-card" v-if="authStore.user?.role === 'admin'">
      <a-space>
        <a-button 
          type="primary" 
          @click="showBatchAssignModal = true"
          :disabled="selectedRowKeys.length === 0"
        >
          <template #icon><icon-send /></template>
          批量分发 ({{ selectedRowKeys.length }})
        </a-button>
        <a-button type="outline" @click="loadData">
          <template #icon><icon-refresh /></template>
          刷新数据
        </a-button>
      </a-space>
    </a-card>

    <!-- 数据表格 -->
    <a-card title="数据记录列表" class="table-card">
      <a-table
        row-key="id"
        :columns="columns"
        :data="tableData"
        :loading="loading"
        :pagination="paginationConfig"
        :row-selection="rowSelection"
        v-model:selectedKeys="selectedRowKeys"
        @page-change="handlePageChange"
        @page-size-change="handlePageSizeChange"
        :scroll="{ x: 1800, y: 600 }"
        stripe
        hoverable
        size="large"
      >
        <template #image="{ record }">
          <a-image
            :src="record.image_url"
            width="60"
            height="60"
            fit="cover"
            :preview="true"
            style="border-radius: 4px; cursor: pointer;"
          />
        </template>
        <template #platform="{ record }">
          {{ getPlatformText(record.platform) }}
        </template>
        <template #is_claimed="{ record }">
          <a-tag :color="record.is_claimed ? 'green' : 'red'">
            {{ record.is_claimed ? '已领取' : '未领取' }}
          </a-tag>
        </template>
        <template #is_completed="{ record }">
          <a-tag :color="record.is_completed ? 'blue' : 'orange'">
            {{ record.is_completed ? '已完成' : '未完成' }}
          </a-tag>
        </template>
        <template #submitter="{ record }">
          {{ record.submitter?.name || '未知' }}
        </template>
        <template #claimer="{ record }">
          {{ record.claimer?.name || '未分配' }}
        </template>
        <template #phone="{ record }">
          <span v-if="record.phone" class="phone-display">
            {{ record.phone }}
          </span>
          <span v-else class="phone-empty">
            未填写
          </span>
        </template>
        <template #is_duplicate="{ record }">
          <a-tag :color="record.is_duplicate ? 'red' : 'green'">
            {{ record.is_duplicate ? '重复' : '正常' }}
          </a-tag>
        </template>
        <!-- 新增分发状态列 -->
        <template #assignment_status="{ record }">
          <div class="assignment-status">
            <div v-if="record.assignments && record.assignments.length > 0" class="assignment-list">
              <div 
                v-for="assignment in record.assignments" 
                :key="assignment.id"
                class="assignment-item"
              >
                <div class="company-info">
                  <span class="company-name">{{ assignment.company?.name }}</span>
                  <a-tag 
                    :color="getAssignmentStatusColor(assignment.status)"
                    size="small"
                  >
                    {{ getAssignmentStatusText(assignment.status) }}
                  </a-tag>
                </div>
                <div class="assignment-user" v-if="assignment.assigned_to_user">
                  处理人: {{ assignment.assigned_to_user.name }}
                </div>
              </div>
            </div>
            <span v-else class="no-assignment">未分发</span>
          </div>
        </template>
        <template #actions="{ record }">
          <a-space size="mini">
            <!-- 原有的领取和完成按钮 -->
            <a-button 
              v-if="!record.is_claimed && !record.is_completed"
              type="primary" 
              size="mini" 
              :loading="claimingIds.has(record.id)"
              :disabled="claimingIds.has(record.id)"
              @click="handleClaim(record)"
            >
              {{ claimingIds.has(record.id) ? '领取中...' : '领取' }}
            </a-button>
            <a-button 
              v-if="record.is_claimed && !record.is_completed"
              type="outline" 
              size="mini" 
              status="success"
              :loading="completingIds.has(record.id)"
              :disabled="completingIds.has(record.id)"
              @click="handleComplete(record)"
            >
              {{ completingIds.has(record.id) ? '完成中...' : '完成' }}
            </a-button>
            <a-tag v-if="record.is_completed" color="green" size="small">
              已完成
            </a-tag>
            
            <!-- 新增分发按钮 -->
            <a-button 
              v-if="authStore.user?.role === 'admin'"
              type="text" 
              size="mini" 
              @click="showSingleAssignModal(record)"
            >
              分发
            </a-button>
            
            <!-- 查看分发详情按钮 -->
            <a-button 
              v-if="record.assignments && record.assignments.length > 0"
              type="text" 
              size="mini" 
              @click="viewAssignments(record)"
            >
              查看分发
            </a-button>
          </a-space>
        </template>
        <template #empty>
          <a-empty description="暂无数据记录" />
        </template>
      </a-table>
    </a-card>

    <!-- 批量分发模态框 -->
    <a-modal
      v-model:visible="showBatchAssignModal"
      title="批量数据分发"
      width="600px"
      @ok="handleBatchAssign"
      :confirm-loading="batchAssignLoading"
    >
      <a-form :model="batchAssignForm" layout="vertical">
        <a-form-item label="选择公司" required>
          <a-select 
            v-model="batchAssignForm.company_ids" 
            placeholder="选择要分发的公司"
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
            v-model="batchAssignForm.notes" 
            placeholder="输入分发备注（可选）"
            :max-length="500"
            show-word-limit
          />
        </a-form-item>
        
        <a-form-item label="选中的数据记录">
          <div class="selected-records">
            <a-tag 
              v-for="recordId in selectedRowKeys" 
              :key="recordId"
              closable
              @close="removeSelectedRecord(recordId)"
            >
              记录 #{{ recordId }}
            </a-tag>
          </div>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 单个分发模态框 -->
    <a-modal
      v-model:visible="showSingleAssignModalVisible"
      title="数据分发"
      width="500px"
      @ok="handleSingleAssign"
      :confirm-loading="singleAssignLoading"
    >
      <a-form :model="singleAssignForm" layout="vertical">
        <a-form-item label="数据记录">
          <div class="record-info">
            <span>记录 #{{ currentRecord?.id }}</span>
            <a-tag>{{ getPlatformText(currentRecord?.platform || '') }}</a-tag>
          </div>
        </a-form-item>
        
        <a-form-item label="选择公司" required>
          <a-select 
            v-model="singleAssignForm.company_id" 
            placeholder="选择要分发的公司"
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
            v-model="singleAssignForm.assigned_to" 
            placeholder="选择处理人（可选）"
            allow-clear
          >
            <a-option 
              v-for="user in companyUsers" 
              :key="user.id" 
              :value="user.id"
            >
              {{ user.name }} ({{ user.email }})
            </a-option>
          </a-select>
        </a-form-item>
        
        <a-form-item label="备注">
          <a-textarea 
            v-model="singleAssignForm.notes" 
            placeholder="输入分发备注（可选）"
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
 * 数据记录列表页面
 * 提供数据记录的查看、搜索、筛选、分页、多公司分发等功能
 */
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Message, Modal } from '@arco-design/web-vue'
import {
  IconSearch,
  IconRefresh,
  IconSend
} from '@arco-design/web-vue/es/icon'
import { useAuthStore } from '@/stores/auth'
import { dataRecordApi } from '@/api/dataRecord'
import type { DataRecord } from '@/api/dataRecord'
import { getActiveCompanies, type Company } from '@/api/company'
import { 
  batchAssignData, 
  createDataAssignment,
  type BatchAssignRequest,
  type DataAssignmentCreateRequest
} from '@/api/dataAssignment'

// 路由实例和用户状态
const router = useRouter()
const authStore = useAuthStore()

// 响应式数据
const loading = ref(false)
const selectedRowKeys = ref<string[]>([])
const companies = ref<Company[]>([])
const companyUsers = ref<any[]>([])

// 按钮加载状态
const claimingIds = ref<Set<number>>(new Set())
const completingIds = ref<Set<number>>(new Set())
const batchAssignLoading = ref(false)
const singleAssignLoading = ref(false)

// 模态框状态
const showBatchAssignModal = ref(false)
const showSingleAssignModalVisible = ref(false)
const currentRecord = ref<DataRecord | null>(null)

// 搜索表单
const searchForm = reactive({
  keyword: '',
  platform: '',
  claimStatus: '',
  completeStatus: '',
  company_id: undefined as number | undefined,
  assignmentStatus: '',
  dateRange: []
})

// 批量分发表单
const batchAssignForm = reactive<BatchAssignRequest>({
  data_record_ids: [],
  company_ids: [],
  notes: undefined
})

// 单个分发表单
const singleAssignForm = reactive<DataAssignmentCreateRequest>({
  data_record_id: 0,
  company_id: 0,
  assigned_to: undefined,
  notes: ''
})

// 分页配置
const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 156
})

// 计算属性：分页配置
const paginationConfig = computed(() => ({
  current: pagination.current,
  pageSize: pagination.pageSize,
  total: pagination.total,
  showTotal: true,
  showPageSize: true,
  pageSizeOptions: ['10', '20', '50', '100'],
  showJumper: true,
  size: 'large'
}))

// 表格列配置
const columns = [
  {
    title: 'ID',
    dataIndex: 'id',
    width: 80,
    align: 'center',
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  },
  {
    title: '图片',
    dataIndex: 'image_url',
    slotName: 'image',
    width: 100,
    align: 'center'
  },
  {
    title: '提交人',
    dataIndex: 'submitter',
    slotName: 'submitter',
    width: 120,
    align: 'center'
  },
  {
    title: '平台',
    dataIndex: 'platform',
    slotName: 'platform',
    width: 100,
    align: 'center'
  },
  {
    title: '平台ID',
    dataIndex: 'platform_id',
    width: 150,
    ellipsis: true,
    tooltip: true
  },
  {
    title: '手机号',
    dataIndex: 'phone',
    slotName: 'phone',
    width: 120,
    align: 'center'
  },
  {
    title: '是否领取',
    dataIndex: 'is_claimed',
    slotName: 'is_claimed',
    width: 100,
    align: 'center'
  },
  {
    title: '是否完成',
    dataIndex: 'is_completed',
    slotName: 'is_completed',
    width: 100,
    align: 'center'
  },
  {
    title: '领取人',
    dataIndex: 'claimer',
    slotName: 'claimer',
    width: 120,
    align: 'center'
  },
  {
    title: '是否重复',
    dataIndex: 'is_duplicate',
    slotName: 'is_duplicate',
    width: 100,
    align: 'center'
  },
  {
    title: '分发状态',
    dataIndex: 'assignment_status',
    slotName: 'assignment_status',
    width: 200,
    align: 'center'
  },
  {
    title: '创建时间',
    dataIndex: 'created_at',
    width: 180,
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 250,
    fixed: 'right',
    align: 'center'
  }
]

// 表格数据
const tableData = ref<DataRecord[]>([])

// 行选择配置
const rowSelection = reactive({
  type: 'checkbox',
  showCheckedAll: true,
  checkStrictly: false,
  onlyCurrent: false
})

/**
 * 获取平台中文名称
 */
const getPlatformText = (platform: string): string => {
  const platformMap: Record<string, string> = {
    douyin: '抖音',
    kuaishou: '快手',
    xiaohongshu: '小红书',
    weibo: '微博',
    bilibili: 'B站'
  }
  return platformMap[platform] || platform
}

/**
 * 获取分发状态颜色
 */
const getAssignmentStatusColor = (status: string) => {
  const colors = {
    pending: 'orange',
    in_progress: 'blue',
    completed: 'green'
  }
  return colors[status as keyof typeof colors] || 'gray'
}

/**
 * 获取分发状态文本
 */
const getAssignmentStatusText = (status: string) => {
  const texts = {
    pending: '待处理',
    in_progress: '处理中',
    completed: '已完成'
  }
  return texts[status as keyof typeof texts] || status
}

/**
 * 加载公司列表
 */
const loadCompanies = async () => {
  try {
    const response = await getActiveCompanies()
    if (response.data.success && response.data.data) {
      companies.value = response.data.data
    }
  } catch (error) {
    console.error('加载公司列表失败:', error)
  }
}

/**
 * 显示单个分发模态框
 */
const showSingleAssignModal = (record: DataRecord) => {
  currentRecord.value = record
  singleAssignForm.data_record_id = record.id
  singleAssignForm.company_id = 0
  singleAssignForm.assigned_to = undefined
  singleAssignForm.notes = ''
  showSingleAssignModalVisible.value = true
}

/**
 * 处理单个分发
 */
const handleSingleAssign = async () => {
  if (!singleAssignForm.data_record_id || !singleAssignForm.company_id || singleAssignForm.company_id === 0) {
    Message.error('请填写必填字段')
    return
  }
  
  try {
    singleAssignLoading.value = true
    await createDataAssignment(singleAssignForm)
    Message.success('分发成功')
    showSingleAssignModalVisible.value = false
    loadData()
  } catch (error) {
    console.error('分发失败:', error)
    Message.error('分发失败')
  } finally {
    singleAssignLoading.value = false
  }
}

/**
 * 处理批量分发
 */
const handleBatchAssign = async () => {
  if (!selectedRowKeys.value.length || !batchAssignForm.company_ids.length) {
    Message.error('请选择数据记录和公司')
    return
  }
  
  batchAssignForm.data_record_ids = selectedRowKeys.value.map(id => Number(id))
  
  try {
    batchAssignLoading.value = true
    await batchAssignData(batchAssignForm)
    Message.success('批量分发成功')
    showBatchAssignModal.value = false
    selectedRowKeys.value = []
    loadData()
  } catch (error) {
    console.error('批量分发失败:', error)
    Message.error('批量分发失败')
  } finally {
    batchAssignLoading.value = false
  }
}

/**
 * 移除选中的记录
 */
const removeSelectedRecord = (recordId: string) => {
  const index = selectedRowKeys.value.indexOf(recordId)
  if (index > -1) {
    selectedRowKeys.value.splice(index, 1)
  }
}

/**
 * 查看分发详情
 */
const viewAssignments = (record: DataRecord) => {
  router.push(`/assignment?data_record_id=${record.id}`)
}

/**
 * 处理搜索
 */
const handleSearch = () => {
  pagination.current = 1
  loadData()
}

/**
 * 处理重置
 */
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.platform = ''
  searchForm.claimStatus = ''
  searchForm.completeStatus = ''
  searchForm.company_id = undefined
  searchForm.assignmentStatus = ''
  searchForm.dateRange = []
  pagination.current = 1
  loadData()
}

/**
 * 处理页码变化
 */
const handlePageChange = (page: number) => {
  pagination.current = page
  loadData()
}

/**
 * 处理页面大小变化
 */
const handlePageSizeChange = (pageSize: number) => {
  pagination.pageSize = pageSize
  pagination.current = 1
  loadData()
}

/**
 * 处理领取操作
 */
const handleClaim = async (record: DataRecord) => {
  // 防止重复点击
  if (claimingIds.value.has(record.id)) {
    return
  }
  
  try {
    // 设置加载状态
    claimingIds.value.add(record.id)
    
    // 调用后端API进行领取操作
    const response = await dataRecordApi.claimDataRecord(record.id)
    
    if (response.data) {
      // 更新本地数据状态
      record.is_claimed = true
      record.claimer = response.data.claimer
      record.claimer_id = response.data.claimer_id
      
      Message.success('领取成功')
    }
  } catch (error: any) {
    console.error('领取失败:', error)
    
    // 处理不同类型的错误
    if (error.response?.data?.message) {
      Message.error(error.response.data.message)
    } else if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat()
      Message.error(errorMessages[0] as string)
    } else {
      Message.error('领取失败，请稍后重试')
    }
  } finally {
    // 清除加载状态
    claimingIds.value.delete(record.id)
  }
}

/**
 * 处理完成操作
 */
const handleComplete = async (record: DataRecord) => {
  // 防止重复点击
  if (completingIds.value.has(record.id)) {
    return
  }
  
  try {
    // 设置加载状态
    completingIds.value.add(record.id)
    
    // 调用后端API进行完成操作
    const response = await dataRecordApi.completeDataRecord(record.id)
    
    if (response.data) {
      // 更新本地数据状态
      record.is_completed = true
      
      Message.success('已通过成功')
    }
  } catch (error: any) {
    console.error('已通过失败:', error)
    
    // 处理不同类型的错误
    if (error.response?.data?.message) {
      Message.error(error.response.data.message)
    } else if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat()
      Message.error(errorMessages[0] as string)
    } else {
      Message.error('已通过失败，请稍后重试')
    }
  } finally {
    // 清除加载状态
    completingIds.value.delete(record.id)
  }
}

/**
 * 加载数据
 */
const loadData = async () => {
  try {
    loading.value = true
    
    // 构建查询参数
    const params: any = {
      page: pagination.current,
      per_page: pagination.pageSize,
      include_assignments: true // 包含分发信息
    }
    
    // 添加搜索条件
    if (searchForm.keyword) {
      params.search = searchForm.keyword
    }
    
    if (searchForm.platform) {
      params.platform = searchForm.platform
    }
    
    if (searchForm.claimStatus) {
      params.is_claimed = searchForm.claimStatus === 'claimed' ? 1 : 0
    }
    
    if (searchForm.completeStatus) {
      params.is_completed = searchForm.completeStatus === 'completed' ? 1 : 0
    }
    
    if (searchForm.company_id) {
      params.company_id = searchForm.company_id
    }
    
    if (searchForm.assignmentStatus) {
      params.assignment_status = searchForm.assignmentStatus
    }
    
    // 处理日期范围
    if (searchForm.dateRange && searchForm.dateRange.length === 2) {
      params.start_date = searchForm.dateRange[0]
      params.end_date = searchForm.dateRange[1]
    }
    
    const response = await dataRecordApi.getList(params)
    
    if (response.data) {
      tableData.value = response.data.data
      pagination.total = response.data.total
      pagination.current = response.data.current_page
    }
  } catch (error) {
    console.error('加载数据失败:', error)
    Message.error('加载数据失败')
  } finally {
    loading.value = false
  }
}

// 组件挂载时获取数据
onMounted(() => {
  loadData()
  loadCompanies()
})
</script>

<style scoped>
.dashboard-container {
  padding: 24px;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.dashboard-header {
  margin-bottom: 24px;
}

.dashboard-header h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 600;
  color: #1d2129;
}

.dashboard-header p {
  margin: 0;
  font-size: 16px;
  color: #86909c;
}

.search-card,
.table-card,
.action-card {
  margin-bottom: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.search-card :deep(.arco-card-header-title),
.table-card :deep(.arco-card-header-title) {
  font-size: 18px;
  font-weight: 600;
  color: #1d2129;
}

.search-form {
  margin: 0;
}

.search-form :deep(.arco-form-item) {
  margin-bottom: 16px;
  margin-right: 16px;
}

.table-card :deep(.arco-table-th) {
  background-color: #f7f8fa;
  font-weight: 600;
}

.table-card :deep(.arco-table-td) {
  padding: 12px 16px;
}

/* 手机号显示样式 */
.phone-display {
  font-family: 'Courier New', monospace;
  color: #1d2129;
  font-weight: 500;
}

.phone-empty {
  color: #86909c;
  font-style: italic;
}

/* 分发状态样式 */
.assignment-status {
  max-width: 180px;
}

.assignment-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.assignment-item {
  padding: 8px;
  background: #f7f8fa;
  border-radius: 4px;
  border-left: 3px solid #165dff;
}

.company-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.company-name {
  font-weight: 500;
  font-size: 12px;
}

.assignment-user {
  font-size: 11px;
  color: #86909c;
}

.no-assignment {
  color: #86909c;
  font-style: italic;
}

/* 模态框样式 */
.selected-records {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  max-height: 120px;
  overflow-y: auto;
}

.record-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: #f7f8fa;
  border-radius: 4px;
}

/* 响应式设计 */
@media (max-width: 1200px) {
  .search-form {
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 16px;
  }
  
  .search-form {
    flex-direction: column;
  }
  
  .search-form :deep(.arco-form-item) {
    margin-bottom: 12px;
    margin-right: 0;
  }
}
</style>