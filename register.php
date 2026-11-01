<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config.php";

$error = "";
$success = "";
$username = "";
$fullname = "";

// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

// Tạo CSRF token
if (empty($_SESSION["register_csrf_token"])) {
    $_SESSION["register_csrf_token"] = bin2hex(random_bytes(32));
}

// Hàm hiển thị dữ liệu an toàn
function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Xử lý đăng ký
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $fullname = trim($_POST["fullname"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["register_csrf_token"], $token)
    ) {
        $error = "Phiên đăng ký không hợp lệ. Vui lòng tải lại trang.";
    }

    elseif (
        $username === "" ||
        $fullname === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    }

    elseif (
        mb_strlen($username) > 50 ||
        mb_strlen($fullname) > 100
    ) {
        $error = "Tên đăng nhập hoặc họ tên quá dài.";
    }

    elseif (
        !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)
    ) {
        $error = "Tên đăng nhập phải có từ 3 đến 50 ký tự, "
               . "chỉ gồm chữ không dấu, số hoặc dấu gạch dưới.";
    }

    elseif (strlen($password) < 8) {
        $error = "Mật khẩu phải có ít nhất 8 ký tự.";
    }

    elseif (strlen($password) > 72) {
        $error = "Mật khẩu không được vượt quá 72 byte.";
    }

    elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    }

    else {

        // Kiểm tra tên đăng nhập trùng
        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = :username
             LIMIT 1"
        );

        $check->execute([
            "username" => $username
        ]);

        if ($check->fetch()) {
            $error = "Tên đăng nhập đã tồn tại.";
        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                 (username, password, fullname, role)
                 VALUES
                 (:username, :password, :fullname, 'user')"
            );

            try {

                $stmt->execute([
                    "username" => $username,
                    "password" => $hashedPassword,
                    "fullname" => $fullname
                ]);

                $success = "Đăng ký tài khoản thành công! "
                         . "Bạn có thể đăng nhập ngay.";

                $username = "";
                $fullname = "";

                // Tạo token mới sau khi đăng ký thành công
                $_SESSION["register_csrf_token"] =
                    bin2hex(random_bytes(32));

            } catch (PDOException $e) {

                error_log($e->getMessage());

                if ($e->getCode() === "23000") {
                    $error = "Tên đăng nhập đã tồn tại "
                           . "hoặc dữ liệu bị trùng.";
                } else {
                    $error = "Không thể đăng ký. "
                           . "Vui lòng thử lại.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - AI MILK TEA</title>

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
            --success: #10B981;
            --success-bg: #ECFDF5;
            
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
            font-weight: 500;
            transition: var(--transition);
        }

        a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        /* HEADER */
        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            text-align: center;
        }

        .brand-wrap {
            display: inline-flex;
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
        }

        .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-title);
            letter-spacing: -0.3px;
        }

        /* CONTAINER CARD */
        .main-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            flex: 1;
        }

        .container {
            width: 100%;
            max-width: 440px;
            padding: 32px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-header h2 {
            margin: 0 0 6px;
            color: var(--text-title);
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.4px;
        }

        .auth-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13.5px;
        }

        /* INPUTS & LABELS */
        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13.5px;
            color: var(--text-title);
        }

        input[type="text"],
        input[type="password"] {
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

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* BUTTON */
        button[type="submit"] {
            width: 100%;
            padding: 11px;
            margin-top: 8px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 14.5px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        button[type="submit"]:hover {
            background: var(--primary-hover);
        }

        /* MESSAGES */
        .message {
            padding: 12px 14px;
            margin-bottom: 20px;
            border-radius: var(--radius-md);
            font-size: 13.5px;
            line-height: 1.5;
        }

        .error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* LINKS */
        .links {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            font-size: 13.5px;
        }

        .links p {
            margin: 0 0 10px;
            color: var(--text-muted);
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 13px;
        }

        .back-home:hover {
            color: var(--text-title);
            text-decoration: none;
        }

        /* FOOTER */
        footer {
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            border-top: 1px solid var(--border);
            background: var(--surface);
        }

        @media (max-width: 480px) {
            .container {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="brand-wrap">
        <div class="brand-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"></path><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"></path></svg>
        </div>
        <span class="brand-name">AI MILK TEA</span>
    </div>
</header>

<div class="main-wrapper">
    <div class="container">

        <div class="auth-header">
            <h2>Tạo tài khoản</h2>
            <p>Đăng ký để trải nghiệm đầy đủ tính năng AI MILK TEA</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="message error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="message success">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION["register_csrf_token"]) ?>"
            >

            <div class="form-group">
                <label for="fullname">Họ và tên</label>
                <input
                    type="text"
                    id="fullname"
                    name="fullname"
                    placeholder="Ví dụ: Nguyễn Văn A"
                    maxlength="100"
                    value="<?= e($fullname) ?>"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Chỉ dùng chữ không dấu, số, _"
                    minlength="3"
                    maxlength="50"
                    pattern="[a-zA-Z0-9_]{3,50}"
                    title="Chỉ dùng chữ không dấu, số hoặc dấu gạch dưới."
                    value="<?= e($username) ?>"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Ít nhất 8 ký tự"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Nhập lại mật khẩu"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit">Đăng ký tài khoản</button>

        </form>

        <div class="links">
            <p>
                Đã có tài khoản?
                <a href="login.php">Đăng nhập ngay</a>
            </p>

            <a class="back-home" href="index.php">
                ← Quay về trang chủ
            </a>
        </div>

    </div>
</div>

<footer>
    AI MILK TEA - Đồ án nhóm Chủ đề 12
</footer>

</body>
</html>