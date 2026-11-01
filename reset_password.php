<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "config.php";

$error = "";
$success = "";
$token = $_GET["token"] ?? "";

if (!$token) {
    die("Liên kết không hợp lệ.");
}

// Bỏ kiểm tra expires_at tạm thời để tránh lỗi lệch múi giờ trên Localhost
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = :token LIMIT 1");
$stmt->execute(["token" => $token]);
$resetReq = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resetReq) {
    die("Liên kết khôi phục mật khẩu không tồn tại hoặc đã được sử dụng.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPass = $_POST["password"] ?? "";
    $confirmPass = $_POST["confirm_password"] ?? "";

    if (strlen($newPass) < 8) {
        $error = "Mật khẩu mới phải có ít nhất 8 ký tự.";
    } elseif ($newPass !== $confirmPass) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu mới cho user
        $update = $conn->prepare("UPDATE users SET password = :password WHERE username = :username");
        $update->execute(["password" => $hashed, "username" => $resetReq["email"]]);

        // Xóa token sau khi dùng xong để chống dùng lại
        $del = $conn->prepare("DELETE FROM password_resets WHERE token = :token");
        $del->execute(["token" => $token]);

        $success = "Đổi mật khẩu thành công! Bạn có thể <a href='login.php'><b>Đăng nhập ngay</b></a>.";
    }
}
function e($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu - AI MILK TEA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #F9FAFB; --surface: #FFFFFF; --border: #E5E7EB; --primary: #2563EB; --text: #374151; --title: #111827; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .box { width: 100%; max-width: 400px; background: var(--surface); padding: 32px; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: var(--title); font-size: 22px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--title); }
        input { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; margin-bottom: 16px; outline: none; }
        input:focus { border-color: var(--primary); }
        button { width: 100%; padding: 11px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        button:hover { background: #1D4ED8; }
        .msg { padding: 10px; border-radius: 8px; font-size: 13.5px; margin-bottom: 16px; }
        .error { background: #FEF2F2; color: #EF4444; }
        .success { background: #ECFDF5; color: #10B981; }
    </style>
</head>
<body>
<div class="box">
    <h2>Đặt lại mật khẩu</h2>
    <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg success"><?= $success ?></div><?php else: ?>
    <form method="POST">
        <label>Mật khẩu mới (≥ 8 ký tự)</label>
        <input type="password" name="password" minlength="8" required>
        
        <label>Xác nhận mật khẩu mới</label>
        <input type="password" name="confirm_password" minlength="8" required>
        
        <button type="submit">Cập nhật mật khẩu</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>