<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTI 启动失败</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 16px;
            padding: 48px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: #fee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .error-icon svg { width: 40px; height: 40px; color: #f44336; }
        .error-code {
            display: inline-block;
            background: #f5f5f5;
            color: #666;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            margin-bottom: 16px;
        }
        .error-title { font-size: 24px; font-weight: 600; color: #333; margin-bottom: 12px; }
        .error-message { font-size: 16px; color: #666; line-height: 1.6; margin-bottom: 32px; }
        .error-actions { display: flex; gap: 12px; justify-content: center; }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd6; }
        .btn-secondary { background: #f5f5f5; color: #666; }
        .btn-secondary:hover { background: #e8e8e8; }
        .help-text {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="error-code">错误代码: {{ $code ?? 'ERROR' }}</div>
        <h1 class="error-title">启动失败</h1>
        <p class="error-message">{{ $message ?? '抱歉，无法完成 LTI 工具启动。' }}</p>
        <div class="error-actions">
            <button onclick="window.location.reload()" class="btn btn-primary">重试</button>
            <button onclick="window.history.back()" class="btn btn-secondary">返回</button>
        </div>
        <div class="help-text">
            如果问题持续存在，请联系系统管理员<br>
            <small>LTI Gateway v1.0</small>
        </div>
    </div>
</body>
</html>
