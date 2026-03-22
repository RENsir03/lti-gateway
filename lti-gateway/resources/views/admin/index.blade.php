<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTI Gateway 管理后台</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .nav-tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .nav-tab:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-tab.active {
            background: white;
            color: #667eea;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group .help-text {
            margin-top: 5px;
            font-size: 0.85rem;
            color: #888;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #555;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .status-card {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-item {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .status-item.ok {
            background: #d1fae5;
            color: #065f46;
        }

        .status-item.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-item.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-item h3 {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .status-item p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .config-display {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .config-display h4 {
            margin-bottom: 15px;
            color: #333;
        }

        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .config-item:last-child {
            border-bottom: none;
        }

        .config-item label {
            color: #666;
            font-weight: 500;
        }

        .config-item span {
            color: #333;
            font-family: monospace;
        }

        .config-item .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 LTI Gateway 管理后台</h1>
            <p>通过Web界面轻松管理LTI Gateway配置</p>
        </div>

        <div class="nav-tabs">
            <button class="nav-tab active" onclick="switchTab('status')">📊 系统状态</button>
            <button class="nav-tab" onclick="switchTab('tools')">🔧 工具管理</button>
            <button class="nav-tab" onclick="switchTab('config')">⚙️ 配置编辑</button>
            <button class="nav-tab" onclick="switchTab('logs')">📋 操作日志</button>
        </div>

        <div class="content-card">
            <!-- 系统状态页面 -->
            <div id="status-tab" class="tab-content active">
                <h2 class="section-title">系统状态监控</h2>
                <div id="status-container">
                    <div class="status-card">
                        <div class="status-item" id="db-status">
                            <h3>🗄️ 数据库</h3>
                            <p>检查中...</p>
                        </div>
                        <div class="status-item" id="redis-status">
                            <h3>⚡ Redis</h3>
                            <p>检查中...</p>
                        </div>
                    </div>

                    <!-- 工具状态概览 -->
                    <div style="margin-top: 20px; background: #f8f9fa; padding: 20px; border-radius: 12px;">
                        <h3 style="margin-bottom: 15px; color: #333;">📊 工具状态概览</h3>
                        <div id="tools-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 15px;">
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold; color: #667eea;" id="tools-total">-</div>
                                <div style="color: #666; font-size: 0.9rem;">总工具数</div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold; color: #28a745;" id="tools-active">-</div>
                                <div style="color: #666; font-size: 0.9rem;">已启用</div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold; color: #17a2b8;" id="tools-connected">-</div>
                                <div style="color: #666; font-size: 0.9rem;">连接正常</div>
                            </div>
                        </div>
                    </div>

                    <!-- 各工具详细状态 -->
                    <div style="margin-top: 20px;">
                        <h3 style="margin-bottom: 15px; color: #333;">🔧 各工具详细状态</h3>
                        <div id="tools-status-list">
                            <p>加载中...</p>
                        </div>
                    </div>

                    <button class="btn btn-primary" onclick="refreshStatus()" style="margin-top: 20px;">
                        🔄 刷新状态
                    </button>
                </div>
            </div>

            <!-- 工具配置页面 -->
            <div id="config-tab" class="tab-content">
                <h2 class="section-title">工具配置管理</h2>
                <div id="config-container">
                    <div class="config-display" id="current-config">
                        <h4>当前配置</h4>
                        <div id="config-loading">加载中...</div>
                    </div>

                    <!-- 工具选择器 -->
                    <div class="form-group" style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <label for="tool-selector" style="font-weight: bold; color: #667eea;">选择要编辑的工具</label>
                        <select id="tool-selector" onchange="loadToolConfig(this.value)" style="width: 100%; padding: 10px; border: 2px solid #667eea; border-radius: 6px; font-size: 1rem;">
                            <option value="">-- 请选择工具 --</option>
                        </select>
                        <p class="help-text" style="margin-top: 5px; color: #666;">选择工具后，下方表单将显示该工具的当前配置</p>
                    </div>

                    <form id="config-form">
                        <input type="hidden" id="config_tool_id" name="tool_id">

                        <div class="form-group">
                            <label for="name">工具名称</label>
                            <input type="text" id="name" name="name" placeholder="例如: Test Tool">
                        </div>

                        <div class="form-group">
                            <label for="platform_issuer">平台 Issuer URL</label>
                            <input type="url" id="platform_issuer" name="platform_issuer" placeholder="https://moodle.example.com">
                            <p class="help-text">LTI平台的Issuer标识符</p>
                        </div>

                        <div class="form-group">
                            <label for="client_id">客户端 ID</label>
                            <input type="text" id="client_id" name="client_id" placeholder="test-client-id">
                        </div>

                        <div class="form-group">
                            <label for="api_base_url">Moodle API 地址</label>
                            <input type="url" id="api_base_url" name="api_base_url" placeholder="http://moodle:8080">
                            <p class="help-text">Moodle Web Service API的基础URL</p>
                        </div>

                        <div class="form-group">
                            <label for="auth_token">Web Service Token</label>
                            <input type="text" id="auth_token" name="auth_token" placeholder="输入新的Token（留空保持不变）">
                            <p class="help-text">Moodle的Web Service访问令牌</p>
                        </div>

                        <div class="form-group">
                            <label for="is_active">
                                <input type="checkbox" id="is_active" name="is_active">
                                启用此工具
                            </label>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">💾 保存配置</button>
                            <button type="button" class="btn btn-success" onclick="testConnection()">🔌 测试连接</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 工具管理页面 -->
            <div id="tools-tab" class="tab-content">
                <h2 class="section-title">工具管理</h2>
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="showAddToolModal()">➕ 添加新工具</button>
                </div>
                <div id="tools-list">
                    <p>加载中...</p>
                </div>
            </div>

            <!-- 操作日志页面 -->
            <div id="logs-tab" class="tab-content">
                <h2 class="section-title">操作日志</h2>
                <p>请返回首页查看操作日志，或访问 <a href="/">首页</a></p>
            </div>

            <!-- 添加工具弹窗 -->
            <div id="add-tool-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
                <div style="background: white; padding: 30px; border-radius: 16px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
                    <h3 style="margin-bottom: 20px;">添加新LTI工具</h3>
                    <form id="add-tool-form">
                        <div class="form-group">
                            <label for="new_name">工具名称 *</label>
                            <input type="text" id="new_name" name="name" required placeholder="例如: 生产环境Moodle">
                        </div>

                        <div class="form-group">
                            <label for="new_type">LTI版本 *</label>
                            <select id="new_type" name="type" required>
                                <option value="lti13">LTI 1.3</option>
                                <option value="lti11">LTI 1.1</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="new_platform_issuer">平台 Issuer URL *</label>
                            <input type="url" id="new_platform_issuer" name="platform_issuer" required placeholder="https://moodle.example.com">
                        </div>

                        <div class="form-group">
                            <label for="new_client_id">客户端 ID *</label>
                            <input type="text" id="new_client_id" name="client_id" required placeholder="client-id-123">
                        </div>

                        <div class="form-group">
                            <label for="new_api_base_url">Moodle API 地址 *</label>
                            <input type="url" id="new_api_base_url" name="api_base_url" required placeholder="http://moodle:8080">
                        </div>

                        <div class="form-group">
                            <label for="new_auth_token">Web Service Token *</label>
                            <input type="text" id="new_auth_token" name="auth_token" required placeholder="输入Moodle Web Service Token">
                        </div>

                        <div class="form-group">
                            <label for="new_jwks_url">JWKS URL</label>
                            <input type="url" id="new_jwks_url" name="jwks_url" placeholder="可选，留空自动生成">
                            <p class="help-text">用于验证平台JWT的公钥地址</p>
                        </div>

                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="hideAddToolModal()">取消</button>
                            <button type="submit" class="btn btn-primary">创建工具</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 切换标签页
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');

            if (tabName === 'status') {
                refreshStatus();
            } else if (tabName === 'config') {
                loadConfig();
            }
        }

        // 刷新系统状态
        async function refreshStatus() {
            try {
                const response = await fetch('/admin/api/system-status');
                const result = await response.json();

                if (result.success) {
                    updateStatusDisplay(result.data);
                } else {
                    showAlert('error', '获取状态失败: ' + result.message);
                }
            } catch (error) {
                showAlert('error', '请求失败: ' + error.message);
            }
        }

        // 更新状态显示
        function updateStatusDisplay(data) {
            // 更新数据库和Redis状态
            const dbStatus = document.getElementById('db-status');
            const redisStatus = document.getElementById('redis-status');

            if (dbStatus) updateStatusItem(dbStatus, data.database);
            if (redisStatus) updateStatusItem(redisStatus, data.redis);

            // 更新工具状态概览
            if (data.tools) {
                document.getElementById('tools-total').textContent = data.tools.total ?? '-';
                document.getElementById('tools-active').textContent = data.tools.active ?? '-';
                document.getElementById('tools-connected').textContent = data.tools.connected ?? '-';

                // 更新各工具详细状态
                updateToolsStatusList(data.tools.tools);
            }
        }

        function updateStatusItem(element, status) {
            element.className = 'status-item ' + status.status;
            element.querySelector('p').textContent = status.message;
        }

        // 更新工具状态列表
        function updateToolsStatusList(tools) {
            const container = document.getElementById('tools-status-list');

            if (!tools || tools.length === 0) {
                container.innerHTML = '<p>未配置工具</p>';
                return;
            }

            const html = tools.map(tool => `
                <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${tool.status === 'ok' ? '#28a745' : '#dc3545'};">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong style="font-size: 1.1rem;">${tool.name || '未命名工具'}</strong>
                        <span class="status-badge ${tool.status}">
                            ${tool.status === 'ok' ? '✅ 正常' : '❌ 异常'}
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; font-size: 0.85rem; color: #666;">
                        <div>ID: ${tool.id}</div>
                        <div>类型: ${tool.type?.toUpperCase() || 'N/A'}</div>
                        <div>状态: ${tool.is_active ? '🟢 启用' : '⚪ 停用'}</div>
                        <div>API: ${tool.api_base_url || 'N/A'}</div>
                    </div>
                    ${tool.message && tool.status !== 'ok' ? `<div style="margin-top: 8px; color: #dc3545; font-size: 0.85rem;">⚠️ ${tool.message}</div>` : ''}
                </div>
            `).join('');

            container.innerHTML = html;
        }

        // 加载配置
        async function loadConfig() {
            // 加载工具选择器
            await loadToolSelector();

            // 如果有工具，默认加载第一个
            const selector = document.getElementById('tool-selector');
            if (selector.options.length > 1) {
                selector.selectedIndex = 1;
                await loadToolConfig(selector.value);
            } else {
                document.getElementById('config-loading').textContent = '暂无工具配置';
            }
        }

        // 加载工具选择器
        async function loadToolSelector() {
            try {
                const response = await fetch('/admin/api/tools');
                const result = await response.json();

                if (result.success) {
                    const selector = document.getElementById('tool-selector');
                    // 保留第一个选项
                    const firstOption = selector.options[0];
                    selector.innerHTML = '';
                    selector.appendChild(firstOption);

                    // 添加工具选项
                    result.data.forEach(tool => {
                        const option = document.createElement('option');
                        option.value = tool.id;
                        option.textContent = `ID:${tool.id} - ${tool.name} (${tool.is_active ? '启用' : '停用'})`;
                        selector.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('加载工具选择器失败:', error);
            }
        }

        // 加载指定工具的配置
        async function loadToolConfig(toolId) {
            console.log('loadToolConfig 被调用，toolId:', toolId);

            const loadingEl = document.getElementById('config-loading');
            const formEl = document.getElementById('config-form');
            const toolIdEl = document.getElementById('config_tool_id');

            if (!toolId) {
                if (loadingEl) loadingEl.textContent = '请选择工具';
                if (formEl) formEl.reset();
                if (toolIdEl) toolIdEl.value = '';
                return;
            }

            if (loadingEl) loadingEl.textContent = '加载中...';

            try {
                const url = '/admin/api/tools/' + toolId;
                console.log('请求 URL:', url);

                const response = await fetch(url);
                console.log('响应状态:', response.status);

                const result = await response.json();
                console.log('响应数据:', result);

                if (result.success) {
                    displayConfig(result.data);
                    fillForm(result.data);
                    if (toolIdEl) toolIdEl.value = toolId;
                    if (loadingEl) loadingEl.textContent = '加载完成';
                } else {
                    if (loadingEl) loadingEl.textContent = '加载失败: ' + result.message;
                }
            } catch (error) {
                console.error('加载工具配置出错:', error);
                if (loadingEl) loadingEl.textContent = '请求失败: ' + error.message;
            }
        }

        // 显示配置
        function displayConfig(config) {
            const html = `
                <div class="config-item">
                    <label>ID</label>
                    <span>${config.id}</span>
                </div>
                <div class="config-item">
                    <label>名称</label>
                    <span>${config.name || 'N/A'}</span>
                </div>
                <div class="config-item">
                    <label>类型</label>
                    <span>${config.type || 'N/A'}</span>
                </div>
                <div class="config-item">
                    <label>状态</label>
                    <span class="status-badge ${config.is_active ? 'active' : 'inactive'}">
                        ${config.is_active ? '启用' : '禁用'}
                    </span>
                </div>
                <div class="config-item">
                    <label>API地址</label>
                    <span>${config.api_base_url || 'N/A'}</span>
                </div>
                <div class="config-item">
                    <label>Token</label>
                    <span>${config.has_auth_token ? '✅ 已配置' : '❌ 未配置'}</span>
                </div>
                <div class="config-item">
                    <label>公钥</label>
                    <span>${config.has_public_key ? '✅ 已配置' : '❌ 未配置'}</span>
                </div>
                <div class="config-item">
                    <label>私钥</label>
                    <span>${config.has_private_key ? '✅ 已配置' : '❌ 未配置'}</span>
                </div>
            `;
            document.getElementById('current-config').innerHTML = '<h4>当前配置</h4>' + html;
        }

        // 填充表单
        function fillForm(config) {
            console.log('填充表单，配置数据:', config);

            const nameEl = document.getElementById('name');
            const platformIssuerEl = document.getElementById('platform_issuer');
            const clientIdEl = document.getElementById('client_id');
            const apiBaseUrlEl = document.getElementById('api_base_url');
            const isActiveEl = document.getElementById('is_active');

            if (nameEl) nameEl.value = config.name || '';
            if (platformIssuerEl) platformIssuerEl.value = config.platform_issuer || '';
            if (clientIdEl) clientIdEl.value = config.client_id || '';
            if (apiBaseUrlEl) apiBaseUrlEl.value = config.api_base_url || '';
            if (isActiveEl) isActiveEl.checked = Boolean(config.is_active);

            console.log('表单填充完成');
        }

        // 保存配置
        document.getElementById('config-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const toolId = document.getElementById('config_tool_id').value;
            if (!toolId) {
                showAlert('error', '请先选择工具');
                return;
            }

            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (key === 'is_active') {
                    data[key] = true;
                } else if (key === 'tool_id') {
                    // 跳过隐藏字段
                    return;
                } else if (value) {
                    data[key] = value;
                }
            });
            data.is_active = document.getElementById('is_active').checked;

            try {
                const response = await fetch('/admin/api/tools/' + toolId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', '配置保存成功！');
                    loadToolConfig(toolId);
                    loadToolSelector(); // 刷新选择器（名称可能已更改）
                } else {
                    showAlert('error', '保存失败: ' + result.message);
                }
            } catch (error) {
                showAlert('error', '请求失败: ' + error.message);
            }
        });

        // 测试连接
        async function testConnection() {
            const toolId = document.getElementById('config_tool_id').value;
            if (!toolId) {
                showAlert('error', '请先选择工具');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> 测试中...';

            try {
                const response = await fetch('/admin/api/tools/' + toolId + '/test-connection', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success && result.data.connected) {
                    showAlert('success', '✅ 连接成功！' + result.data.message);
                } else {
                    showAlert('error', '❌ 连接失败: ' + result.data.message);
                }
            } catch (error) {
                showAlert('error', '请求失败: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔌 测试连接';
            }
        }

        // 显示提示
        function showAlert(type, message) {
            const container = document.querySelector('.content-card');
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            container.insertBefore(alert, container.firstChild);

            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // 页面加载时获取状态
        document.addEventListener('DOMContentLoaded', function() {
            refreshStatus();
            loadToolsList();
        });

        // ========== 工具管理功能 ==========

        // 加载工具列表
        async function loadToolsList() {
            try {
                const response = await fetch('/admin/api/tools');
                const result = await response.json();

                if (result.success) {
                    displayToolsList(result.data);
                } else {
                    document.getElementById('tools-list').innerHTML = '<p>加载失败: ' + result.message + '</p>';
                }
            } catch (error) {
                document.getElementById('tools-list').innerHTML = '<p>请求失败: ' + error.message + '</p>';
            }
        }

        // 显示工具列表
        function displayToolsList(tools) {
            if (tools.length === 0) {
                document.getElementById('tools-list').innerHTML = '<p>暂无工具配置</p>';
                return;
            }

            const html = tools.map(tool => `
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0;">${tool.name || '未命名工具'}</h4>
                        <span class="status-badge ${tool.is_active ? 'active' : 'inactive'}">
                            ${tool.is_active ? '启用' : '禁用'}
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 0.9rem; color: #666;">
                        <div>ID: ${tool.id}</div>
                        <div>类型: ${tool.type?.toUpperCase() || 'N/A'}</div>
                        <div>API: ${tool.api_base_url || 'N/A'}</div>
                        <div>Issuer: ${tool.platform_issuer || 'N/A'}</div>
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="/lti/launch/${tool.id}" class="btn btn-primary" style="text-decoration: none; font-size: 0.9rem; padding: 8px 16px;">
                            🚀 启动链接
                        </a>
                        <button class="btn btn-secondary" style="font-size: 0.9rem; padding: 8px 16px;" onclick="editTool(${tool.id})">
                            ✏️ 编辑
                        </button>
                        <button class="btn btn-success" style="font-size: 0.9rem; padding: 8px 16px;" onclick="testToolConnection(${tool.id})">
                            🔌 测试
                        </button>
                        <button class="btn ${tool.is_active ? 'btn-warning' : 'btn-info'}" style="font-size: 0.9rem; padding: 8px 16px;" onclick="toggleToolStatus(${tool.id}, ${tool.is_active})">
                            ${tool.is_active ? '⏸️ 停用' : '▶️ 启用'}
                        </button>
                        <button class="btn btn-danger" style="font-size: 0.9rem; padding: 8px 16px;" onclick="deleteTool(${tool.id}, '${tool.name}')">
                            🗑️ 删除
                        </button>
                    </div>
                </div>
            `).join('');

            document.getElementById('tools-list').innerHTML = html;
        }

        // 显示添加工具弹窗
        function showAddToolModal() {
            document.getElementById('add-tool-modal').style.display = 'flex';
        }

        // 隐藏添加工具弹窗
        function hideAddToolModal() {
            document.getElementById('add-tool-modal').style.display = 'none';
            document.getElementById('add-tool-form').reset();
        }

        // 添加新工具
        document.getElementById('add-tool-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            // 如果没有填写JWKS URL，自动生成
            if (!data.jwks_url) {
                data.jwks_url = window.location.origin + '/lti/jwks/' + Date.now();
            }

            try {
                const response = await fetch('/admin/api/tools', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', '工具创建成功！ID: ' + result.data.id);
                    hideAddToolModal();
                    loadToolsList();
                } else {
                    showAlert('error', '创建失败: ' + result.message);
                }
            } catch (error) {
                showAlert('error', '请求失败: ' + error.message);
            }
        });

        // 编辑工具（切换到配置编辑标签页）
        function editTool(toolId) {
            switchTab('config');
            // 这里可以加载指定工具的配置
            showAlert('info', '请在配置编辑页面选择工具 ID: ' + toolId);
        }

        // 测试指定工具的连接
        async function testToolConnection(toolId) {
            try {
                const response = await fetch('/admin/api/tools/' + toolId + '/test-connection', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success && result.data.connected) {
                    showAlert('success', '✅ 工具 ' + toolId + ' 连接成功！');
                } else {
                    showAlert('error', '❌ 工具 ' + toolId + ' 连接失败: ' + result.data.message);
                }
            } catch (error) {
                showAlert('error', '请求失败: ' + error.message);
            }
        }

        // 切换工具状态（启用/停用）
        async function toggleToolStatus(toolId, currentStatus) {
            const actionText = currentStatus ? '停用' : '启用';

            if (!confirm(`确定要${actionText}工具 ${toolId} 吗？`)) {
                return;
            }

            try {
                const response = await fetch('/admin/api/tools/' + toolId + '/toggle-status', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    loadToolsList(); // 刷新列表
                } else {
                    showAlert('error', '操作失败: ' + result.message);
                }
            } catch (error) {
                showAlert('error', '请求失败: ' + error.message);
            }
        }

        // 删除工具
        async function deleteTool(toolId, toolName) {
            if (!confirm(`确定要删除工具 "${toolName}" (ID: ${toolId}) 吗？\n\n⚠️ 此操作不可恢复！`)) {
                return;
            }

            try {
                const response = await fetch('/admin/api/tools/' + toolId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', `工具 "${result.data.name}" 已删除`);
                    loadToolsList(); // 刷新列表
                } else {
                    showAlert('error', '删除失败: ' + result.message);
                }
            } catch (error) {
                showAlert('error', '请求失败: ' + error.message);
            }
        }

        // 点击弹窗外部关闭
        document.getElementById('add-tool-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideAddToolModal();
            }
        });
    </script>
</body>
</html>
