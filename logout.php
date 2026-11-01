
<?php
session_start();

// Xóa toàn bộ dữ liệu phiên đăng nhập
$_SESSION = [];

// Xóa cookie phiên nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hủy phiên đăng nhập
session_destroy();

// Chuyển về trang đăng nhập
header("Location: login.php");
exit;
