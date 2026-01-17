<?php
session_start();
// ログインしていなければログイン画面へリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
require_once 'Gacha.php';

$gacha = new Gacha();

$totalCount = $gacha->getTotalHistoryCount();
$itemStats  = $gacha->getItemStats();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ガチャ結果 集計レポート</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        h1 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 20px; }
        th, td { border: 1px solid #999; padding: 10px; text-align: center; }
        th { background-color: #f0f0f0; }
        .total-area { font-size: 1.2em; margin: 20px 0; font-weight: bold; }
        a { display: inline-block; margin-top: 20px; font-size: 1.1em; }
    </style>
</head>
<body>
    <h1>ガチャ結果 集計レポート</h1>
    <p>全ユーザーのガチャ履歴から算出された排出率です。</p>

    <div class="total-area">
        集計対象の履歴総数: <?= number_format($totalCount) ?> 件
    </div>

    <table>
        <thead>
            <tr>
                <th>Item ID</th>
                <th>アイテム名</th>
                <th>出現回数 (count)</th>
                <th>出現率 (rate)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itemStats as $stat): ?>
                <?php 
                    if ($totalCount > 0) {
                        $rate = ($stat['count'] / $totalCount) * 100;
                    } else {
                        $rate = 0;
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($stat['item_id']) ?></td>
                    <td><?= htmlspecialchars($stat['name']) ?></td>
                    <td><?= number_format($stat['count']) ?> 回</td>
                    <td><?= number_format($rate, 2) ?> %</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php">戻る</a>
</body>
</html>