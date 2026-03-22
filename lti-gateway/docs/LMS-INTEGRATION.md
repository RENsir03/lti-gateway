# LMS 平台接入指南

本文档介绍如何将 LTI Gateway 接入到各种 LMS 平台（如 Moodle、Canvas、Blackboard 等）。

---

## 目录

- [快速开始](#快速开始)
- [Moodle 接入](#moodle-接入)
- [Canvas 接入](#canvas-接入)
- [通用 LTI 1.3 配置](#通用-lti-13-配置)
- [常见问题](#常见问题)

---

## 快速开始

### 1. 获取工具配置信息

在 LTI Gateway 管理后台 (`http://localhost:8081/admin`) 中：

1. 进入"工具管理"页面
2. 选择或创建一个工具
3. 记录以下信息：
   - **工具 ID**: 例如 `1`
   - **Target Link URI**: `http://localhost:8081/lti/launch/{toolId}`
   - **JWKS URL**: `http://localhost:8081/lti/jwks/{toolId}`
   - **Platform Issuer**: 你的 LMS 地址
   - **Client ID**: 在 LMS 中生成的客户端 ID

### 2. 在 LMS 中配置外部工具

根据你的 LMS 类型，选择对应的配置方式。

---

## Moodle 接入

### 方式一：通过管理后台配置（推荐）

#### 步骤 1: 在 LTI Gateway 中创建工具

1. 访问 `http://localhost:8081/admin`
2. 点击"➕ 添加新工具"
3. 填写以下信息：

| 字段 | 值 |
|------|-----|
| 工具名称 | Moodle 生产环境 |
| LTI 版本 | LTI 1.3 |
| Platform Issuer URL | `https://your-moodle.com` |
| 客户端 ID | （先留空，步骤 3 获取后填写）|
| Moodle API 地址 | `http://moodle:8080` |
| Web Service Token | （步骤 4 获取）|

4. 保存后记录工具 ID

#### 步骤 2: 在 Moodle 中注册外部工具

1. 以管理员身份登录 Moodle
2. 进入 **网站管理 → 插件 → 活动模块 → 外部工具 → 管理工具** (`/admin/tool/lti/tooltypes.php`)
3. 点击"**配置新工具**"
4. 填写工具配置：

**常规设置**:
- 工具名称: `LTI Gateway`
- 工具 URL: `http://localhost:8081/lti/launch/{toolId}`
- LTI 版本: `LTI 1.3`

**LTI 1.3 设置**:
- 公钥类型: `密钥集 URL`
- 密钥集 URL: `http://localhost:8081/lti/jwks/{toolId}`
- 启动登录 URL: `http://localhost:8081/lti/launch/{toolId}`
- 重定向 URI: `http://localhost:8081/lti/launch/{toolId}`

5. 保存后，Moodle 会生成 **客户端 ID**，复制这个 ID

#### 步骤 3: 更新 LTI Gateway 配置

1. 回到 LTI Gateway 管理后台
2. 编辑刚才创建的工具
3. 将 Moodle 生成的 **客户端 ID** 填入
4. 保存配置

#### 步骤 4: 配置 Moodle Web Service

1. 在 Moodle 中启用 Web Service：
   - 进入 **网站管理 → 高级功能**
   - 启用 **Web 服务**

2. 创建外部服务：
   - 进入 **网站管理 → 服务器 → Web 服务 → 外部服务**
   - 点击"**添加**"
   - 名称: `LTI Gateway`
   - 启用: 是
   - 添加函数：
     - `core_user_create_users`
     - `core_user_get_users_by_field`
     - `core_webservice_get_site_info`

3. 创建令牌：
   - 进入 **网站管理 → 服务器 → Web 服务 → 管理令牌**
   - 点击"**创建令牌**"
   - 选择用户（推荐创建专用服务账号）
   - 选择服务: `LTI Gateway`
   - 保存并复制令牌

4. 将令牌填入 LTI Gateway 的 **Web Service Token** 字段

#### 步骤 5: 测试连接

1. 在 LTI Gateway 管理后台
2. 点击工具卡片的"🔌 测试"按钮
3. 确认显示"连接成功"

#### 步骤 6: 在课程中添加活动

1. 进入 Moodle 课程
2. 开启编辑模式
3. 点击"**添加活动或资源**"
4. 选择"**外部工具**"
5. 选择预配置的 `LTI Gateway`
6. 设置活动名称，保存

---

### 方式二：通过课程直接配置

如果不想在站点级别配置，可以在单个课程中直接添加：

1. 进入课程，开启编辑模式
2. 添加活动 → 外部工具
3. 选择"**手动配置**"
4. 填写 LTI 1.3 参数（同上）

---

## Canvas 接入

### 步骤 1: 在 Canvas 中创建开发者密钥

1. 以管理员身份登录 Canvas
2. 进入 **Admin → {账户} → Developer Keys**
3. 点击"**+ Developer Key**" → "**+ LTI Key**"
4. 填写配置：

| 字段 | 值 |
|------|-----|
| Key Name | LTI Gateway |
| Owner Email | 你的邮箱 |
| Redirect URIs | `http://localhost:8081/lti/launch/{toolId}` |
| Target Link URI | `http://localhost:8081/lti/launch/{toolId}` |
| OpenID Connect Initiation Url | `http://localhost:8081/lti/launch/{toolId}` |
| JWK Method | Public JWK URL |
| Public JWK URL | `http://localhost:8081/lti/jwks/{toolId}` |

5. 在 **Placements** 中选择工具显示位置（如 Course Navigation、Assignment Menu 等）
6. 保存后记录 **Client ID**

### 步骤 2: 在 LTI Gateway 中配置

1. 创建或编辑工具
2. Platform Issuer: `https://your-canvas.instructure.com`
3. Client ID: Canvas 生成的 Client ID
4. API Base URL: Canvas API 地址（如果有）

### 步骤 3: 在 Canvas 中启用工具

1. 进入 **Admin → {账户} → Settings**
2. 找到 **Apps** 标签
3. 点击"**+ App**"
4. 选择 **By Client ID**
5. 输入 Client ID
6. 安装应用

---

## 通用 LTI 1.3 配置

对于其他支持 LTI 1.3 的 LMS 平台，需要配置以下参数：

### LTI Gateway 提供的参数

| 参数 | 值 | 说明 |
|------|-----|------|
| Target Link URI | `http://localhost:8081/lti/launch/{toolId}` | 启动 URL |
| Login Initiation URL | `http://localhost:8081/lti/launch/{toolId}` | 登录初始化 URL |
| JWKS URL | `http://localhost:8081/lti/jwks/{toolId}` | 公钥集合地址 |
| Redirect URIs | `http://localhost:8081/lti/launch/{toolId}` | 重定向地址 |

### 从 LMS 获取的参数

| 参数 | 来源 | 用途 |
|------|------|------|
| Issuer | LMS 平台 URL | 平台标识 |
| Client ID | LMS 生成的客户端 ID | 客户端认证 |
| Deployment ID | LMS 部署 ID | 部署标识（某些平台需要）|

### 自定义参数（推荐配置）

在 LMS 中配置以下自定义参数，以便 LTI Gateway 正确提取学号：

```
custom_student_id=$User.username
```

支持的变量（根据 LMS 不同可能有所差异）：
- `$User.username` - 用户名/学号
- `$User.id` - 用户 ID
- `$Person.sourcedId` - 学号（LIS 标准）

---

## 配置验证清单

接入完成后，请检查以下项目：

### LTI Gateway 端

- [ ] 工具已创建并配置正确
- [ ] Platform Issuer 与 LMS 地址匹配
- [ ] Client ID 已填写
- [ ] API Base URL 可访问
- [ ] Web Service Token 已配置
- [ ] 连接测试通过

### LMS 端

- [ ] 外部工具已注册
- [ ] LTI 1.3 参数正确
- [ ] JWKS URL 可访问
- [ ] 重定向 URI 配置正确
- [ ] 工具已在课程中启用

### 功能测试

- [ ] 学生可以从 LMS 启动工具
- [ ] 学号正确传递
- [ ] 用户自动创建/同步成功
- [ ] 可以正常访问下游 Moodle

---

## 常见问题

### Q1: 启动时提示 "Invalid LTI request"

**可能原因**:
- Platform Issuer 不匹配
- Client ID 错误
- 时间不同步（JWT 过期）

**解决方法**:
1. 检查 LTI Gateway 中的 Platform Issuer 是否与 LMS 地址完全一致
2. 确认 Client ID 正确无误
3. 同步服务器时间

### Q2: 学号提取失败

**可能原因**:
- 未配置自定义参数
- 自定义参数名称错误
- LMS 未传递该字段

**解决方法**:
1. 在 LMS 中配置 `custom_student_id` 自定义参数
2. 检查 LTI Gateway 日志，查看实际接收到的参数
3. 使用 `lis_person_sourcedid` 作为备选

### Q3: 用户无法在下游 Moodle 中登录

**可能原因**:
- Web Service Token 无效
- Moodle API 地址不可访问
- 用户创建失败

**解决方法**:
1. 在 LTI Gateway 中测试连接
2. 检查 Moodle Web Service 配置
3. 查看 LTI Gateway 日志中的错误信息

### Q4: Canvas 中工具不显示

**可能原因**:
- Placement 未配置
- 工具未启用
- 权限问题

**解决方法**:
1. 在 Developer Key 中配置 Placement
2. 确保工具在账户/课程级别已启用
3. 检查用户权限

---

## 调试技巧

### 1. 查看 LTI Gateway 日志

```bash
docker exec lti_gateway_app tail -f /var/www/html/storage/logs/lti-$(date +%Y-%m-%d).log
```

### 2. 检查 JWT 内容

在浏览器开发者工具中查看网络请求，检查 `id_token` 的内容：

```javascript
// 在 Console 中解码 JWT
atob('your-jwt-payload-part')
```

### 3. 测试 JWKS 端点

```bash
curl http://localhost:8081/lti/jwks/{toolId}
```

### 4. 验证启动流程

1. 打开浏览器开发者工具 → Network
2. 从 LMS 启动工具
3. 观察请求流程：
   - LMS → OIDC Login
   - LMS → LTI Gateway (with id_token)
   - LTI Gateway → Moodle

---

## 支持的平台

已测试支持的平台：

- ✅ Moodle 3.9+
- ✅ Canvas LMS
- ✅ Blackboard Learn
- ✅ Brightspace/D2L
- ✅ Sakai

理论上支持所有符合 LTI 1.3 / LTI Advantage 标准的平台。

---

## 获取帮助

如有问题，请：

1. 查看 LTI Gateway 日志
2. 检查浏览器开发者工具中的网络请求
3. 参考 [API 文档](./API.md)
4. 提交 Issue 到 GitHub 仓库
