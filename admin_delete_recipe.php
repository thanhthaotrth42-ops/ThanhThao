
<?php
require_once "auth.php";
requireAdmin();

$stmtUser = $conn->prepare(
    "SELECT role FROM users WHERE id = :id LIMIT 1"
);
$stmtUser->execute(["id" => $_SESSION["user_id"]]);
$currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser["role"] !== "admin") {
    http_response_code(403);
    die("Bạn không có quyền truy cập.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Phương thức không hợp lệ.");
}

$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    die("Yêu cầu không hợp lệ.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    die("ID công thức không hợp lệ.");
}

// Lấy thông tin trước khi xóa
$stmt = $conn->prepare(
    "SELECT r.id, r.name, r.ingredients,
            r.instructions, c.name AS category_name
     FROM recipes r
     INNER JOIN categories c
         ON r.category_id = c.id
     WHERE r.id = :id"
);

$stmt->execute(["id" => $id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    http_response_code(404);
    die("Không tìm thấy công thức cần xóa.");
}

// Thực hiện xóa
$stmt = $conn->prepare(
    "DELETE FROM recipes WHERE id = :id"
);
$stmt->execute(["id" => $id]);

// Lưu thông tin đã xóa để hiển thị trên trang chủ
$_SESSION["recipe_notice"] = [
    "type" => "deleted",
    "title" => "Đã xóa công thức thành công!",
    "recipe_id" => $id,
    "recipe_name" => $recipe["name"],
    "details" => [
        [
            "label" => "Tên công thức",
            "old" => $recipe["name"]
        ],
        [
            "label" => "Danh mục",
            "old" => $recipe["category_name"]
        ],
        [
            "label" => "Nguyên liệu",
            "old" => $recipe["ingredients"]
        ],
        [
            "label" => "Hướng dẫn pha chế",
            "old" => $recipe["instructions"]
        ]
    ]
];

header("Location: index.php");
exit;
