<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "concrete_app");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? 'Engineer';
    $pass     = $_POST['password'] ?? '';
    $conf     = $_POST['confirm'] ?? '';

    // Basic validation
    if (!$fullname || !$email || !$pass || !$conf) {
        header("Location: register.html?error=" . urlencode("All fields are required"));
        exit;
    }
    if ($pass !== $conf) {
        header("Location: register.html?error=" . urlencode("Passwords do not match"));
        exit;
    }
    if (strlen($pass) < 6) {
        header("Location: register.html?error=" . urlencode("Password must be at least 6 characters"));
        exit;
    }

    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, role, password) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        header("Location: register.html?error=" . urlencode("Email already registered"));
        exit;
 }

    $stmt->bind_param("ssss", $fullname, $email, $role, $hashedPassword);

    if ($stmt->execute()) {
        header("Location: login.html?success=" . urlencode("Account created successfully"));
        exit;

        // Handle duplicate email or other SQL errors
        $msg = strpos($stmt->error, 'Duplicate') !== false
            ? "Email already registered"
            : "Error: " . $stmt->error;
        header("Location: register.html?error=" . urlencode($msg));
        exit;
    }

    $stmt->close();
}
$conn->close();
?>
