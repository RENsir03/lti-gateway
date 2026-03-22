<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTI Gateway</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 1200px;
            width: 100%;
        }
        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .subtitle {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.875rem;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .links {
            margin-top: 3rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .link {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            transition: all 0.2s;
            cursor: pointer;
            background: transparent;
            font-size: 0.875rem;
        }
        .link:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.5);
        }
        .link.active {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.8);
        }
        .version {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0.6;
            font-size: 0.875rem;
        }

        /* 操作日志面板样式 */
        .logs-panel {
            margin-top: 2rem;
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 1.5rem;
            color: #333;
            text-align: left;
            display: none;
            max-height: 600px;
            overflow-y: auto;
        }
        .logs-panel.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .logs-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }
        .logs-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
        }
        .stat-item {
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        .stat-label {
            color: #6b7280;
            margin-right: 0.5rem;
        }
        .stat-value {
            font-weight: 600;
        }
        .stat-value.success { color: #10b981; }
        .stat-value.fail { color: #ef4444; }

        /* 日志列表样式 */
        .logs-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .log-item {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1rem;
            border-left: 4px solid #e5e7eb;
            transition: all 0.2s;
        }
        .log-item:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }
        .log-item.success {
            border-left-color: #10b981;
        }
        .log-item.fail {
            border-left-color: #ef4444;
        }
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .log-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .log-status.success {
            background: #d1fae5;
            color: #065f46;
        }
        .log-status.fail {
            background: #fee2e2;
            color: #991b1b;
        }
        .log-time {
            font-size: 0.75rem;
            color: #6b7280;
        }
        .log-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .log-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .log-detail-label {
            color: #6b7280;
            min-width: 60px;
        }
        .log-detail-value {
            color: #374151;
            font-weight: 500;
        }
        .log-processing-time {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        /* 加载状态 */
        .logs-loading {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        .logs-loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #e5e7eb;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 空状态 */
        .logs-empty {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        .logs-empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* 错误状态 */
        .logs-error {
            text-align: center;
            padding: 2rem;
            color: #ef4444;
            background: #fee2e2;
            border-radius: 8px;
        }

        /* 刷新按钮 */
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background 0.2s;
        }
        .refresh-btn:hover {
            background: #5a67d8;
        }
        .refresh-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        /* 响应式 */
        @media (max-width: 768px) {
            h1 { font-size: 2rem; }
            .logs-header { flex-direction: column; gap: 1rem; }
            .logs-stats { flex-wrap: wrap; justify-content: center; }
            .log-details { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>LTI Gateway</h1>
        <p class="subtitle">基于学号的通用 LTI 代理网关</p>
        
        <div class="status">
            <span class="status-dot"></span>
            <span>系统运行正常</span>
        </div>

        <div class="links">
            <a href="/lti/health" class="link">健康检查</a>
            <a href="/docs" class="link">API 文档</a>
            <button class="link" id="logsBtn" onclick="toggleLogs()">
                <span id="logsBtnText">查看操作日志</span>
            </button>
            <a href="https://github.com/RENsir03/lti-gateway/tree/main/lti-gateway" class="link" target="_blank">GitHub</a>
        </div>

        <!-- 操作日志面板 -->
        <div class="logs-panel" id="logsPanel">
            <div class="logs-header">
                <h2 class="logs-title">系统操作日志</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="logs-stats" id="logsStats">
                        <div class="stat-item">
                            <span class="stat-label">总计:</span>
                            <span class="stat-value" id="statTotal">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">成功:</span>
                            <span class="stat-value success" id="statSuccess">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">失败:</span>
                            <span class="stat-value fail" id="statFail">-</span>
                        </div>
                    </div>
                    <button class="refresh-btn" id="refreshBtn" onclick="loadLogs()">刷新</button>
                </div>
            </div>
            <div id="logsContent">
                <div class="logs-loading">正在加载日志数据</div>
            </div>
        </div>

        <div class="version">
            v1.0.0 | Laravel {{ app()->version() }}
        </div>
    </div>

    <script>
        let isLogsVisible = false;
        let isLoading = false;

        /**
         * 切换日志面板显示/隐藏
         */
        function toggleLogs() {
            const panel = document.getElementById('logsPanel');
            const btn = document.getElementById('logsBtn');
            const btnText = document.getElementById('logsBtnText');

            isLogsVisible = !isLogsVisible;

            if (isLogsVisible) {
                panel.classList.add('show');
                btn.classList.add('active');
                btnText.textContent = '隐藏操作日志';
                loadLogs();
            } else {
                panel.classList.remove('show');
                btn.classList.remove('active');
                btnText.textContent = '查看操作日志';
            }
        }

        /**
         * 加载日志数据
         */
        async function loadLogs() {
            if (isLoading) return;

            isLoading = true;
            const refreshBtn = document.getElementById('refreshBtn');
            const content = document.getElementById('logsContent');

            refreshBtn.disabled = true;
            refreshBtn.textContent = '加载中...';

            try {
                // 并行获取统计数据和最近日志
                const [statsResponse, logsResponse] = await Promise.all([
                    fetch('/logs/stats'),
                    fetch('/logs/recent?limit=20')
                ]);

                if (!statsResponse.ok || !logsResponse.ok) {
                    throw new Error('获取数据失败');
                }

                const statsData = await statsResponse.json();
                const logsData = await logsResponse.json();

                // 更新统计数据
                updateStats(statsData.data);

                // 更新日志列表
                renderLogs(logsData.data);

            } catch (error) {
                console.error('加载日志失败:', error);
                content.innerHTML = `
                    <div class="logs-error">
                        <div>加载日志数据失败</div>
                        <div style="font-size: 0.875rem; margin-top: 0.5rem;">${error.message}</div>
                    </div>
                `;
            } finally {
                isLoading = false;
                refreshBtn.disabled = false;
                refreshBtn.textContent = '刷新';
            }
        }

        /**
         * 更新统计数据展示
         */
        function updateStats(stats) {
            document.getElementById('statTotal').textContent = stats.total;
            document.getElementById('statSuccess').textContent = stats.success;
            document.getElementById('statFail').textContent = stats.fail;
        }

        /**
         * 渲染日志列表
         */
        function renderLogs(logs) {
            const content = document.getElementById('logsContent');

            if (!logs || logs.length === 0) {
                content.innerHTML = `
                    <div class="logs-empty">
                        <div class="logs-empty-icon">📋</div>
                        <div>暂无操作日志记录</div>
                    </div>
                `;
                return;
            }

            const logsHtml = logs.map(log => `
                <div class="log-item ${log.status}">
                    <div class="log-header">
                        <span class="log-status ${log.status}">
                            ${log.status === 'success' ? '✓ 成功' : '✗ 失败'}
                        </span>
                        <span class="log-time">${formatTime(log.created_at)}</span>
                    </div>
                    <div class="log-details">
                        <div class="log-detail">
                            <span class="log-detail-label">工具:</span>
                            <span class="log-detail-value">${escapeHtml(log.tool_name)}</span>
                        </div>
                        <div class="log-detail">
                            <span class="log-detail-label">类型:</span>
                            <span class="log-detail-value">${log.tool_type?.toUpperCase() || 'Unknown'}</span>
                        </div>
                        <div class="log-detail">
                            <span class="log-detail-label">学号:</span>
                            <span class="log-detail-value">${log.student_id || 'N/A'}</span>
                        </div>
                        <div class="log-detail">
                            <span class="log-detail-label">IP:</span>
                            <span class="log-detail-value">${log.ip_address || 'N/A'}</span>
                        </div>
                    </div>
                    ${log.processing_time_ms ? `
                        <div class="log-processing-time">
                            处理时间: ${log.processing_time_ms}ms
                        </div>
                    ` : ''}
                    ${log.error_code ? `
                        <div class="log-processing-time" style="color: #ef4444;">
                            错误码: ${log.error_code}
                        </div>
                    ` : ''}
                </div>
            `).join('');

            content.innerHTML = `<div class="logs-list">${logsHtml}</div>`;
        }

        /**
         * 格式化时间显示
         */
        function formatTime(isoString) {
            if (!isoString) return 'N/A';
            const date = new Date(isoString);
            const now = new Date();
            const diff = now - date;

            // 小于1分钟显示"刚刚"
            if (diff < 60000) {
                return '刚刚';
            }
            // 小于1小时显示"X分钟前"
            if (diff < 3600000) {
                return `${Math.floor(diff / 60000)}分钟前`;
            }
            // 小于24小时显示"X小时前"
            if (diff < 86400000) {
                return `${Math.floor(diff / 3600000)}小时前`;
            }

            // 否则显示具体日期时间
            return date.toLocaleString('zh-CN', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        /**
         * HTML转义防止XSS
         */
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 自动刷新日志（每30秒）
        setInterval(() => {
            if (isLogsVisible && !isLoading) {
                loadLogs();
            }
        }, 30000);
    </script>
</body>
</html>
