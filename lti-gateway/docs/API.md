# LTI Gateway API 文档

## 端点列表

### 1. LTI 启动

**POST** `/lti/launch/{toolId}`

启动 LTI 工具会话。

#### 参数

| 参数 | 类型 | 必需 | 说明 |
|------|------|------|------|
| toolId | integer | ✓ | 工具配置 ID |
| id_token | string | ✓ (LTI 1.3) | JWT ID Token |
| state | string | ✓ (LTI 1.3) | 状态参数 |

#### 响应

成功: 返回自动提交的 HTML 表单

错误: 返回错误页面

---

### 2. JWKS 公钥

**GET** `/lti/jwks/{toolId}`

获取工具的 JWKS 公钥集合。

#### 参数

| 参数 | 类型 | 必需 | 说明 |
|------|------|------|------|
| toolId | integer | ✓ | 工具配置 ID |

#### 响应

```json
{
  "keys": [
    {
      "kty": "RSA",
      "kid": "gateway-key-1",
      "use": "sig",
      "alg": "RS256",
      "n": "...",
      "e": "..."
    }
  ]
}
```

---

### 3. 健康检查

**GET** `/lti/health`

检查服务健康状态。

#### 响应

```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00+08:00",
  "version": "1.0.0"
}
```

---

### 4. 系统健康 (Laravel)

**GET** `/up`

Laravel 系统健康检查。

---

## 学号提取规则

LTI Gateway 按以下优先级从请求中提取学号：

1. `custom_student_id` - 最高优先级
2. `lis_person_sourcedid` - 次优先级
3. `sub` (JWT subject) - 最低优先级

## 错误代码

| 代码 | 说明 |
|------|------|
| MISSING_STUDENT_ID | 缺少学号信息 |
| INVALID_LTI_REQUEST | 无效的 LTI 请求 |
| DOWNSTREAM_API_ERROR | 下游服务错误 |
| USER_MAPPING_ERROR | 用户映射错误 |

## 虚拟邮箱格式

```
{student_id}@{virtual_email_domain}
```

示例: `2024001@proxy.university.edu`
