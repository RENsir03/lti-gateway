# LTI Gateway 快速开始

## 环境要求

- Docker >= 20.10
- Docker Compose >= 2.0
- 4GB+ 可用内存

## 5 分钟快速启动

### 1. 启动所有服务

```bash
cd /path/to/moodle-project

# 创建共享网络
docker network create moodle_network 2>/dev/null || true

# 启动 Moodle (如果还没运行)
docker-compose up -d

# 启动 LTI Gateway
docker-compose -f docker-compose-lti.yml up -d --build
```

### 2. 等待服务就绪

```bash
# 查看日志等待启动完成
docker-compose -f docker-compose-lti.yml logs -f lti_gateway_app
```

看到 `INFO Server running` 表示启动成功。

### 3. 初始化数据库

```bash
# 运行迁移
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan migrate --force

# 创建示例配置
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan db:seed --class=ToolConfigSeeder
```

### 4. 配置 Moodle Web Service

1. 访问 http://localhost:8080 登录 Moodle (admin/Admin@123)
2. 按照 [MOODLE_SETUP.md](docs/MOODLE_SETUP.md) 配置 Web Service
3. 获取 Token 并更新到 LTI Gateway

```bash
# 更新 Token
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan tinker
>>> $config = App\Models\ToolConfig::first();
>>> $config->update(['auth_token' => 'your-token-here']);
>>> exit
```

### 5. 验证安装

```bash
# 健康检查
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan lti:health-check

# 或访问
open http://localhost:8081/lti/health
```

## 常用命令

```bash
# 查看日志
docker-compose -f docker-compose-lti.yml logs -f

# 进入容器
docker-compose -f docker-compose-lti.yml exec lti_gateway_app bash

# 运行测试
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan test

# 清理日志
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan lti:cleanup

# 重启服务
docker-compose -f docker-compose-lti.yml restart
```

## 访问地址

| 服务 | 地址 | 说明 |
|------|------|------|
| Moodle | http://localhost:8080 | 课程平台 |
| LTI Gateway | http://localhost:8081 | 代理网关 |
| 健康检查 | http://localhost:8081/lti/health | 状态检查 |
| JWKS | http://localhost:8081/lti/jwks/1 | 公钥集合 |

## 故障排查

### 服务无法启动

```bash
# 检查端口占用
netstat -an | grep 8081

# 查看详细日志
docker-compose -f docker-compose-lti.yml logs
```

### 数据库连接失败

```bash
# 等待数据库就绪
docker-compose -f docker-compose-lti.yml exec lti_gateway_db pg_isready

# 重新运行迁移
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan migrate:fresh --seed
```

### Moodle API 错误

```bash
# 检查 Moodle 健康状态
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan lti:health-check
```

## 下一步

- 阅读 [API 文档](docs/API.md)
- 配置 LTI 1.3 工具
- 查看 [Moodle 配置指南](docs/MOODLE_SETUP.md)
