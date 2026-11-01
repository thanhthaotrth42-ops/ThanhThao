<?php
require_once "auth.php";
requireAdmin();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$stmtUser = $conn->prepare(
    "SELECT role FROM users WHERE id = :id LIMIT 1"
);
$stmtUser->execute(["id" => $_SESSION["user_id"]]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser["role"] !== "admin") {
    http_response_code(403);
    die("Bạn không có quyền truy cập.");
}

$categories = $conn->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$error = "";
$name = "";
$category_id = 0;
$ingredients = "";
$instructions = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        die("Yêu cầu không hợp lệ.");
    }

    $name = trim($_POST["name"] ?? "");
    $category_id = (int)($_POST["category_id"] ?? 0);
    $ingredients = trim($_POST["ingredients"] ?? "");
    $instructions = trim($_POST["instructions"] ?? "");

    $validCategoryIds = array_map(
        "intval",
        array_column($categories, "id")
    );

    if ($name === "" || $ingredients === "" ||
        $instructions === "" || $category_id <= 0) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } elseif (mb_strlen($name) > 150) {
        $error = "Tên công thức không được quá 150 ký tự.";
    } elseif (!in_array($category_id, $validCategoryIds, true)) {
        $error = "Danh mục không hợp lệ.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO recipes
             (category_id, name, ingredients, instructions)
             VALUES
             (:category_id, :name, :ingredients, :instructions)"
        );

        $stmt->execute([
            "category_id" => $category_id,
            "name" => $name,
            "ingredients" => $ingredients,
            "instructions" => $instructions
        ]);

        $categoryName = "";

        foreach ($categories as $category) {
            if ((int)$category["id"] === $category_id) {
                $categoryName = $category["name"];
                break;
            }
        }

        $_SESSION["recipe_notice"] = [
            "type" => "added",
            "title" => "Đã thêm công thức thành công!",
            "recipe_id" => (int)$conn->lastInsertId(),
            "recipe_name" => $name,
            "details" => [
                ["label" => "Tên công thức", "new" => $name],
                ["label" => "Danh mục", "new" => $categoryName],
                ["label" => "Nguyên liệu", "new" => $ingredients],
                ["label" => "Hướng dẫn pha chế", "new" => $instructions]
            ]
        ];

        header("Location: index.php");
        exit;
    }
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm công thức - AI MILK TEA</title>

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
            
            --danger: #EF4444;
            --danger-bg: #FEF2F2;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            
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
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
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
            max-width: 1000px;
            min-height: 68px;
            margin: 0 auto;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
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
        }

        header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 12.5px;
        }

        /* MAIN CONTENT */
        main {
            max-width: 720px;
            width: 100%;
            margin: 36px auto;
            padding: 0 24px;
            flex: 1;
        }

        .form-card {
            background: var(--surface);
            padding: 32px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .form-card h2 {
            margin: 0 0 6px;
            color: var(--text-title);
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.4px;
        }

        .form-intro {
            margin: 0 0 24px;
            color: var(--text-muted);
            font-size: 13.5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 13.5px;
            color: var(--text-title);
            margin-bottom: 6px;
        }

        input[type="text"], select, textarea {
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

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        /* BUTTONS */
        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 28px;
        }

        button[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 22px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        button[type="submit"]:hover {
            background: var(--primary-hover);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--text-title);
            font-size: 13.5px;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-btn:hover {
            background: var(--bg-body);
            border-color: var(--border-hover);
        }

        /* ERROR */
        .error {
            padding: 12px 14px;
            margin-bottom: 20px;
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: var(--radius-md);
            font-size: 13.5px;
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

        @media (max-width: 600px) {
            main {
                margin: 20px auto;
                padding: 0 16px;
            }

            .form-card {
                padding: 22px;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            button[type="submit"], .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="brand-wrap">
            <div class="brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <div>
                <h1>AI MILK TEA</h1>
            </div>
        </div>
        <p>Thêm công thức trà sữa mới</p>
    </div>
</header>

<main>
    <div class="form-card">
        <h2>Thêm công thức mới</h2>
        <p class="form-intro">Bổ sung công thức trà sữa mới vào cơ sở dữ liệu hệ thống.</p>

        <?php if ($error !== ""): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">

            <div class="form-group">
                <label for="name">Tên công thức</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    placeholder="Ví dụ: Trà sữa Trân châu Đường đen"
                    value="<?= e($name) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="category_id">Danh mục</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category["id"] ?>"
                            <?= $category_id === (int)$category["id"] ? "selected" : "" ?>>
                            <?= e($category["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="ingredients">Nguyên liệu</label>
                <textarea
                    id="ingredients"
                    name="ingredients"
                    placeholder="Nhập chi tiết các nguyên liệu và định lượng..."
                    required
                ><?= e($ingredients) ?></textarea>
            </div>

            <div class="form-group">
                <label for="instructions">Hướng dẫn pha chế</label>
                <textarea
                    id="instructions"
                    name="instructions"
                    placeholder="Nhập hướng dẫn các bước pha chế..."
                    required
                ><?= e($instructions) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit">Lưu công thức</button>
                <a class="back-btn" href="index.php">← Quay lại trang chủ</a>
            </div>
        </form>
    </div>
</main>

<footer>
    AI MILK TEA - Đồ án nhóm Chủ đề 12
</footer>

</body>
</html>