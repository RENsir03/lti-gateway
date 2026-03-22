#!/bin/bash

# LTI Gateway 安装脚本

set -e

echo "============================================"
echo "LTI Gateway 安装脚本"
echo "============================================"

# 检查 Docker
echo "[1/6] 检查 Docker 环境..."
if ! command -v docker &> /dev/null; then
    echo "错误: Docker 未安装"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "错误: Docker Compose 未安装"
    exit 1
fi

echo "Docker 环境检查通过"

# 创建必要的目录
echo "[2/6] 创建必要的目录..."
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p database/seeders
mkdir -p database/factories
mkdir -p resources/views/lti

# 复制环境配置
echo "[3/6] 配置环境变量..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "已创建 .env 文件，请编辑配置"
fi

# 生成应用密钥
echo "[4/6] 生成应用密钥..."
APP_KEY=$(openssl rand -base64 32)
sed -i "s/APP_KEY=/APP_KEY=base64:${APP_KEY}/g" .env

echo "应用密钥已生成"

# 启动 Docker 容器
echo "[5/6] 启动 Docker 容器..."
cd ..
docker-compose -f docker-compose-lti.yml up -d --build

# 等待数据库就绪
echo "等待数据库就绪..."
sleep 10

# 运行迁移
echo "[6/6] 运行数据库迁移..."
docker-compose -f docker-compose-lti.yml exec -T lti_gateway_app php artisan migrate --force

echo ""
echo "============================================"
echo "安装完成!"
echo "============================================"
echo ""
echo "LTI Gateway 访问地址: http://localhost:8081"
echo "Moodle 访问地址: http://localhost:8080"
echo ""
echo "下一步:"
echo "1. 配置 Moodle Web Service Token"
echo "2. 添加工具配置到数据库"
echo "3. 测试 LTI 启动"
echo ""
