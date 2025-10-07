<template>
  <div class="dashboard-container">
    <!-- 页面头部 -->
    <div class="dashboard-header">
      <h1>数据采集仪表板</h1>
      <p>欢迎回来，{{ userStore.user?.name || '用户' }}！</p>
    </div>

    <!-- 统计卡片 -->
    <a-row :gutter="16" class="stats-row">
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="总数据记录"
            :value="stats.totalRecords"
            :loading="statsLoading"
          >
            <template #prefix>
              <icon-file />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="活跃记录"
            :value="stats.activeRecords"
            :loading="statsLoading"
          >
            <template #prefix>
              <icon-check-circle />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="今日新增"
            :value="stats.todayRecords"
            :loading="statsLoading"
          >
            <template #prefix>
              <icon-plus-circle />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card class="stat-card">
          <a-statistic
            title="分类数量"
            :value="stats.categoryCount"
            :loading="statsLoading"
          >
            <template #prefix>
              <icon-folder />
            </template>
          </a-statistic>
        </a-card>
      </a-col>
    </a-row>

    <!-- 快速操作 -->
    <a-card title="快速操作" class="quick-actions-card">
      <a-row :gutter="16">
        <a-col :span="6">
          <a-button type="primary" size="large" long @click="handleCreateRecord">
            <template #icon>
              <icon-plus />
            </template>
            新建数据记录
          </a-button>
        </a-col>
        <a-col :span="6">
          <a-button type="outline" size="large" long @click="handleViewRecords">
            <template #icon>
              <icon-list />
            </template>
            查看所有记录
          </a-button>
        </a-col>
        <a-col :span="6">
          <a-button type="outline" size="large" long @click="handleExportData">
            <template #icon>
              <icon-download />
            </template>
            导出数据
          </a-button>
        </a-col>
        <a-col :span="6">
          <a-button type="outline" size="large" long @click="handleSettings">
            <template #icon>
              <icon-settings />
            </template>
            系统设置
          </a-button>
        </a-col>
      </a-row>
    </a-card>

    <!-- 最近记录 -->
    <a-card title="最近记录" class="recent-records-card">
      <a-table
        :columns="recentRecordsColumns"
        :data="recentRecords"
        :loading="recentRecordsLoading"
        :pagination="false"
        size="small"
      >
        <template #status="{ record }">
          <a-tag :color="record.status === 'active' ? 'green' : 'gray'">
            {{ record.status === 'active' ? '活跃' : '非活跃' }}
          </a-tag>
        </template>
        <template #actions="{ record }">
          <a-button type="text" size="small" @click="handleViewRecord(record.id)">
            查看
          </a-button>
          <a-button type="text" size="small" @click="handleEditRecord(record.id)">
            编辑
          </a-button>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup lang="ts">
/**
 * 仪表板页面组件
 * 显示数据概览、统计信息和快速操作功能
 */
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import {
  IconFile,
  IconCheckCircle,
  IconPlusCircle,
  IconFolder,
  IconPlus,
  IconList,
  IconDownload,
  IconSettings
} from '@arco-design/web-vue/es/icon'
import { useAuthStore } from '@/stores/auth'
import { dataRecordApi } from '@/api/dataRecord'

// 路由和状态管理
const router = useRouter()
const userStore = useAuthStore()

// 响应式数据
const statsLoading = ref(false)
const recentRecordsLoading = ref(false)

// 统计数据
const stats = ref({
  totalRecords: 0,
  activeRecords: 0,
  todayRecords: 0,
  categoryCount: 0
})

// 最近记录数据
const recentRecords = ref([])

// 最近记录表格列配置
const recentRecordsColumns = [
  {
    title: '标题',
    dataIndex: 'title',
    width: 200
  },
  {
    title: '分类',
    dataIndex: 'category',
    width: 120
  },
  {
    title: '状态',
    dataIndex: 'status',
    slotName: 'status',
    width: 100
  },
  {
    title: '创建时间',
    dataIndex: 'created_at',
    width: 180
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 120,
    align: 'center'
  }
]

/**
 * 获取统计数据
 */
const fetchStats = async () => {
  try {
    statsLoading.value = true
    const response = await dataRecordApi.getStatistics()
    if (response.success) {
      stats.value = response.data
    }
  } catch (error) {
    console.error('获取统计数据失败:', error)
    Message.error('获取统计数据失败')
  } finally {
    statsLoading.value = false
  }
}

/**
 * 获取最近记录
 */
const fetchRecentRecords = async () => {
  try {
    recentRecordsLoading.value = true
    const response = await dataRecordApi.getList({ page: 1, per_page: 5 })
    if (response.success) {
      recentRecords.value = response.data.data
    }
  } catch (error) {
    console.error('获取最近记录失败:', error)
    Message.error('获取最近记录失败')
  } finally {
    recentRecordsLoading.value = false
  }
}

/**
 * 新建数据记录
 */
const handleCreateRecord = () => {
  router.push('/records/create')
}

/**
 * 查看所有记录
 */
const handleViewRecords = () => {
  router.push('/records')
}

/**
 * 导出数据
 */
const handleExportData = () => {
  Message.info('导出功能开发中...')
}

/**
 * 系统设置
 */
const handleSettings = () => {
  router.push('/settings')
}

/**
 * 查看记录详情
 */
const handleViewRecord = (id: number) => {
  router.push(`/records/${id}`)
}

/**
 * 编辑记录
 */
const handleEditRecord = (id: number) => {
  router.push(`/records/${id}/edit`)
}

// 组件挂载时获取数据
onMounted(() => {
  fetchStats()
  fetchRecentRecords()
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

.stats-row {
  margin-bottom: 24px;
}

.stat-card {
  text-align: center;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-card :deep(.arco-statistic-title) {
  font-size: 14px;
  color: #86909c;
  margin-bottom: 8px;
}

.stat-card :deep(.arco-statistic-value) {
  font-size: 24px;
  font-weight: 600;
  color: #1d2129;
}

.quick-actions-card,
.recent-records-card {
  margin-bottom: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.quick-actions-card :deep(.arco-card-header-title),
.recent-records-card :deep(.arco-card-header-title) {
  font-size: 18px;
  font-weight: 600;
  color: #1d2129;
}

.quick-actions-card .arco-btn {
  height: 48px;
  font-size: 14px;
  font-weight: 500;
}

.recent-records-card :deep(.arco-table-th) {
  background-color: #f7f8fa;
  font-weight: 600;
}

.recent-records-card :deep(.arco-table-td) {
  padding: 12px 16px;
}
</style>