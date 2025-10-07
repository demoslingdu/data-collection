/**
 * 用户认证状态管理
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/api/auth'
import { login, register, logout, getCurrentUser } from '@/api/auth'
import type { LoginData, RegisterData } from '@/api/auth'

// 声明全局变量类型
declare global {
  interface Window {
    __AUTH_INITIALIZING__?: boolean
  }
}

export const useAuthStore = defineStore('auth', () => {
  // 状态
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const isInitializing = ref(false) // 新增：标识是否正在初始化认证状态

  // 计算属性
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  /**
   * 用户登录
   */
  const userLogin = async (loginData: LoginData) => {
    loading.value = true
    try {
      const response = await login(loginData)
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user_info', JSON.stringify(response.data.user))
        return { success: true, message: response.message }
      } else {
        return { success: false, message: response.message || '登录失败' }
      }
    } catch (error: any) {
      const message = error.response?.data?.message || '登录失败，请检查网络连接'
      return { success: false, message }
    } finally {
      loading.value = false
    }
  }

  /**
   * 用户注册
   */
  const userRegister = async (registerData: RegisterData) => {
    loading.value = true
    try {
      const response = await register(registerData)
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user_info', JSON.stringify(response.data.user))
        return { success: true, message: response.message }
      } else {
        return { success: false, message: response.message || '注册失败' }
      }
    } catch (error: any) {
      const message = error.response?.data?.message || '注册失败，请检查网络连接'
      return { success: false, message }
    } finally {
      loading.value = false
    }
  }

  /**
   * 用户登出
   */
  const userLogout = async () => {
    loading.value = true
    try {
      await logout()
    } catch (error) {
      console.error('登出请求失败:', error)
    } finally {
      // 无论请求是否成功，都清除本地状态
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_info')
      loading.value = false
    }
  }

  /**
   * 获取当前用户信息
   */
  const fetchCurrentUser = async () => {
    if (!token.value) {
      console.log('fetchCurrentUser: 没有 token，跳过获取用户信息')
      return
    }

    loading.value = true
    console.log('fetchCurrentUser: 开始获取用户信息')
    
    try {
      const response = await getCurrentUser()
      console.log('fetchCurrentUser: API 响应', response)
      
      if (response.success && response.data) {
        // 后端直接返回用户对象，不是包装在 user 字段中
        user.value = response.data
        localStorage.setItem('user_info', JSON.stringify(response.data))
        console.log('fetchCurrentUser: 用户信息更新成功', response.data)
      } else {
        console.log('fetchCurrentUser: API 响应失败，清除认证状态')
        // 如果获取用户信息失败，清除认证状态
        await userLogout()
      }
    } catch (error) {
      console.error('fetchCurrentUser: 获取用户信息失败:', error)
      // 如果获取用户信息失败，清除认证状态
      await userLogout()
    } finally {
      loading.value = false
    }
  }

  /**
   * 初始化认证状态
   */
  const initAuth = async () => {
    // 如果已经在初始化中，直接返回
    if (isInitializing.value) {
      console.log('initAuth: 已经在初始化中，跳过')
      return
    }
    
    console.log('initAuth: 开始初始化认证状态')
    isInitializing.value = true
    window.__AUTH_INITIALIZING__ = true // 设置全局标识
    
    try {
      const savedToken = localStorage.getItem('auth_token')
      const savedUser = localStorage.getItem('user_info')
      
      console.log('initAuth: 本地存储状态', { 
        hasToken: !!savedToken, 
        hasUser: !!savedUser,
        token: savedToken?.substring(0, 20) + '...' // 只显示前20个字符
      })

      if (savedToken && savedUser) {
        token.value = savedToken
        try {
          user.value = JSON.parse(savedUser)
          console.log('initAuth: 恢复用户信息成功', user.value)
          // 验证 token 是否仍然有效，获取最新用户信息
          await fetchCurrentUser()
        } catch (error) {
          console.error('initAuth: 初始化认证状态失败:', error)
          // 如果初始化失败，清除本地存储的无效数据
          localStorage.removeItem('auth_token')
          localStorage.removeItem('user_info')
          token.value = null
          user.value = null
        }
      } else {
        console.log('initAuth: 没有本地认证信息')
      }
    } finally {
      isInitializing.value = false
      window.__AUTH_INITIALIZING__ = false // 清除全局标识
      console.log('initAuth: 认证状态初始化完成', { 
        isAuthenticated: isAuthenticated.value,
        user: user.value?.name 
      })
    }
  }

  return {
    // 状态
    user,
    token,
    loading,
    isInitializing, // 新增：暴露初始化状态
    // 计算属性
    isAuthenticated,
    isAdmin,
    // 方法
    userLogin,
    userRegister,
    userLogout,
    fetchCurrentUser,
    initAuth,
  }
})