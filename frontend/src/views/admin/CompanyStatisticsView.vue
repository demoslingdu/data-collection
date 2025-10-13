<template>
  <div class="company-statistics-view">
    <!-- 页面标题 -->
    <div class="page-header">
      <h1 class="page-title">公司统计报表</h1>
      <p class="page-description">查看各公司的数据处理统计信息</p>
    </div>

    <!-- 统计卡片 -->
    <div class="statistics-cards">
      <a-card class="stat-card">
        <a-statistic
          title="总公司数"
          :value="overallStats.totalCompanies"
          :value-style="{ color: '#1890ff' }"
        >
          <template #prefix>
            <icon-home />
          </template>
        </a-statistic>
      </a-card>

      <a-card class="stat-card">
        <a-statistic
          title="活跃公司数"
          :value="overallStats.activeCompanies"
          :value-style="{ color: '#52c41a' }"
        >
          <template #prefix>
            <icon-check-circle />
          </template>
        </a-statistic>
      </a-card>

      <a-card class="stat-card">
        <a-statistic
          title="总分发数据量"
          :value="overallStats.totalAssignments"
          :value-style="{ color: '#722ed1' }"
        >
          <template #prefix>
            <icon-send />
          </template>
        </a-statistic>
      </a-card>

      <a-card class="stat-card">
        <a-statistic
          title="已完成数据量"
          :value="overallStats.completedAssignments"
          :value-style="{ color: '#fa8c16' }"
        >
          <template #prefix>
            <icon-check />
          </template>
        </a-statistic>
      </a-card>
    </div>

    <!-- 筛选条件 -->
    <a-card class="filter-card">
      <a-form
        :model="searchForm"
        layout="inline"
        @submit="handleSearch"
      >
        <a-form-item label="公司名称">
          <a-input
            v-model="searchForm.keyword"
            placeholder="请输入公司名称"
            allow-clear
            style="width: 200px"
          />
        </a-form-item>

        <a-form-item label="公司状态">
          <a-select
            v-model="searchForm.status"
            placeholder="请选择状态"
            allow-clear
            style="width: 150px"
          >
            <a-option value="active">活跃</a-option>
            <a-option value="inactive">停用</a-option>
          </a-select>
        </a-form-item>

        <a-form-item label="时间范围">
          <a-range-picker
            v-model="searchForm.dateRange"
            style="width: 300px"
          />
        </a-form-item>

        <a-form-item>
          <a-button type="primary" html-type="submit" :loading="loading">
            <template #icon>
              <icon-search />
            </template>
            查询
          </a-button>
          <a-button @click="handleReset" style="margin-left: 8px">
            重置
          </a-button>
        </a-form-item>
      </a-form>
    </a-card>

    <!-- 公司统计表格 -->
    <a-card class="table-card">
      <template #title>
        <div class="table-header">
          <span>公司统计详情</span>
          <a-button type="primary" @click="handleExport" :loading="exportLoading">
            <template #icon>
              <icon-download />
            </template>
            导出报表
          </a-button>
        </div>
      </template>

      <a-table
        :columns="columns"
        :data="tableData"
        :loading="loading"
        :pagination="pagination"
        @page-change="handlePageChange"
        @page-size-change="handlePageSizeChange"
        row-key="id"
      >
        <template #status="{ record }">
          <a-tag :color="record.status === 'active' ? 'green' : 'red'">
            {{ record.status === 'active' ? '活跃' : '停用' }}
          </a-tag>
        </template>

        <template #completionRate="{ record }">
          <div class="completion-rate">
            <a-progress
              :percent="record.completionRate"
              :color="getProgressColor(record.completionRate)"
              size="small"
            />
            <span class="rate-text">{{ record.completionRate }}%</span>
          </div>
        </template>

        <template #actions="{ record }">
          <a-button
            type="text"
            size="small"
            @click="viewCompanyDetail(record)"
          >
            查看详情
          </a-button>
          <a-button
            type="text"
            size="small"
            @click="viewAssignments(record)"
          >
            查看分发
          </a-button>
        </template>
      </a-table>
    </a-card>

    <!-- 公司详情模态框 -->
    <a-modal
      v-model:visible="detailModalVisible"
      title="公司统计详情"
      width="800px"
      :footer="false"
    >
      <div v-if="selectedCompany" class="company-detail">
        <!-- 基本信息 -->
        <div class="detail-section">
          <h3>基本信息</h3>
          <a-descriptions :column="2" bordered>
            <a-descriptions-item label="公司名称">
              {{ selectedCompany.name }}
            </a-descriptions-item>
            <a-descriptions-item label="公司代码">
              {{ selectedCompany.code }}
            </a-descriptions-item>
            <a-descriptions-item label="状态">
              <a-tag :color="selectedCompany.status === 'active' ? 'green' : 'red'">
                {{ selectedCompany.status === 'active' ? '活跃' : '停用' }}
              </a-tag>
            </a-descriptions-item>
            <a-descriptions-item label="创建时间">
              {{ formatDateTime(selectedCompany.createdAt) }}
            </a-descriptions-item>
          </a-descriptions>
        </div>

        <!-- 统计信息 -->
        <div class="detail-section">
          <h3>数据统计</h3>
          <div class="stat-grid">
            <div class="stat-item">
              <div class="stat-value">{{ selectedCompany.totalAssignments }}</div>
              <div class="stat-label">总分发数量</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ selectedCompany.pendingAssignments }}</div>
              <div class="stat-label">待处理</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ selectedCompany.inProgressAssignments }}</div>
              <div class="stat-label">处理中</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ selectedCompany.completedAssignments }}</div>
              <div class="stat-label">已完成</div>
            </div>
          </div>
        </div>

        <!-- 完成率趋势 -->
        <div class="detail-section">
          <h3>完成率</h3>
          <div class="completion-chart">
            <a-progress
              :percent="selectedCompany.completionRate"
              :color="getProgressColor(selectedCompany.completionRate)"
              :stroke-width="12"
            />
            <p class="completion-text">
              完成率：{{ selectedCompany.completionRate }}%
              （{{ selectedCompany.completedAssignments }}/{{ selectedCompany.totalAssignments }}）
            </p>
          </div>
        </div>
      </div>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
/**
 * 公司统计报表页面
 * 提供各公司的数据处理统计信息展示和分析功能
 */
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import {
  IconHome,
  IconCheckCircle,
  IconSend,
  IconCheck,
  IconSearch,
  IconDownload
} from '@arco-design/web-vue/es/icon'
import { getCompanies } from '@/api/company'
import { getAssignmentStatistics } from '@/api/dataAssignment'
import type { Company } from '@/api/company'

const router = useRouter()

// 响应式数据
const loading = ref(false)
const exportLoading = ref(false)
const detailModalVisible = ref(false)
const selectedCompany = ref<CompanyStatistics | null>(null)

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
  total: 0,
  showSizeChanger: true,
  showTotal: true,
  pageSizeOptions: ['10', '20', '50', '100']
})

// 表格数据
const tableData = ref<CompanyStatistics[]>([])

// 总体统计数据
const overallStats = ref({
  totalCompanies: 0,
  activeCompanies: 0,
  totalAssignments: 0,
  completedAssignments: 0
})

// 公司统计接口类型
interface CompanyStatistics extends Company {
  totalAssignments: number
  pendingAssignments: number
  inProgressAssignments: number
  completedAssignments: number
  completionRate: number
}

// 表格列配置
const columns = [
  {
    title: '公司名称',
    dataIndex: 'name',
    width: 150,
    ellipsis: true
  },
  {
    title: '公司代码',
    dataIndex: 'code',
    width: 120
  },
  {
    title: '状态',
    dataIndex: 'status',
    slotName: 'status',
    width: 80
  },
  {
    title: '总分发数',
    dataIndex: 'totalAssignments',
    width: 100,
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  },
  {
    title: '待处理',
    dataIndex: 'pendingAssignments',
    width: 80
  },
  {
    title: '处理中',
    dataIndex: 'inProgressAssignments',
    width: 80
  },
  {
    title: '已完成',
    dataIndex: 'completedAssignments',
    width: 80
  },
  {
    title: '完成率',
    dataIndex: 'completionRate',
    slotName: 'completionRate',
    width: 150,
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  },
  {
    title: '创建时间',
    dataIndex: 'createdAt',
    width: 150,
    render: ({ record }: { record: CompanyStatistics }) => formatDateTime(record.createdAt)
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 150,
    fixed: 'right'
  }
]

/**
 * 获取进度条颜色
 */
const getProgressColor = (rate: number) => {
  if (rate >= 80) return '#52c41a'
  if (rate >= 60) return '#faad14'
  if (rate >= 40) return '#fa8c16'
  return '#f5222d'
}

/**
 * 格式化日期时间
 */
const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('zh-CN')
}

/**
 * 加载公司统计数据
 */
const loadCompanyStatistics = async () => {
  try {
    loading.value = true
    
    // 构建查询参数
    const params = {
      page: pagination.current,
      per_page: pagination.pageSize,
      keyword: searchForm.keyword || undefined,
      status: searchForm.status || undefined,
      start_date: searchForm.dateRange[0] || undefined,
      end_date: searchForm.dateRange[1] || undefined
    }

    // 获取公司列表
    const companiesResponse = await getCompanies(params)
    const companies = companiesResponse.data

    // 为每个公司获取统计数据
    const statisticsPromises = companies.map(async (company: Company) => {
      try {
        const statsResponse = await getAssignmentStatistics({ company_id: company.id })
        const stats = statsResponse.data
        
        const totalAssignments = stats.total || 0
        const completedAssignments = stats.completed || 0
        const completionRate = totalAssignments > 0 
          ? Math.round((completedAssignments / totalAssignments) * 100) 
          : 0

        return {
          ...company,
          totalAssignments,
          pendingAssignments: stats.pending || 0,
          inProgressAssignments: stats.in_progress || 0,
          completedAssignments,
          completionRate
        } as CompanyStatistics
      } catch (error) {
        console.error(`获取公司 ${company.name} 统计数据失败:`, error)
        return {
          ...company,
          totalAssignments: 0,
          pendingAssignments: 0,
          inProgressAssignments: 0,
          completedAssignments: 0,
          completionRate: 0
        } as CompanyStatistics
      }
    })

    const statisticsData = await Promise.all(statisticsPromises)
    tableData.value = statisticsData

    // 更新分页信息
    pagination.total = companiesResponse.meta?.total || 0

    // 计算总体统计
    calculateOverallStats(statisticsData)

  } catch (error) {
    console.error('加载公司统计数据失败:', error)
    Message.error('加载统计数据失败')
  } finally {
    loading.value = false
  }
}

/**
 * 计算总体统计数据
 */
const calculateOverallStats = (data: CompanyStatistics[]) => {
  overallStats.value = {
    totalCompanies: data.length,
    activeCompanies: data.filter(item => item.status === 'active').length,
    totalAssignments: data.reduce((sum, item) => sum + item.totalAssignments, 0),
    completedAssignments: data.reduce((sum, item) => sum + item.completedAssignments, 0)
  }
}

/**
 * 处理搜索
 */
const handleSearch = () => {
  pagination.current = 1
  loadCompanyStatistics()
}

/**
 * 处理重置
 */
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = ''
  searchForm.dateRange = []
  pagination.current = 1
  loadCompanyStatistics()
}

/**
 * 处理分页变化
 */
const handlePageChange = (page: number) => {
  pagination.current = page
  loadCompanyStatistics()
}

/**
 * 处理页面大小变化
 */
const handlePageSizeChange = (pageSize: number) => {
  pagination.pageSize = pageSize
  pagination.current = 1
  loadCompanyStatistics()
}

/**
 * 查看公司详情
 */
const viewCompanyDetail = (company: CompanyStatistics) => {
  selectedCompany.value = company
  detailModalVisible.value = true
}

/**
 * 查看公司分发记录
 */
const viewAssignments = (company: CompanyStatistics) => {
  router.push({
    path: '/assignments',
    query: { company_id: company.id }
  })
}

/**
 * 导出报表
 */
const handleExport = async () => {
  try {
    exportLoading.value = true
    
    // 这里可以调用导出API或者前端生成Excel
    // 暂时使用简单的CSV导出
    const csvContent = generateCSV(tableData.value)
    downloadCSV(csvContent, `公司统计报表_${new Date().toISOString().split('T')[0]}.csv`)
    
    Message.success('报表导出成功')
  } catch (error) {
    console.error('导出报表失败:', error)
    Message.error('导出报表失败')
  } finally {
    exportLoading.value = false
  }
}

/**
 * 生成CSV内容
 */
const generateCSV = (data: CompanyStatistics[]) => {
  const headers = ['公司名称', '公司代码', '状态', '总分发数', '待处理', '处理中', '已完成', '完成率(%)', '创建时间']
  const rows = data.map(item => [
    item.name,
    item.code,
    item.status === 'active' ? '活跃' : '停用',
    item.totalAssignments,
    item.pendingAssignments,
    item.inProgressAssignments,
    item.completedAssignments,
    item.completionRate,
    formatDateTime(item.createdAt)
  ])
  
  return [headers, ...rows].map(row => row.join(',')).join('\n')
}

/**
 * 下载CSV文件
 */
const downloadCSV = (content: string, filename: string) => {
  const blob = new Blob(['\uFEFF' + content], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', filename)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// 组件挂载时加载数据
onMounted(() => {
  loadCompanyStatistics()
})
</script>

<style scoped>
.company-statistics-view {
  padding: 0;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #1d2129;
  margin: 0 0 8px 0;
}

.page-description {
  color: #86909c;
  margin: 0;
}

.statistics-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  text-align: center;
}

.filter-card {
  margin-bottom: 16px;
}

.table-card {
  background: #fff;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.completion-rate {
  display: flex;
  align-items: center;
  gap: 8px;
}

.rate-text {
  font-size: 12px;
  color: #86909c;
  min-width: 35px;
}

.company-detail {
  max-height: 600px;
  overflow-y: auto;
}

.detail-section {
  margin-bottom: 24px;
}

.detail-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1d2129;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e6eb;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.stat-item {
  text-align: center;
  padding: 16px;
  background: #f7f8fa;
  border-radius: 6px;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: #1d2129;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;
  color: #86909c;
}

.completion-chart {
  text-align: center;
}

.completion-text {
  margin-top: 16px;
  color: #86909c;
  font-size: 14px;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .statistics-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .stat-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .table-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
}

@media (max-width: 480px) {
  .statistics-cards {
    grid-template-columns: 1fr;
  }
  
  .stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>