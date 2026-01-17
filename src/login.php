<?php
session_start(); // セッション開始（記憶領域を使う）
require_once 'Gacha.php';

$gacha = new Gacha();
$message = "";

// 新規登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_name'])) {
    $name = trim($_POST['register_name']);
    if ($name !== "") {
        $newId = $gacha->createUser($name);
        // そのままログイン状態にする
        $_SESSION['user_id'] = $newId;
        $_SESSION['user_name'] = $name;
        header('Location: index.php'); // ガチャ画面へ移動
        exit;
    } else {
        $message = "名前を入力してください";
    }
}

// 既存ユーザーでログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id'])) {
    $id = $_POST['login_id'];
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $gacha->getUserName($id);
    header('Location: index.php'); // ガチャ画面へ移動
    exit;
}

$users = $gacha->getAllUsers();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - ガチャシミュレーター</title>
    <style>
        body { font-family: sans-serif; padding: 50px; text-align: center; max-width: 600px; margin: 0 auto; }
        .box { border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        input[type="text"] { padding: 10px; width: 60%; font-size: 1.1em; }
        button { padding: 10px 20px; font-size: 1.1em; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px; }
        h2 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px;}
        .user-list { list-style: none; padding: 0; }
        .user-list li { margin: 10px 0; }
        .login-btn { background: #28a745; width: 100%; }
    </style>
</head>
<body>
    <h1>ガチャシミュレーター</h1>
    <p style="color:red"><?= htmlspecialchars($message) ?></p>

    <!-- 新規登録エリア -->
    <div class="box">
        <h2>✨ はじめから遊ぶ</h2>
        <form method="post">
            <input type="text" name="register_name" placeholder="勇者の名前を入力" required>
            <button type="submit">冒険を始める</button>
        </form>
    </div>

    <!-- ログインエリア -->
    <div class="box">
        <h2>📂 続きから遊ぶ</h2>
        <?php if (empty($users)): ?>
            <p>データがありません</p>
        <?php else: ?>
            <form method="post">
                <ul class="user-list">
                    <?php foreach ($users as $u): ?>
                        <li>
                            <button type="submit" name="login_id" value="<?= $u['id'] ?>" class="login-btn">
                                <?= htmlspecialchars($u['name']) ?> (ID: <?= $u['id'] ?>)
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>