CREATE DATABASE IF NOT EXISTS concrete_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE concrete_app;

-- ── Users ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    role         ENUM('Engineer','Student','Technician','Other') DEFAULT 'Engineer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Saved Mix Designs ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS saved_designs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL DEFAULT 'Mix Design',
    grade           VARCHAR(10)  NOT NULL DEFAULT 'M20',
    fck             DECIMAL(6,2) NOT NULL,
    wc_ratio        DECIMAL(5,3) NOT NULL,
    slump           INT          NOT NULL,
    max_agg_size    INT          NOT NULL DEFAULT 20,
    admixture_pct   DECIMAL(5,2) DEFAULT 0,
    std_dev         DECIMAL(5,2) DEFAULT 5.0,
    target_strength DECIMAL(8,2) DEFAULT NULL,
    cement_content  DECIMAL(8,2) DEFAULT NULL,
    water_content   DECIMAL(8,2) DEFAULT NULL,
    sand_mass       DECIMAL(8,2) DEFAULT NULL,
    coarse_mass     DECIMAL(8,2) DEFAULT NULL,
    admixture_mass  DECIMAL(8,2) DEFAULT NULL,
    mix_ratio       VARCHAR(50)  DEFAULT NULL,
    est_cost        DECIMAL(10,2) DEFAULT NULL,
    notes           TEXT          DEFAULT NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── View: designs with user info ─────────────────────────────
CREATE OR REPLACE VIEW v_designs_full AS
SELECT
    sd.*,
    u.full_name  AS user_name,
    u.email      AS user_email,
    u.role       AS user_role
FROM saved_designs sd
JOIN users u ON u.id = sd.user_id;
