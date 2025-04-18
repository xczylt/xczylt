<?php
$mirrorContent = '';
$errorMessage = '';
$inputUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUrl = trim($_POST['url']);
    $urlInfo = parse_url($inputUrl);

    // 1. URL格式验证
    if (!isset($urlInfo['scheme']) || !in_array($urlInfo['scheme'], ['http', 'https'])) {
        $errorMessage = '请输入有效的HTTP/HTTPS网址（例如：https://example.com）';
    } else {
        // 2. cURL获取远程内容
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $inputUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 20, // 适应移动端网络延迟
            CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile/15E148 Safari/604.1', // 移动端UA
            CURLOPT_SSL_VERIFYPEER => false, // 生产环境需启用验证
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode >= 400) {
            $errorMessage = "获取内容失败（状态码：{$httpCode}）";
        } else {
            // 3. 字符编码转换（解决乱码）
            $encoding = 'UTF-8';
            if (preg_match('/charset=([a-zA-Z0-9\-]+)/i', $content, $match)) {
                $encoding = strtoupper($match[1]);
            }
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', $encoding);
            $content = mb_convert_encoding($content, 'UTF-8', 'HTML-ENTITIES');

            // 4. 构建基础URL（包含路径）
            $baseUrl = $urlInfo['scheme'] . '://' . $urlInfo['host'];
            if (isset($urlInfo['port'])) $baseUrl .= ':' . $urlInfo['port'];
            $basePath = isset($urlInfo['path']) ? dirname($urlInfo['path']) . '/' : '/';
            $baseUrl .= rtrim($basePath, '/') . '/';

            // 5. 解析HTML并修复资源路径
            $dom = new DOMDocument();
            libxml_use_internal_errors(true); // 抑制解析警告
            $dom->loadHTML($content);
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);
            $elements = [
                ['tag' => 'a', 'attr' => 'href'],
                ['tag' => 'img', 'attr' => 'src'],
                ['tag' => 'link', 'attr' => 'href'],
                ['tag' => 'script', 'attr' => 'src'],
                ['tag' => 'iframe', 'attr' => 'src'],
                ['tag' => 'form', 'attr' => 'action'],
                ['tag' => 'img', 'attr' => 'srcset'], // 响应式图片
                ['tag' => 'source', 'attr' => 'src'], // 多媒体资源
            ];

            foreach ($elements as $el) {
                $nodes = $xpath->query("//{$el['tag']}[@{$el['attr']}]");
                foreach ($nodes as $node) {
                    $attr = $node->getAttribute($el['attr']);
                    if (empty($attr) || strpos($attr, '://') !== false || $attr[0] === '#') continue;
                    $absUrl = $baseUrl . ltrim($attr, '/');
                    $node->setAttribute($el['attr'], $absUrl);
                }
            }

            $mirrorContent = $dom->saveHTML();
        }
    }
}

// URL处理辅助类
class Uri {
    private $url;
    public function __construct($url) {
        $this->url = filter_var($url, FILTER_SANITIZE_URL);
    }
    public function getUrl() {
        return $this->url;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>全能网页镜像工具</title>
    <!-- 移动端核心适配 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#1a73e8">

    <style>
        /* 全局样式 */
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 640px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        /* 表单区域 */
        .form-box {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        input[type="url"] {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        button {
            width: 100%;
            padding: 18px;
            font-size: 18px;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        button:hover {
            transform: scale(1.02);
        }

        .error {
            color: #dc2626;
            margin-top: 15px;
            font-size: 15px;
            line-height: 1.4;
        }

        /* 镜像内容区域 */
        .mirror-wrap {
            flex: 1;
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .mirror-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }

        /* 内容适配规则 */
        .mirror-content * {
            max-width: 100% !important;
            height: auto !important;
            word-wrap: break-word !important;
            box-sizing: border-box !important;
            display: block !important;
            margin: 10px auto !important;
        }

        .mirror-content img,
        .mirror-content video,
        .mirror-content iframe {
            border-radius: 12px;
            max-height: 80vh;
            object-fit: contain;
        }

        /* 移动端专属优化 */
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            input[type="url"], button {
                font-size: 14px;
                padding: 14px;
            }
            
            .mirror-wrap {
                padding: 18px;
                border-radius: 10px;
            }
        }

        /* 暗黑模式适配 */
        @media (prefers-color-scheme: dark) {
            body { background-color: #1a1a1a; color: white; }
            .form-box, .mirror-wrap { background-color: #333; border-color: #444; }
            button { background-color: #3b82f6; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <form method="post">
                <input type="url" name="url" 
                       placeholder="请输入完整网址（例如：https://www.baidu.com）" 
                       value="<?= htmlspecialchars($inputUrl) ?>" 
                       required>
                <button type="submit">开始镜像网页</button>
            </form>

            <?php if ($errorMessage): ?>
                <div class="error"><?= $errorMessage ?></div>
            <?php endif; ?>
        </div>

        <?php if ($mirrorContent): ?>
            <div class="mirror-wrap">
                <h2 class="mirror-title">镜像网页内容</h2>
                <div class="mirror-content"><?= $mirrorContent ?></div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
