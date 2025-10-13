import { request } from './index'
import type { DataRecord } from './dataRecord'
import type { Company } from './company'

export interface User {
  id: number
  name: string
  account: string
  role: string
}

export interface DataRecordAssignment {
  id: number
  data_record_id: number
  company_id: number
  assigned_to?: number
  assigned_at: string
  created_at: string
  updated_at: string
  data_record?: DataRecord
  company?: Company
  assigned_to_user?: User
}

export interface DataAssignmentCreateRequest {
  data_record_ids: number[]
  company_id: number
  assigned_to?: number
}

export interface DataAssignmentUpdateRequest {
  assigned_to?: number
}

export interface BatchAssignRequest {
  data_record_ids: number[]
  company_ids: number[]
}

export interface DataAssignmentListResponse {
  success: boolean
  data: {
    data: DataRecordAssignment[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface DataAssignmentResponse {
  success: boolean
  data: DataRecordAssignment | DataRecordAssignment[]
  message?: string
}

export interface DataAssignmentListParams {
  page?: number
  per_page?: number
  company_id?: number
  assigned_to?: number
  date_from?: string
  date_to?: string
}

export interface AssignmentStatistics {
  total: number
  by_company: Array<{
    company_id: number
    count: number
    company: Company
  }>
}

/**
 * 获取数据分发列表
 */
export const getDataAssignments = (params?: DataAssignmentListParams) => {
  return request<DataAssignmentListResponse>({
    url: '/data-assignments',
    method: 'GET',
    params
  })
}

/**
 * 创建数据分发
 */
export const createDataAssignment = (data: DataAssignmentCreateRequest) => {
  return request<DataAssignmentResponse>({
    url: '/data-assignments',
    method: 'POST',
    data
  })
}

/**
 * 批量分发数据
 */
export const batchAssignData = (data: BatchAssignRequest) => {
  return request<DataAssignmentResponse>({
    url: '/data-assignments/batch-assign',
    method: 'POST',
    data
  })
}

/**
 * 获取分发详情
 */
export const getDataAssignment = (id: number) => {
  return request<{ success: boolean; data: DataRecordAssignment }>({
    url: `/data-assignments/${id}`,
    method: 'GET'
  })
}

/**
 * 更新分发状态
 */
export const updateDataAssignment = (id: number, data: DataAssignmentUpdateRequest) => {
  return request<DataAssignmentResponse>({
    url: `/data-assignments/${id}`,
    method: 'PUT',
    data
  })
}

/**
 * 删除分发记录
 */
export const deleteDataAssignment = (id: number) => {
  return request<{ success: boolean; message: string }>({
    url: `/data-assignments/${id}`,
    method: 'DELETE'
  })
}

/**
 * 获取分发统计信息
 */
export const getAssignmentStatistics = (params?: { date_from?: string; date_to?: string }) => {
  return request<{ success: boolean; data: AssignmentStatistics }>({
    url: '/data-assignments/statistics',
    method: 'GET',
    params
  })
}

/**
 * 获取可分发的数据记录
 */
export const getAvailableRecords = (params?: {
  page?: number
  per_page?: number
  search?: string
  platform?: string
  status?: string
}) => {
  return request<{
    success: boolean
    data: {
      data: DataRecord[]
      current_page: number
      last_page: number
      per_page: number
      total: number
    }
  }>({
    url: '/data-assignments/available-records',
    method: 'GET',
    params
  })
}