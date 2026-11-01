<?php
require_once "auth.php";
requireAdmin();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        die("Yêu cầu không hợp lệ.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $name = trim($_POST["name"] ?? "");
        if ($name === "") {
            $error = "Tên danh mục không được để trống.";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->execute(["name" => $name]);
            $success = "Thêm danh mục thành công!";
        }
    } elseif ($action === "delete") {
        $catId = (int)($_POST["category_id"] ?? 0);
        // Kiểm tra xem danh mục có đang chứa công thức nào không
        $check = $conn->prepare("SELECT COUNT(*) FROM recipes WHERE category_id = :id");
        $check->execute(["id" => $catId]);
        if ($check->fetchColumn() > 0) {
            $error = "Không thể xóa danh mục này vì đang có công thức thuộc về nó.";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute(["id" => $catId]);
            $success = "Đã xóa danh mục thành công!";
        }
    }
}

$categories = $conn->query("SELECT c.id, c.name, (SELECT COUNT(*) FROM recipes r WHERE r.category_id = c.id) as total_recipes FROM categories c ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý danh mục - AI MILK TEA</title>
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
        main { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        h2, h3 { color: var(--text-title); margin-top: 0; }
        input { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 14px; outline: none; margin-bottom: 12px; }
        input:focus { border-color: var(--primary); }
        .btn { padding: 10px 18px; background: var(--primary); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; }
        .btn:hover { background: var(--primary-hover); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 16px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: var(--bg-body); color: var(--text-muted); font-size: 12px; text-transform: uppercase; }
        .msg { padding: 12px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px; }
        .error { background: var(--danger-bg); color: var(--danger); }
        .success { background: var(--success-bg); color: var(--success); }
    </style>
</head>
<body>

<header>
    <strong>Quản lý Danh mục Đồ uống</strong>
    <a href="admin.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">← Quay lại trang Admin</a>
</header>

<main>
    <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg success"><?= e($success) ?></div><?php endif; ?>

    <div class="card">
        <h3>➕ Thêm danh mục mới</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
            <input type="hidden" name="action" value="add">
            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; display:block;">Tên danh mục</label>
            <input type="text" name="name" placeholder="Ví dụ: Trà sữa kem muối, Trà trái cây..." required>
            <button type="submit" class="btn">Thêm danh mục</button>
        </form>
    </div>

    <div class="card">
        <h3>📁 Danh sách danh mục hiện có</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên danh mục</th>
                    <th>Số lượng công thức</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>#<?= (int)$cat["id"] ?></td>
                    <td><strong><?= e($cat["name"]) ?></strong></td>
                    <td><?= (int)$cat["total_recipes"] ?> công thức</td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?= (int)$cat["id"] ?>">
                            <button type="submit" class="btn" style="background: var(--danger); padding: 6px 12px; font-size: 13px;">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>