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
        <a-form-item label="状态">
          <a-select
            v-model="searchForm.status"
            placeholder="请选择状态"
            style="width: 120px"
            allow-clear
          >
            <a-option value="unclaimed">未领取</a-option>
            <a-option value="claimed">已领取</a-option>
            <a-option value="completed">已完成</a-option>
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
        :scroll="{ x: 1200, y: 600 }"
        stripe
        hoverable
        size="large"
      >
        <template #status="{ record }">
          <a-tag :color="getStatusColor(record.status)">
            {{ getStatusText(record.status) }}
          </a-tag>
        </template>
        <template #actions="{ record }">
          <a-space size="small">
            <a-button type="text" size="mini" @click="handleView(record.id)">
              <template #icon>
                <icon-eye />
              </template>
              查看
            </a-button>
            <a-button type="text" size="mini" @click="handleEdit(record.id)">
              <template #icon>
                <icon-edit />
              </template>
              编辑
            </a-button>
            <a-button type="text" size="mini" status="danger" @click="handleDelete(record.id)">
              <template #icon>
                <icon-delete />
              </template>
              删除
            </a-button>
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
  IconDelete,
  IconRefresh,
  IconEye,
  IconEdit
} from '@arco-design/web-vue/es/icon'
import { dataRecordApi } from '@/api/dataRecord'
import type { DataRecord } from '@/api/dataRecord'

// 路由实例
const router = useRouter()

// 响应式数据
const loading = ref(false)
const selectedRowKeys = ref<string[]>([])

// 搜索表单
const searchForm = reactive({
  keyword: '',
  status: '',
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
    title: '标题',
    dataIndex: 'title',
    width: 200,
    ellipsis: true,
    tooltip: true
  },
  {
    title: '描述',
    dataIndex: 'description',
    width: 300,
    ellipsis: true,
    tooltip: true
  },
  {
    title: '状态',
    dataIndex: 'status',
    slotName: 'status',
    width: 120,
    align: 'center',
    filterable: {
      filters: [
        { text: '未领取', value: 'unclaimed' },
        { text: '已领取', value: 'claimed' },
        { text: '已完成', value: 'completed' }
      ],
      multiple: true
    }
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
    title: '更新时间',
    dataIndex: 'updated_at',
    width: 180,
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 260,
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
 * 获取状态颜色
 */
const getStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    unclaimed: 'blue',
    claimed: 'orange',
    completed: 'green'
  }
  return colorMap[status] || 'gray'
}

/**
 * 获取状态文本
 */
const getStatusText = (status: string): string => {
  const textMap: Record<string, string> = {
    unclaimed: '未领取',
    claimed: '已领取',
    completed: '已完成'
  }
  return textMap[status] || '未知'
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
  searchForm.status = ''
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
    
    if (searchForm.status) {
      params.status = searchForm.status
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