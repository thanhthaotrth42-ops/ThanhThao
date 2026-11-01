<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config.php";

// --- XỬ LÝ XÓA NHANH TỪ TRANG CHỦ (DÀNH CHO ADMIN) ---
if (isset($_GET['delete_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $delId = (int)$_GET['delete_id'];
    $stmtDel = $conn->prepare("DELETE FROM recipes WHERE id = :id");
    $stmtDel->execute(['id' => $delId]);
    header("Location: index.php?msg=deleted");
    exit;
}

// Lấy danh mục cho dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Lấy từ khóa và danh mục lọc
$search = trim($_GET["search"] ?? "");
$category_id = (int)($_GET["category"] ?? 0);

$params = [];
$sql = "SELECT recipes.id, recipes.name, recipes.ingredients, recipes.instructions, categories.name AS category_name 
        FROM recipes 
        INNER JOIN categories ON recipes.category_id = categories.id 
        WHERE 1=1";

if ($search !== "") {
    $sql .= " AND (recipes.name LIKE :search OR recipes.ingredients LIKE :search)";
    $params["search"] = "%" . $search . "%";
}

if ($category_id > 0) {
    $sql .= " AND recipes.category_id = :category_id";
    $params["category_id"] = $category_id;
}

$sql .= " ORDER BY recipes.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// --- XỬ LÝ TRẢ VỀ KẾT QUẢ TÌM KIẾM TỨC THÌ (AJAX SEARCH) ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_start();
    if (!empty($recipes)): 
        foreach ($recipes as $recipe): ?>
            <div class="recipe-card card">
                <div>
                    <h3 class="recipe-title"><?= e($recipe["name"]) ?></h3>
                    <span class="category-badge"><?= e($recipe["category_name"]) ?></span>
                    
                    <div class="section-block">
                        <strong>NGUYÊN LIỆU</strong>
                        <p><?= nl2br(e($recipe["ingredients"])) ?></p>
                    </div>

                    <div class="section-block">
                        <strong>HƯỚNG DẪN PHA CHẾ</strong>
                        <p><?= nl2br(e($recipe["instructions"])) ?></p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; border-top: 1px solid var(--border); padding-top: 12px;">
                    <a class="action-link" href="detail.php?id=<?= (int)$recipe["id"] ?>" style="margin-top: 0;">Xem chi tiết →</a>
                    
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                        <div style="display: flex; gap: 8px;">
                            <a href="admin_edit_recipe.php?id=<?= (int)$recipe["id"] ?>" style="font-size: 12px; padding: 4px 8px; background: #EFF6FF; color: var(--primary); border-radius: 4px; font-weight: 600;">Sửa</a>
                            <a href="index.php?delete_id=<?= (int)$recipe["id"] ?>" onclick="return confirm('Bạn có chắc muốn xóa món này không?');" style="font-size: 12px; padding: 4px 8px; background: #FEF2F2; color: #EF4444; border-radius: 4px; font-weight: 600;">Xóa</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach;
    else: ?>
        <div class="empty-state">
            Không tìm thấy công thức trà sữa phù hợp.
        </div>
    <?php endif;
    
    $html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html,
        'total' => count($recipes)
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI MILK TEA | Thư viện công thức trà sữa</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #F9FAFB;
            --surface: #FFFFFF;
            --border: #E5E7EB;
            --border-hover: #D1D5DB;
            --text-title: #111827;
            --text-body: #374151;
            --text-muted: #6B7280;
            
            --primary: #2563EB;
            --primary-hover: #1D4ED8;
            --primary-light: #EFF6FF;
            
            --radius-md: 8px;
            --radius-lg: 12px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg-body);
            color: var(--text-body);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 14.5px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; color: inherit; }

        .header-content, main {
            max-width: 1360px !important;
            width: 100%;
            margin: 0 auto;
            padding: 0 32px;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
        }

        .header-content {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: var(--primary);
            color: white;
        }

        header h1 {
            margin: 0;
            color: var(--text-title);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        header p { margin: 1px 0 0; color: var(--text-muted); font-size: 12px; }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: var(--radius-md);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary { background: var(--primary); color: white; border: none; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .btn-light { background: var(--surface); border: 1px solid var(--border); color: var(--text-title); }
        .btn-light:hover { background: var(--bg-body); border-color: var(--border-hover); }

        .hero {
            padding: 40px 0 20px;
        }

        .hero h2 {
            font-size: clamp(28px, 3vw, 38px);
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.8px;
            margin: 0 0 10px;
        }

        .hero h2 span { color: var(--primary); }

        .search-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            margin: 24px 0 32px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        input[type="text"], select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--text-body);
            font-size: 14px;
            outline: none;
            transition: var(--transition);
        }

        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
            margin-bottom: 60px;
            transition: opacity 0.2s ease;
        }

        .recipe-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            top: 0;
            transition: var(--transition);
        }

        .recipe-card:hover {
            top: -4px;
            border-color: #93C5FD;
            box-shadow: 0 12px 20px -5px rgba(37, 99, 235, 0.1);
        }

        .recipe-title {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-title);
        }

        .category-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
            align-self: flex-start;
        }

        .section-block {
            margin-bottom: 12px;
        }

        .section-block strong {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .section-block p {
            margin: 0;
            font-size: 13.5px;
            color: var(--text-body);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .action-link {
            margin-top: 16px;
            display: inline-flex;
            align-items: center;
            color: var(--primary);
            font-weight: 600;
            font-size: 13.5px;
            transition: var(--transition);
        }

        .action-link:hover {
            color: var(--primary-hover);
            transform: translateX(3px);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: var(--radius-lg);
        }

        @keyframes slideIn {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        footer {
            padding: 32px;
            text-align: center;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
            background: var(--surface);
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .header-content, main { padding: 0 16px; }
            .search-form { grid-template-columns: 1fr; }
            .recipe-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="brand-wrap">
            <div class="brand-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8h1a4 4 0 1 1 0 8h-1"></path><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path></svg>
            </div>
            <div>
                <h1>AI MILK TEA</h1>
                <p>Thư viện công thức chuẩn vị</p>
            </div>
        </div>

        <div class="user-nav">
            <?php if (isset($_SESSION["user_id"])): ?>
                <span style="font-size: 13px; color: var(--text-muted);">Xin chào, <strong><?= e($_SESSION["fullname"]) ?></strong></span>
                <a class="btn btn-primary" href="chat.php" style="background: #10B981;">✨ Hỏi AI ngay</a>
                <?php if (($_SESSION["role"] ?? '') === 'admin'): ?>
                    <a class="btn btn-light" href="admin.php">Quản trị</a>
                <?php endif; ?>
                <a class="btn btn-light" href="logout.php">Đăng xuất</a>
            <?php else: ?>
                <a class="btn btn-light" href="login.php">Đăng nhập</a>
                <a class="btn btn-primary" href="register.php">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <div class="hero">
        <h2>Khám phá công thức <br>trà sữa <span>chuẩn vị AI.</span></h2>
        <p style="color: var(--text-muted); margin: 0;">Tra cứu nguyên liệu, công thức pha chế độc quyền nhanh chóng và tinh tế.</p>
    </div>

    <div class="search-card">
        <div class="search-form">
            <div class="form-group">
                <label>Tên công thức / Nguyên liệu</label>
                <input type="text" name="search" placeholder="Gõ để tìm công thức tức thì..." value="<?= e($search) ?>" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Danh mục</label>
                <select name="category">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat["id"] ?>" <?= $category_id === (int)$cat["id"] ? 'selected' : '' ?>>
                            <?= e($cat["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <p class="results-count" style="color: var(--text-muted); margin-bottom: 20px;">
        Đang hiển thị <strong><?= count($recipes) ?></strong> công thức kết quả
    </p>

    <!-- CONTAINER HIỂN THỊ CÔNG THỨC -->
    <div class="recipe-grid">
        <?php if (!empty($recipes)): ?>
            <?php foreach ($recipes as $recipe): ?>
                <div class="recipe-card">
                    <div>
                        <h3 class="recipe-title"><?= e($recipe["name"]) ?></h3>
                        <span class="category-badge"><?= e($recipe["category_name"]) ?></span>
                        
                        <div class="section-block">
                            <strong>NGUYÊN LIỆU</strong>
                            <p><?= nl2br(e($recipe["ingredients"])) ?></p>
                        </div>

                        <div class="section-block">
                            <strong>HƯỚNG DẪN PHA CHẾ</strong>
                            <p><?= nl2br(e($recipe["instructions"])) ?></p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; border-top: 1px solid var(--border); padding-top: 12px;">
                        <a class="action-link" href="detail.php?id=<?= (int)$recipe["id"] ?>" style="margin-top: 0;">Xem chi tiết →</a>
                        
                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                            <div style="display: flex; gap: 8px;">
                                <a href="admin_edit_recipe.php?id=<?= (int)$recipe["id"] ?>" style="font-size: 12px; padding: 4px 8px; background: #EFF6FF; color: var(--primary); border-radius: 4px; font-weight: 600;">Sửa</a>
                                <a href="index.php?delete_id=<?= (int)$recipe["id"] ?>" onclick="return confirm('Bạn có chắc muốn xóa món này không?');" style="font-size: 12px; padding: 4px 8px; background: #FEF2F2; color: #EF4444; border-radius: 4px; font-weight: 600;">Xóa</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                Không tìm thấy công thức trà sữa phù hợp.
            </div>
        <?php endif; ?>
    </div>
</main>

<footer>
    AI MILK TEA - Đồ án nhóm Chủ đề 12
</footer>

<!-- JavaScript Xử lý Toast thông báo từ URL & AJAX Search -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // --- 1. HIỆN THÔNG BÁO NỔI TOAST TỪ THAM SỐ URL (?msg=...) ---
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg) {
        let msgText = '';
        if (msg === 'deleted') msgText = 'Xóa công thức thành công!';
        if (msg === 'updated') msgText = 'Cập nhật công thức thành công!';
        if (msg === 'added') msgText = 'Thêm mới công thức thành công!';

        if (msgText) {
            const toast = document.createElement('div');
            toast.id = 'toast-alert';
            toast.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                <span>${msgText}</span>
            `;
            toast.style.cssText = "position: fixed; bottom: 24px; right: 24px; z-index: 1000; background: #10B981; color: white; padding: 12px 20px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 8px; animation: slideIn 0.3s ease;";
            
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }

    // --- 2. TÌM KIẾM AJAX TỨC THÌ ---
    const searchInput = document.querySelector('input[name="search"]');
    const categorySelect = document.querySelector('select[name="category"]');
    const recipeGrid = document.querySelector('.recipe-grid');
    const countSummary = document.querySelector('.results-count');

    if (!searchInput || !recipeGrid) return;

    let debounceTimer;

    function fetchRecipes() {
        const query = searchInput.value.trim();
        const category = categorySelect ? categorySelect.value : '0';

        recipeGrid.style.opacity = '0.4';

        const url = `index.php?ajax=1&search=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                recipeGrid.innerHTML = data.html;
                recipeGrid.style.opacity = '1';

                if (countSummary && data.total !== undefined) {
                    countSummary.innerHTML = `Đang hiển thị <strong>${data.total}</strong> công thức kết quả`;
                }
            })
            .catch(error => {
                console.error("Lỗi AJAX Search:", error);
                recipeGrid.style.opacity = '1';
            });
    }

    searchInput.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchRecipes, 200);
    });

    if (categorySelect) {
        categorySelect.addEventListener("change", fetchRecipes);
    }
});
</script>

</body>
</html>