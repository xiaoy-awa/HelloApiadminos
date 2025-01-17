<?php
session_start();

// 检查登录状态
function checkLogin() {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: login.php');
        exit;
    }

    // 检查登录是否过期
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 86400)) {
        unset($_SESSION['logged_in']);
        unset($_SESSION['login_time']);
        setcookie('login_cookie', '', time() - 3600, '/');
        header('Location: login.php');
        exit;
    }
}

checkLogin();

// 加载配置文件
$configFile = '../main.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
} else {
    $config = ['api' => []];
}

// 处理添加、编辑或删除API
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_api']) || isset($_POST['edit_api'])) {
        $newApi = [
            'name' => $_POST['name'],
            'now' => $_POST['now'],
            'txt' => $_POST['txt'],
            'url' => $_POST['url'],
            'get' => $_POST['get'],
            'out' => $_POST['out'],
            'count' => $_POST['count'],
            'fh' => $_POST['fh'],
        ];

        // 处理动态参数
        $paramCount = (int) $_POST['count'];
        for ($i = 1; $i <= $paramCount; $i++) {
            $newApi["{$i}-name"] = $_POST["param_name_$i"];
            $newApi["{$i}-ok"] = isset($_POST["param_required_$i"]) ? '是' : '否';
            $newApi["{$i}-type"] = $_POST["param_type_$i"];
            $newApi["{$i}-main"] = $_POST["param_main_$i"];
        }

        if (isset($_POST['add_api'])) {
            // 添加新API
            $config['api'][] = $newApi;
        } else {
            // 编辑现有API
            $index = $_POST['index'];
            $config['api'][$index] = $newApi;
        }
    } elseif (isset($_POST['delete_api'])) {
        // 删除API
        $index = $_POST['index'];
        array_splice($config['api'], $index, 1);
    }

    // 保存更改到配置文件
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    
    // 重定向以防止表单重复提交
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 获取编辑数据（如果是编辑模式）
$editApiData = null;
if (isset($_GET['edit'])) {
    $index = (int) $_GET['edit'];
    $editApiData = $config['api'][$index];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API管理系统</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        textarea { width: 100%; min-height: 120px; resize: vertical; }
        .param-group { margin-bottom: 10px; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        .param-group input, .param-group select { width: 100%; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-5">API管理系统</h1>
        
        <div class="text-end mb-3">
            <a href="logout.php" class="btn btn-secondary">退出登录</a>
        </div>

        <button type="button" class="btn btn-primary mb-3" onclick="showAddForm()">添加新API</button>

        <div id="apiForm" style="display: <?php echo $editApiData ? 'block' : 'none'; ?>;">
            <h2><?php echo $editApiData ? '编辑API' : '添加新API'; ?></h2>
            <form action="" method="POST">
                <?php if ($editApiData): ?>
                    <input type="hidden" name="index" value="<?php echo $_GET['edit']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">API名称：</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo $editApiData['name'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="now">状态：</label>
                    <select name="now" id="now" class="form-control" required>
                        <option value="正常" <?php echo ($editApiData['now'] ?? '') == '正常' ? 'selected' : ''; ?>>正常</option>
                        <option value="维护" <?php echo ($editApiData['now'] ?? '') == '维护' ? 'selected' : ''; ?>>维护</option>
                        <option value="未公开" <?php echo ($editApiData['now'] ?? '') == '未公开' ? 'selected' : ''; ?>>未公开</option>
                        <option value="收费" <?php echo ($editApiData['now'] ?? '') == '收费' ? 'selected' : ''; ?>>收费</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="txt">描述：</label>
                    <input type="text" name="txt" id="txt" class="form-control" value="<?php echo $editApiData['txt'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="url">URL：</label>
                    <input type="url" name="url" id="url" class="form-control" value="<?php echo $editApiData['url'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="get">请求方法：</label>
                    <select name="get" id="get" class="form-control" required>
                        <option value="Get" <?php echo ($editApiData['get'] ?? '') == 'Get' ? 'selected' : ''; ?>>GET</option>
                        <option value="Post" <?php echo ($editApiData['get'] ?? '') == 'Post' ? 'selected' : ''; ?>>POST</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="out">返回类型：</label>
                    <input type="text" name="out" id="out" class="form-control" value="<?php echo $editApiData['out'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="count">参数个数：</label>
                    <input type="number" name="count" id="count" class="form-control" min="1" value="<?php echo $editApiData['count'] ?? 1; ?>" required>
                    <button type="button" class="btn btn-secondary mt-2" onclick="generateParams()">生成参数输入框</button>
                </div>

                <div id="params-container">
                    <?php
                    if ($editApiData) {
                        $paramCount = (int)$editApiData['count'];
                        for ($i = 1; $i <= $paramCount; $i++) {
                            echo "<div class='param-group'>";
                            echo "<label>参数 $i</label>";
                            echo "<input type='text' name='param_name_$i' value='" . ($editApiData["{$i}-name"] ?? '') . "' class='form-control' placeholder='参数名' required>";
                            echo "<select name='param_type_$i' class='form-control'>";
                            $types = ['Text', 'Number', 'Boolean'];
                            foreach ($types as $type) {
                                $selected = ($editApiData["{$i}-type"] ?? '') == $type ? 'selected' : '';
                                echo "<option value='$type' $selected>$type</option>";
                            }
                            echo "</select>";
                            $checked = ($editApiData["{$i}-ok"] ?? '') == '是' ? 'checked' : '';
                            echo "<div class='form-check'>";
                            echo "<input type='checkbox' name='param_required_$i' class='form-check-input' $checked>";
                            echo "<label class='form-check-label'>是否必填</label>";
                            echo "</div>";
                            echo "<input type='text' name='param_main_$i' value='" . ($editApiData["{$i}-main"] ?? '') . "' class='form-control' placeholder='参数说明'>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>

                <div class="form-group">
                    <label for="fh">返回示例：</label>
                    <textarea name="fh" id="fh" class="form-control" required><?php echo $editApiData['fh'] ?? ''; ?></textarea>
                </div>

                <button type="submit" name="<?php echo $editApiData ? 'edit_api' : 'add_api'; ?>" class="btn btn-primary mt-3">
                    <?php echo $editApiData ? '提交修改' : '添加API'; ?>
                </button>
            </form>
        </div>

        <h2 class="mt-5">现有API列表</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>API名称</th>
                    <th>描述</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($config['api'] as $index => $api): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($api['name']); ?></td>
                        <td><?php echo htmlspecialchars($api['txt']); ?></td>
                        <td><?php echo htmlspecialchars($api['now']); ?></td>
                        <td>
                            <a href="?edit=<?php echo $index; ?>" class="btn btn-warning btn-sm">编辑</a>
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <button type="submit" name="delete_api" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除这个API吗？');">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function showAddForm() {
        document.getElementById('apiForm').style.display = 'block';
    }

    function generateParams() {
        const paramCount = document.getElementById('count').value;
        const paramsContainer = document.getElementById('params-container');
        paramsContainer.innerHTML = '';

        for (let i = 1; i <= paramCount; i++) {
            const paramGroup = document.createElement('div');
            paramGroup.className = 'param-group';

            paramGroup.innerHTML = `
                <label>参数 ${i}</label>
                <input type="text" name="param_name_${i}" class="form-control" placeholder="参数名" required>
                <select name="param_type_${i}" class="form-control">
                    <option value="Text">Text</option>
                    <option value="Number">Number</option>
                    <option value="Boolean">Boolean</option>
                </select>
                <div class="form-check">
                    <input type="checkbox" name="param_required_${i}" class="form-check-input">
                    <label class="form-check-label">是否必填</label>
                </div>
                <input type="text" name="param_main_${i}" class="form-control" placeholder="参数说明">
            `;

            paramsContainer.appendChild(paramGroup);
        }
    }
    </script>
</body>
</html>