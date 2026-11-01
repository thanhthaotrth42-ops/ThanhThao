<?php
require_once "auth.php";
requireLogin(); // Yêu cầu đăng nhập mới được chat
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trợ lý AI - AI MILK TEA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #F9FAFB;
            --surface: #FFFFFF;
            --border: #E5E7EB;
            --primary: #2563EB;
            --text-title: #111827;
            --text-body: #374151;
            --radius-lg: 12px;
        }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--bg-body); height: 100vh; display: flex; flex-direction: column; }
        header { background: var(--surface); padding: 16px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .back-btn { text-decoration: none; color: var(--primary); font-weight: 600; font-size: 14px; }
        .chat-container { flex: 1; max-width: 800px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; background: var(--surface); border-left: 1px solid var(--border); border-right: 1px solid var(--border); }
        .chat-box { flex: 1; padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }
        .msg { max-width: 75%; padding: 12px 16px; border-radius: var(--radius-lg); font-size: 14.5px; line-height: 1.5; }
        .msg.bot { align-self: flex-start; background: #F3F4F6; color: var(--text-title); border-bottom-left-radius: 4px; }
        .msg.user { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 4px; }
        .input-area { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; gap: 12px; background: var(--surface); }
        input[type="text"] { flex: 1; padding: 12px 16px; border: 1px solid var(--border); border-radius: 8px; outline: none; font-size: 14px; }
        button { padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="back-btn">← Quay lại thư viện</a>
    <strong style="color: var(--text-title);">Trợ lý Pha chế AI</strong>
    <div style="width: 100px;"></div>
</header>

<div class="chat-container">
    <div class="chat-box" id="chatBox">
        <div class="msg bot">Chào <strong><?= htmlspecialchars($_SESSION["fullname"]) ?></strong>! Tôi có thể giúp gì cho bạn hôm nay? (Ví dụ: Gợi ý trà sữa ít ngọt, Cách làm lục trà trân châu...)</div>
    </div>
    
    <div class="input-area">
        <input type="text" id="userInput" placeholder="Nhập câu hỏi của bạn..." onkeypress="if(event.key === 'Enter') sendMessage()">
        <button onclick="sendMessage()">Gửi</button>
    </div>
</div>

<script>
async function sendMessage() {
    const input = document.getElementById('userInput');
    const chatBox = document.getElementById('chatBox');
    const text = input.value.trim();
    if (!text) return;

    chatBox.innerHTML += `<div class="msg user">${text}</div>`;
    input.value = '';
    chatBox.scrollTop = chatBox.scrollHeight;

    // Hiển thị trạng thái đang gõ
    const typingId = "typing-" + Date.now();
    chatBox.innerHTML += `<div class="msg bot" id="${typingId}">Đang suy nghĩ...</div>`;
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        const response = await fetch('api_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        });
        const data = await response.json();
        
        document.getElementById(typingId).remove();
        chatBox.innerHTML += `<div class="msg bot">${data.reply}</div>`;
        chatBox.scrollTop = chatBox.scrollHeight;
    } catch (error) {
        document.getElementById(typingId).innerText = "Lỗi kết nối. Vui lòng thử lại.";
        document.getElementById(typingId).style.color = "red";
    }
}
</script>
</body>
</html>