<?php
// 保持PHP逻辑不变（同改进版代码）
// 主要优化HTML和CSS部分
?>

<!DOCTYPE html>
<html>
<head>
    <title>网址镜像工具</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"> <!-- 移动端适配关键 -->
    <style>
        /* 基础样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent; /* 消除点击高亮 */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 600px; /* 限制最大宽度，避免大屏拉伸 */
            margin: 0 auto;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* 表单样式（移动端友好） */
        .form-group {
            margin-bottom: 15px;
        }

        input[type="url"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background-color: white;
        }

        button {
            width: 100%;
            padding: 14px;
            font-size: 18px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #1976D2;
        }

        .error {
            color: #ff4444;
            margin: 10px 0 20px;
            font-size: 16px;
            line-height: 1.4;
        }

        /* 镜像内容容器（核心适配部分） */
        .mirror-container {
            margin-top: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* 强制镜像内容适配手机屏幕 */
        .mirror-container * {
            max-width: 100% !important; /* 防止图片/视频溢出 */
            height: auto !important;
            box-sizing: border-box !important;
            word-wrap: break-word !important; /* 长单词自动换行 */
        }

        .mirror-container img,
        .mirror-container video,
        .mirror-container iframe {
            display: block;
            margin: 15px 0;
            border-radius: 8px;
            max-height: 60vh; /* 限制最大高度，避免长视频撑大页面 */
            object-fit: contain; /* 保持比例 */
        }

        /* 链接和按钮触摸优化 */
        .mirror-container a,
        .mirror-container button {
            min-width: 44px;
            min-height: 44px; /* 符合iOS人机交互规范 */
            padding: 12px;
        }

        /* 媒体查询（进一步优化小屏幕） */
        @media (max-width: 480px) {
            h1 {
                font-size: 20px;
            }

            .mirror-container {
                padding: 15px;
            }

            .mirror-container h2 {
                font-size: 18px;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>网址镜像工具</h1>
        
        <form method="post">
            <div class="form-group">
                <input type="url" name="url" 
                       placeholder="请输入网址（例如：https://example.com）" 
                       value="<?= htmlspecialchars($inputUrl) ?>" 
                       required>
            </div>
            <button type="submit">开始镜像</button>
        </form>

        <?php if ($errorMessage): ?>
            <div class="error"><?= $errorMessage ?></div>
        <?php endif; ?>

        <?php if ($mirrorContent): ?>
            <div class="mirror-container">
                <h2>镜像内容</h2>
                <?= $mirrorContent ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
