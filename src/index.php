<?php
session_start();
// ログインしていなければログイン画面へリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// エラーを表示する設定
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');
require_once 'Gacha.php';

// セッションからユーザー情報を取得
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

$results = [];
$message = "";

// POSTリクエストが来たらガチャを実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gacha = new Gacha();
    try {
        // 【修正箇所】引数なしの draw10() ではなく、ユーザーIDを渡す draw10($userId) に変更！
        $results = $gacha->draw10($userId);
        $message = "ガチャを回しました！";
    } catch (Exception $e) {
        $message = "エラーが発生しました: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ガチャ課題</title>
    <style>
        body { font-family: sans-serif; padding: 20px; text-align: center; }
        
        /* カードのデザイン */
        .card { 
            border: 2px solid #ccc; 
            border-radius: 8px;
            padding: 10px; 
            margin: 10px; 
            display: inline-block; 
            width: 160px; 
            vertical-align: top;
            background-color: white;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
        }
        
        .card img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .SSR { border-color: #ffd700; background-color: #fffacd; }
        .SR  { border-color: #c0c0c0; background-color: #f5f5f5; }
        .R   { border-color: #a0a0a0; }
        .b-LR  { background: linear-gradient(to right, #000, #550, #000); color: #fff; border: 1px solid gold; }

        .rarity-label { font-weight: bold; display: block; margin-bottom: 5px;}
        .name-label { font-size: 0.9em; }

        button { padding: 15px 30px; font-size: 1.2em; cursor: pointer; margin-bottom: 20px;}
        
        .link-area { margin: 20px 0; font-size: 1.1em; }
        .link-area a { margin: 0 10px; }
        a.report-link { color: #007bff; text-decoration: none; border-bottom: 1px solid #007bff;}
        
        .header-info { text-align: right; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header-info">
        勇者: <b><?= htmlspecialchars($userName) ?></b> (ID: <?= $userId ?>) | 
        <a href="login.php">ログアウト</a>
    </div>

    <h1>ソーシャルゲームガチャ課題</h1>
    
    <form method="post">
        <button type="submit">10連ガチャを引く</button>
    </form>

    <div class="link-area">
        <a href="gacha_total_count.php" class="report-link">📊 結果集計</a>
        <a href="inventory.php" class="report-link">🎒 所持アイテム一覧</a>
        <a href="synthesis.php" class="report-link">🔮 モンスター配合</a>
    </div>

    <p><?= htmlspecialchars($message) ?></p>

    <?php if (!empty($results)): ?>
        <div class="results">
            <?php foreach ($results as $item): ?>
                <div class="card <?= $item['rarity'] ?>">
                    <img src="images/<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <span class="rarity-label">[<?= $item['rarity'] ?>]</span>
                    <span class="name-label"><?= htmlspecialchars($item['name']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>