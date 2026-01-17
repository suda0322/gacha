<?php
session_start();
// ログインしていなければログイン画面へリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');
require_once 'Gacha.php';

// セッションからユーザーIDを取得
$userId = $_SESSION['user_id'];

$gacha = new Gacha();
$resultData = null;
$errorMessage = "";

// 合成ボタンが押された時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baseId = $_POST['base_id'] ?? null;
    $materialId = $_POST['material_id'] ?? null;

    if ($baseId && $materialId) {
        // 固定の1ではなく、$userIdを渡す
        $resultData = $gacha->fuseCards($userId, $baseId, $materialId);
        
        // ロジック内でエラーメッセージが返ってきた場合の処理
        if (isset($resultData['message']) && (strpos($resultData['message'], 'エラー') !== false || strpos($resultData['message'], '生まれない') !== false)) {
            $errorMessage = $resultData['message'];
            $resultData = null;
        }
    } else {
        $errorMessage = "ベースと素材を選択してください。";
    }
}

// 固定の1ではなく、$userIdを渡す
$myItems = $gacha->getUserInventory($userId);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>モンスター配合</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; text-align: center; }
        h1 { border-bottom: 2px solid #333; }
        
        /* 結果表示モーダル風エリア */
        .result-area {
            background-color: #fff;
            border: 5px solid gold;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            animation: pop 0.5s ease;
        }
        @keyframes pop { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .result-img { width: 200px; height: auto; display: block; margin: 10px auto; }
        .success-msg { color: #d32f2f; font-size: 1.5em; font-weight: bold; }
        .fail-msg { color: #555; font-size: 1.2em; font-weight: bold; }

        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 20px;}

        .select-area { display: flex; justify-content: space-around; margin-bottom: 30px; text-align: left; }
        .box { width: 45%; border: 1px solid #ccc; padding: 15px; border-radius: 8px; background: #f9f9f9; }
        label { display: block; margin-bottom: 8px; padding: 5px; border-bottom: 1px solid #eee; cursor: pointer; }
        label:hover { background-color: #eef; }
        
        button { 
            padding: 15px 40px; font-size: 1.5em; background-color: #6a5acd; color: white; 
            border: none; border-radius: 5px; cursor: pointer; 
        }
        button:hover { background-color: #483d8b; }
        
        .badge { font-weight: bold; font-size: 0.8em; padding: 2px 5px; border-radius: 3px; color: white; }
        .b-UR  { background: linear-gradient(45deg, #f06, #9f6); }
        .b-SSR { background: gold; color: black; }
        .b-SR  { background: silver; color: black; }
        .b-R   { background: #88f; }
        .b-N   { background: #555; }
        .b-LR  { background: linear-gradient(to right, #000, #550, #000); color: #fff; border: 1px solid gold; } 
    </style>
</head>
<body>

    <h1>モンスター配合の館</h1>
    <p>
        <a href="recipe.php" target="_blank" style="background:#eee; padding:5px 10px; border-radius:5px; text-decoration:none;">
            📖 レシピ帳を開く
        </a>
    </p>
    <p>2体のモンスターを混ぜて強力な魔物を生み出せ！<br>（※失敗するとヘドロになります）</p>

    <!-- エラー表示 -->
    <?php if ($errorMessage): ?>
        <div class="error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <!-- 結果表示 -->
    <?php if ($resultData): ?>
        <div class="result-area">
            <div class="<?= $resultData['success'] ? 'success-msg' : 'fail-msg' ?>">
                <?= htmlspecialchars($resultData['message']) ?>
            </div>
            
            <img src="images/<?= htmlspecialchars($resultData['result_img']) ?>" class="result-img">
            
            <h2><?= htmlspecialchars($resultData['result_name']) ?></h2>
            <p>を入手しました！</p>
            
            <a href="synthesis.php" style="display:block; margin-top:10px;">もう一度配合する</a>
        </div>
    <?php else: ?>

        <!-- 選択フォーム -->
        <form method="post">
            <div class="select-area">
                <div class="box">
                    <h3>ベース (Base)</h3>
                    <?php if (empty($myItems)): ?>
                        <p>モンスターがいません</p>
                    <?php else: ?>
                        <?php foreach ($myItems as $item): ?>
                            <label>
                                <input type="radio" name="base_id" value="<?= $item['item_id'] ?>" required>
                                <span class="badge b-<?= $item['rarity'] ?>"><?= $item['rarity'] ?></span>
                                <b><?= htmlspecialchars($item['name']) ?></b> x<?= $item['count'] ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="box">
                    <h3>素材 (Material)</h3>
                    <?php if (empty($myItems)): ?>
                        <p>モンスターがいません</p>
                    <?php else: ?>
                        <?php foreach ($myItems as $item): ?>
                            <label>
                                <input type="radio" name="material_id" value="<?= $item['item_id'] ?>" required>
                                <span class="badge b-<?= $item['rarity'] ?>"><?= $item['rarity'] ?></span>
                                <b><?= htmlspecialchars($item['name']) ?></b> x<?= $item['count'] ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit">配合儀式を行う</button>
        </form>

    <?php endif; ?>

    <div style="margin-top:30px;">
        <a href="inventory.php">所持一覧へ</a> | <a href="index.php">ガチャへ</a>
    </div>

</body>
</html>