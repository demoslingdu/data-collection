<template>
  <div class="register-container">
    <div class="register-card">
      <div class="register-header">
        <h1>数据采集平台</h1>
        <p>创建您的账户</p>
      </div>
      
      <a-form
        :model="registerForm"
        :rules="registerRules"
        @submit="handleRegister"
        layout="vertical"
        class="register-form"
      >
        <a-form-item field="name" label="姓名">
          <a-input
            v-model="registerForm.name"
            placeholder="请输入您的姓名"
            size="large"
            :disabled="loading"
          >
            <template #prefix>
              <icon-user />
            </template>
          </a-input>
        </a-form-item>
        
        <a-form-item field="email" label="邮箱">
          <a-input
            v-model="registerForm.email"
            placeholder="请输入邮箱地址"
            size="large"
            :disabled="loading"
          >
            <template #prefix>
              <icon-email />
            </template>
          </a-input>
        </a-form-item>
        
        <a-form-item field="password" label="密码">
          <a-input-password
            v-model="registerForm.password"
            placeholder="请输入密码（至少6位）"
            size="large"
            :disabled="loading"
          >
            <template #prefix>
              <icon-lock />
            </template>
          </a-input-password>
        </a-form-item>
        
        <a-form-item field="password_confirmation" label="确认密码">
          <a-input-password
            v-model="registerForm.password_confirmation"
            placeholder="请再次输入密码"
            size="large"
            :disabled="loading"
          >
            <template #prefix>
              <icon-lock />
            </template>
          </a-input-password>
        </a-form-item>
        
        <a-form-item>
          <a-button
            type="primary"
            html-type="submit"
            size="large"
            :loading="loading"
            long
          >
            注册
          </a-button>
        </a-form-item>
      </a-form>
      
      <div class="register-footer">
        <p>
          已有账户？
          <router-link to="/login">立即登录</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 用户注册页面
 * 提供用户注册功能
 */
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import { IconUser, IconLock, IconEmail } from '@arco-design/web-vue/es/icon'
import { useAuthStore } from '@/stores/auth'
import type { RegisterData } from '@/api/auth'

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)

// 注册表单数据
const registerForm = reactive<RegisterData>({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

// 表单验证规则
const registerRules = {
  name: [
    { required: true, message: '请输入姓名' },
    { minLength: 2, message: '姓名长度不能少于2位' }
  ],
  email: [
    { required: true, message: '请输入邮箱地址' },
    { type: 'email', message: '请输入有效的邮箱地址' }
  ],
  password: [
    { required: true, message: '请输入密码' },
    { minLength: 6, message: '密码长度不能少于6位' }
  ],
  password_confirmation: [
    { required: true, message: '请确认密码' },
    {
      validator: (value: string, callback: (error?: string) => void) => {
        if (value !== registerForm.password) {
          callback('两次输入的密码不一致')
        } else {
          callback()
        }
      }
    }
  ]
}

/**
 * 处理注册提交
 */
const handleRegister = async ({ errors }: { errors: any }) => {
  if (errors) return

  loading.value = true
  try {
    const result = await authStore.userRegister(registerForm)
    
    if (result.success) {
      Message.success('注册成功！')
      router.push('/dashboard')
    } else {
      Message.error(result.message)
    }
  } catch (error) {
    Message.error('注册失败，请稍后重试')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.register-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  padding: 40px;
  width: 100%;
  max-width: 400px;
}

.register-header {
  text-align: center;
  margin-bottom: 32px;
}

.register-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #1d2129;
  margin-bottom: 8px;
}

.register-header p {
  color: #86909c;
  font-size: 14px;
}

.register-form {
  margin-bottom: 24px;
}

.register-footer {
  text-align: center;
}

.register-footer p {
  color: #86909c;
  font-size: 14px;
}

.register-footer a {
  color: #1890ff;
  font-weight: 500;
}

.register-footer a:hover {
  text-decoration: underline;
}
</style>