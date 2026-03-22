# Moodle Web Service 配置指南

## 1. 启用 Web Service

1. 以管理员身份登录 Moodle (http://localhost:8080)
2. 进入 **网站管理 → 高级功能**
3. 勾选 **启用 Web 服务** → 保存更改

## 2. 启用 REST 协议

1. 进入 **网站管理 → 服务器 → Web 服务 → 管理协议**
2. 启用 **REST 协议**
3. 保存更改

## 3. 创建外部服务

1. 进入 **网站管理 → 服务器 → Web 服务 → 外部服务**
2. 点击 **添加**
3. 填写：
   - **名称**: LTI Gateway Service
   - **已启用**: ✓
   - **授权用户**: 选择管理员用户
4. 点击 **添加服务**

## 4. 添加函数

在服务详情页面，点击 **添加函数**，添加以下函数：

- `core_user_create_users` - 创建用户
- `core_user_get_users_by_field` - 查询用户
- `core_webservice_get_site_info` - 获取站点信息

## 5. 生成 Token

1. 进入 **网站管理 → 服务器 → Web 服务 → 管理令牌**
2. 点击 **创建令牌**
3. 选择用户: **admin**
4. 选择服务: **LTI Gateway Service**
5. 点击 **保存更改**
6. **复制生成的 Token** (类似: `a1b2c3d4e5f6...`)

## 6. 配置 LTI Gateway

```bash
# 进入容器
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app bash

# 使用 Tinker 更新配置
php artisan tinker

# 在 tinker 中执行:
>>> $config = App\Models\ToolConfig::first();
>>> $config->update(['auth_token' => '你的-token-这里']);
>>> exit
```

## 7. 测试连接

```bash
# 运行健康检查
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:health-check
```

## 8. 配置 LTI 1.3 工具 (可选)

### 在 Moodle 中配置外部工具

1. 进入 **网站管理 → 插件 → 活动模块 → 外部工具 → 管理工具**
2. 点击 **配置工具**
3. 填写：
   - **工具名称**: LTI Gateway
   - **工具 URL**: `http://lti_gateway_nginx/lti/launch/1`
   - **LTI 版本**: LTI 1.3
   - **公钥类型**: RSA 密钥
   - **公钥**: 从 http://localhost:8081/lti/jwks/1 获取

## 常见问题

### Q: Token 无效错误
检查 Token 是否正确复制，没有多余的空格。

### Q: 函数未找到错误
确保已在服务中添加所有必需的函数。

### Q: 跨域错误
确保 Moodle 和 LTI Gateway 在同一网络中，或配置 CORS。
