# API接口文档补充 - 数据记录管理接口

## 10. 更新数据记录接口

### 10.1 接口信息

- **接口路径**: `PUT /api/data-records/{id}`
- **接口描述**: 更新指定ID的数据记录信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: PUT
- **Content-Type**: `application/json`
- **权限要求**: 只有记录的创建者可以编辑

### 10.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 10.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |
| platform | string | 否 | 平台名称，最大255字符 |
| platform_id | string | 否 | 平台ID，最大255字符 |
| content | string | 否 | 记录内容 |
| status | string | 否 | 记录状态 |
| metadata | object | 否 | 元数据对象 |

### 10.4 请求示例

```bash
curl -X PUT http://localhost:8000/api/data-records/1 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "京东",
    "platform_id": "JD123456789",
    "content": "更新后的商品数据",
    "metadata": {
      "updated_reason": "平台变更"
    }
  }'
```

### 10.5 响应示例

#### 10.5.1 更新成功 (200)

```json
{
  "success": true,
  "message": "数据记录更新成功",
  "data": {
    "id": 1,
    "platform": "京东",
    "platform_id": "JD123456789",
    "content": "更新后的商品数据",
    "status": "pending",
    "metadata": {
      "updated_reason": "平台变更"
    },
    "user_id": 1,
    "claimed_by": null,
    "claimed_at": null,
    "completed_at": null,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T12:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "张三",
      "account": "zhangsan",
      "role": "user"
    }
  }
}
```

#### 10.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "无权限编辑此记录，只有提交者可以编辑"
}
```

#### 10.5.3 记录不存在 (404)

```json
{
  "success": false,
  "message": "数据记录不存在"
}
```

### 10.6 业务逻辑说明

1. **权限控制**: 只有记录的创建者可以编辑
2. **唯一性检查**: 更新时会检查平台和平台ID的唯一性
3. **部分更新**: 支持只更新部分字段

---

## 11. 删除数据记录接口

### 11.1 接口信息

- **接口路径**: `DELETE /api/data-records/{id}`
- **接口描述**: 删除指定ID的数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: DELETE
- **权限要求**: 只有记录的创建者可以删除

### 11.2 请求头

```
Authorization: Bearer {token}
```

### 11.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 11.4 请求示例

```bash
curl -X DELETE http://localhost:8000/api/data-records/1 \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 11.5 响应示例

#### 11.5.1 删除成功 (200)

```json
{
  "success": true,
  "message": "数据记录删除成功"
}
```

#### 11.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "无权限删除此记录，只有提交者可以删除"
}
```

#### 11.5.3 记录不存在 (404)

```json
{
  "success": false,
  "message": "数据记录不存在"
}
```

---

## 12. 批量删除数据记录接口

### 12.1 接口信息

- **接口路径**: `DELETE /api/data-records/batch`
- **接口描述**: 批量删除多个数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: DELETE
- **Content-Type**: `application/json`
- **权限要求**: 只能删除自己创建的记录

### 12.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 12.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| ids | array | 是 | 要删除的记录ID数组 |

### 12.4 请求示例

```bash
curl -X DELETE http://localhost:8000/api/data-records/batch \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3, 4, 5]
  }'
```

### 12.5 响应示例

#### 12.5.1 批量删除成功 (200)

```json
{
  "success": true,
  "message": "成功删除 3 条记录",
  "data": {
    "deleted_count": 3,
    "failed_count": 2,
    "failed_ids": [4, 5],
    "failed_reasons": {
      "4": "记录不存在",
      "5": "无权限删除此记录"
    }
  }
}
```

#### 12.5.2 参数验证失败 (422)

```json
{
  "success": false,
  "message": "验证失败",
  "errors": {
    "ids": ["ids字段必须是数组"]
  }
}
```

---

## 13. 领取数据记录接口

### 13.1 接口信息

- **接口路径**: `POST /api/data-records/{id}/claim`
- **接口描述**: 领取指定的数据记录
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`

### 13.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 13.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 13.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records/1/claim \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json"
```

### 13.5 响应示例

#### 13.5.1 领取成功 (200)

```json
{
  "success": true,
  "message": "记录领取成功",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "claimed",
    "user_id": 2,
    "claimed_by": 1,
    "claimed_at": "2024-01-20T12:00:00.000000Z",
    "completed_at": null,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T12:00:00.000000Z"
  }
}
```

#### 13.5.2 记录已被领取 (400)

```json
{
  "success": false,
  "message": "记录已被领取"
}
```

#### 13.5.3 不能领取自己的记录 (400)

```json
{
  "success": false,
  "message": "不能领取自己提交的记录"
}
```

#### 13.5.4 记录不存在 (404)

```json
{
  "success": false,
  "message": "记录不存在"
}
```

---

## 14. 完成数据记录接口

### 14.1 接口信息

- **接口路径**: `POST /api/data-records/{id}/complete`
- **接口描述**: 标记数据记录为已完成
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`
- **权限要求**: 只有领取者可以完成记录

### 14.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 14.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 14.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records/1/complete \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json"
```

### 14.5 响应示例

#### 14.5.1 完成成功 (200)

```json
{
  "success": true,
  "message": "记录已标记为完成",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "completed",
    "user_id": 2,
    "claimed_by": 1,
    "claimed_at": "2024-01-20T12:00:00.000000Z",
    "completed_at": "2024-01-20T14:00:00.000000Z",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T14:00:00.000000Z"
  }
}
```

#### 14.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "只有领取者可以完成记录"
}
```

#### 14.5.3 记录未被领取 (400)

```json
{
  "success": false,
  "message": "记录尚未被领取"
}
```

---

## 15. 标记重复记录接口

### 15.1 接口信息

- **接口路径**: `POST /api/data-records/{id}/mark-duplicate`
- **接口描述**: 标记数据记录为重复
- **认证要求**: 需要Bearer Token认证
- **请求方式**: POST
- **Content-Type**: `application/json`
- **权限要求**: 只有领取者可以标记重复

### 15.2 请求头

```
Authorization: Bearer {token}
Content-Type: application/json
```

### 15.3 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据记录ID（路径参数） |

### 15.4 请求示例

```bash
curl -X POST http://localhost:8000/api/data-records/1/mark-duplicate \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789" \
  -H "Content-Type: application/json"
```

### 15.5 响应示例

#### 15.5.1 标记成功 (200)

```json
{
  "success": true,
  "message": "记录已标记为重复",
  "data": {
    "id": 1,
    "platform": "淘宝",
    "platform_id": "TB123456789",
    "content": "某品牌手机的价格和销量数据",
    "status": "duplicate",
    "user_id": 2,
    "claimed_by": 1,
    "claimed_at": "2024-01-20T12:00:00.000000Z",
    "completed_at": "2024-01-20T14:00:00.000000Z",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T14:00:00.000000Z"
  }
}
```

#### 15.5.2 权限不足 (403)

```json
{
  "success": false,
  "message": "只有领取者可以标记重复"
}
```

---

## 16. 获取数据统计信息接口

### 16.1 接口信息

- **接口路径**: `GET /api/data-records/statistics`
- **接口描述**: 获取数据记录的统计信息
- **认证要求**: 需要Bearer Token认证
- **请求方式**: GET

### 16.2 请求头

```
Authorization: Bearer {token}
```

### 16.3 请求参数

无需请求参数

### 16.4 请求示例

```bash
curl -X GET http://localhost:8000/api/data-records/statistics \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789"
```

### 16.5 响应示例

#### 16.5.1 获取成功 (200)

```json
{
  "success": true,
  "message": "获取统计信息成功",
  "data": {
    "total_records": 150,
    "pending_records": 45,
    "claimed_records": 30,
    "completed_records": 60,
    "duplicate_records": 15,
    "today_created": 12,
    "this_week_created": 35,
    "this_month_created": 89,
    "my_submitted": 25,
    "my_claimed": 8,
    "my_completed": 15
  }
}
```

### 16.6 业务逻辑说明

1. **全局统计**: 包含所有记录的状态统计
2. **时间统计**: 包含今日、本周、本月的新增记录数
3. **个人统计**: 包含当前用户的提交、领取、完成记录数

### 16.7 使用说明

1. 仪表板数据展示
2. 工作量统计
3. 系统运营数据分析