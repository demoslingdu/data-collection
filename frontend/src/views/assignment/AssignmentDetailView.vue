<template>
  <div class="assignment-detail-container">
    <!-- 页面标题 -->
    <div class="page-header">
      <a-page-header 
        title="分发详情" 
        :subtitle="`分发ID: ${assignmentId}`"
        @back="goBack"
      >
        <template #extra>
          <a-space>
            <a-button 
              v-if="canEdit" 
              type="primary" 
              @click="showEditModal = true"
            >
              <template #icon><icon-edit /></template>
              编辑状态
            </a-button>
            <a-button type="outline" @click="loadAssignment">
              <template #icon><icon-refresh /></template>
              刷新
            </a-button>
          </a-space>
        </template>
      </a-page-header>
    </div>

    <!-- 加载状态 -->
    <div v-if="loading" class="loading-container">
      <a-spin size="large" />
    </div>

    <!-- 分发详情内容 -->
    <div v-else-if="assignment" class="detail-content">
      <!-- 基本信息卡片 -->
      <a-card title="基本信息" class="info-card">
        <a-descriptions :column="2" bordered>
          <a-descriptions-item label="分发状态">
            <a-tag 
                :color="getStatusColor(assignment.status || 'pending')"
                :bordered="false"
                size="large"
              >
                {{ getStatusText(assignment.status || 'pending') }}
              </a-tag>
          </a-descriptions-item>
          
          <a-descriptions-item label="分发公司">
            <div class="company-info">
              <div class="company-name">{{ assignment.company?.name }}</div>
              <div class="company-code">代码: {{ assignment.company?.code }}</div>
            </div>
          </a-descriptions-item>

          <a-descriptions-item label="分发人">
            <div class="user-info">
              <div class="user-name">{{ assignment.assigned_by_user?.name }}</div>
              <div class="user-email">{{ assignment.assigned_by_user?.email }}</div>
            </div>
          </a-descriptions-item>

          <a-descriptions-item label="处理人">
            <div v-if="assignment.assigned_to_user" class="user-info">
              <div class="user-name">{{ assignment.assigned_to_user.name }}</div>
              <div class="user-email">{{ assignment.assigned_to_user.email }}</div>
            </div>
            <span v-else class="text-gray">未指定</span>
          </a-descriptions-item>

          <a-descriptions-item label="分发时间">
            {{ formatDateTime(assignment.assigned_at) }}
          </a-descriptions-item>

          <a-descriptions-item label="开始时间">
            <span v-if="assignment.started_at">
              {{ formatDateTime(assignment.started_at) }}
            </span>
            <span v-else class="text-gray">未开始</span>
          </a-descriptions-item>

          <a-descriptions-item label="完成时间">
            <span v-if="assignment.completed_at">
              {{ formatDateTime(assignment.completed_at) }}
            </span>
            <span v-else class="text-gray">未完成</span>
          </a-descriptions-item>

          <a-descriptions-item label="处理时长">
            <span v-if="assignment.started_at && assignment.completed_at">
              {{ calculateDuration(assignment.started_at, assignment.completed_at) }}
            </span>
            <span v-else-if="assignment.started_at">
              {{ calculateDuration(assignment.started_at, new Date().toISOString()) }} (进行中)
            </span>
            <span v-else class="text-gray">-</span>
          </a-descriptions-item>
        </a-descriptions>

        <div v-if="assignment.notes" class="notes-section">
          <h4>备注信息</h4>
          <div class="notes-content">{{ assignment.notes }}</div>
        </div>
      </a-card>

      <!-- 数据记录信息卡片 -->
      <a-card title="数据记录信息" class="info-card">
        <div v-if="assignment.data_record" class="data-record-detail">
          <a-descriptions :column="2" bordered>
            <a-descriptions-item label="记录标题">
              {{ assignment.data_record.title || '无标题' }}
            </a-descriptions-item>

            <a-descriptions-item label="平台">
              <a-tag>{{ getPlatformText(assignment.data_record.platform) }}</a-tag>
            </a-descriptions-item>

            <a-descriptions-item label="记录状态">
              <a-tag 
                :color="getRecordStatusColor(assignment.data_record.status)"
                :bordered="false"
              >
                {{ getRecordStatusText(assignment.data_record.status) }}
              </a-tag>
            </a-descriptions-item>

            <a-descriptions-item label="创建时间">
              {{ formatDateTime(assignment.data_record.created_at) }}
            </a-descriptions-item>

            <a-descriptions-item label="提交人">
              <div v-if="assignment.data_record.submitter" class="user-info">
                <div class="user-name">{{ assignment.data_record.submitter.name }}</div>
                <div class="user-email">{{ assignment.data_record.submitter.email }}</div>
              </div>
              <span v-else class="text-gray">未知</span>
            </a-descriptions-item>

            <a-descriptions-item label="认领人">
              <div v-if="assignment.data_record.claimer" class="user-info">
                <div class="user-name">{{ assignment.data_record.claimer.name }}</div>
                <div class="user-email">{{ assignment.data_record.claimer.email }}</div>
              </div>
              <span v-else class="text-gray">未认领</span>
            </a-descriptions-item>
          </a-descriptions>

          <div v-if="assignment.data_record.content" class="content-section">
            <h4>记录内容</h4>
            <div class="content-display">{{ assignment.data_record.content }}</div>
          </div>

          <div v-if="assignment.data_record.url" class="url-section">
            <h4>原始链接</h4>
            <a :href="assignment.data_record.url" target="_blank" class="record-url">
              {{ assignment.data_record.url }}
              <icon-link />
            </a>
          </div>
        </div>
      </a-card>

      <!-- 处理历史卡片 -->
      <a-card title="处理历史" class="info-card">
        <a-timeline>
          <a-timeline-item>
            <template #dot>
              <icon-clock-circle style="color: #00b42a" />
            </template>
            <div class="timeline-content">
              <div class="timeline-title">分发创建</div>
              <div class="timeline-time">{{ formatDateTime(assignment.assigned_at) }}</div>
              <div class="timeline-user">
                分发人: {{ assignment.assigned_by_user?.name }}
              </div>
            </div>
          </a-timeline-item>

          <a-timeline-item v-if="assignment.started_at">
            <template #dot>
              <icon-play-circle style="color: #165dff" />
            </template>
            <div class="timeline-content">
              <div class="timeline-title">开始处理</div>
              <div class="timeline-time">{{ formatDateTime(assignment.started_at) }}</div>
              <div class="timeline-user">
                处理人: {{ assignment.assigned_to_user?.name }}
              </div>
            </div>
          </a-timeline-item>

          <a-timeline-item v-if="assignment.completed_at">
            <template #dot>
              <icon-check-circle style="color: #00b42a" />
            </template>
            <div class="timeline-content">
              <div class="timeline-title">处理完成</div>
              <div class="timeline-time">{{ formatDateTime(assignment.completed_at) }}</div>
              <div class="timeline-user">
                处理人: {{ assignment.assigned_to_user?.name }}
              </div>
            </div>
          </a-timeline-item>
        </a-timeline>
      </a-card>
    </div>

    <!-- 编辑状态模态框 -->
    <a-modal
      v-model:visible="showEditModal"
      title="编辑分发状态"
      width="500px"
      @ok="handleUpdateStatus"
      :confirm-loading="updateLoading"
    >
      <a-form :model="editForm" layout="vertical">
        <a-form-item label="当前状态" required>
          <a-select v-model="editForm.status" placeholder="选择状态">
            <a-option value="pending">待处理</a-option>
            <a-option value="in_progress">处理中</a-option>
            <a-option value="completed">已完成</a-option>
          </a-select>
        </a-form-item>

        <a-form-item label="备注">
          <a-textarea 
            v-model="editForm.notes" 
            placeholder="更新备注信息（可选）"
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
 * 数据分发详情页面
 * 显示分发的详细信息和处理历史
 */

import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import { 
  IconEdit, 
  IconRefresh, 
  IconClockCircle, 
  IconPlayCircle, 
  IconCheckCircle,
  IconLink
} from '@arco-design/web-vue/es/icon'
import { useAuthStore } from '@/stores/auth'
import { 
  getDataAssignment, 
  updateDataAssignment,
  type DataRecordAssignment,
  type UpdateDataAssignmentRequest
} from '@/api/dataAssignment'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// 响应式数据
const loading = ref(false)
const updateLoading = ref(false)
const assignment = ref<DataRecordAssignment | null>(null)
const showEditModal = ref(false)
const assignmentId = computed(() => Number(route.params.id))

// 编辑表单
const editForm = reactive<UpdateDataAssignmentRequest>({
  status: 'pending',
  notes: undefined
})

// 计算属性
const canEdit = computed(() => {
  const user = authStore.user
  if (!user || !assignment.value) return false
  
  // 管理员可以编辑所有分发
  if (user.role === 'admin') return true
  
  // 公司管理员可以编辑本公司的分发
  if (user.role === 'company_admin' && user.company_id === assignment.value.company_id) {
    return true
  }
  
  // 处理人可以编辑分配给自己的分发
  if (assignment.value.assigned_to === user.id) return true
  
  return false
})

// 方法定义
const loadAssignment = async () => {
  try {
    loading.value = true
    const response = await getDataAssignment(assignmentId.value)
    assignment.value = response.data.data
    
    // 初始化编辑表单
    editForm.status = response.data.data.status || 'pending'
    editForm.notes = response.data.data.notes || ''
  } catch (error) {
    console.error('加载分发详情失败:', error)
    Message.error('加载分发详情失败')
  } finally {
    loading.value = false
  }
}

const handleUpdateStatus = async () => {
  try {
    updateLoading.value = true
    await updateDataAssignment(assignmentId.value, editForm)
    Message.success('更新状态成功')
    showEditModal.value = false
    loadAssignment()
  } catch (error) {
    console.error('更新状态失败:', error)
    Message.error('更新状态失败')
  } finally {
    updateLoading.value = false
  }
}

const goBack = () => {
  router.back()
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

const getRecordStatusColor = (status: string) => {
  const colors = {
    unclaimed: 'gray',
    claimed: 'blue',
    completed: 'green',
    duplicate: 'red'
  }
  return colors[status as keyof typeof colors] || 'gray'
}

const getRecordStatusText = (status: string) => {
  const texts = {
    unclaimed: '未认领',
    claimed: '已认领',
    completed: '已完成',
    duplicate: '重复'
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

const calculateDuration = (startTime: string, endTime: string) => {
  const start = new Date(startTime)
  const end = new Date(endTime)
  const diffMs = end.getTime() - start.getTime()
  
  const days = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))
  
  if (days > 0) {
    return `${days}天 ${hours}小时 ${minutes}分钟`
  } else if (hours > 0) {
    return `${hours}小时 ${minutes}分钟`
  } else {
    return `${minutes}分钟`
  }
}

// 生命周期
onMounted(() => {
  loadAssignment()
})
</script>

<style scoped>
.assignment-detail-container {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.loading-container {
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

.info-card {
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

.user-info .user-name {
  font-weight: 500;
  margin-bottom: 2px;
}

.user-info .user-email {
  font-size: 12px;
  color: #86909c;
}

.text-gray {
  color: #86909c;
}

.notes-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e6eb;
}

.notes-section h4 {
  margin-bottom: 12px;
  color: #1d2129;
}

.notes-content {
  padding: 12px;
  background: #f7f8fa;
  border-radius: 4px;
  color: #4e5969;
  line-height: 1.5;
}

.content-section,
.url-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e6eb;
}

.content-section h4,
.url-section h4 {
  margin-bottom: 12px;
  color: #1d2129;
}

.content-display {
  padding: 12px;
  background: #f7f8fa;
  border-radius: 4px;
  color: #4e5969;
  line-height: 1.5;
  white-space: pre-wrap;
  max-height: 200px;
  overflow-y: auto;
}

.record-url {
  color: #165dff;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.record-url:hover {
  text-decoration: underline;
}

.timeline-content {
  padding-left: 8px;
}

.timeline-title {
  font-weight: 500;
  margin-bottom: 4px;
  color: #1d2129;
}

.timeline-time {
  font-size: 12px;
  color: #86909c;
  margin-bottom: 2px;
}

.timeline-user {
  font-size: 12px;
  color: #4e5969;
}
</style>