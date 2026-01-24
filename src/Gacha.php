<?php
require_once 'db_connect.php';

class Gacha {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /* ====================================================
       ユーザー管理機能 (Login)
       ==================================================== */
    
    // 新規ユーザー作成
    public function createUser($name) {
        $sql = "INSERT INTO m_users (name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }

    // 全ユーザー取得
    public function getAllUsers() {
        $sql = "SELECT * FROM m_users ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // ユーザー名取得
    public function getUserName($id) {
        $sql = "SELECT name FROM m_users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ? $res['name'] : '不明な勇者';
    }

    /* ====================================================
       ガチャ機能
       ==================================================== */

    /**
     * 10連ガチャを実行 (修正: $userIdを受け取るように変更)
     */
    public function draw10($userId) {
        $weights = $this->getWeightList();
        $totalWeight = array_sum(array_column($weights, 'weight'));
        $results = [];

        try {
            $this->pdo->beginTransaction();
            for ($i = 0; $i < 10; $i++) {
                $drawnItem = $this->lottery($weights, $totalWeight);
                $results[] = $drawnItem;
                
                // 修正: 固定の1ではなく、$userIdを使う
                $this->saveHistory($userId, $drawnItem['id']);
                $this->saveUserItem($userId, $drawnItem['id']);
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return $results;
    }

    /* ====================================================
       配合機能 (Synthesis)
       ==================================================== */

    public function fuseCards($userId, $baseItemId, $materialItemId) {
        try {
            $this->pdo->beginTransaction();

            // 1. レシピ確認
            $sqlRecipe = "SELECT result_id FROM m_synthesis_recipes 
                          WHERE base_id = :base AND material_id = :mat";
            $stmtRecipe = $this->pdo->prepare($sqlRecipe);
            $stmtRecipe->bindValue(':base', $baseItemId, PDO::PARAM_INT);
            $stmtRecipe->bindValue(':mat', $materialItemId, PDO::PARAM_INT);
            $stmtRecipe->execute();
            $recipe = $stmtRecipe->fetch();

            if (!$recipe) {
                throw new Exception("この組み合わせからは何も生まれないようだ...");
            }

            // 2. 所持チェック
            $items = $this->getUserInventory($userId);
            $hasBase = false;
            $hasMat = false;
            foreach($items as $item) {
                if ($item['item_id'] == $baseItemId && $item['count'] >= 1) $hasBase = true;
                if ($item['item_id'] == $materialItemId && $item['count'] >= 1) $hasMat = true;
                if ($baseItemId == $materialItemId && $item['item_id'] == $baseItemId && $item['count'] < 2) {
                     throw new Exception("同じカード同士の配合には2枚必要です。");
                }
            }

            if (!$hasBase || !$hasMat) {
                throw new Exception("素材が足りません。");
            }

            // 3. 消費
            $this->decreaseUserItem($userId, $baseItemId);
            $this->decreaseUserItem($userId, $materialItemId);

            // 4. 抽選 (成功率50%)
            $isSuccess = (random_int(1, 100) <= 50);
            $resultItemId = 0;
            $message = "";

            if ($isSuccess) {
                $resultItemId = $recipe['result_id'];
                $message = "配合成功！！新たなモンスターが誕生した！";
            } else {
                $resultItemId = 99; // 失敗作ヘドロ
                $message = "配合失敗... 変な物体が生まれてしまった...";
            }

            // 5. 付与
            $this->saveUserItem($userId, $resultItemId);
            
            // 結果情報取得
            $stmtInfo = $this->pdo->prepare("SELECT name, img_name FROM m_items WHERE id = ?");
            $stmtInfo->execute([$resultItemId]);
            $resultInfo = $stmtInfo->fetch();

            $this->pdo->commit();
            
            return [
                "success" => $isSuccess, 
                "message" => $message,
                "result_name" => $resultInfo['name'],
                "result_img" => $resultInfo['img_name']
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["success" => false, "message" => "エラー: " . $e->getMessage()];
        }
    }

    /* ====================================================
       データ取得・集計機能
       ==================================================== */

    // ユーザー所持アイテム取得
    public function getUserInventory($userId) {
        $sql = "SELECT u.item_id, u.count, m.name, m.rarity, m.img_name 
                FROM t_user_items u
                JOIN m_items m ON u.item_id = m.id
                WHERE u.user_id = :uid AND u.count > 0
                ORDER BY m.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // レシピ図鑑取得
    public function getRecipeBook($userId) {
        $sql = "SELECT 
                    r.base_id, b.name AS base_name, b.rarity AS base_rarity,
                    r.material_id, m.name AS mat_name, m.rarity AS mat_rarity,
                    r.result_id, res.name AS res_name, res.img_name AS res_img, res.rarity AS res_rarity,
                    CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS is_discovered
                FROM m_synthesis_recipes r
                JOIN m_items b ON r.base_id = b.id
                JOIN m_items m ON r.material_id = m.id
                JOIN m_items res ON r.result_id = res.id
                LEFT JOIN t_user_items u ON u.item_id = res.id AND u.user_id = :uid
                ORDER BY r.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 全履歴数 (サーバー全体)
    public function getTotalHistoryCount() {
        $sql = "SELECT COUNT(*) as total FROM t_gacha_history";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result['total'];
    }

    // アイテム別排出数 (サーバー全体)
    public function getItemStats() {
        $sql = "SELECT h.item_id, m.name, COUNT(h.id) as count 
                FROM t_gacha_history h JOIN m_items m ON h.item_id = m.id
                GROUP BY h.item_id ORDER BY h.item_id ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /* ====================================================
       内部ヘルパーメソッド (private)
       ==================================================== */

    private function getWeightList() {
        $sql = "SELECT w.item_id, w.weight, i.name, i.rarity, i.img_name 
                FROM m_gacha_weights w JOIN m_items i ON w.item_id = i.id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    private function lottery($list, $totalWeight) {
        $random = random_int(1, $totalWeight);
        $current = 0;
        foreach ($list as $item) {
            $current += $item['weight'];
            if ($random <= $current) {
                return $this->formatItem($item);
            }
        }
        return $this->formatItem(end($list));
    }
    
    private function formatItem($item) {
        return [
            'id' => $item['item_id'], 'name' => $item['name'],
            'rarity' => $item['rarity'], 'img' => $item['img_name']
        ];
    }

    // 履歴保存 (ユーザーID対応)
    private function saveHistory($userId, $itemId) {
        $sql = "INSERT INTO t_gacha_history (user_id, item_id, created_at) VALUES (:uid, :iid, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':iid', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    // 所持追加 (ユーザーID対応)
    private function saveUserItem($userId, $itemId) {
        $sql = "INSERT INTO t_user_items (user_id, item_id, count, created_at, updated_at) 
                VALUES (:uid, :iid, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE count = count + 1, updated_at = NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':iid', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    // 所持減少 (ユーザーID対応)
    private function decreaseUserItem($userId, $itemId) {
        $sql = "UPDATE t_user_items SET count = count - 1 WHERE user_id = :uid AND item_id = :iid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':iid', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }
}