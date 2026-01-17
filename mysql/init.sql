SET NAMES utf8mb4;

-- 1. アイテムマスタ
CREATE TABLE m_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    rarity VARCHAR(10) NOT NULL,
    img_name VARCHAR(50) NOT NULL
) DEFAULT CHARSET=utf8mb4;

-- 2. 排出確率テーブル
CREATE TABLE m_gacha_weights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    weight INT NOT NULL,
    FOREIGN KEY (item_id) REFERENCES m_items(id)
) DEFAULT CHARSET=utf8mb4;

-- 3. ガチャ履歴テーブル
CREATE TABLE t_gacha_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 1,
    item_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES m_items(id)
) DEFAULT CHARSET=utf8mb4;

-- 4. ユーザー所持アイテムテーブル
CREATE TABLE t_user_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    count INT NOT NULL DEFAULT 1,
    level INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_item (user_id, item_id),
    FOREIGN KEY (item_id) REFERENCES m_items(id)
) DEFAULT CHARSET=utf8mb4;

-- 5. 合成レシピテーブル
CREATE TABLE m_synthesis_recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_id INT NOT NULL,
    material_id INT NOT NULL,
    result_id INT NOT NULL,
    FOREIGN KEY (base_id) REFERENCES m_items(id),
    FOREIGN KEY (material_id) REFERENCES m_items(id),
    FOREIGN KEY (result_id) REFERENCES m_items(id)
) DEFAULT CHARSET=utf8mb4;


-- === データ投入 ===

-- アイテムマスタ
INSERT INTO m_items (id, name, rarity, img_name) VALUES
(1, '伝説の勇者', 'SSR', 'hero.png'),
(2, '聖なる騎士', 'SR',  'knight.png'),
(3, '見習い兵士', 'R',   'soldier.png'),
(4, '村人A',      'R',   'villager.png'),
(5, 'ただの棒',   'R',   'stick.png'),
(6, '覚醒勇者ゴッド', 'UR',  'super_hero.png'), -- 合成成功用
(99, '失敗作ヘドロ',  'N',   'trash.png');      -- 合成失敗用

-- ガチャ排出設定 (覚醒勇者とヘドロはガチャからは出ない)
INSERT INTO m_gacha_weights (item_id, weight) VALUES
(1, 1),
(2, 9),
(3, 30),
(4, 30),
(5, 30);

-- レシピ登録
-- 「伝説の勇者(1)」＋「聖なる騎士(2)」＝「覚醒勇者ゴッド(6)」
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (1, 2, 6);

-- 「見習い兵士(3)」＋「ただの棒(5)」＝「聖なる騎士(2)」(昇格)
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (3, 5, 2);
-- 1. 「見習い兵士(3)」＋「見習い兵士(3)」＝「聖なる騎士(2)」
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (3, 3, 2);

-- 2. 「村人A(4)」＋「村人A(4)」＝「見習い兵士(3)」
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (4, 4, 3);

-- 3. 「ただの棒(5)」＋「ただの棒(5)」＝「村人A(4)」
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (5, 5, 4);

-- 「聖なる騎士(2)」＋「聖なる騎士(2)」＝「伝説の勇者(1)」
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (2, 2, 1);

-- 1.超ヘドロ」を登録
INSERT INTO m_items (id, name, rarity, img_name) VALUES
(100, '超ヘドロ', 'SR', 'super_trash.png');

-- 2.失敗作ヘドロ(99)」＋「失敗作ヘドロ(99)」＝「超ヘドロ(100)」
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (99, 99, 100);

-- 1. 究極のキャラクター登録 (ID:999)
INSERT INTO m_items (id, name, rarity, img_name) VALUES
(999, '混沌の神カオス', 'LR', 'chaos.png');

-- 2.
-- パターンA: 覚醒勇者ゴッド(6) ＋ 超ヘドロ(100) ＝ 混沌の神カオス(999)
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (6, 100, 999);

-- パターンB: 超ヘドロ(100) ＋ 覚醒勇者ゴッド(6) ＝ 混沌の神カオス(999)
INSERT INTO m_synthesis_recipes (base_id, material_id, result_id) VALUES (100, 6, 999);

-- ユーザー管理テーブル
CREATE TABLE m_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4;

-- 初期ユーザー（テスト用）
INSERT INTO m_users (id, name) VALUES (1, 'テスト勇者');