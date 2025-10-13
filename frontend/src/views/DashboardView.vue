<template>
  <div class="dashboard-container">
    <!-- 双表格布局 -->
    <div class="tables-layout">
      <!-- 左侧：未领取数据表格 -->
      <a-card title="待领取数据" class="table-card left-table">
        <template #extra>
          <a-button @click="refreshUnclaimedData" :loading="unclaimedLoading" size="small">
            <template #icon>
              <icon-refresh />
            </template>
            刷新
          </a-button>
        </template>
        
        <a-table
          row-key="id"
          :columns="unclaimedColumns"
          :data="unclaimedData"
          :loading="unclaimedLoading"
          :pagination="unclaimedPagination"
          @page-change="handleUnclaimedPageChange"
          @page-size-change="handleUnclaimedPageSizeChange"
          :scroll="{ y: 'calc(100vh - 240px)' }"
          stripe
          hoverable
          size="medium"
        >
          <template #image="{ record }">
            <a-image
              v-if="record.image_url"
              :src="record.image_url"
              width="40"
              height="40"
              fit="cover"
              :preview="false"
              style="border-radius: 4px;"
            />
            <span v-else class="no-image">无图片</span>
          </template>

          <template #submitter="{ record }">
            {{ record.submitter?.name || '未知' }}
          </template>
          <template #actions="{ record }">
            <a-button 
              type="primary" 
              size="mini" 
              :loading="claimingIds.has(record.id)"
              :disabled="claimingIds.has(record.id)"
              @click="handleClaim(record)"
            >
              {{ claimingIds.has(record.id) ? '领取中...' : '领取' }}
            </a-button>
          </template>
          <template #empty>
            <a-empty description="暂无待领取数据" />
          </template>
        </a-table>
      </a-card>

      <!-- 右侧：我已领取未完成数据表格 -->
      <a-card title="我的待通过数据" class="table-card right-table">
        <template #extra>
          <a-button @click="refreshMyClaimedData" :loading="myClaimedLoading" size="small">
            <template #icon>
              <icon-refresh />
            </template>
            刷新
          </a-button>
        </template>
        
        <a-table
          row-key="id"
          :columns="myClaimedColumns"
          :data="myClaimedData"
          :loading="myClaimedLoading"
          :pagination="myClaimedPagination"
          @page-change="handleMyClaimedPageChange"
          @page-size-change="handleMyClaimedPageSizeChange"
          :scroll="{ y: 'calc(100vh - 240px)' }"
          stripe
          hoverable
          size="medium"
        >
          <template #image="{ record }">
            <a-image
              v-if="record.image_url"
              :src="record.image_url"
              width="300"
              height="300"
              fit="contain"
              :preview="true"
              style="border-radius: 4px; cursor: pointer;"
            />
            <span v-else class="no-image">无图片</span>
          </template>
          <template #platform="{ record }">
            {{ getPlatformText(record.platform) }}
          </template>
          <template #phone="{ record }">
            <span 
              v-if="record.phone" 
              class="phone-number clickable"
              @click="copyToClipboard(record.phone)"
              :title="`点击复制手机号: ${record.phone}`"
            >
              {{ record.phone }}
            </span>
            <span v-else class="phone-empty">-</span>
          </template>
          <template #actions="{ record }">
            <a-button 
              type="outline" 
              size="mini" 
              status="success"
              :loading="completingIds.has(record.id)"
              :disabled="completingIds.has(record.id)"
              @click="handleComplete(record)"
            >
              {{ completingIds.has(record.id) ? '完成中...' : '已通过' }}
            </a-button>
          </template>
          <template #empty>
            <a-empty description="暂无待完成数据" />
          </template>
        </a-table>
      </a-card>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 仪表板页面组件
 * 展示未领取数据和我已领取未完成数据的双表格布局
 */
import { ref, reactive, onMounted, computed } from 'vue'
import { Message } from '@arco-design/web-vue'
import { IconRefresh } from '@arco-design/web-vue/es/icon'
import { useAuthStore } from '@/stores/auth'
import { dataRecordApi } from '@/api/dataRecord'
import type { DataRecord } from '@/api/dataRecord'

// 状态管理
const userStore = useAuthStore()

// 响应式数据
const unclaimedLoading = ref(false)
const myClaimedLoading = ref(false)
const unclaimedData = ref<DataRecord[]>([])
const myClaimedData = ref<DataRecord[]>([])

// 按钮加载状态
const claimingIds = ref<Set<number>>(new Set())
const completingIds = ref<Set<number>>(new Set())

// 分页配置
const unclaimedPagination = reactive({
  current: 1,
  pageSize: 20,
  total: 0
})

const myClaimedPagination = reactive({
  current: 1,
  pageSize: 20,
  total: 0
})

// 未领取数据表格列配置
const unclaimedColumns = [
  {
    title: 'ID',
    dataIndex: 'id',
    width: 60,
    align: 'center'
  },
  {
    title: '图片',
    dataIndex: 'image_url',
    slotName: 'image',
    width: 170,
    align: 'center'
  },
  {
    title: '平台',
    dataIndex: 'platform',
    slotName: 'platform',
    width: 80,
    align: 'center'
  },
  {
    title: '平台ID',
    dataIndex: 'platform_id',
    width: 120,
    ellipsis: true,
    tooltip: true
  },
  {
    title: '提交人',
    dataIndex: 'submitter',
    slotName: 'submitter',
    width: 100,
    align: 'center'
  },
  {
    title: '创建时间',
    dataIndex: 'created_at',
    width: 140,
    render: ({ record }: { record: DataRecord }) => {
      return new Date(record.created_at).toLocaleString('zh-CN', {
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 80,
    align: 'center'
  }
]

// 我已领取未完成数据表格列配置
const myClaimedColumns = [
  {
    title: 'ID',
    dataIndex: 'id',
    width: 80,
    align: 'center'
  },
  {
    title: '图片',
    dataIndex: 'image_url',
    slotName: 'image',
    width: 80,
    align: 'center'
  },
  {
    title: '手机号',
    dataIndex: 'phone',
    slotName: 'phone',
    width: 180,
    align: 'center',
    ellipsis: true,
    tooltip: true
  },
  {
    title: '领取时间',
    dataIndex: 'created_at',
    width: 180,
    render: ({ record }: { record: DataRecord }) => {
      return new Date(record.created_at).toLocaleString('zh-CN', {
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 120,
    align: 'center'
  }
]

/**
 * 获取平台中文名称
 */
const getPlatformText = (platform: string): string => {
  const platformMap: Record<string, string> = {
    douyin: '抖音',
    kuaishou: '快手',
    xiaohongshu: '小红书',
    weibo: '微博',
    taobao: '淘宝',
    xianyu: '闲鱼'
  }
  return platformMap[platform] || platform
}

/**
 * 加载未领取数据
 */
const loadUnclaimedData = async () => {
  try {
    unclaimedLoading.value = true
    
    const params = {
      page: unclaimedPagination.current,
      per_page: unclaimedPagination.pageSize
    }
    
    const response = await dataRecordApi.getUnclaimedRecords(params)
    
    if (response.data) {
      unclaimedData.value = response.data.data
      unclaimedPagination.total = response.data.total
      unclaimedPagination.current = response.data.current_page
    }
  } catch (error) {
    console.error('加载未领取数据失败:', error)
    Message.error('加载未领取数据失败')
  } finally {
    unclaimedLoading.value = false
  }
}

/**
 * 加载我已领取未完成数据
 */
const loadMyClaimedData = async () => {
  try {
    myClaimedLoading.value = true
    
    const params = {
      page: myClaimedPagination.current,
      per_page: myClaimedPagination.pageSize
    }
    
    const response = await dataRecordApi.getMyClaimedRecords(params)
    
    if (response.data) {
      myClaimedData.value = response.data.data
      myClaimedPagination.total = response.data.total
      myClaimedPagination.current = response.data.current_page
    }
  } catch (error) {
    console.error('加载我已领取未完成数据失败:', error)
    Message.error('加载我已领取未完成数据失败')
  } finally {
    myClaimedLoading.value = false
  }
}

/**
 * 刷新未领取数据
 */
const refreshUnclaimedData = () => {
  unclaimedPagination.current = 1
  loadUnclaimedData()
}

/**
 * 刷新我已领取未完成数据
 */
const refreshMyClaimedData = () => {
  myClaimedPagination.current = 1
  loadMyClaimedData()
}

/**
 * 处理未领取数据页码变化
 */
const handleUnclaimedPageChange = (page: number) => {
  unclaimedPagination.current = page
  loadUnclaimedData()
}

/**
 * 处理未领取数据页面大小变化
 */
const handleUnclaimedPageSizeChange = (pageSize: number) => {
  unclaimedPagination.pageSize = pageSize
  unclaimedPagination.current = 1
  loadUnclaimedData()
}

/**
 * 处理我已领取未完成数据页码变化
 */
const handleMyClaimedPageChange = (page: number) => {
  myClaimedPagination.current = page
  loadMyClaimedData()
}

/**
 * 处理我已领取未完成数据页面大小变化
 */
const handleMyClaimedPageSizeChange = (pageSize: number) => {
  myClaimedPagination.pageSize = pageSize
  myClaimedPagination.current = 1
  loadMyClaimedData()
}

/**
 * 复制图片到剪贴板
 * @param imageUrl 图片URL
 */
const copyImageToClipboard = async (imageUrl: string) => {
  try {
    // 检查浏览器是否支持 Clipboard API
    if (!navigator.clipboard || !navigator.clipboard.write) {
      throw new Error('浏览器不支持图片复制功能')
    }

    // 使用 Canvas 方案获取图片数据，避免跨域问题
    const blob = await getImageBlobFromCanvas(imageUrl)
    
    // 创建 ClipboardItem 并复制到剪贴板
    const clipboardItem = new ClipboardItem({
      [blob.type]: blob
    })

    await navigator.clipboard.write([clipboardItem])
    Message.success('图片已复制到剪贴板')
  } catch (error) {
    console.error('复制图片失败:', error)
    // 只显示错误提示，不打开新窗口
    Message.error('复制图片失败，请稍后重试')
  }
}

/**
 * 使用 Canvas 获取图片 Blob 数据
 * @param imageUrl 图片URL
 * @returns Promise<Blob>
 */
const getImageBlobFromCanvas = (imageUrl: string): Promise<Blob> => {
  return new Promise((resolve, reject) => {
    // 创建隐藏的 img 元素
    const img = new Image()
    
    // 设置跨域属性
    img.crossOrigin = 'anonymous'
    
    img.onload = () => {
      try {
        // 创建 Canvas 元素
        const canvas = document.createElement('canvas')
        const ctx = canvas.getContext('2d')
        
        if (!ctx) {
          throw new Error('无法创建 Canvas 上下文')
        }
        
        // 设置 Canvas 尺寸与图片相同
        canvas.width = img.naturalWidth
        canvas.height = img.naturalHeight
        
        // 将图片绘制到 Canvas 上
        ctx.drawImage(img, 0, 0)
        
        // 将 Canvas 转换为 Blob
        canvas.toBlob((blob) => {
          if (blob) {
            resolve(blob)
          } else {
            reject(new Error('Canvas 转换为 Blob 失败'))
          }
        }, 'image/png')
        
      } catch (error) {
        reject(error)
      }
    }
    
    img.onerror = () => {
      reject(new Error('图片加载失败'))
    }
    
    // 开始加载图片
    img.src = imageUrl
  })
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
      Message.success('领取成功')
      
      // 领取成功后，根据数据内容自动复制到剪贴板
      await handleAutoClipboard(record)
      
      // 刷新两个表格的数据
      loadUnclaimedData()
      loadMyClaimedData()
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
 * 处理自动复制到剪贴板功能
 * 优先级：手机号 > 图片
 * @param record 数据记录
 */
const handleAutoClipboard = async (record: DataRecord) => {
  try {
    // 优先复制手机号
    if (record.phone && record.phone.trim()) {
      await copyToClipboard(record.phone)
      return
    }
    
    // 如果没有手机号，则复制图片
    if (record.image_url && record.image_url.trim()) {
      await copyImageToClipboard(record.image_url)
      return
    }
    
    // 如果都没有，则不进行复制操作
    console.log('该记录没有可复制的手机号或图片')
  } catch (error) {
    console.error('自动复制失败:', error)
    // 这里不显示错误消息，因为复制失败不应该影响领取操作的成功提示
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
      Message.success('已通过成功')
      
      // 刷新我已领取未完成数据表格
      loadMyClaimedData()
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
 * 复制文本到剪贴板
 * @param text 要复制的文本
 */
const copyToClipboard = async (text: string) => {
  try {
    // 优先使用现代浏览器的 Clipboard API
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text)
      Message.success(`手机号 ${text} 已复制到剪贴板`)
    } else {
      // 降级方案：使用传统的 document.execCommand
      const textArea = document.createElement('textarea')
      textArea.value = text
      textArea.style.position = 'fixed'
      textArea.style.left = '-999999px'
      textArea.style.top = '-999999px'
      document.body.appendChild(textArea)
      textArea.focus()
      textArea.select()
      
      const successful = document.execCommand('copy')
      document.body.removeChild(textArea)
      
      if (successful) {
        Message.success(`手机号 ${text} 已复制到剪贴板`)
      } else {
        throw new Error('复制失败')
      }
    }
  } catch (err) {
    console.error('复制失败:', err)
    Message.error('复制失败，请手动复制')
  }
}

// 组件挂载时获取数据
onMounted(() => {
  loadUnclaimedData()
  loadMyClaimedData()
})
</script>

<style scoped>
.dashboard-container {
  padding: 24px;
  background-color: #f5f5f5;
  height: 100vh;
  max-height: 100vh;
  overflow: hidden;
  box-sizing: border-box;
}

.tables-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  height: calc(100vh - 48px);
  max-height: calc(100vh - 48px);
  overflow: hidden;
}

.table-card {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  height: 100%;
  max-height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.table-card :deep(.arco-card-body) {
  flex: 1;
  padding: 8px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.table-card :deep(.arco-table-container) {
  flex: 1;
  height: 100%;
  min-height: 0;
}

.table-card :deep(.arco-table) {
  height: 100%;
}

.table-card :deep(.arco-table-body) {
  flex: 1;
  min-height: 0;
}

.table-card :deep(.arco-card-header-title) {
  font-size: 18px;
  font-weight: 600;
  color: #1d2129;
}

.table-card :deep(.arco-table-th) {
  background-color: #f7f8fa;
  font-weight: 600;
  font-size: 12px;
}

.table-card :deep(.arco-table-td) {
  padding: 8px 12px;
  font-size: 12px;
}

.no-image {
  color: #86909c;
  font-size: 12px;
}

/* 手机号样式 */
.phone-number.clickable {
  color: #1890ff;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 2px 4px;
  border-radius: 3px;
  user-select: none;
}

.phone-number.clickable:hover {
  color: #40a9ff;
  background-color: #e6f7ff;
  transform: translateY(-1px);
}

.phone-number.clickable:active {
  color: #096dd9;
  background-color: #bae7ff;
  transform: translateY(0);
}

.phone-empty {
  color: #86909c;
  font-size: 12px;
}

/* 响应式设计 */
@media (max-width: 1200px) {
  .dashboard-container {
    height: 100vh;
    max-height: 100vh;
    overflow: hidden;
  }
  
  .tables-layout {
    grid-template-columns: 1fr;
    gap: 16px;
    height: calc(100vh - 48px);
    max-height: calc(100vh - 48px);
    overflow: hidden;
  }
  
  .table-card {
    height: calc(50vh - 32px);
    max-height: calc(50vh - 32px);
    overflow: hidden;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 16px;
    height: 100vh;
    max-height: 100vh;
    overflow: hidden;
  }
  
  .tables-layout {
    gap: 12px;
    height: calc(100vh - 32px);
    max-height: calc(100vh - 32px);
  }
  
  .table-card {
    height: calc(50vh - 26px);
    max-height: calc(50vh - 26px);
    overflow: hidden;
  }
}
</style>