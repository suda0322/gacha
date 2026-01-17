<?php
session_start();
// ログインしていなければログイン画面へ
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; } 

header('Content-Type: text/html; charset=UTF-8');
require_once 'Gacha.php';

// セッションからユーザーIDを取得
$userId = $_SESSION['user_id'];

// 所持データを取得
$gacha = new Gacha();
// 固定の1ではなく、$userIdを渡す
$myItems = $gacha->getUserInventory($userId); 
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>所持アイテム一覧</title>
    <style>
        body { font-family: sans-serif; padding: 20px; text-align: center;}
        h1 { border-bottom: 2px solid #333; padding-bottom: 10px; }

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
            position: relative;
        }
        .card img { width: 100%; border-radius: 4px; }
        
        /* 所持数のバッジ */
        .count-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: red;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            line-height: 30px;
            font-weight: bold;
            font-size: 0.9em;
            box-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .SSR { border-color: #ffd700; background-color: #fffacd; }
        .SR  { border-color: #c0c0c0; background-color: #f5f5f5; }
        .R   { border-color: #a0a0a0; }

        a { display: inline-block; margin-top: 20px; font-size: 1.2em; }
    </style>
</head>
<body>

    <!-- User: 1 ではなく 変数を表示 -->
    <h1>所持アイテム一覧 (User: <?= htmlspecialchars($userId) ?>)</h1>

    <?php if (empty($myItems)): ?>
        <p>まだ何も持っていません。ガチャを引いてみましょう！</p>
    <?php else: ?>
        <div>
            <?php foreach ($myItems as $item): ?>
                <div class="card <?= $item['rarity'] ?>">
                    <!-- 所持数バッジ -->
                    <div class="count-badge">x<?= $item['count'] ?></div>
                    
                    <img src="images/<?= htmlspecialchars($item['img_name']) ?>">
                    <strong>[<?= $item['rarity'] ?>]</strong><br>
                    <?= htmlspecialchars($item['name']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <a href="index.php">ガチャ画面へ戻る</a>

</body>
</html>