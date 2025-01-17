<?php
session_start();

// 清除session中的登录状态
unset($_SESSION['logged_in']);
unset($_SESSION['login_time']);

// 清除登录cookie
setcookie('login_cookie', '', time() - 3600, '/');

// 重定向到登录页面
header('Location: login.php');
exit;