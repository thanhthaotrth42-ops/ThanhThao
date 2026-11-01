<?php
require_once "auth.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reply' => 'Vui lòng đăng nhập để sử dụng AI.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_message = trim($data['message'] ?? '');

if (empty($user_message)) {
    echo json_encode(['reply' => 'Tin nhắn rỗng.']);
    exit;
}

// =========================================================================
// THẢO VY SẼ VIẾT CODE Ở ĐÂY
// 1. Kiểm tra Redis Cache xem câu hỏi ($user_message) đã có câu trả lời chưa.
// 2. Nếu có trong Redis -> Lấy ra và trả về ngay.
// 3. Nếu chưa có -> Gọi API OpenAI/Gemini để lấy câu trả lời.
// 4. Lưu câu trả lời từ AI vào Redis Cache.
// 5. (Tùy chọn) Lưu lịch sử chat vào bảng query_logs trong Database ($conn).
// =========================================================================

// Phản hồi tạm thời để bạn test giao diện:
$bot_reply = "Đây là phản hồi mặc định. Hệ thống AI và Redis đang chờ Thảo Vy tích hợp. Câu hỏi của bạn là: " . htmlspecialchars($user_message);

echo json_encode(['reply' => $bot_reply]);
?>