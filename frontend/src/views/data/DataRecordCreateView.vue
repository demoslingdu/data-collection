<template>
  <div class="data-record-create">
    <!-- 页面头部 -->
    <div class="page-header">
      <a-page-header 
        title="创建数据记录" 
        subtitle="填写表单创建新的数据记录"
        @back="handleBack"
      />
    </div>

    <!-- 创建表单 -->
    <div class="form-section">
      <a-card>
        <a-form
          ref="formRef"
          :model="formData"
          :rules="formRules"
          layout="vertical"
          @submit="handleSubmit"
        >
          <a-row :gutter="24">
            <a-col :span="12">
              <a-form-item label="记录标题" field="title">
                <a-input
                  v-model="formData.title"
                  placeholder="请输入记录标题"
                  allow-clear
                />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="记录状态" field="status">
                <a-select
                  v-model="formData.status"
                  placeholder="请选择记录状态"
                >
                  <a-option value="active">活跃</a-option>
                  <a-option value="inactive">非活跃</a-option>
                  <a-option value="pending">待处理</a-option>
                </a-select>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="24">
            <a-col :span="12">
              <a-form-item label="分类" field="category">
                <a-select
                  v-model="formData.category"
                  placeholder="请选择分类"
                  allow-clear
                >
                  <a-option value="type1">类型一</a-option>
                  <a-option value="type2">类型二</a-option>
                  <a-option value="type3">类型三</a-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="优先级" field="priority">
                <a-select
                  v-model="formData.priority"
                  placeholder="请选择优先级"
                >
                  <a-option value="high">高</a-option>
                  <a-option value="medium">中</a-option>
                  <a-option value="low">低</a-option>
                </a-select>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="24">
            <a-col :span="24">
              <a-form-item label="记录内容" field="content">
                <a-textarea
                  v-model="formData.content"
                  placeholder="请输入记录内容"
                  :rows="6"
                  allow-clear
                  show-word-limit
                  :max-length="1000"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="24">
            <a-col :span="12">
              <a-form-item label="开始日期" field="start_date">
                <a-date-picker
                  v-model="formData.start_date"
                  placeholder="请选择开始日期"
                  style="width: 100%"
                />
              </a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="结束日期" field="end_date">
                <a-date-picker
                  v-model="formData.end_date"
                  placeholder="请选择结束日期"
                  style="width: 100%"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="24">
            <a-col :span="24">
              <a-form-item label="标签" field="tags">
                <a-input-tag
                  v-model="formData.tags"
                  placeholder="输入标签后按回车添加"
                  allow-clear
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="24">
            <a-col :span="24">
              <a-form-item label="附件上传" field="attachments">
                <a-upload
                  ref="uploadRef"
                  :file-list="formData.attachments"
                  :limit="5"
                  multiple
                  draggable
                  tip="支持拖拽上传，最多上传5个文件"
                  @change="handleFileChange"
                >
                  <template #upload-button>
                    <div class="upload-area">
                      <div>
                        <icon-plus />
                        <div>点击或拖拽文件到此处上传</div>
                      </div>
                    </div>
                  </template>
                </a-upload>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="24">
            <a-col :span="24">
              <a-form-item label="备注" field="remarks">
                <a-textarea
                  v-model="formData.remarks"
                  placeholder="请输入备注信息"
                  :rows="3"
                  allow-clear
                />
              </a-form-item>
            </a-col>
          </a-row>

          <!-- 表单操作按钮 -->
          <div class="form-actions">
            <a-space size="large">
              <a-button type="primary" html-type="submit" :loading="submitting">
                <template #icon>
                  <icon-save />
                </template>
                保存
              </a-button>
              <a-button @click="handleSaveDraft" :loading="savingDraft">
                <template #icon>
                  <icon-file />
                </template>
                保存草稿
              </a-button>
              <a-button @click="handleReset">
                <template #icon>
                  <icon-refresh />
                </template>
                重置
              </a-button>
              <a-button @click="handleBack">
                <template #icon>
                  <icon-left />
                </template>
                返回
              </a-button>
            </a-space>
          </div>
        </a-form>
      </a-card>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 数据记录创建页面
 * 提供数据记录的创建表单和相关功能
 */
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import {
  IconPlus,
  IconSave,
  IconFile,
  IconRefresh,
  IconLeft
} from '@arco-design/web-vue/es/icon'

// 路由实例
const router = useRouter()

// 表单引用
const formRef = ref()
const uploadRef = ref()

// 响应式数据
const submitting = ref(false)
const savingDraft = ref(false)

// 表单数据
const formData = reactive({
  title: '',
  status: 'pending',
  category: '',
  priority: 'medium',
  content: '',
  start_date: '',
  end_date: '',
  tags: [],
  attachments: [],
  remarks: ''
})

// 表单验证规则
const formRules = {
  title: [
    { required: true, message: '请输入记录标题' },
    { minLength: 2, message: '标题至少2个字符' },
    { maxLength: 100, message: '标题不能超过100个字符' }
  ],
  status: [
    { required: true, message: '请选择记录状态' }
  ],
  content: [
    { required: true, message: '请输入记录内容' },
    { minLength: 10, message: '内容至少10个字符' },
    { maxLength: 1000, message: '内容不能超过1000个字符' }
  ],
  priority: [
    { required: true, message: '请选择优先级' }
  ],
  start_date: [
    { required: true, message: '请选择开始日期' }
  ]
}

/**
 * 处理文件上传变化
 */
const handleFileChange = (fileList: any[]) => {
  formData.attachments = fileList
}

/**
 * 处理表单提交
 */
const handleSubmit = async ({ values, errors }: any) => {
  if (errors) {
    Message.error('请检查表单填写是否正确')
    return
  }

  submitting.value = true
  try {
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    Message.success('数据记录创建成功！')
    router.push('/data')
  } catch (error) {
    Message.error('创建失败，请重试')
  } finally {
    submitting.value = false
  }
}

/**
 * 处理保存草稿
 */
const handleSaveDraft = async () => {
  savingDraft.value = true
  try {
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 800))
    
    Message.success('草稿保存成功！')
  } catch (error) {
    Message.error('草稿保存失败，请重试')
  } finally {
    savingDraft.value = false
  }
}

/**
 * 处理表单重置
 */
const handleReset = () => {
  formRef.value?.resetFields()
  formData.attachments = []
  Message.info('表单已重置')
}

/**
 * 处理返回
 */
const handleBack = () => {
  router.back()
}
</script>

<style scoped>
.data-record-create {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.form-section {
  margin-bottom: 20px;
}

.form-section :deep(.arco-card-body) {
  padding: 24px;
}

.form-actions {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--color-border-2);
  text-align: center;
}

.upload-area {
  width: 100%;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--color-border-3);
  border-radius: 6px;
  background-color: var(--color-fill-1);
  transition: all 0.3s;
  cursor: pointer;
}

.upload-area:hover {
  border-color: var(--color-primary-light-4);
  background-color: var(--color-primary-light-1);
}

.upload-area div {
  text-align: center;
  color: var(--color-text-3);
}

.upload-area .arco-icon {
  font-size: 24px;
  margin-bottom: 8px;
}
</style>