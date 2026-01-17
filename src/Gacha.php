<?php
require_once 'db_connect.php';

class Gacha {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * 10連ガチャを実行
     */
    public function draw10() {
        $weights = $this->getWeightList();
        $totalWeight = array_sum(array_column($weights, 'weight'));
        $results = [];

        try {
            $this->pdo->beginTransaction();
            for ($i = 0; $i < 10; $i++) {
                $drawnItem = $this->lottery($weights, $totalWeight);
                $results[] = $drawnItem;
                $this->saveHistory(1, $drawnItem['id']);
                // 獲得したアイテムを所持テーブルに追加
                $this->saveUserItem(1, $drawnItem['id']);
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return $results;
    }

    /* ============================
            配合合成機能
       ============================ */
    public function fuseCards($userId, $baseItemId, $materialItemId) {
        try {
            $this->pdo->beginTransaction();

            // 1. レシピが存在するか確認
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

            // 2. 素材を持っているか確認（ベースと素材の両方）
            $items = $this->getUserInventory($userId);
            $hasBase = false;
            $hasMat = false;
            foreach($items as $item) {
                if ($item['item_id'] == $baseItemId && $item['count'] >= 1) $hasBase = true;
                if ($item['item_id'] == $materialItemId && $item['count'] >= 1) $hasMat = true;
                // ベースと素材が同じカードの場合、2枚以上必要
                if ($baseItemId == $materialItemId && $item['item_id'] == $baseItemId && $item['count'] < 2) {
                     throw new Exception("同じカード同士の配合には2枚必要です。");
                }
            }

            if (!$hasBase || !$hasMat) {
                throw new Exception("素材が足りません。");
            }

            // 3. 素材を消費（ベースと素材、両方1つずつ減らす）
            $this->decreaseUserItem($userId, $baseItemId);
            $this->decreaseUserItem($userId, $materialItemId);

            // 4. 抽選（成功率 70%）
            $isSuccess = (random_int(1, 100) <= 70);
            $resultItemId = 0;
            $message = "";

            if ($isSuccess) {
                // 成功：レシピ通りのモンスター
                $resultItemId = $recipe['result_id'];
                $message = "配合成功！！新たなモンスターが誕生した！";
            } else {
                // 失敗：失敗作ヘドロ(ID:99)になってしまう
                $resultItemId = 99; 
                $message = "配合失敗... 変な物体が生まれてしまった...";
            }

            // 5. 結果モンスターを付与
            $this->saveUserItem($userId, $resultItemId);
            
            // 結果表示用に情報を取得
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

    private function saveHistory($userId, $itemId) {
        $sql = "INSERT INTO t_gacha_history (user_id, item_id, created_at) VALUES (:uid, :iid, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':iid', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function saveUserItem($userId, $itemId) {
        // 所持数を+1する（なければ新規作成）
        $sql = "INSERT INTO t_user_items (user_id, item_id, count, created_at, updated_at) 
                VALUES (:uid, :iid, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE count = count + 1, updated_at = NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':iid', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function decreaseUserItem($userId, $itemId) {
        // 所持数を-1する
        $sql = "UPDATE t_user_items SET count = count - 1 WHERE user_id = :uid AND item_id = :iid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':iid', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getTotalHistoryCount() {
        $sql = "SELECT COUNT(*) as total FROM t_gacha_history";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function getItemStats() {
        $sql = "SELECT h.item_id, m.name, COUNT(h.id) as count 
                FROM t_gacha_history h JOIN m_items m ON h.item_id = m.id
                GROUP BY h.item_id ORDER BY h.item_id ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getUserInventory($userId) {
        // 所持数が0より大きいものだけ取得
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
    /**
     * レシピ図鑑用のデータを取得
     * ユーザーが結果アイテムを一度でも入手したことがあるかもチェック
     */
    public function getRecipeBook($userId) {
        $sql = "SELECT 
                    r.base_id, b.name AS base_name, b.rarity AS base_rarity,
                    r.material_id, m.name AS mat_name, m.rarity AS mat_rarity,
                    r.result_id, res.name AS res_name, res.img_name AS res_img, res.rarity AS res_rarity,
                    -- ユーザー所持テーブルにレコードがあるかチェック（1なら発見済み、0なら未発見）
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

    /* ============================
            ユーザー管理機能
       ============================ */
    
    /**
     * 新規ユーザーを作成し、そのIDを返す
     */
    public function createUser($name) {
        $sql = "INSERT INTO m_users (name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $this->pdo->lastInsertId(); // 登録されたIDを返す
    }

    /**
     * 登録済みのユーザー一覧を取得
     */
    public function getAllUsers() {
        $sql = "SELECT * FROM m_users ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * IDからユーザー名を取得
     */
    public function getUserName($id) {
        $sql = "SELECT name FROM m_users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ? $res['name'] : '不明な勇者';
    }

}