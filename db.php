<?php
/**
 * Database — Concrete Mix Design App
 * Shared connection + session helper
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'concrete_app');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default; change for production
define('DB_PORT', '3306');

function getDBConnection(): PDO|false {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME);
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        ensureSchema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        error_log('DB Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create core tables if they are missing.
 * Keeps registrations working even when database.sql has not been imported.
 */
function ensureSchema(PDO $pdo): void {
    // users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name    VARCHAR(100) NOT NULL,
            email        VARCHAR(150) NOT NULL UNIQUE,
            password     VARCHAR(255) NOT NULL,
            role         ENUM('Engineer','Student','Technician','Other') DEFAULT 'Engineer'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // saved_designs table (needed for dashboards and profile counts)
    $pdo->exec("
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
            INDEX idx_user_id (user_id),
            INDEX idx_created (created_at),
            CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // view for joined designs
    $pdo->exec("
        CREATE OR REPLACE VIEW v_designs_full AS
        SELECT
            sd.*,
            u.full_name  AS user_name,
            u.email      AS user_email,
            u.role       AS user_role
        FROM saved_designs sd
        JOIN users u ON u.id = sd.user_id;
    ");
}

/** Start session if not already started */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('concrete_sess');
        session_start();
    }
}

/** Return logged-in user_id or null */
function getSessionUserId(): ?int {
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/** Require auth — sends 401 JSON and exits if not logged in */
function requireAuth(): int {
    $uid = getSessionUserId();
    if (!$uid) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Not authenticated']);
        exit;
    }
    return $uid;
}

/** Standard JSON response helper */
function jsonResponse(bool $success, string $message, mixed $data = null, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success'=>$success,'message'=>$message,'data'=>$data]);
    exit;
}
