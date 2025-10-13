import apiClient from './index'

/**
 * 统计数据相关的API接口
 */

// 统计数据类型定义
export interface StatisticsParams {
  date?: string // 查询日期，格式：YYYY-MM-DD
  start_date?: string // 开始日期，格式：YYYY-MM-DD
  end_date?: string // 结束日期，格式：YYYY-MM-DD
  company_id?: number // 公司ID，用于筛选特定公司的统计数据
}

// 用户统计数据类型
export interface UserStatistics {
  user_id: number
  user_name: string
  claimed_count?: number // 领取数量
  completed_count?: number // 完成数量
  completion_rate?: number // 完成率
  submitted_count?: number // 提交数量
  duplicate_count?: number // 重复数量
  duplicate_rate?: number // 重复率
}

// 领取统计响应数据类型
export interface ClaimStatisticsResponse {
  total_records: number // 总数据量
  claimed_records: number // 已领取数据量
  completed_records: number // 已通过数据量
  completion_rate: number // 通过率
  user_statistics: UserStatistics[] // 用户统计列表
}

// 收集统计响应数据类型
export interface CollectionStatisticsResponse {
  total_records: number // 总数据量
  claimed_records: number // 已领取数据量
  completed_records: number // 已通过数据量
  completion_rate: number // 通过率
  duplicate_records: number // 重复数据量
  duplicate_rate: number // 重复率
  user_statistics: UserStatistics[] // 用户统计列表
}

/**
 * 获取领取统计数据
 * @param params 查询参数
 * @returns 领取统计数据
 */
export const getClaimStatistics = async (params?: StatisticsParams): Promise<ClaimStatisticsResponse> => {
  const response = await apiClient.get('/statistics/claims', { params })
  return response.data
}

/**
 * 获取收集统计数据
 * @param params 查询参数
 * @returns 收集统计数据
 */
export const getCollectionStatistics = async (params?: StatisticsParams): Promise<CollectionStatisticsResponse> => {
  const response = await apiClient.get('/statistics/collections', { params })
  return response.data
}