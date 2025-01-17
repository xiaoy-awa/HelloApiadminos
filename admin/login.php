<?php
session_start();
$banFile = 'ban.json';
$maxLoginAttempts = 3;
$banDuration = 24 * 60 * 60; // 24 hours in seconds

// 检查IP是否被禁止
function isIPBanned($ip) {
    global $banFile, $banDuration;
    if (file_exists($banFile)) {
        $bannedIPs = json_decode(file_get_contents($banFile), true);
        if (is_array($bannedIPs) && isset($bannedIPs[$ip]) && time() - $bannedIPs[$ip] < $banDuration) {
            return true;
        }
    }
    return false;
}

// 添加IP到黑名单
function banIP($ip) {
    global $banFile;
    $bannedIPs = file_exists($banFile) ? json_decode(file_get_contents($banFile), true) : [];
    if (!is_array($bannedIPs)) {
        $bannedIPs = [];
    }
    $bannedIPs[$ip] = time();
    file_put_contents($banFile, json_encode($bannedIPs));
}

$error = '';
$ip = $_SERVER['REMOTE_ADDR'];

if (isIPBanned($ip)) {
    $error = "您的IP已被禁止访问。请24小时后再试。";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = strtolower($_POST['username']);
    $password = $_POST['password'];

    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        setcookie('login_cookie', 'valid', time() + 86400, '/'); // 1 day
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= $maxLoginAttempts) {
            banIP($ip);
            $error = "登录失败次数过多。您的IP已被禁止访问24小时。";
        } else {
            $error = "用户名或密码错误。剩余尝试次数: " . ($maxLoginAttempts - $_SESSION['login_attempts']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - API管理系统</title>
    <link rel="stylesheet" href="bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center mb-4">API管理系统登录</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">登录</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>