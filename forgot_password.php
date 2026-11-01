<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "config.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    
    if ($username === "") {
        $error = "Vui lòng nhập tên đăng nhập của bạn.";
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(["username" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Tạo token ngẫu nhiên bảo mật
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+15 minutes")); // Token hết hạn sau 15 phút

            // Lưu token vào bảng reset
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = :username");
            $del->execute(["username" => $username]);

            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:username, :token, :expires)");
            $ins->execute(["username" => $username, "token" => $token, "expires" => $expires]);

            // Đối với đồ án chạy Localhost không gửi được mail thực tế, ta in thẳng link ra màn hình để test cho tiện:
            $resetLink = "http://localhost/DoAn12/reset_password.php?token=" . $token;
            $success = "Yêu cầu thành công! (Do chạy Localhost, bấm vào liên kết sau để đổi mật khẩu: <a href='$resetLink'><b>Đổi mật khẩu ngay</b></a>)";
        } else {
            // Bảo mật: Vẫn thông báo chung chung hoặc báo lỗi nếu không tìm thấy
            $error = "Không tìm thấy tên đăng nhập này trong hệ thống.";
        }
    }
}
function e($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu - AI MILK TEA</title>
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
        .back { text-align: center; margin-top: 16px; font-size: 13px; }
        .back a { color: var(--primary); text-decoration: none; }
    </style>
</head>
<body>
<div class="box">
    <h2>Quên mật khẩu</h2>
    <p style="font-size: 13.5px; color: #6B7280; margin-bottom: 20px;">Nhập tên đăng nhập để tiến hành khôi phục mật khẩu tài khoản của bạn.</p>

    <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg success"><?= $success ?></div><?php endif; ?>

    <form method="POST">
        <label>Tên đăng nhập</label>
        <input type="text" name="username" placeholder="Nhập username của bạn" required>
        <button type="submit">Gửi yêu cầu khôi phục</button>
    </form>

    <div class="back">
        <a href="login.php">← Quay lại trang Đăng nhập</a>
    </div>
</div>
</body>
</html>