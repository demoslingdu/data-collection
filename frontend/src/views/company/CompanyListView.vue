<template>
  <div class="company-list-view">
    <div class="header">
      <h2>公司管理</h2>
      <a-button type="primary" @click="showCreateModal">
        <template #icon>
          <icon-plus />
        </template>
        新增公司
      </a-button>
    </div>

    <!-- 搜索和过滤 -->
    <div class="filters">
      <a-row :gutter="16">
        <a-col :span="8">
          <a-input
            v-model="searchForm.search"
            placeholder="搜索公司名称、代码或联系人"
            allow-clear
            @press-enter="handleSearch"
          >
            <template #prefix>
              <icon-search />
            </template>
          </a-input>
        </a-col>
        <a-col :span="4">
          <a-select
            v-model="searchForm.is_active"
            placeholder="状态"
            allow-clear
            @change="handleSearch"
          >
            <a-option :value="true">启用</a-option>
            <a-option :value="false">禁用</a-option>
          </a-select>
        </a-col>
        <a-col :span="4">
          <a-button type="primary" @click="handleSearch">搜索</a-button>
        </a-col>
      </a-row>
    </div>

    <!-- 公司列表表格 -->
    <a-table
      :columns="columns"
      :data="tableData"
      :loading="loading"
      :pagination="pagination"
      @page-change="handlePageChange"
      @page-size-change="handlePageSizeChange"
    >
      <template #is_active="{ record }">
        <a-tag :color="record.is_active ? 'green' : 'red'">
          {{ record.is_active ? '启用' : '禁用' }}
        </a-tag>
      </template>
      
      <template #actions="{ record }">
        <a-space>
          <a-button size="small" @click="handleView(record)">查看</a-button>
          <a-button size="small" type="primary" @click="handleEdit(record)">编辑</a-button>
          <a-button
            size="small"
            :type="record.is_active ? 'outline' : 'primary'"
            @click="handleToggleStatus(record)"
          >
            {{ record.is_active ? '禁用' : '启用' }}
          </a-button>
          <a-popconfirm
            content="确定要删除这个公司吗？"
            @ok="handleDelete(record)"
          >
            <a-button size="small" status="danger">删除</a-button>
          </a-popconfirm>
        </a-space>
      </template>
    </a-table>

    <!-- 创建/编辑公司模态框 -->
    <a-modal
      v-model:visible="modalVisible"
      :title="isEdit ? '编辑公司' : '新增公司'"
      width="600px"
      @ok="handleSubmit"
      @cancel="handleCancel"
    >
      <a-form
        ref="formRef"
        :model="form"
        :rules="rules"
        layout="vertical"
      >
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="公司名称" field="name">
              <a-input v-model="form.name" placeholder="请输入公司名称" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="公司代码" field="code">
              <a-input v-model="form.code" placeholder="请输入公司代码" />
            </a-form-item>
          </a-col>
        </a-row>
        
        <a-form-item label="公司描述" field="description">
          <a-textarea
            v-model="form.description"
            placeholder="请输入公司描述"
            :rows="3"
          />
        </a-form-item>
        
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="联系人" field="contact_person">
              <a-input v-model="form.contact_person" placeholder="请输入联系人" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="联系电话" field="contact_phone">
              <a-input v-model="form.contact_phone" placeholder="请输入联系电话" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="联系邮箱" field="contact_email">
              <a-input v-model="form.contact_email" placeholder="请输入联系邮箱" />
            </a-form-item>
          </a-col>
        </a-row>
        
        <a-form-item label="状态" field="is_active">
          <a-switch v-model="form.is_active" />
          <span class="ml-2">{{ form.is_active ? '启用' : '禁用' }}</span>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 查看公司详情模态框 -->
    <a-modal
      v-model:visible="viewModalVisible"
      title="公司详情"
      width="600px"
      :footer="false"
    >
      <div v-if="viewData" class="company-detail">
        <a-descriptions :column="2" bordered>
          <a-descriptions-item label="公司名称">
            {{ viewData.name }}
          </a-descriptions-item>
          <a-descriptions-item label="公司代码">
            {{ viewData.code }}
          </a-descriptions-item>
          <a-descriptions-item label="状态">
            <a-tag :color="viewData.is_active ? 'green' : 'red'">
              {{ viewData.is_active ? '启用' : '禁用' }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item label="联系人">
            {{ viewData.contact_person || '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="联系电话">
            {{ viewData.contact_phone || '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="联系邮箱">
            {{ viewData.contact_email || '-' }}
          </a-descriptions-item>
          <a-descriptions-item label="创建时间" :span="2">
            {{ formatDate(viewData.created_at) }}
          </a-descriptions-item>
          <a-descriptions-item label="更新时间" :span="2">
            {{ formatDate(viewData.updated_at) }}
          </a-descriptions-item>
          <a-descriptions-item label="公司描述" :span="2">
            {{ viewData.description || '-' }}
          </a-descriptions-item>
        </a-descriptions>
      </div>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Message } from '@arco-design/web-vue'
import { IconPlus, IconSearch } from '@arco-design/web-vue/es/icon'
import {
  getCompanies,
  createCompany,
  updateCompany,
  deleteCompany,
  toggleCompanyStatus,
  type Company,
  type CompanyCreateRequest,
  type CompanyUpdateRequest
} from '@/api/company'

/**
 * 公司列表页面组件
 * 提供公司的增删改查功能
 */

// 表格列定义
const columns = [
  {
    title: '公司名称',
    dataIndex: 'name',
    width: 150
  },
  {
    title: '公司代码',
    dataIndex: 'code',
    width: 120
  },
  {
    title: '联系人',
    dataIndex: 'contact_person',
    width: 100
  },
  {
    title: '联系电话',
    dataIndex: 'contact_phone',
    width: 120
  },
  {
    title: '联系邮箱',
    dataIndex: 'contact_email',
    width: 180
  },
  {
    title: '状态',
    dataIndex: 'is_active',
    slotName: 'is_active',
    width: 80
  },
  {
    title: '创建时间',
    dataIndex: 'created_at',
    width: 160,
    render: ({ record }: { record: Company }) => formatDate(record.created_at)
  },
  {
    title: '操作',
    slotName: 'actions',
    width: 200,
    fixed: 'right'
  }
]

// 响应式数据
const loading = ref(false)
const tableData = ref<Company[]>([])
const modalVisible = ref(false)
const viewModalVisible = ref(false)
const isEdit = ref(false)
const currentId = ref<number | null>(null)
const viewData = ref<Company | null>(null)
const formRef = ref()

// 搜索表单
const searchForm = reactive({
  search: '',
  is_active: undefined as boolean | undefined
})

// 分页配置
const pagination = reactive({
  current: 1,
  pageSize: 15,
  total: 0,
  showTotal: true,
  showPageSize: true
})

// 表单数据
const form = reactive<CompanyCreateRequest>({
  name: '',
  code: '',
  description: '',
  contact_person: '',
  contact_phone: '',
  contact_email: '',
  is_active: true
})

// 表单验证规则
const rules = {
  name: [
    { required: true, message: '请输入公司名称' }
  ],
  code: [
    { required: true, message: '请输入公司代码' }
  ],
  contact_email: [
    {
      type: 'email',
      message: '请输入正确的邮箱格式'
    }
  ]
}

// 获取公司列表
const fetchCompanies = async () => {
  try {
    loading.value = true
    const params = {
      page: pagination.current,
      per_page: pagination.pageSize,
      ...searchForm
    }
    
    const response = await getCompanies(params)
    if (response.success) {
      tableData.value = response.data.data
      pagination.total = response.data.total
      pagination.current = response.data.current_page
    }
  } catch (error) {
    console.error('获取公司列表失败:', error)
    Message.error('获取公司列表失败')
  } finally {
    loading.value = false
  }
}

// 搜索处理
const handleSearch = () => {
  pagination.current = 1
  fetchCompanies()
}

// 分页处理
const handlePageChange = (page: number) => {
  pagination.current = page
  fetchCompanies()
}

const handlePageSizeChange = (pageSize: number) => {
  pagination.pageSize = pageSize
  pagination.current = 1
  fetchCompanies()
}

// 显示创建模态框
const showCreateModal = () => {
  isEdit.value = false
  currentId.value = null
  resetForm()
  modalVisible.value = true
}

// 查看公司详情
const handleView = (record: Company) => {
  viewData.value = record
  viewModalVisible.value = true
}

// 编辑公司
const handleEdit = (record: Company) => {
  isEdit.value = true
  currentId.value = record.id
  Object.assign(form, {
    name: record.name,
    code: record.code,
    description: record.description || '',
    contact_person: record.contact_person || '',
    contact_phone: record.contact_phone || '',
    contact_email: record.contact_email || '',
    is_active: record.is_active
  })
  modalVisible.value = true
}

// 切换公司状态
const handleToggleStatus = async (record: Company) => {
  try {
    const response = await toggleCompanyStatus(record.id)
    if (response.success) {
      Message.success(response.message || '状态更新成功')
      fetchCompanies()
    }
  } catch (error) {
    console.error('切换状态失败:', error)
    Message.error('切换状态失败')
  }
}

// 删除公司
const handleDelete = async (record: Company) => {
  try {
    const response = await deleteCompany(record.id)
    if (response.success) {
      Message.success(response.message || '删除成功')
      fetchCompanies()
    }
  } catch (error: any) {
    console.error('删除失败:', error)
    Message.error(error.response?.data?.message || '删除失败')
  }
}

// 提交表单
const handleSubmit = async () => {
  console.log('handleSubmit 函数被调用')
  console.log('当前表单数据:', form)
  console.log('是否编辑模式:', isEdit.value)
  console.log('当前ID:', currentId.value)
  
  try {
    console.log('开始表单验证...')
    
    // Arco Design Vue 的 validate() 方法返回 Promise
    // 验证成功时 resolve(undefined)，验证失败时 reject(errors)
    await formRef.value?.validate()
    console.log('表单验证通过')

    if (isEdit.value && currentId.value) {
      console.log('执行更新操作...')
      const response = await updateCompany(currentId.value, form as CompanyUpdateRequest)
      console.log('更新响应:', response)
      if (response.success) {
        Message.success(response.message || '更新成功')
        modalVisible.value = false
        fetchCompanies()
      }
    } else {
      console.log('执行创建操作...')
      console.log('调用 createCompany API，参数:', form)
      const response = await createCompany(form)
      console.log('创建响应:', response)
      if (response.success) {
        Message.success(response.message || '创建成功')
        modalVisible.value = false
        fetchCompanies()
      }
    }
  } catch (validationErrors: any) {
    // 如果是表单验证错误
    if (validationErrors && typeof validationErrors === 'object' && !validationErrors.response) {
      console.log('表单验证失败:', validationErrors)
      console.log('表单验证失败，退出提交')
      return
    }
    
    // 如果是 API 请求错误
    console.error('提交失败:', validationErrors)
    console.error('错误详情:', validationErrors.response?.data)
    Message.error(validationErrors.response?.data?.message || '操作失败')
  }
}

// 取消操作
const handleCancel = () => {
  modalVisible.value = false
  resetForm()
}

// 重置表单
const resetForm = () => {
  Object.assign(form, {
    name: '',
    code: '',
    description: '',
    contact_person: '',
    contact_phone: '',
    contact_email: '',
    is_active: true
  })
  formRef.value?.resetFields()
}

// 格式化日期
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('zh-CN')
}

// 组件挂载时获取数据
onMounted(() => {
  fetchCompanies()
})
</script>

<style scoped>
.company-list-view {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.filters {
  margin-bottom: 20px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 6px;
}

.company-detail {
  padding: 16px 0;
}

.ml-2 {
  margin-left: 8px;
}
</style>