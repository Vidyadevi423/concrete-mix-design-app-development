<?php
/**
 * GET /get_user.php
 * Returns logged-in user's profile from DB
 */
require_once 'db.php';
header('Content-Type: application/json');

$uid = requireAuth();
$pdo = getDBConnection();
if (!$pdo) jsonResponse(false, 'Database error', null, 500);

$stmt = $pdo->prepare('SELECT id,full_name,email,role FROM users WHERE id=? LIMIT 1');
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) jsonResponse(false, 'User not found', null, 404);

// Count saved designs
$cnt = $pdo->prepare('SELECT COUNT(*) FROM saved_designs WHERE user_id=?');
$cnt->execute([$uid]);
$user['saved_designs_count'] = (int)$cnt->fetchColumn();

jsonResponse(true, 'OK', $user);
 