<?php

declare(strict_types=1);

$baseUrl = getenv('PLATFORM_BASE_URL') ?: 'http://127.0.0.1:8088';
$clientId = getenv('PLATFORM_CLIENT_ID') ?: 'client_demo_002';
$clientSecret = getenv('PLATFORM_CLIENT_SECRET') ?: 'secret_encrypted_demo_002';
$defaultSceneId = (string) ($_GET['sceneId'] ?? getenv('ALIYUN_FACE_SCENE_ID') ?: '1000015632');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'proxy') {
    header('Content-Type: application/json; charset=utf-8');

    $rawBody = file_get_contents('php://input');
    $request = json_decode($rawBody ?: '{}', true);
    if (!is_array($request)) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'msg' => '请求体不是合法 JSON'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $path = (string) ($request['path'] ?? '');
    $payload = is_array($request['payload'] ?? null) ? $request['payload'] : [];
    if ($path === '') {
        http_response_code(400);
        echo json_encode(['code' => 400, 'msg' => 'path 不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    [$signature, $signRaw, $traceId, $timestamp] = buildSignature($clientId, $clientSecret, $payload);
    $result = doPlatformRequest($baseUrl, $clientId, $signature, $traceId, $timestamp, $path, $payload);

    echo json_encode([
        'code' => 0,
        'msg' => 'success',
        'debug' => [
            'traceId' => $traceId,
            'timestamp' => $timestamp,
            'signRaw' => $signRaw,
            'path' => $path,
        ],
        'result' => $result,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function flattenParams(array $data, string $prefix = ''): array
{
    $result = [];
    foreach ($data as $key => $value) {
        $name = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if ($value === null || $value === '') {
            continue;
        }
        if (is_array($value)) {
            $result += flattenParams($value, $name);
            continue;
        }
        if (is_bool($value)) {
            $result[$name] = $value ? 'true' : 'false';
            continue;
        }
        $result[$name] = (string) $value;
    }
    return $result;
}

function buildSignature(string $clientId, string $clientSecret, array $body): array
{
    $traceId = uniqid('face_', true);
    $timestamp = time();
    $paramsForSign = [
        'x-client-id' => $clientId,
        'x-timestamp' => (string) $timestamp,
        'x-trace-id' => $traceId,
    ];
    $paramsForSign += flattenParams($body);
    ksort($paramsForSign);

    $parts = [];
    foreach ($paramsForSign as $key => $value) {
        $parts[] = $key . '=' . $value;
    }

    $raw = implode('&', $parts);
    $signature = hash_hmac('sha256', $raw, $clientSecret);
    return [$signature, $raw, $traceId, $timestamp];
}

function doPlatformRequest(
    string $baseUrl,
    string $clientId,
    string $signature,
    string $traceId,
    int $timestamp,
    string $path,
    array $payload
): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($baseUrl, '/') . $path,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Client-Id: ' . $clientId,
            'X-Timestamp: ' . $timestamp,
            'X-Trace-Id: ' . $traceId,
            'X-Sign: ' . $signature,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $decoded = null;
    if (is_string($response) && $response !== '') {
        $decoded = json_decode($response, true);
    }

    return [
        'httpCode' => $httpCode,
        'errno' => $errno,
        'error' => $error,
        'response' => $decoded ?? $response,
    ];
}

$selfBaseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1')
    . strtok($_SERVER['REQUEST_URI'] ?? '/face.php', '?');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#cf6a3b">
    <title>Aliyun Face H5 Demo</title>
    <style>
        :root {
            --bg: #f7efe0;
            --panel: rgba(255, 252, 247, 0.94);
            --line: #decdb3;
            --text: #2f271d;
            --muted: #776b5c;
            --accent: #cf6a3b;
            --accent-2: #2c8d72;
            --warn: #bd5d2a;
            --shadow: 0 18px 36px rgba(92, 63, 24, 0.12);
            --vh: 1vh;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "PingFang SC", "Microsoft YaHei", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(207, 106, 59, 0.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(44, 141, 114, 0.12), transparent 30%),
                linear-gradient(135deg, #f0e6d3, #fbf7ef 58%, #efe0c3);
            min-height: calc(var(--vh) * 100);
        }

        .shell {
            width: min(1180px, calc(100vw - 28px));
            margin: 18px auto;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 18px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid rgba(222, 205, 179, 0.88);
            border-radius: 24px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
        }

        .hero, .side {
            padding: 22px;
        }

        .hero-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: clamp(28px, 3vw, 40px);
            line-height: 1.05;
        }

        .subtitle {
            margin: 0;
            line-height: 1.7;
            color: var(--muted);
        }

        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .chip {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.82);
            border: 1px solid rgba(222, 205, 179, 0.96);
            font-size: 12px;
            color: var(--muted);
        }

        .notice, .flow-card, .meta-card {
            border-radius: 18px;
            padding: 16px 18px;
            margin-top: 16px;
            border: 1px solid rgba(222, 205, 179, 0.86);
        }

        .notice {
            background: rgba(207, 106, 59, 0.08);
            color: #95502c;
            line-height: 1.65;
        }

        .flow-card {
            background: rgba(255,255,255,0.68);
        }

        .flow-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .flow-step {
            border-radius: 16px;
            padding: 14px;
            background: rgba(246, 238, 223, 0.9);
            border: 1px solid rgba(222, 205, 179, 0.9);
        }

        .flow-step strong {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .flow-step span {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .meta-card {
            background: linear-gradient(135deg, rgba(44, 141, 114, 0.1), rgba(255,255,255,0.7));
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            color: var(--muted);
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid rgba(222, 205, 179, 0.95);
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 14px;
            background: rgba(255,255,255,0.92);
            color: var(--text);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
            line-height: 1.6;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .action-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .action-row.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        button {
            border: 0;
            border-radius: 16px;
            min-height: 48px;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.16s ease, opacity 0.16s ease;
        }

        button:hover {
            transform: translateY(-1px);
        }

        button.primary {
            color: #fff;
            background: linear-gradient(135deg, var(--accent), #e08454);
        }

        button.secondary {
            color: var(--text);
            background: rgba(255,255,255,0.88);
            border: 1px solid rgba(222, 205, 179, 0.96);
        }

        button.ghost {
            color: var(--accent-2);
            background: rgba(44, 141, 114, 0.08);
            border: 1px solid rgba(44, 141, 114, 0.22);
        }

        button.warn {
            color: #fff;
            background: linear-gradient(135deg, var(--warn), #d67a48);
        }

        .status-board {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .status-box {
            padding: 14px;
            border-radius: 16px;
            border: 1px solid rgba(222, 205, 179, 0.9);
            background: rgba(255,255,255,0.7);
        }

        .status-box b {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
        }

        .status-box span {
            font-size: 15px;
            font-weight: 700;
        }

        .tip {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.7;
            margin: 14px 0 0;
        }

        .result-panel {
            margin-top: 16px;
            border-radius: 18px;
            background: rgba(36, 31, 25, 0.95);
            color: #f8f2e9;
            padding: 16px;
            min-height: 320px;
            overflow: auto;
        }

        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            line-height: 1.55;
        }

        .mobile-bar {
            position: sticky;
            bottom: 12px;
            z-index: 40;
            display: none;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
            padding: 12px;
            border-radius: 20px;
            border: 1px solid rgba(222, 205, 179, 0.9);
            background: rgba(255, 252, 247, 0.96);
            box-shadow: 0 16px 28px rgba(92, 63, 24, 0.16);
        }

        @media (max-width: 980px) {
            .shell {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            body {
                padding-bottom: calc(112px + env(safe-area-inset-bottom, 0px));
            }

            .shell {
                width: min(100vw - 14px, 100%);
                margin: 8px auto 18px;
                gap: 12px;
            }

            .hero, .side {
                padding: 16px;
            }

            .hero-head {
                flex-direction: column;
            }

            .chip-row {
                justify-content: flex-start;
            }

            .field-grid,
            .action-row,
            .action-row.three,
            .status-board,
            .flow-grid,
            .mobile-bar {
                grid-template-columns: 1fr;
            }

            .action-row.three.desktop {
                display: none;
            }

            .mobile-bar {
                display: grid;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="panel hero">
        <div class="hero-head">
            <div>
                <h1>Aliyun Face H5 Demo</h1>
                <p class="subtitle">
                    这个页面走阿里云官方“初始化认证 + H5 刷脸 + 查询结果”流程，不再自己抓拍上传图片。
                    适合桌面端和手机端联调正式接入链路。
                </p>
            </div>
            <div class="chip-row">
                <span class="chip" id="deviceBadge">设备检测中</span>
                <span class="chip">Base URL: <?= htmlspecialchars($baseUrl, ENT_QUOTES) ?></span>
                <span class="chip">Client: <?= htmlspecialchars($clientId, ENT_QUOTES) ?></span>
            </div>
        </div>

        <div class="notice">
            官方 H5 流程会先用前端 SDK 获取 <code>MetaInfo</code>，再由当前页面代理签名调用
            <code>/platform-api/verify/face/init</code>，拿到 <code>certifyUrl</code> 后进入阿里云刷脸页，
            最后再通过 <code>/platform-api/verify/face/result</code> 查询结果。
        </div>

        <div class="flow-card">
            <strong>联调流程</strong>
            <div class="flow-grid">
                <div class="flow-step">
                    <strong>1. 获取 MetaInfo</strong>
                    <span>浏览器通过官方 JS 获取设备环境信息，供服务端初始化认证使用。</span>
                </div>
                <div class="flow-step">
                    <strong>2. 初始化认证</strong>
                    <span>服务端调用阿里云 <code>InitFaceVerify</code>，拿到 <code>certifyId</code> 和 <code>certifyUrl</code>。</span>
                </div>
                <div class="flow-step">
                    <strong>3. 刷脸并查结果</strong>
                    <span>浏览器跳转到阿里云刷脸页，完成后回到当前页，再调用结果查询接口。</span>
                </div>
            </div>
        </div>

        <div class="meta-card">
            <div class="field-grid">
                <div class="field full">
                    <label for="metaInfo">MetaInfo</label>
                    <textarea id="metaInfo" placeholder="点击“获取 MetaInfo”后自动填充"></textarea>
                </div>
                <div class="field">
                    <label for="certifyId">CertifyId</label>
                    <input id="certifyId" placeholder="初始化成功后自动回填">
                </div>
                <div class="field">
                    <label for="certifyUrl">CertifyUrl</label>
                    <input id="certifyUrl" placeholder="初始化成功后自动回填">
                </div>
            </div>
            <div class="action-row three desktop">
                <button class="ghost" id="metaBtn">获取 MetaInfo</button>
                <button class="primary" id="initBtn">初始化认证</button>
                <button class="secondary" id="openBtn">打开刷脸页</button>
            </div>
        </div>

        <div class="status-board">
            <div class="status-box">
                <b>MetaInfo 状态</b>
                <span id="metaStatus">未获取</span>
            </div>
            <div class="status-box">
                <b>初始化状态</b>
                <span id="initStatus">未开始</span>
            </div>
            <div class="status-box">
                <b>结果状态</b>
                <span id="resultStatus">未查询</span>
            </div>
        </div>

        <p class="tip">
            如果浏览器没能成功加载阿里云 JS，页面仍然支持手工粘贴 <code>MetaInfo</code> 后继续初始化。
            手机端建议直接在可访问当前 PHP 服务的浏览器中打开本页。
        </p>
    </section>

    <aside class="panel side">
        <div class="field-grid">
            <div class="field">
                <label for="sceneId">SceneId</label>
                <input id="sceneId" value="<?= htmlspecialchars($defaultSceneId, ENT_QUOTES) ?>">
            </div>
            <div class="field">
                <label for="productCode">ProductCode</label>
                <input id="productCode" value="ID_PRO">
            </div>
            <div class="field">
                <label for="outerOrderNo">OuterOrderNo</label>
                <input id="outerOrderNo" value="face-h5-<?= date('YmdHis') ?>">
            </div>
            <div class="field">
                <label for="userId">UserId</label>
                <input id="userId" value="face-demo-user-001">
            </div>
            <div class="field">
                <label for="certType">CertType</label>
                <input id="certType" value="IDENTITY_CARD">
            </div>
            <div class="field">
                <label for="mobile">Mobile</label>
                <input id="mobile" placeholder="可选">
            </div>
            <div class="field">
                <label for="certName">CertName</label>
                <input id="certName" value="张三">
            </div>
            <div class="field">
                <label for="certNo">CertNo</label>
                <input id="certNo" value="110101199001011234">
            </div>
            <div class="field">
                <label for="mode">Mode</label>
                <input id="mode" placeholder="可选，例如 NORMAL">
            </div>
            <div class="field">
                <label for="model">Model</label>
                <input id="model" placeholder="可选，例如 LIVENESS">
            </div>
            <div class="field">
                <label for="cameraSelection">CameraSelection</label>
                <input id="cameraSelection" placeholder="可选，例如 FRONT">
            </div>
            <div class="field">
                <label for="certifyUrlType">CertifyUrlType</label>
                <input id="certifyUrlType" value="H5" placeholder="可选，H5 或 WEB">
            </div>
            <div class="field">
                <label for="certifyUrlStyle">CertifyUrlStyle</label>
                <input id="certifyUrlStyle" value="S" placeholder="可选，S 或 L">
            </div>
            <div class="field full">
                <label for="returnUrl">ReturnUrl</label>
                <input id="returnUrl" value="<?= htmlspecialchars($selfBaseUrl, ENT_QUOTES) ?>">
            </div>
        </div>

        <div class="action-row">
            <button class="primary" id="resultBtn">查询结果</button>
            <button class="warn" id="clearBtn">清空缓存</button>
        </div>

        <div class="result-panel">
            <pre id="resultText">等待操作...</pre>
        </div>
    </aside>
</div>

<div class="mobile-bar">
    <button class="ghost" id="mobileMetaBtn">获取 MetaInfo</button>
    <button class="primary" id="mobileInitBtn">初始化</button>
    <button class="secondary" id="mobileOpenBtn">去刷脸</button>
    <button class="secondary" id="mobileResultBtn">查结果</button>
</div>

<script src="https://o.alicdn.com/yd-cloudauth/cloudauth-cdn/jsvm_all.js"></script>
<script>
    const fields = {
        sceneId: document.getElementById('sceneId'),
        productCode: document.getElementById('productCode'),
        outerOrderNo: document.getElementById('outerOrderNo'),
        userId: document.getElementById('userId'),
        certType: document.getElementById('certType'),
        mobile: document.getElementById('mobile'),
        certName: document.getElementById('certName'),
        certNo: document.getElementById('certNo'),
        mode: document.getElementById('mode'),
        model: document.getElementById('model'),
        cameraSelection: document.getElementById('cameraSelection'),
        certifyUrlType: document.getElementById('certifyUrlType'),
        certifyUrlStyle: document.getElementById('certifyUrlStyle'),
        returnUrl: document.getElementById('returnUrl'),
        metaInfo: document.getElementById('metaInfo'),
        certifyId: document.getElementById('certifyId'),
        certifyUrl: document.getElementById('certifyUrl'),
    };

    const resultText = document.getElementById('resultText');
    const metaStatus = document.getElementById('metaStatus');
    const initStatus = document.getElementById('initStatus');
    const resultStatus = document.getElementById('resultStatus');
    const deviceBadge = document.getElementById('deviceBadge');

    const storageKey = 'neton_face_h5_session';
    const isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

    function setResult(data) {
        resultText.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    }

    function saveSession() {
        const payload = {
            sceneId: fields.sceneId.value.trim(),
            productCode: fields.productCode.value.trim(),
            outerOrderNo: fields.outerOrderNo.value.trim(),
            userId: fields.userId.value.trim(),
            certType: fields.certType.value.trim(),
            mobile: fields.mobile.value.trim(),
            certName: fields.certName.value.trim(),
            certNo: fields.certNo.value.trim(),
            mode: fields.mode.value.trim(),
            model: fields.model.value.trim(),
            cameraSelection: fields.cameraSelection.value.trim(),
            certifyUrlType: fields.certifyUrlType.value.trim(),
            certifyUrlStyle: fields.certifyUrlStyle.value.trim(),
            returnUrl: fields.returnUrl.value.trim(),
            metaInfo: fields.metaInfo.value.trim(),
            certifyId: fields.certifyId.value.trim(),
            certifyUrl: fields.certifyUrl.value.trim(),
        };
        localStorage.setItem(storageKey, JSON.stringify(payload));
    }

    function restoreSession() {
        const raw = localStorage.getItem(storageKey);
        if (!raw) {
            return;
        }
        try {
            const payload = JSON.parse(raw);
            Object.entries(payload).forEach(([key, value]) => {
                if (fields[key] && typeof value === 'string' && !fields[key].value) {
                    fields[key].value = value;
                }
            });
        } catch (error) {
            console.warn('restore session failed', error);
        }
    }

    function currentReturnUrl() {
        const url = new URL(window.location.href);
        url.searchParams.delete('response');
        url.searchParams.delete('mode');
        url.searchParams.set('sceneId', fields.sceneId.value.trim());
        return url.toString();
    }

    function buildInitPayload() {
        fields.returnUrl.value = currentReturnUrl();
        const certifyUrlType = fields.certifyUrlType.value.trim().toUpperCase();
        const certifyUrlStyle = fields.certifyUrlStyle.value.trim().toUpperCase();
        const payload = {
            sceneId: Number(fields.sceneId.value),
            outerOrderNo: fields.outerOrderNo.value.trim(),
            productCode: fields.productCode.value.trim(),
            metaInfo: fields.metaInfo.value.trim(),
            certType: fields.certType.value.trim(),
            certName: fields.certName.value.trim(),
            certNo: fields.certNo.value.trim(),
            userId: fields.userId.value.trim(),
            mobile: fields.mobile.value.trim(),
            returnUrl: fields.returnUrl.value.trim(),
            mode: fields.mode.value.trim(),
            model: fields.model.value.trim(),
            cameraSelection: fields.cameraSelection.value.trim(),
            certifyUrlType: ['H5', 'WEB'].includes(certifyUrlType) ? certifyUrlType : '',
            certifyUrlStyle: ['S', 'L'].includes(certifyUrlStyle) ? certifyUrlStyle : '',
        };
        Object.keys(payload).forEach((key) => {
            if (payload[key] === '' || payload[key] === null) {
                delete payload[key];
            }
        });
        return payload;
    }

    function buildResultPayload() {
        const payload = {
            sceneId: Number(fields.sceneId.value),
            certifyId: fields.certifyId.value.trim(),
        };
        Object.keys(payload).forEach((key) => {
            if (payload[key] === '' || payload[key] === null) {
                delete payload[key];
            }
        });
        return payload;
    }

    async function proxyRequest(path, payload) {
        const response = await fetch(`${window.location.pathname}?action=proxy`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ path, payload }),
        });
        return response.json();
    }

    async function getMetaInfoValue() {
        if (typeof window.getMetaInfo !== 'function') {
            throw new Error('阿里云 getMetaInfo 未加载成功，请检查网络或手工粘贴 MetaInfo');
        }
        const maybePromise = window.getMetaInfo();
        const metaInfo = typeof maybePromise?.then === 'function' ? await maybePromise : maybePromise;
        if (!metaInfo) {
            throw new Error('获取 MetaInfo 为空');
        }
        fields.metaInfo.value = typeof metaInfo === 'string' ? metaInfo : JSON.stringify(metaInfo);
        metaStatus.textContent = '已获取';
        saveSession();
        return fields.metaInfo.value;
    }

    async function initVerify() {
        if (!fields.metaInfo.value.trim()) {
            await getMetaInfoValue();
        }
        initStatus.textContent = '初始化中';
        setResult('初始化认证中...');
        const data = await proxyRequest('/platform-api/verify/face/init', buildInitPayload());
        const result = data?.result?.response?.data || {};
        if (result.certifyId) {
            fields.certifyId.value = result.certifyId;
        }
        if (result.certifyUrl) {
            fields.certifyUrl.value = result.certifyUrl;
        }
        initStatus.textContent = result.certifyUrl ? '初始化成功' : '初始化完成';
        saveSession();
        setResult(data);
        return data;
    }

    function openCertifyUrl() {
        const certifyUrl = fields.certifyUrl.value.trim();
        if (!certifyUrl) {
            throw new Error('还没有 certifyUrl，请先初始化认证');
        }
        window.location.href = certifyUrl;
    }

    async function queryResult() {
        if (!fields.certifyId.value.trim()) {
            throw new Error('还没有 certifyId，请先初始化认证或从回调里拿到 certifyId');
        }
        resultStatus.textContent = '查询中';
        setResult('查询认证结果中...');
        const data = await proxyRequest('/platform-api/verify/face/result', buildResultPayload());
        resultStatus.textContent = '已查询';
        saveSession();
        setResult(data);
        return data;
    }

    function parseResponseParam() {
        const params = new URLSearchParams(window.location.search);
        const raw = params.get('response');
        if (!raw) {
            return null;
        }

        const candidates = [raw];
        try {
            candidates.push(decodeURIComponent(raw));
        } catch (error) {
            // ignore
        }

        for (const item of candidates) {
            try {
                return JSON.parse(item);
            } catch (error) {
                // continue
            }
        }
        return { raw };
    }

    function applyCallbackResponse() {
        const params = new URLSearchParams(window.location.search);
        const response = parseResponseParam();
        const sceneId = params.get('sceneId');
        if (sceneId && !fields.sceneId.value) {
            fields.sceneId.value = sceneId;
        }
        if (!response) {
            return;
        }

        const certifyId = response?.extInfo?.certifyId || response?.certifyId || response?.raw?.certifyId;
        if (certifyId) {
            fields.certifyId.value = certifyId;
            resultStatus.textContent = '已回跳';
            saveSession();
        }
        setResult({
            callbackResponse: response,
            tip: '检测到阿里云回跳参数，已尝试提取 certifyId，可继续点“查询结果”。',
        });
    }

    function clearSession() {
        localStorage.removeItem(storageKey);
        fields.metaInfo.value = '';
        fields.certifyId.value = '';
        fields.certifyUrl.value = '';
        metaStatus.textContent = '未获取';
        initStatus.textContent = '未开始';
        resultStatus.textContent = '未查询';
        setResult('等待操作...');
    }

    function syncDeviceBadge() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
        deviceBadge.textContent = isMobile
            ? `当前设备: 手机/平板 ${window.innerWidth}x${window.innerHeight}`
            : `当前设备: 桌面端 ${window.innerWidth}x${window.innerHeight}`;
    }

    restoreSession();
    applyCallbackResponse();
    syncDeviceBadge();
    fields.returnUrl.value = currentReturnUrl();
    window.addEventListener('resize', syncDeviceBadge);

    document.getElementById('metaBtn').addEventListener('click', async () => {
        try {
            setResult('正在获取 MetaInfo...');
            await getMetaInfoValue();
            setResult('MetaInfo 获取成功');
        } catch (error) {
            metaStatus.textContent = '获取失败';
            setResult({ error: error.message });
        }
    });

    document.getElementById('initBtn').addEventListener('click', async () => {
        try {
            await initVerify();
        } catch (error) {
            initStatus.textContent = '初始化失败';
            setResult({ error: error.message });
        }
    });

    document.getElementById('openBtn').addEventListener('click', () => {
        try {
            openCertifyUrl();
        } catch (error) {
            setResult({ error: error.message });
        }
    });

    document.getElementById('resultBtn').addEventListener('click', async () => {
        try {
            await queryResult();
        } catch (error) {
            resultStatus.textContent = '查询失败';
            setResult({ error: error.message });
        }
    });

    document.getElementById('clearBtn').addEventListener('click', clearSession);

    document.getElementById('mobileMetaBtn').addEventListener('click', async () => {
        try {
            setResult('正在获取 MetaInfo...');
            await getMetaInfoValue();
            setResult('MetaInfo 获取成功');
        } catch (error) {
            metaStatus.textContent = '获取失败';
            setResult({ error: error.message });
        }
    });

    document.getElementById('mobileInitBtn').addEventListener('click', async () => {
        try {
            await initVerify();
        } catch (error) {
            initStatus.textContent = '初始化失败';
            setResult({ error: error.message });
        }
    });

    document.getElementById('mobileOpenBtn').addEventListener('click', () => {
        try {
            openCertifyUrl();
        } catch (error) {
            setResult({ error: error.message });
        }
    });

    document.getElementById('mobileResultBtn').addEventListener('click', async () => {
        try {
            await queryResult();
        } catch (error) {
            resultStatus.textContent = '查询失败';
            setResult({ error: error.message });
        }
    });
</script>
</body>
</html>
