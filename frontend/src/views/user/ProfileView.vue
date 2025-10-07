<template>
  <div class="dashboard-container">
    <!-- 页面头部 -->
    <div class="dashboard-header">
      <h1>个人中心</h1>
      <p>管理您的个人信息和账户设置</p>
    </div>

    <a-row :gutter="24">
      <!-- 左侧个人信息 -->
      <a-col :span="8">
        <!-- 用户头像和基本信息 -->
        <div class="profile-card">
          <a-card>
            <div class="profile-avatar-section">
              <a-avatar :size="120" class="profile-avatar">
                <img v-if="userInfo.avatar" :src="userInfo.avatar" alt="头像" />
                <icon-user v-else />
              </a-avatar>
              <div class="avatar-actions">
                <a-upload
                  :show-file-list="false"
                  accept="image/*"
                  @change="handleAvatarChange"
                >
                  <a-button type="primary">
                    <template #icon>
                      <icon-upload />
                    </template>
                    点击上传
                  </a-button>
                </a-upload>
              </div>
            </div>
            <div class="profile-info">
              <h3>{{ userInfo.name }}</h3>
              <p class="user-email">{{ userInfo.email }}</p>
              <p class="user-role">
                <a-tag color="blue">{{ getRoleText(userInfo.role) }}</a-tag>
              </p>
              <p class="join-time">加入时间: {{ userInfo.created_at }}</p>
            </div>
          </a-card>
        </div>

        <!-- 快捷操作 -->
        <div class="quick-actions">
          <a-card title="快捷操作">
            <a-space direction="vertical" fill>
              <a-button type="outline" long @click="handleChangePassword">
                <template #icon>
                  <icon-lock />
                </template>
                修改密码
              </a-button>

            </a-space>
          </a-card>
        </div>
      </a-col>

      <!-- 右侧详细信息和设置 -->
      <a-col :span="16">
        <a-tabs default-active-key="info" type="card">
          <!-- 个人信息标签页 -->
          <a-tab-pane key="info" title="个人信息">
            <a-card>
              <a-form
                ref="profileFormRef"
                :model="profileForm"
                :rules="profileRules"
                layout="vertical"
                @submit="handleUpdateProfile"
              >
                <a-row :gutter="16">
                  <a-col :span="12">
                    <a-form-item label="姓名" field="name">
                      <a-input
                        v-model="profileForm.name"
                        placeholder="请输入姓名"
                        allow-clear
                      />
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item label="邮箱" field="email">
                      <a-input
                        v-model="profileForm.email"
                        placeholder="请输入邮箱"
                        allow-clear
                        disabled
                      />
                    </a-form-item>
                  </a-col>
                </a-row>

                <a-row :gutter="16">
                  <a-col :span="12">
                    <a-form-item label="手机号" field="phone">
                      <a-input
                        v-model="profileForm.phone"
                        placeholder="请输入手机号"
                        allow-clear
                      />
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item label="性别" field="gender">
                      <a-select
                        v-model="profileForm.gender"
                        placeholder="请选择性别"
                        allow-clear
                      >
                        <a-option value="male">男</a-option>
                        <a-option value="female">女</a-option>
                        <a-option value="other">其他</a-option>
                      </a-select>
                    </a-form-item>
                  </a-col>
                </a-row>

                <a-row :gutter="16">
                  <a-col :span="12">
                    <a-form-item label="生日" field="birthday">
                      <a-date-picker
                        v-model="profileForm.birthday"
                        placeholder="请选择生日"
                        style="width: 100%"
                      />
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item label="所在地区" field="location">
                      <a-input
                        v-model="profileForm.location"
                        placeholder="请输入所在地区"
                        allow-clear
                      />
                    </a-form-item>
                  </a-col>
                </a-row>

                <a-form-item label="个人简介" field="bio">
                  <a-textarea
                    v-model="profileForm.bio"
                    placeholder="请输入个人简介"
                    :rows="4"
                    allow-clear
                    show-word-limit
                    :max-length="200"
                  />
                </a-form-item>

                <div class="form-actions">
                  <a-space>
                    <a-button type="primary" html-type="submit" :loading="updating">
                      <template #icon>
                        <icon-save />
                      </template>
                      保存修改
                    </a-button>
                    <a-button @click="handleResetProfile">
                      <template #icon>
                        <icon-refresh />
                      </template>
                      重置
                    </a-button>
                  </a-space>
                </div>
              </a-form>
            </a-card>
          </a-tab-pane>



          <!-- 数据统计标签页 -->
          <a-tab-pane key="statistics" title="数据统计">
            <a-card>
              <a-row :gutter="16">
                <a-col :span="8">
                  <a-statistic
                    title="创建记录数"
                    :value="statistics.recordsCreated"
                    :value-style="{ color: '#0fbf60' }"
                  >
                    <template #prefix>
                      <icon-file />
                    </template>
                  </a-statistic>
                </a-col>
                <a-col :span="8">
                  <a-statistic
                    title="登录次数"
                    :value="statistics.loginCount"
                    :value-style="{ color: '#165dff' }"
                  >
                    <template #prefix>
                      <icon-user />
                    </template>
                  </a-statistic>
                </a-col>
                <a-col :span="8">
                  <a-statistic
                    title="在线时长"
                    :value="statistics.onlineHours"
                    suffix="小时"
                    :value-style="{ color: '#ff7d00' }"
                  >
                    <template #prefix>
                      <icon-clock-circle />
                    </template>
                  </a-statistic>
                </a-col>
              </a-row>

              <a-divider />

              <h4>最近活动</h4>
              <a-timeline>
                <a-timeline-item
                  v-for="activity in recentActivities"
                  :key="activity.id"
                >
                  <div class="activity-item">
                    <div class="activity-action">{{ activity.action }}</div>
                    <div class="activity-time">{{ activity.time }}</div>
                  </div>
                </a-timeline-item>
              </a-timeline>
            </a-card>
          </a-tab-pane>
        </a-tabs>
      </a-col>
    </a-row>

    <!-- 修改密码模态框 -->
    <a-modal
      v-model:visible="passwordModalVisible"
      title="修改密码"
      @ok="handlePasswordSubmit"
      @cancel="handlePasswordCancel"
    >
      <a-form
        ref="passwordFormRef"
        :model="passwordForm"
        :rules="passwordRules"
        layout="vertical"
      >
        <a-form-item label="当前密码" field="currentPassword">
          <a-input-password
            v-model="passwordForm.currentPassword"
            placeholder="请输入当前密码"
          />
        </a-form-item>
        <a-form-item label="新密码" field="newPassword">
          <a-input-password
            v-model="passwordForm.newPassword"
            placeholder="请输入新密码"
          />
        </a-form-item>
        <a-form-item label="确认新密码" field="confirmPassword">
          <a-input-password
            v-model="passwordForm.confirmPassword"
            placeholder="请再次输入新密码"
          />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
/**
 * 用户个人中心页面
 * 提供个人信息管理、安全设置、通知设置等功能
 */
import { ref, reactive, onMounted } from 'vue'
import { Message } from '@arco-design/web-vue'
import {
  IconUser,
  IconUpload,
  IconLock,
  IconSave,
  IconRefresh,
  IconFile,
  IconClockCircle
} from '@arco-design/web-vue/es/icon'

// 表单引用
const profileFormRef = ref()
const passwordFormRef = ref()

// 响应式数据
const updating = ref(false)
const passwordModalVisible = ref(false)

// 用户信息
const userInfo = ref({
  id: '1',
  name: '张三',
  email: 'zhangsan@example.com',
  avatar: '',
  role: 'user',
  created_at: '2024-01-15'
})

// 个人信息表单
const profileForm = reactive({
  name: '张三',
  email: 'zhangsan@example.com',
  phone: '13800138000',
  gender: 'male',
  birthday: '1990-01-01',
  location: '北京市',
  bio: '这是一段个人简介...'
})

// 个人信息验证规则
const profileRules = {
  name: [
    { required: true, message: '请输入姓名' },
    { minLength: 2, message: '姓名至少2个字符' }
  ],
  phone: [
    { match: /^1[3-9]\d{9}$/, message: '请输入正确的手机号' }
  ]
}

// 密码修改表单
const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

// 密码验证规则
const passwordRules = {
  currentPassword: [
    { required: true, message: '请输入当前密码' }
  ],
  newPassword: [
    { required: true, message: '请输入新密码' },
    { minLength: 6, message: '密码至少6个字符' }
  ],
  confirmPassword: [
    { required: true, message: '请确认新密码' },
    {
      validator: (value: string, cb: Function) => {
        if (value !== passwordForm.newPassword) {
          cb('两次输入的密码不一致')
        } else {
          cb()
        }
      }
    }
  ]
}



// 数据统计
const statistics = ref({
  recordsCreated: 156,
  loginCount: 89,
  onlineHours: 234
})

// 最近活动
const recentActivities = ref([
  {
    id: '1',
    action: '创建了新的数据记录',
    time: '2024-01-16 10:30:00'
  },
  {
    id: '2',
    action: '修改了个人信息',
    time: '2024-01-15 15:20:00'
  },
  {
    id: '3',
    action: '登录系统',
    time: '2024-01-15 09:00:00'
  }
])

/**
 * 获取角色文本
 */
const getRoleText = (role: string): string => {
  const roleMap: Record<string, string> = {
    admin: '管理员',
    user: '普通用户',
    vip: 'VIP用户'
  }
  return roleMap[role] || '未知'
}

/**
 * 处理头像更换
 */
const handleAvatarChange = (fileList: any[]) => {
  if (fileList.length > 0) {
    const file = fileList[0]
    // 这里应该上传文件到服务器
    Message.success('头像上传成功')
  }
}

/**
 * 处理个人信息更新
 */
const handleUpdateProfile = async ({ values, errors }: any) => {
  if (errors) {
    Message.error('请检查表单填写是否正确')
    return
  }

  updating.value = true
  try {
    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    Message.success('个人信息更新成功')
  } catch (error) {
    Message.error('更新失败，请重试')
  } finally {
    updating.value = false
  }
}

/**
 * 处理个人信息重置
 */
const handleResetProfile = () => {
  profileFormRef.value?.resetFields()
  Message.info('表单已重置')
}

/**
 * 处理修改密码
 */
const handleChangePassword = () => {
  passwordModalVisible.value = true
}

/**
 * 处理密码提交
 */
const handlePasswordSubmit = async () => {
  try {
    const valid = await passwordFormRef.value?.validate()
    if (!valid) return

    // 模拟API调用
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    Message.success('密码修改成功')
    passwordModalVisible.value = false
    passwordForm.currentPassword = ''
    passwordForm.newPassword = ''
    passwordForm.confirmPassword = ''
  } catch (error) {
    Message.error('密码修改失败，请重试')
  }
}

/**
 * 处理密码取消
 */
const handlePasswordCancel = () => {
  passwordForm.currentPassword = ''
  passwordForm.newPassword = ''
  passwordForm.confirmPassword = ''
}



// 组件挂载时加载数据
onMounted(() => {
  // 加载用户数据
})
</script>

<style scoped>
.dashboard-container {
  padding: 24px;
  background-color: var(--color-bg-1);
  min-height: 100vh;
}

.dashboard-header {
  margin-bottom: 24px;
}

.dashboard-header h1 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
  color: var(--color-text-1);
}

.dashboard-header p {
  margin: 0;
  color: var(--color-text-2);
  font-size: 14px;
}

.profile-card {
  margin-bottom: 24px;
}

.profile-card .arco-card {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--color-border-2);
}

.profile-avatar-section {
  text-align: center;
  margin-bottom: 24px;
  padding: 16px 0;
}

.profile-avatar {
  margin-bottom: 16px;
  border: 4px solid var(--color-bg-2);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.avatar-actions {
  margin-top: 12px;
}

.profile-info {
  text-align: center;
  padding: 0 16px;
}

.profile-info h3 {
  margin: 0 0 12px 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--color-text-1);
}

.user-email {
  color: var(--color-text-2);
  margin: 8px 0;
  font-size: 14px;
}

.user-role {
  margin: 12px 0;
}

.join-time {
  color: var(--color-text-3);
  font-size: 12px;
  margin: 8px 0 0 0;
}

.quick-actions {
  margin-bottom: 24px;
}

.quick-actions .arco-card {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--color-border-2);
}

.form-actions {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border-2);
  text-align: left;
}

/* 右侧标签页样式优化 */
.arco-tabs-card .arco-tabs-content {
  padding: 0;
}

.arco-tabs-card .arco-tabs-content .arco-card {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--color-border-2);
  margin-top: 16px;
}

/* 表单布局优化 */
.arco-form-item {
  margin-bottom: 20px;
}

.arco-form-item-label {
  font-weight: 500;
  color: var(--color-text-1);
}

/* 响应式设计 */
@media (max-width: 768px) {
  .dashboard-container {
    padding: 16px;
  }
  
  .dashboard-header h1 {
    font-size: 20px;
  }
  
  .profile-avatar {
    width: 80px !important;
    height: 80px !important;
  }
  
  .profile-info h3 {
    font-size: 18px;
  }
}

.activity-item {
  padding: 4px 0;
}

.activity-action {
  font-weight: 500;
  color: var(--color-text-1);
  margin-bottom: 4px;
}

.activity-time {
  font-size: 12px;
  color: var(--color-text-3);
}
</style>