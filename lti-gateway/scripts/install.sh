#!/bin/bash

# LTI Gateway Docker 安装脚本

set -e

echo "============================================"
echo "LTI Gateway Docker 安装脚本"
echo "============================================"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 检查 Docker
echo -e "${YELLOW}[1/8]${NC} 检查 Docker 环境..."
if ! command -v docker &> /dev/null; then
    echo -e "${RED}错误: Docker 未安装${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}错误: Docker Compose 未安装${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Docker 环境检查通过"

# 检查网络
echo -e "${YELLOW}[2/8]${NC} 检查 Docker 网络..."
if ! docker network ls | grep -q "moodle_network"; then
    echo "创建 moodle_network 网络..."
    docker network create moodle_network
fi
echo -e "${GREEN}✓${NC} 网络检查完成"

# 创建目录
echo -e "${YELLOW}[3/8]${NC} 创建必要的目录..."
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p bootstrap/cache
mkdir -p database/seeders
mkdir -p database/factories
echo -e "${GREEN}✓${NC} 目录创建完成"

# 设置权限
echo -e "${YELLOW}[4/8]${NC} 设置目录权限..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || true
echo -e "${GREEN}✓${NC} 权限设置完成"

# 复制环境配置
echo -e "${YELLOW}[5/8]${NC} 配置环境变量..."
if [ ! -f .env ]; then
    cp .env.example .env
    # 生成应用密钥
    APP_KEY=$(openssl rand -base64 32)
    sed -i "s/APP_KEY=/APP_KEY=base64:${APP_KEY}/g" .env
    echo -e "${GREEN}✓${NC} 已创建 .env 文件并生成应用密钥"
else
    echo -e "${YELLOW}!${NC} .env 文件已存在，跳过创建"
fi

# 启动服务
echo -e "${YELLOW}[6/8]${NC} 启动 Docker 容器..."
cd ..
docker-compose -f docker-compose-lti.yml up -d --build

# 等待数据库就绪
echo -e "${YELLOW}[7/8]${NC} 等待数据库就绪..."
sleep 5

# 检查数据库连接
for i in {1..30}; do
    if docker-compose -f docker-compose-lti.yml exec -T lti_gateway_db pg_isready -U lti_gateway_user > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} 数据库已就绪"
        break
    fi
    echo -n "."
    sleep 2
    
    if [ $i -eq 30 ]; then
        echo -e "${RED}错误: 数据库连接超时${NC}"
        exit 1
    fi
done

# 运行迁移
echo -e "${YELLOW}[8/8]${NC} 运行数据库迁移..."
docker-compose -f docker-compose-lti.yml exec -T lti_gateway_app php artisan migrate --force
docker-compose -f docker-compose-lti.yml exec -T lti_gateway_app php artisan db:seed --class=ToolConfigSeeder

echo -e "${GREEN}✓${NC} 迁移完成"

echo ""
echo "============================================"
echo -e "${GREEN}安装完成!${NC}"
echo "============================================"
echo ""
echo "访问地址:"
echo "  - LTI Gateway: http://localhost:8081"
echo "  - 健康检查:    http://localhost:8081/lti/health"
echo "  - Moodle:      http://localhost:8080"
echo ""
echo "下一步:"
echo "  1. 配置 Moodle Web Service Token"
echo "  2. 运行健康检查: make health"
echo "  3. 查看日志: make logs"
echo ""
echo "常用命令:"
echo "  make up      - 启动服务"
echo "  make down    - 停止服务"
echo "  make shell   - 进入容器"
echo ""
