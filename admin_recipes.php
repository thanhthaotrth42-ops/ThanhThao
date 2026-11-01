<?php
require_once "auth.php";
requireAdmin();

// Kiểm tra quyền Admin từ Database
$stmtUser = $conn->prepare(
    "SELECT role
     FROM users
     WHERE id = :id
     LIMIT 1"
);

$stmtUser->execute([
    "id" => $_SESSION["user_id"]
]);

$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser["role"] !== "admin") {
    http_response_code(403);
    die("Bạn không có quyền truy cập.");
}

// Tạo CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Lấy danh sách công thức
$sql = "SELECT
            r.id,
            r.name,
            c.name AS category_name,
            r.created_at
        FROM recipes r
        INNER JOIN categories c
            ON r.category_id = c.id
        ORDER BY r.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hàm hiển thị dữ liệu an toàn
function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý công thức - AI MILK TEA</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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
            
            --tag-bg: #F3F4F6;
            --tag-text: #4B5563;
            
            --danger: #EF4444;
            --danger-bg: #FEF2F2;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg-body);
            color: var(--text-body);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 14.5px;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* HEADER */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
            width: 100%;
        }

        .header-content {
            max-width: 1440px;
            min-height: 72px;
            margin: 0 auto;
            padding: 12px 32px;
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
            flex-shrink: 0;
        }

        header h1 {
            margin: 0;
            color: var(--text-title);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        header p {
            margin: 1px 0 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        /* MAIN CONTENT */
        main {
            max-width: 1440px;
            width: 100%;
            margin: 0 auto;
            padding: 40px 32px 60px;
            flex: 1;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .toolbar h2 {
            color: var(--text-title);
            font-size: clamp(22px, 2.5vw, 28px);
            font-weight: 700;
            letter-spacing: -0.4px;
            margin: 0;
        }

        .toolbar-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 40px;
            padding: 8px 18px;
            border: 1px solid transparent;
            border-radius: var(--radius-md);
            background: var(--primary);
            color: white;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn:hover {
            background: var(--primary-hover);
        }

        .btn-light {
            background: var(--surface);
            border-color: var(--border);
            color: var(--text-title);
        }

        .btn-light:hover {
            background: var(--bg-body);
            border-color: var(--border-hover);
        }

        .summary {
            margin-bottom: 20px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .summary strong {
            color: var(--text-title);
            font-weight: 700;
        }

        /* TABLE DESIGN */
        .table-wrap {
            overflow-x: auto;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        th, td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: var(--bg-body);
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            font-size: 14px;
            color: var(--text-body);
        }

        tbody tr:hover {
            background: #F3F4F6;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .recipe-name {
            font-weight: 600;
            color: var(--text-title);
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 12px;
            background: var(--tag-bg);
            color: var(--tag-text);
            font-size: 12.5px;
            font-weight: 500;
        }

        /* ACTION BUTTONS */
        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-title);
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
        }

        .action-link:hover {
            background: var(--bg-body);
            border-color: var(--border-hover);
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: var(--radius-sm);
            padding: 6px 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
        }

        .delete-btn:hover {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        .empty {
            text-align: center;
            padding: 48px;
            color: var(--text-muted);
            font-size: 15px;
        }

        /* FOOTER */
        footer {
            padding: 24px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            border-top: 1px solid var(--border);
            background: var(--surface);
        }

        @media (max-width: 650px) {
            .header-content {
                padding: 12px 20px;
            }

            main {
                padding: 24px 20px 40px;
            }

            .toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .toolbar-links {
                width: 100%;
            }

            .btn {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-content">
        <div class="brand-wrap">
            <div class="brand-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <div>
                <h1>AI MILK TEA</h1>
                <p>Quản lý công thức trà sữa</p>
            </div>
        </div>
    </div>
</header>

<main>

    <div class="toolbar">
        <h2>Danh sách công thức</h2>

        <div class="toolbar-links">
            <a class="btn" href="admin_add_recipe.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Thêm công thức
            </a>

            <a class="btn btn-light" href="admin.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Quay lại Admin
            </a>
        </div>
    </div>

    <p class="summary">
        Tổng số công thức hiện có: <strong><?= count($recipes) ?></strong>
    </p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Tên công thức</th>
                    <th>Danh mục</th>
                    <th>Ngày tạo</th>
                    <th style="width: 200px;">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($recipes as $recipe): ?>
                    <tr>
                        <td>
                            #<?= (int)$recipe["id"] ?>
                        </td>

                        <td class="recipe-name">
                            <?= e($recipe["name"]) ?>
                        </td>

                        <td>
                            <span class="category-badge">
                                <?= e($recipe["category_name"]) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($recipe["created_at"] ?? "") ?>
                        </td>

                        <td>
                            <div class="actions">
                                <a
                                    class="action-link"
                                    href="detail.php?id=<?= (int)$recipe["id"] ?>"
                                >
                                    Xem
                                </a>

                                <a
                                    class="action-link"
                                    href="admin_edit_recipe.php?id=<?= (int)$recipe["id"] ?>"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="admin_delete_recipe.php"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa công thức này?');"
                                    style="margin: 0;"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$recipe["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["csrf_token"]) ?>"
                                    >

                                    <button
                                        class="delete-btn"
                                        type="submit"
                                    >
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($recipes)): ?>
                    <tr>
                        <td colspan="5" class="empty">
                            Chưa có công thức nào trong hệ thống.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<footer>
    AI MILK TEA - Đồ án nhóm Chủ đề 12
</footer>

</body>
</html>