<?php
session_start();

// 获取请求参数
$type = isset($_GET['type']) ? $_GET['type'] : 'text';

// 读取文件
$file = 'data/saying.json';
if (!file_exists($file)) {
    echo 'File not found.';
    exit;
}

// 读取文件内容并选择随机一行
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$lines) {
    echo '好像出错了:P';
    exit;
}

// 初始化会话中的历史记录
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = array();
}

// 确保历史记录中没有重复的内容
$remainingLines = array_diff($lines, $_SESSION['history']);
if (empty($remainingLines)) {
    echo 'All sayings have been shown.';
    exit;
}

// 选择随机一行
$randomLine = $remainingLines[array_rand($remainingLines)];

// 更新会话历史记录
$_SESSION['history'][] = $randomLine;
if (count($_SESSION['history']) > 100) {
    // 如果历史记录超过100条，移除最早的一条
    array_shift($_SESSION['history']);
}

// 输出格式
if ($type === 'json') {
    header('Content-Type: application/json');
    echo json_encode(array('code' => '200', 'saying' => $randomLine), JSON_UNESCAPED_UNICODE);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $randomLine;
}
?>
