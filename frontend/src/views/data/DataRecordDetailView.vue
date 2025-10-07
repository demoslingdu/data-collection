<template>
  <div class="data-record-detail">
    <!-- 页面头部 -->
    <div class="page-header">
      <a-page-header 
        title="数据记录详情" 
        :subtitle="`记录ID: ${recordId}`"
        @back="handleBack"
      >
        <template #extra>
          <a-space>
            <a-button type="primary" @click="handleEdit">
              <template #icon>
                <icon-edit />
              </template>
              编辑
            </a-button>
            <a-popconfirm
              content="确定要删除这条记录吗？"
              @ok="handleDelete"
            >
              <a-button status="danger">
                <template #icon>
                  <icon-delete />
                </template>
                删除
              </a-button>
            </a-popconfirm>
          </a-space>
        </template>
      </a-page-header>
    </div>

    <!-- 加载状态 -->
    <div v-if="loading" class="loading-section">
      <a-spin size="large" />
    </div>

    <!-- 记录详情内容 -->
    <div v-else-if="recordData" class="detail-content">
      <!-- 基本信息 -->
      <div class="info-section">
        <a-card title="基本信息">
          <a-descriptions :column="2" bordered>
            <a-descriptions-item label="记录标题">
              {{ recordData.title }}
            </a-descriptions-item>
            <a-descriptions-item label="记录状态">
              <a-tag :color="getStatusColor(recordData.status)">
                {{ getStatusText(recordData.status) }}
              </a-tag>
            </a-descriptions-item>
            <a-descriptions-item label="分类">
              {{ recordData.category || '未分类' }}
            </a-descriptions-item>
            <a-descriptions-item label="优先级">
              <a-tag :color="getPriorityColor(recordData.priority)">
                {{ getPriorityText(recordData.priority) }}
              </a-tag>
            </a-descriptions-item>
            <a-descriptions-item label="创建时间">
              {{ recordData.created_at }}
            </a-descriptions-item>
            <a-descriptions-item label="更新时间">
              {{ recordData.updated_at }}
            </a-descriptions-item>
            <a-descriptions-item label="开始日期">
              {{ recordData.start_date || '未设置' }}
            </a-descriptions-item>
            <a-descriptions-item label="结束日期">
              {{ recordData.end_date || '未设置' }}
            </a-descriptions-item>
          </a-descriptions>
        </a-card>
      </div>

      <!-- 记录内容 -->
      <div class="content-section">
        <a-card title="记录内容">
          <div class="content-text">
            {{ recordData.content }}
          </div>
        </a-card>
      </div>

      <!-- 标签信息 -->
      <div v-if="recordData.tags && recordData.tags.length > 0" class="tags-section">
        <a-card title="标签">
          <a-space wrap>
            <a-tag
              v-for="tag in recordData.tags"
              :key="tag"
              color="blue"
            >
              {{ tag }}
            </a-tag>
          </a-space>
        </a-card>
      </div>

      <!-- 附件信息 -->
      <div v-if="recordData.attachments && recordData.attachments.length > 0" class="attachments-section">
        <a-card title="附件">
          <a-list :data="recordData.attachments">
            <template #item="{ item }">
              <a-list-item>
                <a-list-item-meta>
                  <template #title>
                    <a-link @click="handleDownload(item)">
                      <icon-file />
                      {{ item.name }}
                    </a-link>
                  </template>
                  <template #description>
                    大小: {{ formatFileSize(item.size) }} | 
                    上传时间: {{ item.upload_time }}
                  </template>
                </a-list-item-meta>
                <template #actions>
                  <a-button type="text" @click="handleDownload(item)">
                    <template #icon>
                      <icon-download />
                    </template>
                    下载
                  </a-button>
                </template>
              </a-list-item>
            </template>
          </a-list>
        </a-card>
      </div>

      <!-- 备注信息 -->
      <div v-if="recordData.remarks" class="remarks-section">
        <a-card title="备注">
          <div class="remarks-text">
            {{ recordData.remarks }}
          </div>
        </a-card>
      </div>

      <!-- 操作历史 -->
      <div class="history-section">
        <a-card title="操作历史">
          <a-timeline>
            <a-timeline-item
              v-for="history in operationHistory"
              :key="history.id"
            >
              <template #dot>
                <icon-check-circle v-if="history.type === 'create'" style="color: #00b42a" />
                <icon-edit v-else-if="history.type === 'update'" style="color: #165dff" />
                <icon-info-circle v-else style="color: #86909c" />
              </template>
              <div class="history-item">
                <div class="history-action">{{ history.action }}</div>
                <div class="history-time">{{ history.time }}</div>
                <div class="history-user">操作人: {{ history.user }}</div>
              </div>
            </a-timeline-item>
          </a-timeline>
        </a-card>
      </div>
    </div>

    <!-- 记录不存在 -->
    <div v-else class="not-found-section">
      <a-result
        status="404"
        title="记录不存在"
        subtitle="您访问的数据记录不存在或已被删除"
      >
        <template #extra>
          <a-button type="primary" @click="handleBack">
            返回列表
          </a-button>
        </template>
      </a-result>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 数据记录详情页面
 * 展示数据记录的详细信息和操作历史
 */
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import {
  IconEdit,
  IconDelete,
  IconFile,
  IconDownload,
  IconCheckCircle,
  IconInfoCircle
} from '@arco-design/web-vue/es/icon'

// 路由实例
const router = useRouter()
const route = useRoute()

// 响应式数据
const loading = ref(true)
const recordId = ref(route.params.id as string)

// 记录数据
const recordData = ref({
  id: '1',
  title: '示例数据记录详情',
  content: '这是一条详细的数据记录内容，包含了完整的描述信息。这里可以展示记录的具体内容，支持长文本显示。用户可以通过这个页面查看记录的所有详细信息，包括基本信息、内容、标签、附件等。',
  status: 'active',
  category: 'type1',
  priority: 'high',
  start_date: '2024-01-15',
  end_date: '2024-01-30',
  tags: ['重要', '紧急', '项目A'],
  attachments: [
    {
      id: '1',
      name: '附件文档.pdf',
      size: 1024000,
      upload_time: '2024-01-15 10:30:00'
    },
    {
      id: '2',
      name: '数据表格.xlsx',
      size: 512000,
      upload_time: '2024-01-15 11:00:00'
    }
  ],
  remarks: '这是一条备注信息，用于记录额外的说明或注意事项。',
  created_at: '2024-01-15 10:30:00',
  updated_at: '2024-01-15 15:20:00'
})

// 操作历史
const operationHistory = ref([
  {
    id: '1',
    type: 'create',
    action: '创建了数据记录',
    time: '2024-01-15 10:30:00',
    user: '张三'
  },
  {
    id: '2',
    type: 'update',
    action: '更新了记录内容',
    time: '2024-01-15 15:20:00',
    user: '李四'
  },
  {
    id: '3',
    type: 'view',
    action: '查看了记录详情',
    time: '2024-01-16 09:15:00',
    user: '王五'
  }
])

/**
 * 获取状态颜色
 */
const getStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    active: 'green',
    inactive: 'red',
    pending: 'orange'
  }
  return colorMap[status] || 'gray'
}

/**
 * 获取状态文本
 */
const getStatusText = (status: string): string => {
  const textMap: Record<string, string> = {
    active: '活跃',
    inactive: '非活跃',
    pending: '待处理'
  }
  return textMap[status] || '未知'
}

/**
 * 获取优先级颜色
 */
const getPriorityColor = (priority: string): string => {
  const colorMap: Record<string, string> = {
    high: 'red',
    medium: 'orange',
    low: 'blue'
  }
  return colorMap[priority] || 'gray'
}

/**
 * 获取优先级文本
 */
const getPriorityText = (priority: string): string => {
  const textMap: Record<string, string> = {
    high: '高',
    medium: '中',
    low: '低'
  }
  return textMap[priority] || '未知'
}

/**
 * 格式化文件大小
 */
const formatFileSize = (size: number): string => {
  if (size < 1024) {
    return `${size} B`
  } else if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`
  } else {
    return `${(size / (1024 * 1024)).toFixed(1)} MB`
  }
}

/**
 * 处理编辑
 */
const handleEdit = () => {
  router.push(`/data/${recordId.value}/edit`)
}

/**
 * 处理删除
 */
const handleDelete = async () => {
  try {
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 500))
    
    Message.success('记录删除成功')
    router.push('/data')
  } catch (error) {
    Message.error('删除失败，请重试')
  }
}

/**
 * 处理文件下载
 */
const handleDownload = (file: any) => {
  Message.success(`开始下载文件: ${file.name}`)
  // 这里实现文件下载逻辑
}

/**
 * 处理返回
 */
const handleBack = () => {
  router.back()
}

/**
 * 加载记录数据
 */
const loadRecordData = async () => {
  loading.value = true
  try {
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 800))
    
    // 这里应该根据recordId从API获取数据
    // const response = await api.getRecord(recordId.value)
    // recordData.value = response.data
    
  } catch (error) {
    Message.error('加载记录详情失败')
    recordData.value = null
  } finally {
    loading.value = false
  }
}

// 组件挂载时加载数据
onMounted(() => {
  loadRecordData()
})
</script>

<style scoped>
.data-record-detail {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.loading-section {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 400px;
}

.detail-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.info-section,
.content-section,
.tags-section,
.attachments-section,
.remarks-section,
.history-section {
  margin-bottom: 20px;
}

.content-text,
.remarks-text {
  line-height: 1.6;
  color: var(--color-text-1);
  white-space: pre-wrap;
  word-break: break-word;
}

.history-item {
  padding: 8px 0;
}

.history-action {
  font-weight: 500;
  color: var(--color-text-1);
  margin-bottom: 4px;
}

.history-time {
  font-size: 12px;
  color: var(--color-text-3);
  margin-bottom: 2px;
}

.history-user {
  font-size: 12px;
  color: var(--color-text-2);
}

.not-found-section {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 400px;
}
</style>