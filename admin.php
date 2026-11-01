<?php
require_once "auth.php";
requireAdmin();

$stmtUser = $conn->prepare("SELECT role, fullname FROM users WHERE id = :id LIMIT 1");
$stmtUser->execute(["id" => $_SESSION["user_id"]]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser["role"] !== "admin") {
    http_response_code(403);
    die("Bạn không có quyền truy cập trang quản trị.");
}

$totalRecipes = (int)$conn->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
$totalCategories = (int)$conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalUsers = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - AI MILK TEA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #F9FAFB; --surface: #FFFFFF; --border: #E5E7EB; --border-hover: #D1D5DB;
            --text-title: #111827; --text-body: #374151; --text-muted: #6B7280;
            --primary: #2563EB; --primary-hover: #1D4ED8; --primary-light: #EFF6FF;
            --danger: #EF4444; --danger-bg: #FEF2F2;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius-md: 8px; --radius-lg: 12px; --transition: all 0.2s ease;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg-body); color: var(--text-body); font-family: 'Inter', sans-serif; font-size: 14.5px; }
        header { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); border-bottom: 1px solid var(--border); }
        .header-content { max-width: 1440px; min-height: 72px; margin: 0 auto; padding: 12px 32px; display: flex; align-items: center; justify-content: space-between; }
        .brand-wrap { display: flex; align-items: center; gap: 12px; }
        .brand-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); background: var(--primary); color: white; }
        header h1 { margin: 0; color: var(--text-title); font-size: 18px; font-weight: 700; }
        header p { margin: 0; color: var(--text-muted); font-size: 12px; }
        .admin-badge { background: var(--primary-light); border: 1px solid #DBEAFE; color: var(--primary); border-radius: var(--radius-md); padding: 8px 14px; font-size: 13.5px; }
        main { max-width: 1440px; margin: 0 auto; padding: 40px 32px; }
        .page-title h2 { margin: 0 0 4px; color: var(--text-title); font-size: 26px; font-weight: 700; }
        .page-title p { margin: 0 0 28px; color: var(--text-muted); }
        
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 36px; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); transition: var(--transition); }
        .card:hover { border-color: var(--primary); box-shadow: var(--shadow-md); }
        .card-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .card-number { font-size: 36px; font-weight: 800; color: var(--text-title); margin-bottom: 16px; }
        
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--radius-md); background: var(--primary); color: white; font-size: 13px; font-weight: 600; text-decoration: none; transition: var(--transition); }
        .btn:hover { background: var(--primary-hover); }
        .btn-light { background: var(--surface); border: 1px solid var(--border); color: var(--text-title); }
        .btn-light:hover { background: var(--bg-body); }
        .btn-danger { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-danger:hover { background: var(--danger); color: white; }
        
        .quick-actions { display: flex; gap: 12px; margin-top: 20px; }
        footer { margin-top: 60px; padding: 24px; background: var(--surface); border-top: 1px solid var(--border); text-align: center; color: var(--text-muted); font-size: 13px; }
        @media(max-width: 900px) { .stats { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="brand-wrap">
            <div class="brand-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <div>
                <h1>AI MILK TEA</h1>
                <p>Hệ thống quản trị hệ thống</p>
            </div>
        </div>
        <div class="admin-badge">Quản trị viên: <strong><?= e($currentUser["fullname"]) ?></strong></div>
    </div>
</header>

<main>
    <div class="page-title">
        <h2>Tổng quan hệ thống</h2>
        <p>Quản lý toàn bộ công thức, danh mục và tài khoản người dùng trực tiếp từ giao diện.</p>
    </div>

    <div class="stats">
        <!-- Quản lý Công thức -->
        <div class="card">
            <div class="card-label"><span>Công thức trà sữa</span> 📦</div>
            <div class="card-number"><?= $totalRecipes ?></div>
            <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 16px;">Xem, thêm, sửa, xóa các công thức pha chế.</p>
            <a class="btn" href="admin_recipes.php">Quản lý công thức →</a>
        </div>

        <!-- Quản lý Danh mục -->
        <div class="card">
            <div class="card-label"><span>Danh mục sản phẩm</span> 📁</div>
            <div class="card-number"><?= $totalCategories ?></div>
            <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 16px;">Thêm hoặc đổi tên các danh mục đồ uống.</p>
            <a class="btn" href="admin_categories.php">Quản lý danh mục →</a>
        </div>

        <!-- Quản lý Tài khoản -->
        <div class="card">
            <div class="card-label"><span>Tài khoản thành viên</span> 👤</div>
            <div class="card-number"><?= $totalUsers ?></div>
            <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 16px;">Xem danh sách, thêm tài khoản, đổi mật khẩu hoặc phân quyền.</p>
            <a class="btn" href="admin_users.php">Quản lý tài khoản →</a>
        </div>
    </div>

    <div class="quick-actions">
        <a class="btn btn-light" href="index.php">🏠 Về trang chủ website</a>
        <a class="btn btn-danger" href="logout.php">🚪 Đăng xuất</a>
    </div>
</main>

<footer>AI MILK TEA - Đồ án nhóm Chủ đề 12</footer>

</body>
</html>