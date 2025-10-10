<template>
  <div class="admin-container">
    <!-- 页面头部 -->
    <div class="admin-header">
      <h1>管理员控制台</h1>
      <p>系统管理和数据统计</p>
    </div>



    <!-- 管理功能区 -->
    <a-row :gutter="16">
      <!-- 用户管理 -->
      <a-col :span="24">
        <a-card title="用户管理" class="management-card">
          <template #extra>
            <a-button type="primary" size="small" @click="handleAddUser">
              <template #icon>
                <icon-plus />
              </template>
              添加用户
            </a-button>
          </template>
          
          <div class="search-bar">
            <a-input-search
              v-model="userSearchKeyword"
              placeholder="搜索用户..."
              @search="handleUserSearch"
              style="margin-bottom: 16px"
            />
          </div>

          <a-table
             :columns="userColumns"
             :data="userList"
             :loading="userListLoading"
             :pagination="userPagination"
             @page-change="handleUserPageChange"
             size="small"
           >
             <template #role="{ record }">
               <a-tag :color="record.role === 'admin' ? 'red' : 'blue'">
                 {{ record.role === 'admin' ? '管理员' : '普通用户' }}
               </a-tag>
             </template>
             <template #actions="{ record }">
               <a-space>
                 <a-button type="text" size="small" @click="handleEditUser(record)">
                   编辑
                 </a-button>
                 <a-button 
                   type="text" 
                   size="small" 
                   status="warning"
                   @click="handleResetPassword(record)"
                 >
                   重置密码
                 </a-button>
                 <a-button 
                   type="text" 
                   size="small" 
                   status="danger"
                   @click="handleDeleteUser(record)"
                 >
                   删除
                 </a-button>
               </a-space>
             </template>
           </a-table>
        </a-card>
      </a-col>
    </a-row>

    <!-- 系统日志 -->
    <a-card title="系统日志" class="log-card">
      <template #extra>
        <a-space>
          <a-select v-model="logLevel" placeholder="日志级别" style="width: 120px">
            <a-option value="all">全部</a-option>
            <a-option value="info">信息</a-option>
            <a-option value="warning">警告</a-option>
            <a-option value="error">错误</a-option>
          </a-select>
          <a-button type="outline" size="small" @click="handleRefreshLogs">
            <template #icon>
              <icon-refresh />
            </template>
            刷新
          </a-button>
        </a-space>
      </template>

      <a-table
        :columns="logColumns"
        :data="logList"
        :loading="logListLoading"
        :pagination="logPagination"
        @page-change="handleLogPageChange"
        size="small"
      >
        <template #level="{ record }">
          <a-tag 
            :color="getLogLevelColor(record.level)"
          >
            {{ record.level.toUpperCase() }}
          </a-tag>
        </template>
        <template #message="{ record }">
          <a-tooltip :content="record.message">
            <span class="log-message">{{ record.message }}</span>
          </a-tooltip>
        </template>
      </a-table>
    </a-card>

    <!-- 用户编辑弹窗 -->
    <a-modal
      v-model:visible="userModalVisible"
      :title="userModalTitle"
      @ok="handleUserModalOk"
      @cancel="handleUserModalCancel"
    >
      <a-form
        ref="userFormRef"
        :model="userForm"
        :rules="userFormRules"
        layout="vertical"
      >
        <a-form-item field="name" label="用户名">
           <a-input v-model="userForm.name" placeholder="请输入用户名" />
         </a-form-item>
         <a-form-item field="account" label="账号">
           <a-input v-model="userForm.account" placeholder="请输入账号" />
         </a-form-item>
         <a-form-item field="password" label="密码" v-if="!userForm.id">
           <a-input-password v-model="userForm.password" placeholder="请输入密码" />
         </a-form-item>
         <a-form-item field="password_confirmation" label="确认密码" v-if="!userForm.id">
           <a-input-password v-model="userForm.password_confirmation" placeholder="请确认密码" />
         </a-form-item>
         <a-form-item field="role" label="角色">
           <a-select v-model="userForm.role" placeholder="请选择角色">
             <a-option value="user">普通用户</a-option>
             <a-option value="admin">管理员</a-option>
           </a-select>
         </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
/**
 * 管理员控制台页面组件
 * 提供用户管理、数据统计和系统监控功能
 */
import { ref, reactive, onMounted } from 'vue'
import { Message, Modal } from '@arco-design/web-vue'
import {
  IconUser,
  IconUserGroup,
  IconFile,
  IconCheckCircle,
  IconPlus,
  IconDownload,
  IconBarChart,
  IconRefresh
} from '@arco-design/web-vue/es/icon'
import { getUserList, createUser, updateUser, deleteUser, resetUserPassword, type User, type CreateUserData, type UpdateUserData, type UserQuery } from '@/api/user'

// 响应式数据
const userListLoading = ref(false)
const logListLoading = ref(false)



// 用户管理
const userSearchKeyword = ref('')
const userList = ref<User[]>([])
const userPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showTotal: true
})

// 用户表格列配置
const userColumns = [
  {
    title: 'ID',
    dataIndex: 'id',
    width: 80
  },
  {
    title: '用户名',
    dataIndex: 'name',
    width: 120
  },
  {
    title: '账号',
    dataIndex: 'account',
    width: 150
  },
  {
    title: '角色',
    dataIndex: 'role',
    slotName: 'role',
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

// 用户弹窗
const userModalVisible = ref(false)
const userModalTitle = ref('')
const userFormRef = ref()
// 用户表单数据类型定义
interface UserForm {
  id: number | null
  name: string
  account: string
  password: string
  password_confirmation: string
  role: 'admin' | 'user'
}

const userForm = reactive<UserForm>({
  id: null,
  name: '',
  account: '',
  password: '',
  password_confirmation: '',
  role: 'user'
})

const userFormRules = {
  name: [
    { required: true, message: '请输入用户名' }
  ],
  account: [
    { required: true, message: '请输入账号' },
    { minLength: 3, message: '账号长度不能少于3位' }
  ],
  password: [
    { required: true, message: '请输入密码' },
    { minLength: 6, message: '密码长度不能少于6位' }
  ],
  password_confirmation: [
    { required: true, message: '请确认密码' },
    {
      validator: (value: string, callback: Function) => {
        if (value !== userForm.password) {
          callback('两次输入的密码不一致')
        } else {
          callback()
        }
      }
    }
  ],
  role: [
    { required: true, message: '请选择角色' }
  ]
}

// 日志数据类型定义
interface LogEntry {
  timestamp: string
  level: string
  message: string
  user: string
}

// 系统日志
const logLevel = ref('all')
const logList = ref<LogEntry[]>([])
const logPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showTotal: true
})

// 日志表格列配置
const logColumns = [
  {
    title: '时间',
    dataIndex: 'timestamp',
    width: 180
  },
  {
    title: '级别',
    dataIndex: 'level',
    slotName: 'level',
    width: 100
  },
  {
    title: '消息',
    dataIndex: 'message',
    slotName: 'message'
  },
  {
    title: '用户',
    dataIndex: 'user',
    width: 120
  }
]



/**
 * 获取用户列表
 */
const fetchUserList = async () => {
  try {
    userListLoading.value = true
    const params: UserQuery = {
      page: userPagination.current,
      per_page: userPagination.pageSize,
      search: userSearchKeyword.value || undefined
    }
    
    const response = await getUserList(params)
    if (response.data) {
      userList.value = response.data.users
      userPagination.total = response.data.pagination.total
      userPagination.current = response.data.pagination.current_page
    }
  } catch (error) {
    console.error('获取用户列表失败:', error)
    Message.error('获取用户列表失败')
    userList.value = []
    userPagination.total = 0
  } finally {
    userListLoading.value = false
  }
}

/**
 * 获取系统日志
 */
const fetchLogList = async () => {
  try {
    logListLoading.value = true
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 600))
    logList.value = [
      {
        timestamp: '2024-01-20 10:30:15',
        level: 'info',
        message: '用户登录成功',
        user: '张三'
      },
      {
        timestamp: '2024-01-20 10:25:32',
        level: 'warning',
        message: '密码错误尝试',
        user: '李四'
      },
      {
        timestamp: '2024-01-20 10:20:45',
        level: 'error',
        message: '数据库连接失败',
        user: '系统'
      }
    ]
    logPagination.total = 200
  } catch (error) {
    console.error('获取系统日志失败:', error)
    Message.error('获取系统日志失败')
  } finally {
    logListLoading.value = false
  }
}

/**
 * 用户搜索
 */
const handleUserSearch = (keyword: string) => {
  console.log('搜索用户:', keyword)
  fetchUserList()
}

/**
 * 用户分页
 */
const handleUserPageChange = (page: number) => {
  userPagination.current = page
  fetchUserList()
}

/**
 * 添加用户
 */
const handleAddUser = () => {
  userModalTitle.value = '添加用户'
  userForm.id = null
  userForm.name = ''
  userForm.account = ''
  userForm.password = ''
  userForm.password_confirmation = ''
  userForm.role = 'user'
  userModalVisible.value = true
}

/**
 * 编辑用户
 */
const handleEditUser = (record: User) => {
  userModalTitle.value = '编辑用户'
  userForm.id = record.id
  userForm.name = record.name
  userForm.account = record.account
  userForm.password = ''
  userForm.password_confirmation = ''
  userForm.role = record.role
  userModalVisible.value = true
}

/**
 * 删除用户
 */
const handleDeleteUser = (record: User) => {
  Modal.confirm({
    title: '确认删除',
    content: `确定要删除用户 "${record.name}" 吗？`,
    onOk: async () => {
      try {
        await deleteUser(record.id)
        Message.success('删除成功')
        fetchUserList()
      } catch (error) {
        console.error('删除用户失败:', error)
        Message.error('删除失败')
      }
    }
  })
}

/**
 * 用户弹窗确认
 */
const handleUserModalOk = async () => {
  try {
    const valid = await userFormRef.value?.validate()
    if (valid) return

    if (userForm.id) {
      // 编辑用户
      const updateData: UpdateUserData = {
        name: userForm.name,
        account: userForm.account,
        role: userForm.role
      }
      await updateUser(userForm.id, updateData)
      Message.success('更新成功')
    } else {
      // 添加用户
      const createData: CreateUserData = {
        name: userForm.name,
        account: userForm.account,
        password: userForm.password,
        password_confirmation: userForm.password_confirmation,
        role: userForm.role
      }
      await createUser(createData)
      Message.success('添加成功')
    }
    
    userModalVisible.value = false
    fetchUserList()
  } catch (error) {
    console.error('用户操作失败:', error)
    Message.error('操作失败')
  }
}

/**
 * 用户弹窗取消
 */
const handleUserModalCancel = () => {
  userModalVisible.value = false
}

/**
 * 重置用户密码
 */
const handleResetPassword = (record: User) => {
  Modal.confirm({
    title: '确认重置密码',
    content: `确定要重置用户 "${record.name}" 的密码吗？重置后的默认密码为：123456`,
    onOk: async () => {
      try {
        await resetUserPassword(record.id, '123456')
        Message.success('密码重置成功，新密码为：123456')
      } catch (error) {
        console.error('重置密码失败:', error)
        Message.error('重置密码失败')
      }
    }
  })
}

/**
 * 导出统计报告
 */
const handleExportStats = () => {
  Message.info('导出功能开发中...')
}

/**
 * 日志分页
 */
const handleLogPageChange = (page: number) => {
  logPagination.current = page
  fetchLogList()
}

/**
 * 刷新日志
 */
const handleRefreshLogs = () => {
  fetchLogList()
}

/**
 * 获取日志级别颜色
 */
const getLogLevelColor = (level: string) => {
  const colors: Record<string, string> = {
    info: 'blue',
    warning: 'orange',
    error: 'red'
  }
  return colors[level] || 'gray'
}

// 组件挂载时获取数据
onMounted(() => {
  fetchUserList()
  fetchLogList()
})
</script>

<style scoped>
.admin-container {
  padding: 24px;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.admin-header {
  margin-bottom: 24px;
}

.admin-header h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 600;
  color: #1d2129;
}

.admin-header p {
  margin: 0;
  font-size: 16px;
  color: #86909c;
}

.overview-row {
  margin-bottom: 24px;
}

.overview-card {
  text-align: center;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.overview-card :deep(.arco-statistic-title) {
  font-size: 14px;
  color: #86909c;
  margin-bottom: 8px;
}

.overview-card :deep(.arco-statistic-value) {
  font-size: 24px;
  font-weight: 600;
  color: #1d2129;
}

.management-card,
.log-card {
  margin-bottom: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.management-card :deep(.arco-card-header-title),
.log-card :deep(.arco-card-header-title) {
  font-size: 18px;
  font-weight: 600;
  color: #1d2129;
}

.search-bar {
  margin-bottom: 16px;
}

.chart-container {
  margin-bottom: 24px;
}

.chart-item {
  margin-bottom: 20px;
}

.chart-item h4 {
  margin: 0 0 12px 0;
  font-size: 16px;
  font-weight: 600;
  color: #1d2129;
}

.chart-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 120px;
  background-color: #f7f8fa;
  border-radius: 6px;
  color: #86909c;
}

.chart-placeholder p {
  margin: 8px 0 0 0;
  font-size: 14px;
}

.stats-list {
  border-top: 1px solid #e5e6eb;
  padding-top: 16px;
}

.log-message {
  display: inline-block;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* 响应式设计 */
@media (max-width: 1200px) {
  .overview-row .arco-col {
    margin-bottom: 16px;
  }
}

@media (max-width: 768px) {
  .admin-container {
    padding: 16px;
  }
  
  .overview-row .arco-col,
  .management-row .arco-col {
    margin-bottom: 16px;
  }
  
  .chart-container {
    margin-bottom: 16px;
  }
}
</style>