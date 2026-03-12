<?php
require_once 'db.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        jsonResponse(false, 'Method not allowed', null, 405);
}

function handleGet(): never {
    $pdo = getDBConnection();
    if (!$pdo) jsonResponse(false, 'Database connection failed', null, 500);

    $userId = requireAuth();

    $stmt = $pdo->prepare("SELECT * FROM saved_designs WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    jsonResponse(true, 'OK', $rows);
}

function handlePost(): never {
    $pdo = getDBConnection();
    if (!$pdo) jsonResponse(false, 'Database connection failed', null, 500);

    $userId = requireAuth();
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) jsonResponse(false, 'Invalid JSON payload', null, 400);

    $stmt = $pdo->prepare("
        INSERT INTO saved_designs
            (user_id, name, grade, fck, wc_ratio, slump, max_agg_size, admixture_pct, std_dev,
             target_strength, cement_content, water_content, sand_mass, coarse_mass, admixture_mass,
             mix_ratio, est_cost)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    try {
        $stmt->execute([
            $userId,
            $data['name'] ?? 'Mix Design',
            $data['grade'] ?? 'M20',
            $data['fck'] ?? 0,
            $data['wc_ratio'] ?? 0,
            $data['slump'] ?? 0,
            $data['max_agg_size'] ?? 20,
            $data['admixture_pct'] ?? 0,
            $data['std_dev'] ?? 5,
            $data['target_strength'] ?? null,
            $data['cement_content'] ?? null,
            $data['water_content'] ?? null,
            $data['sand_mass'] ?? null,
            $data['coarse_mass'] ?? null,
            $data['admixture_mass'] ?? null,
            $data['mix_ratio'] ?? null,
            $data['est_cost'] ?? null
        ]);
        jsonResponse(true, 'Design saved');
    } catch (Throwable $e) {
        jsonResponse(false, 'Insert failed', ['error'=>$e->getMessage()], 500);
    }
}

function handleDelete(): never {
    $pdo = getDBConnection();
    if (!$pdo) jsonResponse(false, 'Database connection failed', null, 500);

    $userId = requireAuth();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) jsonResponse(false, 'Missing id', null, 400);

    $stmt = $pdo->prepare("DELETE FROM saved_designs WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(true, 'Deleted');
    } else {
        jsonResponse(false, 'Not found or not owned by user', null, 404);
    }
}
?>
