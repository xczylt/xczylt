<?php
$mirrorContent = '';
$errorMessage = '';
$inputUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUrl = trim($_POST['url']);
    
    // 验证URL格式
    $urlInfo = parse_url($inputUrl);
    if (!isset($urlInfo['scheme']) || !in_array($urlInfo['scheme'], ['http', 'https'])) {
        $errorMessage = '请输入有效的HTTP/HTTPS网址';
    } else {
        // 使用cURL获取远程内容
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $inputUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false, // 忽略SSL验证（生产环境需谨慎）
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode >= 400) {
            $errorMessage = "无法获取网址内容（HTTP状态码：{$httpCode}）";
        } else {
            // 转换相对链接为绝对链接
            $baseUrl = $urlInfo['scheme'] . '://' . $urlInfo['host'] . (isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '');
            $dom = new DOMDocument();
            libxml_use_internal_errors(true); // 抑制HTML解析错误
            $dom->loadHTML($content);
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);
            $elements = [
                ['tag' => 'a', 'attr' => 'href'],
                ['tag' => 'img', 'attr' => 'src'],
                ['tag' => 'link', 'attr' => 'href'],
                ['tag' => 'script', 'attr' => 'src'],
                ['tag' => 'iframe', 'attr' => 'src'],
                ['tag' => 'form', 'attr' => 'action']
            ];

            foreach ($elements as $el) {
                $nodes = $xpath->query("//{$el['tag']}[@{$el['attr']}]");
                foreach ($nodes as $node) {
                    $attrValue = $node->getAttribute($el['attr']);
                    if (!empty($attrValue) && parse_url($attrValue, PHP_URL_SCHEME) === null) {
                        $absoluteUrl = new Uri($baseUrl . '/' . ltrim($attrValue, '/'));
                        $node->setAttribute($el['attr'], $absoluteUrl->getUrl());
                    }
                }
            }

            $mirrorContent = $dom->saveHTML();
        }
    }
}

// 辅助类用于处理URL拼接（需PHP 5.3+）
class Uri {
    private $url;

    public function __construct($url) {
        $this->url = $url;
    }

    public function getUrl() {
        return $this->url;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>网址镜像工具</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        input[type="url"] { width: 100%; padding: 8px; }
        .result { border-top: 1px solid #ccc; padding-top: 20px; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>网址镜像工具</h1>
    
    <form method="post">
        <div class="form-group">
            <input type="url" name="url" placeholder="请输入网址（例如：https://example.com）" value="<?= htmlspecialchars($inputUrl) ?>" required>
        </div>
        <button type="submit">开始镜像</button>
    </form>

    <?php if ($errorMessage): ?>
        <div class="error"><?= $errorMessage ?></div>
    <?php endif; ?>

    <?php if ($mirrorContent): ?>
        <div class="result">
            <h2>镜像内容</h2>
            <?= $mirrorContent ?>
        </div>
    <?php endif; ?>
</body>
</html>
