# LTI Gateway 生产部署指南

## 系统要求

- Docker >= 20.10
- Docker Compose >= 2.0
- 4GB+ RAM
- 20GB+ 磁盘空间

## 环境配置

### 1. 基础配置

```bash
# 复制环境变量
cp .env.example .env

# 编辑关键配置
nano .env
```

必需修改的配置项：

```env
APP_ENV=production
APP_URL=https://lti-gateway.yourdomain.com
APP_KEY=your-generated-key

DB_PASSWORD=your-secure-password

MOODLE_WEBSERVICE_TOKEN=your-moodle-token
DEFAULT_VIRTUAL_EMAIL_DOMAIN=your-university.edu
```

### 2. 生成应用密钥

```bash
php artisan key:generate --show
# 复制输出到 APP_KEY
```

### 3. 生成 RSA 密钥对 (LTI 1.3)

```bash
php scripts/generate-keys.php
# 保存私钥到配置，公钥给上游 LMS
```

## 部署步骤

### 使用 Docker Compose

```bash
# 1. 启动服务
docker-compose -f docker-compose-lti.yml up -d

# 2. 运行迁移
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan migrate --force

# 3. 创建初始配置
docker-compose -f docker-compose-lti.yml exec lti_gateway_app php artisan db:seed --class=ToolConfigSeeder

# 4. 测试连接
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan lti:test-moodle
```

### 使用 Makefile

```bash
# 一键安装
make install

# 查看状态
make health
make stats
make tools
```

## 监控与维护

### 健康检查

```bash
# 手动检查
curl https://lti-gateway.yourdomain.com/lti/health

# 命令行检查
make health
```

### 日志查看

```bash
# 实时日志
make logs

# 查看特定服务
docker-compose -f docker-compose-lti.yml logs -f lti_gateway_app
```

### 备份策略

```bash
# 手动备份
make backup

# 自动备份 (添加到 crontab)
0 2 * * * cd /path/to/lti-gateway && make backup
```

### 定时任务

已配置的定时任务：

- `02:00` - 清理过期日志
- 每 5 分钟 - 健康检查
- 每小时 - 统计信息记录

## 安全建议

1. **启用 HTTPS**
   - 使用 Let's Encrypt 证书
   - 配置 HSTS

2. **防火墙配置**
   - 仅开放 80/443 端口
   - 限制数据库访问

3. **定期更新**
   - 更新 Docker 镜像
   - 更新 Composer 依赖

4. **监控告警**
   - 配置 Sentry 错误监控
   - 设置服务可用性告警

## 故障排查

### 服务无法启动

```bash
# 检查端口占用
netstat -tlnp | grep 8081

# 查看详细日志
docker-compose -f docker-compose-lti.yml logs
```

### 数据库连接失败

```bash
# 检查数据库状态
docker-compose -f docker-compose-lti.yml exec lti_gateway_db pg_isready

# 重置数据库
make fresh
```

### Moodle API 错误

```bash
# 测试连接
make test-moodle

# 查看详细错误日志
docker-compose -f docker-compose-lti.yml exec lti_gateway_app tail -f storage/logs/laravel.log
```

## 性能优化

1. **启用 OPcache** (已默认启用)
2. **配置 Redis 持久化**
3. **调整 PHP-FPM 进程数**
4. **启用 Nginx gzip 压缩** (已默认启用)

## 扩展阅读

- [Moodle Web Service 配置](MOODLE_SETUP.md)
- [API 文档](API.md)
- [快速开始](../QUICKSTART.md)
