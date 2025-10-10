<template>
  <div class="dashboard-container">
    <!-- 页面头部 -->
    <div class="dashboard-header">
      <h1>数据记录管理</h1>
      <p>管理和查看所有数据记录，支持搜索、筛选功能</p>
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
        :scroll="{ x: 1520, y: 600 }"
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
        <template #actions="{ record }">
          <a-space size="mini">
            <!-- 领取按钮：仅在未领取且未完成时显示 -->
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
            <!-- 完成按钮：仅在已领取且未完成时显示 -->
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
            <!-- 已完成状态显示 -->
            <a-tag v-if="record.is_completed" color="green" size="small">
              已完成
            </a-tag>
          </a-space>
        </template>
        <template #empty>
          <a-empty description="暂无数据记录" />
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup lang="ts">
/**
 * 数据记录列表页面
 * 提供数据记录的查看、搜索、筛选、分页等功能
 */
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Message, Modal } from '@arco-design/web-vue'
import {
  IconSearch,
  IconRefresh
} from '@arco-design/web-vue/es/icon'
import { dataRecordApi } from '@/api/dataRecord'
import type { DataRecord } from '@/api/dataRecord'

// 路由实例
const router = useRouter()

// 响应式数据
const loading = ref(false)
const selectedRowKeys = ref<string[]>([])

// 按钮加载状态
const claimingIds = ref<Set<number>>(new Set())
const completingIds = ref<Set<number>>(new Set())

// 搜索表单
const searchForm = reactive({
  keyword: '',
  platform: '',
  claimStatus: '',
  completeStatus: '',
  dateRange: []
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
    width: 200,
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
    weibo: '微博'
  }
  return platformMap[platform] || platform
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
  searchForm.dateRange = []
  pagination.current = 1
  loadData()
}



/**
 * 处理查看记录
 */
const handleView = (id: number) => {
  router.push(`/data/${id}`)
}

/**
 * 处理编辑记录
 */
const handleEdit = (id: number) => {
  router.push(`/data/${id}/edit`)
}

/**
 * 处理删除记录
 */
const handleDelete = async (id: number) => {
  Modal.confirm({
    title: '确认删除',
    content: '确定要删除这条数据记录吗？删除后无法恢复。',
    okText: '确认删除',
    cancelText: '取消',
    okButtonProps: { status: 'danger' },
    onOk: async () => {
      try {
        await dataRecordApi.deleteDataRecord(id)
        Message.success('删除成功')
        loadData()
      } catch (error) {
        console.error('删除失败:', error)
        Message.error('删除失败，请重试')
      }
    }
  })
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
      per_page: pagination.pageSize
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
.table-card {
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