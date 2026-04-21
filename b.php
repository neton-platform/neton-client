<?php

/*
 * 开放平台联调示例脚本
 *
 * 使用方式：
 * 1. 先确认 8088 服务已重启，并已导入 platform_api 元数据
 * 2. 再确认测试 client 已授权对应 api_code
 * 3. 默认只执行安全查询类接口；短信发送类默认 enabled=false，避免误发
 *
 * 短信接口示例：
 * - 查询余额：/platform-api/message/sms/balance
 * - 查询状态报告：/platform-api/message/sms/report
 * - 查询上行回复：/platform-api/message/sms/reply
 * - 单条发送：把对应 case 的 enabled 改成 true，再改 mobile/content
 * - 批量发送：把对应 case 的 enabled 改成 true，再改 mobiles/content
 *
 * 人脸核验接口示例：
 * - 把同目录 face.jpg 或 FACE_IMAGE_FILE 指向的图片准备好
 * - 活体、独立人证核身、组合认证默认 enabled=false，避免空图片或直接产生计费
 */

$baseUrl = 'http://127.0.0.1:8088';
$clientId = 'client_demo_002';
$clientSecret = 'secret_encrypted_demo_002';
$faceImageFile = getenv('FACE_IMAGE_FILE') ?: __DIR__ . '/face.jpg';
$faceImageBase64 = is_file($faceImageFile) ? base64_encode(file_get_contents($faceImageFile)) : '';

$cases = [
    [
        'name' => '原接口-探针C',
        'path' => '/platform-api/risk/probe/check',
        'body' => [
            'phone' => '13696943074',
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => 1,
        ],
    ],
    [
        'name' => '原接口-三要素',
        'path' => '/platform-api/risk/identity-verification/three-element',
        'body' => [
            'phone' => '13696943074',
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => 1,
        ],
    ],
    [
        'name' => '原接口-全景雷达',
        'path' => '/platform-api/risk/radar/query',
        'body' => [
            'phone' => '13696943074',
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => 1,
        ],
    ],
    [
        'name' => '原接口-全景档案',
        'path' => '/platform-api/risk/loan-overview/query',
        'body' => [
            'phone' => '13696943074',
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => 1,
        ],
    ],
    [
        'name' => '新接口-ZCI001-身份二要素',
        'path' => '/platform-api/risk/identity-verification/two-element',
        'body' => [
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => '1',
        ],
    ],
    [
        'name' => '新接口-ZCI009-探针A',
        'path' => '/platform-api/risk/probe-a/check',
        'body' => [
            'phone' => '13696943074',
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => '1',
        ],
    ],
    [
        'name' => '新接口-ZCI024-全景雷达V2',
        'path' => '/platform-api/risk/radar-v2/query',
        'body' => [
            'phone' => '13696943074',
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => '1',
        ],
    ],
    [
        'name' => '新接口-ZCI061-行为雷达',
        'path' => '/platform-api/risk/behavior-radar/query',
        'body' => [
            'phone' => '13696943074',
            'idCard' => '350721199910042615',
            'name' => '谢信凯',
            'authorized' => '1',
        ],
    ],
    [
        'name' => '短信接口-余额查询',
        'method' => 'GET',
        'path' => '/platform-api/message/sms/balance',
        'body' => [],
        'note' => '示例：查询 8dx 余额，不会产生短信发送',
    ],
    [
        'name' => '短信接口-状态报告查询',
        'method' => 'GET',
        'path' => '/platform-api/message/sms/report',
        'body' => [],
        'note' => '示例：拉取短信状态报告，供应商侧每条状态通常只返回一次',
    ],
    [
        'name' => '短信接口-上行回复查询',
        'method' => 'GET',
        'path' => '/platform-api/message/sms/reply',
        'body' => [],
        'note' => '示例：拉取用户回复短信内容',
    ],
    [
        'name' => '短信接口-单条发送(默认关闭，避免误发)',
        'enabled' => true,
        'path' => '/platform-api/message/sms/send',
        'body' => [
            'mobile' => '17600955560',
            'content' => '【云纪科技】验证码1234',
            'ext' => '123',
        ],
        'note' => '示例：把 enabled 改成 true 后可测试单条短信发送',
    ],
    [
        'name' => '短信接口-批量发送(默认关闭，避免误发)',
        'enabled' => false,
        'path' => '/platform-api/message/sms/batch-send',
        'body' => [
            'mobiles' => '17600000000,17600000001',
            'content' => '【测试】验证码1234',
            'ext' => '123',
        ],
        'note' => '示例：把 enabled 改成 true 后可测试批量短信发送',
    ],
    [
        'name' => '核验接口-活体认证(默认关闭，避免空图片/计费)',
        'enabled' => false,
        'path' => '/platform-api/verify/face/liveness',
        'body' => [
            'sceneId' => 100000001,
            'outerOrderNo' => 'verify-live-' . date('YmdHis'),
            'productCode' => 'LR_FR_MIN',
            'model' => 'FRONT_CAMERA_LIVENESS',
            'faceContrastPicture' => $faceImageBase64,
        ],
        'note' => '需要准备真实人脸图片。可放在 b.php 同目录 face.jpg，或通过 FACE_IMAGE_FILE 指定图片路径',
    ],
    [
        'name' => '核验接口-独立人证核身(默认关闭，避免空图片/计费)',
        'enabled' => false,
        'path' => '/platform-api/verify/face/idcard-compare',
        'body' => [
            'sceneId' => 100000002,
            'outerOrderNo' => 'verify-idcard-' . date('YmdHis'),
            'productCode' => 'ID_MIN',
            'certType' => 'IDENTITY_CARD',
            'certName' => '张三',
            'certNo' => '110101199001011234',
            'model' => 'FRONT_CAMERA_LIVENESS',
            'faceContrastPicture' => $faceImageBase64,
        ],
        'note' => '需要替换成真实姓名、身份证、人脸图片',
    ],
    [
        'name' => '核验接口-活体+独立人证核身(默认关闭，避免空图片/计费)',
        'enabled' => false,
        'path' => '/platform-api/verify/face/liveness-idcard-compare',
        'body' => [
            'livenessSceneId' => 100000001,
            'idcardCompareSceneId' => 100000002,
            'outerOrderNo' => 'verify-combo-' . date('YmdHis'),
            'livenessProductCode' => 'LR_FR_MIN',
            'idcardCompareProductCode' => 'ID_MIN',
            'certType' => 'IDENTITY_CARD',
            'certName' => '张三',
            'certNo' => '110101199001011234',
            'livenessModel' => 'FRONT_CAMERA_LIVENESS',
            'idcardCompareModel' => 'FRONT_CAMERA_LIVENESS',
            'faceContrastPicture' => $faceImageBase64,
        ],
        'note' => '同一张图片分别做活体和独立人证核身，返回两个子结果',
    ],
];

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

function buildSignature(string $clientId, int $timestamp, string $traceId, array $body, string $clientSecret): array
{
    $paramsForSign = [
        'x-client-id' => $clientId,
        'x-timestamp' => (string) $timestamp,
        'x-trace-id' => $traceId,
    ];

    $paramsForSign += flattenParams($body);
    ksort($paramsForSign);

    $signParts = [];
    foreach ($paramsForSign as $key => $value) {
        $signParts[] = $key . '=' . $value;
    }

    $raw = implode('&', $signParts);
    $signature = hash_hmac('sha256', $raw, $clientSecret);

    return [$signature, $raw];
}

function doRequest(string $baseUrl, string $clientId, string $clientSecret, array $case): array
{
    $traceId = uniqid('trace_', true);
    $timestamp = time();
    $method = strtoupper($case['method'] ?? 'POST');
    $body = $case['body'] ?? [];
    [$signature, $raw] = buildSignature($clientId, $timestamp, $traceId, $body, $clientSecret);

    $ch = curl_init();
    $options = [
        CURLOPT_URL => $baseUrl . $case['path'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Client-Id: ' . $clientId,
            'X-Timestamp: ' . $timestamp,
            'X-Trace-Id: ' . $traceId,
            'X-Sign: ' . $signature,
        ],
        CURLOPT_TIMEOUT => 20,
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    } else {
        $options[CURLOPT_CUSTOMREQUEST] = $method;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [
        'traceId' => $traceId,
        'timestamp' => $timestamp,
        'signatureRaw' => $raw,
        'signature' => $signature,
        'httpCode' => $httpCode,
        'errno' => $errno,
        'error' => $error,
        'response' => $response,
    ];
}

foreach ($cases as $case) {
    if (array_key_exists('enabled', $case) && $case['enabled'] === false) {
        echo str_repeat('=', 100) . PHP_EOL;
        echo $case['name'] . PHP_EOL;
        echo 'SKIPPED: enabled=false' . PHP_EOL;
        continue;
    }

    $result = doRequest($baseUrl, $clientId, $clientSecret, $case);

    echo str_repeat('=', 100) . PHP_EOL;
    echo $case['name'] . PHP_EOL;
    if (!empty($case['note'])) {
        echo 'NOTE: ' . $case['note'] . PHP_EOL;
    }
    echo 'METHOD: ' . strtoupper($case['method'] ?? 'POST') . PHP_EOL;
    echo 'PATH: ' . $case['path'] . PHP_EOL;
    echo 'TRACE_ID: ' . $result['traceId'] . PHP_EOL;
    echo 'HTTP_CODE: ' . $result['httpCode'] . PHP_EOL;
    echo 'SIGN_RAW: ' . $result['signatureRaw'] . PHP_EOL;
    echo 'REQUEST_BODY:' . PHP_EOL;
    echo json_encode($case['body'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

    if ($result['errno'] !== 0) {
        echo 'CURL_ERROR: ' . $result['error'] . PHP_EOL;
        continue;
    }

    echo 'RESPONSE_RAW:' . PHP_EOL;
    echo $result['response'] . PHP_EOL;

    $decoded = json_decode($result['response'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo 'RESPONSE_JSON:' . PHP_EOL;
        echo json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    }
}
