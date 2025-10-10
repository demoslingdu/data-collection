<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1>数据采集平台</h1>
        <p>请登录您的账户</p>
      </div>
      
      <a-form
        :model="loginForm"
        :rules="loginRules"
        @submit="handleLogin"
        layout="vertical"
        class="login-form"
      >
        <a-form-item field="account" label="账户名">
          <a-input
            v-model="loginForm.account"
            placeholder="请输入账户名"
            size="large"
            :disabled="loading"
          >
            <template #prefix>
              <icon-user />
            </template>
          </a-input>
        </a-form-item>
        
        <a-form-item field="password" label="密码">
          <a-input-password
            v-model="loginForm.password"
            placeholder="请输入密码"
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
            登录
          </a-button>
        </a-form-item>
      </a-form>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * 用户登录页面
 * 提供用户登录功能
 */
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Message } from '@arco-design/web-vue'
import { IconUser, IconLock } from '@arco-design/web-vue/es/icon'
import { useAuthStore } from '@/stores/auth'
import type { LoginData } from '@/api/auth'

const router = useRouter()
const authStore = useAuthStore()
const loading = ref(false)

// 登录表单数据
const loginForm = reactive<LoginData>({
  account: '',
  password: ''
})

// 表单验证规则
const loginRules = {
  account: [
    { required: true, message: '请输入账户名' },
    { minLength: 3, message: '账户名长度不能少于3位' },
    { maxLength: 50, message: '账户名长度不能超过50位' }
  ],
  password: [
    { required: true, message: '请输入密码' },
    { minLength: 6, message: '密码长度不能少于6位' }
  ]
}

/**
 * 处理登录提交
 */
const handleLogin = async ({ errors }: { errors: any }) => {
  if (errors) return

  loading.value = true
  try {
    const result = await authStore.userLogin(loginForm)
    
    if (result.success) {
      Message.success('登录成功！欢迎回来')
      
      // 根据用户角色跳转到不同页面
      const userRole = authStore.user?.role
      if (userRole === 'admin') {
        router.push('/admin')
      } else {
        router.push('/dashboard')
      }
    } else {
      Message.error(result.message)
    }
  } catch (error) {
    Message.error('登录失败，请稍后重试')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  padding: 40px;
  width: 100%;
  max-width: 400px;
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.login-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #1d2129;
  margin-bottom: 8px;
}

.login-header p {
  color: #86909c;
  font-size: 14px;
}

.login-form {
  margin-bottom: 0;
}
</style>