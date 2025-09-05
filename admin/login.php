<?php
session_start(); // Bắt đầu session để kiểm tra nếu đã đăng nhập

// Nếu admin đã đăng nhập rồi, chuyển thẳng vào trang dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Chuẩn bị biến để hiển thị thông báo lỗi (nếu có từ URL)
$error_message = '';
if (isset($_GET['error'])) {
    // Dùng htmlspecialchars để tránh lỗi XSS khi hiển thị lỗi
    $error_message = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị</title>
    <?php // Link CSS chung, nhớ dùng ../ để đi ra khỏi thư mục admin ?>
    <link rel="stylesheet" href="../style.css">
    
</head>
<body>
    <div class="login-container">
        <h1>Đăng Nhập Trang Quản Trị</h1>

        <?php
        // Hiển thị thông báo lỗi nếu có
        if (!empty($error_message)) {
            echo '<p class="error-message">' . $error_message . '</p>';
        }
        ?>

        <?php // Form sẽ gửi dữ liệu đến process_login.php bằng phương thức POST ?>
        <form action="process_login.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Đăng Nhập</button>
        </form>

        <?php // Link quay về trang chủ (tùy chọn) ?>
        <p class="back-link"><a href="../index.php">&laquo; Quay về trang chủ</a></p>

    </div>
</body>
</html>