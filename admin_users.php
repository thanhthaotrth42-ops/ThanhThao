<?php
require_once "auth.php";
requireAdmin();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// Xử lý thêm hoặc cập nhật tài khoản / đổi mật khẩu
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        die("Yêu cầu không hợp lệ.");
    }

    $action = $_POST["action"] ?? "";

    // 1. Thêm tài khoản mới
    if ($action === "add") {
        $username = trim($_POST["username"] ?? "");
        $fullname = trim($_POST["fullname"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "user";

        if ($username === "" || $fullname === "" || $password === "") {
            $error = "Vui lòng nhập đầy đủ thông tin tài khoản mới.";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $check->execute(["username" => $username]);
            if ($check->fetch()) {
                $error = "Tên đăng nhập này đã tồn tại.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, role) VALUES (:username, :password, :fullname, :role)");
                $stmt->execute(["username" => $username, "password" => $hashed, "fullname" => $fullname, "role" => $role]);
                $success = "Đã thêm tài khoản thành công!";
            }
        }
    }
    // 2. Đổi mật khẩu thành viên
    elseif ($action === "change_password") {
        $userId = (int)($_POST["user_id"] ?? 0);
        $newPass = trim($_POST["new_password"] ?? "");

        if ($userId <= 0 || strlen($newPass) < 8) {
            $error = "Mật khẩu mới phải có ít nhất 8 ký tự.";
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute(["password" => $hashed, "id" => $userId]);
            $success = "Đã cập nhật mật khẩu mới cho thành công!";
        }
    }
    // 3. Xóa tài khoản
    elseif ($action === "delete") {
        $userId = (int)($_POST["user_id"] ?? 0);
        if ($userId === (int)$_SESSION["user_id"]) {
            $error = "Bạn không thể tự xóa tài khoản của chính mình đang đăng nhập.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(["id" => $userId]);
            $success = "Đã xóa tài khoản thành công!";
        }
    }
}

// Lấy danh sách toàn bộ users
$users = $conn->query("SELECT id, username, fullname, role, created_at FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý tài khoản - AI MILK TEA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #F9FAFB; --surface: #FFFFFF; --border: #E5E7EB;
            --text-title: #111827; --text-body: #374151; --text-muted: #6B7280;
            --primary: #2563EB; --primary-hover: #1D4ED8; --primary-light: #EFF6FF;
            --danger: #EF4444; --danger-bg: #FEF2F2; --success: #10B981; --success-bg: #ECFDF5;
            --radius-md: 8px; --radius-lg: 12px;
        }
        body { margin: 0; background: var(--bg-body); color: var(--text-body); font-family: 'Inter', sans-serif; font-size: 14.5px; }
        header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 16px 32px; display: flex; justify-content: space-between; align-items: center; }
        main { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        h2, h3 { color: var(--text-title); margin-top: 0; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
        label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
        input, select { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 14px; outline: none; }
        input:focus, select:focus { border-color: var(--primary); }
        .btn { padding: 10px 18px; background: var(--primary); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; }
        .btn:hover { background: var(--primary-hover); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 16px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg-body); color: var(--text-muted); font-size: 12px; text-transform: uppercase; }
        .badge { padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-admin { background: #DBEAFE; color: #1D4ED8; }
        .badge-user { background: #F3F4F6; color: #374151; }
        .msg { padding: 12px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px; }
        .error { background: var(--danger-bg); color: var(--danger); }
        .success { background: var(--success-bg); color: var(--success); }
    </style>
</head>
<body>

<header>
    <strong>Quản lý Thành viên & Phân quyền</strong>
    <a href="admin.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">← Quay lại trang Admin</a>
</header>

<main>
    <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg success"><?= e($success) ?></div><?php endif; ?>

    <!-- Form Thêm tài khoản -->
    <div class="card">
        <h3>➕ Thêm tài khoản mới</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div><label>Tên đăng nhập</label><input type="text" name="username" required></div>
                <div><label>Họ và tên</label><input type="text" name="fullname" required></div>
                <div><label>Mật khẩu</label><input type="password" name="password" minlength="8" required></div>
                <div>
                    <label>Quyền hạn</label>
                    <select name="role">
                        <option value="user">User (Thường)</option>
                        <option value="admin">Admin (Quản trị)</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn">Tạo tài khoản</button>
        </form>
    </div>

    <!-- Danh sách tài khoản -->
    <div class="card">
        <h3>👥 Danh sách thành viên trong hệ thống (<?= count($users) ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Họ tên</th>
                    <th>Quyền</th>
                    <th>Ngày tạo</th>
                    <th>Hành động đổi mật khẩu / Xóa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= (int)$u["id"] ?></td>
                    <td><strong><?= e($u["username"]) ?></strong></td>
                    <td><?= e($u["fullname"]) ?></td>
                    <td>
                        <span class="badge <?= $u["role"] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                            <?= strtoupper($u["role"]) ?>
                        </span>
                    </td>
                    <td><?= e($u["created_at"]) ?></td>
                    <td>
                        <!-- Form Đổi mật khẩu nhanh -->
                        <form method="POST" style="display:inline-flex; gap:6px; align-items:center;">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                            <input type="hidden" name="action" value="change_password">
                            <input type="hidden" name="user_id" value="<?= (int)$u["id"] ?>">
                            <input type="password" name="new_password" placeholder="Mật khẩu mới (≥8 ký tự)" style="width: 160px; padding: 6px;" required>
                            <button type="submit" class="btn" style="padding: 6px 10px; font-size: 12px;">Đổi MK</button>
                        </form>

                        <!-- Form Xóa tài khoản -->
                        <?php if ((int)$u["id"] !== (int)$_SESSION["user_id"]): ?>
                        <form method="POST" style="display:inline; margin-left: 6px;" onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?');">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= (int)$u["id"] ?>">
                            <button type="submit" class="btn" style="background: var(--danger); padding: 6px 10px; font-size: 12px;">Xóa</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>