
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config.php";

// Kiểm tra người dùng đã đăng nhập chưa
function requireLogin()
{
    global $conn;

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    // Kiểm tra tài khoản còn tồn tại trong Database
    $stmt = $conn->prepare(
        "SELECT id, username, fullname, role
         FROM users
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->execute([
        "id" => $_SESSION["user_id"]
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tài khoản không còn tồn tại
    if (!$user) {
        $_SESSION = [];
        session_regenerate_id(true);

        header("Location: login.php");
        exit;
    }

    // Cập nhật thông tin Session theo Database
    $_SESSION["username"] = $user["username"];
    $_SESSION["fullname"] = $user["fullname"];
    $_SESSION["role"] = $user["role"];
}

// Kiểm tra quyền quản trị viên
function requireAdmin()
{
    requireLogin();

    // Quyền đã được cập nhật từ Database
    $role = $_SESSION["role"] ?? "";

    if ($role !== "admin") {
        http_response_code(403);
        die("Bạn không có quyền truy cập trang quản trị.");
    }
}
