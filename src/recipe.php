<?php
session_start();
// ログインしていなければログイン画面へリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
require_once 'Gacha.php';

// セッションからユーザーIDを取得
$userId = $_SESSION['user_id'];

$gacha = new Gacha();
// 固定の1ではなく、$userIdを渡す
$recipes = $gacha->getRecipeBook($userId);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>配合レシピブック</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; text-align: center; background-color: #fdfdfd;}
        h1 { border-bottom: 2px solid #555; padding-bottom: 10px; }
        
        .recipe-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.05);
        }
        
        .item-box { width: 30%; text-align: center; }
        .operator { font-size: 2em; color: #888; font-weight: bold; }
        
        .badge { font-size: 0.8em; padding: 2px 5px; border-radius: 3px; color: white; display: inline-block; margin-bottom: 5px;}
        .name { font-weight: bold; display: block; }
        
        /* 発見済みの結果画像 */
        .res-img { width: 80px; height: auto; border-radius: 5px; border: 2px solid gold; }
        
        /* 未発見の場合のスタイル */
        .unknown-box {
            width: 80px; height: 80px; 
            background-color: #444; 
            color: #fff; 
            font-size: 2em; 
            line-height: 80px; 
            border-radius: 5px; 
            margin: 0 auto;
        }
        .unknown-text { color: #888; font-style: italic; }

        /* レアリティ色 */
        .b-UR  { background: linear-gradient(45deg, #f06, #9f6); }
        .b-SSR { background: gold; color: black; }
        .b-SR  { background: silver; color: black; }
        .b-R   { background: #88f; }
        .b-N   { background: #555; }
        /* 究極合体(カオス)用に追加 */
        .b-LR  { background: linear-gradient(to right, #000, #550, #000); color: #fff; border: 1px solid gold; }

        .link-area { margin-top: 30px; }
    </style>
</head>
<body>

    <h1>古代の配合レシピ帳</h1>
    <p>これまでに発見された配合の記録です。</p>

    <?php foreach ($recipes as $r): ?>
        <div class="recipe-row">
            
            <!-- ベース素材 -->
            <div class="item-box">
                <span class="badge b-<?= $r['base_rarity'] ?>"><?= $r['base_rarity'] ?></span>
                <span class="name"><?= htmlspecialchars($r['base_name']) ?></span>
            </div>

            <div class="operator">+</div>

            <!-- 素材 -->
            <div class="item-box">
                <span class="badge b-<?= $r['mat_rarity'] ?>"><?= $r['mat_rarity'] ?></span>
                <span class="name"><?= htmlspecialchars($r['mat_name']) ?></span>
            </div>

            <div class="operator">=</div>

            <!-- 結果 (発見済みなら表示、未発見なら伏せる) -->
            <div class="item-box">
                <?php if ($r['is_discovered']): ?>
                    <!-- 発見済み -->
                    <img src="images/<?= htmlspecialchars($r['res_img']) ?>" class="res-img"><br>
                    <span class="badge b-<?= $r['res_rarity'] ?>"><?= $r['res_rarity'] ?></span>
                    <span class="name" style="color:#d32f2f;"><?= htmlspecialchars($r['res_name']) ?></span>
                <?php else: ?>
                    <!-- 未発見 -->
                    <div class="unknown-box">?</div>
                    <span class="unknown-text">？？？</span>
                <?php endif; ?>
            </div>

        </div>
    <?php endforeach; ?>

    <div class="link-area">
        <a href="synthesis.php">配合画面へ戻る</a> | 
        <a href="index.php">トップへ</a>
    </div>

</body>
</html>