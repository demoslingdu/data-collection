/**
 * 数据记录相关 API
 */
import apiClient from './index'
import type { ApiResponse } from './auth'

// 数据记录接口
export interface DataRecord {
  id: number
  title: string
  description?: string
  content?: string
  platform: 'taobao' | 'tmall' | 'jd' | 'pdd' | 'douyin' | 'kuaishou' | 'xiaohongshu' | 'other'
  platform_id?: string
  url?: string
  price?: number
  quantity?: number
  contact_info?: string
  notes?: string
  phone?: string
  image_url?: string
  status: 'unclaimed' | 'claimed' | 'completed'
  submitter_id: number
  claimer_id?: number | null
  submitted_at?: string
  claimed_at?: string
  completed_at?: string
  created_at: string
  updated_at: string
  // 添加前端需要的状态属性
  is_claimed?: boolean
  is_completed?: boolean
  is_duplicate?: boolean
  // 添加详情页面需要的属性
  category?: string
  priority?: string
  start_date?: string
  end_date?: string
  tags?: string[]
  attachments?: any[]
  remarks?: string
  submitter?: {
    id: number
    name: string
    email: string
  }
  claimer?: {
    id: number
    name: string
    email: string
  } | null
}

// 数据记录创建数据
export interface CreateDataRecordData {
  title: string
  description: string
  platform: string
  url: string
  price?: number
  quantity?: number
  contact_info?: string
  notes?: string
}

// 数据记录更新数据
export interface UpdateDataRecordData {
  title?: string
  description?: string
  platform?: string
  url?: string
  price?: number
  quantity?: number
  contact_info?: string
  notes?: string
}

// 数据记录查询参数
export interface DataRecordQuery {
  page?: number
  per_page?: number
  status?: string
  platform?: string
  search?: string
  submitter_id?: number
  claimer_id?: number
}

// 分页链接信息接口
export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

// 分页元数据接口 - 包含完整的分页信息
export interface PaginationMeta {
  current_page: number    // 当前页码
  from: number           // 当前页第一条记录的序号
  last_page: number      // 最后一页页码
  per_page: number       // 每页记录数
  to: number             // 当前页最后一条记录的序号
  total: number          // 总记录数
  links: Array<{         // 分页链接数组
    url: string | null
    label: string
    active: boolean
  }>
}

// 分页响应格式 - 修复类型定义以匹配Laravel分页响应结构
export interface PaginatedResponse<T> {
  data: T[]                    // 数据数组
  links: PaginationLinks       // 分页链接
  meta: PaginationMeta         // 分页元数据
  // 保持向后兼容性：保留原有的直接属性
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// 统计数据格式
export interface Statistics {
  total_records: number
  unclaimed_records: number
  claimed_records: number
  completed_records: number
  my_submitted: number
  my_claimed: number
  platform_stats: Record<string, number>
}

/**
 * 获取数据记录列表
 */
export const getDataRecords = (params?: DataRecordQuery): Promise<ApiResponse<PaginatedResponse<DataRecord>>> => {
  return apiClient.get('/data-records', { params })
}

/**
 * 获取单个数据记录
 */
export const getDataRecord = (id: number): Promise<ApiResponse<DataRecord>> => {
  return apiClient.get(`/data-records/${id}`)
}

/**
 * 创建数据记录
 */
export const createDataRecord = (data: CreateDataRecordData): Promise<ApiResponse<DataRecord>> => {
  return apiClient.post('/data-records', data)
}

/**
 * 更新数据记录
 */
export const updateDataRecord = (id: number, data: UpdateDataRecordData): Promise<ApiResponse<DataRecord>> => {
  return apiClient.put(`/data-records/${id}`, data)
}

/**
 * 删除数据记录
 */
export const deleteDataRecord = (id: number): Promise<ApiResponse> => {
  return apiClient.delete(`/data-records/${id}`)
}

/**
 * 领取数据记录
 */
export const claimDataRecord = (id: number): Promise<ApiResponse<DataRecord>> => {
  return apiClient.post(`/data-records/${id}/claim`)
}

/**
 * 完成数据记录
 */
export const completeDataRecord = (id: number): Promise<ApiResponse<DataRecord>> => {
  return apiClient.post(`/data-records/${id}/complete`)
}

/**
 * 标记数据记录为重复
 */
export const markDuplicate = (id: number): Promise<ApiResponse<DataRecord>> => {
  return apiClient.post(`/data-records/${id}/mark-duplicate`)
}

/**
 * 获取统计信息
 */
export const getStatistics = (): Promise<ApiResponse<Statistics>> => {
  return apiClient.get('/data-records/statistics')
}

/**
 * 获取未领取的数据记录列表
 */
export const getUnclaimedRecords = (params?: DataRecordQuery): Promise<ApiResponse<PaginatedResponse<DataRecord>>> => {
  return apiClient.get('/data-records/unclaimed', { params })
}

/**
 * 获取我已领取但未完成的数据记录列表
 */
export const getMyClaimedRecords = (params?: DataRecordQuery): Promise<ApiResponse<PaginatedResponse<DataRecord>>> => {
  return apiClient.get('/data-records/my-claimed', { params })
}

/**
 * 导出数据记录 API
 */
export const dataRecordApi = {
  getList: getDataRecords,
  getDataRecord,
  createDataRecord,
  updateDataRecord,
  deleteDataRecord,
  claimDataRecord,
  completeDataRecord,
  markDuplicate,
  getStatistics,
  getUnclaimedRecords,
  getMyClaimedRecords
}