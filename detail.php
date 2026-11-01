<?php
require_once "config.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    die("Mã công thức không hợp lệ.");
}

$sql = "SELECT recipes.id,
               recipes.name,
               recipes.ingredients,
               recipes.instructions,
               categories.name AS category_name
        FROM recipes
        INNER JOIN categories
            ON recipes.category_id = categories.id
        WHERE recipes.id = :id";

$stmt = $conn->prepare($sql);
$stmt->execute(["id" => $id]);

$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    http_response_code(404);
    die("Không tìm thấy công thức.");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($recipe["name"]) ?> | AI MILK TEA</title>

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
            max-width: 900px;
            width: 100%;
            margin: 36px auto;
            padding: 0 24px;
            flex: 1;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
            color: var(--text-muted);
            font-size: 13.5px;
            font-weight: 500;
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            box-shadow: var(--shadow-sm);
        }

        .back:hover {
            color: var(--text-title);
            background: var(--bg-body);
            border-color: var(--border-hover);
            text-decoration: none;
        }

        .detail {
            background: var(--surface);
            padding: 36px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .detail-header {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .detail h2 {
            margin: 0 0 10px;
            color: var(--text-title);
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            line-height: 1.3;
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 16px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 12.5px;
            font-weight: 600;
        }

        .section-block {
            margin-top: 24px;
        }

        .section-title {
            margin: 0 0 10px;
            color: var(--text-title);
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-content {
            color: var(--text-body);
            font-size: 15px;
            line-height: 1.7;
            background: var(--bg-body);
            padding: 18px 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
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

            .detail {
                padding: 22px;
            }

            .detail h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-content">
        <div class="brand-wrap">
            <div class="brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"></path><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path></svg>
            </div>
            <div>
                <h1>AI MILK TEA</h1>
            </div>
        </div>
        <p>Chi tiết công thức pha chế</p>
    </div>
</header>

<main>
    <a class="back" href="index.php">
        ← Quay lại danh sách
    </a>

    <div class="detail">
        <div class="detail-header">
            <h2>
                <?= htmlspecialchars($recipe["name"]) ?>
            </h2>

            <span class="category-badge">
                Danh mục: <?= htmlspecialchars($recipe["category_name"]) ?>
            </span>
        </div>

        <div class="section-block">
            <div class="section-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Nguyên liệu
            </div>
            <div class="section-content">
                <?= nl2br(htmlspecialchars($recipe["ingredients"])) ?>
            </div>
        </div>

        <div class="section-block">
            <div class="section-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                Hướng dẫn pha chế
            </div>
            <div class="section-content">
                <?= nl2br(htmlspecialchars($recipe["instructions"])) ?>
            </div>
        </div>
    </div>
</main>

<footer>
    AI MILK TEA - Đồ án nhóm Chủ đề 12
</footer>

</body>
</html>