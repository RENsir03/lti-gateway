#!/bin/bash

# 检查系统要求脚本

echo "============================================"
echo "LTI Gateway 系统要求检查"
echo "============================================"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

CHECKS_PASSED=0
CHECKS_FAILED=0

check_command() {
    if command -v $1 &> /dev/null; then
        echo -e "${GREEN}✓${NC} $1 已安装"
        ((CHECKS_PASSED++))
        return 0
    else
        echo -e "${RED}✗${NC} $1 未安装"
        ((CHECKS_FAILED++))
        return 1
    fi
}

check_version() {
    local cmd=$1
    local min_version=$2
    local current_version=$($cmd --version 2>&1 | grep -oE '[0-9]+\.[0-9]+' | head -1)
    
    if [ "$(printf '%s\n' "$min_version" "$current_version" | sort -V | head -n1)" = "$min_version" ]; then
        echo -e "${GREEN}✓${NC} $cmd 版本 $current_version (>= $min_version)"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}✗${NC} $cmd 版本 $current_version (需要 >= $min_version)"
        ((CHECKS_FAILED++))
    fi
}

check_port() {
    local port=$1
    if ! netstat -tlnp 2>/dev/null | grep -q ":$port "; then
        echo -e "${GREEN}✓${NC} 端口 $port 可用"
        ((CHECKS_PASSED++))
    else
        echo -e "${YELLOW}!${NC} 端口 $port 已被占用"
        ((CHECKS_FAILED++))
    fi
}

check_disk_space() {
    local available=$(df -BG . | tail -1 | awk '{print $4}' | sed 's/G//')
    if [ "$available" -ge 20 ]; then
        echo -e "${GREEN}✓${NC} 磁盘空间充足 (${available}GB 可用)"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}✗${NC} 磁盘空间不足 (${available}GB 可用，需要 20GB+)"
        ((CHECKS_FAILED++))
    fi
}

check_memory() {
    local total=$(free -g 2>/dev/null | awk '/^Mem:/{print $2}')
    if [ -z "$total" ]; then
        # macOS
        total=$(sysctl -n hw.memsize 2>/dev/null | awk '{print int($1/1024/1024/1024)}')
    fi
    
    if [ "$total" -ge 4 ]; then
        echo -e "${GREEN}✓${NC} 内存充足 (${total}GB)"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}✗${NC} 内存不足 (${total}GB，需要 4GB+)"
        ((CHECKS_FAILED++))
    fi
}

# 检查 Docker
echo ""
echo "检查 Docker 环境..."
check_command docker
check_version docker 20.10
check_command docker-compose
check_version docker-compose 2.0

# 检查端口
echo ""
echo "检查端口可用性..."
check_port 8081
check_port 5433
check_port 6380

# 检查系统资源
echo ""
echo "检查系统资源..."
check_disk_space
check_memory

# 检查网络
echo ""
echo "检查网络连接..."
if ping -c 1 github.com &> /dev/null; then
    echo -e "${GREEN}✓${NC} 网络连接正常"
    ((CHECKS_PASSED++))
else
    echo -e "${YELLOW}!${NC} 网络连接可能受限"
fi

# 总结
echo ""
echo "============================================"
if [ $CHECKS_FAILED -eq 0 ]; then
    echo -e "${GREEN}所有检查通过！可以开始安装。${NC}"
    echo "运行: make install"
else
    echo -e "${YELLOW}有 $CHECKS_FAILED 项检查未通过，请解决后再安装。${NC}"
fi
echo "============================================"

exit $CHECKS_FAILED
