<?php
/**
 * POST /login.php
 * Body (JSON or form): { email, password }
 * Returns: { success, message, data: { id, name, email, role } }
 */
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$email    = trim(strtolower($in['email']    ?? ''));
$password = $in['password'] ?? '';

if (!$email)                              jsonResponse(false, 'Email is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Invalid email address.');
if (!$password)                           jsonResponse(false, 'Password is required.');

$pdo = getDBConnection();
if (!$pdo) jsonResponse(false, 'Database connection failed.', null, 500);

$stmt = $pdo->prepare('SELECT id,full_name,email,password,role FROM users WHERE email=? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user)                                    jsonResponse(false, 'No account found with this email.');
if (!password_verify($password, $user['password'])) jsonResponse(false, 'Incorrect password.');

startSession();
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['full_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role']  = $user['role'];

jsonResponse(true, 'Login successful!', [
    'id'    => $user['id'],
    'name'  => $user['full_name'],
    'email' => $user['email'],
    'role'  => $user['role'],
]);
