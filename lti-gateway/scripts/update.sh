#!/bin/bash

# LTI Gateway 更新脚本

set -e

echo "============================================"
echo "LTI Gateway 更新脚本"
echo "============================================"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 检查是否在正确目录
if [ ! -f "composer.json" ]; then
    echo -e "${RED}错误: 请在项目根目录运行此脚本${NC}"
    exit 1
fi

# 备份
echo -e "${YELLOW}[1/6]${NC} 备份当前数据..."
if [ -f "scripts/backup.sh" ]; then
    ./scripts/backup.sh
else
    echo "跳过备份 (脚本不存在)"
fi

# 拉取最新代码
echo -e "${YELLOW}[2/6]${NC} 拉取最新代码..."
git pull origin main || echo "跳过 (非 git 仓库)"

# 更新依赖
echo -e "${YELLOW}[3/6]${NC} 更新 Composer 依赖..."
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app composer install --no-dev --optimize-autoloader

# 运行迁移
echo -e "${YELLOW}[4/6]${NC} 运行数据库迁移..."
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan migrate --force

# 清除缓存
echo -e "${YELLOW}[5/6]${NC} 清除缓存..."
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan cache:clear
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan config:clear
docker-compose -f ../docker-compose-lti.yml exec lti_gateway_app php artisan view:clear

# 重启服务
echo -e "${YELLOW}[6/6]${NC} 重启服务..."
docker-compose -f ../docker-compose-lti.yml restart

echo ""
echo "============================================"
echo -e "${GREEN}更新完成！${NC}"
echo "============================================"
