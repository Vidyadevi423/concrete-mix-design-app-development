<?php
/**
 * POST /update_profile.php
 * Body: { name?, email?, role?, current_password?, new_password? }
 */
require_once 'db.php';
header('Content-Type: application/json');

$uid = requireAuth();
$in  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$pdo = getDBConnection();
if (!$pdo) jsonResponse(false, 'Database error', null, 500);

$updates = []; $params = [];

if (!empty($in['name'])) {
    $updates[] = 'full_name=?'; $params[] = trim($in['name']);
    $_SESSION['user_name'] = trim($in['name']);
}
if (!empty($in['email'])) {
    $email = strtolower(trim($in['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Please provide a valid email address.');
    }
    // ensure uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
    $stmt->execute([$email, $uid]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Email is already in use by another account.');
    }
    $updates[] = 'email=?'; $params[] = $email;
    $_SESSION['user_email'] = $email;
}
if (!empty($in['role']) && in_array($in['role'],['Engineer','Student','Technician','Other'])) {
    $updates[] = 'role=?'; $params[] = $in['role'];
    $_SESSION['user_role'] = $in['role'];
}

// Password change
if (!empty($in['new_password'])) {
    if (strlen($in['new_password']) < 6) jsonResponse(false, 'New password must be at least 6 characters.');
    $row = $pdo->prepare('SELECT password FROM users WHERE id=?');
    $row->execute([$uid]); $row = $row->fetch();
    if (!password_verify($in['current_password'] ?? '', $row['password']))
        jsonResponse(false, 'Current password is incorrect.');
    $updates[] = 'password=?';
    $params[]  = password_hash($in['new_password'], PASSWORD_BCRYPT, ['cost'=>12]);
}

if (empty($updates)) jsonResponse(false, 'No changes provided.');

$params[] = $uid;
$pdo->prepare('UPDATE users SET '.implode(',',$updates).' WHERE id=?')->execute($params);

jsonResponse(true, 'Profile updated successfully!');
