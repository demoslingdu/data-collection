<template>
  <div class="claim-statistics-container">
    <!-- 页面标题 -->
    <div class="page-header">
      <h1 class="page-title">领取统计</h1>
      <p class="page-description">查看数据领取情况和用户工作效率统计</p>
    </div>

    <!-- 日期筛选器 -->
    <div class="filter-section">
      <a-card title="筛选条件" :bordered="false">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="选择公司">
              <a-select
                v-model="selectedCompanyId"
                :placeholder="'选择公司'"
                :allow-clear="false"
                style="width: 100%"
                @change="handleCompanyChange"
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
          </a-col>
          <a-col :span="8">
            <a-form-item label="查询日期">
              <a-date-picker
                v-model="selectedDate"
                :placeholder="'选择日期'"
                :allow-clear="true"
                style="width: 100%"
                @change="handleDateChange"
              />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="开始日期">
              <a-date-picker
                v-model="startDate"
                :placeholder="'开始日期'"
                :allow-clear="true"
                style="width: 100%"
                @change="handleDateRangeChange"
              />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="结束日期">
              <a-date-picker
                v-model="endDate"
                :placeholder="'结束日期'"
                :allow-clear="true"
                style="width: 100%"
                @change="handleDateRangeChange"
              />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row>
          <a-col :span="24">
            <a-space>
              <a-button type="primary" @click="loadStatistics">查询</a-button>
              <a-button @click="resetFilters">重置</a-button>
            </a-space>
          </a-col>
        </a-row>
      </a-card>
    </div>

    <!-- 统计卡片区域 -->
    <div class="statistics-cards">
      <a-row :gutter="16">
        <a-col :xs="24" :sm="12" :md="6">
          <a-card class="stat-card">
            <a-statistic
              title="总数据量"
              :value="statisticsData.total_records"
              :value-style="{ color: '#1890ff' }"
            >
              <template #prefix>
                <icon-file />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="6">
          <a-card class="stat-card">
            <a-statistic
              title="已领取数据"
              :value="statisticsData.claimed_records"
              :value-style="{ color: '#52c41a' }"
            >
              <template #prefix>
                <icon-check-circle />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="6">
          <a-card class="stat-card">
            <a-statistic
              title="已通过数据"
              :value="statisticsData.completed_records"
              :value-style="{ color: '#faad14' }"
            >
              <template #prefix>
                <icon-check />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="6">
          <a-card class="stat-card">
            <a-statistic
              title="通过率"
              :value="statisticsData.completion_rate"
              suffix="%"
              :precision="2"
              :value-style="{ color: '#f5222d' }"
            >
              <template #prefix>
                <icon-trophy />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
      </a-row>
    </div>

    <!-- 用户领取排行榜 -->
    <div class="user-ranking">
      <a-card title="用户领取排行榜" :bordered="false">
        <a-table
          :columns="tableColumns"
          :data="statisticsData.user_statistics"
          :loading="loading"
          :pagination="{
            showTotal: true,
            showPageSize: true,
            pageSizeOptions: ['10', '20', '50', '100']
          }"
          :scroll="{ x: 800 }"
        >
          <template #user_name="{ record }">
            <a-tag color="blue">{{ record.user_name }}</a-tag>
          </template>
          <template #completion_rate="{ record }">
            <a-progress
              :percent="record.completion_rate / 100"
              :show-text="true"
              :size="'small'"
              :color="getProgressColor(record.completion_rate / 100)"
            />
          </template>
        </a-table>
      </a-card>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 领取统计页面组件
 * 提供数据领取情况分析和用户工作效率统计功能
 */
import { ref, reactive, onMounted } from 'vue'
import { Message } from '@arco-design/web-vue'
import {
  IconFile,
  IconCheckCircle,
  IconCheck,
  IconTrophy
} from '@arco-design/web-vue/es/icon'
import { getClaimStatistics, type ClaimStatisticsResponse, type StatisticsParams } from '@/api/statistics'
import { getActiveCompanies, type Company } from '@/api/company'
import { useAuthStore } from '@/stores/auth'

// 响应式数据
const loading = ref(false)
const selectedDate = ref('')
const startDate = ref('')
const endDate = ref('')
const selectedCompanyId = ref<number | null>(null)
const companies = ref<Company[]>([])

// 用户信息
const authStore = useAuthStore()

// 统计数据
const statisticsData = reactive<ClaimStatisticsResponse>({
  total_records: 0,
  claimed_records: 0,
  completed_records: 0,
  completion_rate: 0,
  user_statistics: []
})

// 表格列定义
const tableColumns = [
  {
    title: '排名',
    dataIndex: 'index',
    width: 80,
    render: ({ rowIndex }: { rowIndex: number }) => rowIndex + 1
  },
  {
    title: '用户名称',
    dataIndex: 'user_name',
    slotName: 'user_name',
    width: 120
  },
  {
    title: '领取数量',
    dataIndex: 'claimed_count',
    width: 100,
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  },
  {
    title: '完成数量',
    dataIndex: 'completed_count',
    width: 100,
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  },
  {
    title: '完成率',
    dataIndex: 'completion_rate',
    slotName: 'completion_rate',
    width: 150,
    sortable: {
      sortDirections: ['ascend', 'descend']
    }
  }
]

/**
 * 获取进度条颜色
 * @param rate 完成率（0-1之间的小数）
 * @returns 颜色值
 */
const getProgressColor = (rate: number): string => {
  if (rate >= 0.8) return '#52c41a'
  if (rate >= 0.6) return '#faad14'
  if (rate >= 0.4) return '#ff7875'
  return '#f5222d'
}

/**
 * 处理单日期变化
 */
const handleDateChange = () => {
  if (selectedDate.value) {
    startDate.value = ''
    endDate.value = ''
  }
}

/**
 * 处理日期范围变化
 */
const handleDateRangeChange = () => {
  if (startDate.value || endDate.value) {
    selectedDate.value = ''
  }
}

/**
 * 处理公司选择变化
 */
const handleCompanyChange = () => {
  loadStatistics()
}

/**
 * 重置筛选条件
 */
const resetFilters = () => {
  selectedDate.value = ''
  startDate.value = ''
  endDate.value = ''
  selectedCompanyId.value = authStore.user?.company_id || null
  loadStatistics()
}

/**
 * 加载统计数据
 */
const loadStatistics = async () => {
  try {
    loading.value = true
    
    const params: StatisticsParams = {}
    
    if (selectedDate.value) {
      params.date = selectedDate.value
    } else if (startDate.value && endDate.value) {
      params.start_date = startDate.value
      params.end_date = endDate.value
    }
    
    // 添加公司ID参数
    if (selectedCompanyId.value) {
      params.company_id = selectedCompanyId.value
    }
    
    const data = await getClaimStatistics(params)
    
    // 更新统计数据
    Object.assign(statisticsData, data)
    
    Message.success('统计数据加载成功')
  } catch (error) {
    console.error('加载统计数据失败:', error)
    Message.error('加载统计数据失败，请稍后重试')
  } finally {
    loading.value = false
  }
}

/**
 * 加载公司列表
 */
const loadCompanies = async () => {
  try {
    const response = await getActiveCompanies()
    if (response.data) {
      companies.value = response.data
      // 默认选择当前用户的公司
      if (authStore.user?.company_id) {
        selectedCompanyId.value = authStore.user.company_id
      }
    }
  } catch (error) {
    console.error('加载公司列表失败:', error)
    Message.error('加载公司列表失败')
  }
}

// 组件挂载时加载数据
onMounted(() => {
  loadCompanies()
  loadStatistics()
})
</script>

<style scoped>
.claim-statistics-container {
  padding: 20px;
  background-color: #f5f5f5;
  min-height: 100vh;
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
  font-size: 14px;
  color: #86909c;
  margin: 0;
}

.filter-section {
  margin-bottom: 24px;
}

.statistics-cards {
  margin-bottom: 24px;
}

.stat-card {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.stat-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.user-ranking {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* 响应式设计 */
@media (max-width: 768px) {
  .claim-statistics-container {
    padding: 16px;
  }
  
  .page-title {
    font-size: 20px;
  }
  
  .statistics-cards .arco-col {
    margin-bottom: 16px;
  }
}

@media (max-width: 576px) {
  .claim-statistics-container {
    padding: 12px;
  }
  
  .page-title {
    font-size: 18px;
  }
}
</style>